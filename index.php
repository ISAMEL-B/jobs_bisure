<?php

require_once __DIR__ . '/core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| DASHBOARD STATS
|--------------------------------------------------------------------------
*/

$totalJobs = $db->query("
    SELECT COUNT(*) as total
    FROM jobs
")->fetch()['total'];

$totalSubscribers = $db->query("
    SELECT COUNT(*) as total
    FROM subscribers
")->fetch()['total'];

$totalEmails = $db->query("
    SELECT COUNT(*) as total
    FROM email_logs
")->fetch()['total'];

$totalCategories = $db->query("
    SELECT COUNT(*) as total
    FROM job_categories
")->fetch()['total'];

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$jobsPerPage = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if($page < 1){

    $page = 1;
}

$offset = ($page - 1) * $jobsPerPage;

/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

$totalPages = ceil($totalJobs / $jobsPerPage);

/*
|--------------------------------------------------------------------------
| FETCH JOBS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT jobs.*, job_categories.name AS category_name
    FROM jobs
    LEFT JOIN job_categories
    ON jobs.category_id = job_categories.id
    ORDER BY jobs.id DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(
    ':limit',
    $jobsPerPage,
    PDO::PARAM_INT
);

$stmt->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();

$latestJobs = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Uganda Job Aggregator</title>

    <!-- BOOTSTRAP -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->

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

        .top-navbar{

            background:#ffffff;

            border-radius:18px;

            padding:22px 25px;

            margin-bottom:25px;

            box-shadow:0 4px 20px rgba(0,0,0,0.05);
        }

        .stat-card{

            border:none;

            border-radius:18px;

            overflow:hidden;

            transition:0.3s;
        }

        .stat-card:hover{

            transform:translateY(-4px);
        }

        .stat-icon{

            width:60px;

            height:60px;

            border-radius:15px;

            display:flex;

            align-items:center;

            justify-content:center;

            color:#fff;

            font-size:26px;
        }

        .jobs-bg{
            background:#2563eb;
        }

        .subs-bg{
            background:#059669;
        }

        .emails-bg{
            background:#d97706;
        }

        .cats-bg{
            background:#7c3aed;
        }

        .activity-box{

            background:#0f172a;

            color:#e2e8f0;

            min-height:200px;

            max-height:450px;

            overflow:auto;

            font-family:monospace;

            padding:20px;

            border-radius:0 0 18px 18px;
        }

        .table-card{

            border:none;

            border-radius:18px;

            overflow:hidden;
        }

        .table thead{

            background:#111827;

            color:#fff;
        }

        .table td{

            vertical-align:middle;
        }

        .badge-category{

            background:#2563eb;

            color:#fff;

            padding:6px 12px;

            border-radius:8px;

            font-size:12px;
        }

        .quick-btn{

            border-radius:12px;

            padding:11px 18px;

            font-weight:600;
        }

        @media(max-width:992px){

            .main-wrapper{

                margin-left:80px;
            }
        }
        .pagination .page-link{

            border:none;

            margin:0 3px;

            border-radius:10px;

            color:#111827;

            padding:10px 15px;

            font-weight:600;
        }

        .pagination .active .page-link{

            background:#111827;

            color:#fff;
        }

        .table tbody tr{

            transition:0.2s;
        }

        .table tbody tr:hover{

            transform:scale(1.002);

            background:#f8fafc;
        }

        #jobSearch{

            border-radius:12px;

            border:1px solid #cbd5e1;
        }
    </style>

</head>

<body>

<!-- SIDEBAR -->

<?php include 'sidebar/sidebar.php'; ?>

<!-- MAIN CONTENT -->

<div class="main-wrapper">

    <!-- TOP NAVBAR -->

    <div class="top-navbar d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h3 class="mb-1">
                Uganda Job Aggregator
            </h3>

            <small class="text-muted">
                Smart Job Scraping & Email Automation System
            </small>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <!-- RUN SCRAPER -->

            <button
                id="runScraperBtn"
                class="btn btn-success quick-btn d-flex align-items-center gap-2"
            >

                <span
                    id="scraperSpinner"
                    class="spinner-border spinner-border-sm d-none"
                ></span>

                <i class="bi bi-cloud-download"></i>

                Run Scraper

            </button>

            <!-- SEND EMAILS -->

            <button
                id="runEmailsBtn"
                class="btn btn-warning text-white quick-btn d-flex align-items-center gap-2"
            >

                <span
                    id="emailsSpinner"
                    class="spinner-border spinner-border-sm d-none"
                ></span>

                <i class="bi bi-envelope"></i>

                Send Emails

            </button>

            <!-- RUN FULL CRON -->

            <button
                id="runCronBtn"
                class="btn btn-dark quick-btn d-flex align-items-center gap-2"
            >

                <span
                    id="cronSpinner"
                    class="spinner-border spinner-border-sm d-none"
                ></span>

                <i class="bi bi-arrow-repeat"></i>

                Run Full Cron

            </button>

        </div>

    </div>

    <!-- STATS -->

    <div class="row mb-4">

        <!-- JOBS -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Total Jobs
                        </h6>

                        <h2>
                            <?= number_format($totalJobs) ?>
                        </h2>

                    </div>

                    <div class="stat-icon jobs-bg">

                        <i class="bi bi-briefcase"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- SUBSCRIBERS -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Subscribers
                        </h6>

                        <h2>
                            <?= number_format($totalSubscribers) ?>
                        </h2>

                    </div>

                    <div class="stat-icon subs-bg">

                        <i class="bi bi-people"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- EMAILS -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Emails Sent
                        </h6>

                        <h2>
                            <?= number_format($totalEmails) ?>
                        </h2>

                    </div>

                    <div class="stat-icon emails-bg">

                        <i class="bi bi-envelope-check"></i>

                    </div>

                </div>

            </div>

        </div>

        <!-- CATEGORIES -->

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Categories
                        </h6>

                        <h2>
                            <?= number_format($totalCategories) ?>
                        </h2>

                    </div>

                    <div class="stat-icon cats-bg">

                        <i class="bi bi-tags"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- SYSTEM CONSOLE -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

            <strong>
                System Activity Console
            </strong>

            <button
                class="btn btn-sm btn-dark"
                id="toggleConsoleBtn"
            >

                <i class="bi bi-terminal"></i>

                Show Console

            </button>

        </div>

        <div
            id="consoleWrapper"
            style="display:none;"
        >

            <div
                class="activity-box"
                id="systemOutput"
            >

                Waiting for system activity...

            </div>

        </div>

    </div>

    <!-- LATEST JOBS -->

    <!-- LATEST JOBS -->

    <div class="card table-card shadow-sm border-0">

        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">

            <div>

                <h5 class="mb-1">
                    Latest Jobs
                </h5>

                <small class="text-muted">
                    Showing <?= count($latestJobs) ?> jobs from database
                </small>

            </div>

            <div class="d-flex gap-2">

                <input
                    type="text"
                    id="jobSearch"
                    class="form-control"
                    placeholder="Search jobs..."
                    style="width:220px;"
                >

                <button class="btn btn-dark">

                    <i class="bi bi-search"></i>

                </button>

            </div>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle" id="jobsTable">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Job Title</th>

                            <th>Company</th>

                            <th>Category</th>

                            <th>Location</th>

                            <th>Date Posted</th>

                            <th>Status</th>

                            <th>Apply</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if(count($latestJobs) > 0): ?>

                        <?php
                        $counter = $offset + 1;
                        ?>

                        <?php foreach ($latestJobs as $job): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= $counter++ ?>
                                    </strong>

                                </td>

                                <td>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars($job['title']) ?>

                                    </div>

                                </td>

                                <td>

                                    <?= htmlspecialchars($job['company_name']) ?>

                                </td>

                                <td>

                                    <span class="badge-category">

                                        <?= htmlspecialchars($job['category_name'] ?? 'Other') ?>

                                    </span>

                                </td>

                                <td>

                                    <i class="bi bi-geo-alt text-danger"></i>

                                    <?= htmlspecialchars($job['location']) ?>

                                </td>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($job['posted_date'])
                                    ) ?>

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                </td>

                                <td>

                                    <a
                                        href="<?= htmlspecialchars($job['apply_url']) ?>"
                                        target="_blank"
                                        class="btn btn-primary btn-sm"
                                    >

                                        <i class="bi bi-box-arrow-up-right"></i>

                                        Apply

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="text-center py-5">

                                <div class="text-muted">

                                    <i class="bi bi-database-x fs-1"></i>

                                    <p class="mt-2 mb-0">
                                        No jobs found
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- PAGINATION -->

            <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">

                <div class="text-muted small">

                    Page <?= $page ?> of <?= $totalPages ?>

                </div>

                <nav>

                    <ul class="pagination mb-0">

                        <!-- PREVIOUS -->

                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">

                            <a
                                class="page-link"
                                href="?page=<?= $page - 1 ?>"
                            >

                                Previous

                            </a>

                        </li>

                        <!-- PAGE NUMBERS -->

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

                        <!-- NEXT -->

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

        </div>

    </div>

</div>

<!-- JAVASCRIPT -->

<script>

/*
|--------------------------------------------------------------------------
| CONSOLE TOGGLE
|--------------------------------------------------------------------------
*/

const toggleConsoleBtn =
    document.getElementById('toggleConsoleBtn');

const consoleWrapper =
    document.getElementById('consoleWrapper');

let consoleVisible = false;

toggleConsoleBtn.addEventListener('click', function(){

    consoleVisible = !consoleVisible;

    if(consoleVisible){

        consoleWrapper.style.display = 'block';

        toggleConsoleBtn.innerHTML = `
            <i class="bi bi-eye-slash"></i>
            Hide Console
        `;

    } else {

        consoleWrapper.style.display = 'none';

        toggleConsoleBtn.innerHTML = `
            <i class="bi bi-terminal"></i>
            Show Console
        `;
    }

});

/*
|--------------------------------------------------------------------------
| SYSTEM TASK FUNCTION
|--------------------------------------------------------------------------
*/

function runSystemTask(buttonId, spinnerId, url) {

    const button = document.getElementById(buttonId);

    const spinner = document.getElementById(spinnerId);

    const output = document.getElementById('systemOutput');

    /*
    |--------------------------------------------------------------------------
    | AUTO SHOW CONSOLE
    |--------------------------------------------------------------------------
    */

    consoleWrapper.style.display = 'block';

    consoleVisible = true;

    toggleConsoleBtn.innerHTML = `
        <i class="bi bi-eye-slash"></i>
        Hide Console
    `;

    spinner.classList.remove('d-none');

    button.disabled = true;

    output.innerHTML = `
        <div class="text-info">
            Running process... please wait...
        </div>
    `;

    fetch(url)

    .then(response => response.text())

    .then(data => {

        output.innerHTML = data;

    })

    .catch(error => {

        output.innerHTML = `
            <div class="text-danger">
                Error: ${error}
            </div>
        `;

    })

    .finally(() => {

        spinner.classList.add('d-none');

        button.disabled = false;

    });
}

/*
|--------------------------------------------------------------------------
| RUN SCRAPER
|--------------------------------------------------------------------------
*/

document
.getElementById('runScraperBtn')
.addEventListener('click', function(){

    runSystemTask(
        'runScraperBtn',
        'scraperSpinner',
        'run_scraper.php'
    );

});

/*
|--------------------------------------------------------------------------
| SEND EMAILS
|--------------------------------------------------------------------------
*/

document
.getElementById('runEmailsBtn')
.addEventListener('click', function(){

    runSystemTask(
        'runEmailsBtn',
        'emailsSpinner',
        'send_emails.php'
    );

});

/*
|--------------------------------------------------------------------------
| RUN FULL CRON
|--------------------------------------------------------------------------
*/

document
.getElementById('runCronBtn')
.addEventListener('click', function(){

    runSystemTask(
        'runCronBtn',
        'cronSpinner',
        'cron.php'
    );

});

/*
|--------------------------------------------------------------------------
| LIVE SEARCH / AUTOCOMPLETE FILTER
|--------------------------------------------------------------------------
*/

const jobSearch =
    document.getElementById('jobSearch');

const jobsTable =
    document.getElementById('jobsTable');

const tableRows =
    jobsTable.querySelectorAll('tbody tr');

jobSearch.addEventListener('keyup', function(){

    const searchValue =
        this.value.toLowerCase().trim();

    let visibleCount = 0;

    tableRows.forEach(function(row){

        const columns =
            row.querySelectorAll('td');

        /*
        |--------------------------------------------------------------------------
        | SKIP EMPTY ROW
        |--------------------------------------------------------------------------
        */

        if(columns.length < 8){
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | TARGET COLUMNS
        |--------------------------------------------------------------------------
        */

        const title =
            columns[1].innerText.toLowerCase();

        const company =
            columns[2].innerText.toLowerCase();

        const category =
            columns[3].innerText.toLowerCase();

        const location =
            columns[4].innerText.toLowerCase();

        /*
        |--------------------------------------------------------------------------
        | MATCH SEARCH
        |--------------------------------------------------------------------------
        */

        const matches =
            title.includes(searchValue) ||
            company.includes(searchValue) ||
            category.includes(searchValue) ||
            location.includes(searchValue);

        if(matches){

            row.style.display = '';

            visibleCount++;

        } else {

            row.style.display = 'none';
        }

    });

    /*
    |--------------------------------------------------------------------------
    | NO RESULTS MESSAGE
    |--------------------------------------------------------------------------
    */

    let noResults =
        document.getElementById('noResultsRow');

    if(visibleCount === 0){

        if(!noResults){

            noResults = document.createElement('tr');

            noResults.id = 'noResultsRow';

            noResults.innerHTML = `
                <td colspan="8" class="text-center py-5">

                    <div class="text-muted">

                        <i class="bi bi-search fs-1"></i>

                        <p class="mt-3 mb-0">
                            No matching jobs found
                        </p>

                    </div>

                </td>
            `;

            jobsTable
            .querySelector('tbody')
            .appendChild(noResults);
        }

    } else {

        if(noResults){
            noResults.remove();
        }
    }

});

/*
|--------------------------------------------------------------------------
| AUTO FOCUS SEARCH
|--------------------------------------------------------------------------
*/

window.addEventListener('load', function(){

    jobSearch.focus();

});

</script>

</body>
</html>