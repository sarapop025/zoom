<?php
/**
 * ============================================================
 * approvals/index.php
 * ============================================================
 * ระบบตรวจสอบ / อนุมัติ / เผยแพร่ภาพ
 *
 * Status:
 * PENDING
 * APPROVED
 * REJECTED
 * PUBLISHED
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
    'STAFF'
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
                เฉพาะผู้ดูแลระบบและเจ้าหน้าที่เท่านั้น
            </p>

            <a href="../dashboard/index.php">
                กลับ Dashboard
            </a>

        </div>
    ');
}


// ============================================================
// Process POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = isset($_POST['action'])
        ? strtoupper(trim($_POST['action']))
        : '';

    $photoId = isset($_POST['photo_id'])
        ? (int) $_POST['photo_id']
        : 0;

    $rejectionReason = isset($_POST['rejection_reason'])
        ? trim($_POST['rejection_reason'])
        : '';


    if ($photoId <= 0) {

        header('Location: index.php?error=' . urlencode(
            'ไม่พบรหัสภาพ'
        ));

        exit;
    }


    // ========================================================
    // APPROVE
    // ========================================================

    if ($action === 'APPROVE') {

        try {

            $stmt = $pdo->prepare("
                UPDATE photos
                SET
                    status = 'APPROVED',
                    updated_at = CURRENT_TIMESTAMP
                WHERE photo_id = ?
                  AND status = 'PENDING'
            ");

            $stmt->execute([
                $photoId
            ]);


            if ($stmt->rowCount() > 0) {

                header(
                    'Location: index.php?approved=1'
                );

            } else {

                header(
                    'Location: index.php?error=' .
                    urlencode(
                        'ภาพนี้ไม่ได้อยู่ในสถานะรอตรวจสอบ'
                    )
                );
            }

            exit;

        } catch (PDOException $e) {

            header(
                'Location: index.php?error=' .
                urlencode(
                    $e->getMessage()
                )
            );

            exit;
        }
    }


    // ========================================================
    // REJECT
    // ========================================================

    if ($action === 'REJECT') {

        if ($rejectionReason === '') {

            header(
                'Location: index.php?error=' .
                urlencode(
                    'กรุณาระบุเหตุผลที่ไม่อนุมัติ'
                )
            );

            exit;
        }


        try {

            /*
             * ตาราง photos ของคุณไม่มี
             * rejection_reason
             *
             * จึงเก็บเฉพาะ status
             */

            $stmt = $pdo->prepare("
                UPDATE photos
                SET
                    status = 'REJECTED',
                    updated_at = CURRENT_TIMESTAMP
                WHERE photo_id = ?
                  AND status = 'PENDING'
            ");

            $stmt->execute([
                $photoId
            ]);


            if ($stmt->rowCount() > 0) {

                header(
                    'Location: index.php?rejected=1'
                );

            } else {

                header(
                    'Location: index.php?error=' .
                    urlencode(
                        'ภาพนี้ไม่ได้อยู่ในสถานะรอตรวจสอบ'
                    )
                );
            }

            exit;

        } catch (PDOException $e) {

            header(
                'Location: index.php?error=' .
                urlencode(
                    $e->getMessage()
                )
            );

            exit;
        }
    }


    // ========================================================
    // PUBLISH
    // ========================================================

    if ($action === 'PUBLISH') {

        /*
         * เฉพาะภาพ APPROVED เท่านั้น
         */

        try {

            $stmt = $pdo->prepare("
                UPDATE photos
                SET
                    status = 'PUBLISHED',
                    updated_at = CURRENT_TIMESTAMP
                WHERE photo_id = ?
                  AND status = 'APPROVED'
            ");

            $stmt->execute([
                $photoId
            ]);


            if ($stmt->rowCount() > 0) {

                header(
                    'Location: index.php?published=1'
                );

            } else {

                header(
                    'Location: index.php?error=' .
                    urlencode(
                        'ภาพนี้ยังไม่ได้รับการอนุมัติ'
                    )
                );
            }

            exit;

        } catch (PDOException $e) {

            header(
                'Location: index.php?error=' .
                urlencode(
                    $e->getMessage()
                )
            );

            exit;
        }
    }


    // ========================================================
    // UNKNOWN ACTION
    // ========================================================

    header(
        'Location: index.php?error=' .
        urlencode(
            'ไม่พบคำสั่งที่ต้องการ'
        )
    );

    exit;
}


// ============================================================
// GET FILTER
// ============================================================

$statusFilter = isset($_GET['status'])
    ? strtoupper(trim($_GET['status']))
    : 'PENDING';

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';


// ============================================================
// Allowed Status
// ============================================================

$allowedStatuses = array(
    'PENDING',
    'APPROVED',
    'REJECTED',
    'PUBLISHED'
);


if (!in_array($statusFilter, $allowedStatuses, true)) {

    $statusFilter = 'PENDING';
}


// ============================================================
// Query Photos
// ============================================================

$sql = "
    SELECT
        ph.photo_id,
        ph.project_id,
        ph.photo_name,
        ph.original_name,
        ph.file_name,
        ph.file_path,
        ph.thumbnail_path,
        ph.photo_description,
        ph.photo_date,
        ph.file_size,
        ph.mime_type,
        ph.width,
        ph.height,
        ph.uploaded_by,
        ph.status,
        ph.download_count,
        ph.view_count,
        ph.created_at,
        ph.updated_at,

        p.project_name,

        u.username,
        u.full_name AS uploader_name

    FROM photos ph

    LEFT JOIN projects p
        ON p.project_id = ph.project_id

    LEFT JOIN users u
        ON u.user_id = ph.uploaded_by

    WHERE ph.status = ?
";

$params = array(
    $statusFilter
);


// ============================================================
// Search
// ============================================================

if ($search !== '') {

    $sql .= "
        AND (
            ph.photo_name LIKE ?
            OR ph.original_name LIKE ?
            OR p.project_name LIKE ?
            OR u.username LIKE ?
            OR u.full_name LIKE ?
        )
    ";

    $keyword = '%' . $search . '%';

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}


// ============================================================
// Order
// ============================================================

$sql .= "
    ORDER BY ph.photo_id DESC
";


try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $photos = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    die('
        <div style="
            font-family:Arial;
            padding:40px;
            color:#b91c1c;
        ">

            <h3>Database Error</h3>

            <pre>' .
            e($e->getMessage()) .
            '</pre>

        </div>
    ');
}


// ============================================================
// Statistics
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN status = 'PENDING'
                    THEN 1
                    ELSE 0
                END
            ) AS pending,

            SUM(
                CASE
                    WHEN status = 'APPROVED'
                    THEN 1
                    ELSE 0
                END
            ) AS approved,

            SUM(
                CASE
                    WHEN status = 'REJECTED'
                    THEN 1
                    ELSE 0
                END
            ) AS rejected,

            SUM(
                CASE
                    WHEN status = 'PUBLISHED'
                    THEN 1
                    ELSE 0
                END
            ) AS published

        FROM photos
    ");

    $stats = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $stats = array(
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'published' => 0
    );
}


$total = (int)($stats['total'] ?? 0);

$pending = (int)($stats['pending'] ?? 0);

$approved = (int)($stats['approved'] ?? 0);

$rejected = (int)($stats['rejected'] ?? 0);

$published = (int)($stats['published'] ?? 0);


// ============================================================
// Image URL
// ============================================================

function imageUrl($path)
{
    if (empty($path)) {

        return '../assets/images/no-image.png';
    }


    /*
     * ไม่ใช้ str_starts_with()
     * เพื่อรองรับ PHP รุ่นเก่าบน XAMPP
     */

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
// File Size
// ============================================================

function formatFileSize($bytes)
{
    $bytes = (int)$bytes;


    if ($bytes <= 0) {
        return '-';
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
// Status Badge
// ============================================================

function statusBadge($status)
{
    switch ($status) {

        case 'PENDING':

            return '
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-hourglass-split me-1"></i>
                    รอตรวจสอบ
                </span>
            ';


        case 'APPROVED':

            return '
                <span class="badge bg-success">
                    <i class="bi bi-check-circle me-1"></i>
                    อนุมัติแล้ว
                </span>
            ';


        case 'REJECTED':

            return '
                <span class="badge bg-danger">
                    <i class="bi bi-x-circle me-1"></i>
                    ไม่อนุมัติ
                </span>
            ';


        case 'PUBLISHED':

            return '
                <span class="badge bg-primary">
                    <i class="bi bi-globe2 me-1"></i>
                    เผยแพร่แล้ว
                </span>
            ';


        default:

            return '
                <span class="badge bg-secondary">
                    ' .
                    e($status) .
                    '
                </span>
            ';
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
    ตรวจสอบภาพ | PSU Photo System
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

/* ============================================================
   GLOBAL
============================================================ */

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

    box-shadow:
        0 4px 10px
        rgba(13,71,161,.20);
}


.menu-link i {

    width: 22px;

    text-align: center;

    font-size: 16px;
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

    font-size: 21px;

    background: #eef5ff;

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
   PHOTO CARD
============================================================ */

.photo-card {

    height: 100%;

    overflow: hidden;

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 14px;

    transition: .2s;
}


.photo-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 22px
        rgba(0,0,0,.08);
}


.photo-image {

    width: 100%;

    height: 210px;

    object-fit: cover;

    background: #eef2f7;
}


.photo-body {

    padding: 15px;
}


.photo-title {

    font-weight: 700;

    font-size: 14px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.photo-meta {

    margin-top: 5px;

    color: #6b7280;

    font-size: 12px;
}


.action-buttons {

    display: flex;

    gap: 6px;

    margin-top: 14px;
}


.empty-box {

    padding:
        70px 20px;

    text-align: center;

    color: #9ca3af;
}


.empty-box i {

    font-size: 55px;
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

    <i class="bi bi-images me-2"></i>

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


<a
    href="index.php"
    class="menu-link active"
>

    <i class="bi bi-check2-square"></i>

    ตรวจสอบภาพ

</a>


<a
    href="../reports/index.php"
    class="menu-link"
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


<!-- Header -->

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
        class="bi bi-check2-square me-2"
    ></i>

    ตรวจสอบและอนุมัติภาพ

</h3>


<div class="text-muted small">

    ตรวจสอบภาพก่อนเผยแพร่บนเว็บไซต์

</div>

</div>


<a
    href="../photos/index.php"
    class="btn btn-outline-primary"
>

    <i class="bi bi-images me-1"></i>

    คลังภาพ

</a>


</div>


<!-- ============================================================
     ALERT
============================================================ -->

<?php if (isset($_GET['approved'])): ?>

<div
    class="alert alert-success alert-dismissible fade show"
>

    <i class="bi bi-check-circle me-1"></i>

    อนุมัติภาพเรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if (isset($_GET['rejected'])): ?>

<div
    class="alert alert-warning alert-dismissible fade show"
>

    <i class="bi bi-x-circle me-1"></i>

    ไม่อนุมัติภาพเรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if (isset($_GET['published'])): ?>

<div
    class="alert alert-primary alert-dismissible fade show"
>

    <i class="bi bi-globe2 me-1"></i>

    เผยแพร่ภาพเรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if (isset($_GET['error'])): ?>

<div
    class="alert alert-danger alert-dismissible fade show"
>

    <i class="bi bi-exclamation-triangle me-1"></i>

    <?= e($_GET['error']) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<!-- ============================================================
     STATISTICS
============================================================ -->

<div class="row g-3 mb-4">


<div class="col-xl col-md-4 col-6">

<div class="card-custom stat-card">

<div class="stat-icon">

    <i class="bi bi-images"></i>

</div>

<div class="stat-number">

    <?= number_format($total) ?>

</div>

<div class="stat-label">

    ภาพทั้งหมด

</div>

</div>

</div>


<div class="col-xl col-md-4 col-6">

<div class="card-custom stat-card">

<div class="stat-icon text-warning">

    <i class="bi bi-hourglass-split"></i>

</div>

<div class="stat-number text-warning">

    <?= number_format($pending) ?>

</div>

<div class="stat-label">

    รอตรวจสอบ

</div>

</div>

</div>


<div class="col-xl col-md-4 col-6">

<div class="card-custom stat-card">

<div class="stat-icon text-success">

    <i class="bi bi-check-circle"></i>

</div>

<div class="stat-number text-success">

    <?= number_format($approved) ?>

</div>

<div class="stat-label">

    อนุมัติแล้ว

</div>

</div>

</div>


<div class="col-xl col-md-4 col-6">

<div class="card-custom stat-card">

<div class="stat-icon text-danger">

    <i class="bi bi-x-circle"></i>

</div>

<div class="stat-number text-danger">

    <?= number_format($rejected) ?>

</div>

<div class="stat-label">

    ไม่อนุมัติ

</div>

</div>

</div>


<div class="col-xl col-md-4 col-6">

<div class="card-custom stat-card">

<div class="stat-icon text-primary">

    <i class="bi bi-globe2"></i>

</div>

<div class="stat-number text-primary">

    <?= number_format($published) ?>

</div>

<div class="stat-label">

    เผยแพร่แล้ว

</div>

</div>

</div>


</div>


<!-- ============================================================
     FILTER
============================================================ -->

<div class="card-custom p-3 mb-4">


<form
    method="GET"
    action="index.php"
>


<div class="row g-2">


<div class="col-lg-5">

<div class="input-group">

<span class="input-group-text bg-white">

    <i class="bi bi-search"></i>

</span>


<input
    type="text"
    name="search"
    class="form-control"
    placeholder="ค้นหาชื่อภาพ / กิจกรรม / ผู้ Upload"
    value="<?= e($search) ?>"
>

</div>

</div>


<div class="col-lg-3">

<select
    name="status"
    class="form-select"
>

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


<div class="col-lg-2">

<button
    type="submit"
    class="btn btn-primary w-100"
>

    <i class="bi bi-search me-1"></i>

    ค้นหา

</button>

</div>


<div class="col-lg-2">

<a
    href="index.php"
    class="btn btn-outline-secondary w-100"
>

    <i
        class="bi bi-arrow-counterclockwise me-1"
    ></i>

    ล้าง

</a>

</div>


</div>

</form>

</div>


<!-- ============================================================
     PHOTO LIST
============================================================ -->

<?php if (empty($photos)): ?>


<div class="card-custom">

<div class="empty-box">

<i class="bi bi-check2-square"></i>


<h5 class="mt-3">

    ไม่พบภาพ

</h5>


<p class="small">

    ไม่มีภาพตามสถานะหรือเงื่อนไขการค้นหาที่เลือก

</p>

</div>

</div>


<?php else: ?>


<div class="row g-4">


<?php foreach ($photos as $photo): ?>


<div class="col-xl-3 col-lg-4 col-md-6">


<div class="photo-card">


<!-- Image -->

<a
    href="../photos/download.php?id=<?= (int)$photo['photo_id'] ?>"
    target="_blank"
>

<img
    src="<?= e(
        imageUrl(
            !empty($photo['thumbnail_path'])
                ? $photo['thumbnail_path']
                : $photo['file_path']
        )
    ) ?>"
    class="photo-image"
    alt="<?= e($photo['photo_name']) ?>"
    loading="lazy"
    onerror="
        this.src='../assets/images/no-image.png';
    "
>

</a>


<div class="photo-body">


<!-- Name -->

<div
    class="photo-title"
    title="<?= e($photo['photo_name']) ?>"
>

    <?= e($photo['photo_name']) ?>

</div>


<!-- Project -->

<div class="photo-meta">

    <i class="bi bi-folder2 me-1"></i>

    <?= e(
        !empty($photo['project_name'])
            ? $photo['project_name']
            : 'ไม่ระบุกิจกรรม'
    ) ?>

</div>


<!-- Uploader -->

<div class="photo-meta">

    <i class="bi bi-person me-1"></i>

    <?= e(
        !empty($photo['uploader_name'])
            ? $photo['uploader_name']
            : (
                !empty($photo['username'])
                    ? $photo['username']
                    : '-'
            )
    ) ?>

</div>


<!-- Date -->

<?php if (!empty($photo['photo_date'])): ?>

<div class="photo-meta">

    <i class="bi bi-calendar3 me-1"></i>

    <?= e($photo['photo_date']) ?>

</div>

<?php endif; ?>


<!-- File -->

<div class="photo-meta">

    <i class="bi bi-file-earmark me-1"></i>

    <?= e(
        formatFileSize(
            $photo['file_size']
        )
    ) ?>


    <?php if (
        !empty($photo['width']) &&
        !empty($photo['height'])
    ): ?>

        ·

        <?= (int)$photo['width'] ?>

        ×

        <?= (int)$photo['height'] ?>

    <?php endif; ?>

</div>


<!-- Status -->

<div class="mt-3">

    <?= statusBadge(
        $photo['status']
    ) ?>

</div>


<!-- ========================================================
     PENDING ACTIONS
======================================================== -->

<?php if (
    $photo['status'] === 'PENDING'
): ?>


<div class="action-buttons">


<!-- Approve -->

<form
    method="POST"
    class="flex-grow-1"
    onsubmit="
        return confirm(
            'ยืนยันการอนุมัติภาพนี้หรือไม่?'
        );
    "
>

<input
    type="hidden"
    name="action"
    value="APPROVE"
>


<input
    type="hidden"
    name="photo_id"
    value="<?= (int)$photo['photo_id'] ?>"
>


<button
    type="submit"
    class="btn btn-success btn-sm w-100"
>

    <i class="bi bi-check-lg me-1"></i>

    อนุมัติ

</button>

</form>


<!-- Reject -->

<button
    type="button"
    class="btn btn-outline-danger btn-sm"
    data-bs-toggle="modal"
    data-bs-target="#rejectModal<?= (int)$photo['photo_id'] ?>"
>

    <i class="bi bi-x-lg me-1"></i>

    ไม่อนุมัติ

</button>


</div>


<!-- Reject Modal -->

<div
    class="modal fade"
    id="rejectModal<?= (int)$photo['photo_id'] ?>"
    tabindex="-1"
>

<div class="modal-dialog">

<div class="modal-content">


<div class="modal-header">

<h5 class="modal-title">

    <i
        class="bi bi-x-circle text-danger me-1"
    ></i>

    ไม่อนุมัติภาพ

</h5>


<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
></button>

</div>


<form method="POST">


<div class="modal-body">


<input
    type="hidden"
    name="action"
    value="REJECT"
>


<input
    type="hidden"
    name="photo_id"
    value="<?= (int)$photo['photo_id'] ?>"
>


<div class="mb-3">

<label class="form-label">

    เหตุผลที่ไม่อนุมัติ

    <span class="text-danger">
        *
    </span>

</label>


<textarea
    name="rejection_reason"
    class="form-control"
    rows="4"
    required
    placeholder="ระบุเหตุผลที่ไม่อนุมัติ..."
></textarea>

</div>


</div>


<div class="modal-footer">

<button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
>

    ยกเลิก

</button>


<button
    type="submit"
    class="btn btn-danger"
>

    <i
        class="bi bi-x-circle me-1"
    ></i>

    ไม่อนุมัติ

</button>

</div>


</form>


</div>

</div>

</div>


<?php endif; ?>


<!-- ========================================================
     APPROVED ACTION
======================================================== -->

<?php if (
    $photo['status'] === 'APPROVED'
): ?>


<form
    method="POST"
    class="mt-3"
    onsubmit="
        return confirm(
            'ยืนยันการเผยแพร่ภาพนี้บนเว็บไซต์หรือไม่?'
        );
    "
>

<input
    type="hidden"
    name="action"
    value="PUBLISH"
>


<input
    type="hidden"
    name="photo_id"
    value="<?= (int)$photo['photo_id'] ?>"
>


<button
    type="submit"
    class="btn btn-primary btn-sm w-100"
>

    <i
        class="bi bi-globe2 me-1"
    ></i>

    เผยแพร่ภาพ

</button>

</form>


<?php endif; ?>


<!-- ========================================================
     Details
======================================================== -->

<div class="mt-2">


<a
    href="../photos/edit.php?id=<?= (int)$photo['photo_id'] ?>"
    class="btn btn-outline-secondary btn-sm w-100"
>

    <i class="bi bi-eye me-1"></i>

    ดูรายละเอียด

</a>


</div>


</div>

</div>


</div>


<?php endforeach; ?>


</div>


<?php endif; ?>


<!-- Footer -->

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
