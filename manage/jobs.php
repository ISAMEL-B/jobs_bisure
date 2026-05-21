<?php

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| DELETE SINGLE JOB
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $stmt = $db->prepare("
        DELETE FROM jobs
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header("Location: jobs.php?deleted=1");

    exit;
}

/*
|--------------------------------------------------------------------------
| BULK ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_POST['job_ids']) &&
        !empty($_POST['job_ids']) &&
        isset($_POST['bulk_action']) &&
        !empty($_POST['bulk_action'])
    ) {

        $jobIds = $_POST['job_ids'];

        $action = $_POST['bulk_action'];

        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));

        /*
        |--------------------------------------------------------------------------
        | MARK AS FEATURED
        |--------------------------------------------------------------------------
        */

        if ($action === 'feature') {

            $stmt = $db->prepare("
                UPDATE jobs
                SET is_featured = 1
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        /*
        |--------------------------------------------------------------------------
        | REMOVE FEATURED
        |--------------------------------------------------------------------------
        */

        if ($action === 'unfeature') {

            $stmt = $db->prepare("
                UPDATE jobs
                SET is_featured = 0
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        /*
        |--------------------------------------------------------------------------
        | MARK ACTIVE
        |--------------------------------------------------------------------------
        */

        if ($action === 'activate') {

            $stmt = $db->prepare("
                UPDATE jobs
                SET is_active = 1
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        /*
        |--------------------------------------------------------------------------
        | MARK INACTIVE
        |--------------------------------------------------------------------------
        */

        if ($action === 'deactivate') {

            $stmt = $db->prepare("
                UPDATE jobs
                SET is_active = 0
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        /*
        |--------------------------------------------------------------------------
        | MARK AS EMAILED
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_emailed') {

            $stmt = $db->prepare("
                UPDATE jobs
                SET views = 1
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        /*
        |--------------------------------------------------------------------------
        | REMOVE EMAILED MARK
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_not_emailed') {

            $stmt = $db->prepare("
                UPDATE jobs
                SET views = 0
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE JOBS
        |--------------------------------------------------------------------------
        */

        if ($action === 'delete') {

            $stmt = $db->prepare("
                DELETE FROM jobs
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($jobIds);
        }

        header("Location: jobs.php?success=1");

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH & FILTERS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$category = trim($_GET['category'] ?? '');

$status = trim($_GET['status'] ?? '');

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {

    $page = 1;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| WHERE CONDITIONS
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if (!empty($search)) {

    $where[] = "
        (
            jobs.title LIKE ?
            OR jobs.company_name LIKE ?
            OR jobs.location LIKE ?
            OR job_categories.name LIKE ?
        )
    ";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

/*
|--------------------------------------------------------------------------
| CATEGORY
|--------------------------------------------------------------------------
*/

if (!empty($category)) {

    $where[] = "jobs.category_id = ?";

    $params[] = $category;
}

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    if ($status === 'active') {

        $where[] = "jobs.is_active = 1";
    }

    if ($status === 'inactive') {

        $where[] = "jobs.is_active = 0";
    }

    if ($status === 'featured') {

        $where[] = "jobs.is_featured = 1";
    }

    if ($status === 'emailed') {

        $where[] = "jobs.views > 0";
    }

    if ($status === 'remote') {

        $where[] = "jobs.job_type = 'Remote'";
    }
}

/*
|--------------------------------------------------------------------------
| FINAL WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| TOTAL JOBS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) total

    FROM jobs

    LEFT JOIN job_categories
    ON jobs.category_id = job_categories.id

    $whereSql
";

$countStmt = $db->prepare($countSql);

$countStmt->execute($params);

$totalJobs = $countStmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

$totalPages = ceil($totalJobs / $perPage);

if ($totalPages < 1) {

    $totalPages = 1;
}

if ($page > $totalPages) {

    $page = $totalPages;

    $offset = ($page - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| FETCH JOBS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        jobs.*,
        job_categories.name AS category_name,
        job_sources.name AS source_name

    FROM jobs

    LEFT JOIN job_categories
    ON jobs.category_id = job_categories.id

    LEFT JOIN job_sources
    ON jobs.source_id = job_sources.id

    $whereSql

    ORDER BY jobs.id DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);

$stmt->execute($params);

$jobs = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalCategories = $db->query("
    SELECT COUNT(*) total
    FROM job_categories
")->fetch()['total'];

$totalFeaturedJobs = $db->query("
    SELECT COUNT(*) total
    FROM jobs
    WHERE is_featured = 1
")->fetch()['total'];

$totalActiveJobs = $db->query("
    SELECT COUNT(*) total
    FROM jobs
    WHERE is_active = 1
")->fetch()['total'];

$totalRemoteJobs = $db->query("
    SELECT COUNT(*) total
    FROM jobs
    WHERE job_type = 'Remote'
")->fetch()['total'];

$totalEmailedJobs = $db->query("
    SELECT COUNT(*) total
    FROM jobs
    WHERE views > 0
")->fetch()['total'];

$totalViews = $db->query("
    SELECT SUM(views) total
    FROM jobs
")->fetch()['total'] ?? 0;

$totalClicks = $db->query("
    SELECT SUM(clicks) total
    FROM jobs
")->fetch()['total'] ?? 0;

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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Jobs</title>

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
            transform:translateY(-5px);
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

        .purple{
            background:#7c3aed;
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
            background:#111827;
            color:#fff;
        }

        .table td{
            vertical-align:middle;
        }

        .search-box{
            min-height:50px;
            border-radius:12px;
        }

        .badge-status{
            padding:7px 12px;
            border-radius:10px;
            font-size:12px;
        }

        .active-job{
            background:#dcfce7;
            color:#166534;
        }

        .inactive-job{
            background:#fee2e2;
            color:#991b1b;
        }

        .featured-badge{
            background:#fef3c7;
            color:#92400e;
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

        @media(max-width:992px){

            .main-wrapper{
                margin-left:80px;
            }
        }

    </style>

</head>

<body>

<?php include '../sidebar/sidebar.php'; ?>

<div class="main-wrapper">

    <div class="top-header d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="mb-1">
                Manage Jobs
            </h2>

            <small class="text-muted">
                Manage all scraped jobs, email status, categories and visibility
            </small>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="../run_scraper.php"
               class="btn btn-success">

                <i class="bi bi-cloud-download me-2"></i>

                Run Scraper

            </a>

            <a href="../cron.php"
               class="btn btn-dark">

                <i class="bi bi-arrow-repeat me-2"></i>

                Run Full Cron

            </a>

        </div>

    </div>

    <?php if(isset($_GET['deleted'])): ?>

        <div class="alert alert-danger border-0 shadow-sm">

            Job removed successfully.

        </div>

    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>

        <div class="alert alert-success border-0 shadow-sm">

            Selected jobs updated successfully.

        </div>

    <?php endif; ?>

    <div class="row mb-4">

        <div class="col-lg-3 col-md-6 mb-3">

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

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Active Jobs
                        </h6>

                        <h2>
                            <?= number_format($totalActiveJobs) ?>
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
                            Featured Jobs
                        </h6>

                        <h2>
                            <?= number_format($totalFeaturedJobs) ?>
                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-star"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Emailed Jobs
                        </h6>

                        <h2>
                            <?= number_format($totalEmailedJobs) ?>
                        </h2>

                    </div>

                    <div class="icon-box red">

                        <i class="bi bi-envelope-check"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="card table-card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <input
                            type="text"
                            name="search"
                            class="form-control search-box"
                            placeholder="Search jobs..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>

                    <div class="col-md-3 mb-3">

                        <select
                            name="category"
                            class="form-select search-box"
                        >

                            <option value="">
                                All Categories
                            </option>

                            <?php foreach($categories as $cat): ?>

                                <option
                                    value="<?= $cat['id'] ?>"
                                    <?= $category == $cat['id'] ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($cat['name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3 mb-3">

                        <select
                            name="status"
                            class="form-select search-box"
                        >

                            <option value="">
                                All Status
                            </option>

                            <option value="active"
                                <?= $status === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>

                            <option value="inactive"
                                <?= $status === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>

                            <option value="featured"
                                <?= $status === 'featured' ? 'selected' : '' ?>>
                                Featured
                            </option>

                            <option value="emailed"
                                <?= $status === 'emailed' ? 'selected' : '' ?>>
                                Emailed
                            </option>

                            <option value="remote"
                                <?= $status === 'remote' ? 'selected' : '' ?>>
                                Remote
                            </option>

                        </select>

                    </div>

                    <div class="col-md-2 mb-3">

                        <button
                            class="btn btn-primary w-100 search-box"
                        >

                            <i class="bi bi-search me-2"></i>

                            Search Jobs

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="card table-card shadow-sm">

        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">

            <h5 class="mb-0">
                All Scraped Jobs
            </h5>

            <span class="badge bg-dark">

                <?= number_format($totalJobs) ?> Jobs

            </span>

        </div>

        <div class="card-body">

            <div class="alert alert-light border mb-3">

                <strong>Tip:</strong>

                Use bulk operations to quickly mark jobs as featured,
                emailed, active or inactive.

            </div>

            <form method="POST">

                <div class="d-flex gap-2 mb-3 flex-wrap">

                    <select
                        name="bulk_action"
                        class="form-select"
                        style="max-width:280px;"
                    >

                        <option value="">
                            Select Bulk Operation
                        </option>

                        <option value="feature">
                            Mark as Featured
                        </option>

                        <option value="unfeature">
                            Remove Featured Mark
                        </option>

                        <option value="activate">
                            Mark as Active
                        </option>

                        <option value="deactivate">
                            Mark as Inactive
                        </option>

                        <option value="mark_emailed">
                            Mark as Emailed
                        </option>

                        <option value="mark_not_emailed">
                            Remove Emailed Mark
                        </option>

                        <option value="delete">
                            Delete Selected Jobs
                        </option>

                    </select>

                    <button
                        type="submit"
                        class="btn btn-dark"
                    >

                        Run Operation

                    </button>

                </div>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>

                            <tr>

                                <th>

                                    <input
                                        type="checkbox"
                                        id="checkAll"
                                    >

                                </th>

                                <th>Job</th>

                                <th>Company</th>

                                <th>Category</th>

                                <th>Location</th>

                                <th>Status</th>

                                <th>Date</th>

                                <th>Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if(count($jobs) > 0): ?>

                            <?php foreach($jobs as $job): ?>

                                <tr>

                                    <td>

                                        <input
                                            type="checkbox"
                                            name="job_ids[]"
                                            value="<?= $job['id'] ?>"
                                        >

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

                                        <?= htmlspecialchars($job['category_name'] ?? 'Other') ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($job['location']) ?>

                                    </td>

                                    <td>

                                        <?php if(!$job['is_active']): ?>

                                            <span class="badge-status inactive-job">

                                                Inactive

                                            </span>

                                        <?php else: ?>

                                            <span class="badge-status active-job">

                                                Active

                                            </span>

                                        <?php endif; ?>

                                        <?php if($job['is_featured']): ?>

                                            <span class="badge featured-badge mt-1">

                                                Featured

                                            </span>

                                        <?php endif; ?>

                                        <?php if($job['views'] > 0): ?>

                                            <span class="badge bg-primary mt-1">

                                                Emailed

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if(!empty($job['posted_date'])): ?>

                                            <?= date(
                                                'd M Y',
                                                strtotime($job['posted_date'])
                                            ) ?>

                                        <?php else: ?>

                                            N/A

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="d-flex gap-2">

                                            <a
                                                href="<?= htmlspecialchars($job['apply_url']) ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-primary"
                                            >

                                                <i class="bi bi-box-arrow-up-right"></i>

                                            </a>

                                            <a
                                                href="?delete=<?= $job['id'] ?>"
                                                onclick="return confirm('Delete this job?')"
                                                class="btn btn-sm btn-danger"
                                            >

                                                <i class="bi bi-trash"></i>

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8" class="text-center py-5">

                                    <div class="text-muted">

                                        <i class="bi bi-database-x fs-1"></i>

                                        <p class="mt-3 mb-0">

                                            No jobs matched your search or filters

                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </form>

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
                                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>"
                                >

                                    Previous

                                </a>

                            </li>

                            <?php for($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>"
                                    >

                                        <?= $i ?>

                                    </a>

                                </li>

                            <?php endfor; ?>

                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>"
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

<script>

document
.getElementById('checkAll')
.addEventListener('change', function(){

    const checkboxes =
        document.querySelectorAll('input[name="job_ids[]"]');

    checkboxes.forEach(box => {

        box.checked = this.checked;

    });

});

</script>

</body>
</html>