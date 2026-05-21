<?php

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| HANDLE SIGNUP
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (
        empty($fullName) ||
        empty($email) ||
        empty($password) ||
        empty($confirm)
    ) {

        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK IF EMAIL EXISTS
        |--------------------------------------------------------------------------
        */

        $check = $db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $check->execute([$email]);

        if ($check->fetch()) {

            $error = "Email already exists.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | CREATE ACCOUNT
            |--------------------------------------------------------------------------
            */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $db->prepare("
                INSERT INTO users
                (
                    full_name,
                    email,
                    password,
                    role,
                    is_active,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'Editor',
                    1,
                    NOW()
                )
            ");

            $stmt->execute([
                $fullName,
                $email,
                $hashedPassword
            ]);

            $success = "Account created successfully. You can now login.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Create Account | Job Aggregator
    </title>

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            font-family:'Inter',sans-serif;

            min-height:100vh;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff 0%,
                    #dbeafe 35%,
                    #e0f2fe 100%
                );

            display:flex;
            align-items:center;
            justify-content:center;

            padding:30px;
            overflow-x:hidden;
            position:relative;
        }

        /*
        |--------------------------------------------------------------------------
        | BACKGROUND
        |--------------------------------------------------------------------------
        */

        .bg-shape{

            position:absolute;
            border-radius:50%;
            filter:blur(80px);
            opacity:0.4;
            z-index:0;
        }

        .shape-one{

            width:320px;
            height:320px;
            background:#60a5fa;

            top:-80px;
            left:-80px;
        }

        .shape-two{

            width:280px;
            height:280px;
            background:#818cf8;

            bottom:-80px;
            right:-60px;
        }

        /*
        |--------------------------------------------------------------------------
        | CARD
        |--------------------------------------------------------------------------
        */

        .signup-card{

            width:100%;
            max-width:520px;

            background:rgba(255,255,255,0.92);

            backdrop-filter:blur(20px);

            border:1px solid rgba(255,255,255,0.7);

            border-radius:32px;

            overflow:hidden;

            box-shadow:
                0 25px 60px rgba(37,99,235,0.12);

            position:relative;
            z-index:5;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .signup-header{

            padding:45px 40px 35px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb 0%,
                    #3b82f6 45%,
                    #6366f1 100%
                );

            color:#fff;
            text-align:center;
            position:relative;
        }

        .signup-header::after{

            content:'';

            position:absolute;
            bottom:-60px;
            left:-40px;

            width:180px;
            height:180px;

            background:rgba(255,255,255,0.08);

            border-radius:50%;
        }

        .logo-box{

            width:90px;
            height:90px;

            margin:0 auto 22px;

            border-radius:28px;

            background:rgba(255,255,255,0.18);

            display:flex;
            align-items:center;
            justify-content:center;

            backdrop-filter:blur(10px);

            border:1px solid rgba(255,255,255,0.25);
        }

        .logo-box i{

            font-size:40px;
            color:#fff;
        }

        .signup-header h2{

            font-size:30px;
            font-weight:800;
            margin-bottom:8px;
        }

        .signup-header p{

            margin:0;
            color:rgba(255,255,255,0.88);
            font-size:15px;
        }

        /*
        |--------------------------------------------------------------------------
        | BODY
        |--------------------------------------------------------------------------
        */

        .signup-body{

            padding:40px;
        }

        .form-label{

            font-size:14px;
            font-weight:700;
            color:#1e293b;
            margin-bottom:10px;
        }

        .input-group{

            margin-bottom:22px;
        }

        .form-icon{

            width:58px;
            border:none;

            background:#eff6ff;

            display:flex;
            align-items:center;
            justify-content:center;

            border-radius:16px 0 0 16px;
        }

        .form-icon i{

            color:#2563eb;
            font-size:18px;
        }

        .form-control{

            border:none;
            background:#f8fafc;

            height:58px;

            font-size:15px;

            border-radius:0 16px 16px 0;

            box-shadow:none;
        }

        .form-control:focus{

            background:#fff;

            border:none;

            box-shadow:
                0 0 0 4px rgba(37,99,235,0.12);
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD STRENGTH
        |--------------------------------------------------------------------------
        */

        .password-strength{

            margin-top:-10px;
            margin-bottom:20px;
        }

        .progress{

            height:8px;
            border-radius:50px;
            background:#e2e8f0;
        }

        .progress-bar{

            border-radius:50px;
            transition:0.3s;
        }

        .password-hints{

            margin-top:10px;
            font-size:13px;
            color:#64748b;
        }

        .password-hints div{

            margin-bottom:5px;
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .btn-signup{

            height:58px;

            border:none;
            border-radius:18px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4f46e5
                );

            color:#fff;

            font-size:16px;
            font-weight:700;

            transition:0.3s;

            box-shadow:
                0 12px 25px rgba(37,99,235,0.25);
        }

        .btn-signup:hover{

            transform:translateY(-2px);

            background:
                linear-gradient(
                    135deg,
                    #1d4ed8,
                    #4338ca
                );

            color:#fff;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERTS
        |--------------------------------------------------------------------------
        */

        .alert{

            border:none;
            border-radius:18px;

            padding:16px 18px;

            font-size:14px;
            font-weight:500;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .bottom-links{

            text-align:center;
            margin-top:25px;
        }

        .bottom-links a{

            color:#2563eb;
            text-decoration:none;
            font-weight:700;
        }

        .bottom-links a:hover{

            color:#1d4ed8;
        }

        .footer-text{

            margin-top:18px;

            text-align:center;

            font-size:13px;
            color:#94a3b8;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media(max-width:576px){

            body{
                padding:18px;
            }

            .signup-header{

                padding:35px 25px 30px;
            }

            .signup-body{

                padding:28px 22px;
            }

            .signup-header h2{

                font-size:24px;
            }
        }

    </style>

</head>

<body>

<!-- BACKGROUND -->

<div class="bg-shape shape-one"></div>
<div class="bg-shape shape-two"></div>

<!-- CARD -->

<div class="signup-card">

    <!-- HEADER -->

    <div class="signup-header">

        <div class="logo-box">

            <i class="bi bi-person-plus-fill"></i>

        </div>

        <h2>
            Create Account
        </h2>

        <p>
            Uganda Job Aggregator Administration Portal
        </p>

    </div>

    <!-- BODY -->

    <div class="signup-body">

        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>

        <!-- SUCCESS -->

        <?php if (!empty($success)): ?>

            <div class="alert alert-success">

                <i class="bi bi-check-circle-fill me-2"></i>

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>

        <!-- FORM -->

        <form method="POST">

            <!-- FULL NAME -->

            <label class="form-label">

                Full Name

            </label>

            <div class="input-group">

                <span class="form-icon">

                    <i class="bi bi-person-fill"></i>

                </span>

                <input
                    type="text"
                    name="full_name"
                    class="form-control"
                    placeholder="Enter your full name"
                    required
                >

            </div>

            <!-- EMAIL -->

            <label class="form-label">

                Email Address

            </label>

            <div class="input-group">

                <span class="form-icon">

                    <i class="bi bi-envelope-fill"></i>

                </span>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    required
                >

            </div>

            <!-- PASSWORD -->

            <label class="form-label">

                Password

            </label>

            <div class="input-group">

                <span class="form-icon">

                    <i class="bi bi-lock-fill"></i>

                </span>

                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="Create password"
                    required
                >

            </div>

            <!-- PASSWORD STRENGTH -->

            <div class="password-strength">

                <div class="progress">

                    <div
                        class="progress-bar"
                        id="strengthBar"
                        style="width:0%"
                    ></div>

                </div>

                <div class="password-hints">

                    <div id="lengthCheck">
                        • Minimum 8 characters
                    </div>

                    <div id="numberCheck">
                        • Include at least one number
                    </div>

                    <div id="specialCheck">
                        • Include a special character
                    </div>

                </div>

            </div>

            <!-- CONFIRM PASSWORD -->

            <label class="form-label">

                Confirm Password

            </label>

            <div class="input-group">

                <span class="form-icon">

                    <i class="bi bi-shield-lock-fill"></i>

                </span>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirmPassword"
                    class="form-control"
                    placeholder="Confirm password"
                    required
                >

            </div>

            <!-- BUTTON -->

            <button
                type="submit"
                class="btn btn-signup w-100"
            >

                <i class="bi bi-person-check-fill me-2"></i>

                Create Account

            </button>

        </form>

        <!-- LINKS -->

        <div class="bottom-links">

            Already have an account?

            <a href="signin.php">

                Login Here

            </a>

        </div>

        <!-- FOOTER -->

        <div class="footer-text">

            © <?= date('Y') ?>

            Uganda Job Aggregator System

        </div>

    </div>

</div>

<script>

    const passwordInput =
        document.getElementById('password');

    const strengthBar =
        document.getElementById('strengthBar');

    passwordInput.addEventListener('input', function(){

        const password = this.value;

        let strength = 0;

        const hasLength = password.length >= 8;
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        const hasUpper = /[A-Z]/.test(password);

        if(hasLength) strength += 25;
        if(hasNumber) strength += 25;
        if(hasSpecial) strength += 25;
        if(hasUpper) strength += 25;

        strengthBar.style.width = strength + '%';

        if(strength < 50){

            strengthBar.className =
                'progress-bar bg-danger';

        } else if(strength < 75){

            strengthBar.className =
                'progress-bar bg-warning';

        } else {

            strengthBar.className =
                'progress-bar bg-success';
        }

        document.getElementById('lengthCheck').style.color =
            hasLength ? '#16a34a' : '#64748b';

        document.getElementById('numberCheck').style.color =
            hasNumber ? '#16a34a' : '#64748b';

        document.getElementById('specialCheck').style.color =
            hasSpecial ? '#16a34a' : '#64748b';
    });

</script>

</body>
</html>