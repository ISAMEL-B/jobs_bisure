<?php

session_start();

require_once __DIR__ . '/../core/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Africa/Kampala');

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| LOAD PHPMailer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../mailing/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../mailing/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../mailing/PHPMailer/src/SMTP.php';

/*
|--------------------------------------------------------------------------
| GET SETTINGS
|--------------------------------------------------------------------------
*/

function getSetting($db, $key, $default = '')
{
    $stmt = $db->prepare("
        SELECT setting_value
        FROM settings
        WHERE setting_key = ?
        LIMIT 1
    ");

    $stmt->execute([$key]);

    $row = $stmt->fetch();

    return $row['setting_value'] ?? $default;
}

$siteName = getSetting(
    $db,
    'site_name',
    'Job Aggregator'
);

$smtpHost = getSetting(
    $db,
    'smtp_host',
    'smtp.gmail.com'
);

$smtpPort = getSetting(
    $db,
    'smtp_port',
    '587'
);

$emailFrom = getSetting(
    $db,
    'email_from',
    'noreply@example.com'
);

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | STEP 1 - REQUEST RESET
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['request_reset'])) {

        $email = trim($_POST['email']);

        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user) {

            $verificationCode = sprintf(
                '%06d',
                mt_rand(0, 999999)
            );

            $expiry = date(
                'Y-m-d H:i:s',
                strtotime('+30 minutes')
            );

            $_SESSION['recovery_email'] = $email;

            $_SESSION['verification_code'] = $verificationCode;

            $_SESSION['code_expiry'] = $expiry;

            /*
            |--------------------------------------------------------------------------
            | STORE TEMPORARILY IN SESSION
            |--------------------------------------------------------------------------
            */

            $mail = new PHPMailer(true);

            try {

                /*
                |--------------------------------------------------------------------------
                | SMTP SETTINGS
                |--------------------------------------------------------------------------
                */

                $mail->isSMTP();

                $mail->Host = $smtpHost;

                $mail->SMTPAuth = true;

                /*
                |--------------------------------------------------------------------------
                | CHANGE THESE
                |--------------------------------------------------------------------------
                */

                $mail->Username = 'byaruhangaisamelk@gmail.com';

                $mail->Password = 'txhu xuhy hzbf oaps';

                $mail->SMTPSecure =
                    PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port = $smtpPort;

                /*
                |--------------------------------------------------------------------------
                | EMAIL
                |--------------------------------------------------------------------------
                */

                $mail->setFrom(
                    $emailFrom,
                    $siteName
                );

                $mail->addAddress(
                    $email,
                    $user['full_name']
                );

                $mail->isHTML(true);

                $mail->Subject =
                    "$siteName Password Reset Code";

                $mail->Body = "

                    <div style='font-family:Arial,sans-serif;'>

                        <h2 style='color:#2563eb;'>

                            Password Recovery

                        </h2>

                        <p>

                            Hello
                            <strong>{$user['full_name']}</strong>,

                        </p>

                        <p>

                            Your verification code is:

                        </p>

                        <div style='
                            font-size:36px;
                            font-weight:bold;
                            letter-spacing:8px;
                            color:#2563eb;
                            margin:20px 0;
                        '>

                            {$verificationCode}

                        </div>

                        <p>

                            This code expires in
                            30 minutes.

                        </p>

                        <p>

                            If you did not request
                            this password reset,
                            ignore this email.

                        </p>

                        <br>

                        <p>

                            Regards,<br>
                            {$siteName}

                        </p>

                    </div>

                ";

                $mail->send();

                header(
                    'Location: password_recovery.php?step=verify'
                );

                exit;

            } catch (Exception $e) {

                $_SESSION['error'] =
                    "Failed to send verification email.";

                header(
                    'Location: password_recovery.php'
                );

                exit;
            }

        } else {

            $_SESSION['error'] =
                "No account found with that email.";

            header(
                'Location: password_recovery.php'
            );

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2 - VERIFY CODE
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST['verify_code'])) {

        $enteredCode = implode('', [

            $_POST['digit1'],
            $_POST['digit2'],
            $_POST['digit3'],
            $_POST['digit4'],
            $_POST['digit5'],
            $_POST['digit6']

        ]);

        if (
            !isset($_SESSION['verification_code']) ||
            !isset($_SESSION['code_expiry'])
        ) {

            $_SESSION['error'] =
                "Session expired.";

            header(
                'Location: password_recovery.php'
            );

            exit;
        }

        $storedCode =
            $_SESSION['verification_code'];

        $expiry =
            strtotime($_SESSION['code_expiry']);

        if (time() > $expiry) {

            $_SESSION['error'] =
                "Verification code expired.";

            header(
                'Location: password_recovery.php?step=verify'
            );

            exit;
        }

        if ($enteredCode === $storedCode) {

            $_SESSION['verified'] = true;

            header(
                'Location: password_recovery.php?step=reset'
            );

            exit;

        } else {

            $_SESSION['error'] =
                "Invalid verification code.";

            header(
                'Location: password_recovery.php?step=verify'
            );

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3 - RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST['reset_password'])) {

        if (
            !isset($_SESSION['verified']) ||
            !isset($_SESSION['recovery_email'])
        ) {

            header(
                'Location: password_recovery.php'
            );

            exit;
        }

        $password =
            trim($_POST['password']);

        $confirmPassword =
            trim($_POST['confirm_password']);

        if ($password !== $confirmPassword) {

            $_SESSION['error'] =
                "Passwords do not match.";

            header(
                'Location: password_recovery.php?step=reset'
            );

            exit;
        }

        if (strlen($password) < 8) {

            $_SESSION['error'] =
                "Password must be at least 8 characters.";

            header(
                'Location: password_recovery.php?step=reset'
            );

            exit;
        }

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $db->prepare("
            UPDATE users
            SET password = ?
            WHERE email = ?
        ");

        $updated = $stmt->execute([

            $hashedPassword,
            $_SESSION['recovery_email']

        ]);

        if ($updated) {

            $_SESSION['success'] =
                "Password updated successfully.";

            unset(

                $_SESSION['recovery_email'],
                $_SESSION['verification_code'],
                $_SESSION['code_expiry'],
                $_SESSION['verified']

            );

            header(
                'Location: ../security/signin.php'
            );

            exit;

        } else {

            $_SESSION['error'] =
                "Failed to update password.";

            header(
                'Location: password_recovery.php?step=reset'
            );

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| CURRENT STEP
|--------------------------------------------------------------------------
*/

$step = $_GET['step'] ?? 'request';

/*
|--------------------------------------------------------------------------
| COUNTDOWN
|--------------------------------------------------------------------------
*/

$countdownText = '';

if (isset($_SESSION['code_expiry'])) {

    $remaining =
        strtotime($_SESSION['code_expiry']) - time();

    if ($remaining > 0) {

        $minutes = floor($remaining / 60);

        $seconds = $remaining % 60;

        $countdownText =
            "Code expires in {$minutes}:"
            . str_pad($seconds, 2, '0', STR_PAD_LEFT);

    } else {

        $countdownText = "Code expired";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        <?= htmlspecialchars($siteName) ?>

        - Password Recovery

    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        body{

            background:#f1f5f9;
            font-family:Arial,sans-serif;
        }

        .recovery-card{

            max-width:550px;

            margin:60px auto;

            border:none;

            border-radius:28px;

            overflow:hidden;

            box-shadow:
            0 10px 40px rgba(0,0,0,0.08);
        }

        .card-header-custom{

            background:linear-gradient(
                135deg,
                #2563eb,
                #7c3aed
            );

            color:#fff;

            padding:35px;
        }

        .card-body{

            padding:35px;
        }

        .form-control{

            border-radius:14px;

            padding:14px;
        }

        .btn-modern{

            border-radius:14px;

            padding:14px;

            font-weight:600;
        }

        .verification-input{

            display:flex;

            gap:10px;

            justify-content:center;
        }

        .verification-digit{

            width:55px;

            height:60px;

            border:2px solid #cbd5e1;

            border-radius:12px;

            text-align:center;

            font-size:24px;

            font-weight:bold;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card recovery-card">

        <div class="card-header-custom">

            <h2>

                <i class="bi bi-shield-lock-fill me-2"></i>

                <?= htmlspecialchars($siteName) ?>

            </h2>

            <p class="mb-0">

                Secure Password Recovery

            </p>

        </div>

        <div class="card-body">

            <?php if(isset($_SESSION['error'])): ?>

                <div class="alert alert-danger">

                    <?= $_SESSION['error']; ?>

                </div>

                <?php unset($_SESSION['error']); ?>

            <?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>

                <div class="alert alert-success">

                    <?= $_SESSION['success']; ?>

                </div>

                <?php unset($_SESSION['success']); ?>

            <?php endif; ?>

            <?php if($step === 'request'): ?>

                <form method="POST">

                    <div class="text-center mb-4">

                        <i class="bi bi-key-fill
                                  fs-1 text-primary"></i>

                        <h4 class="mt-3">

                            Forgot Password?

                        </h4>

                    </div>

                    <div class="mb-4">

                        <label class="form-label">

                            Email Address

                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        name="request_reset"
                        class="btn btn-primary btn-modern w-100"
                    >

                        Send Verification Code

                    </button>

                    <button
                        type="button"
                        onclick="window.location.href='../security/signin.php'"
                        class="btn btn-secondary btn-modern mt-3 w-100"
                    >
                        Back to Login
                    </button>

                </form>

            <?php endif; ?>

            <?php if($step === 'verify'): ?>

                <form method="POST">

                    <div class="text-center mb-4">

                        <i class="bi bi-envelope-check-fill
                                  fs-1 text-success"></i>

                        <h4 class="mt-3">

                            Verify Code

                        </h4>

                        <small class="text-muted">

                            <?= $countdownText ?>

                        </small>

                    </div>

                    <div class="verification-input mb-4">

                        <?php for($i=1; $i<=6; $i++): ?>

                            <input
                                type="text"
                                name="digit<?= $i ?>"
                                maxlength="1"
                                required
                                class="verification-digit"
                            >

                        <?php endfor; ?>

                    </div>

                    <button
                        type="submit"
                        name="verify_code"
                        class="btn btn-success btn-modern w-100"
                    >

                        Verify Code

                    </button>

                </form>

            <?php endif; ?>

            <?php if($step === 'reset'): ?>

                <form method="POST">

                    <div class="text-center mb-4">

                        <i class="bi bi-lock-fill
                                  fs-1 text-danger"></i>

                        <h4 class="mt-3">

                            Create New Password

                        </h4>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            New Password

                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="mb-4">

                        <label class="form-label">

                            Confirm Password

                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        name="reset_password"
                        class="btn btn-danger btn-modern w-100"
                    >

                        Update Password

                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>

const inputs =
document.querySelectorAll('.verification-digit');

inputs.forEach((input, index) => {

    input.addEventListener('input', () => {

        if (
            input.value.length === 1 &&
            index < inputs.length - 1
        ) {

            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (e) => {

        if (
            e.key === 'Backspace' &&
            input.value === '' &&
            index > 0
        ) {

            inputs[index - 1].focus();
        }
    });
});

</script>

</body>
</html>