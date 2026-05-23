<?php

require_once __DIR__ . '/core/database.php';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$jobsPerPage = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $jobsPerPage;

/*
|--------------------------------------------------------------------------
| SEARCH + FILTERS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? '');

/*
|--------------------------------------------------------------------------
| BUILD WHERE
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(jobs.title LIKE ? OR jobs.company_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($location)) {
    $where[] = "jobs.location LIKE ?";
    $params[] = "%{$location}%";
}

if (!empty($category)) {
    $where[] = "job_categories.name = ?";
    $params[] = $category;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| TOTAL JOBS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) total
    FROM jobs
    LEFT JOIN job_categories ON jobs.category_id = job_categories.id
    $whereSql
";

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalJobs = $countStmt->fetch()['total'];
$totalPages = ceil($totalJobs / $jobsPerPage);

if ($totalPages < 1) {
    $totalPages = 1;
}

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $jobsPerPage;
}

/*
|--------------------------------------------------------------------------
| FETCH JOBS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        jobs.*,
        job_categories.name AS category_name
    FROM jobs
    LEFT JOIN job_categories ON jobs.category_id = job_categories.id
    $whereSql
    ORDER BY jobs.id DESC
    LIMIT $jobsPerPage OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $db->query("
    SELECT * FROM job_categories ORDER BY name ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| TOTAL ACTIVE JOBS
|--------------------------------------------------------------------------
*/

$totalActive = $db->query("
    SELECT COUNT(*) total FROM jobs WHERE is_active = 1
")->fetch()['total'];

?>
