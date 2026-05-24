<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| SUBSCRIBE HANDLER
|--------------------------------------------------------------------------
*/

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_now'])) {
    $fullName  = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $country   = trim($_POST['country'] ?? 'Uganda');
    $frequency = trim($_POST['frequency'] ?? 'Daily');

    $selectedCategories = $_POST['categories'] ?? [];

    // Validation
    if (empty($fullName) || empty($email) || empty($phone)) {
        $error = "Please fill in Full Name, Email and Phone Number.";
    } else {
        // Check existing subscriber
        $stmt = $db->prepare("SELECT id FROM subscribers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingSubscriber = $stmt->fetch();

        if ($existingSubscriber) {
            $subscriberId = $existingSubscriber['id'];
        } else {
            // Insert new subscriber
            $stmt = $db->prepare("
                INSERT INTO subscribers (email, full_name, phone, country, preferred_frequency, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$email, $fullName, $phone, $country, $frequency]);
            $subscriberId = $db->lastInsertId();
        }

        // Remove old categories
        $deleteStmt = $db->prepare("DELETE FROM subscriber_categories WHERE subscriber_id = ?");
        $deleteStmt->execute([$subscriberId]);

        // If none selected -> subscribe to all categories
        if (empty($selectedCategories)) {
            $allCategories = $db->query("SELECT id FROM job_categories")->fetchAll();
            foreach ($allCategories as $cat) {
                $insertCat = $db->prepare("INSERT IGNORE INTO subscriber_categories (subscriber_id, category_id) VALUES (?, ?)");
                $insertCat->execute([$subscriberId, $cat['id']]);
            }
        } else {
            foreach ($selectedCategories as $categoryId) {
                $insertCat = $db->prepare("INSERT IGNORE INTO subscriber_categories (subscriber_id, category_id) VALUES (?, ?)");
                $insertCat->execute([$subscriberId, $categoryId]);
            }
        }

        $success = "You have successfully subscribed to Job Finder Services.";
    }
}

// Fetch categories for the modal
$categories = $db->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Subscribe to Daily Jobs - Uganda Job Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
       
        /*
        |--------------------------------------------------------------------------
        | FLOATING SUBSCRIBE BUTTON
        |--------------------------------------------------------------------------
        */
        .floating-subscribe {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            color: white;
            font-size: 32px;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: pulse 2s infinite;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .floating-subscribe:hover {
            transform: scale(1.1);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }
            50% {
                transform: scale(1.08);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SMALL MODAL - FULLY VISIBLE, TWO COLUMN LAYOUT
        |--------------------------------------------------------------------------
        */
         .subscribe-modal .modal-content {
            border: none;
            border-radius: 24px;
            overflow: hidden;
        }
        
        .modal-header-custom {
            background: linear-gradient(135deg, #a08dcb, #00CEC9);
            color: white;
            padding: 15px 20px;
        }

        .modal-header-custom h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .modal-header-custom small {
            font-size: 12px;
            opacity: 0.9;
        }

        .modal-body {
            padding: 20px;
            max-height: calc(90vh - 120px);
            overflow-y: auto;
        }

        /* Custom scrollbar */
        .modal-body::-webkit-scrollbar {
            width: 5px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .form-control,
        .form-select {
            min-height: 42px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 12px;
            color: #2d3748;
        }

        /* Compact category grid - two columns */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            max-height: 160px;
            overflow-y: auto;
            padding: 4px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: #fafafa;
        }

        .category-item {
            background: white;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-item:hover {
            background: #f0f0ff;
            border-color: #667eea;
        }

        .category-item input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .category-item span {
            flex: 1;
            font-size: 12px;
            font-weight: 500;
            color: #2d3748;
        }

        .info-note {
            background: #fef5e7;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 11px;
            color: #d69e2e;
            margin-top: 10px;
        }

        .btn-subscribe {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            height: 45px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-subscribe:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        /* Left-Right layout using Bootstrap grid */
        .form-row-2col {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -8px;
        }
        
        .form-col {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 8px;
        }
        
        @media (max-width: 576px) {
            .form-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FLOATING ALERT / TOAST
        |--------------------------------------------------------------------------
        */
        .floating-alert {
            position: fixed;
            top: 25px;
            right: 25px;
            min-width: 320px;
            max-width: 400px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            z-index: 99999;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-header {
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-header strong {
            font-size: 14px;
        }

        .alert-header div div {
            font-size: 13px;
        }

        .alert-progress {
            height: 4px;
            background: #22c55e;
            width: 100%;
            animation: shrink 5s linear forwards;
        }

        .alert-progress.error {
            background: #ef4444;
        }

        @keyframes shrink {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .floating-alert {
                width: 90%;
                right: 5%;
                min-width: auto;
                left: 5%;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
                max-height: 180px;
            }
            
            .floating-subscribe {
                width: 60px;
                height: 60px;
                font-size: 24px;
                bottom: 20px;
                right: 20px;
            }

            .modal-body {
                padding: 15px;
            }

            .modal-header-custom h3 {
                font-size: 16px;
            }

            .modal-dialog {
                margin: 0.5rem;
            }
        }

        /* Ensure modal is centered and fully visible */
        .modal {
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-dialog {
            max-width: 550px;
            width: 95%;
            margin: 1rem auto;
        }

        @media (min-width: 576px) {
            .modal-dialog {
                max-width: 520px;
                margin: 1.75rem auto;
            }
        }
        
        /* Two column layout spacing */
        .two-column-layout {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .two-column-layout > div {
            flex: 1;
            padding: 0 10px;
            min-width: 0;
        }
        
        @media (max-width: 550px) {
            .two-column-layout {
                flex-direction: column;
            }
            .two-column-layout > div {
                padding: 0;
            }
        }
    </style>
</head>
<body>


<!-- Success/Error Toast Messages -->
<?php if ($success): ?>
    <div class="floating-alert" id="successAlert">
        <div class="alert-header">
            <div>
                <strong class="text-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Success
                </strong>
                <div class="mt-1"><?= htmlspecialchars($success) ?></div>
            </div>
            <button class="btn-close btn-sm" onclick="closeAlert()"></button>
        </div>
        <div class="alert-progress"></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="floating-alert" id="successAlert">
        <div class="alert-header">
            <div>
                <strong class="text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error
                </strong>
                <div class="mt-1"><?= htmlspecialchars($error) ?></div>
            </div>
            <button class="btn-close btn-sm" onclick="closeAlert()"></button>
        </div>
        <div class="alert-progress error"></div>
    </div>
<?php endif; ?>

<!-- FLOATING SUBSCRIBE BUTTON -->
<button class="floating-subscribe" data-bs-toggle="modal" data-bs-target="#subscribeModal">
    <i class="bi bi-bell-fill"></i>
</button>

<!-- SUBSCRIBE MODAL - TWO COLUMN LAYOUT (LEFT-RIGHT) -->
<div class="modal fade" id="subscribeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content subscribe-modal">
            <div class="modal-header-custom">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <h3><i class="bi bi-envelope-paper-fill me-2"></i> Subscribe To Daily Jobs</h3>
                        <small>Get latest Uganda jobs instantly</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="subscribe_now" value="1">
                <div class="modal-body">
                    <!-- TWO COLUMN LAYOUT - LEFT and RIGHT -->
                    <div class="two-column-layout">
                        <!-- LEFT COLUMN -->
                        <div>
                            <!-- Full Name -->
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required placeholder="Enter your full name">
                            </div>
                            
                            <!-- Phone -->
                            <div class="mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="text" name="phone" class="form-control" required placeholder="+256 XXX XXX XXX">
                            </div>
                            
                            <!-- Email Frequency -->
                            <div class="mb-3">
                                <label class="form-label">Email Frequency</label>
                                <select name="frequency" class="form-select">
                                    <option value="Daily">Daily</option>
                                    <option value="Instant">Instant</option>
                                    <option value="Weekly">Weekly</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- RIGHT COLUMN -->
                        <div>
                            <!-- Email Address -->
                            <div class="mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required placeholder="your@email.com">
                            </div>
                            
                            <!-- Country -->
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <select name="country" class="form-select">
                                    <option value="Uganda">Uganda</option>
                                    <option value="Kenya">Kenya</option>
                                    <option value="Tanzania">Tanzania</option>
                                    <option value="Rwanda">Rwanda</option>
                                    <option value="South Sudan">South Sudan</option>
                                </select>
                            </div>
                            
                            <!-- Placeholder for alignment - empty div to maintain height balance -->
                            <div style="visibility: hidden; height: 0;"></div>
                        </div>
                    </div>
                    
                    <!-- CATEGORIES SECTION - FULL WIDTH -->
                    <div class="mt-2 mb-2">
                        <label class="form-label">Select Categories (Optional)</label>
                        <div class="category-grid">
                            <?php foreach ($categories as $category): ?>
                                <label class="category-item">
                                    <input type="checkbox" name="categories[]" value="<?= $category['id'] ?>">
                                    <span><?= htmlspecialchars($category['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="info-note">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            If you don't select categories, all categories will automatically be subscribed.
                        </div>
                    </div>
                    
                    <!-- SUBSCRIBE BUTTON - FULL WIDTH -->
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100 btn-subscribe">
                            <i class="bi bi-bell-fill me-2"></i>
                            Subscribe Now
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Close alert function
    function closeAlert() {
        let alertBox = document.getElementById('successAlert');
        if (alertBox) {
            alertBox.remove();
        }
    }

    // Auto close alert after 5 seconds
    setTimeout(() => {
        closeAlert();
    }, 5000);

    // Close modal after successful subscription
    <?php if ($success): ?>
        setTimeout(() => {
            let modalElement = document.getElementById('subscribeModal');
            let modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            // Remove backdrop if stuck
            setTimeout(() => {
                let backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            }, 300);
        }, 2500);
    <?php endif; ?>
</script>
</body>
</html>