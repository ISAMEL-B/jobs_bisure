<?php

// session_start();

require_once __DIR__ . '/core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 12;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {

    $page = 1;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| TOTAL JOBS
|--------------------------------------------------------------------------
*/

$totalJobsQuery = $db->query("
    SELECT COUNT(*) total
    FROM jobs
    WHERE is_active = 1
");

$totalJobs = $totalJobsQuery->fetch()['total'];

$totalPages = ceil($totalJobs / $perPage);

if ($totalPages < 1) {

    $totalPages = 1;
}

/*
|--------------------------------------------------------------------------
| FETCH JOBS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        jobs.*,
        job_categories.name AS category_name,
        job_sources.name AS source_name

    FROM jobs

    LEFT JOIN job_categories
        ON jobs.category_id = job_categories.id

    LEFT JOIN job_sources
        ON jobs.source_id = job_sources.id

    WHERE jobs.is_active = 1

    ORDER BY jobs.created_at DESC

    LIMIT $perPage OFFSET $offset
");

$stmt->execute();

$jobs = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $db->query("
    SELECT *
    FROM job_categories
    ORDER BY name ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| SUBSCRIBE
|--------------------------------------------------------------------------
*/

$success = '';
$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['subscribe_now'])
) {

    $fullName  = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $country   = trim($_POST['country'] ?? 'Uganda');
    $frequency = trim($_POST['frequency'] ?? 'Daily');

    $selectedCategories = $_POST['categories'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        empty($fullName)
        ||
        empty($email)
        ||
        empty($phone)
    ) {

        $error = "Please fill in Full Name, Email and Phone Number.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare("
            SELECT id
            FROM subscribers
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $existingSubscriber = $stmt->fetch();

        if ($existingSubscriber) {

            $subscriberId = $existingSubscriber['id'];

        } else {

            /*
            |--------------------------------------------------------------------------
            | INSERT SUBSCRIBER
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                INSERT INTO subscribers (

                    email,
                    full_name,
                    phone,
                    country,
                    preferred_frequency,
                    is_active,
                    created_at

                )

                VALUES (

                    ?, ?, ?, ?, ?, 1, NOW()

                )
            ");

            $stmt->execute([

                $email,
                $fullName,
                $phone,
                $country,
                $frequency

            ]);

            $subscriberId = $db->lastInsertId();
        }

        /*
        |--------------------------------------------------------------------------
        | REMOVE OLD CATEGORIES
        |--------------------------------------------------------------------------
        */

        $deleteStmt = $db->prepare("
            DELETE FROM subscriber_categories
            WHERE subscriber_id = ?
        ");

        $deleteStmt->execute([$subscriberId]);

        /*
        |--------------------------------------------------------------------------
        | IF NONE SELECTED -> SUBSCRIBE TO ALL
        |--------------------------------------------------------------------------
        */

        if (empty($selectedCategories)) {

            $allCategories = $db->query("
                SELECT id
                FROM job_categories
            ")->fetchAll();

            foreach ($allCategories as $cat) {

                $insertCat = $db->prepare("
                    INSERT IGNORE INTO subscriber_categories (
                        subscriber_id,
                        category_id
                    )
                    VALUES (?, ?)
                ");

                $insertCat->execute([

                    $subscriberId,
                    $cat['id']

                ]);
            }

        } else {

            foreach ($selectedCategories as $categoryId) {

                $insertCat = $db->prepare("
                    INSERT IGNORE INTO subscriber_categories (
                        subscriber_id,
                        category_id
                    )
                    VALUES (?, ?)
                ");

                $insertCat->execute([

                    $subscriberId,
                    $categoryId

                ]);
            }
        }

        $success = "
            You have successfully subscribed to
            Job Finder Services.
        ";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Uganda Job Finder
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            background:#f1f5f9;
            font-family:Inter, sans-serif;
            overflow-x:hidden;
        }

        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .hero{

            background:
            linear-gradient(
                135deg,
                rgba(15,23,42,0.95),
                rgba(37,99,235,0.90)
            ),
            url('https://images.unsplash.com/photo-1521791136064-7986c2920216?q=80&w=1400');

            background-size:cover;
            background-position:center;

            padding:90px 20px;
            color:#fff;
            text-align:center;
            position:relative;
            overflow:hidden;
        }

        .hero::before{

            content:'';

            position:absolute;
            width:500px;
            height:500px;

            background:rgba(255,255,255,0.05);

            border-radius:50%;

            top:-200px;
            right:-100px;
        }

        .hero h1{

            font-size:58px;
            font-weight:800;
            margin-bottom:20px;
            position:relative;
            z-index:2;
        }

        .hero p{

            font-size:20px;
            color:#dbeafe;
            max-width:800px;
            margin:auto;
            position:relative;
            z-index:2;
        }

        /*
        |--------------------------------------------------------------------------
        | MARQUEE
        |--------------------------------------------------------------------------
        */

        .marquee-wrapper{

            background:#111827;
            overflow:hidden;
            white-space:nowrap;
            padding:12px 0;
            border-top:4px solid #2563eb;
        }

        .marquee{

            display:inline-block;
            padding-left:100%;
            animation:moveText 18s linear infinite;

            color:#facc15;
            font-weight:700;
            font-size:18px;
        }

        @keyframes moveText{

            0%{
                transform:translateX(0);
            }

            100%{
                transform:translateX(-100%);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        .filters-section{

            padding:35px 20px;
        }

        .filter-box{

            background:#fff;
            border-radius:25px;
            padding:25px;
            box-shadow:0 10px 30px rgba(0,0,0,0.06);
        }

        .filter-btn{

            border:none;
            background:#e2e8f0;
            padding:14px 20px;
            border-radius:14px;
            font-weight:600;
            width:100%;
            text-align:left;
            transition:0.3s;
        }

        .filter-btn:hover{

            background:#2563eb;
            color:#fff;
        }

        .dropdown-panel{

            display:none;
            margin-top:15px;
            background:#f8fafc;
            padding:15px;
            border-radius:16px;
            border:1px solid #e2e8f0;
        }

        /*
        |--------------------------------------------------------------------------
        | JOBS
        |--------------------------------------------------------------------------
        */

        .jobs-section{

            padding:10px 20px 80px;
        }

        .job-card{

            background:#fff;
            border-radius:26px;
            padding:28px;
            margin-bottom:25px;

            transition:0.3s;

            border:1px solid #e2e8f0;

            position:relative;

            overflow:hidden;
        }

        .job-card:hover{

            transform:translateY(-8px);
            box-shadow:0 18px 45px rgba(0,0,0,0.10);
        }

        .job-number{

            position:absolute;
            top:0;
            right:0;

            background:#2563eb;
            color:#fff;

            padding:12px 18px;

            border-bottom-left-radius:18px;

            font-weight:700;
        }

        .job-title{

            font-size:26px;
            font-weight:800;
            margin-bottom:10px;
            color:#0f172a;
        }

        .job-company{

            color:#475569;
            margin-bottom:15px;
            font-weight:600;
        }

        .job-meta{

            display:flex;
            flex-wrap:wrap;
            gap:12px;
            margin-bottom:20px;
        }

        .job-badge{

            background:#eff6ff;
            color:#2563eb;

            padding:10px 14px;
            border-radius:999px;

            font-size:13px;
            font-weight:700;
        }

        .job-description{

            color:#475569;
            line-height:1.8;
            margin-bottom:25px;
        }

        .apply-btn{

            background:linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

            color:#fff;
            border:none;

            padding:14px 28px;
            border-radius:14px;

            font-weight:700;
            text-decoration:none;

            transition:0.3s;

            display:inline-flex;
            align-items:center;
            gap:10px;
        }

        .apply-btn:hover{

            transform:scale(1.03);
            color:#fff;
        }

        /*
        |--------------------------------------------------------------------------
        | FLOATING SUBSCRIBE
        |--------------------------------------------------------------------------
        */

        .floating-subscribe{

            position:fixed;
            bottom:25px;
            right:25px;

            width:70px;
            height:70px;

            border-radius:50%;

            border:none;

            background:linear-gradient(
                135deg,
                #2563eb,
                #7c3aed
            );

            color:#fff;

            font-size:28px;

            z-index:9999;

            box-shadow:0 15px 35px rgba(37,99,235,0.35);

            animation:pulse 2s infinite;
        }

        @keyframes pulse{

            0%{
                transform:scale(1);
            }

            50%{
                transform:scale(1.08);
            }

            100%{
                transform:scale(1);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .modal-dialog-slide{

            transform:translateX(100%);
            transition:all 0.5s ease;
        }

        .modal.show .modal-dialog-slide{

            transform:translateX(0);
        }

        .subscribe-modal{

            border:none;
            border-radius:25px;
            overflow:hidden;
        }

        .modal-header-custom{

            background:linear-gradient(
                135deg,
                #2563eb,
                #7c3aed
            );

            color:#fff;
            padding:25px;
        }

        .modal-body{

            padding:30px;
        }

        .form-control,
        .form-select{

            min-height:55px;
            border-radius:14px;
        }

        .category-grid{

            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:12px;
        }

        .category-item{

            background:#f8fafc;
            padding:14px;
            border-radius:14px;
            border:1px solid #e2e8f0;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .floating-alert{

            position:fixed;
            top:25px;
            right:25px;

            min-width:350px;

            background:#fff;

            border-radius:18px;

            overflow:hidden;

            z-index:99999;

            box-shadow:0 18px 40px rgba(0,0,0,0.15);

            animation:slideIn 0.5s ease;
        }

        @keyframes slideIn{

            from{
                opacity:0;
                transform:translateX(100%);
            }

            to{
                opacity:1;
                transform:translateX(0);
            }
        }

        .alert-header{

            padding:18px 20px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .alert-progress{

            height:5px;
            background:#22c55e;
            width:100%;
            animation:shrink 5s linear forwards;
        }

        @keyframes shrink{

            from{
                width:100%;
            }

            to{
                width:0%;
            }
        }

        footer{

            background:#0f172a;
            color:#94a3b8;

            text-align:center;

            padding:35px 20px;
        }

        footer a{

            color:#60a5fa;
            text-decoration:none;
        }

        @media(max-width:768px){

            .hero h1{

                font-size:38px;
            }

            .category-grid{

                grid-template-columns:1fr;
            }

            .floating-alert{

                width:90%;
                right:5%;
            }
        }

        /*
            |--------------------------------------------------------------------------
            | PAGINATION
            |--------------------------------------------------------------------------
            */

            .pagination .page-link{

                border:none;

                margin:0 6px;

                border-radius:14px;

                color:#0f172a;

                font-weight:700;

                min-width:50px;

                height:50px;

                display:flex;

                align-items:center;

                justify-content:center;

                box-shadow:0 5px 15px rgba(0,0,0,0.05);

                transition:0.3s;
            }

            .pagination .page-link:hover{

                background:#2563eb;

                color:#fff;

                transform:translateY(-3px);
            }

            .pagination .active .page-link{

                background:linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

                color:#fff;

                box-shadow:0 12px 25px rgba(37,99,235,0.25);
            }

    </style>

</head>

<body>

<?php if($success): ?>

    <div class="floating-alert" id="successAlert">

        <div class="alert-header">

            <div>

                <strong class="text-success">

                    <i class="bi bi-check-circle-fill me-2"></i>

                    Success
                </strong>

                <div class="mt-1">

                    <?= htmlspecialchars($success) ?>

                </div>

            </div>

            <button
                class="btn-close"
                onclick="closeAlert()"
            ></button>

        </div>

        <div class="alert-progress"></div>

    </div>

<?php endif; ?>

<?php if($error): ?>

    <div class="floating-alert" id="successAlert">

        <div class="alert-header">

            <div>

                <strong class="text-danger">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    Error
                </strong>

                <div class="mt-1">

                    <?= htmlspecialchars($error) ?>

                </div>

            </div>

            <button
                class="btn-close"
                onclick="closeAlert()"
            ></button>

        </div>

        <div class="alert-progress"
             style="background:#ef4444;"></div>

    </div>

<?php endif; ?>

<!-- HERO -->

<section class="hero">

    <h1>

        Uganda Job Finder

    </h1>

    <p>

        Discover fresh daily opportunities across Uganda.
        Browse jobs from Government, NGOs, IT, Healthcare,
        Engineering, Finance and many more sectors.

    </p>

</section>

<!-- MARQUEE -->

<div class="marquee-wrapper">

    <div class="marquee">

        🔥 Subscribe for daily advertised jobs •
        Latest Uganda jobs updated every day •
        Government • NGO • IT • Healthcare • Engineering • Finance •
        Subscribe now and never miss opportunities 🔥

    </div>

</div>

<!-- FILTERS -->

<section class="filters-section">

    <div class="container">

        <div class="filter-box">

            <div class="row g-3">

                <!-- CATEGORY -->

                <div class="col-lg-4">

                    <button
                        class="filter-btn"
                        onclick="togglePanel('categoryPanel')"
                    >

                        <i class="bi bi-tags-fill me-2"></i>

                        Filter By Category

                    </button>

                    <div
                        class="dropdown-panel"
                        id="categoryPanel"
                    >

                        <select
                            class="form-select"
                            id="categoryFilter"
                            onchange="filterJobs()"
                        >

                            <option value="">
                                All Categories
                            </option>

                            <?php foreach($categories as $category): ?>

                                <option
                                    value="<?= strtolower($category['name']) ?>"
                                >

                                    <?= htmlspecialchars($category['name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

                <!-- LOCATION -->

                <div class="col-lg-4">

                    <button
                        class="filter-btn"
                        onclick="togglePanel('locationPanel')"
                    >

                        <i class="bi bi-geo-alt-fill me-2"></i>

                        Filter By Location

                    </button>

                    <div
                        class="dropdown-panel"
                        id="locationPanel"
                    >

                        <input
                            type="text"
                            class="form-control"
                            id="locationFilter"
                            placeholder="Enter location..."
                            onkeyup="filterJobs()"
                        >

                    </div>

                </div>

                <!-- SEARCH -->

                <div class="col-lg-4">

                    <button
                        class="filter-btn"
                        onclick="togglePanel('searchPanel')"
                    >

                        <i class="bi bi-search me-2"></i>

                        Search Jobs

                    </button>

                    <div
                        class="dropdown-panel"
                        id="searchPanel"
                    >

                        <input
                            type="text"
                            class="form-control"
                            id="searchInput"
                            placeholder="Search title/company..."
                            onkeyup="filterJobs()"
                        >

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- JOBS -->

<section class="jobs-section">

    <div class="container">

        <div class="row">

            <?php if(count($jobs) > 0): ?>

                <?php $number = 1; ?>

                <?php foreach($jobs as $job): ?>

                    <div
                        class="col-lg-6 mb-4 job-item"
                        data-category="<?= strtolower($job['category_name']) ?>"
                        data-location="<?= strtolower($job['location']) ?>"
                        data-title="<?= strtolower($job['title']) ?>"
                        data-company="<?= strtolower($job['company_name']) ?>"
                    >

                        <div class="job-card h-100">

                            <div class="job-number">

                                #<?= $number++ ?>

                            </div>

                            <div class="job-title">

                                <?= htmlspecialchars($job['title']) ?>

                            </div>

                            <div class="job-company">

                                

                                <?php
                                    $companyName = trim($job['company_name'] ?? '');

                                    if (
                                        !empty($companyName)
                                        &&
                                        strtolower($companyName) !== 'unknown company'
                                    ):
                                    ?>

                                        <div class="d-flex align-items-center gap-2">

                                            <i class="bi bi-building"></i>

                                            <span>
                                                <?= htmlspecialchars($companyName) ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>

                            </div>

                            <div class="job-meta">

                                <div class="job-badge">

                                    <i class="bi bi-tags-fill me-1"></i>

                                    <?= htmlspecialchars($job['category_name'] ?? 'General') ?>

                                </div>

                                <div class="job-badge">

                                    <i class="bi bi-geo-alt-fill me-1"></i>

                                    <?= htmlspecialchars($job['location'] ?? 'Uganda') ?>

                                </div>

                                <div class="job-badge">

                                    <i class="bi bi-briefcase-fill me-1"></i>

                                    <?= htmlspecialchars($job['job_type']) ?>

                                </div>

                            </div>

                            <div class="job-description">

                                <?= nl2br(
                                    htmlspecialchars(
                                        substr(
                                            strip_tags($job['description'] ?? ''),
                                            0,
                                            220
                                        )
                                    )
                                ) ?>...

                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                                <small class="text-muted">

                                    <i class="bi bi-clock me-1"></i>

                                    <?= date(
                                        'd M Y',
                                        strtotime($job['created_at'])
                                    ) ?>

                                </small>

                                <a
                                    href="<?= htmlspecialchars($job['apply_url']) ?>"
                                    target="_blank"
                                    class="apply-btn"
                                >

                                    Visit Source

                                    <i class="bi bi-box-arrow-up-right"></i>

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12">

                    <div class="alert alert-warning shadow-sm border-0 rounded-4 p-5 text-center">

                        <h3>

                            No jobs available yet

                        </h3>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>
    <!-- PAGINATION -->

    <?php if($totalPages > 1): ?>

        <div class="container mb-5">

            <div class="d-flex justify-content-center">

                <nav>

                    <ul class="pagination pagination-lg">

                        <!-- PREVIOUS -->

                        <?php if($page > 1): ?>

                            <li class="page-item">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page - 1 ?>"
                                >

                                    <i class="bi bi-chevron-left"></i>

                                </a>

                            </li>

                        <?php endif; ?>

                        <!-- PAGE NUMBERS -->

                        <?php for($i = 1; $i <= $totalPages; $i++): ?>

                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?page=<?= $i ?>"
                                >

                                    <?= $i ?>

                                </a>

                            </li>

                        <?php endfor; ?>

                        <!-- NEXT -->

                        <?php if($page < $totalPages): ?>

                            <li class="page-item">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page + 1 ?>"
                                >

                                    <i class="bi bi-chevron-right"></i>

                                </a>

                            </li>

                        <?php endif; ?>

                    </ul>

                </nav>

            </div>

        </div>

    <?php endif; ?>
</section>

<!-- FLOATING BUTTON -->

<button
    class="floating-subscribe"
    data-bs-toggle="modal"
    data-bs-target="#subscribeModal"
>

    <i class="bi bi-bell-fill"></i>

</button>

<!-- MODAL -->

<div class="modal fade"
     id="subscribeModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-slide modal-lg modal-dialog-end">

        <div class="modal-content subscribe-modal">

            <div class="modal-header-custom">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h3 class="mb-1">

                            Subscribe To Daily Jobs

                        </h3>

                        <small>

                            Get latest Uganda jobs instantly

                        </small>

                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

            </div>

            <form method="POST">

                <input
                    type="hidden"
                    name="subscribe_now"
                    value="1"
                >

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-4">

                            <label class="form-label fw-bold">

                                Full Name

                            </label>

                            <input
                                type="text"
                                name="full_name"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="col-md-6 mb-4">

                            <label class="form-label fw-bold">

                                Email Address

                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="col-md-6 mb-4">

                            <label class="form-label fw-bold">

                                Phone

                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="col-md-6 mb-4">

                            <label class="form-label fw-bold">

                                Country

                            </label>

                            <select
                                name="country"
                                class="form-select"
                            >

                                <option value="Uganda">

                                    Uganda

                                </option>

                                <option value="Kenya">

                                    Kenya

                                </option>

                                <option value="Tanzania">

                                    Tanzania

                                </option>

                                <option value="Rwanda">

                                    Rwanda

                                </option>

                            </select>

                        </div>

                        <div class="col-md-12 mb-4">

                            <label class="form-label fw-bold">

                                Email Frequency

                            </label>

                            <select
                                name="frequency"
                                class="form-select"
                            >

                                <option value="Daily">

                                    Daily

                                </option>

                                <option value="Instant">

                                    Instant

                                </option>

                                <option value="Weekly">

                                    Weekly

                                </option>

                            </select>

                        </div>

                        <div class="col-md-12">

                            <label class="form-label fw-bold">

                                Select Categories

                            </label>

                            <div class="category-grid">

                                <?php foreach($categories as $category): ?>

                                    <label class="category-item">

                                        <input
                                            type="checkbox"
                                            name="categories[]"
                                            value="<?= $category['id'] ?>"
                                            class="form-check-input me-2"
                                        >

                                        <?= htmlspecialchars($category['name']) ?>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                            <small class="text-muted d-block mt-3">

                                If you don't select categories,
                                all categories will automatically
                                be subscribed.

                            </small>

                        </div>

                        <div class="col-12 mt-5">

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                style="
                                    height:60px;
                                    border-radius:18px;
                                    font-weight:700;
                                    font-size:18px;
                                "
                            >

                                <i class="bi bi-bell-fill me-2"></i>

                                Subscribe Now

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/bars/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | TOGGLE FILTER PANELS
    |--------------------------------------------------------------------------
    */

    function togglePanel(panelId){

        let panel = document.getElementById(panelId);

        panel.style.display =
            panel.style.display === 'block'
            ? 'none'
            : 'block';
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER JOBS
    |--------------------------------------------------------------------------
    */

    function filterJobs(){

        let category =
            document.getElementById('categoryFilter').value.toLowerCase();

        let location =
            document.getElementById('locationFilter').value.toLowerCase();

        let search =
            document.getElementById('searchInput').value.toLowerCase();

        let jobs =
            document.querySelectorAll('.job-item');

        jobs.forEach(job => {

            let jobCategory =
                job.dataset.category;

            let jobLocation =
                job.dataset.location;

            let jobTitle =
                job.dataset.title;

            let jobCompany =
                job.dataset.company;

            let categoryMatch =
                category === ''
                ||
                jobCategory.includes(category);

            let locationMatch =
                jobLocation.includes(location);

            let searchMatch =
                jobTitle.includes(search)
                ||
                jobCompany.includes(search);

            if (
                categoryMatch
                &&
                locationMatch
                &&
                searchMatch
            ) {

                job.style.display = 'block';

            } else {

                job.style.display = 'none';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO CLOSE ALERT
    |--------------------------------------------------------------------------
    */

    function closeAlert(){

        let alertBox =
            document.getElementById('successAlert');

        if(alertBox){

            alertBox.remove();
        }
    }

    setTimeout(() => {

        closeAlert();

    }, 5000);

</script>

</body>
</html>