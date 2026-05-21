<?php

session_start();

/*
|--------------------------------------------------------------------------
| AUTHENTICATION CHECK
|--------------------------------------------------------------------------
|
| Redirect users who are not logged in
|
*/

if (
    !isset($_SESSION['user_id'])
    ||
    empty($_SESSION['user_id'])
) {

    header("Location: /jobaggregator/security/signin.php");

    exit;
}
;
require_once __DIR__ . '/core/functions.php';

writeLog(
    SCRAPER_LOG_FILE,
    "==============================="
);

writeLog(
    SCRAPER_LOG_FILE,
    "MASTER SCRAPER STARTED"
);

/*
|--------------------------------------------------------------------------
| RUN SCRAPERS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/scrapers/jobadverts.php';

writeLog(
    SCRAPER_LOG_FILE,
    "MASTER SCRAPER FINISHED"
);

echo "All scrapers completed successfully.";