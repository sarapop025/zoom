<?php
/**
 * ============================================================
 * dashboard/index.php
 * ============================================================
 * Dashboard ระบบจัดเก็บภาพกิจกรรม / โครงการ
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

$userId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;

$fullName = isset($_SESSION['full_name'])
    ? $_SESSION['full_name']
    : 'ผู้ใช้งาน';

$role = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ============================================================
// Statistics
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT

            COUNT(*) AS total_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'PENDING'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS pending_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'APPROVED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS approved_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'REJECTED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS rejected_photos,

            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'PUBLISHED'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS published_photos,

            COALESCE(
                SUM(download_count),
                0
            ) AS total_downloads,

            COALESCE(
                SUM(view_count),
                0
            ) AS total_views,

            COALESCE(
                SUM(file_size),
                0
            ) AS total_size

        FROM photos
    ");

    $photoStats = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $photoStats = array(
        'total_photos' => 0,
        'pending_photos' => 0,
        'approved_photos' => 0,
        'rejected_photos' => 0,
        'published_photos' => 0,
        'total_downloads' => 0,
        'total_views' => 0,
        'total_size' => 0
    );
}


// ============================================================
// Projects Count
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM projects
    ");

    $totalProjects =
        (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $totalProjects = 0;
}


// ============================================================
// Categories Count
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM categories
    ");

    $totalCategories =
        (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $totalCategories = 0;
}


// ============================================================
// Users Count
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
    ");

    $totalUsers =
        (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $totalUsers = 0;
}


// ============================================================
// Latest Photos
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT

            ph.photo_id,

            ph.photo_name,

            ph.file_path,

            ph.thumbnail_path,

            ph.status,

            ph.view_count,

            ph.download_count,

            ph.created_at,

            p.project_name,

            u.full_name AS uploader_name

        FROM photos ph

        LEFT JOIN projects p
            ON p.project_id =
               ph.project_id

        LEFT JOIN users u
            ON u.user_id =
               ph.uploaded_by

        ORDER BY
            ph.photo_id DESC

        LIMIT 8
    ");

    $latestPhotos =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $latestPhotos = array();
}


// ============================================================
// Latest Projects
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT

            p.project_id,

            p.project_name,

            p.created_at,

            COUNT(ph.photo_id)
                AS photo_count

        FROM projects p

        LEFT JOIN photos ph
            ON ph.project_id =
               p.project_id

        GROUP BY
            p.project_id,
            p.project_name,
            p.created_at

        ORDER BY
            p.project_id DESC

        LIMIT 5
    ");

    $latestProjects =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $latestProjects = array();
}


// ============================================================
// Top Viewed Photos
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT

            ph.photo_id,

            ph.photo_name,

            ph.view_count,

            ph.download_count,

            p.project_name

        FROM photos ph

        LEFT JOIN projects p
            ON p.project_id =
               ph.project_id

        ORDER BY
            ph.view_count DESC

        LIMIT 5
    ");

    $topPhotos =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $topPhotos = array();
}


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
// Image URL
// ============================================================

function imageUrl($path)
{
    if (empty($path)) {

        return '../assets/images/no-image.png';
    }

    if (
        strpos($path, 'http://') === 0 ||
        strpos($path, 'https://') === 0
    ) {

        return $path;
    }

    if (strpos($path, '/') === 0) {

        return $path;
    }

    return '../' . ltrim(
        $path,
        '/'
    );
}


// ============================================================
// Status
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
    Dashboard | PSU Photo System
</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>


<!-- System CSS -->

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>

/* ============================================================
   DASHBOARD
============================================================ */

body {

    background: #f5f7fb;
}


/*
 * เนื่องจาก Sidebar กว้าง 245px
 */

.dashboard-main {

    margin-left: 245px;

    padding:
        25px 28px 40px;

    min-height: calc(
        100vh - 68px
    );
}


/* ============================================================
   HEADER
============================================================ */

.dashboard-title {

    font-size: 25px;

    font-weight: 800;

    color: #172033;
}


.dashboard-subtitle {

    color: #6b7280;

    font-size: 13px;
}


/* ============================================================
   STAT CARD
============================================================ */

.stat-card {

    position: relative;

    height: 100%;

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 15px;

    padding: 20px;

    overflow: hidden;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.04);

    transition:
        transform .2s,
        box-shadow .2s;
}


.stat-card:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.08);
}


.stat-icon {

    width: 48px;

    height: 48px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background: #eef5ff;

    color: #0d6efd;
}


.stat-value {

    margin-top: 13px;

    font-size: 27px;

    font-weight: 800;

    color: #172033;
}


.stat-label {

    margin-top: 2px;

    color: #6b7280;

    font-size: 13px;
}


/* ============================================================
   GENERAL CARD
============================================================ */

.dashboard-card {

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 15px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.04);

    overflow: hidden;
}


.dashboard-card-header {

    padding:
        17px 20px;

    border-bottom:
        1px solid #eef0f3;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 10px;
}


.dashboard-card-title {

    font-size: 15px;

    font-weight: 700;

    margin: 0;
}


/* ============================================================
   PHOTO
============================================================ */

.dashboard-photo {

    width: 100%;

    height: 145px;

    object-fit: cover;

    background: #eef2f7;
}


.photo-name {

    font-size: 13px;

    font-weight: 700;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.photo-project {

    font-size: 11px;

    color: #6b7280;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* ============================================================
   QUICK MENU
============================================================ */

.quick-menu {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 14px;

    border:
        1px solid #e5e7eb;

    border-radius: 12px;

    color: #374151;

    text-decoration: none;

    background: #fff;

    transition: .2s;
}


.quick-menu:hover {

    background: #eef5ff;

    color: #0d47a1;

    border-color: #cbdcf7;

    transform:
        translateY(-1px);
}


.quick-icon {

    width: 42px;

    height: 42px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef5ff;

    color: #0d47a1;

    font-size: 18px;
}


/* ============================================================
   TABLE
============================================================ */

.dashboard-table {

    margin: 0;
}


.dashboard-table th {

    font-size: 12px;

    color: #6b7280;

    background: #f8fafc;

    white-space: nowrap;
}


.dashboard-table td {

    font-size: 12px;

    vertical-align: middle;
}


/* ============================================================
   MOBILE
============================================================ */

@media (
    max-width: 991px
) {

    .dashboard-main {

        margin-left: 0;

        padding:
            20px 15px 35px;
    }

}


@media (
    max-width: 576px
) {

    .dashboard-title {

        font-size: 21px;
    }

    .stat-value {

        font-size: 23px;
    }

}

</style>

</head>


<body>


<!-- ============================================================
     NAVBAR
============================================================ -->

<?php
require_once __DIR__ . '/../includes/navbar.php';
?>


<!-- ============================================================
     SIDEBAR
============================================================ -->

<?php
require_once __DIR__ . '/../includes/sidebar.php';
?>


<!-- ============================================================
     MAIN
============================================================ -->

<main class="dashboard-main">


<!-- ============================================================
     PAGE HEADER
============================================================ -->

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

<div class="dashboard-title">

    <i
        class="bi bi-speedometer2 me-2"
    ></i>

    Dashboard

</div>


<div class="dashboard-subtitle">

    ภาพรวมระบบจัดเก็บภาพกิจกรรม / โครงการ

</div>

</div>


<div class="text-muted small">

    <i
        class="bi bi-person-circle me-1"
    ></i>

    <?= e($fullName) ?>

</div>


</div>


<!-- ============================================================
     STAT CARDS
============================================================ -->

<div class="row g-3 mb-4">


<!-- Photos -->

<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon">

    <i class="bi bi-images"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['total_photos']
    ) ?>

</div>


<div class="stat-label">

    ภาพทั้งหมด

</div>

</div>

</div>


<!-- Projects -->

<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-success">

    <i class="bi bi-folder2-open"></i>

</div>


<div class="stat-value">

    <?= number_format(
        $totalProjects
    ) ?>

</div>


<div class="stat-label">

    กิจกรรม / โครงการ

</div>

</div>

</div>


<!-- Published -->

<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-primary">

    <i class="bi bi-globe2"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['published_photos']
    ) ?>

</div>


<div class="stat-label">

    ภาพเผยแพร่แล้ว

</div>

</div>

</div>


<!-- Downloads -->

<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-warning">

    <i class="bi bi-download"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['total_downloads']
    ) ?>

</div>


<div class="stat-label">

    จำนวนดาวน์โหลด

</div>

</div>

</div>


</div>


<!-- ============================================================
     SECOND STAT
============================================================ -->

<div class="row g-3 mb-4">


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-warning">

    <i class="bi bi-hourglass-split"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['pending_photos']
    ) ?>

</div>


<div class="stat-label">

    รอตรวจสอบ

</div>


<?php if (
    (
        $role === 'ADMIN' ||
        $role === 'STAFF'
    ) &&
    (int)$photoStats['pending_photos'] > 0
): ?>

<div class="mt-3">

<a
    href="../approvals/index.php"
    class="btn btn-sm btn-warning"
>

    ตรวจสอบภาพ

</a>

</div>

<?php endif; ?>


</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-success">

    <i class="bi bi-check-circle"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['approved_photos']
    ) ?>

</div>


<div class="stat-label">

    อนุมัติแล้ว

</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-danger">

    <i class="bi bi-x-circle"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['rejected_photos']
    ) ?>

</div>


<div class="stat-label">

    ไม่อนุมัติ

</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-icon text-info">

    <i class="bi bi-eye"></i>

</div>


<div class="stat-value">

    <?= number_format(
        (int)$photoStats['total_views']
    ) ?>

</div>


<div class="stat-label">

    จำนวนเข้าชมภาพ

</div>

</div>

</div>


</div>


<!-- ============================================================
     QUICK MENU
============================================================ -->

<div class="dashboard-card mb-4">


<div class="dashboard-card-header">

<h5 class="dashboard-card-title">

    <i
        class="bi bi-lightning-charge me-2"
    ></i>

    เมนูด่วน

</h5>

</div>


<div class="p-3">


<div class="row g-3">


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF'
): ?>


<div class="col-xl-3 col-md-6">

<a
    href="../photos/upload.php"
    class="quick-menu"
>

<div class="quick-icon">

    <i class="bi bi-cloud-arrow-up"></i>

</div>


<div>

<div class="fw-bold">

    Upload ภาพ

</div>


<div class="small text-muted">

    เพิ่มภาพเข้าสู่ระบบ

</div>

</div>

</a>

</div>


<div class="col-xl-3 col-md-6">

<a
    href="../projects/create.php"
    class="quick-menu"
>

<div class="quick-icon">

    <i class="bi bi-folder-plus"></i>

</div>


<div>

<div class="fw-bold">

    สร้างโครงการ

</div>


<div class="small text-muted">

    เพิ่มกิจกรรมใหม่

</div>

</div>

</a>

</div>


<?php endif; ?>


<div class="col-xl-3 col-md-6">

<a
    href="../photos/index.php"
    class="quick-menu"
>

<div class="quick-icon">

    <i class="bi bi-images"></i>

</div>


<div>

<div class="fw-bold">

    คลังภาพ

</div>


<div class="small text-muted">

    ดูภาพทั้งหมด

</div>

</div>

</a>

</div>


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF' ||
    $role === 'EXECUTIVE'
): ?>


<div class="col-xl-3 col-md-6">

<a
    href="../reports/index.php"
    class="quick-menu"
>

<div class="quick-icon">

    <i class="bi bi-bar-chart-line"></i>

</div>


<div>

<div class="fw-bold">

    รายงาน

</div>


<div class="small text-muted">

    ดูสถิติระบบ

</div>

</div>

</a>

</div>


<?php endif; ?>


</div>

</div>

</div>


<!-- ============================================================
     LATEST PHOTOS
============================================================ -->

<div class="dashboard-card mb-4">


<div class="dashboard-card-header">


<h5 class="dashboard-card-title">

    <i
        class="bi bi-clock-history me-2"
    ></i>

    ภาพล่าสุด

</h5>


<a
    href="../photos/index.php"
    class="btn btn-sm btn-outline-primary"
>

    ดูทั้งหมด

    <i class="bi bi-arrow-right ms-1"></i>

</a>


</div>


<div class="p-3">


<?php if (
    empty($latestPhotos)
): ?>


<div
    class="text-center text-muted py-5"
>

    <i
        class="bi bi-images"
        style="font-size:45px;"
    ></i>


    <div class="mt-2">

        ยังไม่มีภาพในระบบ

    </div>

</div>


<?php else: ?>


<div class="row g-3">


<?php foreach (
    $latestPhotos
    as $photo
): ?>


<div class="col-xl-3 col-lg-4 col-md-6">


<div
    class="border rounded-3 overflow-hidden bg-white"
>


<img
    src="<?= e(
        imageUrl(
            !empty(
                $photo['thumbnail_path']
            )
                ? $photo['thumbnail_path']
                : $photo['file_path']
        )
    ) ?>"
    class="dashboard-photo"
    alt="<?= e(
        $photo['photo_name']
    ) ?>"
    loading="lazy"
    onerror="
        this.src='../assets/images/no-image.png';
    "
>


<div class="p-3">


<div
    class="photo-name"
    title="<?= e(
        $photo['photo_name']
    ) ?>"
>

    <?= e(
        $photo['photo_name']
    ) ?>

</div>


<div
    class="photo-project mt-1"
>

    <i class="bi bi-folder2 me-1"></i>

    <?= e(
        $photo['project_name']
            ?: 'ไม่ระบุโครงการ'
    ) ?>

</div>


<div class="mt-2">

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

</div>


<div
    class="
        d-flex
        gap-3
        mt-3
        text-muted
        small
    "
>

<span>

    <i class="bi bi-eye me-1"></i>

    <?= number_format(
        (int)$photo['view_count']
    ) ?>

</span>


<span>

    <i class="bi bi-download me-1"></i>

    <?= number_format(
        (int)$photo['download_count']
    ) ?>

</span>

</div>


</div>

</div>


</div>


<?php endforeach; ?>


</div>


<?php endif; ?>


</div>

</div>


<!-- ============================================================
     PROJECT + TOP PHOTOS
============================================================ -->

<div class="row g-4">


<!-- Latest Projects -->

<div class="col-xl-7">


<div class="dashboard-card">


<div class="dashboard-card-header">

<h5 class="dashboard-card-title">

    <i
        class="bi bi-folder2-open me-2"
    ></i>

    โครงการล่าสุด

</h5>


<a
    href="../projects/index.php"
    class="btn btn-sm btn-outline-primary"
>

    ดูทั้งหมด

</a>

</div>


<div class="table-responsive">


<table class="
    table
    table-hover
    dashboard-table
">

<thead>

<tr>

<th>
    โครงการ
</th>

<th class="text-center">
    จำนวนภาพ
</th>

<th>
    วันที่สร้าง
</th>

</tr>

</thead>


<tbody>


<?php if (
    empty($latestProjects)
): ?>


<tr>

<td
    colspan="3"
    class="text-center text-muted py-4"
>

    ไม่มีข้อมูล

</td>

</tr>


<?php else: ?>


<?php foreach (
    $latestProjects
    as $project
): ?>


<tr>


<td>

<a
    href="../projects/view.php?id=<?= (int)$project['project_id'] ?>"
    class="text-decoration-none fw-semibold"
>

    <?= e(
        $project['project_name']
    ) ?>

</a>

</td>


<td class="text-center">

<span class="badge bg-primary">

    <?= number_format(
        (int)$project['photo_count']
    ) ?>

</span>

</td>


<td>

<?= e(
    $project['created_at']
        ?: '-'
) ?>

</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>

</table>

</div>

</div>

</div>


<!-- Top Photos -->

<div class="col-xl-5">


<div class="dashboard-card">


<div class="dashboard-card-header">

<h5 class="dashboard-card-title">

    <i
        class="bi bi-trophy me-2"
    ></i>

    ภาพยอดนิยม

</h5>


</div>


<div class="p-2">


<?php if (
    empty($topPhotos)
): ?>


<div
    class="text-center text-muted py-5"
>

    ยังไม่มีข้อมูล

</div>


<?php else: ?>


<?php foreach (
    $topPhotos
    as $index => $photo
): ?>


<div
    class="
        d-flex
        align-items-center
        gap-3
        p-3
        border-bottom
    "
>


<div
    class="
        rounded-circle
        bg-primary
        text-white
        d-flex
        align-items-center
        justify-content-center
        flex-shrink-0
    "
    style="
        width:34px;
        height:34px;
        font-size:12px;
        font-weight:700;
    "
>

    <?= $index + 1 ?>

</div>


<div
    class="flex-grow-1"
    style="min-width:0;"
>


<div
    class="photo-name"
    title="<?= e(
        $photo['photo_name']
    ) ?>"
>

    <?= e(
        $photo['photo_name']
    ) ?>

</div>


<div
    class="photo-project"
>

    <?= e(
        $photo['project_name']
            ?: '-'
    ) ?>

</div>


</div>


<div
    class="
        text-end
        small
        text-muted
    "
>

<div>

    <i class="bi bi-eye"></i>

    <?= number_format(
        (int)$photo['view_count']
    ) ?>

</div>


<div>

    <i class="bi bi-download"></i>

    <?= number_format(
        (int)$photo['download_count']
    ) ?>

</div>

</div>


</div>


<?php endforeach; ?>


<?php endif; ?>


</div>

</div>

</div>


</div>


<!-- ============================================================
     SYSTEM INFO
============================================================ -->

<div class="row g-3 mt-4">


<div class="col-md-4">

<div class="dashboard-card p-4">


<div
    class="
        d-flex
        justify-content-between
    "
>

<div>

<div class="text-muted small">

    หมวดหมู่

</div>


<div class="fs-4 fw-bold">

    <?= number_format(
        $totalCategories
    ) ?>

</div>

</div>


<i
    class="
        bi
        bi-tags
        text-primary
    "
    style="font-size:30px;"
></i>

</div>

</div>

</div>


<?php if (
    $role === 'ADMIN'
): ?>

<div class="col-md-4">

<div class="dashboard-card p-4">


<div
    class="
        d-flex
        justify-content-between
    "
>

<div>

<div class="text-muted small">

    ผู้ใช้งานระบบ

</div>


<div class="fs-4 fw-bold">

    <?= number_format(
        $totalUsers
    ) ?>

</div>

</div>


<i
    class="
        bi
        bi-people
        text-success
    "
    style="font-size:30px;"
></i>

</div>

</div>

</div>

<?php endif; ?>


<div class="col-md-4">

<div class="dashboard-card p-4">


<div
    class="
        d-flex
        justify-content-between
    "
>

<div>

<div class="text-muted small">

    พื้นที่ภาพรวม

</div>


<div class="fs-4 fw-bold">

    <?= e(
        formatFileSize(
            (int)$photoStats['total_size']
        )
    ) ?>

</div>

</div>


<i
    class="
        bi
        bi-hdd
        text-warning
    "
    style="font-size:30px;"
></i>

</div>

</div>

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
        pb-3
    "
>

    PSU Photo System

    <br>

    ระบบจัดเก็บภาพกิจกรรม / โครงการ

</div>


</main>


<!-- ============================================================
     Bootstrap JS
============================================================ -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- ============================================================
     App JS
============================================================ -->

<script
    src="../assets/js/app.js"
></script>


</body>

</html>
