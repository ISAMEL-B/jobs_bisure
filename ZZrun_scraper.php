<?php

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