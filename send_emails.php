<?php

require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/mailer.php';
require_once __DIR__ . '/core/functions.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 20;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {

    $page = 1;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| HANDLE MANUAL SEND
|--------------------------------------------------------------------------
*/

$systemMessage = '';

if (isset($_GET['send'])) {

    $subscriberId = (int) $_GET['send'];

    /*
    |--------------------------------------------------------------------------
    | GET SUBSCRIBER
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT *
        FROM subscribers
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$subscriberId]);

    $subscriber = $stmt->fetch();

    if ($subscriber) {

        /*
        |--------------------------------------------------------------------------
        | GET SUBSCRIBER CATEGORIES
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare("
            SELECT category_id
            FROM subscriber_categories
            WHERE subscriber_id = ?
        ");

        $stmt->execute([$subscriberId]);

        $categoryIds = array_column(
            $stmt->fetchAll(),
            'category_id'
        );

        if (!empty($categoryIds)) {

            $placeholders = implode(
                ',',
                array_fill(0, count($categoryIds), '?')
            );

            /*
            |--------------------------------------------------------------------------
            | GET UNSENT JOBS
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    jobs.*,
                    job_categories.name AS category_name
                FROM jobs

                LEFT JOIN job_categories
                ON jobs.category_id = job_categories.id

                WHERE jobs.category_id IN ($placeholders)

                AND jobs.id NOT IN (

                    SELECT job_id
                    FROM sent_jobs
                    WHERE subscriber_id = ?

                )

                AND jobs.is_active = 1

                ORDER BY jobs.id DESC

                LIMIT 20
            ";

            $params = $categoryIds;

            $params[] = $subscriberId;

            $stmt = $db->prepare($sql);

            $stmt->execute($params);

            $jobs = $stmt->fetchAll();

            if ($jobs) {

                /*
                |--------------------------------------------------------------------------
                | EMAIL SUBJECT
                |--------------------------------------------------------------------------
                */

                $subject = "Latest Job Opportunities (" . count($jobs) . " Jobs)";

                /*
                |--------------------------------------------------------------------------
                | BUILD EMAIL BODY
                |--------------------------------------------------------------------------
                */

                $body = "
                <!DOCTYPE html>
                <html>
                <head>

                    <meta charset='UTF-8'>

                    <style>

                        body{
                            font-family:Arial,sans-serif;
                            background:#f4f6f9;
                            margin:0;
                            padding:20px;
                        }

                        .container{
                            max-width:700px;
                            margin:auto;
                            background:#ffffff;
                            border-radius:12px;
                            overflow:hidden;
                            box-shadow:0 4px 20px rgba(0,0,0,0.08);
                        }

                        .header{
                            background:#0f172a;
                            color:#ffffff;
                            padding:30px;
                            text-align:center;
                        }

                        .header h1{
                            margin:0;
                            font-size:30px;
                        }

                        .header p{
                            margin-top:10px;
                            color:#cbd5e1;
                        }

                        .job{
                            padding:20px;
                            border-bottom:1px solid #e2e8f0;
                        }

                        .job h3{
                            margin:0 0 10px;
                            font-size:20px;
                        }

                        .job h3 a{
                            text-decoration:none;
                            color:#2563eb;
                        }

                        .meta{
                            font-size:14px;
                            color:#64748b;
                            margin-bottom:10px;
                        }

                        .apply-btn{
                            display:inline-block;
                            background:#2563eb;
                            color:#ffffff !important;
                            text-decoration:none;
                            padding:10px 16px;
                            border-radius:8px;
                            font-size:14px;
                            margin-top:10px;
                        }

                        .footer{
                            padding:20px;
                            text-align:center;
                            font-size:13px;
                            background:#f8fafc;
                            color:#64748b;
                        }

                    </style>

                </head>

                <body>

                    <div class='container'>

                        <div class='header'>

                            <h1>
                                Uganda Job Aggregator
                            </h1>

                            <p>

                                Hello " . htmlspecialchars(
                                    $subscriber['full_name']
                                    ?: 'Subscriber'
                                ) . ", here are your latest jobs.

                            </p>

                        </div>
                ";

                foreach ($jobs as $job) {

                    $body .= "

                        <div class='job'>

                            <h3>

                                <a
                                    href='" . htmlspecialchars($job['apply_url']) . "'
                                    target='_blank'
                                >

                                    " . htmlspecialchars($job['title']) . "

                                </a>

                            </h3>

                            <div class='meta'>

                                Company:
                                " . htmlspecialchars($job['company_name']) . "

                            </div>

                            <div class='meta'>

                                Category:
                                " . htmlspecialchars($job['category_name'] ?? 'Other') . "

                            </div>

                            <div class='meta'>

                                Location:
                                " . htmlspecialchars($job['location']) . "

                            </div>

                            <a
                                href='" . htmlspecialchars($job['apply_url']) . "'
                                target='_blank'
                                class='apply-btn'
                            >

                                Apply Now

                            </a>

                        </div>
                    ";
                }

                $body .= "

                        <div class='footer'>

                            Generated on " . date('d M Y H:i:s') . "

                        </div>

                    </div>

                </body>

                </html>
                ";

                /*
                |--------------------------------------------------------------------------
                | SEND EMAIL
                |--------------------------------------------------------------------------
                */

                $sent = sendJobEmail(

                    $subscriber['email'],
                    $subject,
                    $body

                );

                /*
                |--------------------------------------------------------------------------
                | SAVE EMAIL LOG
                |--------------------------------------------------------------------------
                */

                $status = $sent
                    ? 'Sent'
                    : 'Failed';

                $stmt = $db->prepare("
                    INSERT INTO email_logs (

                        subscriber_id,
                        subject,
                        body,
                        status,
                        sent_at

                    )

                    VALUES (?, ?, ?, ?, NOW())
                ");

                $stmt->execute([

                    $subscriberId,
                    $subject,
                    $body,
                    $status

                ]);

                /*
                |--------------------------------------------------------------------------
                | MARK JOBS AS EMAILED
                |--------------------------------------------------------------------------
                */

                if ($sent) {

                    foreach ($jobs as $job) {

                        $stmt = $db->prepare("
                            INSERT IGNORE INTO sent_jobs (

                                subscriber_id,
                                job_id

                            )

                            VALUES (?, ?)
                        ");

                        $stmt->execute([

                            $subscriberId,
                            $job['id']

                        ]);
                    }

                    $systemMessage = "
                        <div class='alert alert-success border-0 shadow-sm'>
                            Email sent successfully to:
                            <strong>
                                " . htmlspecialchars($subscriber['email']) . "
                            </strong>
                        </div>
                    ";

                } else {

                    $systemMessage = "
                        <div class='alert alert-danger border-0 shadow-sm'>
                            Failed to send email.
                        </div>
                    ";
                }

            } else {

                $systemMessage = "
                    <div class='alert alert-warning border-0 shadow-sm'>
                        No new jobs available for this subscriber.
                    </div>
                ";
            }

        } else {

            $systemMessage = "
                <div class='alert alert-warning border-0 shadow-sm'>
                    Subscriber has no selected categories.
                </div>
            ";
        }
    }
}

/*
|--------------------------------------------------------------------------
| SUBSCRIBERS
|--------------------------------------------------------------------------
*/

$totalSubscribers = $db->query("
    SELECT COUNT(*) total
    FROM subscribers
")->fetch()['total'];

$totalPages = ceil($totalSubscribers / $perPage);

if ($totalPages < 1) {

    $totalPages = 1;
}

$stmt = $db->prepare("
    SELECT *
    FROM subscribers
    ORDER BY id DESC
    LIMIT $perPage OFFSET $offset
");

$stmt->execute();

$subscribers = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalEmails = $db->query("
    SELECT COUNT(*) total
    FROM email_logs
")->fetch()['total'];

$totalSent = $db->query("
    SELECT COUNT(*) total
    FROM email_logs
    WHERE status = 'Sent'
")->fetch()['total'];

$totalFailed = $db->query("
    SELECT COUNT(*) total
    FROM email_logs
    WHERE status = 'Failed'
")->fetch()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Send Emails
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

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
            background:#ffffff;
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

        .red{
            background:#dc2626;
        }

        .table-card{
            border:none;
            border-radius:20px;
            overflow:hidden;
        }

        .table thead{
            background:#0f172a;
            color:#fff;
        }

        .badge-status{
            padding:7px 12px;
            border-radius:10px;
            font-size:12px;
            font-weight:600;
        }

        .active-badge{
            background:#dcfce7;
            color:#166534;
        }

        .inactive-badge{
            background:#fee2e2;
            color:#991b1b;
        }

        @media(max-width:992px){

            .main-wrapper{
                margin-left:80px;
            }
        }

    </style>

</head>

<body>

<?php include 'sidebar/sidebar.php'; ?>

<div class="main-wrapper">

    <!-- HEADER -->

    <div class="top-header d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="mb-1">
                Email Delivery Center
            </h2>

            <small class="text-muted">
                Manage subscribers, send emails and monitor delivery
            </small>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="cron.php"
               class="btn btn-dark">

                <i class="bi bi-arrow-repeat me-2"></i>

                Run Full Cron

            </a>

            <a href="run_scraper.php"
               class="btn btn-success">

                <i class="bi bi-cloud-download me-2"></i>

                Run Scraper

            </a>

        </div>

    </div>

    <!-- ALERT -->

    <?= $systemMessage ?>

    <!-- STATS -->

    <div class="row mb-4">

        <div class="col-lg-3 col-md-6 mb-3">

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

                    <div class="icon-box blue">

                        <i class="bi bi-people"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Emails Logged
                        </h6>

                        <h2>
                            <?= number_format($totalEmails) ?>
                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-envelope"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Emails Sent
                        </h6>

                        <h2>
                            <?= number_format($totalSent) ?>
                        </h2>

                    </div>

                    <div class="icon-box green">

                        <i class="bi bi-check-circle"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Failed Emails
                        </h6>

                        <h2>
                            <?= number_format($totalFailed) ?>
                        </h2>

                    </div>

                    <div class="icon-box red">

                        <i class="bi bi-x-circle"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- SUBSCRIBERS -->

    <div class="card table-card shadow-sm">

        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">

            <h5 class="mb-0">
                Subscribers
            </h5>

            <span class="badge bg-dark">

                <?= number_format($totalSubscribers) ?> Subscribers

            </span>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Name</th>

                            <th>Email</th>

                            <th>Country</th>

                            <th>Frequency</th>

                            <th>Status</th>

                            <th>Joined</th>

                            <th width="180">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if(count($subscribers) > 0): ?>

                        <?php foreach($subscribers as $subscriber): ?>

                            <tr>

                                <td>

                                    <?= htmlspecialchars(
                                        $subscriber['full_name']
                                        ?: 'Unknown'
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($subscriber['email']) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($subscriber['country']) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($subscriber['preferred_frequency']) ?>

                                </td>

                                <td>

                                    <?php if($subscriber['is_active']): ?>

                                        <span class="badge-status active-badge">

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span class="badge-status inactive-badge">

                                            Disabled

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($subscriber['created_at'])
                                    ) ?>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <a
                                            href="?send=<?= $subscriber['id'] ?>"
                                            class="btn btn-sm btn-primary"
                                        >

                                            <i class="bi bi-send"></i>

                                        </a>

                                        <a
                                            href="manage/subscribe.php"
                                            class="btn btn-sm btn-success"
                                        >

                                            <i class="bi bi-person-plus"></i>

                                        </a>

                                        <a
                                            href="manage/settings.php"
                                            class="btn btn-sm btn-dark"
                                        >

                                            <i class="bi bi-gear"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="text-center py-5">

                                <div class="text-muted">

                                    <i class="bi bi-envelope-x fs-1"></i>

                                    <p class="mt-3 mb-0">

                                        No subscribers found

                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- PAGINATION -->

            <?php if($totalPages > 1): ?>

                <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 gap-3">

                    <div class="text-muted">

                        Page <?= $page ?> of <?= $totalPages ?>

                    </div>

                    <nav>

                        <ul class="pagination mb-0">

                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page - 1 ?>"
                                >

                                    Previous

                                </a>

                            </li>

                            <?php for($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>"
                                    >

                                        <?= $i ?>

                                    </a>

                                </li>

                            <?php endfor; ?>

                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page + 1 ?>"
                                >

                                    Next

                                </a>

                            </li>

                        </ul>

                    </nav>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>