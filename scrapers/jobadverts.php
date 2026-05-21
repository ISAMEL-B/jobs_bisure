<?php

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| GET STATISTICS
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

$totalFailedJobs = $db->query("
    SELECT COUNT(*) total
    FROM failed_jobs
")->fetch()['total'];

$totalSources = $db->query("
    SELECT COUNT(*) total
    FROM job_sources
")->fetch()['total'];

$totalCategories = $db->query("
    SELECT COUNT(*) total
    FROM job_categories
")->fetch()['total'];

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

        Automation Console

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

    <style>

        body{

            background:#f1f5f9;
            overflow-x:hidden;
            font-family:Arial,sans-serif;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN WRAPPER
        |--------------------------------------------------------------------------
        */

        .main-wrapper{

            margin-left:270px;
            padding:30px;
            transition:0.3s;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .top-header{

            background:#ffffff;
            border-radius:24px;
            padding:30px;
            margin-bottom:25px;

            box-shadow:
                0 10px 30px rgba(0,0,0,0.05);
        }

        .gradient-text{

            background:linear-gradient(
                135deg,
                #2563eb,
                #7c3aed
            );

            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }

        /*
        |--------------------------------------------------------------------------
        | STATS CARDS
        |--------------------------------------------------------------------------
        */

        .stats-card{

            border:none;
            border-radius:22px;
            overflow:hidden;
            transition:0.3s;
            background:#ffffff;
        }

        .stats-card:hover{

            transform:translateY(-6px);
        }

        .icon-box{

            width:65px;
            height:65px;

            border-radius:18px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:#fff;
            font-size:26px;
        }

        .blue{
            background:linear-gradient(135deg,#2563eb,#1d4ed8);
        }

        .green{
            background:linear-gradient(135deg,#059669,#047857);
        }

        .orange{
            background:linear-gradient(135deg,#d97706,#b45309);
        }

        .red{
            background:linear-gradient(135deg,#dc2626,#991b1b);
        }

        .purple{
            background:linear-gradient(135deg,#7c3aed,#6d28d9);
        }

        .dark{
            background:linear-gradient(135deg,#111827,#1f2937);
        }

        /*
        |--------------------------------------------------------------------------
        | TERMINAL
        |--------------------------------------------------------------------------
        */

        .terminal-card{

            border:none;
            border-radius:24px;
            overflow:hidden;
        }

        .terminal-header{

            background:#111827;
            color:#fff;

            padding:20px 25px;

            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .terminal-body{

            background:#020617;
            color:#22c55e;

            padding:25px;

            min-height:420px;

            font-family:Consolas,monospace;

            overflow:auto;
        }

        .terminal-line{

            margin-bottom:14px;
            animation:fadeIn 0.4s ease;
        }

        @keyframes fadeIn{

            from{
                opacity:0;
                transform:translateY(5px);
            }

            to{
                opacity:1;
                transform:translateY(0);
            }
        }

        .success{
            color:#22c55e;
        }

        .warning{
            color:#facc15;
        }

        .danger{
            color:#ef4444;
        }

        .info{
            color:#38bdf8;
        }

        /*
        |--------------------------------------------------------------------------
        | QUICK ACTIONS
        |--------------------------------------------------------------------------
        */

        .quick-card{

            border:none;
            border-radius:22px;
            overflow:hidden;
        }

        .action-btn{

            border-radius:16px;
            padding:14px 20px;
            font-weight:600;
            transition:0.3s;
        }

        .action-btn:hover{

            transform:translateY(-3px);
        }

        /*
        |--------------------------------------------------------------------------
        | SCROLLBAR
        |--------------------------------------------------------------------------
        */

        .terminal-body::-webkit-scrollbar{

            width:8px;
        }

        .terminal-body::-webkit-scrollbar-thumb{

            background:#334155;
            border-radius:20px;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media(max-width:992px){

            .main-wrapper{

                margin-left:85px;
                padding:20px;
            }
        }

    </style>

</head>

<body>

<!-- MAIN WRAPPER -->

<div class="main-wrapper">

    <!-- HEADER -->

    <div class="top-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h2 class="fw-bold mb-2 gradient-text">

                    Automation & Cron Console

                </h2>

                <p class="text-muted mb-0">

                    Run automated scraping, email delivery,
                    cron monitoring and system maintenance.

                </p>

            </div>

            <div>

                <span class="badge bg-success px-4 py-3 fs-6 shadow-sm">

                    <i class="bi bi-check-circle-fill me-2"></i>

                    System Online

                </span>

            </div>

        </div>

    </div>

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

                        <h2 class="fw-bold">

                            <?= number_format($totalJobs) ?>

                        </h2>

                    </div>

                    <div class="icon-box blue">

                        <i class="bi bi-briefcase-fill"></i>

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

                        <h2 class="fw-bold">

                            <?= number_format($totalSubscribers) ?>

                        </h2>

                    </div>

                    <div class="icon-box green">

                        <i class="bi bi-people-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- EMAILS -->

        <div class="col-lg-4 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">

                            Emails Sent

                        </h6>

                        <h2 class="fw-bold">

                            <?= number_format($totalEmails) ?>

                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-envelope-check-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- FAILED -->

        <div class="col-lg-4 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">

                            Failed Jobs

                        </h6>

                        <h2 class="fw-bold">

                            <?= number_format($totalFailedJobs) ?>

                        </h2>

                    </div>

                    <div class="icon-box red">

                        <i class="bi bi-bug-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- SOURCES -->

        <div class="col-lg-4 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">

                            Job Sources

                        </h6>

                        <h2 class="fw-bold">

                            <?= number_format($totalSources) ?>

                        </h2>

                    </div>

                    <div class="icon-box purple">

                        <i class="bi bi-database-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- CATEGORIES -->

        <div class="col-lg-4 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">

                            Categories

                        </h6>

                        <h2 class="fw-bold">

                            <?= number_format($totalCategories) ?>

                        </h2>

                    </div>

                    <div class="icon-box dark">

                        <i class="bi bi-tags-fill"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- QUICK ACTIONS -->

    <div class="card quick-card shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h5 class="mb-1">

                        Quick Actions

                    </h5>

                    <small class="text-muted">

                        Run system tools and monitor automation.

                    </small>

                </div>

                <div class="d-flex flex-wrap gap-2">

                    <a
                        href="run_scraper.php"
                        class="btn btn-primary action-btn"
                    >

                        <i class="bi bi-cloud-download-fill me-2"></i>

                        Run Scraper

                    </a>

                    <a
                        href="send_emails.php"
                        class="btn btn-success action-btn"
                    >

                        <i class="bi bi-envelope-fill me-2"></i>

                        Send Emails

                    </a>

                    <a
                        href="manage/failed_jobs.php"
                        class="btn btn-danger action-btn"
                    >

                        <i class="bi bi-bug-fill me-2"></i>

                        Failed Jobs

                    </a>

                </div>

            </div>

        </div>

    </div>

    <!-- TERMINAL -->

    <div class="card terminal-card shadow-sm">

        <!-- HEADER -->

        <div class="terminal-header">

            <div>

                <i class="bi bi-terminal-fill me-2"></i>

                Live Automation Console

            </div>

            <div>

                <span class="badge bg-success px-3 py-2">

                    Running

                </span>

            </div>

        </div>

        <!-- BODY -->

        <div class="terminal-body">

            <div class="terminal-line success">

                [SYSTEM] Initializing automation console...

            </div>

            <div class="terminal-line info">

                [INFO] Loading cron services...

            </div>

            <div class="terminal-line">

                --------------------------------------------------

            </div>

<?php

/*
|--------------------------------------------------------------------------
| RUN SCRAPER
|--------------------------------------------------------------------------
*/

echo "<div class='terminal-line warning'>";

echo "[SCRAPER] Starting job scraping engine...";

echo "</div>";

require_once __DIR__ . '/../run_scraper.php';

/*
|--------------------------------------------------------------------------
| SEND EMAILS
|--------------------------------------------------------------------------
*/

echo "<div class='terminal-line warning'>";

echo "[EMAILS] Sending subscriber notifications...";

echo "</div>";

require_once __DIR__ . '/../send_emails.php';

/*
|--------------------------------------------------------------------------
| FINISHED
|--------------------------------------------------------------------------
*/

echo "<div class='terminal-line'>";

echo "--------------------------------------------------";

echo "</div>";

echo "<div class='terminal-line success'>";

echo "[SUCCESS] Automation completed successfully.";

echo "</div>";

echo "<div class='terminal-line info'>";

echo "[TIME] " . date('d M Y H:i:s');

echo "</div>";

echo "<div class='terminal-line success'>";

echo "[SYSTEM] All cron operations executed successfully.";

echo "</div>";

?>

        </div>

    </div>

</div>

</body>
</html>