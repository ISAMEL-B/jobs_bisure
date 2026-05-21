<?php

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

$message = '';

/*
|--------------------------------------------------------------------------
| CREATE CATEGORY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create_category'])
) {

    $name = trim($_POST['name'] ?? '');

    $slug = trim($_POST['slug'] ?? '');

    $keywords = trim($_POST['keywords'] ?? '');

    $icon = trim($_POST['icon'] ?? '');

    if (empty($name) || empty($slug)) {

        $message = "
            <div class='alert alert-danger shadow-sm border-0'>
                Category name and slug are required.
            </div>
        ";

    } else {

        $check = $db->prepare("
            SELECT id
            FROM job_categories
            WHERE slug = ?
            LIMIT 1
        ");

        $check->execute([$slug]);

        if ($check->fetch()) {

            $message = "
                <div class='alert alert-warning shadow-sm border-0'>
                    Category slug already exists.
                </div>
            ";

        } else {

            $stmt = $db->prepare("
                INSERT INTO job_categories (

                    name,
                    slug,
                    keywords,
                    icon

                )

                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([

                $name,
                $slug,
                $keywords,
                $icon

            ]);

            header("Location: manage_categories.php?created=1");

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE CATEGORY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_category'])
) {

    $id = (int) $_POST['id'];

    $name = trim($_POST['name'] ?? '');

    $slug = trim($_POST['slug'] ?? '');

    $keywords = trim($_POST['keywords'] ?? '');

    $icon = trim($_POST['icon'] ?? '');

    $stmt = $db->prepare("
        UPDATE job_categories
        SET

            name = ?,
            slug = ?,
            keywords = ?,
            icon = ?

        WHERE id = ?
    ");

    $stmt->execute([

        $name,
        $slug,
        $keywords,
        $icon,
        $id

    ]);

    header("Location: manage_categories.php?updated=1");

    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE CATEGORY
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $stmt = $db->prepare("
        DELETE FROM job_categories
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header("Location: manage_categories.php?deleted=1");

    exit;
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

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
| WHERE
|--------------------------------------------------------------------------
*/

$whereSql = '';

$params = [];

if (!empty($search)) {

    $whereSql = "
        WHERE

            name LIKE ?
            OR slug LIKE ?
            OR keywords LIKE ?
    ";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

/*
|--------------------------------------------------------------------------
| TOTAL CATEGORIES
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) total
    FROM job_categories
    $whereSql
";

$countStmt = $db->prepare($countSql);

$countStmt->execute($params);

$totalCategories = $countStmt->fetch()['total'];

$totalPages = ceil($totalCategories / $perPage);

if ($totalPages < 1) {

    $totalPages = 1;
}

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        job_categories.*,

        (
            SELECT COUNT(*)
            FROM jobs
            WHERE jobs.category_id = job_categories.id
        ) AS total_jobs,

        (
            SELECT COUNT(*)
            FROM subscriber_categories
            WHERE subscriber_categories.category_id = job_categories.id
        ) AS total_subscribers

    FROM job_categories

    $whereSql

    ORDER BY id DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);

$stmt->execute($params);

$categories = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| EDIT CATEGORY
|--------------------------------------------------------------------------
*/

$editCategory = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $db->prepare("
        SELECT *
        FROM job_categories
        WHERE id = ?
    ");

    $stmt->execute([$editId]);

    $editCategory = $stmt->fetch();
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Categories</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

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
            padding:25px;
            border-radius:20px;
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
            font-size:22px;
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

        .card-box{
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

        .form-control,
        .form-select{
            min-height:50px;
            border-radius:12px;
        }

        .btn{
            border-radius:12px;
            padding:10px 18px;
        }

        .category-icon{
            width:45px;
            height:45px;
            border-radius:12px;
            background:#e0e7ff;
            color:#1d4ed8;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
        }

        .pagination .page-link{
            border:none;
            margin:0 4px;
            border-radius:10px;
            color:#111827;
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

    <!-- HEADER -->

    <div class="top-header d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h2 class="mb-1">
                Manage Categories
            </h2>

            <small class="text-muted">
                Create, edit and organize job categories
            </small>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="../index.php"
               class="btn btn-dark">

                <i class="bi bi-speedometer2 me-2"></i>

                Dashboard

            </a>

        </div>

    </div>

    <!-- ALERTS -->

    <?php if(isset($_GET['created'])): ?>

        <div class="alert alert-success shadow-sm border-0">

            Category created successfully.

        </div>

    <?php endif; ?>

    <?php if(isset($_GET['updated'])): ?>

        <div class="alert alert-primary shadow-sm border-0">

            Category updated successfully.

        </div>

    <?php endif; ?>

    <?php if(isset($_GET['deleted'])): ?>

        <div class="alert alert-danger shadow-sm border-0">

            Category deleted successfully.

        </div>

    <?php endif; ?>

    <?= $message ?>

    <!-- STATS -->

    <div class="row mb-4">

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Categories
                        </h6>

                        <h2>
                            <?= number_format($totalCategories) ?>
                        </h2>

                    </div>

                    <div class="icon-box blue">

                        <i class="bi bi-tags"></i>

                    </div>

                </div>

            </div>

        </div>

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

                    <div class="icon-box green">

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
                            Subscribers
                        </h6>

                        <h2>
                            <?= number_format($totalSubscribers) ?>
                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-people"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- FORM -->

    <div class="card card-box shadow-sm mb-4">

        <div class="card-header bg-white py-3">

            <strong>

                <?= $editCategory ? 'Edit Category' : 'Create New Category' ?>

            </strong>

        </div>

        <div class="card-body p-4">

            <form method="POST">

                <?php if($editCategory): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $editCategory['id'] ?>"
                    >

                <?php endif; ?>

                <div class="row">

                    <!-- NAME -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Category Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>"
                        >

                    </div>

                    <!-- SLUG -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Slug
                        </label>

                        <input
                            type="text"
                            name="slug"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>"
                        >

                    </div>

                    <!-- ICON -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Bootstrap Icon
                        </label>

                        <input
                            type="text"
                            name="icon"
                            class="form-control"
                            placeholder="Example: bi-laptop"
                            value="<?= htmlspecialchars($editCategory['icon'] ?? '') ?>"
                        >

                    </div>

                    <!-- KEYWORDS -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Keywords
                        </label>

                        <input
                            type="text"
                            name="keywords"
                            class="form-control"
                            placeholder="developer, software, network"
                            value="<?= htmlspecialchars($editCategory['keywords'] ?? '') ?>"
                        >

                    </div>

                </div>

                <div class="d-flex gap-2">

                    <?php if($editCategory): ?>

                        <button
                            type="submit"
                            name="update_category"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-save me-2"></i>

                            Update Category

                        </button>

                        <a href="manage_categories.php"
                           class="btn btn-secondary">

                            Cancel

                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="create_category"
                            class="btn btn-success"
                        >

                            <i class="bi bi-plus-circle me-2"></i>

                            Add Category

                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>

    </div>

    <!-- SEARCH -->

    <div class="card card-box shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-10 mb-3">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search categories..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>

                    <div class="col-md-2 mb-3">

                        <button
                            class="btn btn-dark w-100"
                        >

                            <i class="bi bi-search me-2"></i>

                            Search

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- TABLE -->

    <div class="card card-box shadow-sm">

        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

            <strong>
                Categories List
            </strong>

            <span class="badge bg-dark">

                <?= number_format($totalCategories) ?> Categories

            </span>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Category</th>

                            <th>Slug</th>

                            <th>Jobs</th>

                            <th>Subscribers</th>

                            <th>Created</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if(count($categories) > 0): ?>

                        <?php foreach($categories as $category): ?>

                            <tr>

                                <td>
                                    #<?= $category['id'] ?>
                                </td>

                                <td>

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="category-icon">

                                            <i class="bi <?= htmlspecialchars($category['icon'] ?: 'bi-tag') ?>"></i>

                                        </div>

                                        <div>

                                            <div class="fw-semibold">

                                                <?= htmlspecialchars($category['name']) ?>

                                            </div>

                                            <small class="text-muted">

                                                <?= htmlspecialchars($category['keywords']) ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>

                                <td>

                                    <?= htmlspecialchars($category['slug']) ?>

                                </td>

                                <td>

                                    <span class="badge bg-primary">

                                        <?= number_format($category['total_jobs']) ?>

                                    </span>

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        <?= number_format($category['total_subscribers']) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($category['created_at'])
                                    ) ?>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <!-- EDIT -->

                                        <a
                                            href="?edit=<?= $category['id'] ?>"
                                            class="btn btn-sm btn-primary"
                                        >

                                            <i class="bi bi-pencil-square"></i>

                                        </a>

                                        <!-- DELETE -->

                                        <a
                                            href="?delete=<?= $category['id'] ?>"
                                            onclick="return confirm('Delete this category?')"
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

                            <td colspan="7" class="text-center py-5">

                                <div class="text-muted">

                                    <i class="bi bi-database-x fs-1"></i>

                                    <p class="mt-3 mb-0">

                                        No categories found

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

                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">

                    <div class="text-muted">

                        Page <?= $page ?> of <?= $totalPages ?>

                    </div>

                    <nav>

                        <ul class="pagination mb-0">

                            <?php for($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    >

                                        <?= $i ?>

                                    </a>

                                </li>

                            <?php endfor; ?>

                        </ul>

                    </nav>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>