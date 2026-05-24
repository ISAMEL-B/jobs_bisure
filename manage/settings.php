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
require_once __DIR__ . '/../bars/head_nav.php';
?>

<?php
require_once __DIR__ . '/../core/database.php';

$db = Database::connect();

$message = "";

/*
|--------------------------------------------------------------------------
| CREATE SETTINGS TABLE
|--------------------------------------------------------------------------
*/

$db->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

/*
|--------------------------------------------------------------------------
| DEFAULT SETTINGS
|--------------------------------------------------------------------------
*/

$defaultSettings = [
    'site_name' => 'Uganda Job Aggregator',
    'admin_email' => 'admin@example.com',
    'jobs_per_email' => '20',
    'scraper_pages' => '3',
    'email_enabled' => '1',
    'scraper_enabled' => '1',
    'maintenance_mode' => '0',
    'default_country' => 'Uganda'
];

/*
|--------------------------------------------------------------------------
| INSERT DEFAULT SETTINGS
|--------------------------------------------------------------------------
*/

foreach ($defaultSettings as $key => $value) {
    $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
}

/*
|--------------------------------------------------------------------------
| SAVE SETTINGS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([trim($value), trim($key)]);
    }
    
    $message = "<div class='alert alert-success border-0 shadow-sm mb-4'>
                    <i class='bi bi-check-circle-fill me-2'></i>
                    Settings updated successfully.
                </div>";
}

/*
|--------------------------------------------------------------------------
| GET SETTINGS
|--------------------------------------------------------------------------
*/

$settings = [];
$rows = $db->query("SELECT * FROM settings")->fetchAll();

foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

/*
|--------------------------------------------------------------------------
| DASHBOARD STATS
|--------------------------------------------------------------------------
*/

$totalJobs = $db->query("SELECT COUNT(*) total FROM jobs")->fetch()['total'];
$totalSubscribers = $db->query("SELECT COUNT(*) total FROM subscribers")->fetch()['total'];
$totalEmails = $db->query("SELECT COUNT(*) total FROM email_logs")->fetch()['total'];
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

    /* Settings Card */
    .settings-card {
        background: var(--white);
        border-radius: 14px;
        margin-bottom: 25px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    .settings-card:hover {
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

    /* Form Controls */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-label i {
        color: var(--primary);
    }

    .form-control-custom {
        border: 2px solid #E8E8F0;
        border-radius: 10px;
        padding: 12px 15px;
        transition: 0.3s;
        width: 100%;
    }

    .form-control-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        outline: none;
    }

    select.form-control-custom {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236C5CE7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
    }

    /* Toggle Switch for Better UX */
    .toggle-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--bg-light);
        padding: 15px;
        border-radius: 12px;
        border: 1px solid #E8E8F0;
    }

    .toggle-label {
        font-weight: 600;
        color: var(--dark);
    }

    .toggle-label small {
        font-weight: normal;
        color: #636E72;
        font-size: 12px;
        display: block;
        margin-top: 4px;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 34px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background-color: var(--primary);
    }

    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }

    /* Save Button */
    .save-btn {
        background: var(--gradient-1);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 16px;
        transition: 0.3s;
        width: 100%;
    }

    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

</style>

<div class="main-wrapper">
    <!-- Page Heading -->
    <div class="page_heading">
        System Settings
        <div class="totalcategoriesheading">
            <i class="bi bi-gear-fill me-2"></i> Configuration Panel
        </div>
    </div>

    <!-- Alert Messages -->
    <?= $message ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Jobs</h6>
                        <h2 class="mb-0"><?= number_format($totalJobs) ?></h2>
                    </div>
                    <div class="icon-box blue"><i class="bi bi-briefcase"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Subscribers</h6>
                        <h2 class="mb-0"><?= number_format($totalSubscribers) ?></h2>
                    </div>
                    <div class="icon-box green"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-3">
            <div class="card stats-card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Emails Sent</h6>
                        <h2 class="mb-0"><?= number_format($totalEmails) ?></h2>
                    </div>
                    <div class="icon-box orange"><i class="bi bi-envelope-check"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="settings-card">
        <div class="card-header-custom">
            <h4><i class="bi bi-sliders2 me-2"></i> System Configuration</h4>
        </div>
        <div class="card-body" style="padding: 25px;">
            <form method="POST">
                <div class="row">
                    <!-- Site Name -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-globe2"></i> Site Name
                            </label>
                            <input type="text" name="site_name" class="form-control-custom" 
                                   value="<?= htmlspecialchars($settings['site_name']) ?>"
                                   placeholder="Enter your site name">
                        </div>
                    </div>

                    <!-- Admin Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-envelope"></i> Admin Email
                            </label>
                            <input type="email" name="admin_email" class="form-control-custom" 
                                   value="<?= htmlspecialchars($settings['admin_email']) ?>"
                                   placeholder="admin@example.com">
                        </div>
                    </div>

                    <!-- Jobs Per Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-envelope-paper"></i> Jobs Per Email
                            </label>
                            <input type="number" name="jobs_per_email" class="form-control-custom" 
                                   value="<?= htmlspecialchars($settings['jobs_per_email']) ?>"
                                   placeholder="Number of jobs to send per email">
                            <small class="text-muted">How many jobs to include in each newsletter</small>
                        </div>
                    </div>

                    <!-- Scraper Pages -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-files"></i> Scraper Pages
                            </label>
                            <input type="number" name="scraper_pages" class="form-control-custom" 
                                   value="<?= htmlspecialchars($settings['scraper_pages']) ?>"
                                   placeholder="Number of pages to scrape">
                            <small class="text-muted">How many pages to scrape from each source</small>
                        </div>
                    </div>

                    <!-- Default Country -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-geo-alt"></i> Default Country
                            </label>
                            <input type="text" name="default_country" class="form-control-custom" 
                                   value="<?= htmlspecialchars($settings['default_country']) ?>"
                                   placeholder="e.g., Uganda">
                        </div>
                    </div>

                    <!-- Email System Toggle -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-envelope-check"></i> Email System
                            </label>
                            <div class="toggle-group">
                                <div class="toggle-label">
                                    Email Notifications
                                    <small>Send job alerts to subscribers</small>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="email_enabled" value="1" 
                                           <?= $settings['email_enabled'] == 1 ? 'checked' : '' ?>
                                           onchange="this.value = this.checked ? '1' : '0'; this.form.submit();">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Scraper System Toggle -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-cloud-download"></i> Scraper System
                            </label>
                            <div class="toggle-group">
                                <div class="toggle-label">
                                    Auto Scraping
                                    <small>Automatically scrape jobs from sources</small>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="scraper_enabled" value="1" 
                                           <?= $settings['scraper_enabled'] == 1 ? 'checked' : '' ?>
                                           onchange="this.value = this.checked ? '1' : '0'; this.form.submit();">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Mode Toggle -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-shield-shaded"></i> Maintenance Mode
                            </label>
                            <div class="toggle-group">
                                <div class="toggle-label">
                                    Site Maintenance
                                    <small>Put site under maintenance</small>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="maintenance_mode" value="1" 
                                           <?= $settings['maintenance_mode'] == 1 ? 'checked' : '' ?>
                                           onchange="this.value = this.checked ? '1' : '0'; this.form.submit();">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mt-4">
                    <button type="submit" class="save-btn">
                        <i class="bi bi-save me-2"></i> Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Card -->
    <div class="settings-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #2D3436, #636E72);">
            <h4><i class="bi bi-info-circle me-2"></i> System Information</h4>
        </div>
        <div class="card-body" style="padding: 20px;">
            <div class="row">
                <div class="col-md-6">
                    <div class="user-field" style="display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #E8E8F0;">
                        <span><i class="bi bi-hdd-stack"></i> PHP Version:</span>
                        <strong><?= phpversion() ?></strong>
                    </div>
                    <div class="user-field" style="display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #E8E8F0;">
                        <span><i class="bi bi-database"></i> Database:</span>
                        <strong>MySQL / MariaDB</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="user-field" style="display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #E8E8F0;">
                        <span><i class="bi bi-clock"></i> Server Time:</span>
                        <strong><?= date('Y-m-d H:i:s') ?></strong>
                    </div>
                    <div class="user-field" style="display: flex; justify-content: space-between; padding: 12px;">
                        <span><i class="bi bi-browser-chrome"></i> Environment:</span>
                        <strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bars/footer.php'; ?>

<script>
// Auto-submit when toggles are changed
document.querySelectorAll('.toggle-switch input').forEach(toggle => {
    toggle.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>

</body>
</html>