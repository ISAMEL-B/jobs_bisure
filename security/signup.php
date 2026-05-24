<?php
// =============================================================
// SIGNUP PAGE - No header/nav include (separate design)
// =============================================================

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

    if (empty($fullName) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Email already exists. Please use a different email or login.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, password, role, is_active, created_at)
                VALUES (?, ?, ?, 'Editor', 1, NOW())
            ");
            $stmt->execute([$fullName, $email, $hashedPassword]);

            $success = "Account created successfully! You can now login.";
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
    <meta name="description" content="Create an account for BISure Jobs Admin Dashboard">
    <title>Create Account | BISure Jobs</title>

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

        /* Signup Container */
        .signup-container {
            width: 100%;
            max-width: 550px;
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

        /* Signup Card */
        .signup-card {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(108, 92, 231, 0.1);
        }

        /* Header Section */
        .signup-header {
            background: var(--gradient-1);
            padding: 40px 35px 35px;
            text-align: center;
            position: relative;
            color: var(--white);
        }

        .signup-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .logo-box i {
            font-size: 40px;
            color: var(--white);
        }

        .signup-header h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .signup-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        /* Body Section */
        .signup-body {
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

        .form-control-signup {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #E8E8F0;
            border-radius: 14px;
            font-size: 14px;
            transition: all 0.3s;
            background: var(--bg-light);
        }

        .form-control-signup:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
            background: var(--white);
        }

        .form-control-signup::placeholder {
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

        /* Password Strength Meter */
        .password-strength {
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .strength-meter {
            height: 6px;
            background: #E8E8F0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 10px;
        }

        .strength-bar.weak { background: var(--danger); width: 25%; }
        .strength-bar.fair { background: var(--warning); width: 50%; }
        .strength-bar.good { background: #4CAF50; width: 75%; }
        .strength-bar.strong { background: var(--success); width: 100%; }

        .password-requirements {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
            font-size: 11px;
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #B2BEC3;
            transition: 0.3s;
        }

        .req-item i {
            font-size: 12px;
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
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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

        /* Signup Button */
        .btn-signup {
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
            margin-top: 10px;
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Links */
        .bottom-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #E8E8F0;
        }

        .bottom-links p {
            font-size: 14px;
            color: #636E72;
            margin: 0;
        }

        .bottom-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .bottom-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #B2BEC3;
        }

        /* Responsive */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .signup-header {
                padding: 30px 25px;
            }
            
            .signup-header h2 {
                font-size: 24px;
            }
            
            .signup-body {
                padding: 25px;
            }
            
            .password-requirements {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <!-- Header Section -->
            <div class="signup-header">
                <div class="logo-box">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h2>Create Account</h2>
                <p>Join the BISure Jobs Administration Portal</p>
            </div>

            <!-- Body Section -->
            <div class="signup-body">
                <!-- Error Alert -->
                <?php if (!empty($error)): ?>
                    <div class="alert-modern alert-danger-modern">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Success Alert -->
                <?php if (!empty($success)): ?>
                    <div class="alert-modern alert-success-modern">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Signup Form -->
                <form method="POST" id="signupForm">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-person-fill"></i> Full Name
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input 
                                type="text" 
                                name="full_name" 
                                class="form-control-signup" 
                                placeholder="Enter your full name"
                                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-envelope-fill"></i> Email Address
                        </label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control-signup" 
                                placeholder="admin@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password -->
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
                                class="form-control-signup" 
                                placeholder="Create a strong password"
                                required
                            >
                            <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                        </div>
                        
                        <!-- Password Strength Meter -->
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-requirements" id="passwordRequirements">
                                <div class="req-item" id="lengthReq">
                                    <i class="bi bi-circle"></i> <span>Min. 8 characters</span>
                                </div>
                                <div class="req-item" id="numberReq">
                                    <i class="bi bi-circle"></i> <span>At least 1 number</span>
                                </div>
                                <div class="req-item" id="upperReq">
                                    <i class="bi bi-circle"></i> <span>At least 1 uppercase</span>
                                </div>
                                <div class="req-item" id="specialReq">
                                    <i class="bi bi-circle"></i> <span>At least 1 special character</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
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
                                class="form-control-signup" 
                                placeholder="Confirm your password"
                                required
                            >
                            <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                        <div id="matchMessage" style="font-size: 11px; margin-top: 5px;"></div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-signup" id="submitBtn">
                        <i class="bi bi-person-check-fill"></i>
                        Create Account
                    </button>
                </form>

                <!-- Links -->
                <div class="bottom-links">
                    <p>Already have an account? <a href="signin">Sign In Here</a></p>
                </div>

                <!-- Footer -->
                <div class="footer-text">
                    <i class="bi bi-c-circle"></i> <?= date('Y') ?> BISure Jobs • All Rights Reserved
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const toggleConfirm = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('submitBtn');
        const matchMessage = document.getElementById('matchMessage');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        if (toggleConfirm) {
            toggleConfirm.addEventListener('click', function() {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }

        // Password strength checker
        const strengthBar = document.getElementById('strengthBar');
        const lengthReq = document.getElementById('lengthReq');
        const numberReq = document.getElementById('numberReq');
        const upperReq = document.getElementById('upperReq');
        const specialReq = document.getElementById('specialReq');

        function checkPasswordStrength() {
            const pwd = password.value;
            
            const hasLength = pwd.length >= 8;
            const hasNumber = /[0-9]/.test(pwd);
            const hasUpper = /[A-Z]/.test(pwd);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
            
            let strength = 0;
            if (hasLength) strength++;
            if (hasNumber) strength++;
            if (hasUpper) strength++;
            if (hasSpecial) strength++;
            
            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (strength === 0) {
                strengthBar.style.width = '0%';
            } else if (strength === 1) {
                strengthBar.classList.add('weak');
            } else if (strength === 2) {
                strengthBar.classList.add('fair');
            } else if (strength === 3) {
                strengthBar.classList.add('good');
            } else if (strength === 4) {
                strengthBar.classList.add('strong');
            }
            
            // Update requirement icons
            updateRequirement(lengthReq, hasLength);
            updateRequirement(numberReq, hasNumber);
            updateRequirement(upperReq, hasUpper);
            updateRequirement(specialReq, hasSpecial);
            
            return strength === 4;
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
        
        // Check password match
        function checkPasswordMatch() {
            const pwd = password.value;
            const confirm = confirmPassword.value;
            
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
        
        // Real-time validation
        password.addEventListener('input', function() {
            checkPasswordStrength();
            checkPasswordMatch();
        });
        
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        // Form validation before submit
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const isStrong = checkPasswordStrength();
            const doMatch = checkPasswordMatch();
            
            if (!isStrong) {
                e.preventDefault();
                alert('Please create a stronger password (min. 8 chars, include number, uppercase & special character)');
            } else if (!doMatch) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
        
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