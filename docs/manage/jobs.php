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
require_once __DIR__ . '/../bars/head_nav.php';
?>

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
    $stmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
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
    if (isset($_POST['job_ids']) && !empty($_POST['job_ids']) && isset($_POST['bulk_action']) && !empty($_POST['bulk_action'])) {
        $jobIds = $_POST['job_ids'];
        $action = $_POST['bulk_action'];
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));

        if ($action === 'feature') {
            $stmt = $db->prepare("UPDATE jobs SET is_featured = 1 WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
        }

        if ($action === 'unfeature') {
            $stmt = $db->prepare("UPDATE jobs SET is_featured = 0 WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
        }

        if ($action === 'activate') {
            $stmt = $db->prepare("UPDATE jobs SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
        }

        if ($action === 'deactivate') {
            $stmt = $db->prepare("UPDATE jobs SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
        }

        if ($action === 'mark_emailed') {
            $stmt = $db->prepare("UPDATE jobs SET views = 1 WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
        }

        if ($action === 'mark_not_emailed') {
            $stmt = $db->prepare("UPDATE jobs SET views = 0 WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
        }

        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM jobs WHERE id IN ($placeholders)");
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
    $where[] = "(jobs.title LIKE ? OR jobs.company_name LIKE ? OR jobs.location LIKE ? OR job_categories.name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category)) {
    $where[] = "jobs.category_id = ?";
    $params[] = $category;
}

if ($status !== '') {
    if ($status === 'active') $where[] = "jobs.is_active = 1";
    if ($status === 'inactive') $where[] = "jobs.is_active = 0";
    if ($status === 'featured') $where[] = "jobs.is_featured = 1";
    if ($status === 'emailed') $where[] = "jobs.views > 0";
    if ($status === 'remote') $where[] = "jobs.job_type = 'Remote'";
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/*
|--------------------------------------------------------------------------
| TOTAL JOBS
|--------------------------------------------------------------------------
*/

$countSql = "SELECT COUNT(*) total FROM jobs LEFT JOIN job_categories ON jobs.category_id = job_categories.id $whereSql";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalJobs = $countStmt->fetch()['total'];

$totalPages = ceil($totalJobs / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| FETCH JOBS
|--------------------------------------------------------------------------
*/

$sql = "SELECT jobs.*, job_categories.name AS category_name, job_sources.name AS source_name 
        FROM jobs 
        LEFT JOIN job_categories ON jobs.category_id = job_categories.id 
        LEFT JOIN job_sources ON jobs.source_id = job_sources.id 
        $whereSql 
        ORDER BY jobs.id DESC 
        LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalCategories = $db->query("SELECT COUNT(*) total FROM job_categories")->fetch()['total'];
$totalFeaturedJobs = $db->query("SELECT COUNT(*) total FROM jobs WHERE is_featured = 1")->fetch()['total'];
$totalActiveJobs = $db->query("SELECT COUNT(*) total FROM jobs WHERE is_active = 1")->fetch()['total'];
$totalRemoteJobs = $db->query("SELECT COUNT(*) total FROM jobs WHERE job_type = 'Remote'")->fetch()['total'];
$totalEmailedJobs = $db->query("SELECT COUNT(*) total FROM jobs WHERE views > 0")->fetch()['total'];
$totalViews = $db->query("SELECT SUM(views) total FROM jobs")->fetch()['total'] ?? 0;
$totalClicks = $db->query("SELECT SUM(clicks) total FROM jobs")->fetch()['total'] ?? 0;

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $db->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();
?>

<style>
    /* Page-specific styles for Manage Jobs - Matching Feed Page Style */
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

    .totaljobsheading {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .totaljobsheading span {
        color: var(--white) !important;
        font-weight: 700;
    }

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

    /* Bulk Actions Bar */
    .bulk-actions-bar {
        background: var(--bg-light);
        border-radius: 14px;
        padding: 15px 20px;
        margin-bottom: 25px;
        border: 1px solid #E8E8F0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* Job Cards - Exactly matching feed page style */
    #js-jobs-wrapper {
        background: var(--white);
        border-radius: 14px;
        margin-bottom: 20px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
        position: relative;
    }

    #js-jobs-wrapper:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    /* Checkbox for bulk actions */
    .job-checkbox-wrapper {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 10;
    }

    .job-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary);
    }

    .js-toprow {
        display: flex;
        gap: 20px;
        padding: 20px;
        align-items: flex-start;
    }

    .js-image {
        min-width: 80px;
    }

    .js-image img {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        object-fit: contain;
        background: var(--bg-light);
        border: 2px solid #E8E8F0;
        padding: 8px;
        transition: 0.3s;
    }

    .js-image img:hover {
        border-color: var(--primary);
        transform: scale(1.05);
    }

    .js-data {
        flex: 1;
    }

    .jobtitle {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        text-decoration: none;
        line-height: 1.4;
        transition: 0.3s;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .jobtitle:hover {
        background: var(--gradient-2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .company-name {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #636E72;
        font-size: 14px;
        margin: 8px 0;
    }

    .company-name i {
        color: var(--primary);
    }

    .js-status {
        background: #E8F8F5;
        color: var(--success);
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
        margin-right: 8px;
    }

    .bg-new {
        background: var(--danger);
        color: var(--white);
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .js-category-wrp {
        margin-top: 12px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }

    .js-fields {
        background: var(--bg-light);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 12px;
        border: 1px solid #E8E8F0;
        transition: 0.3s;
    }

    .js-fields:hover {
        border-color: var(--primary);
        background: #F0EDFF;
    }

    .js-bold {
        font-weight: 700;
        color: var(--primary);
    }

    .js-bottomrow {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 12px 20px;
        border-top: 1px solid #E8E8F0;
        background: var(--bg-light);
    }

    .js-actions {
        display: flex;
        gap: 10px;
    }

    .js-button {
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

    .js-button:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .js-btn-apply {
        background: var(--gradient-1);
        color: var(--white);
        box-shadow: var(--shadow-sm);
    }

    .js-btn-apply:hover {
        background: var(--gradient-1);
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
        color: var(--white);
    }

    .js-btn-delete {
        background: #fee2e2;
        color: #dc2626;
    }

    .js-btn-delete:hover {
        background: #dc2626;
        color: var(--white);
    }

    /* Badges for status */
    .badge-status {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        margin-left: 8px;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-featured {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-emailed {
        background: #dbeafe;
        color: #1e40af;
    }

    /* Pagination - Matching feed page */
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

    /* Select All Bar */
    .select-all-bar {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding: 12px 20px;
        background: var(--white);
        border-radius: 12px;
        border: 1px solid #E8E8F0;
    }

    /* Alert messages */
    .alert-custom {
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        border: none;
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

        .js-toprow {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 15px;
        }

        .js-bottomrow {
            justify-content: center;
        }

        .js-actions {
            flex-direction: column;
            width: 100%;
        }

        .js-button {
            width: 100%;
            justify-content: center;
        }

        .js-category-wrp {
            grid-template-columns: 1fr;
        }

        .bulk-actions-bar {
            flex-direction: column;
        }

        .select-all-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="main-wrapper">
    <!-- Page Heading -->
    <div class="page_heading">
        Manage Jobs
        <div class="totaljobsheading">
            Total jobs: <span><?= number_format($totalJobs) ?></span>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-danger alert-custom shadow-sm">
            <i class="bi bi-check-circle me-2"></i> Job removed successfully.
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-custom shadow-sm">
            <i class="bi bi-check-circle me-2"></i> Selected jobs updated successfully.
        </div>
    <?php endif; ?>

    <!-- Search Section -->
    <div class="search-section">
        <form method="GET">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control search-input" placeholder="Search jobs by title, company..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select search-input">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select search-input">
                        <option value="">All Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Jobs</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive Jobs</option>
                        <option value="featured" <?= $status === 'featured' ? 'selected' : '' ?>>Featured Jobs</option>
                        <option value="emailed" <?= $status === 'emailed' ? 'selected' : '' ?>>Emailed Jobs</option>
                        <option value="remote" <?= $status === 'remote' ? 'selected' : '' ?>>Remote Jobs</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 search-input">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Form -->
    <form method="POST" id="bulkActionForm">
        <!-- Select All Bar -->
        <div class="select-all-bar">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="selectAllJobs" style="width: 18px; height: 18px;">
                <label class="form-check-label fw-semibold ms-2" for="selectAllJobs">Select All Jobs</label>
            </div>
            <div class="text-muted" id="selectedCount">0 jobs selected</div>
            <div class="ms-auto">
                <select name="bulk_action" class="form-select" style="min-width: 200px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="feature">⭐ Mark as Featured</option>
                    <option value="unfeature">📌 Remove Featured</option>
                    <option value="activate">✅ Activate</option>
                    <option value="deactivate">⛔ Deactivate</option>
                    <option value="mark_emailed">📧 Mark as Emailed</option>
                    <option value="mark_not_emailed">📭 Remove Emailed</option>
                    <option value="delete">🗑️ Delete Selected</option>
                </select>
            </div>
            <button type="submit" class="btn btn-dark">Apply</button>
        </div>

        <!-- Job Listings - Exactly matching feed page style -->
        <?php if(count($jobs) > 0): ?>
            <?php foreach($jobs as $job): ?>
                <div id="js-jobs-wrapper">
                    <!-- Checkbox for bulk actions -->
                    <div class="job-checkbox-wrapper">
                        <input type="checkbox" name="job_ids[]" value="<?= $job['id'] ?>" class="job-checkbox">
                    </div>
                    
                    <div class="js-toprow">
                        <div class="js-image">
                            <img src="//cdn.greatugandajobs.com/jsjobsdata/data/default_logo_company/defaultlogo.png" 
                                 title="<?= htmlspecialchars($job['company_name']) ?>" 
                                 style="width:80px; height:80px; object-fit:contain;">
                        </div>
                        <div class="js-data">
                            <div class="js-first-row">
                                <span class="js-status js-type">Full-time</span>
                                <?php if((strtotime(date('Y-m-d')) - strtotime($job['posted_date'])) / (60 * 60 * 24) <= 1): ?>
                                    <span class="js-status bg-new">New</span>
                                <?php endif; ?>
                                
                                <!-- Status Badges -->
                                <?php if(!$job['is_active']): ?>
                                    <span class="badge-status badge-inactive">Inactive</span>
                                <?php else: ?>
                                    <span class="badge-status badge-active">Active</span>
                                <?php endif; ?>
                                
                                <?php if($job['is_featured']): ?>
                                    <span class="badge-status badge-featured">Featured</span>
                                <?php endif; ?>
                                
                                <?php if($job['views'] > 0): ?>
                                    <span class="badge-status badge-emailed">Emailed</span>
                                <?php endif; ?>
                            </div>
                            <div class="js-first-row">
                                <a class="jobtitle" href="<?= htmlspecialchars($job['apply_url']) ?>" target="_blank">
                                    <?= htmlspecialchars($job['title']) ?>
                                </a>
                            </div>
                            <div class="company-name">
                                <i class="bi bi-building"></i>
                                <span><?= htmlspecialchars($job['company_name']) ?></span>
                                <i class="bi bi-geo-alt ms-2"></i>
                                <span><?= htmlspecialchars($job['location'] ?: 'Remote') ?></span>
                            </div>
                            <div class="js-second-row js-category-wrp">
                                <div class="js-fields">
                                    <span class="js-bold">Job Category: </span><?= htmlspecialchars($job['category_name'] ?? 'Other') ?>
                                </div>
                                <div class="js-fields">
                                    <span class="js-bold">Posted: </span><?= !empty($job['posted_date']) ? date('d M Y', strtotime($job['posted_date'])) : 'N/A' ?>
                                </div>
                                <?php if($job['views'] > 0): ?>
                                    <div class="js-fields">
                                        <span class="js-bold">Emailed to: </span><?= $job['views'] ?> subscriber(s)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="js-bottomrow">
                        <div class="js-actions">
                            <a class="js-button js-btn-apply" href="<?= htmlspecialchars($job['apply_url']) ?>" target="_blank">
                                <i class="bi bi-eye"></i> View Details & Apply
                            </a>
                            <a href="?delete=<?= $job['id'] ?>" onclick="return confirm('Delete this job?')" class="js-button js-btn-delete">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center p-5" style="background: var(--white); border-radius: 14px; box-shadow: var(--shadow-sm);">
                <i class="bi bi-inbox" style="font-size: 48px; color: var(--primary);"></i>
                <p class="mt-3 mb-0">No jobs found matching your criteria.</p>
                <a href="jobs.php" class="btn btn-primary mt-3">Clear Filters</a>
            </div>
        <?php endif; ?>

        <!-- Pagination - Matching feed page -->
        <?php if($totalPages > 1): ?>
            <ul class="pagination-list">
                <?php if($page > 1): ?>
                    <li>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                    <li class="<?= $i == $page ? 'active' : '' ?>">
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                    <li>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </form>
</div>

<script>
// Select All functionality
const selectAllCheckbox = document.getElementById('selectAllJobs');
const jobCheckboxes = document.querySelectorAll('.job-checkbox');
const selectedCountSpan = document.getElementById('selectedCount');

function updateSelectedCount() {
    const checked = document.querySelectorAll('.job-checkbox:checked').length;
    selectedCountSpan.textContent = `${checked} job${checked !== 1 ? 's' : ''} selected`;
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checked === jobCheckboxes.length && jobCheckboxes.length > 0;
        selectAllCheckbox.indeterminate = checked > 0 && checked < jobCheckboxes.length;
    }
}

if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        jobCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateSelectedCount();
    });
}

jobCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

updateSelectedCount();

// Confirm before delete
document.querySelectorAll('.js-btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>