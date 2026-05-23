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
    header('Location: ../security/signin.php');
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
| DELETE SINGLE SUBSCRIBER
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {
    $subscriberId = (int) $_GET['delete'];

    $stmt = $db->prepare("DELETE FROM subscriber_categories WHERE subscriber_id = ?");
    $stmt->execute([$subscriberId]);

    $stmt = $db->prepare("DELETE FROM subscribers WHERE id = ?");
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
        SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
        WHERE id = ?
    ");
    $stmt->execute([$subscriberId]);

    header("Location: subscribe.php?updated=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET SUBSCRIBER DETAILS FOR MODAL
|--------------------------------------------------------------------------
*/

if (isset($_GET['get_subscriber'])) {
    $subscriberId = (int) $_GET['get_subscriber'];
    
    $stmt = $db->prepare("SELECT * FROM subscribers WHERE id = ?");
    $stmt->execute([$subscriberId]);
    $subscriberData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT category_id FROM subscriber_categories WHERE subscriber_id = ?");
    $stmt->execute([$subscriberId]);
    $subscriberCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $allCategories = $db->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();
    
    echo json_encode([
        'subscriber' => $subscriberData,
        'categories' => $subscriberCategories,
        'all_categories' => $allCategories
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE SUBSCRIBER CATEGORIES (AJAX)
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_categories'])) {
    $subscriberId = (int) $_POST['subscriber_id'];
    $selectedCategories = $_POST['categories'] ?? [];
    
    $stmt = $db->prepare("DELETE FROM subscriber_categories WHERE subscriber_id = ?");
    $stmt->execute([$subscriberId]);
    
    foreach ($selectedCategories as $categoryId) {
        $stmt = $db->prepare("INSERT INTO subscriber_categories (subscriber_id, category_id) VALUES (?, ?)");
        $stmt->execute([$subscriberId, $categoryId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Categories updated successfully!']);
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE SUBSCRIBER DETAILS (AJAX)
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $subscriberId = (int) $_POST['subscriber_id'];
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $country = trim($_POST['country']);
    $frequency = trim($_POST['preferred_frequency']);
    
    $stmt = $db->prepare("
        UPDATE subscribers 
        SET full_name = ?, phone = ?, country = ?, preferred_frequency = ?
        WHERE id = ?
    ");
    $stmt->execute([$fullName, $phone, $country, $frequency, $subscriberId]);
    
    echo json_encode(['success' => true, 'message' => 'Subscriber details updated successfully!']);
    exit;
}

/*
|--------------------------------------------------------------------------
| TOGGLE STATUS AJAX
|--------------------------------------------------------------------------
*/

if (isset($_GET['toggle_ajax'])) {
    $subscriberId = (int) $_GET['toggle_ajax'];
    
    $stmt = $db->prepare("
        UPDATE subscribers
        SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
        WHERE id = ?
    ");
    $stmt->execute([$subscriberId]);
    
    $stmt = $db->prepare("SELECT is_active FROM subscribers WHERE id = ?");
    $stmt->execute([$subscriberId]);
    $newStatus = $stmt->fetch(PDO::FETCH_ASSOC)['is_active'];
    
    echo json_encode(['success' => true, 'is_active' => $newStatus]);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $db->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalSubscribers = $db->query("SELECT COUNT(*) total FROM subscribers")->fetch()['total'];
$totalActiveSubscribers = $db->query("SELECT COUNT(*) total FROM subscribers WHERE is_active = 1")->fetch()['total'];
$totalInactiveSubscribers = $db->query("SELECT COUNT(*) total FROM subscribers WHERE is_active = 0")->fetch()['total'];
$totalCategories = $db->query("SELECT COUNT(*) total FROM job_categories")->fetch()['total'];
$totalEmails = $db->query("SELECT COUNT(*) total FROM email_logs")->fetch()['total'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulkAction = $_POST['bulk_action'];
    $subscriberIds = $_POST['subscriber_ids'] ?? [];

    if (!empty($subscriberIds)) {
        $subscriberIds = array_map('intval', $subscriberIds);
        $placeholders = implode(',', array_fill(0, count($subscriberIds), '?'));

        if ($bulkAction === 'delete_selected') {
            $stmt = $db->prepare("DELETE FROM subscriber_categories WHERE subscriber_id IN ($placeholders)");
            $stmt->execute($subscriberIds);
            $stmt = $db->prepare("DELETE FROM subscribers WHERE id IN ($placeholders)");
            $stmt->execute($subscriberIds);
            $message = "<div class='alert alert-danger'>Selected subscribers deleted successfully.</div>";
        }

        if ($bulkAction === 'activate_selected') {
            $stmt = $db->prepare("UPDATE subscribers SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($subscriberIds);
            $message = "<div class='alert alert-success'>Selected subscribers activated successfully.</div>";
        }

        if ($bulkAction === 'deactivate_selected') {
            $stmt = $db->prepare("UPDATE subscribers SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($subscriberIds);
            $message = "<div class='alert alert-warning'>Selected subscribers deactivated successfully.</div>";
        }
    }
}

/*
|--------------------------------------------------------------------------
| ADD / UPDATE SUBSCRIBER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['bulk_action']) && !isset($_POST['update_categories']) && !isset($_POST['update_details'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? 'Uganda');
    $frequency = trim($_POST['preferred_frequency'] ?? 'Daily');
    $selectedCategories = $_POST['categories'] ?? [];

    if (empty($fullName) || empty($email) || empty($selectedCategories)) {
        $message = "<div class='alert alert-danger'>Please fill all required fields.</div>";
    } else {
        $stmt = $db->prepare("SELECT id FROM subscribers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingSubscriber = $stmt->fetch();

        if (!$existingSubscriber) {
            $stmt = $db->prepare("
                INSERT INTO subscribers (email, full_name, phone, country, preferred_frequency, is_active, email_verified, unsubscribe_token, created_at)
                VALUES (?, ?, ?, ?, ?, 1, 1, MD5(RAND()), NOW())
            ");
            $stmt->execute([$email, $fullName, $phone, $country, $frequency]);
            $subscriberId = $db->lastInsertId();
            $message = "<div class='alert alert-success'>Subscriber added successfully.</div>";
        } else {
            $subscriberId = $existingSubscriber['id'];
            $stmt = $db->prepare("UPDATE subscribers SET full_name = ?, phone = ?, country = ?, preferred_frequency = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $country, $frequency, $subscriberId]);
            $stmt = $db->prepare("DELETE FROM subscriber_categories WHERE subscriber_id = ?");
            $stmt->execute([$subscriberId]);
            $message = "<div class='alert alert-info'>Subscriber updated successfully.</div>";
        }

        foreach ($selectedCategories as $categoryId) {
            $stmt = $db->prepare("INSERT INTO subscriber_categories (subscriber_id, category_id) VALUES (?, ?)");
            $stmt->execute([$subscriberId, $categoryId]);
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
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| WHERE CONDITIONS
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR country LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status !== '') {
    if ($status === 'active') $where[] = "is_active = 1";
    if ($status === 'inactive') $where[] = "is_active = 0";
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) total FROM subscribers $whereSql";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalFilteredSubscribers = $countStmt->fetch()['total'];
$totalPages = ceil($totalFilteredSubscribers / $perPage);
if ($totalPages < 1) $totalPages = 1;

$sql = "SELECT * FROM subscribers $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

$number = ($page - 1) * $perPage + 1;
?>

<style>
    /* Page-specific styles for Subscriber Management */
    .main-wrapper {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 30px;
    }

    /* Stats Cards Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stats-card {
        background: var(--white);
        border-radius: 20px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }

    .stats-info h6 {
        color: #636E72;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .stats-info h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: var(--dark);
    }

    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 22px;
    }

    .blue { background: var(--primary); }
    .green { background: var(--success); }
    .orange { background: #d97706; }
    .purple { background: #7c3aed; }
    .red { background: var(--danger); }

    /* Accordion Styles */
    .accordion-section {
        background: var(--white);
        border-radius: 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
        overflow: hidden;
    }

    .accordion-header {
        background: var(--white);
        padding: 18px 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.3s;
        border-bottom: 2px solid transparent;
    }

    .accordion-header:hover {
        background: #F8F9FE;
    }

    .accordion-header.active {
        border-bottom-color: var(--primary);
        background: #F8F9FE;
    }

    .accordion-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .accordion-header h3 i {
        color: var(--primary);
        font-size: 22px;
    }

    .accordion-icon {
        font-size: 20px;
        color: var(--primary);
        transition: 0.3s;
    }

    .accordion-header.active .accordion-icon {
        transform: rotate(180deg);
    }

    .accordion-content {
        display: none;
        padding: 25px;
        border-top: 1px solid #E8E8F0;
    }

    .accordion-content.active {
        display: block;
    }

    /* FORM - 2 COLUMN LAYOUT */
    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
    }

    .form-control, .form-select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #E8E8F0;
        border-radius: 12px;
        font-size: 14px;
        transition: 0.3s;
    }

    .form-control:focus, .form-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
    }

    /* Categories Grid - 3 columns */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        max-height: 300px;
        overflow-y: auto;
        padding: 15px;
        border: 1px solid #E8E8F0;
        border-radius: 12px;
        background: var(--bg-light);
    }

    .category-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 10px;
        transition: 0.3s;
        background: white;
        border: 1px solid #E8E8F0;
    }

    .category-item:hover {
        background: #F0EDFF;
        border-color: var(--primary);
    }

    .category-item input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .category-item label {
        margin: 0;
        cursor: pointer;
        font-weight: normal;
        font-size: 13px;
    }

    /* Full width button */
    .full-width-btn {
        margin-top: 20px;
    }

    .btn-primary {
        background: var(--gradient-1);
        border: none;
        border-radius: 25px;
        padding: 12px 24px;
        font-weight: 600;
        color: white;
        cursor: pointer;
        transition: 0.3s;
        width: 100%;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .btn-secondary {
        background: #E8E8F0;
        border: none;
        border-radius: 25px;
        padding: 8px 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-secondary:hover {
        background: #D1D1E0;
    }

    /* Alert Messages */
    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: none;
    }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-danger { background: #fee2e2; color: #991b1b; }
    .alert-warning { background: #fef3c7; color: #92400e; }
    .alert-info { background: #dbeafe; color: #1e40af; }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table thead {
        background: var(--dark);
        color: white;
    }

    table th, table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #E8E8F0;
    }

    table tbody tr:hover {
        background: #F8F9FE;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-primary {
        background: var(--primary);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-info {
        background: #E0F2FE;
        color: #0284C7;
    }
    .btn-info:hover { background: #0284C7; color: white; }
    .btn-warning {
        background: #FEF3C7;
        color: #D97706;
    }
    .btn-warning:hover { background: #D97706; color: white; }
    .btn-danger {
        background: #FEE2E2;
        color: #DC2626;
    }
    .btn-danger:hover { background: #DC2626; color: white; }

    /* Search & Filter */
    .search-filter {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    .bulk-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: #F8F9FE;
        border-radius: 12px;
    }

    .bulk-actions-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .bulk-actions-right {
        display: flex;
        gap: 10px;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .pagination a, .pagination span {
        padding: 8px 14px;
        border-radius: 10px;
        text-decoration: none;
        color: var(--dark);
        background: white;
        border: 1px solid #E8E8F0;
        transition: 0.3s;
    }

    .pagination a:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .pagination .active a, .pagination .active span {
        background: var(--gradient-1);
        color: white;
        border-color: transparent;
    }

    /* Modal Styles */
    #subscriberModalOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 9998;
    }

    #subscriberModal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 600px;
        max-height: 85vh;
        overflow-y: auto;
        z-index: 9999;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
    }

    .modal-header-custom {
        background: var(--gradient-1);
        color: var(--white);
        padding: 15px 20px;
        font-size: 18px;
        font-weight: 700;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        transition: 0.3s;
    }

    .modal-close:hover {
        transform: rotate(90deg);
        background: rgba(255, 255, 255, 0.3);
    }

    .modal-body-custom {
        background: var(--white);
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .modal-footer-custom {
        background: var(--white);
        padding: 15px 20px;
        border-top: 1px solid #E8E8F0;
        border-radius: 0 0 12px 12px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .tab-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #E8E8F0;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 10px 20px;
        font-weight: 600;
        color: var(--dark);
        cursor: pointer;
        transition: 0.3s;
        position: relative;
    }

    .tab-btn.active {
        color: var(--primary);
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--gradient-1);
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    /* Toast */
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        color: #fff;
        padding: 14px 18px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 280px;
        z-index: 999999;
        transform: translateX(120%);
        opacity: 0;
        transition: all 0.4s ease;
    }

    .toast-notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast-success {
        background: linear-gradient(135deg, #00B894 0%, #00CEC9 100%);
    }

    .toast-error {
        background: linear-gradient(135deg, #FF7675 0%, #D63031 100%);
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .categories-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .main-wrapper {
            padding: 20px;
        }
        .form-row {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .search-filter {
            grid-template-columns: 1fr;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .categories-grid {
            grid-template-columns: 1fr;
        }
        .bulk-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .bulk-actions-left {
            justify-content: space-between;
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .stats-info h2 {
            font-size: 22px;
        }
        .icon-box {
            width: 45px;
            height: 45px;
            font-size: 18px;
        }
        .accordion-header {
            padding: 15px 20px;
        }
        .accordion-header h3 {
            font-size: 16px;
        }
        .accordion-content {
            padding: 15px;
        }
        table th, table td {
            padding: 8px 10px;
            font-size: 12px;
        }
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<?php require_once __DIR__ . '/../bars/head_nav.php'; ?>

<!-- Main Content - Centered -->
<div class="main-wrapper">
    <?= $message ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-info">
                <h6>Subscribers</h6>
                <h2><?= number_format($totalSubscribers) ?></h2>
            </div>
            <div class="icon-box blue"><i class="bi bi-people"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Active</h6>
                <h2><?= number_format($totalActiveSubscribers) ?></h2>
            </div>
            <div class="icon-box green"><i class="bi bi-check-circle"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Inactive</h6>
                <h2><?= number_format($totalInactiveSubscribers) ?></h2>
            </div>
            <div class="icon-box red"><i class="bi bi-x-circle"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Categories</h6>
                <h2><?= number_format($totalCategories) ?></h2>
            </div>
            <div class="icon-box orange"><i class="bi bi-tags"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Emails Sent</h6>
                <h2><?= number_format($totalEmails) ?></h2>
            </div>
            <div class="icon-box purple"><i class="bi bi-envelope-check"></i></div>
        </div>
    </div>

    <!-- Accordion: Add Subscriber -->
    <div class="accordion-section">
        <div class="accordion-header" onclick="toggleAccordion(this)">
            <h3><i class="bi bi-person-plus-fill"></i> Add New Subscriber</h3>
            <i class="bi bi-chevron-down accordion-icon"></i>
        </div>
        <div class="accordion-content">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" class="form-control" value="Uganda">
                    </div>
                    <div class="form-group">
                        <label>Email Frequency</label>
                        <select name="preferred_frequency" class="form-select">
                            <option value="Instant">Instant</option>
                            <option value="Daily" selected>Daily</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Categories *</label>
                    <div class="categories-grid">
                        <div class="category-item" style="background:#F0EDFF;">
                            <input type="checkbox" id="checkAllCategories">
                            <label><strong>Select All Categories</strong></label>
                        </div>
                        <?php foreach ($categories as $category): ?>
                            <div class="category-item">
                                <input type="checkbox" class="category-checkbox" name="categories[]" value="<?= $category['id'] ?>">
                                <label><?= htmlspecialchars($category['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn-primary full-width-btn">Save Subscriber</button>
            </form>
        </div>
    </div>

    <!-- Accordion: Manage Subscribers -->
    <div class="accordion-section">
        <div class="accordion-header active" onclick="toggleAccordion(this)">
            <h3><i class="bi bi-people-fill"></i> Manage Subscribers</h3>
            <i class="bi bi-chevron-down accordion-icon"></i>
        </div>
        <div class="accordion-content active">
            <!-- Search & Filter -->
            <form method="GET" class="search-filter">
                <input type="text" name="search" class="form-control" placeholder="Search subscriber..." value="<?= htmlspecialchars($search) ?>">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit" class="btn-primary" style="margin:0;">Filter</button>
                <a href="subscribe.php" class="btn-secondary" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">Reset</a>
            </form>

            <!-- Bulk Actions -->
            <form method="POST" id="bulkForm">
                <div class="bulk-actions">
                    <div class="bulk-actions-left">
                        <input type="checkbox" id="checkAllSubscribers">
                        <label>Select All</label>
                        <span class="badge-primary" id="selectedCounter">0 Selected</span>
                    </div>
                    <div class="bulk-actions-right">
                        <select name="bulk_action" class="form-select" style="width:200px;">
                            <option value="">Bulk Actions</option>
                            <option value="activate_selected">Activate Selected</option>
                            <option value="deactivate_selected">Deactivate Selected</option>
                            <option value="delete_selected">Delete Selected</option>
                        </select>
                        <button type="submit" class="btn-secondary" onclick="return confirmBulkAction()">Apply</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="tableCheckAll"></th>
                                <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Date</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($subscribers) > 0): ?>
                                <?php foreach($subscribers as $subscriber): ?>
                                    <tr>
                                        <td><input type="checkbox" class="subscriber-checkbox" name="subscriber_ids[]" value="<?= $subscriber['id'] ?>"></td>
                                        <td><strong><?= $number++ ?></strong></td>
                                        <td><?= htmlspecialchars($subscriber['full_name']) ?></td>
                                        <td colspan="1"><?= htmlspecialchars($subscriber['email']) ?></td>
                                        <td colspan="1"><?= htmlspecialchars($subscriber['phone']) ?></td>
                                        <td><span class="<?= $subscriber['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $subscriber['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                        <td><?= date('d M Y', strtotime($subscriber['created_at'])) ?></td>
                                        <td class="action-buttons">
                                            <button type="button" class="btn-sm btn-info" onclick="viewSubscriber(<?= $subscriber['id'] ?>)"><i class="bi bi-eye"></i></button>
                                            <button type="button" class="btn-sm btn-warning" onclick="toggleSubscriberStatus(<?= $subscriber['id'] ?>, this)"><i class="bi bi-arrow-repeat"></i></button>
                                            <a href="?delete=<?= $subscriber['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this subscriber?')"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10" style="text-align:center; padding:40px;">No subscribers found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Subscriber Modal -->
<div id="subscriberModalOverlay"></div>
<div id="subscriberModal">
    <div class="modal-header-custom">
        <span><i class="bi bi-person-circle"></i> Subscriber Details</span>
        <button class="modal-close" onclick="closeSubscriberModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body-custom">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="switchTab('details')">Personal Info</button>
            <button class="tab-btn" onclick="switchTab('categories')">Categories</button>
        </div>
        
        <div id="detailsTab" class="tab-pane active">
            <form id="updateDetailsForm">
                <input type="hidden" name="subscriber_id" id="modal_subscriber_id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="modal_full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="modal_email" class="form-control" disabled style="background:#f0f0f0;">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="modal_phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" id="modal_country" class="form-control">
                </div>
                <div class="form-group">
                    <label>Frequency</label>
                    <select name="preferred_frequency" id="modal_frequency" class="form-select">
                        <option value="Instant">Instant</option>
                        <option value="Daily">Daily</option>
                        <option value="Weekly">Weekly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <div><span id="modal_status" class="badge-active">Active</span></div>
                </div>
                <div class="form-group">
                    <label>Subscribed Since</label>
                    <div><span id="modal_created_at" class="text-muted"></span></div>
                </div>
                <button type="submit" class="btn-primary full-width-btn">Update Details</button>
            </form>
        </div>
        
        <div id="categoriesTab" class="tab-pane">
            <form id="updateCategoriesForm">
                <input type="hidden" name="subscriber_id" id="cat_subscriber_id">
                <div class="form-group">
                    <label>Select Categories</label>
                    <div id="categoriesList" class="categories-grid"></div>
                </div>
                <button type="submit" class="btn-primary full-width-btn">Update Categories</button>
            </form>
        </div>
    </div>
    <div class="modal-footer-custom">
        <button class="btn-secondary" onclick="closeSubscriberModal()">Close</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    // Accordion Function
    function toggleAccordion(header) {
        const allHeaders = document.querySelectorAll('.accordion-header');
        const allContents = document.querySelectorAll('.accordion-content');
        
        const content = header.nextElementSibling;
        const isActive = header.classList.contains('active');
        
        if (!isActive) {
            allHeaders.forEach(h => h.classList.remove('active'));
            allContents.forEach(c => c.classList.remove('active'));
            header.classList.add('active');
            content.classList.add('active');
        }
    }

    // Modal functions
    function viewSubscriber(id) {
        $.ajax({
            url: 'subscribe.php?get_subscriber=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.subscriber) {
                    $('#modal_subscriber_id').val(data.subscriber.id);
                    $('#cat_subscriber_id').val(data.subscriber.id);
                    $('#modal_full_name').val(data.subscriber.full_name);
                    $('#modal_email').val(data.subscriber.email);
                    $('#modal_phone').val(data.subscriber.phone);
                    $('#modal_country').val(data.subscriber.country);
                    $('#modal_frequency').val(data.subscriber.preferred_frequency);
                    $('#modal_created_at').text(new Date(data.subscriber.created_at).toLocaleDateString());
                    
                    var statusHtml = data.subscriber.is_active ? 
                        '<span class="badge-active">Active</span>' : 
                        '<span class="badge-inactive">Inactive</span>';
                    $('#modal_status').html(statusHtml);
                    
                    var categoriesHtml = '';
                    data.all_categories.forEach(function(cat) {
                        var checked = data.categories.includes(cat.id) ? 'checked' : '';
                        categoriesHtml += '<div class="category-item">' +
                            '<input type="checkbox" name="categories[]" value="' + cat.id + '" ' + checked + '>' +
                            '<label>' + cat.name + '</label>' +
                            '</div>';
                    });
                    $('#categoriesList').html(categoriesHtml);
                    
                    $('#subscriberModalOverlay').fadeIn();
                    $('#subscriberModal').fadeIn();
                    document.body.style.overflow = 'hidden';
                }
            }
        });
    }
    
    function closeSubscriberModal() {
        $('#subscriberModalOverlay').fadeOut();
        $('#subscriberModal').fadeOut();
        document.body.style.overflow = '';
    }
    
    $('#subscriberModalOverlay').click(function() {
        closeSubscriberModal();
    });
    
    function switchTab(tab) {
        $('.tab-btn').removeClass('active');
        $('.tab-pane').removeClass('active');
        
        if (tab === 'details') {
            $('.tab-btn:eq(0)').addClass('active');
            $('#detailsTab').addClass('active');
        } else {
            $('.tab-btn:eq(1)').addClass('active');
            $('#categoriesTab').addClass('active');
        }
    }
    
    // Update details form submission
    $('#updateDetailsForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'subscribe.php',
            type: 'POST',
            data: $(this).serialize() + '&update_details=1',
            dataType: 'json',
            success: function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            }
        });
    });
    
    // Update categories form submission
    $('#updateCategoriesForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'subscribe.php',
            type: 'POST',
            data: $(this).serialize() + '&update_categories=1',
            dataType: 'json',
            success: function(response) {
                showToast(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            }
        });
    });
    
    // Toggle status
    function toggleSubscriberStatus(id, btn) {
        $.ajax({
            url: 'subscribe.php?toggle_ajax=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var statusSpan = $(btn).closest('tr').find('td:eq(7) span');
                    if (response.is_active) {
                        statusSpan.removeClass('badge-inactive').addClass('badge-active').text('Active');
                    } else {
                        statusSpan.removeClass('badge-active').addClass('badge-inactive').text('Inactive');
                    }
                    showToast('Status updated successfully!', 'success');
                }
            }
        });
    }
    
    // Toast notification
    function showToast(message, type) {
        var toast = $('<div>').addClass('toast-notification toast-' + type).html(
            '<div class="toast-icon"><i class="bi bi-' + (type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i></div>' +
            '<div class="toast-message">' + message + '</div>'
        );
        $('body').append(toast);
        setTimeout(function() { toast.addClass('show'); }, 100);
        setTimeout(function() {
            toast.removeClass('show');
            setTimeout(function() { toast.remove(); }, 400);
        }, 4000);
    }
    
    // Category select all
    const checkAllCategories = document.getElementById('checkAllCategories');
    if (checkAllCategories) {
        checkAllCategories.addEventListener('change', function() {
            document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = this.checked);
        });
    }
    
    // Subscriber bulk select
    const tableCheckAll = document.getElementById('tableCheckAll');
    const checkAllSubscribers = document.getElementById('checkAllSubscribers');
    const subscriberCheckboxes = document.querySelectorAll('.subscriber-checkbox');
    const selectedCounter = document.getElementById('selectedCounter');
    
    function updateSelectedCounter() {
        const checked = document.querySelectorAll('.subscriber-checkbox:checked').length;
        if (selectedCounter) selectedCounter.innerHTML = checked + ' Selected';
    }
    
    if (tableCheckAll) {
        tableCheckAll.addEventListener('change', function() {
            subscriberCheckboxes.forEach(cb => cb.checked = tableCheckAll.checked);
            if (checkAllSubscribers) checkAllSubscribers.checked = tableCheckAll.checked;
            updateSelectedCounter();
        });
    }
    
    if (checkAllSubscribers) {
        checkAllSubscribers.addEventListener('change', function() {
            subscriberCheckboxes.forEach(cb => cb.checked = checkAllSubscribers.checked);
            if (tableCheckAll) tableCheckAll.checked = checkAllSubscribers.checked;
            updateSelectedCounter();
        });
    }
    
    subscriberCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const total = subscriberCheckboxes.length;
            const checked = document.querySelectorAll('.subscriber-checkbox:checked').length;
            if (tableCheckAll) tableCheckAll.checked = total === checked;
            if (checkAllSubscribers) checkAllSubscribers.checked = total === checked;
            updateSelectedCounter();
        });
    });
    
    function confirmBulkAction() {
        const checked = document.querySelectorAll('.subscriber-checkbox:checked').length;
        if (checked === 0) { alert('Please select subscribers first.'); return false; }
        return confirm('Are you sure you want to apply this bulk action?');
    }
    
    // Escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSubscriberModal();
    });
    
    window.switchTab = switchTab;
    window.closeSubscriberModal = closeSubscriberModal;
    window.viewSubscriber = viewSubscriber;
    window.toggleSubscriberStatus = toggleSubscriberStatus;
    window.toggleAccordion = toggleAccordion;
</script>
</body>
</html>