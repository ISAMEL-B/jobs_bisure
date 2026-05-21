<?php

session_start();

require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_role'])
    ||
    $_SESSION['user_role'] !== 'Admin'
) {

    die("
        <div style='padding:40px;font-family:Arial;'>

            <h2>Access Denied</h2>

            <p>
                Only administrators can manage users. <a href='../'>Dashboard</a>
            </p>

        </div>
    ");
}

/*
|--------------------------------------------------------------------------
| DELETE SINGLE USER
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $userId = (int) $_GET['delete'];

    // prevent self delete
    if ($userId == $_SESSION['user_id']) {

        header("Location: users.php?self_delete=1");
        exit;
    }

    $stmt = $db->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $stmt->execute([$userId]);

    header("Location: users.php?deleted=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE ALL USERS
|--------------------------------------------------------------------------
*/

if (isset($_POST['delete_all_users'])) {

    $stmt = $db->prepare("
        DELETE FROM users
        WHERE id != ?
    ");

    $stmt->execute([
        $_SESSION['user_id']
    ]);

    header("Location: users.php?all_deleted=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| TOGGLE STATUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['toggle'])) {

    $userId = (int) $_GET['toggle'];

    $stmt = $db->prepare("
        UPDATE users
        SET is_active =
            CASE
                WHEN is_active = 1 THEN 0
                ELSE 1
            END
        WHERE id = ?
    ");

    $stmt->execute([$userId]);

    header("Location: users.php?status=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHANGE ROLE
|--------------------------------------------------------------------------
*/

if (isset($_POST['change_role'])) {

    $userId = (int) $_POST['user_id'];

    $newRole = trim($_POST['role']);

    $allowedRoles = [
        'Admin',
        'Editor',
        'Moderator'
    ];

    if (in_array($newRole, $allowedRoles)) {

        $stmt = $db->prepare("
            UPDATE users
            SET role = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $newRole,
            $userId
        ]);

        header("Location: users.php?role_updated=1");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if (isset($_POST['change_password'])) {

    $userId = (int) $_POST['user_id'];

    $newPassword = trim($_POST['new_password']);

    if (!empty($newPassword)) {

        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $stmt = $db->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $hashedPassword,
            $userId
        ]);

        header("Location: users.php?password_updated=1");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| ADD USER
|--------------------------------------------------------------------------
*/

$message = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['add_user'])
) {

    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (
        empty($fullName)
        ||
        empty($email)
        ||
        empty($password)
    ) {

        $message = "
            <div class='alert alert-danger border-0 shadow-sm rounded-4'>
                All fields are required.
            </div>
        ";

    } else {

        $stmt = $db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $message = "
                <div class='alert alert-warning border-0 shadow-sm rounded-4'>
                    User already exists.
                </div>
            ";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $db->prepare("
                INSERT INTO users (

                    full_name,
                    email,
                    password,
                    role,
                    is_active,
                    created_at

                )

                VALUES (

                    ?, ?, ?, ?, 1, NOW()

                )
            ");

            $stmt->execute([

                $fullName,
                $email,
                $hashedPassword,
                $role

            ]);

            $message = "
                <div class='alert alert-success border-0 shadow-sm rounded-4'>
                    User created successfully.
                </div>
            ";
        }
    }
}

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalUsers = $db->query("
    SELECT COUNT(*) total
    FROM users
")->fetch()['total'];

$totalAdmins = $db->query("
    SELECT COUNT(*) total
    FROM users
    WHERE role = 'Admin'
")->fetch()['total'];

$totalEditors = $db->query("
    SELECT COUNT(*) total
    FROM users
    WHERE role = 'Editor'
")->fetch()['total'];

$totalModerators = $db->query("
    SELECT COUNT(*) total
    FROM users
    WHERE role = 'Moderator'
")->fetch()['total'];

$totalActive = $db->query("
    SELECT COUNT(*) total
    FROM users
    WHERE is_active = 1
")->fetch()['total'];

/*
|--------------------------------------------------------------------------
| SEARCH + FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {

    $where[] = "
        (
            full_name LIKE ?
            OR email LIKE ?
            OR role LIKE ?
        )
    ";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($roleFilter)) {

    $where[] = "role = ?";
    $params[] = $roleFilter;
}

$whereSql = '';

if (!empty($where)) {

    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

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
| TOTAL
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) total
    FROM users
    $whereSql
";

$countStmt = $db->prepare($countSql);

$countStmt->execute($params);

$totalFiltered = $countStmt->fetch()['total'];

$totalPages = ceil($totalFiltered / $perPage);

if ($totalPages < 1) {

    $totalPages = 1;
}

/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM users

    $whereSql

    ORDER BY id DESC

    LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);

$stmt->execute($params);

$users = $stmt->fetchAll();

$number = ($page - 1) * $perPage + 1;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Users</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body{
            background:#f4f7fb;
            overflow-x:hidden;
            font-family:Segoe UI, sans-serif;
        }

        .main-wrapper{
            margin-left:270px;
            padding:30px;
        }

        .top-header{
            background:#ffffff;
            padding:25px;
            border-radius:24px;
            margin-bottom:25px;
            box-shadow:0 4px 20px rgba(15,23,42,0.06);
        }

        .stats-card{
            border:none;
            border-radius:24px;
            transition:0.3s;
            background:#fff;
        }

        .stats-card:hover{
            transform:translateY(-4px);
        }

        .icon-box{
            width:60px;
            height:60px;
            border-radius:18px;
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

        .card-box{
            border:none;
            border-radius:24px;
            overflow:hidden;
            background:#fff;
        }

        .form-control,
        .form-select{
            min-height:52px;
            border-radius:14px;
            border:1px solid #dbe3ee;
        }

        .form-control:focus,
        .form-select:focus{
            box-shadow:none;
            border-color:#2563eb;
        }

        .table thead{
            background:#2563eb;
            color:#fff;
        }

        .table thead th{
            padding:16px;
            border:none;
        }

        .table tbody td{
            padding:16px;
            vertical-align:middle;
        }

        .badge-active{
            background:#dcfce7;
            color:#166534;
            padding:8px 14px;
            border-radius:12px;
            font-size:12px;
            font-weight:600;
        }

        .badge-inactive{
            background:#fee2e2;
            color:#991b1b;
            padding:8px 14px;
            border-radius:12px;
            font-size:12px;
            font-weight:600;
        }

        .you-badge{
            background:#dbeafe;
            color:#1d4ed8;
            padding:5px 10px;
            border-radius:10px;
            font-size:11px;
            font-weight:700;
            margin-left:8px;
        }

        .action-btn{
            width:40px;
            height:40px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:12px;
        }

        .btn-primary{
            background:#2563eb;
            border:none;
            border-radius:14px;
        }

        .btn-primary:hover{
            background:#1d4ed8;
        }

        .btn-danger{
            border-radius:14px;
        }

        .page-link{
            border:none;
            border-radius:10px;
            margin:0 3px;
            color:#2563eb;
        }

        .page-item.active .page-link{
            background:#2563eb;
        }

        @media(max-width:992px){

            .main-wrapper{
                margin-left:85px;
                padding:15px;
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

            <h2 class="fw-bold mb-1">
                Manage System Users
            </h2>

            <small class="text-muted">
                Advanced administrator management panel
            </small>

        </div>

        <div class="d-flex gap-2">

            <a href="../index.php"
               class="btn btn-primary px-4">

                <i class="bi bi-speedometer2 me-2"></i>

                Dashboard

            </a>

            <form method="POST"
                  onsubmit="return confirm('Delete all users except yourself?');">

                <button
                    type="submit"
                    name="delete_all_users"
                    class="btn btn-danger px-4"
                >

                    <i class="bi bi-trash3-fill me-2"></i>

                    Delete All Users

                </button>

            </form>

        </div>

    </div>

    <!-- ALERTS -->

    <?= $message ?>

    <?php if(isset($_GET['deleted'])): ?>

        <div class="alert alert-danger rounded-4 border-0 shadow-sm">
            User deleted successfully.
        </div>

    <?php endif; ?>

    <?php if(isset($_GET['all_deleted'])): ?>

        <div class="alert alert-danger rounded-4 border-0 shadow-sm">
            All users deleted successfully except your account.
        </div>

    <?php endif; ?>

    <?php if(isset($_GET['status'])): ?>

        <div class="alert alert-success rounded-4 border-0 shadow-sm">
            User status updated successfully.
        </div>

    <?php endif; ?>

    <?php if(isset($_GET['role_updated'])): ?>

        <div class="alert alert-primary rounded-4 border-0 shadow-sm">
            User role updated successfully.
        </div>

    <?php endif; ?>

    <?php if(isset($_GET['password_updated'])): ?>

        <div class="alert alert-warning rounded-4 border-0 shadow-sm">
            Password changed successfully.
        </div>

    <?php endif; ?>

    <!-- STATS -->

    <div class="row mb-4">

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Total Users
                        </h6>

                        <h2 class="fw-bold">
                            <?= number_format($totalUsers) ?>
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
                            Active Users
                        </h6>

                        <h2 class="fw-bold">
                            <?= number_format($totalActive) ?>
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
                            Admins
                        </h6>

                        <h2 class="fw-bold">
                            <?= number_format($totalAdmins) ?>
                        </h2>

                    </div>

                    <div class="icon-box orange">

                        <i class="bi bi-shield-lock"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6 mb-3">

            <div class="card stats-card shadow-sm">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <h6 class="text-muted">
                            Editors + Mods
                        </h6>

                        <h2 class="fw-bold">
                            <?= number_format($totalEditors + $totalModerators) ?>
                        </h2>

                    </div>

                    <div class="icon-box purple">

                        <i class="bi bi-person-workspace"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ADD USER -->

    <div class="card card-box shadow-sm mb-4">

        <div class="card-header bg-white border-0 py-4">

            <h4 class="fw-bold mb-0">
                Add New User
            </h4>

        </div>

        <div class="card-body p-4">

            <form method="POST">

                <input type="hidden"
                       name="add_user"
                       value="1">

                <div class="row">

                    <div class="col-md-4 mb-3">

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

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-semibold">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-2 mb-3">

                        <label class="form-label fw-semibold">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-2 mb-3">

                        <label class="form-label fw-semibold">
                            Role
                        </label>

                        <select
                            name="role"
                            class="form-select"
                        >

                            <option value="Admin">Admin</option>
                            <option value="Editor">Editor</option>
                            <option value="Moderator">Moderator</option>

                        </select>

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                    style="height:55px;"
                >

                    <i class="bi bi-person-plus-fill me-2"></i>

                    Create User

                </button>

            </form>

        </div>

    </div>

    <!-- USERS TABLE -->

    <div class="card card-box shadow-sm">

        <div class="card-header bg-white border-0 py-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <h4 class="fw-bold mb-0">
                    All System Users
                </h4>

                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">

                    <?= number_format($totalFiltered) ?>

                    Users

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
                            list="usersList"
                            autocomplete="off"
                            class="form-control"
                            placeholder="Search by name, email or role..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                        <datalist id="usersList">

                            <?php foreach($users as $u): ?>

                                <option value="<?= htmlspecialchars($u['full_name']) ?>">
                                <option value="<?= htmlspecialchars($u['email']) ?>">

                            <?php endforeach; ?>

                        </datalist>

                    </div>

                    <div class="col-lg-3 mb-3">

                        <select
                            name="role"
                            class="form-select"
                        >

                            <option value="">
                                Filter by role
                            </option>

                            <option value="Admin"
                                <?= $roleFilter == 'Admin' ? 'selected' : '' ?>>
                                Admin
                            </option>

                            <option value="Editor"
                                <?= $roleFilter == 'Editor' ? 'selected' : '' ?>>
                                Editor
                            </option>

                            <option value="Moderator"
                                <?= $roleFilter == 'Moderator' ? 'selected' : '' ?>>
                                Moderator
                            </option>

                        </select>

                    </div>

                    <div class="col-lg-2 mb-3">

                        <button class="btn btn-primary w-100 h-100">

                            <i class="bi bi-search me-2"></i>

                            Search

                        </button>

                    </div>

                    <div class="col-lg-2 mb-3">

                        <a href="users.php"
                           class="btn btn-light border w-100 h-100">

                            <i class="bi bi-x-circle me-2"></i>

                            Clear

                        </a>

                    </div>

                </div>

            </form>

            <!-- TABLE -->

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if(count($users) > 0): ?>

                            <?php foreach($users as $user): ?>

                                <tr>

                                    <td>
                                        <strong><?= $number++ ?></strong>
                                    </td>

                                    <td>

                                        <div class="fw-semibold">

                                            <?= htmlspecialchars($user['full_name']) ?>

                                            <?php if($user['id'] == $_SESSION['user_id']): ?>

                                                <span class="you-badge">
                                                    #YOU
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($user['email']) ?>
                                    </td>

                                    <td colspan="2">

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="change_role"
                                                value="1"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= $user['id'] ?>"
                                            >

                                            <select
                                                name="role"
                                                class="form-select form-select-sm"
                                                onchange="this.form.submit()"
                                            >

                                                <option value="Admin"
                                                    <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>
                                                    Admin
                                                </option>

                                                <option value="Editor"
                                                    <?= $user['role'] == 'Editor' ? 'selected' : '' ?>>
                                                    Editor
                                                </option>

                                                <option value="Moderator"
                                                    <?= $user['role'] == 'Moderator' ? 'selected' : '' ?>>
                                                    Moderator
                                                </option>

                                            </select>

                                        </form>

                                    </td>

                                    <td>

                                        <?php if($user['is_active']): ?>

                                            <span class="badge-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="badge-inactive">
                                                Disabled
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?= date(
                                            'd M Y',
                                            strtotime($user['created_at'])
                                        ) ?>

                                    </td>

                                    <td>

                                        <div class="d-flex gap-2 flex-wrap">

                                            <!-- TOGGLE -->

                                            <a
                                                href="?toggle=<?= $user['id'] ?>"
                                                class="btn btn-warning btn-sm action-btn"
                                                title="Enable / Disable"
                                            >

                                                <i class="bi bi-arrow-repeat"></i>

                                            </a>

                                            <!-- PASSWORD -->

                                            <button
                                                class="btn btn-dark btn-sm action-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#passwordModal<?= $user['id'] ?>"
                                            >

                                                <i class="bi bi-key"></i>

                                            </button>

                                            <!-- DELETE -->

                                            <?php if($user['id'] != $_SESSION['user_id']): ?>

                                                <a
                                                    href="?delete=<?= $user['id'] ?>"
                                                    onclick="return confirm('Delete this user?')"
                                                    class="btn btn-danger btn-sm action-btn"
                                                >

                                                    <i class="bi bi-trash"></i>

                                                </a>

                                            <?php endif; ?>

                                        </div>

                                        <!-- PASSWORD MODAL -->

                                        <div class="modal fade"
                                             id="passwordModal<?= $user['id'] ?>"
                                             tabindex="-1">

                                            <div class="modal-dialog">

                                                <div class="modal-content rounded-4 border-0">

                                                    <form method="POST">

                                                        <div class="modal-header border-0">

                                                            <h5 class="modal-title fw-bold">
                                                                Change Password
                                                            </h5>

                                                            <button
                                                                type="button"
                                                                class="btn-close"
                                                                data-bs-dismiss="modal"
                                                            ></button>

                                                        </div>

                                                        <div class="modal-body">

                                                            <input
                                                                type="hidden"
                                                                name="change_password"
                                                                value="1"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= $user['id'] ?>"
                                                            >

                                                            <label class="form-label fw-semibold">
                                                                New Password
                                                            </label>

                                                            <input
                                                                type="password"
                                                                name="new_password"
                                                                class="form-control"
                                                                required
                                                            >

                                                        </div>

                                                        <div class="modal-footer border-0">

                                                            <button
                                                                type="button"
                                                                class="btn btn-light border"
                                                                data-bs-dismiss="modal"
                                                            >

                                                                Cancel

                                                            </button>

                                                            <button
                                                                type="submit"
                                                                class="btn btn-primary"
                                                            >

                                                                Update Password

                                                            </button>

                                                        </div>

                                                    </form>

                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7" class="text-center py-5">

                                    <div class="text-muted">

                                        <i class="bi bi-people fs-1"></i>

                                        <p class="mt-3 mb-0">
                                            No users found
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

                        Page <?= $page ?>

                        of

                        <?= $totalPages ?>

                    </div>

                    <nav>

                        <ul class="pagination mb-0">

                            <?php for($i = 1; $i <= $totalPages; $i++): ?>

                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>"
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>