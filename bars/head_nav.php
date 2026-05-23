<?php
// =============================================================
// head_nav.php - Standalone Header & Navigation with Statistics
// Include this file at the top of your pages after session start
// =============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection for statistics
require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

// Fetch statistics for header
$totalSubscribers = $db->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
$totalActiveSubscribers = $db->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 1")->fetchColumn();
$totalInactiveSubscribers = $db->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 0")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(*) FROM job_categories")->fetchColumn();
$totalEmails = $db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();

$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Dynamic Base URL
if (!defined('MY_BASE_URL')) {

    $isLocalhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

    define(
        'MY_BASE_URL',
        $isLocalhost
            ? '/jobaggregator'
            : 'https://bisurejobs.22web.org'
    );
}
?>

<!DOCTYPE html>
<html lang="en-GB" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    
    <!-- CSS Files - Added preconnect for faster loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Fallback for Bootstrap Icons in case CDN fails -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    
    <style>
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
            background: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Header Styles - Full Width */
        #g-header {
            background: var(--white);
            border-bottom: 2px solid #E8E8F0;
            padding: 8px 0;
            width: 100%;
            box-shadow: var(--shadow-sm);
        }

        #g-navigation {
            background: var(--white);
            border-bottom: 1px solid #E8E8F0;
            position: sticky;
            top: 0;
            z-index: 999;
            width: 100%;
            box-shadow: var(--shadow-sm);
        }

        .g-container {
            width: 100%;
            max-width: 1400px;
            margin: auto;
            padding: 0 15px;
        }

        .g-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .g-block {
            min-width: 0;
        }

        .jl-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: 0.3s;
        }

        .jl-logo:hover {
            transform: scale(1.05);
        }

        .jl-logo img {
            height: 42px;
            width: auto;
            display: block;
        }

        /* Header Statistics - Desktop */
        .header-stats {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            background: var(--bg-light);
            padding: 5px 15px;
            border-radius: 50px;
            border: 1px solid #E8E8F0;
        }

        .header-stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            border-radius: 30px;
            transition: 0.3s;
            background: white;
            border: 1px solid #E8E8F0;
        }

        .header-stat-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
        }

        .header-stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            flex-shrink: 0;
        }

        .header-stat-icon.blue { background: var(--primary); }
        .header-stat-icon.green { background: var(--success); }
        .header-stat-icon.red { background: var(--danger); }
        .header-stat-icon.orange { background: #d97706; }
        .header-stat-icon.purple { background: #7c3aed; }

        .header-stat-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .header-stat-label {
            font-size: 9px;
            font-weight: 600;
            color: #636E72;
            letter-spacing: 0.3px;
        }

        .header-stat-number {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
        }

        /* Admin Info */
        .admin-info {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-light);
            padding: 6px 15px;
            border-radius: 30px;
            border: 1px solid #E8E8F0;
        }

        .admin-info i {
            color: var(--primary);
            font-size: 18px;
        }

        .admin-info span {
            font-weight: 600;
            font-size: 13px;
        }
        /* Mobile Admin Info */
        .mobile-admin-info {
            display: none;
            align-items: center;
            gap: 6px;
            background: var(--bg-light);
            padding: 6px 12px;
            border-radius: 25px;
            border: 1px solid #E8E8F0;
            margin-right: auto;
            margin-left: 10px;
        }

        .mobile-admin-info i {
            color: var(--primary);
            font-size: 16px;
        }

        .mobile-admin-info span {
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
        }

        /* Mobile Statistics - Horizontal Scroll */
        .mobile-stats-wrapper {
            display: none;
            width: 100%;
            background: var(--white);
            padding: 12px 0;
            border-bottom: 1px solid #E8E8F0;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .mobile-stats-wrapper::-webkit-scrollbar {
            height: 4px;
        }

        .mobile-stats-wrapper::-webkit-scrollbar-track {
            background: #E8E8F0;
            border-radius: 10px;
        }

        .mobile-stats-wrapper::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .mobile-stats {
            display: flex;
            gap: 12px;
            padding: 0 15px;
            min-width: min-content;
        }

        .mobile-stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-light);
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid #E8E8F0;
            white-space: nowrap;
            transition: 0.3s;
        }

        .mobile-stat-item:active {
            transform: scale(0.98);
        }

        .mobile-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .mobile-stat-icon i {
            font-size: 18px;
            /* color: blue; */
        }

        /* Mobile Statistics Icon Colors */
        .mobile-stat-icon.blue {
            background: var(--primary);
        }

        .mobile-stat-icon.green {
            background: var(--success);
        }

        .mobile-stat-icon.red {
            background: var(--danger);
        }

        .mobile-stat-icon.orange {
            background: #d97706;
        }

        .mobile-stat-icon.purple {
            background: #7c3aed;
        }

        .mobile-stat-info {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .mobile-stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #636E72;
            letter-spacing: 0.3px;
        }

        .mobile-stat-number {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        /* Desktop Navigation */
        .g-main-nav {
            width: 100%;
        }

        .g-main-nav .g-toplevel {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .g-main-nav .g-menu-item {
            list-style: none;
        }

        .g-main-nav .g-menu-item-container {
            display: flex;
            align-items: center;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            transition: 0.3s;
            color: var(--dark);
            position: relative;
            overflow: hidden;
        }

        .g-main-nav .g-menu-item-container i {
            margin-right: 6px;
            font-size: 14px;
        }

        .g-main-nav .g-menu-item-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-1);
            transition: 0.4s;
            z-index: -1;
            border-radius: 25px;
        }

        .g-main-nav .g-menu-item-container:hover {
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .g-main-nav .g-menu-item-container:hover::before {
            left: 0;
        }

        /* Mobile Menu */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-menu-overlay.active {
            display: block;
            opacity: 1;
        }

        .mobile-menu-panel {
            display: none;
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100%;
            background: var(--white);
            z-index: 999;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
            overflow-y: auto;
            padding: 20px 0;
        }

        .mobile-menu-panel.active {
            left: 0;
            display: block;
        }

        .mobile-menu-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E8E8F0;
            margin-bottom: 20px;
            background: var(--gradient-1);
            color: var(--white);
        }

        .mobile-menu-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .mobile-menu-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: var(--white);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .mobile-menu-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .mobile-menu-items {
            list-style: none;
            padding: 0 15px;
            margin: 0;
        }

        .mobile-menu-items .g-menu-item {
            list-style: none;
            margin-bottom: 5px;
        }

        .mobile-menu-items .g-menu-item-container {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            font-size: 13px;
            border-radius: 10px;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .mobile-menu-items .g-menu-item-container::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-1);
            border-radius: 0 4px 4px 0;
            transform: scaleY(0);
            transition: 0.3s;
        }

        .mobile-menu-items .g-menu-item-container:hover {
            background: #F0EDFF;
            color: var(--primary);
            padding-left: 28px;
        }

        .mobile-menu-items .g-menu-item-container:hover::before {
            transform: scaleY(1);
        }

        .mobile-menu-items .g-menu-item-container i {
            margin-right: 10px;
            font-size: 18px;
            color: var(--primary);
        }

        .mobile-menu-toggle {
            display: none;
            border: none;
            background: var(--gradient-1);
            color: var(--white);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            box-shadow: var(--shadow-md);
        }

        .mobile-menu-toggle:hover {
            transform: scale(1.05);
        }

        .hidden-phone {
            display: block;
        }

        .visible-phone {
            display: none;
        }

        /* Force icons to display properly on mobile */
        .bi {
            display: inline-block;
            font-family: 'bootstrap-icons' !important;
            font-style: normal;
            font-weight: normal;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            vertical-align: middle;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .header-stats {
                padding: 3px 10px;
            }
            .header-stat-item {
                padding: 3px 10px;
            }
            .header-stat-number {
                font-size: 12px;
            }
        }

        @media (max-width: 991px) {
            .hidden-phone {
                display: none !important;
            }
            .visible-phone {
                display: block !important;
            }
            .mobile-menu-toggle {
                display: flex;
            }
            .header-stats {
                display: none;
            }
            .admin-info {
                display: none;
            }
            .mobile-stats-wrapper {
                display: block;
            }
        }

        @media (max-width: 576px) {
            .mobile-menu-panel {
                width: 280px;
                left: -280px;
            }
            .jl-logo img {
                height: 36px;
            }
            .mobile-menu-toggle {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            .mobile-stat-item {
                padding: 6px 12px;
            }
            .mobile-stat-icon {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }
            .mobile-stat-icon i {
                font-size: 16px;
            }
            .mobile-stat-number {
                font-size: 14px;
            }
            .mobile-stat-label {
                font-size: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header id="g-header">
        <div class="g-container">
            <div class="g-grid">
                <!-- Logo -->
                <div class="g-block">
                    <a class="jl-logo" href="/jobaggregator/index.php">
                        <img src="/jobaggregator/bisure-jobs-logo.png" alt="BISure Jobs" style="height:40px;">
                    </a>
                </div>

                <!-- Mobile Admin Info -->
                <div class="mobile-admin-info visible-phone">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($adminName) ?></span>
                </div>

                <!-- Statistics - Desktop (Between Logo and Admin) -->
                <div class="g-block hidden-phone">
                    <div class="header-stats">
                        <div class="header-stat-item">
                            <div class="header-stat-icon blue"><i class="bi bi-people-fill"></i></div>
                            <div class="header-stat-info">
                                <span class="header-stat-label">SUBSCRIBERS</span>
                                <span class="header-stat-number"><?= number_format($totalSubscribers) ?></span>
                            </div>
                        </div>
                        <div class="header-stat-item">
                            <div class="header-stat-icon purple"><i class="bi bi-envelope-paper-fill"></i></div>
                            <div class="header-stat-info">
                                <span class="header-stat-label">EMAILS SENT</span>
                                <span class="header-stat-number"><?= number_format($totalEmails) ?></span>
                            </div>
                        </div>
                        <div class="header-stat-item">
                            <div class="header-stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="header-stat-info">
                                <span class="header-stat-label">ACTIVE</span>
                                <span class="header-stat-number"><?= number_format($totalActiveSubscribers) ?></span>
                            </div>
                        </div>
                        <div class="header-stat-item">
                            <div class="header-stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                            <div class="header-stat-info">
                                <span class="header-stat-label">INACTIVE</span>
                                <span class="header-stat-number"><?= number_format($totalInactiveSubscribers) ?></span>
                            </div>
                        </div>
                        <div class="header-stat-item">
                            <div class="header-stat-icon orange"><i class="bi bi-tags-fill"></i></div>
                            <div class="header-stat-info">
                                <span class="header-stat-label">CATEGORIES</span>
                                <span class="header-stat-number"><?= number_format($totalCategories) ?></span>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <!-- Admin Info - Desktop -->
                <div class="g-block hidden-phone">
                    <div class="admin-info">
                        <i class="bi bi-person-circle"></i>
                        <span><?= htmlspecialchars($adminName) ?></span>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-toggle visible-phone" id="mobileMenuToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Statistics - Horizontal Scroll (Visible only on mobile) -->
    <div class="mobile-stats-wrapper visible-phone">
        <div class="mobile-stats">
            <div class="mobile-stat-item">
                <div class="mobile-stat-icon blue"><i class="bi bi-people-fill"></i></div>
                <div class="mobile-stat-info">
                    <span class="mobile-stat-label">SUBSCRIBERS</span>
                    <span class="mobile-stat-number"><?= number_format($totalSubscribers) ?></span>
                </div>
            </div>
            <div class="mobile-stat-item">
                <div class="mobile-stat-icon purple"><i class="bi bi-envelope-paper-fill"></i></div>
                <div class="mobile-stat-info">
                    <span class="mobile-stat-label">EMAILS SENT</span>
                    <span class="mobile-stat-number"><?= number_format($totalEmails) ?></span>
                </div>
            </div>
            <div class="mobile-stat-item">
                <div class="mobile-stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="mobile-stat-info">
                    <span class="mobile-stat-label">ACTIVE</span>
                    <span class="mobile-stat-number"><?= number_format($totalActiveSubscribers) ?></span>
                </div>
            </div>
            <div class="mobile-stat-item">
                <div class="mobile-stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                <div class="mobile-stat-info">
                    <span class="mobile-stat-label">INACTIVE</span>
                    <span class="mobile-stat-number"><?= number_format($totalInactiveSubscribers) ?></span>
                </div>
            </div>
            <div class="mobile-stat-item">
                <div class="mobile-stat-icon orange"><i class="bi bi-tags-fill"></i></div>
                <div class="mobile-stat-info">
                    <span class="mobile-stat-label">CATEGORIES</span>
                    <span class="mobile-stat-number"><?= number_format($totalCategories) ?></span>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Desktop Navigation -->
    <section id="g-navigation" class="hidden-phone">
        <div class="g-container">
            <div class="g-grid">
                <div class="g-block size-100">
                    <nav class="g-main-nav">
                        <ul class="g-toplevel">
                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/index.php">
                                    <i class="bi bi-speedometer2"></i> DASHBOARD
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/users.php">
                                    <i class="bi bi-person-badge-fill"></i> SYSTEM USERS
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/send_emails.php">
                                    <i class="bi bi-envelope-paper"></i> SEND EMAILS
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/subscribe.php">
                                    <i class="bi bi-people-fill"></i> SUBSCRIBERS
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/jobs.php">
                                    <i class="bi bi-briefcase-fill"></i> MANAGE JOBS
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/categories.php">
                                    <i class="bi bi-folder-fill"></i> CATEGORIES
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/settings.php">
                                    <i class="bi bi-gear-fill"></i> SETTINGS
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/mailing/password_recovery.php">
                                    <i class="bi bi-shield-lock-fill"></i> CHANGE PASSWORD
                                </a>
                            </li>

                            <li class="g-menu-item">
                                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/security/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> LOGOUT
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Menu Panel -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <div class="mobile-menu-panel" id="mobileMenuPanel">
        <div class="mobile-menu-header">
            <h3><i class="bi bi-grid-fill"></i> Menu</h3>

            <button class="mobile-menu-close" id="mobileMenuClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <ul class="mobile-menu-items">

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/index.php">
                    <i class="bi bi-speedometer2"></i> DASHBOARD
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/users.php">
                    <i class="bi bi-person-badge-fill"></i> SYSTEM USERS
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/send_emails.php">
                    <i class="bi bi-envelope-paper"></i> SEND EMAILS
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/subscribe.php">
                    <i class="bi bi-people-fill"></i> SUBSCRIBERS
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/jobs.php">
                    <i class="bi bi-briefcase-fill"></i> MANAGE JOBS
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/categories.php">
                    <i class="bi bi-folder-fill"></i> CATEGORIES
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/manage/settings.php">
                    <i class="bi bi-gear-fill"></i> SETTINGS
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/mailing/password_recovery.php">
                    <i class="bi bi-shield-lock-fill"></i> CHANGE PASSWORD
                </a>
            </li>

            <li class="g-menu-item">
                <a class="g-menu-item-container" href="<?= MY_BASE_URL ?>/security/logout.php">
                    <i class="bi bi-box-arrow-right"></i> LOGOUT
                </a>
            </li>

        </ul>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const menuPanel = document.getElementById('mobileMenuPanel');
            const menuOverlay = document.getElementById('mobileMenuOverlay');
            const menuClose = document.getElementById('mobileMenuClose');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    menuPanel.classList.add('active');
                    menuOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }
            
            function closeMenu() {
                if (menuPanel) menuPanel.classList.remove('active');
                if (menuOverlay) menuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            if (menuClose) menuClose.addEventListener('click', closeMenu);
            if (menuOverlay) menuOverlay.addEventListener('click', closeMenu);
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && menuPanel && menuPanel.classList.contains('active')) {
                    closeMenu();
                }
            });
            
            const mobileMenuItems = document.querySelectorAll('.mobile-menu-items .g-menu-item-container');
            mobileMenuItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    setTimeout(closeMenu, 150);
                });
            });
        });
    </script>
</body>
</html>