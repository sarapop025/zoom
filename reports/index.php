<?php
/**
 * ============================================================
 * reports/index.php
 * ============================================================
 * ระบบรายงานสถิติภาพถ่าย
 * PSU Photo System
 * ============================================================
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_auth.php';


// ============================================================
// Helper
// ============================================================

function e($value)
{
    return htmlspecialchars(
        $value === null ? '' : $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ============================================================
// Session
// ============================================================

$currentUserId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;

$currentFullName = isset($_SESSION['full_name'])
    ? $_SESSION['full_name']
    : 'ผู้ใช้งาน';

$currentRole = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ============================================================
// Permission
// ============================================================

$allowedRoles = array(
    'ADMIN',
    'STAFF',
    'EXECUTIVE'
);

if (!in_array($currentRole, $allowedRoles, true)) {

    http_response_code(403);

    exit('
        <div style="
            font-family:Arial,sans-serif;
            text-align:center;
            padding:60px;
        ">

            <h2>ไม่มีสิทธิ์เข้าถึงหน้านี้</h2>

            <p>
                คุณไม่มีสิทธิ์ดูรายงานของระบบ
            </p>

            <a href="../dashboard/index.php">
                กลับ Dashboard
            </a>

        </div>
    ');
}


// ============================================================
// Filter
// ============================================================

$startDate = isset($_GET['start_date'])
    ? trim($_GET['start_date'])
    : '';

$endDate = isset($_GET['end_date'])
    ? trim($_GET['end_date'])
    : '';

$projectId = isset($_GET['project_id'])
    ? (int) $_GET['project_id']
    : 0;

$statusFilter = isset($_GET['status'])
    ? strtoupper(trim($_GET['status']))
    : '';


// ============================================================
// Validate Date
// ============================================================

function validDate($date)
{
    if (empty($date)) {
        return false;
    }

    $d = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    return $d &&
        $d->format('Y-m-d') === $date;
}


if (!validDate($startDate)) {
    $startDate = '';
}


if (!validDate($endDate)) {
    $endDate = '';
}


// ============================================================
// Status
// ============================================================

$allowedStatuses = array(
    'PENDING',
    'APPROVED',
    'REJECTED',
    'PUBLISHED'
);

if (
    $statusFilter !== '' &&
    !in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $statusFilter = '';
}


// ============================================================
// Projects
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT
            project_id,
            project_name
        FROM projects
        ORDER BY project_name ASC
    ");

    $projects = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $projects = array();
}


// ============================================================
// Base WHERE
// ============================================================

$where = array();

$params = array();


// Date

if ($startDate !== '') {

    $where[] = "
        ph.created_at >= ?
    ";

    $params[] =
        $startDate . ' 00:00:00';
}


if ($endDate !== '') {

    $where[] = "
        ph.created_at <= ?
    ";

    $params[] =
        $endDate . ' 23:59:59';
}


// Project

if ($projectId > 0) {

    $where[] = "
        ph.project_id = ?
    ";

    $params[] =
        $projectId;
}


// Status

if ($statusFilter !== '') {

    $where[] = "
        ph.status = ?
    ";

    $params[] =
        $statusFilter;
}


// Build WHERE

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        ' WHERE ' .
        implode(
            ' AND ',
            $where
        );
}


// ============================================================
// Overall Statistics
// ============================================================

try {

    $sql = "
        SELECT

            COUNT(*) AS total_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'PENDING'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS pending_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'APPROVED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS approved_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'REJECTED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS rejected_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'PUBLISHED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS published_photos,

            COALESCE(
                SUM(ph.download_count),
                0
            ) AS total_downloads,

            COALESCE(
                SUM(ph.view_count),
                0
            ) AS total_views,

            COALESCE(
                SUM(ph.file_size),
                0
            ) AS total_size

        FROM photos ph

        $whereSql
    ";


    $stmt =
        $pdo->prepare($sql);

    $stmt->execute(
        $params
    );

    $summary =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    die(
        '<div style="padding:40px;font-family:Arial;">
            <h3>Database Error</h3>
            <pre>' .
            e($e->getMessage()) .
            '</pre>
        </div>'
    );
}


$totalPhotos =
    (int)(
        $summary['total_photos']
        ?? 0
    );

$pendingPhotos =
    (int)(
        $summary['pending_photos']
        ?? 0
    );

$approvedPhotos =
    (int)(
        $summary['approved_photos']
        ?? 0
    );

$rejectedPhotos =
    (int)(
        $summary['rejected_photos']
        ?? 0
    );

$publishedPhotos =
    (int)(
        $summary['published_photos']
        ?? 0
    );

$totalDownloads =
    (int)(
        $summary['total_downloads']
        ?? 0
    );

$totalViews =
    (int)(
        $summary['total_views']
        ?? 0
    );

$totalSize =
    (int)(
        $summary['total_size']
        ?? 0
    );


// ============================================================
// Format File Size
// ============================================================

function formatFileSize($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes <= 0) {
        return '0 B';
    }

    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1024 * 1024) {

        return number_format(
            $bytes / 1024,
            1
        ) . ' KB';
    }

    if ($bytes < 1024 * 1024 * 1024) {

        return number_format(
            $bytes / (1024 * 1024),
            1
        ) . ' MB';
    }

    return number_format(
        $bytes / (1024 * 1024 * 1024),
        1
    ) . ' GB';
}


// ============================================================
// Project Statistics
// ============================================================

try {

    $projectSql = "
        SELECT

            p.project_id,

            p.project_name,

            COUNT(ph.photo_id)
                AS photo_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'PENDING'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS pending_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'APPROVED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS approved_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN ph.status = 'PUBLISHED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS published_count,

            COALESCE(
                SUM(ph.download_count),
                0
            ) AS downloads,

            COALESCE(
                SUM(ph.view_count),
                0
            ) AS views

        FROM projects p

        LEFT JOIN photos ph
            ON ph.project_id =
               p.project_id

    ";


    /*
     * สำหรับรายงานตามโครงการ
     * filter วันที่/status จะถูกใส่ใน JOIN
     */

    $joinConditions = array();

    $projectParams = array();


    if ($startDate !== '') {

        $joinConditions[] = "
            ph.created_at >= ?
        ";

        $projectParams[] =
            $startDate . ' 00:00:00';
    }


    if ($endDate !== '') {

        $joinConditions[] = "
            ph.created_at <= ?
        ";

        $projectParams[] =
            $endDate . ' 23:59:59';
    }


    if ($statusFilter !== '') {

        $joinConditions[] = "
            ph.status = ?
        ";

        $projectParams[] =
            $statusFilter;
    }


    if (!empty($joinConditions)) {

        $projectSql .= "
            AND " .
            implode(
                ' AND ',
                $joinConditions
            );
    }


    $projectSql .= "

        GROUP BY
            p.project_id,
            p.project_name

        ORDER BY
            photo_count DESC,
            p.project_name ASC

        LIMIT 50
    ";


    $stmt =
        $pdo->prepare(
            $projectSql
        );

    $stmt->execute(
        $projectParams
    );

    $projectStats =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $projectStats = array();
}


// ============================================================
// User Statistics
// ============================================================

try {

    $userSql = "
        SELECT

            u.user_id,

            u.username,

            u.full_name,

            COUNT(ph.photo_id)
                AS upload_count,

            COALESCE(
                SUM(ph.download_count),
                0
            ) AS downloads,

            COALESCE(
                SUM(ph.view_count),
                0
            ) AS views

        FROM users u

        INNER JOIN photos ph
            ON ph.uploaded_by =
               u.user_id
    ";


    $userWhere = array();

    $userParams = array();


    if ($startDate !== '') {

        $userWhere[] =
            'ph.created_at >= ?';

        $userParams[] =
            $startDate . ' 00:00:00';
    }


    if ($endDate !== '') {

        $userWhere[] =
            'ph.created_at <= ?';

        $userParams[] =
            $endDate . ' 23:59:59';
    }


    if ($statusFilter !== '') {

        $userWhere[] =
            'ph.status = ?';

        $userParams[] =
            $statusFilter;
    }


    if (!empty($userWhere)) {

        $userSql .=
            ' WHERE ' .
            implode(
                ' AND ',
                $userWhere
            );
    }


    $userSql .= "

        GROUP BY
            u.user_id,
            u.username,
            u.full_name

        ORDER BY
            upload_count DESC

        LIMIT 20
    ";


    $stmt =
        $pdo->prepare(
            $userSql
        );

    $stmt->execute(
        $userParams
    );

    $userStats =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $userStats = array();
}


// ============================================================
// Latest Photos
// ============================================================

try {

    $latestSql = "
        SELECT

            ph.photo_id,

            ph.photo_name,

            ph.status,

            ph.created_at,

            ph.download_count,

            ph.view_count,

            p.project_name,

            u.full_name AS uploader_name

        FROM photos ph

        LEFT JOIN projects p
            ON p.project_id =
               ph.project_id

        LEFT JOIN users u
            ON u.user_id =
               ph.uploaded_by

        $whereSql

        ORDER BY
            ph.photo_id DESC

        LIMIT 20
    ";


    $stmt =
        $pdo->prepare(
            $latestSql
        );

    $stmt->execute(
        $params
    );

    $latestPhotos =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $latestPhotos = array();
}


// ============================================================
// Status Label
// ============================================================

function statusLabel($status)
{
    switch ($status) {

        case 'PENDING':
            return 'รอตรวจสอบ';

        case 'APPROVED':
            return 'อนุมัติแล้ว';

        case 'REJECTED':
            return 'ไม่อนุมัติ';

        case 'PUBLISHED':
            return 'เผยแพร่แล้ว';

        default:
            return $status;
    }
}


function statusClass($status)
{
    switch ($status) {

        case 'PENDING':
            return 'bg-warning text-dark';

        case 'APPROVED':
            return 'bg-success';

        case 'REJECTED':
            return 'bg-danger';

        case 'PUBLISHED':
            return 'bg-primary';

        default:
            return 'bg-secondary';
    }
}

?>

<!DOCTYPE html>

<html lang="th">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    รายงาน | PSU Photo System
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background: #f5f7fb;

    color: #1f2937;

    font-family:
        "Noto Sans Thai",
        Tahoma,
        Arial,
        sans-serif;
}


/* ============================================================
   NAVBAR
============================================================ */

.navbar {

    height: 68px;

    background:
        linear-gradient(
            135deg,
            #062b63,
            #0b4f9c
        );

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.12);
}


.navbar-brand {

    font-size: 18px;

    font-weight: 700;
}


/* ============================================================
   SIDEBAR
============================================================ */

.sidebar {

    position: fixed;

    left: 0;

    top: 68px;

    bottom: 0;

    width: 245px;

    background: #fff;

    border-right:
        1px solid #e5e7eb;

    padding: 18px 12px;

    overflow-y: auto;

    z-index: 100;
}


.menu-title {

    margin:
        14px 10px 8px;

    color: #9ca3af;

    font-size: 11px;

    font-weight: 700;
}


.menu-link {

    display: flex;

    align-items: center;

    gap: 10px;

    width: 100%;

    padding:
        11px 13px;

    margin-bottom: 4px;

    border-radius: 9px;

    color: #4b5563;

    text-decoration: none;

    font-size: 14px;

    transition: .2s;
}


.menu-link:hover {

    background: #eef5ff;

    color: #0d47a1;
}


.menu-link.active {

    background: #0d47a1;

    color: #fff;
}


.menu-link i {

    width: 22px;

    text-align: center;
}


/* ============================================================
   MAIN
============================================================ */

.main {

    margin-left: 245px;

    padding:
        95px 28px 40px;

    min-height: 100vh;
}


/* ============================================================
   CARD
============================================================ */

.card-custom {

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 14px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.04);
}


/* ============================================================
   STAT
============================================================ */

.stat-card {

    padding: 20px;

    height: 100%;
}


.stat-icon {

    width: 48px;

    height: 48px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef5ff;

    font-size: 21px;

    color: #0d6efd;
}


.stat-number {

    margin-top: 10px;

    font-size: 25px;

    font-weight: 800;
}


.stat-label {

    color: #6b7280;

    font-size: 13px;
}


/* ============================================================
   TABLE
============================================================ */

.table {

    margin-bottom: 0;
}


.table th {

    background: #f8fafc;

    color: #475569;

    font-size: 13px;

    white-space: nowrap;
}


.table td {

    font-size: 13px;

    vertical-align: middle;
}


/* ============================================================
   CHART
============================================================ */

.chart-wrapper {

    position: relative;

    height: 300px;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (
    max-width: 767px
) {

    .sidebar {

        display: none;
    }


    .main {

        margin-left: 0;

        padding:
            90px 15px 30px;
    }


    .user-info {

        display: none;
    }

}

</style>

</head>


<body>


<!-- ============================================================
     NAVBAR
============================================================ -->

<nav class="navbar navbar-dark fixed-top">

<div class="container-fluid">


<a
    href="../dashboard/index.php"
    class="navbar-brand"
>

    <i
        class="bi bi-images me-2"
    ></i>

    PSU Photo System

</a>


<div class="user-info text-white small">

    <i
        class="bi bi-person-circle me-1"
    ></i>

    <?= e($currentFullName) ?>


    <span
        class="badge bg-light text-primary ms-2"
    >

        <?= e($currentRole) ?>

    </span>

</div>


</div>

</nav>


<!-- ============================================================
     SIDEBAR
============================================================ -->

<aside class="sidebar">


<div class="menu-title">
    เมนูหลัก
</div>


<a
    href="../dashboard/index.php"
    class="menu-link"
>

    <i class="bi bi-speedometer2"></i>

    Dashboard

</a>


<a
    href="../projects/index.php"
    class="menu-link"
>

    <i class="bi bi-folder2-open"></i>

    กิจกรรม / โครงการ

</a>


<a
    href="../photos/index.php"
    class="menu-link"
>

    <i class="bi bi-images"></i>

    คลังภาพ

</a>


<div class="menu-title">
    จัดการระบบ
</div>


<a
    href="../categories/index.php"
    class="menu-link"
>

    <i class="bi bi-tags"></i>

    หมวดหมู่

</a>


<?php if ($currentRole === 'ADMIN'): ?>

<a
    href="../users/index.php"
    class="menu-link"
>

    <i class="bi bi-people"></i>

    ผู้ใช้งาน

</a>

<?php endif; ?>


<?php if (
    $currentRole === 'ADMIN' ||
    $currentRole === 'STAFF'
): ?>

<a
    href="../approvals/index.php"
    class="menu-link"
>

    <i class="bi bi-check2-square"></i>

    ตรวจสอบภาพ

</a>

<?php endif; ?>


<a
    href="index.php"
    class="menu-link active"
>

    <i class="bi bi-bar-chart"></i>

    รายงาน

</a>


<div class="menu-title">
    บัญชี
</div>


<a
    href="../auth/logout.php"
    class="menu-link"
>

    <i class="bi bi-box-arrow-right"></i>

    ออกจากระบบ

</a>


</aside>


<!-- ============================================================
     MAIN
============================================================ -->

<main class="main">


<!-- HEADER -->

<div
    class="
        d-flex
        justify-content-between
        align-items-center
        flex-wrap
        gap-3
        mb-4
    "
>


<div>

<h3 class="mb-1">

    <i
        class="bi bi-bar-chart-line me-2"
    ></i>

    รายงานและสถิติ

</h3>


<div class="text-muted small">

    สรุปข้อมูลการจัดเก็บและเผยแพร่ภาพถ่าย

</div>

</div>


<button
    type="button"
    class="btn btn-outline-primary"
    onclick="window.print();"
>

    <i
        class="bi bi-printer me-1"
    ></i>

    พิมพ์รายงาน

</button>


</div>


<!-- ============================================================
     FILTER
============================================================ -->

<div class="card-custom p-3 mb-4">


<form
    method="GET"
    action="index.php"
>


<div class="row g-3">


<div class="col-lg-3">

<label class="form-label">

    วันที่เริ่มต้น

</label>


<input
    type="date"
    name="start_date"
    class="form-control"
    value="<?= e($startDate) ?>"
>

</div>


<div class="col-lg-3">

<label class="form-label">

    วันที่สิ้นสุด

</label>


<input
    type="date"
    name="end_date"
    class="form-control"
    value="<?= e($endDate) ?>"
>

</div>


<div class="col-lg-3">

<label class="form-label">

    โครงการ

</label>


<select
    name="project_id"
    class="form-select"
>

<option value="">

    ทุกโครงการ

</option>


<?php foreach (
    $projects
    as $project
): ?>

<option
    value="<?= (int)$project['project_id'] ?>"
    <?= $projectId ===
        (int)$project['project_id']
        ? 'selected'
        : '' ?>
>

    <?= e(
        $project['project_name']
    ) ?>

</option>

<?php endforeach; ?>


</select>

</div>


<div class="col-lg-3">

<label class="form-label">

    สถานะ

</label>


<select
    name="status"
    class="form-select"
>

<option value="">

    ทุกสถานะ

</option>


<option
    value="PENDING"
    <?= $statusFilter === 'PENDING'
        ? 'selected'
        : '' ?>
>

    รอตรวจสอบ

</option>


<option
    value="APPROVED"
    <?= $statusFilter === 'APPROVED'
        ? 'selected'
        : '' ?>
>

    อนุมัติแล้ว

</option>


<option
    value="REJECTED"
    <?= $statusFilter === 'REJECTED'
        ? 'selected'
        : '' ?>
>

    ไม่อนุมัติ

</option>


<option
    value="PUBLISHED"
    <?= $statusFilter === 'PUBLISHED'
        ? 'selected'
        : '' ?>
>

    เผยแพร่แล้ว

</option>


</select>

</div>


<div class="col-12">

<div class="d-flex gap-2">


<button
    type="submit"
    class="btn btn-primary"
>

    <i
        class="bi bi-search me-1"
    ></i>

    แสดงรายงาน

</button>


<a
    href="index.php"
    class="btn btn-outline-secondary"
>

    <i
        class="bi bi-arrow-counterclockwise me-1"
    ></i>

    ล้างตัวกรอง

</a>


</div>

</div>


</div>

</form>

</div>


<!-- ============================================================
     STATISTICS
============================================================ -->

<div class="row g-3 mb-4">


<div class="col-xl-3 col-md-6">

<div class="card-custom stat-card">

<div class="stat-icon">

    <i class="bi bi-images"></i>

</div>


<div class="stat-number">

    <?= number_format(
        $totalPhotos
    ) ?>

</div>


<div class="stat-label">

    ภาพทั้งหมด

</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="card-custom stat-card">

<div class="stat-icon text-warning">

    <i class="bi bi-hourglass-split"></i>

</div>


<div class="stat-number text-warning">

    <?= number_format(
        $pendingPhotos
    ) ?>

</div>


<div class="stat-label">

    รอตรวจสอบ

</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="card-custom stat-card">

<div class="stat-icon text-success">

    <i class="bi bi-check-circle"></i>

</div>


<div class="stat-number text-success">

    <?= number_format(
        $approvedPhotos
    ) ?>

</div>


<div class="stat-label">

    อนุมัติแล้ว

</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="card-custom stat-card">

<div class="stat-icon text-primary">

    <i class="bi bi-globe2"></i>

</div>


<div class="stat-number text-primary">

    <?= number_format(
        $publishedPhotos
    ) ?>

</div>


<div class="stat-label">

    เผยแพร่แล้ว

</div>

</div>

</div>


</div>


<!-- ============================================================
     SECOND STAT ROW
============================================================ -->

<div class="row g-3 mb-4">


<div class="col-md-4">

<div class="card-custom p-4">


<div class="d-flex justify-content-between">

<div>

<div class="text-muted small">

    การดาวน์โหลด

</div>


<h3 class="mb-0 mt-1">

    <?= number_format(
        $totalDownloads
    ) ?>

</h3>

</div>


<div
    class="text-primary"
    style="font-size:30px;"
>

    <i class="bi bi-download"></i>

</div>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card-custom p-4">


<div class="d-flex justify-content-between">

<div>

<div class="text-muted small">

    การเข้าชม

</div>


<h3 class="mb-0 mt-1">

    <?= number_format(
        $totalViews
    ) ?>

</h3>

</div>


<div
    class="text-success"
    style="font-size:30px;"
>

    <i class="bi bi-eye"></i>

</div>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card-custom p-4">


<div class="d-flex justify-content-between">

<div>

<div class="text-muted small">

    ขนาดไฟล์รวม

</div>


<h3 class="mb-0 mt-1">

    <?= e(
        formatFileSize(
            $totalSize
        )
    ) ?>

</h3>

</div>


<div
    class="text-warning"
    style="font-size:30px;"
>

    <i class="bi bi-hdd"></i>

</div>

</div>

</div>

</div>


</div>


<!-- ============================================================
     STATUS SUMMARY
============================================================ -->

<div class="card-custom p-4 mb-4">


<h5 class="mb-4">

    <i
        class="bi bi-pie-chart me-2"
    ></i>

    สรุปสถานะภาพ

</h5>


<div class="row g-3">


<div class="col-md-3">

<div class="border rounded p-3">

<div class="text-muted small">

    รอตรวจสอบ

</div>


<div class="fs-4 fw-bold text-warning">

    <?= number_format(
        $pendingPhotos
    ) ?>

</div>


<div class="progress mt-2">

<div
    class="progress-bar bg-warning"
    style="
        width:
        <?= $totalPhotos > 0
            ? round(
                ($pendingPhotos /
                $totalPhotos) * 100,
                1
            )
            : 0
        ?>%;
    "
></div>

</div>

</div>

</div>


<div class="col-md-3">

<div class="border rounded p-3">

<div class="text-muted small">

    อนุมัติแล้ว

</div>


<div class="fs-4 fw-bold text-success">

    <?= number_format(
        $approvedPhotos
    ) ?>

</div>


<div class="progress mt-2">

<div
    class="progress-bar bg-success"
    style="
        width:
        <?= $totalPhotos > 0
            ? round(
                ($approvedPhotos /
                $totalPhotos) * 100,
                1
            )
            : 0
        ?>%;
    "
></div>

</div>

</div>

</div>


<div class="col-md-3">

<div class="border rounded p-3">

<div class="text-muted small">

    ไม่อนุมัติ

</div>


<div class="fs-4 fw-bold text-danger">

    <?= number_format(
        $rejectedPhotos
    ) ?>

</div>


<div class="progress mt-2">

<div
    class="progress-bar bg-danger"
    style="
        width:
        <?= $totalPhotos > 0
            ? round(
                ($rejectedPhotos /
                $totalPhotos) * 100,
                1
            )
            : 0
        ?>%;
    "
></div>

</div>

</div>

</div>


<div class="col-md-3">

<div class="border rounded p-3">

<div class="text-muted small">

    เผยแพร่แล้ว

</div>


<div class="fs-4 fw-bold text-primary">

    <?= number_format(
        $publishedPhotos
    ) ?>

</div>


<div class="progress mt-2">

<div
    class="progress-bar bg-primary"
    style="
        width:
        <?= $totalPhotos > 0
            ? round(
                ($publishedPhotos /
                $totalPhotos) * 100,
                1
            )
            : 0
        ?>%;
    "
></div>

</div>

</div>

</div>


</div>

</div>


<!-- ============================================================
     PROJECT REPORT
============================================================ -->

<div class="card-custom mb-4">


<div class="p-4 border-bottom">

<h5 class="mb-1">

    <i
        class="bi bi-folder2-open me-2"
    ></i>

    รายงานตามกิจกรรม / โครงการ

</h5>


<div class="text-muted small">

    จำนวนภาพและสถิติการใช้งานแยกตามโครงการ

</div>

</div>


<div class="table-responsive">


<table class="table table-hover">


<thead>

<tr>

<th>
    #
</th>

<th>
    โครงการ
</th>

<th class="text-center">
    ภาพทั้งหมด
</th>

<th class="text-center">
    รอตรวจ
</th>

<th class="text-center">
    อนุมัติ
</th>

<th class="text-center">
    เผยแพร่
</th>

<th class="text-center">
    ดู
</th>

<th class="text-center">
    ดาวน์โหลด
</th>

</tr>

</thead>


<tbody>


<?php if (
    empty($projectStats)
): ?>


<tr>

<td
    colspan="8"
    class="text-center text-muted py-5"
>

    ไม่พบข้อมูลโครงการ

</td>

</tr>


<?php else: ?>


<?php foreach (
    $projectStats
    as $index => $row
): ?>


<tr>


<td>

    <?= $index + 1 ?>

</td>


<td>

<strong>

    <?= e(
        $row['project_name']
    ) ?>

</strong>

</td>


<td class="text-center">

<span class="badge bg-secondary">

    <?= number_format(
        (int)$row['photo_count']
    ) ?>

</span>

</td>


<td class="text-center">

<?= number_format(
    (int)$row['pending_count']
) ?>

</td>


<td class="text-center">

<?= number_format(
    (int)$row['approved_count']
) ?>

</td>


<td class="text-center">

<span class="text-primary fw-bold">

    <?= number_format(
        (int)$row['published_count']
    ) ?>

</span>

</td>


<td class="text-center">

<?= number_format(
    (int)$row['views']
) ?>

</td>


<td class="text-center">

<?= number_format(
    (int)$row['downloads']
) ?>

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


<!-- ============================================================
     USER REPORT
============================================================ -->

<div class="card-custom mb-4">


<div class="p-4 border-bottom">

<h5 class="mb-1">

    <i
        class="bi bi-people me-2"
    ></i>

    รายงานผู้ Upload ภาพ

</h5>


<div class="text-muted small">

    สถิติการเพิ่มภาพของผู้ใช้งาน

</div>

</div>


<div class="table-responsive">


<table class="table table-hover">


<thead>

<tr>

<th>
    #
</th>

<th>
    ผู้ใช้งาน
</th>

<th>
    ชื่อ-นามสกุล
</th>

<th class="text-center">
    จำนวนภาพ
</th>

<th class="text-center">
    เข้าชม
</th>

<th class="text-center">
    ดาวน์โหลด
</th>

</tr>

</thead>


<tbody>


<?php if (
    empty($userStats)
): ?>


<tr>

<td
    colspan="6"
    class="text-center text-muted py-5"
>

    ไม่พบข้อมูล

</td>

</tr>


<?php else: ?>


<?php foreach (
    $userStats
    as $index => $row
): ?>


<tr>


<td>

    <?= $index + 1 ?>

</td>


<td>

    <strong>

        <?= e(
            $row['username']
        ) ?>

    </strong>

</td>


<td>

    <?= e(
        $row['full_name']
    ) ?>

</td>


<td class="text-center">

<span class="badge bg-primary">

    <?= number_format(
        (int)$row['upload_count']
    ) ?>

</span>

</td>


<td class="text-center">

    <?= number_format(
        (int)$row['views']
    ) ?>

</td>


<td class="text-center">

    <?= number_format(
        (int)$row['downloads']
    ) ?>

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


<!-- ============================================================
     LATEST PHOTOS
============================================================ -->

<div class="card-custom mb-4">


<div class="p-4 border-bottom">

<h5 class="mb-1">

    <i
        class="bi bi-clock-history me-2"
    ></i>

    ภาพล่าสุด

</h5>


<div class="text-muted small">

    รายการภาพตามเงื่อนไขที่เลือก

</div>

</div>


<div class="table-responsive">


<table class="table table-hover">


<thead>

<tr>

<th>
    #
</th>

<th>
    ชื่อภาพ
</th>

<th>
    โครงการ
</th>

<th>
    ผู้ Upload
</th>

<th>
    สถานะ
</th>

<th class="text-center">
    ดู
</th>

<th class="text-center">
    ดาวน์โหลด
</th>

<th>
    วันที่
</th>

</tr>

</thead>


<tbody>


<?php if (
    empty($latestPhotos)
): ?>


<tr>

<td
    colspan="8"
    class="text-center text-muted py-5"
>

    ไม่พบข้อมูลภาพ

</td>

</tr>


<?php else: ?>


<?php foreach (
    $latestPhotos
    as $index => $photo
): ?>


<tr>


<td>

    <?= $index + 1 ?>

</td>


<td>

<strong>

    <?= e(
        $photo['photo_name']
    ) ?>

</strong>

</td>


<td>

    <?= e(
        $photo['project_name']
            ?: '-'
    ) ?>

</td>


<td>

    <?= e(
        $photo['uploader_name']
            ?: '-'
    ) ?>

</td>


<td>

<span
    class="badge <?= e(
        statusClass(
            $photo['status']
        )
    ) ?>"
>

    <?= e(
        statusLabel(
            $photo['status']
        )
    ) ?>

</span>

</td>


<td class="text-center">

    <?= number_format(
        (int)$photo['view_count']
    ) ?>

</td>


<td class="text-center">

    <?= number_format(
        (int)$photo['download_count']
    ) ?>

</td>


<td>

<small>

    <?= e(
        $photo['created_at']
    ) ?>

</small>

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


<!-- ============================================================
     FOOTER
============================================================ -->

<div
    class="
        text-center
        text-muted
        small
        mt-5
    "
>

    ระบบจัดเก็บภาพถ่ายกิจกรรม / โครงการ

    <br>

    โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
    (ฝ่ายมัธยมศึกษา)

</div>


</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
