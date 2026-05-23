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

$message = '';

/*
|--------------------------------------------------------------------------
| CREATE CATEGORY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    
    if (empty($name) || empty($slug)) {
        $message = "<div class='alert alert-danger border-0 shadow-sm mb-4'>Category name and slug are required.</div>";
    } else {
        $check = $db->prepare("SELECT id FROM job_categories WHERE slug = ? LIMIT 1");
        $check->execute([$slug]);
        
        if ($check->fetch()) {
            $message = "<div class='alert alert-warning border-0 shadow-sm mb-4'>Category slug already exists.</div>";
        } else {
            $stmt = $db->prepare("INSERT INTO job_categories (name, slug, keywords, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $keywords, $icon]);
            
            header("Location: categories.php?created=1");
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE CATEGORY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = (int) $_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    
    $stmt = $db->prepare("UPDATE job_categories SET name = ?, slug = ?, keywords = ?, icon = ? WHERE id = ?");
    $stmt->execute([$name, $slug, $keywords, $icon, $id]);
    
    header("Location: categories.php?updated=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE CATEGORY
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM job_categories WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: categories.php?deleted=1");
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

$perPage = 9; // 3x3 grid layout
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
*/

$whereSql = '';
$params = [];

if (!empty($search)) {
    $whereSql = "WHERE name LIKE ? OR slug LIKE ? OR keywords LIKE ?";
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

$countSql = "SELECT COUNT(*) total FROM job_categories $whereSql";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalCategories = $countStmt->fetch()['total'];

$totalPages = ceil($totalCategories / $perPage);
if ($totalPages < 1) $totalPages = 1;

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT 
        job_categories.*,
        (SELECT COUNT(*) FROM jobs WHERE jobs.category_id = job_categories.id) AS total_jobs,
        (SELECT COUNT(*) FROM subscriber_categories WHERE subscriber_categories.category_id = job_categories.id) AS total_subscribers
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
    $stmt = $db->prepare("SELECT * FROM job_categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

/*
|--------------------------------------------------------------------------
| DASHBOARD STATS
|--------------------------------------------------------------------------
*/

$totalJobs = $db->query("SELECT COUNT(*) total FROM jobs")->fetch()['total'];
$totalSubscribers = $db->query("SELECT COUNT(*) total FROM subscribers")->fetch()['total'];
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

    .totalcategoriesheading {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .totalcategoriesheading span {
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

    /* Form Card */
    .form-card {
        background: var(--white);
        border-radius: 14px;
        margin-bottom: 25px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    .form-card:hover {
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

    /* Categories Grid */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .category-card {
        background: var(--white);
        border-radius: 14px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #E8E8F0;
        position: relative;
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }

    .category-card-header {
        padding: 20px;
        text-align: center;
        background: linear-gradient(135deg, #faf9ff 0%, #fff 100%);
        border-bottom: 1px solid #E8E8F0;
        position: relative;
    }

    .category-icon-large {
        width: 80px;
        height: 80px;
        margin: 0 auto 15px;
        background: var(--gradient-1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .category-icon-large i {
        font-size: 40px;
        color: white;
    }

    .category-name {
        font-size: 20px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .category-slug {
        font-size: 12px;
        color: var(--primary);
        background: #F0EDFF;
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
    }

    .category-card-body {
        padding: 20px;
    }

    .stats-row {
        display: flex;
        justify-content: space-around;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #E8E8F0;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
    }

    .stat-label {
        font-size: 11px;
        color: #636E72;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .keywords-section {
        background: var(--bg-light);
        padding: 10px;
        border-radius: 10px;
        margin-top: 10px;
    }

    .keywords-label {
        font-size: 11px;
        font-weight: 600;
        color: #636E72;
        margin-bottom: 5px;
    }

    .keywords-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .keyword-tag {
        background: var(--white);
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        color: var(--primary);
        border: 1px solid #E8E8F0;
    }

    .category-card-footer {
        padding: 15px 20px;
        background: var(--bg-light);
        display: flex;
        justify-content: center;
        gap: 10px;
        border-top: 1px solid #E8E8F0;
    }

    .category-button {
        border: none;
        background: #F0EDFF;
        color: var(--primary);
        padding: 8px 20px;
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

    .category-button:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .category-button-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .category-button-danger:hover {
        background: #dc2626;
        color: var(--white);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--white);
        border-radius: 20px;
    }

    .empty-state i {
        font-size: 64px;
        color: var(--primary);
        opacity: 0.5;
        margin-bottom: 20px;
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

    @media (max-width: 768px) {
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

        .categories-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .stats-row {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<div class="main-wrapper">
    <!-- Page Heading -->
    <div class="page_heading">
        Manage Categories
        <div class="totalcategoriesheading">
            Total Categories: <span><?= number_format($totalCategories) ?></span>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(isset($_GET['created'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> Category created successfully.
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-primary border-0 shadow-sm mb-4">
            <i class="bi bi-pencil-square me-2"></i> Category updated successfully.
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <i class="bi bi-trash me-2"></i> Category deleted successfully.
        </div>
    <?php endif; ?>

    <?= $message ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Categories</h6>
                        <h2 class="mb-0"><?= number_format($totalCategories) ?></h2>
                    </div>
                    <div class="icon-box blue"><i class="bi bi-tags"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Jobs</h6>
                        <h2 class="mb-0"><?= number_format($totalJobs) ?></h2>
                    </div>
                    <div class="icon-box green"><i class="bi bi-briefcase"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Subscribers</h6>
                        <h2 class="mb-0"><?= number_format($totalSubscribers) ?></h2>
                    </div>
                    <div class="icon-box orange"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Form -->
    <div class="form-card">
        <div class="card-header-custom">
            <h4><i class="bi bi-tag me-2"></i> <?= $editCategory ? 'Edit Category' : 'Create New Category' ?></h4>
        </div>
        <div class="card-body" style="padding: 20px;">
            <form method="POST">
                <?php if($editCategory): ?>
                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control search-input" required 
                               value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>"
                               placeholder="e.g., Software Development">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control search-input" required 
                               value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>"
                               placeholder="e.g., software-development">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Bootstrap Icon</label>
                        <input type="text" name="icon" class="form-control search-input" 
                               value="<?= htmlspecialchars($editCategory['icon'] ?? '') ?>"
                               placeholder="e.g., bi-laptop">
                        <small class="text-muted">Browse icons at <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Keywords (SEO)</label>
                        <input type="text" name="keywords" class="form-control search-input" 
                               value="<?= htmlspecialchars($editCategory['keywords'] ?? '') ?>"
                               placeholder="developer, software, programming">
                        <small class="text-muted">Comma separated keywords for better SEO</small>
                    </div>
                </div>
                
                <div class="mt-4 d-flex gap-2">
                    <?php if($editCategory): ?>
                        <button type="submit" name="update_category" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i> Update Category
                        </button>
                        <a href="categories.php" class="btn btn-light border px-4">
                            <i class="bi bi-x-circle me-2"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="create_category" class="btn btn-success px-4">
                            <i class="bi bi-plus-circle me-2"></i> Create Category
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="GET">
            <div class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control search-input" 
                           placeholder="🔍 Search categories by name, slug, or keywords..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 search-input">
                        <i class="bi bi-search me-2"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Categories Grid -->
    <?php if(count($categories) > 0): ?>
        <div class="categories-grid">
            <?php foreach($categories as $category): ?>
                <div class="category-card">
                    <div class="category-card-header">
                        <div class="category-icon-large">
                            <i class="bi <?= htmlspecialchars($category['icon'] ?: 'bi-folder') ?>"></i>
                        </div>
                        <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                        <div class="category-slug"><?= htmlspecialchars($category['slug']) ?></div>
                    </div>
                    
                    <div class="category-card-body">
                        <div class="stats-row">
                            <div class="stat-item">
                                <div class="stat-value"><?= number_format($category['total_jobs']) ?></div>
                                <div class="stat-label">Jobs</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= number_format($category['total_subscribers']) ?></div>
                                <div class="stat-label">Subscribers</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= date('d M Y', strtotime($category['created_at'])) ?></div>
                                <div class="stat-label">Created</div>
                            </div>
                        </div>
                        
                        <?php if(!empty($category['keywords'])): ?>
                            <div class="keywords-section">
                                <div class="keywords-label">Keywords</div>
                                <div class="keywords-tags">
                                    <?php 
                                    $keywords = explode(',', $category['keywords']);
                                    foreach($keywords as $keyword): 
                                        $keyword = trim($keyword);
                                        if(!empty($keyword)):
                                    ?>
                                        <span class="keyword-tag"><?= htmlspecialchars($keyword) ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="category-card-footer">
                        <a href="?edit=<?= $category['id'] ?>" class="category-button">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        <a href="?delete=<?= $category['id'] ?>" onclick="return confirm('Delete this category? This will not delete jobs in this category.')" class="category-button category-button-danger">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <h4>No categories found</h4>
            <p class="text-muted">No categories matched your search criteria. Try a different search term or create a new category.</p>
            <a href="categories.php" class="btn btn-primary mt-3">Clear Filters</a>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
        <ul class="pagination-list">
            <?php if($page > 1): ?>
                <li>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                <li class="<?= $i == $page ? 'active' : '' ?>">
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <li>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>

</body>
</html>