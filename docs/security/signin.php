<?php
// =============================================================
// LOGIN PAGE - No header/nav include (separate design)
// =============================================================

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
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $setting = $stmt->fetch();
    return $setting['setting_value'] ?? $default;
}

$siteName = getSetting($db, 'site_name', 'BISure Jobs');

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
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid email or password.";
        } elseif ((int)$user['is_active'] !== 1) {
            $error = "Your account has been deactivated. Please contact administrator.";
        } elseif (!password_verify($password, $user['password'])) {
            $error = "Invalid email or password.";
        } else {
            // Update last login
            $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update->execute([$user['id']]);

            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect to dashboard or stored page
            $redirect = $_SESSION['redirect_after_login'] ?? '../index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en-GB" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Secure login portal for BISure Jobs Admin Dashboard">
    <title>Login | <?= htmlspecialchars($siteName) ?></title>

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

        /* Login Container */
        .login-container {
            width: 100%;
            max-width: 1200px;
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

        /* Login Card */
        .login-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(108, 92, 231, 0.1);
        }

        /* Left Side - Brand Section */
        .login-brand {
            background: var(--gradient-1);
            padding: 50px 40px;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .login-brand::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .brand-logo i {
            font-size: 40px;
            color: var(--white);
        }

        .brand-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .brand-description {
            font-size: 15px;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .feature-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .feature-text {
            font-size: 14px;
            font-weight: 500;
        }

        /* Right Side - Form Section */
        .login-form {
            padding: 50px 45px;
            background: var(--white);
        }

        .form-header {
            margin-bottom: 35px;
        }

        .form-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-header p {
            color: #636E72;
            font-size: 14px;
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
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert-danger-modern {
            background: #FEE2E2;
            color: #DC2626;
            border-left: 4px solid #DC2626;
        }

        .alert-danger-modern i {
            font-size: 20px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
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

        .form-control-login {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #E8E8F0;
            border-radius: 14px;
            font-size: 14px;
            transition: all 0.3s;
            background: var(--bg-light);
        }

        .form-control-login:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
            background: var(--white);
        }

        .form-control-login::placeholder {
            color: #B2BEC3;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #B2BEC3;
            transition: 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: var(--gradient-1);
            color: var(--white);
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Links */
        .login-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #E8E8F0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .login-links a {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .login-links a:hover {
            color: var(--primary-dark);
            transform: translateX(3px);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E8E8F0;
            font-size: 12px;
            color: #B2BEC3;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .login-card {
                grid-template-columns: 1fr;
            }
            
            .login-brand {
                display: none;
            }
            
            .login-form {
                padding: 40px 30px;
            }
        }

        @media (max-width: 576px) {
            .login-form {
                padding: 30px 20px;
            }
            
            .form-header h2 {
                font-size: 24px;
            }
            
            .login-links {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .login-links a {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Left Side - Brand Section -->
            <div class="login-brand">
                <div class="brand-logo">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <h1 class="brand-title"><?= htmlspecialchars($siteName) ?></h1>
                <p class="brand-description">
                    Smart recruitment automation platform for scraping jobs, managing subscribers, sending email alerts, and controlling the entire job aggregation system professionally.
                </p>
                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="feature-text">Automated Job Scraping</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-envelope-paper-fill"></i></div>
                        <div class="feature-text">Smart Email Notifications</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="feature-text">Subscriber Management</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                        <div class="feature-text">Secure Admin Authentication</div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Form Section -->
            <div class="login-form">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to continue to your administration dashboard</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-modern alert-danger-modern">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-envelope-fill"></i> Email Address
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control-login" 
                                placeholder="admin@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-lock-fill"></i> Password
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                class="form-control-login" 
                                placeholder="Enter your password"
                                required
                            >
                            <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Secure Login
                    </button>
                </form>

                <div class="login-links">
                    <a href="/jobaggregator/mailing/password_recovery.php">
                        <i class="bi bi-key-fill"></i> Forgot Password?
                    </a>
                    <a href="#">
                        <i class="bi bi-shield-check"></i> Secure Access
                    </a>
                </div>

                <div class="login-footer">
                    <i class="bi bi-c-circle"></i> <?= date('Y') ?> <?= htmlspecialchars($siteName) ?> • All Rights Reserved
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-modern');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>