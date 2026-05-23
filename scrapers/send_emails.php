<?php

require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../core/functions.php';

$db = Database::connect();

echo "<h2>Sending Job Emails...</h2>";

/*
|--------------------------------------------------------------------------
| GET ACTIVE SUBSCRIBERS
|--------------------------------------------------------------------------
*/

$subscribers = $db->query("
    SELECT *
    FROM subscribers
    WHERE is_active = 1
")->fetchAll();

if (!$subscribers) {
    echo "No subscribers found.";
    exit;
}

foreach ($subscribers as $subscriber) {
    echo "Processing: " . $subscriber['email'] . "<br>";

    /*
    |--------------------------------------------------------------------------
    | GET USER CATEGORIES
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT category_id
        FROM subscriber_categories
        WHERE subscriber_id = ?
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$subscriber['id']]);
    $categoryIds = array_column($stmt->fetchAll(), 'category_id');

    if (empty($categoryIds)) {
        echo "No categories selected.<br>";
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | FETCH JOBS
    |--------------------------------------------------------------------------
    */

    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $sql = "
        SELECT *
        FROM jobs
        WHERE category_id IN ($placeholders)
        ORDER BY id DESC
        LIMIT 10
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($categoryIds);
    $jobs = $stmt->fetchAll();

    if (!$jobs) {
        echo "No jobs found.<br>";
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | GET JOB COUNT FOR SUBJECT LINE
    |--------------------------------------------------------------------------
    */
    
    // Get total count of jobs (not limited by LIMIT 10)
    $countSql = "
        SELECT COUNT(*) as total
        FROM jobs
        WHERE category_id IN ($placeholders)
    ";
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($categoryIds);
    $totalJobCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Alternative: If you want the count of jobs being sent (up to 10)
    $jobsSentCount = count($jobs);
    
    /*
    |--------------------------------------------------------------------------
    | BUILD EMAIL WITH DYNAMIC SUBJECT
    |--------------------------------------------------------------------------
    */

    // Option 1: Show total jobs available in database
    $subject = "($totalJobCount Jobs Found Today) Latest Job Opportunities - BISure Jobs";
    
    // Option 2: Show jobs being sent in this email (uncomment below and comment Option 1)
    // $subject = "($jobsSentCount New Jobs) Latest Job Opportunities - BISure Jobs";
    
    // Option 3: Show both (uncomment below and comment Option 1)
    // $subject = "($jobsSentCount of $totalJobCount Jobs) Latest Job Opportunities - BISure Jobs";

    $body = buildStyledJobsEmailTemplate($jobs);

    /*
    |--------------------------------------------------------------------------
    | SEND EMAIL
    |--------------------------------------------------------------------------
    */

    $sent = sendJobEmail($subscriber['email'], $subject, $body);

    /*
    |--------------------------------------------------------------------------
    | SAVE EMAIL LOG
    |--------------------------------------------------------------------------
    */

    $status = $sent ? 'Sent' : 'Failed';
    $sql = "
        INSERT INTO email_logs (
            subscriber_id,
            subject,
            body,
            status,
            sent_at
        )
        VALUES (?, ?, ?, ?, NOW())
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$subscriber['id'], $subject, $body, $status]);

    echo $sent ? "Email sent successfully.<br>" : "Email failed.<br>";
}

echo "<br>Done. <a href='/jobaggregator/'>Dashboard</a>";

/*
|--------------------------------------------------------------------------
| FUNCTION: buildStyledJobsEmailTemplate
|--------------------------------------------------------------------------
*/
function buildStyledJobsEmailTemplate($jobs) {
    
    $jobCount = count($jobs);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Latest Job Opportunities</title>
        <style>
            /* Global Styles */
            body {
                margin: 0;
                padding: 0;
                background-color: #F8F9FE;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #2D3436;
            }
            
            .email-container {
                max-width: 650px;
                margin: 0 auto;
                background-color: #F8F9FE;
                padding: 20px;
            }
            
            /* Header */
            .email-header {
                background: linear-gradient(135deg, #6C5CE7 0%, #00CEC9 100%);
                padding: 20px 10px;
                text-align: center;
                border-radius: 14px 14px 0 0;
            }
            
            .email-header h1 {
                color: #FFFFFF;
                margin: 0;
                font-size: 18px;
                font-weight: 500;
            }
            
            .email-header p {
                color: rgba(255,255,255,0.9);
                margin: 8px 0 0;
                font-size: 12px;
            }
            
            /* Job Card */
            .job-card {
                background: #FFFFFF;
                border-radius: 14px;
                margin-bottom: 20px;
                overflow: hidden;
                border: 1px solid #E8E8F0;
                box-shadow: 0 2px 10px rgba(108, 92, 231, 0.1);
                transition: all 0.3s ease;
            }
            
            .job-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(108, 92, 231, 0.15);
            }
            
            .job-content {
                padding: 20px;
            }
            
            .job-number {
                display: inline-block;
                background: linear-gradient(135deg, #6C5CE7 0%, #00CEC9 100%);
                color: white;
                width: 32px;
                height: 32px;
                border-radius: 10px;
                text-align: center;
                line-height: 32px;
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 12px;
            }
            
            .job-title {
                font-size: 18px;
                font-weight: 700;
                margin: 0 0 8px 0;
                color: #2D3436;
            }
            
            .job-title a {
                color: #2D3436;
                text-decoration: none;
                background: linear-gradient(135deg, #6C5CE7 0%, #00CEC9 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .job-title a:hover {
                background: linear-gradient(135deg, #FD79A8 0%, #FDCB6E 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .job-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin: 12px 0;
                font-size: 12px;
            }
            
            .job-category {
                background: #F0EDFF;
                color: #6C5CE7;
                padding: 4px 10px;
                border-radius: 8px;
                font-weight: 600;
            }
            
            .job-date {
                background: #E8F8F5;
                color: #00B894;
                padding: 4px 10px;
                border-radius: 8px;
                font-weight: 600;
            }
            
            .job-company {
                background: #FFF3E0;
                color: #FDCB6E;
                padding: 4px 10px;
                border-radius: 8px;
                font-weight: 600;
            }
            
            .job-description {
                font-size: 13px;
                line-height: 1.5;
                color: #636E72;
                margin: 12px 0;
            }
            
            /* Button Styles */
            .btn-view {
                display: inline-block;
                background: linear-gradient(135deg, #6C5CE7 0%, #00CEC9 100%);
                color: white;
                text-decoration: none;
                padding: 10px 24px;
                border-radius: 25px;
                font-weight: 600;
                font-size: 13px;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                text-align: center;
            }
            
            .btn-view:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
            }
            
            /* Floating Button */
            .floating-btn {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: linear-gradient(135deg, #FD79A8 0%, #FDCB6E 100%);
                color: white;
                padding: 14px 24px;
                border-radius: 50px;
                text-decoration: none;
                font-weight: 700;
                font-size: 14px;
                box-shadow: 0 5px 20px rgba(253, 121, 168, 0.4);
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                z-index: 1000;
            }
            
            .floating-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(253, 121, 168, 0.5);
            }
            
            /* Footer */
            .email-footer {
                background: #2D3436;
                padding: 20px;
                text-align: center;
                border-radius: 0 0 14px 14px;
                margin-top: 20px;
            }
            
            .email-footer p {
                color: #B2BEC3;
                font-size: 11px;
                margin: 5px 0;
            }
            
            .email-footer a {
                color: #6C5CE7;
                text-decoration: none;
            }
            
            hr {
                border: none;
                height: 2px;
                background: linear-gradient(135deg, #6C5CE7 0%, #00CEC9 100%);
                margin: 20px 0;
            }
            
            @media only screen and (max-width: 600px) {
                .email-container {
                    padding: 10px;
                }
                .job-content {
                    padding: 15px;
                }
                .job-title {
                    font-size: 16px;
                }
                .floating-btn {
                    bottom: 20px;
                    right: 20px;
                    padding: 10px 18px;
                    font-size: 12px;
                }
                .btn-view {
                    display: block;
                    text-align: center;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            
            <!-- Header -->
            <div class="email-header">
                <h1>🎯 Latest Job Opportunities</h1>
                <p>Your Daily curated list of top jobs (' . $jobCount . ' new opportunities)</p>
            </div>
            
            <!-- Job Listings -->
            <div style="padding: 20px 0;">
                <p style="font-size: 14px; color: #636E72; margin-bottom: 20px;">
                    Hello,<br>
                    Here are the latest job opportunities matching your preferences:
                </p>';
    
    // Loop through jobs with numbering
    $counter = 1;
    foreach ($jobs as $job) {
        $jobTitle = htmlspecialchars($job['title']);
        $companyName = htmlspecialchars($job['company_name']);
        $applyUrl = htmlspecialchars($job['apply_url']);
        
        $html .= '
                <div class="job-card">
                    <div class="job-content">
                        <div class="job-number">' . $counter . '</div>
                        <h2 class="job-title">
                            <a href="' . $applyUrl . '">' . $jobTitle . '</a>
                        </h2>
                        <div class="job-meta">
                            <span class="job-company">🏢 ' . $companyName . '</span>
                        </div>
                        <a href="' . $applyUrl . '" class="btn-view">
                            🔍 View Details & Apply
                        </a>
                    </div>
                </div>';
        $counter++;
    }
    
    $html .= '
                <hr>

                <!-- Floating All Jobs Button -->
                <a href="https://bisurejobs.22web.org/feed_landing_all.php" 
                class="floating-btn" 
                target="_blank">
                    📋 View All Jobs
                </a>

                <div style="text-align: center; padding: 20px 0;">
                    <p style="font-size: 13px; color: #636E72;">
                        Don\'t miss out on more opportunities!
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <p>© ' . date('Y') . ' BISure Jobs. All rights reserved.</p>
                <p>You\'re receiving this email because you subscribed to job alerts.</p>
                <p><a href="https://bisurejobs.22web.org/unsubscribe.php">Unsubscribe</a> | 
                   <a href="https://bisurejobs.22web.org/manage/subscribe.php">Manage Preferences</a></p>
            </div>
        </div>
        
    </body>
    </html>';
    
    return $html;
}
?>