<?php

session_start();

/*
|--------------------------------------------------------------------------
| AUTHENTICATION CHECK
|--------------------------------------------------------------------------
|
| Redirect users who are not logged in
|
*/

if (
    !isset($_SESSION['user_id'])
    ||
    empty($_SESSION['user_id'])
) {

    header("Location: /jobaggregator/security/signin.php");

    exit;
}
;

/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF']);

/*
|--------------------------------------------------------------------------
| GET SYSTEM SETTINGS
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

    $result = $stmt->fetch();

    return $result['setting_value'] ?? $default;
}

/*
|--------------------------------------------------------------------------
| SYSTEM SETTINGS
|--------------------------------------------------------------------------
*/

$siteName = getSetting(
    $db,
    'site_name',
    'Job Aggregator'
);

$defaultCountry = getSetting(
    $db,
    'default_country',
    'Uganda'
);

/*
|--------------------------------------------------------------------------
| SYSTEM STATISTICS
|--------------------------------------------------------------------------
*/

$totalJobs = $db->query("
    SELECT COUNT(*) total
    FROM jobs
")->fetch()['total'];

$totalSubscribers = $db->query("
    SELECT COUNT(*) total
    FROM subscribers
")->fetch()['total'];

$totalCategories = $db->query("
    SELECT COUNT(*) total
    FROM job_categories
")->fetch()['total'];

$totalSources = $db->query("
    SELECT COUNT(*) total
    FROM job_sources
")->fetch()['total'];

$totalEmails = $db->query("
    SELECT COUNT(*) total
    FROM email_logs
")->fetch()['total'];

$activeSubscribers = $db->query("
    SELECT COUNT(*) total
    FROM subscribers
    WHERE is_active = 1
")->fetch()['total'];

$activeSources = $db->query("
    SELECT COUNT(*) total
    FROM job_sources
    WHERE is_active = 1
")->fetch()['total'];

$failedJobs = $db->query("
    SELECT COUNT(*) total
    FROM failed_jobs
")->fetch()['total'];

/*
|--------------------------------------------------------------------------
| GET CURRENT ADMIN USER
|--------------------------------------------------------------------------
*/

$adminName = 'Administrator';

$adminRole = 'System Admin';

$adminEmail = 'admin@bisure.com';

$adminInitial = 'A';

if (isset($_SESSION['user_id'])) {

    $stmt = $db->prepare("
        SELECT full_name, role, email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user_id']]);

    $admin = $stmt->fetch();

    if ($admin) {

        $adminName = $admin['full_name'];

        $adminRole = $admin['role'];

        $adminEmail = $admin['email'];

        $_SESSION['user_email'] = $adminEmail;

        $adminInitial = strtoupper(
            substr($admin['full_name'], 0, 1)
        );
    }
}

?>

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

    :root{

        --sidebar-bg:#0f172a;
        --sidebar-hover:#1e293b;
        --sidebar-active:#2563eb;
        --sidebar-text:#cbd5e1;
        --sidebar-muted:#94a3b8;
        --sidebar-border:rgba(255,255,255,0.06);
    }

    body{
        overflow-x:hidden;
    }

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    */

    .sidebar{

        width:280px;

        height:100vh;

        position:fixed;

        top:0;

        left:0;

        background:linear-gradient(
            180deg,
            #0f172a 0%,
            #111827 100%
        );

        z-index:999;

        display:flex;

        flex-direction:column;

        border-right:1px solid var(--sidebar-border);

        transition:all 0.3s ease;

        overflow:hidden;
    }

    /*
    |--------------------------------------------------------------------------
    | BRAND
    |--------------------------------------------------------------------------
    */

    .sidebar-brand{

        padding:24px;

        border-bottom:1px solid var(--sidebar-border);

        display:flex;

        align-items:center;

        gap:16px;
    }

    .brand-logo{

        width:60px;

        height:60px;

        border-radius:18px;

        background:linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

        display:flex;

        align-items:center;

        justify-content:center;

        color:#fff;

        font-size:26px;

        box-shadow:0 10px 30px rgba(37,99,235,0.25);

        flex-shrink:0;
    }

    .brand-text{
        flex:1;
        min-width:0;
    }

    .brand-text h4{

        color:#fff;

        margin:0;

        font-size:16px;

        font-weight:700;

        line-height:1.4;
    }

    .brand-email{

        color:#94a3b8;

        font-size:12px;

        font-style:italic;

        display:block;

        margin-top:3px;

        overflow:hidden;

        text-overflow:ellipsis;

        white-space:nowrap;
    }

    .role-badge{

        margin-top:8px;

        display:inline-flex;

        align-items:center;

        gap:6px;

        background:rgba(37,99,235,0.15);

        color:#93c5fd;

        padding:5px 10px;

        border-radius:999px;

        font-size:11px;

        font-weight:600;
    }

    /*
    |--------------------------------------------------------------------------
    | QUICK STATS
    |--------------------------------------------------------------------------
    */

    .quick-stats{

        padding:18px 20px;

        border-bottom:1px solid var(--sidebar-border);

        display:grid;

        grid-template-columns:repeat(2,1fr);

        gap:12px;
    }

    .quick-card{

        background:rgba(255,255,255,0.04);

        border:1px solid rgba(255,255,255,0.05);

        border-radius:16px;

        padding:14px;

        text-align:center;
    }

    .quick-card h5{

        color:#fff;

        margin:0;

        font-size:18px;

        font-weight:700;
    }

    .quick-card small{

        color:#94a3b8;

        font-size:11px;
    }

    /*
    |--------------------------------------------------------------------------
    | MENU
    |--------------------------------------------------------------------------
    */

    .sidebar-menu{

        flex:1;

        overflow-y:auto;

        padding:18px 14px;
    }

    .menu-title{

        color:var(--sidebar-muted);

        font-size:11px;

        font-weight:700;

        letter-spacing:1px;

        padding:12px 16px;

        text-transform:uppercase;
    }

    .sidebar-menu a{

        display:flex;

        align-items:center;

        justify-content:space-between;

        text-decoration:none;

        color:var(--sidebar-text);

        padding:14px 16px;

        border-radius:16px;

        margin-bottom:8px;

        transition:all 0.25s ease;
    }

    .sidebar-menu a:hover{

        background:var(--sidebar-hover);

        color:#fff;

        transform:translateX(4px);
    }

    .sidebar-menu a.active{

        background:linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

        color:#fff;

        box-shadow:0 8px 20px rgba(37,99,235,0.25);
    }

    .sidebar-menu .menu-left{

        display:flex;

        align-items:center;

        gap:14px;
    }

    .sidebar-menu i{

        font-size:20px;
    }

    .menu-badge{

        background:#ef4444;

        color:#fff;

        font-size:11px;

        padding:4px 8px;

        border-radius:20px;

        font-weight:600;
    }

    .menu-badge.green{
        background:#10b981;
    }

    .menu-badge.orange{
        background:#f59e0b;
    }

    /*
    |--------------------------------------------------------------------------
    | FOOTER
    |--------------------------------------------------------------------------
    */

    .sidebar-footer{

        padding:20px;

        border-top:1px solid var(--sidebar-border);

        background:rgba(255,255,255,0.02);
    }

    .admin-card{

        background:rgba(255,255,255,0.04);

        border:1px solid rgba(255,255,255,0.05);

        border-radius:18px;

        padding:16px;

        display:flex;

        align-items:center;

        gap:14px;
    }

    .admin-avatar{

        width:52px;

        height:52px;

        border-radius:50%;

        background:linear-gradient(
            135deg,
            #10b981,
            #059669
        );

        display:flex;

        align-items:center;

        justify-content:center;

        color:#fff;

        font-weight:bold;

        font-size:20px;
    }

    .admin-info h6{

        color:#fff;

        margin:0;

        font-size:14px;
    }

    .admin-info small{

        color:#94a3b8;
    }

    .online-status{

        width:10px;

        height:10px;

        border-radius:50%;

        background:#22c55e;

        display:inline-block;
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN WRAPPER
    |--------------------------------------------------------------------------
    */

    .main-wrapper{

        margin-left:280px;

        transition:all 0.3s ease;
    }

    /*
    |--------------------------------------------------------------------------
    | SCROLLBAR
    |--------------------------------------------------------------------------
    */

    .sidebar-menu::-webkit-scrollbar{

        width:6px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb{

        background:#334155;

        border-radius:10px;
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media(max-width:992px){

        .sidebar{
            width:90px;
        }

        .brand-text,
        .menu-title,
        .sidebar-menu span,
        .menu-badge,
        .admin-info,
        .quick-stats{
            display:none;
        }

        .sidebar-brand{
            justify-content:center;
        }

        .sidebar-menu a{
            justify-content:center;
            padding:16px;
        }

        .sidebar-menu .menu-left{
            gap:0;
        }

        .sidebar-footer{
            display:flex;
            justify-content:center;
        }

        .admin-card{
            padding:10px;
        }

        .main-wrapper{
            margin-left:90px;
        }
    }

</style>

<!-- SIDEBAR -->

<div class="sidebar">

    <!-- BRAND -->

    <div class="sidebar-brand">

        <div class="brand-logo">

            <i class="bi bi-briefcase-fill"></i>

        </div>

        <div class="brand-text">

            <h4>

                <?= htmlspecialchars($siteName) ?>

            </h4>

            <span class="brand-email">

                <?= htmlspecialchars($adminEmail) ?>

            </span>

        </div>

    </div>

    <!-- MENU -->

    <div class="sidebar-menu">

        <div class="menu-title">

            Main Menu

        </div>

        <!-- DASHBOARD -->

        <a
            href="/jobaggregator/index.php"
            class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-speedometer2"></i>

                <span>Dashboard</span>

            </div>

        </a>

        <!-- SEND EMAILS -->

        <a
            href="/jobaggregator/send_emails.php"
            class="<?= ($currentPage == 'send_emails.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-envelope-paper-fill"></i>

                <span>Send Emails</span>

            </div>

        </a>

        <!-- SUBSCRIBERS -->

        <a
            href="/jobaggregator/manage/subscribe.php"
            class="<?= ($currentPage == 'subscribe.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-people-fill"></i>

                <span>Subscribers</span>

            </div>

            <span class="menu-badge">

                <?= number_format($totalSubscribers) ?>

            </span>

        </a>

        <!-- JOBS -->

        <a
            href="/jobaggregator/manage/jobs.php"
            class="<?= ($currentPage == 'jobs.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-briefcase-fill"></i>

                <span>Manage Jobs</span>

            </div>

            <span class="menu-badge green">

                <?= number_format($totalJobs) ?>

            </span>

        </a>

        <!-- CATEGORIES -->

        <a
            href="/jobaggregator/manage/categories.php"
            class="<?= ($currentPage == 'categories.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-tags-fill"></i>

                <span>Categories</span>

            </div>

            <span class="menu-badge orange">

                <?= number_format($totalCategories) ?>

            </span>

        </a>

        <!-- SETTINGS -->

        <a
            href="/jobaggregator/manage/settings.php"
            class="<?= ($currentPage == 'settings.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-gear-fill"></i>

                <span>Settings</span>

            </div>

        </a>

        <!-- USERS -->

        <a
            href="/jobaggregator/manage/users.php"
            class="<?= ($currentPage == 'users.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-person-badge-fill"></i>

                <span>System Users</span>

            </div>

        </a>

        <!-- CHANGE PASSWORD -->

        <a
            href="/jobaggregator/mailing/password_recovery.php"
            class="<?= ($currentPage == 'password_recovery.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-shield-lock-fill"></i>

                <span>Change Password</span>

            </div>

        </a>

        <!-- LOGOUT -->

        <a
            href="/jobaggregator/security/logout.php"
            class="<?= ($currentPage == 'logout.php') ? 'active' : '' ?>"
        >

            <div class="menu-left">

                <i class="bi bi-box-arrow-right"></i>

                <span>Logout</span>

            </div>

        </a>

    </div>

    <!-- FOOTER -->

    <div class="sidebar-footer">

        <div class="admin-card">

            <div class="admin-avatar">

                <?= $adminInitial ?>

            </div>

            <div class="admin-info">

                <h6>

                    <?= htmlspecialchars($adminName) ?>

                </h6>

                <small>

                    <span class="online-status"></span>

                    <?= htmlspecialchars($adminRole) ?>

                </small>

            </div>

        </div>

    </div>

</div>