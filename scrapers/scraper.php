<?php

require_once __DIR__ . '/../core/functions.php';

echo "<h2>Starting JobAdverts Scraper...</h2>";

writeLog(
    SCRAPER_LOG_FILE,
    "==============================="
);

writeLog(
    SCRAPER_LOG_FILE,
    "MASTER SCRAPER STARTED"
);

$db = Database::connect();

$sourceId = 1;

/*
|--------------------------------------------------------------------------
| SCRAPE MULTIPLE PAGES
|--------------------------------------------------------------------------
*/

$maxPages = 5;

$totalFound = 0;
$totalInserted = 0;

for ($page = 1; $page <= $maxPages; $page++) {

    echo "Scraping page {$page}...<br>";

    /*
    |--------------------------------------------------------------------------
    | BUILD URL
    |--------------------------------------------------------------------------
    */

    $url = ($page == 1)
        ? 'https://www.jobadverts.ug/jobs/'
        : "https://www.jobadverts.ug/jobs/page/{$page}/";

    /*
    |--------------------------------------------------------------------------
    | FETCH HTML
    |--------------------------------------------------------------------------
    */

    $html = fetchURL($url);

    if (!$html) {

        writeLog(
            ERROR_LOG_FILE,
            "Failed fetching page {$page}"
        );

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD HTML
    |--------------------------------------------------------------------------
    */

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();

    $dom->loadHTML($html);

    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    /*
    |--------------------------------------------------------------------------
    | GET JOB LISTINGS
    |--------------------------------------------------------------------------
    */

    $jobNodes = $xpath->query(
        '//li[contains(@class, "job_listing")]'
    );

    if (!$jobNodes || $jobNodes->length == 0) {

        writeLog(
            ERROR_LOG_FILE,
            "No jobs found on page {$page}"
        );

        echo "No jobs found on page {$page}<br>";

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | LOOP JOBS
    |--------------------------------------------------------------------------
    */

    foreach ($jobNodes as $jobNode) {

        try {

            /*
            |--------------------------------------------------------------------------
            | TITLE
            |--------------------------------------------------------------------------
            */

            $titleNodes = $xpath->query(
                './/h3[@class="job-listing-loop-job__title"]',
                $jobNode
            );

            if ($titleNodes->length == 0) {
                continue;
            }

            $title = cleanText(
                $titleNodes->item(0)->textContent
            );

            /*
            |--------------------------------------------------------------------------
            | JOB LINK
            |--------------------------------------------------------------------------
            */

            $linkNodes = $xpath->query(
                './/a[@href]',
                $jobNode
            );

            if ($linkNodes->length == 0) {
                continue;
            }

            $jobUrl = $linkNodes->item(0)
                                ->getAttribute('href');

            if (strpos($jobUrl, 'http') !== 0) {

                $jobUrl = 'https://www.jobadverts.ug' . $jobUrl;
            }

            /*
            |--------------------------------------------------------------------------
            | COMPANY
            |--------------------------------------------------------------------------
            */

            $company = 'The Company';

            if (preg_match('/ at (.+)$/i', $title, $matches)) {

                $company = trim($matches[1]);

            } elseif (preg_match('/ - (.+)$/i', $title, $matches)) {

                $company = trim($matches[1]);
            }

            /*
            |--------------------------------------------------------------------------
            | DATE
            |--------------------------------------------------------------------------
            */

            $postedDate = date('Y-m-d H:i:s');

            $dateNodes = $xpath->query(
                './/span[@class="job-published-date"]/time',
                $jobNode
            );

            if ($dateNodes->length > 0) {

                $dateText = cleanText(
                    $dateNodes->item(0)->textContent
                );

                $timestamp = strtotime($dateText);

                if ($timestamp) {

                    $postedDate = date(
                        'Y-m-d H:i:s',
                        $timestamp
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | LOCATION
            |--------------------------------------------------------------------------
            */

            $location = 'Uganda';

            $locationNodes = $xpath->query(
                './/*[contains(@class,"location")]',
                $jobNode
            );

            if ($locationNodes->length > 0) {

                $location = cleanText(
                    $locationNodes->item(0)->textContent
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DESCRIPTION
            |--------------------------------------------------------------------------
            */

            $description = '';

            $descNodes = $xpath->query(
                './/p',
                $jobNode
            );

            if ($descNodes->length > 0) {

                $description = cleanText(
                    $descNodes->item(0)->textContent
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE JOB
            |--------------------------------------------------------------------------
            */

            $jobData = [

                'source_id' => $sourceId,

                'title' => $title,

                'company_name' => $company,

                'location' => $location,

                'description' => $description,

                'apply_url' => $jobUrl,

                'original_url' => $jobUrl,

                'posted_date' => $postedDate,

                'deadline_date' => null
            ];

            $saved = saveJob($jobData);

            $totalFound++;

            if ($saved) {

                $totalInserted++;

                echo "Inserted: {$title}<br>";

                writeLog(
                    SCRAPER_LOG_FILE,
                    "Inserted: {$title}"
                );

            } else {

                echo "Duplicate skipped: {$title}<br>";

                writeLog(
                    SCRAPER_LOG_FILE,
                    "Duplicate skipped: {$title}"
                );
            }

        } catch (Exception $e) {

            writeLog(
                ERROR_LOG_FILE,
                "SCRAPER ERROR: " . $e->getMessage()
            );

            echo "Error: " . $e->getMessage() . "<br>";
        }
    }
}

/*
|--------------------------------------------------------------------------
| FINAL RESULTS
|--------------------------------------------------------------------------
*/

echo "<hr>";

echo "<strong>Total Found:</strong> {$totalFound}<br>";

echo "<strong>Total Inserted:</strong> {$totalInserted}<br>";

writeLog(
    SCRAPER_LOG_FILE,
    "Finished scraping. Found: {$totalFound}, Inserted: {$totalInserted}"
);

//-----------------------------------------
writeLog(
    SCRAPER_LOG_FILE,
    "MASTER SCRAPER FINISHED"
);

echo "All scrapers completed successfully.";