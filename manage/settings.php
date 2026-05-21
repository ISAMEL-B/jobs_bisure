<?php

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

$message = "";

/*
|--------------------------------------------------------------------------
| CREATE SETTINGS TABLE
|--------------------------------------------------------------------------
*/

$db->exec("
    CREATE TABLE IF NOT EXISTS settings (

        id INT AUTO_INCREMENT PRIMARY KEY,

        setting_key VARCHAR(255) UNIQUE,

        setting_value TEXT,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

    )
");

/*
|--------------------------------------------------------------------------
| DEFAULT SETTINGS
|--------------------------------------------------------------------------
*/

$defaultSettings = [

    'site_name' => 'Uganda Job Aggregator',

    'admin_email' => 'admin@example.com',

    'jobs_per_email' => '20',

    'scraper_pages' => '3',

    'email_enabled' => '1',

    'scraper_enabled' => '1',

    'maintenance_mode' => '0',

    'default_country' => 'Uganda'
];

/*
|--------------------------------------------------------------------------
| INSERT DEFAULT SETTINGS
|--------------------------------------------------------------------------
*/

foreach ($defaultSettings as $key => $value) {

    $stmt = $db->prepare("
        INSERT IGNORE INTO settings (
            setting_key,
            setting_value
        )
        VALUES (?, ?)
    ");

    $stmt->execute([$key, $value]);
}

/*
|--------------------------------------------------------------------------
| SAVE SETTINGS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST as $key => $value) {

        $stmt = $db->prepare("
            UPDATE settings
            SET setting_value = ?
            WHERE setting_key = ?
        ");

        $stmt->execute([

            trim($value),
            trim($key)

        ]);
    }

    $message = "
        <div class='alert alert-success border-0 shadow-sm'>
            <i class='bi bi-check-circle-fill me-2'></i>
            Settings updated successfully.
        </div>
    ";
}

/*
|--------------------------------------------------------------------------
| GET SETTINGS
|--------------------------------------------------------------------------
*/

$settings = [];

$rows = $db->query("
    SELECT *
    FROM settings
")->fetchAll();

foreach ($rows as $row) {

    $settings[$row['setting_key']]
        = $row['setting_value'];
}

/*
|--------------------------------------------------------------------------
| DASHBOARD STATS
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

$totalEmails = $db->query("
    SELECT COUNT(*) total
    FROM email_logs
")->fetch()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>System Settings</title>

    <!-- BOOTSTRAP -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- ICONS -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body{

            background:#f1f5f9;

            overflow-x:hidden;
        }

        .main-wrapper{

            margin-left:260px;

            padding:30px;
        }

        .top-header{

            background:#fff;

            border-radius:20px;

            padding:25px;

            margin-bottom:25px;

            box-shadow:0 4px 20px rgba(0,0,0,0.05);
        }

        .stats-card{

            border:none;

            border-radius:18px;

            transition:0.3s;
        }

        .stats-card:hover{

            transform:translateY(-4px);
        }

        .icon-box{

            width:60px;

            height:60px;

            border-radius:15px;

            display:flex;

            align-items:center;

            justify-content:center;

            color:#fff;

            font-size:24px;
        }

        .blue{
            background:#2563eb;
        }

        .green{
            background:#059669;
        }

        .orange{
            background:#d97706;
        }

        .settings-card{

            border:none;

            border-radius:20px;

            overflow:hidden;
        }

        .settings-header{

            background:#111827;

            color:#fff;

            padding:20px;
        }

        .form-control,
        .form-select{

            border-radius:12px;

            min-height:50px;
        }

        .form-control:focus,
        .form-select:focus{

            box-shadow:none;

            border-color:#2563eb;
        }

        .save-btn{

            height:55px;

            border-radius:14px;

            font-weight:600;

            font-size:17px;
        }

        .action-btn{

            border-radius:12px;

            padding:12px 18px;

            font-weight:600;
        }

        .section-title{

            font-size:18px;

            font-weight:700;

            margin-bottom:20px;
        }

        @media(max-width:992px){

            .main-wrapper{

                margin-left:80px;
            }
        }

    </style>

</head>

<body>

<!-- SIDEBAR -->

<?php include '../sidebar/sidebar.php'; ?>

<!-- MAIN -->

<div class="main-wrapper">

    <!-- HEADER -->

    <div class="top-header d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="mb-1">
                System Settings
            </h2>

            <small class="text-muted">
                Manage scraper, emails, automation and system preferences
            </small>

        </div>

        <a href="../index.php"
           class="btn btn-dark action-btn">

            <i class="bi bi-speedometer2 me-2"></i>

            Dashboard

        </a>

    </div>

    <!-- MESSAGE -->

    <?= $message ?>

    <!-- STATS -->

    <div class="row mb-4">

        <!-- JOBS -->

        <div class="col-lg-4 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Total Jobs
                        </h6>

                        <h2>
                            <?= number_format($totalJobs) ?>
                        </h2>

                    </div>

                    <div class="icon-box blue">

                        <i class="bi bi-briefcase"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- SUBSCRIBERS -->

        <div class="col-lg-4 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Subscribers
                        </h6>

                        <h2>
                            <?= number_format($totalSubscribers) ?>
                        </h2>

                    </div>

                    <div class="icon-box green">

                        <i class="bi bi-people"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- EMAILS -->

        <div class="col-lg-4 col-md-12 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Emails Sent
                        </h6>

                        <h2>
                            <?= number_format($totalEmails) ?>
                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-envelope-check"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- SETTINGS FORM -->

    <div class="card settings-card shadow-sm mb-4">

        <div class="settings-header">

            <h4 class="mb-0">

                <i class="bi bi-gear-fill me-2"></i>

                System Configuration

            </h4>

        </div>

        <div class="card-body p-4">

            <form method="POST">

                <div class="row">

                    <!-- SITE NAME -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Site Name
                        </label>

                        <input
                            type="text"
                            name="site_name"
                            class="form-control"
                            value="<?= htmlspecialchars($settings['site_name']) ?>"
                        >

                    </div>

                    <!-- ADMIN EMAIL -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Admin Email
                        </label>

                        <input
                            type="email"
                            name="admin_email"
                            class="form-control"
                            value="<?= htmlspecialchars($settings['admin_email']) ?>"
                        >

                    </div>

                    <!-- JOBS PER EMAIL -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Jobs Per Email
                        </label>

                        <input
                            type="number"
                            name="jobs_per_email"
                            class="form-control"
                            value="<?= htmlspecialchars($settings['jobs_per_email']) ?>"
                        >

                    </div>

                    <!-- SCRAPER PAGES -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Scraper Pages
                        </label>

                        <input
                            type="number"
                            name="scraper_pages"
                            class="form-control"
                            value="<?= htmlspecialchars($settings['scraper_pages']) ?>"
                        >

                    </div>

                    <!-- COUNTRY -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Default Country
                        </label>

                        <input
                            type="text"
                            name="default_country"
                            class="form-control"
                            value="<?= htmlspecialchars($settings['default_country']) ?>"
                        >

                    </div>

                    <!-- EMAIL SYSTEM -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Email System
                        </label>

                        <select
                            name="email_enabled"
                            class="form-select"
                        >

                            <option value="1"
                                <?= $settings['email_enabled'] == 1 ? 'selected' : '' ?>>

                                Enabled

                            </option>

                            <option value="0"
                                <?= $settings['email_enabled'] == 0 ? 'selected' : '' ?>>

                                Disabled

                            </option>

                        </select>

                    </div>

                    <!-- SCRAPER -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Scraper System
                        </label>

                        <select
                            name="scraper_enabled"
                            class="form-select"
                        >

                            <option value="1"
                                <?= $settings['scraper_enabled'] == 1 ? 'selected' : '' ?>>

                                Enabled

                            </option>

                            <option value="0"
                                <?= $settings['scraper_enabled'] == 0 ? 'selected' : '' ?>>

                                Disabled

                            </option>

                        </select>

                    </div>

                    <!-- MAINTENANCE -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label fw-semibold">
                            Maintenance Mode
                        </label>

                        <select
                            name="maintenance_mode"
                            class="form-select"
                        >

                            <option value="0"
                                <?= $settings['maintenance_mode'] == 0 ? 'selected' : '' ?>>

                                OFF

                            </option>

                            <option value="1"
                                <?= $settings['maintenance_mode'] == 1 ? 'selected' : '' ?>>

                                ON

                            </option>

                        </select>

                    </div>

                </div>

                <!-- SAVE -->

                <button
                    type="submit"
                    class="btn btn-primary save-btn w-100"
                >

                    <i class="bi bi-save me-2"></i>

                    Save System Settings

                </button>

            </form>

        </div>

    </div>

    <!-- QUICK ACTIONS -->

    <div class="card settings-card shadow-sm">

        <div class="settings-header">

            <h4 class="mb-0">

                <i class="bi bi-lightning-charge-fill me-2"></i>

                Quick Actions

            </h4>

        </div>

        <div class="card-body p-4">

            <div class="d-flex flex-wrap gap-3">

                <a href="../run_scraper.php"
                   class="btn btn-success action-btn">

                    <i class="bi bi-cloud-download me-2"></i>

                    Run Scraper

                </a>

                <a href="../send_emails.php"
                   class="btn btn-warning text-white action-btn">

                    <i class="bi bi-envelope me-2"></i>

                    Send Emails

                </a>

                <a href="../cron.php"
                   class="btn btn-dark action-btn">

                    <i class="bi bi-arrow-repeat me-2"></i>

                    Run Full Cron

                </a>

                <a href="subscribe.php"
                   class="btn btn-primary action-btn">

                    <i class="bi bi-person-plus me-2"></i>

                    Add Subscriber

                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>