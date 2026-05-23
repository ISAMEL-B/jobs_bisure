<?php require_once __DIR__ . '/feed_backend.php'; ?>
<!DOCTYPE html>
<html lang="en-GB" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Job site that connects job seekers with advertised employment opportunities.">
    <title>New Jobs Advertised - <?= number_format($totalJobs) ?> Current Vacancies</title>

    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
    <style>
        /* =========================================================
        GLOBAL
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
            --gradient-1: linear-gradient(135deg, #8b8b91 0%, #00CEC9 100%);
            --gradient-2: linear-gradient(135deg, #FD79A8 0%, #FDCB6E 100%);
        }

        body {
            background: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* =========================================================
        MODERN RESPONSIVE HEADER & NAVIGATION
        ========================================================= */
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

        /* CONTAINER */
        .g-container {
            width: 100%;
            max-width: 1400px;
            margin: auto;
            padding: 0 15px;
        }

        /* GRID */
        .g-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* BLOCKS */
        .g-block {
            min-width: 0;
        }

        /* =========================================================
        TOP SMALL NAV
        ========================================================= */
        .jl-subnav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .jl-subnav .tm-item {
            list-style: none;
        }

        .jl-subnav .tm-link,
        .jl-subnav span {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            transition: 0.3s;
            white-space: nowrap;
            color: var(--dark);
        }

        .jl-subnav .tm-link:hover {
            background: #F0EDFF;
            color: var(--primary);
            transform: translateY(-2px);
        }

        .jl-subnav .tm-link i {
            color: var(--primary);
            font-size: 16px;
        }

        /* =========================================================
        LOGO
        ========================================================= */
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
            max-width: 100%;
            display: block;
        }

        /* =========================================================
        MAIN NAVIGATION - DESKTOP
        ========================================================= */
        .g-main-nav {
            width: 100%;
        }

        .g-main-nav .g-toplevel {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
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
            justify-content: center;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 700;
            transition: 0.3s;
            white-space: nowrap;
            color: var(--dark);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
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

        /* =========================================================
        MOBILE MENU OVERLAY
        ========================================================= */
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
            opacity: 1;
        }

        /* =========================================================
        MOBILE MENU PANEL (SLIDES FROM LEFT)
        ========================================================= */
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
            padding: 14px 20px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
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
            padding-left: 30px;
        }

        .mobile-menu-items .g-menu-item-container:hover::before {
            transform: scaleY(1);
        }

        .mobile-menu-items .g-menu-item-container i {
            margin-right: 10px;
            font-size: 18px;
            color: var(--primary);
        }

        /* =========================================================
        MOBILE TOGGLE (HAMBURGER) BUTTON
        ========================================================= */
        .mobile-menu-toggle {
            display: none;
            border: none;
            background: var(--gradient-1);
            color: var(--white);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            font-size: 22px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 1000;
        }

        .mobile-menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .mobile-menu-toggle i {
            transition: 0.3s;
        }

        /* =========================================================
        DESKTOP SIZING
        ========================================================= */
        .size-16 {
            flex: 0 0 16%;
        }

        .size-84 {
            flex: 0 0 84%;
        }

        .size-85 {
            flex: 0 0 85%;
        }

        .size-15 {
            flex: 0 0 15%;
        }

        .size-100 {
            flex: 0 0 100%;
        }

        /* =========================================================
        RESPONSIVE - TABLET (991px and below)
        ========================================================= */
        @media(max-width: 991px) {
            /* Show hamburger button */
            .mobile-menu-toggle {
                display: flex;
            }

            /* Show mobile overlay and panel Eeee THIS LINE HAS DISTURBED ME .mobile-menu-overlay.active  */
            .mobile-menu-overlay.active {
                display: block;
            }

            .mobile-menu-panel {
                display: block;
            }

            /* Hide desktop navigation */
            .hidden-phone {
                display: none !important;
            }

            /* Stack header items */
            #g-header .g-grid,
            #g-navigation .g-grid {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            /* Full width for logo area */
            .size-16 {
                flex: 1;
            }

            .size-84 {
                display: none;
            }

            /* Center logo */
            .jl-logo {
                justify-content: flex-start;
            }

            /* Show phone nav */
            .visible-phone {
                display: block !important;
            }

            /* Top nav center */
            .jl-subnav {
                justify-content: center;
                width: 100%;
            }
        }

        /* =========================================================
        SMALL DEVICES (576px and below)
        ========================================================= */
        @media(max-width: 576px) {
            .jl-subnav {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }

            .jl-subnav .tm-link,
            .jl-subnav span {
                width: 100%;
                justify-content: center;
                font-size: 12px;
                padding: 10px;
            }

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
                font-size: 20px;
            }
        }

        /* =========================================================
        ANIMATIONS
        ========================================================= */
        @keyframes fadeMenu {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* =========================================================
        PAGE HEADING
        ========================================================= */
        .page_heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 16px 20px;
            background: var(--gradient-1);
            border-radius: 14px;
            box-shadow: var(--shadow-md);
            font-size: 22px;
            font-weight: 700;
            color: var(--white);
        }

        .totaljobsheading {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .totaljobsheading span {
            color: var(--white) !important;
            font-weight: 700;
        }

        /* =========================================================
        JOB CARD
        ========================================================= */
        #js-jobs-wrapper {
            background: var(--white);
            border-radius: 14px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: var(--shadow-sm);
            border: 1px solid #E8E8F0;
        }

        #js-jobs-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        /* =========================================================
        TOP ROW
        ========================================================= */
        .js-toprow {
            display: flex;
            gap: 15px;
            padding: 16px;
            align-items: flex-start;
        }

        .js-image {
            min-width: 70px;
        }

        .js-image img {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            object-fit: contain;
            background: var(--bg-light);
            border: 2px solid #E8E8F0;
            padding: 6px;
            transition: 0.3s;
        }

        .js-image img:hover {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        /* =========================================================
        JOB DETAILS
        ========================================================= */
        .js-data {
            flex: 1;
        }

        .jobtitle {
            font-size: 17px;
            font-weight: 700;
            color: var(--dark);
            text-decoration: none;
            line-height: 1.4;
            transition: 0.3s;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .jobtitle:hover {
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .js-type {
            background: #E8F8F5;
            color: var(--success);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 6px;
        }

        .bg-new {
            background: var(--danger);
            color: var(--white);
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* =========================================================
        JOB META
        ========================================================= */
        .js-category-wrp {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px;
        }

        .js-fields {
            background: var(--bg-light);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            border: 1px solid #E8E8F0;
            transition: 0.3s;
        }

        .js-fields:hover {
            border-color: var(--primary);
            background: #F0EDFF;
        }

        .js-bold {
            font-weight: 700;
            color: var(--primary);
        }

        /* =========================================================
        BOTTOM ACTIONS
        ========================================================= */
        .js-bottomrow {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 16px;
            border-top: 1px solid #E8E8F0;
            background: var(--bg-light);
        }

        .js-actions {
            display: flex;
            gap: 8px;
        }

        /* =========================================================
        BUTTONS
        ========================================================= */
        .js-button {
            border: none;
            background: #F0EDFF;
            color: var(--primary);
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .js-button:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .js-btn-apply {
            background: var(--gradient-1);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .js-btn-apply:hover {
            background: var(--gradient-1);
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        /* =========================================================
        SMALL MODAL OVERLAY
        ========================================================= */
        #js_job_black_friend {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 9998;
        }

        /* =========================================================
        SMALLER TELL A FRIEND MODAL
        ========================================================= */
        #tellafriend {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 400px;
            z-index: 9999;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        #tellafriend_headline {
            background: var(--gradient-1);
            color: var(--white);
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 12px 12px 0 0;
        }

        .closeimg {
            width: 14px;
            float: right;
            cursor: pointer;
            transition: 0.3s;
        }

        .closeimg:hover {
            transform: rotate(90deg);
        }

        /* =========================================================
        SMALLER FORM
        ========================================================= */
        #borderfieldwrapper {
            background: var(--white);
            padding: 16px;
            border-radius: 0 0 12px 12px;
        }

        .fieldwrapper {
            margin-bottom: 12px;
        }

        .fieldtitle {
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
        }

        .fieldvalue input,
        .fieldvalue textarea {
            width: 100%;
            border: 2px solid #E8E8F0;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            background: var(--bg-light);
            transition: 0.3s;
        }

        .fieldvalue textarea {
            min-height: 70px;
            resize: vertical;
        }

        .fieldvalue input:focus,
        .fieldvalue textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }

        /* =========================================================
        SMALLER MODAL BUTTONS
        ========================================================= */
        .js_job_tellafreind_button {
            border: none;
            border-radius: 20px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.3s;
        }

        .js_job_tellafreind_button.save {
            background: var(--gradient-1);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .js_job_tellafreind_button.save:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .js_job_tellafreind_button:not(.save) {
            background: #E8E8F0;
            color: var(--dark);
        }

        .js_job_tellafreind_button:not(.save):hover {
            background: #D1D1E0;
        }

        /* =========================================================
        PAGINATION
        ========================================================= */
        .pagination-list {
            display: flex;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding: 0;
        }

        .pagination-list li {
            list-style: none;
        }

        .pagination-list li a {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--white);
            color: var(--dark);
            font-weight: 600;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            transition: 0.3s;
            border: 2px solid #E8E8F0;
        }

        .pagination-list li.active a {
            background: var(--gradient-1);
            color: var(--white);
            border-color: transparent;
            box-shadow: var(--shadow-md);
        }

        .pagination-list li a:hover {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* =========================================================
        BACK TO TOP
        ========================================================= */
        #back-top {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: var(--gradient-1);
            color: var(--white);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            transition: 0.3s;
            z-index: 997;
            font-size: 20px;
        }

        #back-top:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        #back-top.backHide {
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
        }

        /* =========================================================
        MOBILE RESPONSIVE
        ========================================================= */
        @media(max-width: 768px) {
            .js-toprow {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 14px;
            }

            .js-bottomrow {
                justify-content: center;
            }

            .js-actions {
                flex-direction: column;
                width: 100%;
            }

            .js-button {
                width: 100%;
                text-align: center;
            }

            .page_heading {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                font-size: 18px;
            }

            .js-category-wrp {
                grid-template-columns: 1fr;
            }

            #back-top {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
            }
        }

        /* =========================================================
        TOAST NOTIFICATION
        ========================================================= */

        #customToast {
            position: fixed;
            top: 20px;
            right: 20px;
            color: #fff;
            padding: 14px 18px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
            max-width: 380px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 999999;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.4s ease;
            font-family: inherit;
        }

        #customToast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-success {
            background: linear-gradient(135deg, #00B894 0%, #00CEC9 100%);
        }

        .toast-error {
            background: linear-gradient(135deg, #FF7675 0%, #D63031 100%);
        }

        #customToast .toast-icon i {
            font-size: 24px;
            color: #fff;
        }

        #customToast .toast-message {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
        }

        @media(max-width: 576px) {

            #customToast {
                top: 15px;
                left: 15px;
                right: 15px;
                min-width: auto;
                max-width: none;
            }
        }
    </style>

</head>

<body class="gantry site com_jsjobs view-job layout-jobs no-task dir-ltr itemid-190 outline-14 g-offcanvas-left g-default g-style-preset1">

    <div id="g-page-surround">
        
        <!-- =========================================================
        MOBILE MENU PANEL
        ========================================================= -->
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

        <div class="mobile-menu-panel" id="mobileMenuPanel">

            <div class="mobile-menu-header">
                <h3>
                    <i class="bi bi-grid"></i> Menu
                </h3>

                <button class="mobile-menu-close"
                        id="mobileMenuClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <ul class="mobile-menu-items">

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/index.php">
                        <i class="bi bi-speedometer2"></i>
                        DASHBOARD
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/manage/users.php">
                        <i class="bi bi-person-badge-fill"></i>
                        SYSTEM USERS
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/send_emails.php">
                        <i class="bi bi-envelope-paper"></i>
                        SEND EMAILS
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/manage/subscribe.php">
                        <i class="bi bi-people"></i>
                        SUBSCRIBERS
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/manage/jobs.php">
                        <i class="bi bi-briefcase"></i>
                        MANAGE JOBS
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/manage/categories.php">
                        <i class="bi bi-folder"></i>
                        CATEGORIES
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/manage/settings.php">
                        <i class="bi bi-gear"></i>
                        SETTINGS
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/mailing/password_recovery.php">
                        <i class="bi bi-shield-lock-fill"></i>
                        CHANGE PASSWORD
                    </a>
                </li>

                <li class="g-menu-item">
                    <a class="g-menu-item-container"
                    href="/jobaggregator/security/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        LOGOUT
                    </a>
                </li>

            </ul>

        </div>

        <!-- Main Container -->
        <section id="g-container-main" class="g-wrapper">
            <div class="g-grid">
                <!-- Sidebar Left -->
                <div class="g-block size-23">
                </div>

                <!-- Main Content Area -->
                <div class="g-block size-54">
                    <section id="g-mainbar">
                        <div class="g-grid"><div class="g-block size-100"><div class="g-system-messages"><div id="system-message-container" aria-live="polite"></div></div></div></div>
                        
                        <div class="g-grid"><div class="g-block size-100 g-flushed"><div class="g-content">
                            <div class="platform-content container"><div class="row"><div class="col">
                                <div id="js_jobs_main_wrapper">
                                    <div id="js_job_black_friend" style="display:none;"></div>
                                    
                                    <!-- Tell A Friend Popup -->
                                    <div id="tellafriend" class="tellafriend" style="display:none;">
                                        <form action="index.php" method="POST">
                                            <div id="tellafriend_headline">Tell A Friend <img class="closeimg" onclick="closetellafriend();" src="//cdn.greatugandajobs.com/components/com_jsjobs/images/popup-close.png" alt="Close"></div>
                                            <div id="borderfieldwrapper">
                                                <div class="fieldwrapper"><div class="fieldtitle">Your Name<font color="red">*</font></div><div class="fieldvalue"><input class="inputbox required" type="text" name="sendername" id="sendername"></div></div>
                                                <div class="fieldwrapper"><div class="fieldtitle">Your Email<font color="red">*</font></div><div class="fieldvalue"><input class="inputbox required" type="text" name="senderemail" id="senderemail"></div></div>
                                                <div class="fieldwrapper"><div class="fieldtitle">Job Link<font color="red">*</font></div><div class="fieldvalue"><input class="inputbox required" type="text" name="joblink" id="joblink" disabled style="background:#f0f0f0; color:#6C5CE7; font-size:12px;"></div></div>
                                                <div class="fieldwrapper"><div class="fieldtitle">Friend Email<font color="red">*</font></div><div class="fieldvalue"><input class="inputbox required validate-email" type="text" name="email1" id="email1"></div></div>
                                                <div class="fieldwrapper"><div class="fieldtitle">Message<font color="red">*</font></div><div class="fieldvalue"><textarea class="inputbox required" name="message" id="message" rows="3" maxlength="250"></textarea></div></div>
                                                <div class="fieldwrapper fullwidth button">
                                                    <input class="js_job_tellafreind_button save" type="button" onclick="friendValidate();" value="Send To Friends">
                                                    <input class="js_job_tellafreind_button" type="button" onclick="closetellafriend();" value="Close">
                                                </div>
                                                <input type="hidden" name="jobid" id="jobid">
                                            </div>
                                        </form>
                                    </div>

                                    <div id="jsjobs-wrapper">
                                        <div class="page_heading">Jobs in Uganda <div class="totaljobsheading">Total jobs: <span><?= number_format($totalJobs) ?></span></div></div>
                                        <div class="jsjobs-breadcrunbs-wrp js-breadcrunbs">
                                        </div>

                                        <!-- Dynamic Job Listings -->
                                        <?php if(count($jobs) > 0): ?>
                                            <?php foreach($jobs as $job): ?>
                                                <div id="js-jobs-wrapper">
                                                    <div class="js-toprow">
                                                        <div class="js-image">
                                                            <a href="/jobs/company-detail/company-<?= urlencode($job['company_name']) ?>-<?= $job['id'] ?>/nav-31">
                                                                <img src="//cdn.greatugandajobs.com/jsjobsdata/data/default_logo_company/defaultlogo.png" title="<?= htmlspecialchars($job['company_name']) ?>" style="width:80px; height:80px; object-fit:contain;">
                                                            </a>
                                                        </div>
                                                        <div class="js-data">
                                                            <div class="js-first-row">
                                                                <span class="js-col-xs-12 js-col-md-6 js-title js-title-tablet">
                                                                    <span class="js-status js-type">Full-time</span>
                                                                    <?php if((strtotime(date('Y-m-d')) - strtotime($job['posted_date'])) / (60 * 60 * 24) <= 1): ?>
                                                                        <span class="js-status bg-new" style="background:var(--danger); color:var(--white); padding:2px 8px; border-radius:4px;">New</span>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            <div class="js-first-row">
                                                                <span class="js-col-xs-12 js-col-md-6 js-title js-title-tablet">
                                                                    <a class="jobtitle" href="<?= htmlspecialchars($job['apply_url']) ?>" target="_blank">
                                                                        <?= htmlspecialchars($job['title']) ?> job at <?= htmlspecialchars($job['company_name']) ?>
                                                                    </a>
                                                                </span>
                                                            </div>
                                                            <div class="js-second-row js-category-wrp">
                                                                <div class="js-col-xs-12 js-col-md-5 js-fields"><span class="js-bold">Job Category: </span><?= htmlspecialchars($job['category_name'] ?? 'Other') ?> jobs in Uganda</div>
                                                                <div class="js-col-xs-12 js-col-md-5 js-fields"><span class="js-bold">Posted: </span><?= date('d M Y', strtotime($job['posted_date'])) ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="js-bottomrow">
                                                        <div class="js-col-xs-12 js-col-md-8 js-address"></div>
                                                        <div class="js-col-xs-12 js-col-md-4 js-actions">
                                                            <!-- FIXED: Changed <a href="#" onclick="..."> to <button> for mobile touch support -->
                                                            <button type="button" class="js-button" onclick="showtellafriend('<?= $job['id'] ?>','<?= htmlspecialchars($job['apply_url']) ?>');" style="cursor:pointer;">
                                                                <i class="bi bi-share"></i> Tell A Friend
                                                            </button>
                                                            <a class="js-button js-btn-apply" href="<?= htmlspecialchars($job['apply_url']) ?>" "><i class="bi bi-eye"></i> View Details & Apply</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-info text-center p-5" style="background: var(--white); border-radius: 14px; box-shadow: var(--shadow-sm);">
                                                <i class="bi bi-info-circle" style="font-size: 24px; color: var(--primary);"></i>
                                                <p class="mt-2">No jobs found matching your criteria.</p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Pagination -->
                                        <?php if($totalPages > 1): ?>
                                            <ul class="pagination-list">
                                                <?php if($page > 1): ?><li><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&location=<?= urlencode($location) ?>&category=<?= urlencode($category) ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
                                                <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                                    <li class="<?= $i == $page ? 'active' : '' ?>"><a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&location=<?= urlencode($location) ?>&category=<?= urlencode($category) ?>"><?= $i ?></a></li>
                                                <?php endfor; ?>
                                                <?php if($page < $totalPages): ?><li><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&location=<?= urlencode($location) ?>&category=<?= urlencode($category) ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
                                            </ul>
                                        <?php endif; ?>
                                        
                                        <a class="scrolltask" data-scrolltask="getNextJobs" data-offset="1"></a>
                                    </div>
                                </div>
                            </div></div></div>
                        </div></div></div>
                    </section>
                </div>

                <!-- Sidebar Right -->
                <div class="g-block size-23">
                    <aside id="g-aside">
                        <div class="g-grid"><div class="g-block size-100 hidden-phone"><div class="g-content"><div class="platform-content"><div class="floatingmoduleck" id="floatingmoduleck107"><div class="floatingmoduleck-inner"><div class="aside jl-panel moduletable sticky"><div id="mod-custom107" class="mod-custom custom"><script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5839100731048282" crossorigin="anonymous"></script><ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-5839100731048282" data-ad-slot="4560494900" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div></div></div></div></div></div></div></div>
                    </aside>
                </div>
            </div>
        </section>

        <!-- Back to Top Button -->
        <a id="back-top" href="#" class="back-to-top" aria-label="Back to top" title="Back to top">
            <i class="bi bi-arrow-up"></i>
        </a>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
     <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="feed_tell_friend_modal_js.js"></script>
    
    <script>
        // =========================================================
        // MOBILE MENU FUNCTIONALITY
        // =========================================================
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const menuPanel = document.getElementById('mobileMenuPanel');
            const menuOverlay = document.getElementById('mobileMenuOverlay');
            const menuClose = document.getElementById('mobileMenuClose');
            
            // Open menu
            menuToggle.addEventListener('click', function() {
                menuPanel.classList.add('active');
                menuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            });
            
            // Close menu function
            function closeMenu() {
                menuPanel.classList.remove('active');
                menuOverlay.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
            
            // Close menu when clicking close button
            menuClose.addEventListener('click', closeMenu);
            
            // Close menu when clicking overlay
            menuOverlay.addEventListener('click', closeMenu);
            
            // Close menu when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && menuPanel.classList.contains('active')) {
                    closeMenu();
                }
            });
            
            // Close menu when clicking a menu item (for better UX)
            const mobileMenuItems = document.querySelectorAll('.mobile-menu-items .g-menu-item-container');
            mobileMenuItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    // Small delay to allow the click to register before closing
                    setTimeout(closeMenu, 150);
                });
            });
        });

        // =========================================================
        // TELL A FRIEND MODAL
        // =========================================================
        jQuery(document).ready(function($) {
            $("div#js_job_black_friend").click(function() {
                $("div#tellafriend").fadeOut();
                $("div#js_job_black_friend").fadeOut();
            });
        });

        function closetellafriend() {
            jQuery('#tellafriend').slideUp("slow");
            jQuery('#js_job_black_friend').fadeOut();
        }
        
        function showtellafriend(jobid, joburl) {
            jQuery('#js_job_black_friend').fadeIn();
            jQuery('#tellafriend').slideDown("slow");
            document.getElementById('jobid').value = jobid;
            document.getElementById('joblink').value = joburl;
        }
        
        function friendValidate() {

            let sendername  = document.getElementById('sendername').value.trim();
            let senderemail = document.getElementById('senderemail').value.trim();
            let email1      = document.getElementById('email1').value.trim();
            let message     = document.getElementById('message').value.trim();
            let jobid       = document.getElementById('jobid').value.trim();
            let joblink     = document.getElementById('joblink').value.trim();
            // =====================================================
            // VALIDATION
            // =====================================================

            if (sendername === '') {
                alert('Your name is required');
                return;
            }

            if (senderemail === '') {
                alert('Your email is required');
                return;
            }

            if (email1 === '') {
                alert('Friend email is required');
                return;
            }

            if (message === '') {
                alert('Message is required');
                return;
            }

            // =====================================================
            // DISABLE BUTTON
            // =====================================================

            const btn = document.querySelector('.js_job_tellafreind_button.save');

            btn.disabled = true;
            btn.value = 'Sending...';

            // =====================================================
            // AJAX REQUEST
            // =====================================================

            $.ajax({
                url: 'email_friend.php',
                type: 'POST',
                dataType: 'json',

                data: {
                    sendername: sendername,
                    senderemail: senderemail,
                    email1: email1,
                    message: message,
                    jobid: jobid,
                    joblink: joblink
                },

                success: function(response) {

                if (response.success) {

                    // SHOW SUCCESS TOAST
                    showToast('Job email sent to your friend successfully!');

                    // CLEAR FORM FIELDS
                    document.getElementById('sendername').value = '';
                    document.getElementById('senderemail').value = '';
                    document.getElementById('email1').value = '';
                    document.getElementById('message').value = '';
                    document.getElementById('jobid').value = '';
                    document.getElementById('joblink').value = '';

                    // CLOSE MODAL
                    closetellafriend();

                } else {

                    showToast(response.message, 'error');
                }

                // ENABLE BUTTON AGAIN
                btn.disabled = false;
                btn.value = 'Send To Friends';
            },

                error: function(xhr, status, error) {

                console.log('XHR RESPONSE:');
                console.log(xhr.responseText);

                console.log('STATUS:');
                console.log(status);

                console.log('ERROR:');
                console.log(error);

                // SHOW REAL ERROR
                alert(
                    'AJAX ERROR:\n\n' +
                    'Status: ' + status + '\n' +
                    'Error: ' + error + '\n\n' +
                    'Response:\n' + xhr.responseText
                );

                btn.disabled = false;
                btn.value = 'Send To Friends';
            }
            });
        }
        
        // =========================================================
        // BACK TO TOP BUTTON
        // =========================================================
        var backToTop = document.getElementById('back-top');
        if(backToTop) {
            window.onscroll = function() {
                if(document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                    backToTop.classList.remove('backHide');
                } else {
                    backToTop.classList.add('backHide');
                }
            };
            backToTop.onclick = function(e) { 
                e.preventDefault(); 
                window.scrollTo({top: 0, behavior: 'smooth'}); 
            };
        }
        // =========================================================
        // TOAST NOTIFICATION
        // =========================================================

        function showToast(message, type = 'success') {

        // REMOVE OLD TOAST
        const oldToast = document.getElementById('customToast');

        if (oldToast) {
            oldToast.remove();
        }

        // CREATE TOAST
        const toast = document.createElement('div');

        toast.id = 'customToast';

        // ERROR OR SUCCESS
        if (type === 'error') {

            toast.classList.add('toast-error');

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>

                <div class="toast-message">
                    ${message}
                </div>
            `;

        } else {

            toast.classList.add('toast-success');

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <div class="toast-message">
                    ${message}
                </div>
            `;
        }

        document.body.appendChild(toast);

        // SHOW
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // AUTO HIDE
        setTimeout(() => {

            toast.classList.remove('show');

            setTimeout(() => {
                toast.remove();
            }, 400);

        }, 4000);
    }
    </script>
    
    <div id="js_job_black_friend" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9998;"></div>
    <div id="js_jobs_main_popup_back" style="display:none;"></div>
    <?php require_once 'subscribe.php'; ?>
    
<!-- =========================================================
FOOTER
========================================================= -->
<footer style="background: linear-gradient(135deg, #2D3436 0%, #1a1a2e 100%); color: #DFE6E9; padding: 40px 20px 20px; margin-top: 40px;">
    <div class="container" style="max-width: 1400px; margin: 0 auto;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 30px; margin-bottom: 30px;">
            
            <!-- Footer Logo & About -->
            <div style="flex: 1; min-width: 200px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="bi bi-briefcase-fill" style="font-size: 28px; color: #6C5CE7;"></i>
                    <span style="font-size: 20px; font-weight: 700; background: linear-gradient(135deg, #6C5CE7, #00CEC9); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Job Finder</span>
                </div>
                <p style="font-size: 13px; line-height: 1.6; color: #B2BEC3;">Your trusted source for the latest job opportunities. Find your dream career today.</p>
            </div>
            
            <!-- Quick Links -->
            <div style="flex: 1; min-width: 150px;">
                <h4 style="color: white; font-size: 16px; margin-bottom: 15px; font-weight: 700;">Quick Link</h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 8px;"><a href="/jobaggregator/feed_landingPageAdmin.php" style="color: #B2BEC3; text-decoration: none; font-size: 13px; transition: 0.3s;"><i class="bi bi-chevron-right" style="font-size: 10px; margin-right: 5px;"></i>BISure</a></li>
                    <!-- <li style="margin-bottom: 8px;"><a href="/jobaggregator/manage/jobs.php" style="color: #B2BEC3; text-decoration: none; font-size: 13px; transition: 0.3s;"><i class="bi bi-chevron-right" style="font-size: 10px; margin-right: 5px;"></i> Browse Jobs</a></li>
                    <li style="margin-bottom: 8px;"><a href="/jobaggregator/manage/subscribe.php" style="color: #B2BEC3; text-decoration: none; font-size: 13px; transition: 0.3s;"><i class="bi bi-chevron-right" style="font-size: 10px; margin-right: 5px;"></i> Subscribers</a></li>
                    <li style="margin-bottom: 8px;"><a href="/jobaggregator/manage/settings.php" style="color: #B2BEC3; text-decoration: none; font-size: 13px; transition: 0.3s;"><i class="bi bi-chevron-right" style="font-size: 10px; margin-right: 5px;"></i> Settings</a></li> -->
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div style="flex: 1; min-width: 180px;">
                <h4 style="color: white; font-size: 16px; margin-bottom: 15px; font-weight: 700;">Contact</h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 10px; font-size: 13px; display: flex; align-items: center; gap: 8px;"><i class="bi bi-envelope" style="color: #6C5CE7;"></i> <span style="color: #B2BEC3;">info@bisuredev.com</span></li>
                    <li style="margin-bottom: 10px; font-size: 13px; display: flex; align-items: center; gap: 8px;"><i class="bi bi-telephone" style="color: #6C5CE7;"></i> <span style="color: #B2BEC3;">+256 764 920 075</span></li>
                    <li style="margin-bottom: 10px; font-size: 13px; display: flex; align-items: center; gap: 8px;"><i class="bi bi-geo-alt" style="color: #6C5CE7;"></i> <span style="color: #B2BEC3;">Mbarara, Uganda</span></li>
                </ul>
            </div>
            
            <!-- Social Links -->
            <div style="flex: 1; min-width: 150px;">
                <h4 style="color: white; font-size: 16px; margin-bottom: 15px; font-weight: 700;">Follow Us</h4>
                <div style="display: flex; gap: 12px;">
                    <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: 0.3s;"><i class="bi bi-facebook"></i></a>
                    <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: 0.3s;"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: 0.3s;"><i class="bi bi-linkedin"></i></a>
                    <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: 0.3s;"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Copyright Line -->
        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; text-align: center; font-size: 12px; color: #B2BEC3;">
            <p>&copy; <?= date('Y') ?> BISure Jobs. All rights reserved. | Developed with <i class="bi bi-heart-fill" style="color: #FF7675;"></i> for all job seekers</p>
        </div>
    </div>
</footer>

<style>
    /* Footer link hover effect */
    footer a:hover {
        color: #6C5CE7 !important;
        transform: translateX(3px);
    }
    
    footer .social-links a:hover {
        transform: translateY(-3px);
        background: #6C5CE7 !important;
    }
</style>

</body>
</html>
</html>