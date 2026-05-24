<?php
// =============================================================
// LOGIN CHECK 
// =============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the redirect function
function redirectToSignin() {
    header('Location: ../security/signin');
    exit();
}

// Check if user is logged in
$isLoggedIn = false;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $isLoggedIn = true;
} 

if (!$isLoggedIn) {
    redirectToSignin();
}

$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

// =============================================================
// INCLUDE EXTERNAL HEADER & NAVIGATION FROM bars/
// =============================================================
?>

<?php
require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    die("
        <div style='padding:40px;font-family:Arial; text-align:center;'>
            <h2>Access Denied</h2>
            <p>Only administrators can manage users. <a href='../'>Return to Dashboard</a></p>
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
        header("Location: users?self_delete=1");
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    header("Location: users?deleted=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE ALL USERS
|--------------------------------------------------------------------------
*/

if (isset($_POST['delete_all_users'])) {
    $stmt = $db->prepare("DELETE FROM users WHERE id != ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    header("Location: users?all_deleted=1");
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
        SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    
    header("Location: users?status=1");
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
    $allowedRoles = ['Admin', 'Editor', 'Moderator'];
    
    if (in_array($newRole, $allowedRoles)) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        
        header("Location: users?role_updated=1");
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
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        header("Location: users?password_updated=1");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| ADD USER
|--------------------------------------------------------------------------
*/

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    
    if (empty($fullName) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>All fields are required.</div>";
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $message = "<div class='alert alert-warning border-0 shadow-sm rounded-4'>User already exists.</div>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, password, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$fullName, $email, $hashedPassword, $role]);
            
            $message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>User created successfully.</div>";
        }
    }
}

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalUsers = $db->query("SELECT COUNT(*) total FROM users")->fetch()['total'];
$totalAdmins = $db->query("SELECT COUNT(*) total FROM users WHERE role = 'Admin'")->fetch()['total'];
$totalEditors = $db->query("SELECT COUNT(*) total FROM users WHERE role = 'Editor'")->fetch()['total'];
$totalModerators = $db->query("SELECT COUNT(*) total FROM users WHERE role = 'Moderator'")->fetch()['total'];
$totalActive = $db->query("SELECT COUNT(*) total FROM users WHERE is_active = 1")->fetch()['total'];

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
    $where[] = "(full_name LIKE ? OR email LIKE ? OR role LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($roleFilter)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$countSql = "SELECT COUNT(*) total FROM users $whereSql";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalFiltered = $countStmt->fetch()['total'];

$totalPages = ceil($totalFiltered / $perPage);
if ($totalPages < 1) $totalPages = 1;

/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM users $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$number = ($page - 1) * $perPage + 1;
?>

<style>
    /* Page-specific styles matching feed page */
    .main-wrapper {
        max-width: 700px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Page Heading */
    .page_heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding: 20px 25px;
        background: var(--gradient-1);
        border-radius: 14px;
        box-shadow: var(--shadow-md);
        font-size: 24px;
        font-weight: 700;
        color: var(--white);
    }

    .totalusersheading {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .totalusersheading span {
        color: var(--white) !important;
        font-weight: 700;
    }

    /* Stats Cards */
    .stats-card {
        border: none;
        border-radius: 18px;
        transition: 0.3s;
        background: var(--white);
        cursor: pointer;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }

    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 24px;
    }

    .blue { background: #2563eb; }
    .green { background: #059669; }
    .orange { background: #d97706; }
    .purple { background: #7c3aed; }

    /* Search Section */
    .search-section {
        background: var(--white);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    .search-input {
        border: 2px solid #E8E8F0;
        border-radius: 10px;
        padding: 10px 15px;
        transition: 0.3s;
    }

    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        outline: none;
    }

    /* Add User Card */
    .add-user-card {
        background: var(--white);
        border-radius: 14px;
        margin-bottom: 25px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    .add-user-card:hover {
        box-shadow: var(--shadow-md);
    }

    .card-header-custom {
        background: var(--gradient-1);
        color: var(--white);
        padding: 15px 20px;
        border-bottom: none;
    }

    .card-header-custom h4 {
        margin: 0;
        font-weight: 700;
    }

    /* Users Grid */
    .users-grid {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .user-card {
        background: var(--white);
        border-radius: 14px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .user-toprow {
        display: flex;
        gap: 20px;
        padding: 20px;
        align-items: flex-start;
    }

    .user-avatar {
        min-width: 70px;
    }

    .user-avatar i {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gradient-1);
        border-radius: 50%;
        font-size: 32px;
        color: white;
    }

    .user-data {
        flex: 1;
    }

    .user-name {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        text-decoration: none;
        line-height: 1.4;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: inline-block;
    }

    .you-badge {
        background: #dbeafe;
        color: #1d4ed8;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        margin-left: 10px;
        display: inline-block;
    }

    .user-meta {
        margin-top: 12px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }

    .user-field {
        background: var(--bg-light);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 12px;
        border: 1px solid #E8E8F0;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-field:hover {
        border-color: var(--primary);
        background: #F0EDFF;
    }

    .user-field i {
        color: var(--primary);
        font-size: 14px;
    }

    .field-label {
        font-weight: 600;
        color: var(--dark);
    }

    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-admin {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-editor {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-moderator {
        background: #e0e7ff;
        color: #3730a3;
    }

    .user-bottomrow {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 12px 20px;
        border-top: 1px solid #E8E8F0;
        background: var(--bg-light);
    }

    .user-actions {
        display: flex;
        gap: 10px;
    }

    .user-button {
        border: none;
        background: #F0EDFF;
        color: var(--primary);
        padding: 8px 18px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        transition: 0.3s;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .user-button:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .user-button-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .user-button-danger:hover {
        background: #dc2626;
        color: var(--white);
    }

    .user-button-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .user-button-warning:hover {
        background: #d97706;
        color: var(--white);
    }

    /* Role Select */
    .role-select {
        border: 2px solid #E8E8F0;
        border-radius: 10px;
        padding: 6px 12px;
        font-size: 13px;
        background: var(--white);
        transition: 0.3s;
    }

    .role-select:focus {
        border-color: var(--primary);
        outline: none;
    }

    /* Pagination */
    .pagination-list {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 30px;
        padding: 0;
    }

    .pagination-list li {
        list-style: none;
    }

    .pagination-list li a {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--white);
        color: var(--dark);
        font-weight: 600;
        text-decoration: none;
        box-shadow: var(--shadow-sm);
        transition: 0.3s;
        border: 2px solid #E8E8F0;
    }

    .pagination-list li.active a {
        background: var(--gradient-1);
        color: var(--white);
        border-color: transparent;
        box-shadow: var(--shadow-md);
    }

    .pagination-list li a:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    @media(max-width: 768px) {
        .main-wrapper {
            padding: 15px;
        }

        .page_heading {
            flex-direction: column;
            gap: 10px;
            text-align: center;
            font-size: 18px;
            padding: 15px;
        }

        .user-toprow {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .user-meta {
            grid-template-columns: 1fr;
        }

        .user-bottomrow {
            justify-content: center;
        }

        .user-actions {
            flex-direction: column;
            width: 100%;
        }

        .user-button {
            width: 100%;
            justify-content: center;
        }
    }

    /* Modal Styles */
    .modal-content-custom {
        border-radius: 14px;
        border: none;
    }

    .modal-header-custom {
        background: var(--gradient-1);
        color: var(--white);
        border-bottom: none;
        border-radius: 14px 14px 0 0;
    }
</style>
<?php require_once __DIR__ . '/../bars/head_nav.php'; ?>
<div class="main-wrapper">
    <!-- Page Heading -->
    <div class="page_heading">
        Manage System Users
        <div class="totalusersheading">
            Total Users: <span><?= number_format($totalUsers) ?></span>
        </div>
    </div>

    <!-- Alert Messages -->
    <?= $message ?>

    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4">User deleted successfully.</div>
    <?php endif; ?>

    <?php if(isset($_GET['self_delete'])): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">You cannot delete your own account.</div>
    <?php endif; ?>

    <?php if(isset($_GET['all_deleted'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4">All users deleted successfully except your account.</div>
    <?php endif; ?>

    <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">User status updated successfully.</div>
    <?php endif; ?>

    <?php if(isset($_GET['role_updated'])): ?>
        <div class="alert alert-primary border-0 shadow-sm mb-4">User role updated successfully.</div>
    <?php endif; ?>

    <?php if(isset($_GET['password_updated'])): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">Password changed successfully.</div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Users</h6>
                        <h2 class="mb-0"><?= number_format($totalUsers) ?></h2>
                    </div>
                    <div class="icon-box blue"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Active Users</h6>
                        <h2 class="mb-0"><?= number_format($totalActive) ?></h2>
                    </div>
                    <div class="icon-box green"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Admins</h6>
                        <h2 class="mb-0"><?= number_format($totalAdmins) ?></h2>
                    </div>
                    <div class="icon-box orange"><i class="bi bi-shield-lock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Staff Members</h6>
                        <h2 class="mb-0"><?= number_format($totalEditors + $totalModerators) ?></h2>
                    </div>
                    <div class="icon-box purple"><i class="bi bi-person-workspace"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Card -->
    <div class="add-user-card">
        <div class="card-header-custom">
            <h4><i class="bi bi-person-plus-fill me-2"></i> Add New User</h4>
        </div>
        <div class="card-body" style="padding: 20px;">
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control search-input" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control search-input" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control search-input" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select search-input">
                            <option value="Admin">👑 Admin</option>
                            <option value="Editor">✏️ Editor</option>
                            <option value="Moderator">🛡️ Moderator</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3" style="height: 50px;">
                    <i class="bi bi-person-plus-fill me-2"></i> Create User
                </button>
            </form>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="GET">
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control search-input" 
                           placeholder="Search by name, email or role..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select search-input">
                        <option value="">All Roles</option>
                        <option value="Admin" <?= $roleFilter == 'Admin' ? 'selected' : '' ?>>👑 Admin</option>
                        <option value="Editor" <?= $roleFilter == 'Editor' ? 'selected' : '' ?>>✏️ Editor</option>
                        <option value="Moderator" <?= $roleFilter == 'Moderator' ? 'selected' : '' ?>>🛡️ Moderator</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 search-input">
                        <i class="bi bi-search me-2"></i> Search
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="users" class="btn btn-light border w-100 search-input">
                        <i class="bi bi-x-circle me-2"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Users List - Card Layout -->
    <div class="users-grid">
        <?php if(count($users) > 0): ?>
            <?php foreach($users as $user): ?>
                <div class="user-card">
                    <div class="user-toprow">
                        <div class="user-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="user-data">
                            <div>
                                <span class="user-name"><?= htmlspecialchars($user['full_name']) ?></span>
                                <?php if($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="you-badge"><i class="bi bi-star-fill"></i> YOU</span>
                                <?php endif; ?>
                            </div>
                            <div class="user-meta">
                                <div class="user-field">
                                    <i class="bi bi-envelope"></i>
                                    <span class="field-label">Email:</span>
                                    <span><?= htmlspecialchars($user['email']) ?></span>
                                </div>
                                <div class="user-field">
                                    <i class="bi bi-calendar"></i>
                                    <span class="field-label">Joined:</span>
                                    <span><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                                </div>
                                <div class="user-field">
                                    <i class="bi bi-tag"></i>
                                    <span class="field-label">Role:</span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="change_role" value="1">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="role" class="role-select" onchange="this.form.submit()">
                                            <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>👑 Admin</option>

                                            <option value="Editor" <?= $user['role'] == 'Editor' ? 'selected' : '' ?>>✏️ Editor</option>

                                            <option value="Moderator" <?= $user['role'] == 'Moderator' ? 'selected' : '' ?>>🛡️ Moderator</option>
                                        </select>
                                    </form>
                                </div>
                                <div class="user-field">
                                    <i class="bi bi-activity"></i>
                                    <span class="field-label">Status:</span>
                                    <span class="badge-status <?= $user['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Disabled' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="user-bottomrow">
                        <div class="user-actions">
                            <a href="?toggle=<?= $user['id'] ?>" class="user-button user-button-warning">
                                <i class="bi bi-arrow-repeat"></i> Change Status
                            </a>
                            <button class="user-button" data-bs-toggle="modal" data-bs-target="#passwordModal<?= $user['id'] ?>">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Delete this user?')" class="user-button user-button-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Password Modal -->
                <div class="modal fade" id="passwordModal<?= $user['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content modal-content-custom">
                            <div class="modal-header modal-header-custom">
                                <h5 class="modal-title fw-bold"><i class="bi bi-key me-2"></i> Change Password</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body" style="padding: 25px;">
                                    <input type="hidden" name="change_password" value="1">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <label class="form-label fw-semibold">New Password for <?= htmlspecialchars($user['full_name']) ?></label>
                                    <input type="password" name="new_password" class="form-control search-input" required placeholder="Enter new password">
                                </div>
                                <div class="modal-footer border-0" style="padding: 20px;">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center p-5" style="background: var(--white); border-radius: 14px; box-shadow: var(--shadow-sm);">
                <i class="bi bi-people" style="font-size: 48px; color: var(--primary);"></i>
                <p class="mt-3 mb-0">No users found matching your criteria.</p>
                <a href="users" class="btn btn-primary mt-3">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
        <ul class="pagination-list">
            <?php if($page > 1): ?>
                <li>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                <li class="<?= $i == $page ? 'active' : '' ?>">
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <li>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../bars/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>