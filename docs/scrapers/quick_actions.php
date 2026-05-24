<?php
// =============================================================
// QUICK ACTIONS PAGE
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

// Get some stats for the page
$totalJobs = $db->query("SELECT COUNT(*) total FROM jobs")->fetch()['total'];
$totalSubscribers = $db->query("SELECT COUNT(*) total FROM subscribers")->fetch()['total'];
$totalEmails = $db->query("SELECT COUNT(*) total FROM email_logs")->fetch()['total'];
$pendingJobs = $db->query("SELECT COUNT(*) total FROM jobs WHERE is_active = 0")->fetch()['total'];
?>

<style>
    /* Page-specific styles for Quick Actions */
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

    .totalheading {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stats-card {
        background: var(--white);
        border-radius: 18px;
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

    /* Quick Actions Grid */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .action-card {
        background: var(--white);
        border: 1px solid #E8E8F0;
        border-radius: 20px;
        padding: 30px 25px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: block;
        position: relative;
        overflow: hidden;
    }

    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--gradient-1);
        transition: 0.4s;
        z-index: 0;
        opacity: 0;
    }

    .action-card:hover::before {
        left: 0;
        opacity: 0.05;
    }

    .action-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }

    .action-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        transition: 0.3s;
    }

    .action-card:hover .action-icon {
        transform: scale(1.1);
    }

    .action-icon i {
        font-size: 36px;
        color: white;
    }

    .action-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .action-desc {
        font-size: 13px;
        color: #636E72;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .action-stats {
        display: inline-block;
        background: var(--bg-light);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: var(--primary);
        position: relative;
        z-index: 1;
    }

    .action-card.success .action-icon { background: linear-gradient(135deg, #00B894, #00CEC9); }
    .action-card.warning .action-icon { background: linear-gradient(135deg, #FDCB6E, #FD79A8); }
    .action-card.dark .action-icon { background: linear-gradient(135deg, #2D3436, #636E72); }
    .action-card.primary .action-icon { background: var(--gradient-1); }
    .action-card.info .action-icon { background: linear-gradient(135deg, #3498db, #2980b9); }

    /* Recent Activity Section */
    .activity-card {
        background: var(--white);
        border-radius: 14px;
        margin-top: 30px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
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

    .activity-list {
        padding: 20px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #E8E8F0;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .activity-icon.success { background: #dcfce7; color: #166534; }
    .activity-icon.warning { background: #fef3c7; color: #92400e; }
    .activity-icon.info { background: #dbeafe; color: #1e40af; }
    .activity-icon.primary { background: #F0EDFF; color: var(--primary); }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
    }

    .activity-time {
        font-size: 12px;
        color: #636E72;
    }

    .btn-clear {
        background: none;
        border: none;
        color: var(--primary);
        cursor: pointer;
        font-size: 20px;
        transition: 0.3s;
    }

    .btn-clear:hover {
        color: var(--danger);
        transform: scale(1.1);
    }

    /* Welcome Section */
    .welcome-card {
        background: var(--gradient-1);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        color: var(--white);
        position: relative;
        overflow: hidden;
    }

    .welcome-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .welcome-card h2 {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .welcome-card p {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 0;
    }

    .welcome-date {
        font-size: 14px;
        opacity: 0.8;
        margin-top: 10px;
    }

    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
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

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .actions-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .welcome-card h2 {
            font-size: 22px;
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
        
        .action-icon {
            width: 60px;
            height: 60px;
        }
        
        .action-icon i {
            font-size: 28px;
        }
        
        .action-title {
            font-size: 18px;
        }
    }
</style>

<div class="main-wrapper">
    <!-- Welcome Section -->
    <div class="welcome-card">
        <h2><i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions Dashboard</h2>
        <p>Execute common tasks and manage your job aggregation system with one click</p>
        <div class="welcome-date">
            <i class="bi bi-calendar3 me-1"></i> <?= date('l, F j, Y') ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-info">
                <h6>Total Jobs</h6>
                <h2><?= number_format($totalJobs) ?></h2>
            </div>
            <div class="icon-box blue"><i class="bi bi-briefcase"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Subscribers</h6>
                <h2><?= number_format($totalSubscribers) ?></h2>
            </div>
            <div class="icon-box green"><i class="bi bi-people"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Emails Sent</h6>
                <h2><?= number_format($totalEmails) ?></h2>
            </div>
            <div class="icon-box orange"><i class="bi bi-envelope-check"></i></div>
        </div>
        <div class="stats-card">
            <div class="stats-info">
                <h6>Pending Jobs</h6>
                <h2><?= number_format($pendingJobs) ?></h2>
            </div>
            <div class="icon-box red"><i class="bi bi-clock-history"></i></div>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="actions-grid">
        <a href="<?= MY_BASE_URL ?>/scrapers/run_scraper.php" class="action-card success" onclick="showToast('Starting scraper...', 'info');">
            <div class="action-icon">
                <i class="bi bi-cloud-download"></i>
            </div>
            <div class="action-title">Run Scraper</div>
            <div class="action-desc">Manually trigger job scraping from all sources</div>
            <span class="action-stats"><i class="bi bi-arrow-repeat"></i> Instant</span>
        </a>

        <a href="<?= MY_BASE_URL ?>/scrapers/send_emails.php" class="action-card warning" onclick="showToast('Preparing to send emails...', 'info');">
            <div class="action-icon">
                <i class="bi bi-envelope-paper"></i>
            </div>
            <div class="action-title">Send Emails</div>
            <div class="action-desc">Send job alerts to all active subscribers</div>
            <span class="action-stats"><i class="bi bi-people"></i> <?= number_format($totalSubscribers) ?> subscribers</span>
        </a>

        <a href="<?= MY_BASE_URL ?>/scrapers/run_full_cron.php" class="action-card dark" onclick="showToast('Executing full cron job...', 'info');">
            <div class="action-icon">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="action-title">Run Full Cron</div>
            <div class="action-desc">Execute all scheduled tasks and maintenance</div>
            <span class="action-stats"><i class="bi bi-gear"></i> Complete sync</span>
        </a>

        <a href="<?= MY_BASE_URL ?>/manage/subscribe.php" class="action-card primary" onclick="showToast('Loading subscriber form...', 'info');">
            <div class="action-icon">
                <i class="bi bi-person-plus"></i>
            </div>
            <div class="action-title">Add Subscriber</div>
            <div class="action-desc">Manually add a new subscriber to the system</div>
            <span class="action-stats"><i class="bi bi-plus-circle"></i> New user</span>
        </a>

        <a href="<?= MY_BASE_URL ?>/manage/jobs.php" class="action-card info" onclick="showToast('Loading jobs manager...', 'info');">
            <div class="action-icon">
                <i class="bi bi-briefcase"></i>
            </div>
            <div class="action-title">Manage Jobs</div>
            <div class="action-desc">View, edit, and manage all job listings</div>
            <span class="action-stats"><i class="bi bi-files"></i> <?= number_format($totalJobs) ?> jobs</span>
        </a>

        <a href="<?= MY_BASE_URL ?>/manage/categories.php" class="action-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <div class="action-icon">
                <i class="bi bi-tags"></i>
            </div>
            <div class="action-title">Manage Categories</div>
            <div class="action-desc">Organize jobs by creating and editing categories</div>
            <span class="action-stats"><i class="bi bi-folder"></i> Categorize</span>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="activity-card">
        <div class="card-header-custom">
            <h4><i class="bi bi-clock-history me-2"></i> Recent Activity</h4>
        </div>
        <div class="activity-list" id="activityList">
            <div class="activity-item">
                <div class="activity-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">Last scraper run completed</div>
                    <div class="activity-time"><?= date('Y-m-d H:i:s', strtotime('-2 hours')) ?></div>
                </div>
                <button class="btn-clear" onclick="clearActivity(this)"><i class="bi bi-x-circle"></i></button>
            </div>
            <div class="activity-item">
                <div class="activity-icon warning">
                    <i class="bi bi-envelope"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">Weekly newsletter sent to <?= number_format($totalSubscribers) ?> subscribers</div>
                    <div class="activity-time"><?= date('Y-m-d H:i:s', strtotime('-1 day')) ?></div>
                </div>
                <button class="btn-clear" onclick="clearActivity(this)"><i class="bi bi-x-circle"></i></button>
            </div>
            <div class="activity-item">
                <div class="activity-icon info">
                    <i class="bi bi-person-plus"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">New subscriber joined</div>
                    <div class="activity-time"><?= date('Y-m-d H:i:s', strtotime('-3 days')) ?></div>
                </div>
                <button class="btn-clear" onclick="clearActivity(this)"><i class="bi bi-x-circle"></i></button>
            </div>
            <div class="activity-item">
                <div class="activity-icon primary">
                    <i class="bi bi-plus-circle"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title"><?= number_format($pendingJobs) ?> new jobs added</div>
                    <div class="activity-time"><?= date('Y-m-d H:i:s', strtotime('-4 days')) ?></div>
                </div>
                <button class="btn-clear" onclick="clearActivity(this)"><i class="bi bi-x-circle"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<style>
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
        max-width: 380px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 999999;
        transform: translateX(120%);
        opacity: 0;
        transition: all 0.4s ease;
        font-family: inherit;
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

    .toast-info {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    }

    .toast-notification .toast-icon i {
        font-size: 24px;
        color: #fff;
    }

    .toast-notification .toast-message {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
    }

    @media(max-width: 576px) {
        .toast-notification {
            top: 15px;
            left: 15px;
            right: 15px;
            min-width: auto;
            max-width: none;
        }
    }
</style>

<script>
    // Toast notification function
    function showToast(message, type = 'success') {
        // Remove existing toast
        const oldToast = document.querySelector('.toast-notification');
        if (oldToast) {
            oldToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification toast-' + type;
        
        let icon = 'check-circle-fill';
        if (type === 'error') icon = 'exclamation-circle-fill';
        if (type === 'info') icon = 'info-circle-fill';
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="bi bi-${icon}"></i>
            </div>
            <div class="toast-message">
                ${message}
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }
    
    // Clear activity item
    function clearActivity(btn) {
        const activityItem = btn.closest('.activity-item');
        activityItem.style.transition = 'all 0.3s';
        activityItem.style.opacity = '0';
        activityItem.style.transform = 'translateX(20px)';
        setTimeout(() => activityItem.remove(), 300);
        showToast('Activity cleared', 'success');
    }
    
    // Add click handlers to action cards
    document.querySelectorAll('.action-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't prevent default - let the link work
            // Just show toast for feedback
            const title = this.querySelector('.action-title')?.innerText || 'Action';
            showToast(`Loading ${title}...`, 'info');
        });
    });
    
    // Auto-hide any existing alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

</body>
</html>