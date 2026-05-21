<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/categories.php';

function cleanText($text)
{
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim($text);

    return $text;
}

function generateSlug($text)
{
    $text = strtolower($text);

    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    $text = trim($text, '-');

    return $text;
}

function writeLog($file, $message)
{
    $date = date('Y-m-d H:i:s');

    $log = "[" . $date . "] " . $message . PHP_EOL;

    file_put_contents($file, $log, FILE_APPEND);
}

function fetchURL($url)
{
    $ch = curl_init();

    curl_setopt_array($ch, [

        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => SCRAPER_TIMEOUT,
        CURLOPT_USERAGENT => USER_AGENT,
        CURLOPT_SSL_VERIFYPEER => false,

    ]);

    $response = curl_exec($ch);

    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {

        writeLog(ERROR_LOG_FILE, "CURL ERROR: " . $error);

        return false;
    }

    return $response;
}

function detectCategory($title, $description = '')
{
    global $JOB_CATEGORIES;

    $text = strtolower($title . ' ' . $description);

    foreach ($JOB_CATEGORIES as $category => $keywords) {

        foreach ($keywords as $keyword) {

            if (strpos($text, strtolower($keyword)) !== false) {

                return $category;
            }
        }
    }

    return 'Other';
}

function getCategoryId($categoryName)
{
    $db = Database::connect();

    $sql = "SELECT id FROM job_categories WHERE name = ? LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->execute([$categoryName]);

    $row = $stmt->fetch();

    return $row ? $row['id'] : null;
}

function generateJobHash($title, $company, $location)
{
    return md5(strtolower($title . $company . $location));
}

function jobExists($hash)
{
    $db = Database::connect();

    $sql = "SELECT id FROM jobs WHERE hash_value = ? LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->execute([$hash]);

    return $stmt->fetch() ? true : false;
}

function saveJob($data)
{
    $db = Database::connect();

    $hash = generateJobHash(
        $data['title'],
        $data['company_name'],
        $data['location']
    );

    if (jobExists($hash)) {

        return false;
    }

    $categoryName = detectCategory(
        $data['title'],
        $data['description']
    );

    $categoryId = getCategoryId($categoryName);

    $sql = "INSERT INTO jobs (

        source_id,
        category_id,
        title,
        slug,
        company_name,
        location,
        description,
        apply_url,
        original_url,
        posted_date,
        deadline_date,
        hash_value

    ) VALUES (

        :source_id,
        :category_id,
        :title,
        :slug,
        :company_name,
        :location,
        :description,
        :apply_url,
        :original_url,
        :posted_date,
        :deadline_date,
        :hash_value

    )";

    $stmt = $db->prepare($sql);

    return $stmt->execute([

        ':source_id' => $data['source_id'],
        ':category_id' => $categoryId,
        ':title' => cleanText($data['title']),
        ':slug' => generateSlug($data['title']),
        ':company_name' => cleanText($data['company_name']),
        ':location' => cleanText($data['location']),
        ':description' => cleanText($data['description']),
        ':apply_url' => $data['apply_url'],
        ':original_url' => $data['original_url'],
        ':posted_date' => $data['posted_date'],
        ':deadline_date' => $data['deadline_date'],
        ':hash_value' => $hash

    ]);
}