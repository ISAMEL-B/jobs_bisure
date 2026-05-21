<?php

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| DELETE SINGLE SUBSCRIBER
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $subscriberId = (int) $_GET['delete'];

    $stmt = $db->prepare("
        DELETE FROM subscriber_categories
        WHERE subscriber_id = ?
    ");

    $stmt->execute([$subscriberId]);

    $stmt = $db->prepare("
        DELETE FROM subscribers
        WHERE id = ?
    ");

    $stmt->execute([$subscriberId]);

    header("Location: subscribe.php?deleted=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| TOGGLE STATUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['toggle'])) {

    $subscriberId = (int) $_GET['toggle'];

    $stmt = $db->prepare("
        UPDATE subscribers
        SET is_active =
            CASE
                WHEN is_active = 1 THEN 0
                ELSE 1
            END
        WHERE id = ?
    ");

    $stmt->execute([$subscriberId]);

    header("Location: subscribe.php?updated=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $db->query("
    SELECT *
    FROM job_categories
    ORDER BY name ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalSubscribers = $db->query("
    SELECT COUNT(*) total
    FROM subscribers
")->fetch()['total'];

$totalActiveSubscribers = $db->query("
    SELECT COUNT(*) total
    FROM subscribers
    WHERE is_active = 1
")->fetch()['total'];

$totalInactiveSubscribers = $db->query("
    SELECT COUNT(*) total
    FROM subscribers
    WHERE is_active = 0
")->fetch()['total'];

$totalCategories = $db->query("
    SELECT COUNT(*) total
    FROM job_categories
")->fetch()['total'];

$totalEmails = $db->query("
    SELECT COUNT(*) total
    FROM email_logs
")->fetch()['total'];

/*
|--------------------------------------------------------------------------
| ALERT MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';

/*
|--------------------------------------------------------------------------
| BULK ACTIONS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['bulk_action'])
) {

    $bulkAction = $_POST['bulk_action'];

    $subscriberIds = $_POST['subscriber_ids'] ?? [];

    if (!empty($subscriberIds)) {

        $subscriberIds =
            array_map('intval', $subscriberIds);

        $placeholders = implode(
            ',',
            array_fill(0, count($subscriberIds), '?')
        );

        /*
        |--------------------------------------------------------------------------
        | DELETE SELECTED
        |--------------------------------------------------------------------------
        */

        if ($bulkAction === 'delete_selected') {

            $stmt = $db->prepare("
                DELETE FROM subscriber_categories
                WHERE subscriber_id IN ($placeholders)
            ");

            $stmt->execute($subscriberIds);

            $stmt = $db->prepare("
                DELETE FROM subscribers
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($subscriberIds);

            $message = "
                <div class='alert alert-danger border-0 shadow-sm'>
                    Selected subscribers deleted successfully.
                </div>
            ";
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVATE SELECTED
        |--------------------------------------------------------------------------
        */

        if ($bulkAction === 'activate_selected') {

            $stmt = $db->prepare("
                UPDATE subscribers
                SET is_active = 1
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($subscriberIds);

            $message = "
                <div class='alert alert-success border-0 shadow-sm'>
                    Selected subscribers activated successfully.
                </div>
            ";
        }

        /*
        |--------------------------------------------------------------------------
        | DEACTIVATE SELECTED
        |--------------------------------------------------------------------------
        */

        if ($bulkAction === 'deactivate_selected') {

            $stmt = $db->prepare("
                UPDATE subscribers
                SET is_active = 0
                WHERE id IN ($placeholders)
            ");

            $stmt->execute($subscriberIds);

            $message = "
                <div class='alert alert-warning border-0 shadow-sm'>
                    Selected subscribers deactivated successfully.
                </div>
            ";
        }
    }
}

/*
|--------------------------------------------------------------------------
| ADD / UPDATE SUBSCRIBER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !isset($_POST['bulk_action'])
) {

    $fullName = trim($_POST['full_name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $country = trim($_POST['country'] ?? 'Uganda');

    $frequency = trim(
        $_POST['preferred_frequency'] ?? 'Daily'
    );

    $selectedCategories =
        $_POST['categories'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        empty($fullName) ||
        empty($email) ||
        empty($selectedCategories)
    ) {

        $message = "
            <div class='alert alert-danger border-0 shadow-sm'>
                Please fill all required fields.
            </div>
        ";

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

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW
        |--------------------------------------------------------------------------
        */

        if (!$existingSubscriber) {

            $stmt = $db->prepare("
                INSERT INTO subscribers (

                    email,
                    full_name,
                    phone,
                    country,
                    preferred_frequency,
                    is_active,
                    email_verified,
                    verification_token,
                    unsubscribe_token,
                    created_at

                )

                VALUES (

                    ?, ?, ?, ?, ?,
                    1,
                    1,
                    NULL,
                    MD5(RAND()),
                    NOW()

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

            $message = "
                <div class='alert alert-success border-0 shadow-sm'>
                    Subscriber added successfully.
                </div>
            ";

        } else {

            $subscriberId = $existingSubscriber['id'];

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                UPDATE subscribers
                SET
                    full_name = ?,
                    phone = ?,
                    country = ?,
                    preferred_frequency = ?
                WHERE id = ?
            ");

            $stmt->execute([

                $fullName,
                $phone,
                $country,
                $frequency,
                $subscriberId

            ]);

            /*
            |--------------------------------------------------------------------------
            | REMOVE OLD CATEGORIES
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                DELETE FROM subscriber_categories
                WHERE subscriber_id = ?
            ");

            $stmt->execute([$subscriberId]);

            $message = "
                <div class='alert alert-warning border-0 shadow-sm'>
                    Subscriber already existed.
                    Information updated successfully.
                </div>
            ";
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT CATEGORIES
        |--------------------------------------------------------------------------
        */

        foreach ($selectedCategories as $categoryId) {

            $stmt = $db->prepare("
                INSERT INTO subscriber_categories (

                    subscriber_id,
                    category_id

                )

                VALUES (?, ?)
            ");

            $stmt->execute([

                $subscriberId,
                $categoryId

            ]);
        }
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH + FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

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
            full_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR country LIKE ?
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
| STATUS
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    if ($status === 'active') {

        $where[] = "is_active = 1";
    }

    if ($status === 'inactive') {

        $where[] = "is_active = 0";
    }
}

/*
|--------------------------------------------------------------------------
| FINAL WHERE
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) total
    FROM subscribers
    $whereSql
";

$countStmt = $db->prepare($countSql);

$countStmt->execute($params);

$totalFilteredSubscribers =
    $countStmt->fetch()['total'];

$totalPages = ceil(
    $totalFilteredSubscribers / $perPage
);

if ($totalPages < 1) {

    $totalPages = 1;
}

/*
|--------------------------------------------------------------------------
| FETCH SUBSCRIBERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM subscribers

    $whereSql

    ORDER BY id DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);

$stmt->execute($params);

$subscribers = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| NUMBERING
|--------------------------------------------------------------------------
*/

$number = ($page - 1) * $perPage + 1;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Subscriber Management
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

        .top-header,
        .card{
            border:none;
            border-radius:20px;
            box-shadow:0 4px 20px rgba(0,0,0,0.05);
        }

        .top-header{
            background:#fff;
            padding:25px;
            margin-bottom:25px;
        }

        .stats-card{
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

        .blue{background:#2563eb;}
        .green{background:#059669;}
        .orange{background:#d97706;}
        .purple{background:#7c3aed;}
        .red{background:#dc2626;}

        .submit-btn{
            height:55px;
            border-radius:12px;
            font-weight:600;
        }

        .form-control,
        .form-select{
            min-height:50px;
            border-radius:12px;
        }

        .category-box{
            border:1px solid #e2e8f0;
            border-radius:12px;
            padding:12px;
            background:#fff;
            transition:0.3s;
        }

        .category-box:hover{
            background:#eff6ff;
            border-color:#2563eb;
        }

        .table thead{
            background:#111827;
            color:#fff;
        }

        .badge-active{
            background:#dcfce7;
            color:#166534;
            padding:8px 12px;
            border-radius:10px;
            font-size:12px;
            font-weight:600;
        }

        .badge-inactive{
            background:#fee2e2;
            color:#991b1b;
            padding:8px 12px;
            border-radius:10px;
            font-size:12px;
            font-weight:600;
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
                Subscriber Management
            </h2>

            <small class="text-muted">
                Advanced job alert management system
            </small>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="../send_emails.php"
               class="btn btn-dark">

                <i class="bi bi-envelope-fill me-2"></i>

                Send Emails

            </a>

            <a href="../index.php"
               class="btn btn-primary">

                <i class="bi bi-speedometer2 me-2"></i>

                Dashboard

            </a>

        </div>

    </div>

    <!-- ALERTS -->

    <?= $message ?>

    <!-- STATS -->

    <div class="row mb-4">

        <div class="col-lg-2 col-md-6 mb-3">

            <div class="card stats-card">

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

        <div class="col-lg-2 col-md-6 mb-3">

            <div class="card stats-card">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Active
                        </h6>

                        <h2>
                            <?= number_format($totalActiveSubscribers) ?>
                        </h2>

                    </div>

                    <div class="icon-box green">

                        <i class="bi bi-check-circle"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-2 col-md-6 mb-3">

            <div class="card stats-card">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Inactive
                        </h6>

                        <h2>
                            <?= number_format($totalInactiveSubscribers) ?>
                        </h2>

                    </div>

                    <div class="icon-box red">

                        <i class="bi bi-x-circle"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Categories
                        </h6>

                        <h2>
                            <?= number_format($totalCategories) ?>
                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-tags"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Emails Sent
                        </h6>

                        <h2>
                            <?= number_format($totalEmails) ?>
                        </h2>

                    </div>

                    <div class="icon-box purple">

                        <i class="bi bi-envelope-check"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ADD SUBSCRIBER -->

    <div class="card mb-4">

        <div class="card-header bg-white py-4">

            <h4 class="mb-1">
                Add Subscriber
            </h4>

        </div>

        <div class="card-body p-4">

            <form method="POST">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-semibold">
                            Country
                        </label>

                        <input
                            type="text"
                            name="country"
                            class="form-control"
                            value="Uganda"
                        >

                    </div>

                    <div class="col-md-12 mb-4">

                        <label class="form-label fw-semibold">
                            Email Frequency
                        </label>

                        <select
                            name="preferred_frequency"
                            class="form-select"
                        >

                            <option value="Instant">
                                Instant
                            </option>

                            <option value="Daily" selected>
                                Daily
                            </option>

                            <option value="Weekly">
                                Weekly
                            </option>

                        </select>

                    </div>

                </div>

                <!-- CATEGORIES -->

                <div class="mb-4">

                    <label class="form-label fw-semibold mb-3">
                        Select Categories
                    </label>

                    <div class="row">

                        <div class="col-12 mb-3">

                            <div class="category-box bg-light border-primary">

                                <div class="form-check">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="checkAllCategories"
                                    >

                                    <label
                                        class="form-check-label fw-bold text-primary"
                                        for="checkAllCategories"
                                    >

                                        Select All Categories

                                    </label>

                                </div>

                            </div>

                        </div>

                        <?php foreach ($categories as $category): ?>

                            <div class="col-lg-4 col-md-6 mb-3">

                                <div class="category-box">

                                    <div class="form-check">

                                        <input
                                            class="form-check-input category-checkbox"
                                            type="checkbox"
                                            name="categories[]"
                                            value="<?= $category['id'] ?>"
                                        >

                                        <label
                                            class="form-check-label"
                                        >

                                            <?= htmlspecialchars($category['name']) ?>

                                        </label>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary submit-btn w-100"
                >

                    <i class="bi bi-person-plus-fill me-2"></i>

                    Save Subscriber

                </button>

            </form>

        </div>

    </div>

    <!-- MANAGE SUBSCRIBERS -->

    <div class="card">

        <div class="card-header bg-white py-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h4 class="mb-1">
                        Manage Subscribers
                    </h4>

                    <small class="text-muted">
                        Search, filter, activate, deactivate and delete subscribers
                    </small>

                </div>

                <span class="badge bg-dark fs-6 px-3 py-2">

                    <?= number_format($totalFilteredSubscribers) ?>

                    Subscribers

                </span>

            </div>

        </div>

        <div class="card-body">

            <!-- SEARCH -->

            <form method="GET" class="mb-4">

                <div class="row">

                    <div class="col-lg-5 mb-3">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search subscriber..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>

                    <div class="col-lg-3 mb-3">

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">
                                All Status
                            </option>

                            <option
                                value="active"
                                <?= $status === 'active' ? 'selected' : '' ?>
                            >

                                Active

                            </option>

                            <option
                                value="inactive"
                                <?= $status === 'inactive' ? 'selected' : '' ?>
                            >

                                Inactive

                            </option>

                        </select>

                    </div>

                    <div class="col-lg-2 mb-3">

                        <button class="btn btn-primary w-100">

                            Filter

                        </button>

                    </div>

                    <div class="col-lg-2 mb-3">

                        <a
                            href="subscribe.php"
                            class="btn btn-dark w-100"
                        >

                            Reset

                        </a>

                    </div>

                </div>

            </form>

            <!-- BULK FORM -->

            <form method="POST" id="bulkForm">

                <!-- BULK ACTIONS -->

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <div class="d-flex gap-3 align-items-center">

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="checkAllSubscribers"
                            >

                            <label
                                class="form-check-label fw-semibold"
                            >

                                Select All

                            </label>

                        </div>

                        <span
                            class="badge bg-primary"
                            id="selectedCounter"
                        >

                            0 Selected

                        </span>

                    </div>

                    <div class="d-flex gap-2">

                        <select
                            name="bulk_action"
                            class="form-select"
                            style="min-width:220px;"
                        >

                            <option value="">
                                Bulk Actions
                            </option>

                            <option value="activate_selected">
                                Activate Selected
                            </option>

                            <option value="deactivate_selected">
                                Deactivate Selected
                            </option>

                            <option value="delete_selected">
                                Delete Selected
                            </option>

                        </select>

                        <button
                            type="submit"
                            class="btn btn-dark"
                            onclick="return confirmBulkAction()"
                        >

                            Apply

                        </button>

                    </div>

                </div>

                <!-- TABLE -->

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>

                            <tr>

                                <th>

                                    <input
                                        type="checkbox"
                                        id="tableCheckAll"
                                    >

                                </th>

                                <th>#</th>

                                <th>Name</th>

                                <th>Email</th>

                                <th>Phone</th>

                                <th>Country</th>

                                <th>Frequency</th>

                                <th>Status</th>

                                <th>Date</th>

                                <th>Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if(count($subscribers) > 0): ?>

                                <?php foreach($subscribers as $subscriber): ?>

                                    <tr>

                                        <td>

                                            <input
                                                type="checkbox"
                                                class="subscriber-checkbox"
                                                name="subscriber_ids[]"
                                                value="<?= $subscriber['id'] ?>"
                                            >

                                        </td>

                                        <td>

                                            <strong>

                                                <?= $number++ ?>

                                            </strong>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars($subscriber['full_name']) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars($subscriber['email']) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars($subscriber['phone']) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars($subscriber['country']) ?>

                                        </td>

                                        <td>

                                            <span class="badge bg-primary">

                                                <?= htmlspecialchars($subscriber['preferred_frequency']) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?php if($subscriber['is_active']): ?>

                                                <span class="badge-active">

                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span class="badge-inactive">

                                                    Inactive

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
                                                    href="?toggle=<?= $subscriber['id'] ?>"
                                                    class="btn btn-sm btn-warning"
                                                >

                                                    <i class="bi bi-arrow-repeat"></i>

                                                </a>

                                                <a
                                                    href="?delete=<?= $subscriber['id'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this subscriber?')"
                                                >

                                                    <i class="bi bi-trash"></i>

                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="10"
                                        class="text-center py-5">

                                        No subscribers found

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </form>

            <!-- PAGINATION -->

            <?php if($totalPages > 1): ?>

                <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 gap-3">

                    <div class="text-muted">

                        Page <?= $page ?>

                        of

                        <?= $totalPages ?>

                    </div>

                    <nav>

                        <ul class="pagination mb-0">

                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                >

                                    Previous

                                </a>

                            </li>

                            <?php for($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
                                    >

                                        <?= $i ?>

                                    </a>

                                </li>

                            <?php endfor; ?>

                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
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

/*
|--------------------------------------------------------------------------
| CATEGORY SELECT ALL
|--------------------------------------------------------------------------
*/

const checkAllCategories =
    document.getElementById('checkAllCategories');

const categoryCheckboxes =
    document.querySelectorAll('.category-checkbox');

checkAllCategories.addEventListener('change', function(){

    categoryCheckboxes.forEach(function(checkbox){

        checkbox.checked =
            checkAllCategories.checked;

    });

});

/*
|--------------------------------------------------------------------------
| TABLE SELECT ALL
|--------------------------------------------------------------------------
*/

const tableCheckAll =
    document.getElementById('tableCheckAll');

const checkAllSubscribers =
    document.getElementById('checkAllSubscribers');

const subscriberCheckboxes =
    document.querySelectorAll('.subscriber-checkbox');

const selectedCounter =
    document.getElementById('selectedCounter');

function updateSelectedCounter(){

    const checked =
        document.querySelectorAll(
            '.subscriber-checkbox:checked'
        ).length;

    selectedCounter.innerHTML =
        checked + ' Selected';
}

tableCheckAll.addEventListener('change', function(){

    subscriberCheckboxes.forEach(function(checkbox){

        checkbox.checked =
            tableCheckAll.checked;

    });

    checkAllSubscribers.checked =
        tableCheckAll.checked;

    updateSelectedCounter();

});

checkAllSubscribers.addEventListener('change', function(){

    subscriberCheckboxes.forEach(function(checkbox){

        checkbox.checked =
            checkAllSubscribers.checked;

    });

    tableCheckAll.checked =
        checkAllSubscribers.checked;

    updateSelectedCounter();

});

subscriberCheckboxes.forEach(function(checkbox){

    checkbox.addEventListener('change', function(){

        const total =
            subscriberCheckboxes.length;

        const checked =
            document.querySelectorAll(
                '.subscriber-checkbox:checked'
            ).length;

        tableCheckAll.checked =
            total === checked;

        checkAllSubscribers.checked =
            total === checked;

        updateSelectedCounter();

    });

});

/*
|--------------------------------------------------------------------------
| CONFIRM BULK ACTION
|--------------------------------------------------------------------------
*/

function confirmBulkAction(){

    const checked =
        document.querySelectorAll(
            '.subscriber-checkbox:checked'
        ).length;

    if (checked === 0) {

        alert('Please select subscribers first.');

        return false;
    }

    return confirm(
        'Are you sure you want to apply this bulk action?'
    );
}

</script>

</body>
</html>