<?php
// =============================================================
// PASSWORD RECOVERY PAGE - No header/nav include (separate design)
// =============================================================

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

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/*
|--------------------------------------------------------------------------
| GET SETTINGS
|--------------------------------------------------------------------------
*/

function getSetting($db, $key, $default = '')
{
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row['setting_value'] ?? $default;
}

$siteName = getSetting($db, 'site_name', 'BISure Jobs');
$smtpHost = getSetting($db, 'smtp_host', 'smtp.gmail.com');
$smtpPort = getSetting($db, 'smtp_port', '587');
$emailFrom = getSetting($db, 'email_from', 'noreply@example.com');

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

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $verificationCode = sprintf('%06d', mt_rand(0, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $_SESSION['recovery_email'] = $email;
            $_SESSION['verification_code'] = $verificationCode;
            $_SESSION['code_expiry'] = $expiry;

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = 'byaruhangaisamelk@gmail.com';
                $mail->Password = 'txhu xuhy hzbf oaps';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtpPort;
                $mail->setFrom($emailFrom, $siteName);
                $mail->addAddress($email, $user['full_name']);
                $mail->isHTML(true);
                $mail->Subject = "$siteName Password Reset Code";

                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <div style='background: linear-gradient(135deg, #6C5CE7, #00CEC9); padding: 30px; text-align: center;'>
                            <h2 style='color: #fff; margin: 0;'>Password Recovery</h2>
                        </div>
                        <div style='padding: 30px; background: #fff;'>
                            <p>Hello <strong>{$user['full_name']}</strong>,</p>
                            <p>We received a request to reset your password. Use the verification code below:</p>
                            <div style='font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #6C5CE7; text-align: center; padding: 20px; background: #F8F9FE; border-radius: 12px; margin: 20px 0;'>
                                {$verificationCode}
                            </div>
                            <p>This code expires in <strong>30 minutes</strong>.</p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                            <hr style='margin: 20px 0;'>
                            <p style='color: #666;'>Regards,<br><strong>{$siteName}</strong></p>
                        </div>
                    </div>
                ";

                $mail->send();
                header('Location: password_recovery?step=verify');
                exit;

            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to send verification email. Please try again.";
                header('Location: password_recovery');
                exit;
            }
        } else {
            $_SESSION['error'] = "No account found with that email address.";
            header('Location: password_recovery');
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
            $_POST['digit1'] ?? '',
            $_POST['digit2'] ?? '',
            $_POST['digit3'] ?? '',
            $_POST['digit4'] ?? '',
            $_POST['digit5'] ?? '',
            $_POST['digit6'] ?? ''
        ]);

        if (!isset($_SESSION['verification_code']) || !isset($_SESSION['code_expiry'])) {
            $_SESSION['error'] = "Session expired. Please request a new code.";
            header('Location: password_recovery');
            exit;
        }

        $storedCode = $_SESSION['verification_code'];
        $expiry = strtotime($_SESSION['code_expiry']);

        if (time() > $expiry) {
            $_SESSION['error'] = "Verification code has expired. Please request a new one.";
            header('Location: password_recovery');
            exit;
        }

        if ($enteredCode === $storedCode) {
            $_SESSION['verified'] = true;
            header('Location: password_recovery?step=reset');
            exit;
        } else {
            $_SESSION['error'] = "Invalid verification code. Please try again.";
            header('Location: password_recovery?step=verify');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3 - RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    elseif (isset($_POST['reset_password'])) {
        if (!isset($_SESSION['verified']) || !isset($_SESSION['recovery_email'])) {
            header('Location: password_recovery');
            exit;
        }

        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if ($password !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match.";
            header('Location: password_recovery?step=reset');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
            header('Location: password_recovery?step=reset');
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updated = $stmt->execute([$hashedPassword, $_SESSION['recovery_email']]);

        if ($updated) {
            $_SESSION['success'] = "Password updated successfully! You can now login.";
            unset(
                $_SESSION['recovery_email'],
                $_SESSION['verification_code'],
                $_SESSION['code_expiry'],
                $_SESSION['verified']
            );
            header('Location: ../security/signin');
            exit;
        } else {
            $_SESSION['error'] = "Failed to update password. Please try again.";
            header('Location: password_recovery?step=reset');
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
$remaining = 0;

if (isset($_SESSION['code_expiry'])) {
    $remaining = strtotime($_SESSION['code_expiry']) - time();
    if ($remaining > 0) {
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $countdownText = "Code expires in {$minutes}:" . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    } else {
        $countdownText = "Code has expired";
    }
}
?>

<!DOCTYPE html>
<html lang="en-GB" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Reset your password for BISure Jobs Admin Dashboard">
    <title>Password Recovery | <?= htmlspecialchars($siteName) ?></title>

    <!-- CSS Files -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* =========================================================
        GLOBAL VARIABLES - Matching Main Site
        ========================================================= */
        :root {
            --primary: #6C5CE7;
            --primary-dark: #5A4BD1;
            --secondary: #00CEC9;
            --accent: #FD79A8;
            --success: #00B894;
            --warning: #FDCB6E;
            --danger: #FF7675;
            --dark: #2D3436;
            --light: #DFE6E9;
            --white: #FFFFFF;
            --bg-light: #F8F9FE;
            --shadow-sm: 0 2px 10px rgba(108, 92, 231, 0.1);
            --shadow-md: 0 5px 20px rgba(108, 92, 231, 0.15);
            --shadow-lg: 0 10px 30px rgba(108, 92, 231, 0.2);
            --gradient-1: linear-gradient(135deg, #6C5CE7 0%, #00CEC9 100%);
            --gradient-2: linear-gradient(135deg, #FD79A8 0%, #FDCB6E 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(108, 92, 231, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 206, 201, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Recovery Container */
        .recovery-container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Recovery Card */
        .recovery-card {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(108, 92, 231, 0.1);
        }

        /* Header Section */
        .recovery-header {
            background: var(--gradient-1);
            padding: 35px;
            text-align: center;
            position: relative;
            color: var(--white);
        }

        .recovery-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .header-icon i {
            font-size: 32px;
        }

        .recovery-header h2 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .recovery-header p {
            font-size: 13px;
            opacity: 0.9;
            margin: 0;
        }

        /* Body Section */
        .recovery-body {
            padding: 35px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--primary);
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: var(--primary);
            font-size: 18px;
        }

        .form-control-recovery {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #E8E8F0;
            border-radius: 14px;
            font-size: 14px;
            transition: all 0.3s;
            background: var(--bg-light);
        }

        .form-control-recovery:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
            background: var(--white);
        }

        /* Verification Inputs */
        .verification-container {
            text-align: center;
            margin: 20px 0;
        }

        .verification-title {
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .verification-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 15px;
        }

        .verification-digit {
            width: 60px;
            height: 70px;
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            border: 2px solid #E8E8F0;
            border-radius: 14px;
            background: var(--bg-light);
            transition: all 0.3s;
        }

        .verification-digit:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
            background: var(--white);
        }

        .countdown-timer {
            font-size: 13px;
            color: var(--warning);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* Password Requirements */
        .password-requirements {
            margin-top: 10px;
            font-size: 11px;
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #B2BEC3;
            margin-bottom: 5px;
        }

        .req-item.valid {
            color: var(--success);
        }

        /* Alert Styles */
        .alert-modern {
            border: none;
            border-radius: 14px;
            padding: 15px 18px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-danger-modern {
            background: #FEE2E2;
            color: #DC2626;
            border-left: 4px solid #DC2626;
        }

        .alert-success-modern {
            background: #D1FAE5;
            color: #059669;
            border-left: 4px solid #059669;
        }

        /* Buttons */
        .btn-recovery {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-danger {
            background: linear-gradient(135deg, #FF7675, #D63031);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: #E8E8F0;
            color: var(--dark);
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: var(--dark);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .recovery-header {
                padding: 25px;
            }
            
            .recovery-header h2 {
                font-size: 20px;
            }
            
            .recovery-body {
                padding: 25px;
            }
            
            .verification-digit {
                width: 50px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-card">
            <!-- Header Section -->
            <div class="recovery-header">
                <div class="header-icon">
                    <?php if ($step === 'request'): ?>
                        <i class="bi bi-key-fill"></i>
                    <?php elseif ($step === 'verify'): ?>
                        <i class="bi bi-envelope-check-fill"></i>
                    <?php else: ?>
                        <i class="bi bi-lock-fill"></i>
                    <?php endif; ?>
                </div>
                <h2>
                    <?php if ($step === 'request'): ?>
                        Forgot Password?
                    <?php elseif ($step === 'verify'): ?>
                        Verify Code
                    <?php else: ?>
                        Reset Password
                    <?php endif; ?>
                </h2>
                <p><?= htmlspecialchars($siteName) ?> - Secure Password Recovery</p>
            </div>

            <!-- Body Section -->
            <div class="recovery-body">
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert-modern alert-danger-modern">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= htmlspecialchars($_SESSION['error']); ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert-modern alert-success-modern">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?= htmlspecialchars($_SESSION['success']); ?></span>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- STEP 1: Request Reset -->
                <?php if($step === 'request'): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-envelope-fill"></i> Email Address
                            </label>
                            <div class="input-wrapper">
                                <i class="bi bi-envelope input-icon"></i>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="form-control-recovery" 
                                    placeholder="admin@example.com"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <button type="submit" name="request_reset" class="btn-recovery btn-primary">
                            <i class="bi bi-send-fill"></i> Send Verification Code
                        </button>

                        <button type="button" onclick="window.location.href='../'" class="btn-recovery btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Login
                        </button>
                    </form>
                <?php endif; ?>

                <!-- STEP 2: Verify Code -->
                <?php if($step === 'verify'): ?>
                    <form method="POST" id="verifyForm">
                        <div class="verification-container">
                            <div class="verification-title">
                                Enter the 6-digit code sent to your email
                            </div>
                            <div class="verification-inputs">
                                <?php for($i = 1; $i <= 6; $i++): ?>
                                    <input 
                                        type="text" 
                                        name="digit<?= $i ?>" 
                                        maxlength="1" 
                                        class="verification-digit"
                                        inputmode="numeric"
                                        pattern="[0-9]"
                                        required
                                    >
                                <?php endfor; ?>
                            </div>
                            <?php if ($countdownText): ?>
                                <div class="countdown-timer">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span id="countdown"><?= $countdownText ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="verify_code" class="btn-recovery btn-success">
                            <i class="bi bi-check-circle-fill"></i> Verify Code
                        </button>

                        <button type="button" onclick="window.location.href='password_recovery'" class="btn-recovery btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                    </form>
                <?php endif; ?>

                <!-- STEP 3: Reset Password -->
                <?php if($step === 'reset'): ?>
                    <form method="POST" id="resetForm">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-lock-fill"></i> New Password
                            </label>
                            <div class="input-wrapper">
                                <i class="bi bi-lock input-icon"></i>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    class="form-control-recovery" 
                                    placeholder="Create new password"
                                    required
                                >
                                <i class="bi bi-eye-slash password-toggle" style="position: absolute; right: 15px; cursor: pointer;"></i>
                            </div>
                            <div class="password-requirements">
                                <div class="req-item" id="lengthReq">
                                    <i class="bi bi-circle"></i> <span>Minimum 8 characters</span>
                                </div>
                                <div class="req-item" id="numberReq">
                                    <i class="bi bi-circle"></i> <span>At least 1 number</span>
                                </div>
                                <div class="req-item" id="upperReq">
                                    <i class="bi bi-circle"></i> <span>At least 1 uppercase letter</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-shield-lock-fill"></i> Confirm Password
                            </label>
                            <div class="input-wrapper">
                                <i class="bi bi-shield-lock input-icon"></i>
                                <input 
                                    type="password" 
                                    name="confirm_password" 
                                    id="confirmPassword"
                                    class="form-control-recovery" 
                                    placeholder="Confirm your password"
                                    required
                                >
                            </div>
                            <div id="matchMessage" style="font-size: 11px; margin-top: 5px;"></div>
                        </div>

                        <button type="submit" name="reset_password" class="btn-recovery btn-danger">
                            <i class="bi bi-arrow-repeat"></i> Update Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus and navigate between verification inputs
        const inputs = document.querySelectorAll('.verification-digit');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            // Only allow numbers
            input.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        // Password toggle visibility
        const togglePassword = document.querySelector('.password-toggle');
        const password = document.getElementById('password');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }

        // Password validation
        const passwordField = document.getElementById('password');
        const confirmField = document.getElementById('confirmPassword');
        const matchMessage = document.getElementById('matchMessage');
        const lengthReq = document.getElementById('lengthReq');
        const numberReq = document.getElementById('numberReq');
        const upperReq = document.getElementById('upperReq');

        function validatePassword() {
            const pwd = passwordField?.value || '';
            
            const hasLength = pwd.length >= 8;
            const hasNumber = /[0-9]/.test(pwd);
            const hasUpper = /[A-Z]/.test(pwd);
            
            if (lengthReq) {
                updateRequirement(lengthReq, hasLength);
            }
            if (numberReq) {
                updateRequirement(numberReq, hasNumber);
            }
            if (upperReq) {
                updateRequirement(upperReq, hasUpper);
            }
            
            return hasLength && hasNumber && hasUpper;
        }

        function updateRequirement(element, isValid) {
            if (isValid) {
                element.classList.add('valid');
                element.innerHTML = '<i class="bi bi-check-circle-fill"></i> <span>' + element.querySelector('span').innerText + '</span>';
            } else {
                element.classList.remove('valid');
                element.innerHTML = '<i class="bi bi-circle"></i> <span>' + element.querySelector('span').innerText + '</span>';
            }
        }

        function checkPasswordMatch() {
            if (!passwordField || !confirmField) return;
            
            const pwd = passwordField.value;
            const confirm = confirmField.value;
            
            if (confirm.length === 0) {
                matchMessage.innerHTML = '';
                return false;
            }
            
            if (pwd === confirm) {
                matchMessage.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #00B894;"></i> Passwords match';
                matchMessage.style.color = '#00B894';
                return true;
            } else {
                matchMessage.innerHTML = '<i class="bi bi-x-circle-fill" style="color: #FF7675;"></i> Passwords do not match';
                matchMessage.style.color = '#FF7675';
                return false;
            }
        }

        if (passwordField) {
            passwordField.addEventListener('input', () => {
                validatePassword();
                checkPasswordMatch();
            });
        }
        
        if (confirmField) {
            confirmField.addEventListener('input', checkPasswordMatch);
        }

        // Form validation
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                const isValid = validatePassword();
                const doMatch = checkPasswordMatch();
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please create a stronger password (min. 8 characters, include a number and uppercase letter)');
                } else if (!doMatch) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }

        // Countdown timer
        let remaining = <?= $remaining > 0 ? $remaining : 0 ?>;
        const countdownElement = document.getElementById('countdown');
        
        if (countdownElement && remaining > 0) {
            const timer = setInterval(() => {
                if (remaining <= 0) {
                    clearInterval(timer);
                    countdownElement.innerHTML = 'Code has expired';
                    countdownElement.style.color = '#FF7675';
                } else {
                    const minutes = Math.floor(remaining / 60);
                    const seconds = remaining % 60;
                    countdownElement.innerHTML = `Code expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;
                    remaining--;
                }
            }, 1000);
        }
    </script>
</body>
</html>