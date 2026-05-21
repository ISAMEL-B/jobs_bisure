<?php

session_start();

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| SETTINGS
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

    $setting = $stmt->fetch();

    return $setting['setting_value'] ?? $default;
}

$siteName = getSetting(
    $db,
    'site_name',
    'Uganda Job Aggregator'
);

/*
|--------------------------------------------------------------------------
| HANDLE LOGIN
|--------------------------------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {

        $error = "Please fill in all fields.";

    } else {

        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {

            $error = "Invalid email or password.";

        } elseif ((int)$user['is_active'] !== 1) {

            $error = "Your account has been deactivated.";

        } elseif (!password_verify($password, $user['password'])) {

            $error = "Invalid email or password.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | UPDATE LAST LOGIN
            |--------------------------------------------------------------------------
            */

            $update = $db->prepare("
                UPDATE users
                SET last_login = NOW()
                WHERE id = ?
            ");

            $update->execute([$user['id']]);

            /*
            |--------------------------------------------------------------------------
            | CREATE SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            header("Location: ../index.php");
            exit;
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

        Login | <?= htmlspecialchars($siteName) ?>

    </title>

    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- BOOTSTRAP ICONS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- GOOGLE FONT -->

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

        :root{

            --primary:#2563eb;
            --primary-dark:#1d4ed8;

            --secondary:#7c3aed;

            --bg:#f1f5f9;

            --card:#ffffff;

            --text:#0f172a;

            --muted:#64748b;

            --border:#e2e8f0;

            --input:#f8fafc;

            --danger:#dc2626;

            --success:#059669;
        }

        *{

            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            min-height:100vh;

            font-family:'Inter',sans-serif;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(124,58,237,0.15),
                    transparent 30%
                ),
                radial-gradient(
                    circle at bottom left,
                    rgba(37,99,235,0.15),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );

            display:flex;
            align-items:center;
            justify-content:center;

            padding:40px 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN WRAPPER
        |--------------------------------------------------------------------------
        */

        .login-wrapper{

            width:100%;
            max-width:1100px;

            display:grid;
            grid-template-columns:1fr 480px;

            background:rgba(255,255,255,0.8);

            backdrop-filter:blur(20px);

            border-radius:30px;

            overflow:hidden;

            border:1px solid rgba(255,255,255,0.6);

            box-shadow:
                0 25px 60px rgba(15,23,42,0.12);
        }

        /*
        |--------------------------------------------------------------------------
        | LEFT SIDE
        |--------------------------------------------------------------------------
        */

        .login-left{

            position:relative;

            padding:70px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1e40af,
                    #7c3aed
                );

            color:#fff;

            overflow:hidden;
        }

        .login-left::before{

            content:'';

            position:absolute;

            width:420px;
            height:420px;

            border-radius:50%;

            background:rgba(255,255,255,0.08);

            top:-160px;
            right:-140px;
        }

        .login-left::after{

            content:'';

            position:absolute;

            width:300px;
            height:300px;

            border-radius:50%;

            background:rgba(255,255,255,0.06);

            bottom:-120px;
            left:-100px;
        }

        .brand-area{

            position:relative;
            z-index:2;
        }

        .brand-logo{

            width:85px;
            height:85px;

            border-radius:26px;

            background:rgba(255,255,255,0.15);

            display:flex;
            align-items:center;
            justify-content:center;

            font-size:38px;

            margin-bottom:30px;

            backdrop-filter:blur(10px);
        }

        .brand-title{

            font-size:44px;
            font-weight:800;

            line-height:1.1;

            margin-bottom:18px;
        }

        .brand-description{

            font-size:17px;
            line-height:1.8;

            color:rgba(255,255,255,0.88);

            max-width:520px;
        }

        .feature-list{

            margin-top:45px;

            display:flex;
            flex-direction:column;
            gap:18px;
        }

        .feature-item{

            display:flex;
            align-items:center;
            gap:15px;

            font-size:15px;
        }

        .feature-icon{

            width:42px;
            height:42px;

            border-radius:14px;

            background:rgba(255,255,255,0.15);

            display:flex;
            align-items:center;
            justify-content:center;

            font-size:18px;
        }

        /*
        |--------------------------------------------------------------------------
        | RIGHT SIDE
        |--------------------------------------------------------------------------
        */

        .login-right{

            background:#fff;

            padding:55px 45px;

            display:flex;
            flex-direction:column;
            justify-content:center;
        }

        .login-top{

            margin-bottom:35px;
        }

        .login-top h2{

            font-size:32px;
            font-weight:800;

            color:var(--text);

            margin-bottom:10px;
        }

        .login-top p{

            color:var(--muted);

            margin:0;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert-modern{

            border:none;

            border-radius:18px;

            padding:16px 18px;

            display:flex;
            align-items:center;
            gap:12px;

            margin-bottom:25px;
        }

        .alert-danger-modern{

            background:#fef2f2;

            color:#991b1b;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-label{

            font-size:14px;
            font-weight:600;

            color:#334155;

            margin-bottom:10px;
        }

        .input-group{

            background:var(--input);

            border:1px solid var(--border);

            border-radius:18px;

            overflow:hidden;

            transition:0.3s;
        }

        .input-group:focus-within{

            border-color:#2563eb;

            box-shadow:
                0 0 0 4px rgba(37,99,235,0.12);
        }

        .input-icon{

            width:60px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:#64748b;

            font-size:18px;
        }

        .form-control{

            border:none !important;

            background:transparent !important;

            height:60px;

            font-size:15px;

            box-shadow:none !important;
        }

        .form-control::placeholder{

            color:#94a3b8;
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .btn-login{

            height:60px;

            border:none;

            border-radius:18px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color:#fff;

            font-size:16px;
            font-weight:700;

            transition:0.3s;

            box-shadow:
                0 15px 30px rgba(37,99,235,0.25);
        }

        .btn-login:hover{

            transform:translateY(-2px);

            box-shadow:
                0 20px 35px rgba(37,99,235,0.30);
        }

        /*
        |--------------------------------------------------------------------------
        | LINKS
        |--------------------------------------------------------------------------
        */

        .login-links{

            margin-top:22px;

            display:flex;
            justify-content:space-between;
            align-items:center;

            flex-wrap:wrap;
            gap:10px;
        }

        .login-links a{

            text-decoration:none;

            color:#2563eb;

            font-weight:600;

            transition:0.3s;
        }

        .login-links a:hover{

            color:#1d4ed8;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer-text{

            margin-top:35px;

            text-align:center;

            font-size:13px;

            color:#94a3b8;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media(max-width:992px){

            .login-wrapper{

                grid-template-columns:1fr;
            }

            .login-left{

                display:none;
            }

            .login-right{

                padding:40px 28px;
            }
        }

    </style>

</head>

<body>

<div class="login-wrapper">

    <!-- LEFT PANEL -->

    <div class="login-left">

        <div class="brand-area">

            <div class="brand-logo">

                <i class="bi bi-briefcase-fill"></i>

            </div>

            <h1 class="brand-title">

                <?= htmlspecialchars($siteName) ?>

            </h1>

            <p class="brand-description">

                Smart recruitment automation platform for scraping jobs,
                managing subscribers, sending email alerts,
                and controlling the entire Uganda Job Aggregator system professionally.
            </p>

            <div class="feature-list">

                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-lightning-charge-fill"></i>

                    </div>

                    <div>

                        Automated Job Scraping

                    </div>

                </div>

                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-envelope-paper-fill"></i>

                    </div>

                    <div>

                        Smart Email Notifications

                    </div>

                </div>

                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-people-fill"></i>

                    </div>

                    <div>

                        Subscriber Management

                    </div>

                </div>

                <div class="feature-item">

                    <div class="feature-icon">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>

                    <div>

                        Secure Admin Authentication

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- RIGHT PANEL -->

    <div class="login-right">

        <div class="login-top">

            <h2>

                Welcome Back

            </h2>

            <p>

                Sign in to continue to your administration dashboard.
            </p>

        </div>

        <?php if (!empty($error)): ?>

            <div class="alert-modern alert-danger-modern">

                <i class="bi bi-exclamation-triangle-fill"></i>

                <div>

                    <?= htmlspecialchars($error) ?>

                </div>

            </div>

        <?php endif; ?>

        <!-- LOGIN FORM -->

        <form method="POST">

            <!-- EMAIL -->

            <div class="mb-4">

                <label class="form-label">

                    Email Address

                </label>

                <div class="input-group">

                    <div class="input-icon">

                        <i class="bi bi-envelope-fill"></i>

                    </div>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email address"
                        required
                    >

                </div>

            </div>

            <!-- PASSWORD -->

            <div class="mb-4">

                <label class="form-label">

                    Password

                </label>

                <div class="input-group">

                    <div class="input-icon">

                        <i class="bi bi-lock-fill"></i>

                    </div>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >

                </div>

            </div>

            <!-- BUTTON -->

            <button
                type="submit"
                class="btn btn-login w-100"
            >

                <i class="bi bi-box-arrow-in-right me-2"></i>

                Secure Login

            </button>

        </form>

        <!-- LINKS -->

        <div class="login-links">

            <a href="/jobaggregator/mailing/password_recovery.php">

                <i class="bi bi-key-fill me-1"></i>

                Forgot Password?

            </a>

            <a href="#">

                <i class="bi bi-shield-check me-1"></i>

                Secure Access

            </a>

        </div>

        <!-- FOOTER -->

        <div class="footer-text">

            © <?= date('Y') ?>

            <?= htmlspecialchars($siteName) ?>

            • All Rights Reserved

        </div>

    </div>

</div>

</body>
</html>