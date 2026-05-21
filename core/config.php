<?php

// =========================
// DATABASE SETTINGS
// =========================

define('DB_HOST', 'localhost');
define('DB_NAME', 'job_aggregator');
define('DB_USER', 'root');
define('DB_PASS', '');

// =========================
// SITE SETTINGS
// =========================

define('SITE_NAME', 'Uganda Job Aggregator');

define('BASE_URL', 'http://localhost/mail_jobs');

// =========================
// EMAIL SETTINGS
// =========================

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);

define('MAIL_USERNAME', 'byaruhangaisamelk@gmail.com');
define('MAIL_PASSWORD', 'txhu xuhy hzbf oaps');

define('MAIL_FROM_EMAIL', 'byaruhangaisamelk@gmail.com');
define('MAIL_FROM_NAME', SITE_NAME);

// =========================
// LOG SETTINGS
// =========================

define('ERROR_LOG_FILE', __DIR__ . '/../logs/errors.log');

define('SCRAPER_LOG_FILE', __DIR__ . '/../logs/scraper.log');

// =========================
// SCRAPER SETTINGS
// =========================

define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

define('SCRAPER_TIMEOUT', 30);

// =========================
// TIMEZONE
// =========================

date_default_timezone_set('Africa/Kampala');