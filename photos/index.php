<?php
/**
 * ============================================================
 * photos/index.php
 * ============================================================
 * คลังภาพถ่ายกิจกรรม / โครงการ
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
        $value === null ? '' : (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ============================================================
// Session
// ============================================================

$userId = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : 0;

$fullName = isset($_SESSION['full_name'])
    ? $_SESSION['full_name']
    : 'ผู้ใช้งาน';

$role = isset($_SESSION['role'])
    ? strtoupper(trim($_SESSION['role']))
    : '';


// ============================================================
// Search
// ============================================================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$projectId = isset($_GET['project_id'])
    ? (int)$_GET['project_id']
    : 0;

$status = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';


// ============================================================
// Image URL
// ============================================================

function imageUrl($path)
{
    if (empty($path)) {
        return '';
    }

    $path = str_replace('\\', '/', trim($path));

    // URL
    if (
        strpos($path, 'http://') === 0 ||
        strpos($path, 'https://') === 0
    ) {
        return $path;
    }

    // uploads/photos/filename.jpg
    if (strpos($path, 'uploads/photos/') === 0) {
        return '../' . $path;
    }

    // uploads/filename.jpg
    if (strpos($path, 'uploads/') === 0) {
        return '../' . $path;
    }

    // photos/filename.jpg
    if (strpos($path, 'photos/') === 0) {
        return '../uploads/' . $path;
    }

    // filename.jpg
    return '../uploads/photos/' . ltrim($path, '/');
}


// ============================================================
// Status
// ============================================================

function statusLabel($status)
{
    $status = strtoupper(trim((string)$status));

    switch ($status) {

        case 'PUBLISHED':
            return 'เผยแพร่';

        case 'APPROVED':
            return 'อนุมัติ';

        case 'PENDING':
            return 'รอตรวจสอบ';

        case 'REJECTED':
            return 'ไม่อนุมัติ';

        default:
            return $status !== ''
                ? $status
                : 'ไม่ระบุ';
    }
}


function statusClass($status)
{
    $status = strtoupper(trim((string)$status));

    switch ($status) {

        case 'PUBLISHED':
            return 'bg-success';

        case 'APPROVED':
            return 'bg-primary';

        case 'PENDING':
            return 'bg-warning text-dark';

        case 'REJECTED':
            return 'bg-danger';

        default:
            return 'bg-secondary';
    }
}


// ============================================================
// Permission
// ============================================================

function canManagePhoto($role, $userId, $photo)
{
    // ADMIN จัดการได้ทั้งหมด
    if ($role === 'ADMIN') {
        return true;
    }

    // STAFF จัดการเฉพาะโครงการที่ตัวเองสร้าง
    if (
        $role === 'STAFF' &&
        isset($photo['project_owner']) &&
        (int)$photo['project_owner'] === $userId
    ) {
        return true;
    }

    return false;
}


// ============================================================
// Load Projects
// ============================================================

$stmt = $pdo->query("
    SELECT
        project_id,
        project_name,
        project_date
    FROM projects
    ORDER BY
        project_date DESC,
        project_id DESC
");

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// WHERE
// ============================================================

$where = array();
$params = array();


// Project

if ($projectId > 0) {

    $where[] = 'ph.project_id = ?';

    $params[] = $projectId;
}


// Status

if ($status !== '') {

    $where[] = 'ph.status = ?';

    $params[] = $status;
}


// Search

if ($search !== '') {

    $where[] = "
        (
            ph.photo_name LIKE ?
            OR ph.original_name LIKE ?
            OR ph.file_name LIKE ?
            OR p.project_name LIKE ?
        )
    ";

    $keyword = '%' . $search . '%';

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}


// ============================================================
// SQL
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
        p.project_date,

        p.created_by AS project_owner,
        p.status AS project_status,

        u.full_name AS uploader_name

    FROM photos ph

    LEFT JOIN projects p
        ON ph.project_id = p.project_id

    LEFT JOIN users u
        ON ph.uploaded_by = u.user_id
";


// WHERE

if (!empty($where)) {

    $sql .=
        ' WHERE ' .
        implode(' AND ', $where);
}


// ORDER

$sql .= "
    ORDER BY
        ph.created_at DESC,
        ph.photo_id DESC
";


// ============================================================
// Execute
// ============================================================

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$photoCount = count($photos);


// ============================================================
// Flash Message
// ============================================================

$deleted =
    isset($_GET['deleted']) &&
    $_GET['deleted'] == '1';

$uploaded =
    isset($_GET['uploaded']) &&
    $_GET['uploaded'] == '1';

$updated =
    isset($_GET['updated']) &&
    $_GET['updated'] == '1';

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
    คลังภาพ | PSU Photo System
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

    min-height: 68px;

    background: #062b63;
}

.navbar-brand {

    font-size: 19px;

    font-weight: 700;
}

.navbar-user {

    color: #fff;

    font-size: 13px;
}


/* ============================================================
   SIDEBAR
============================================================ */

.sidebar {

    position: fixed;

    left: 0;

    top: 68px;

    bottom: 0;

    width: 240px;

    background: #fff;

    border-right:
        1px solid #e5e7eb;

    padding: 18px 12px;

    overflow-y: auto;
}

.menu-title {

    margin:
        15px 10px 7px;

    color: #9ca3af;

    font-size: 11px;

    text-transform: uppercase;
}

.menu-link {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        11px 12px;

    margin-bottom: 4px;

    border-radius: 9px;

    color: #4b5563;

    text-decoration: none;

    font-size: 14px;

    transition: .2s;
}

.menu-link i {

    width: 22px;

    text-align: center;

    font-size: 17px;
}

.menu-link:hover {

    background: #eef5ff;

    color: #0d47a1;
}

.menu-link.active {

    background: #0d47a1;

    color: #fff;
}


/* ============================================================
   MAIN
============================================================ */

.main {

    margin-left: 240px;

    padding:
        95px
        25px
        35px;

    min-height: 100vh;
}

.page-title {

    color: #082d63;

    font-size: 24px;

    font-weight: 700;
}

.page-subtitle {

    color: #6b7280;

    font-size: 13px;
}


/* ============================================================
   FILTER
============================================================ */

.filter-card {

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 12px;

    padding: 16px;

    box-shadow:
        0 3px 12px
        rgba(0, 0, 0, .03);
}

.form-control,
.form-select {

    min-height: 43px;

    border-radius: 8px;
}


/* ============================================================
   PHOTO CARD
============================================================ */

.photo-card {

    position: relative;

    height: 100%;

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 13px;

    overflow: hidden;

    box-shadow:
        0 3px 12px
        rgba(0, 0, 0, .04);

    transition:
        transform .2s,
        box-shadow .2s;
}

.photo-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 10px 25px
        rgba(0, 0, 0, .10);
}


/* ============================================================
   IMAGE
============================================================ */

.photo-cover {

    position: relative;

    width: 100%;

    height: 210px;

    background: #eef1f5;

    overflow: hidden;
}

.photo-cover img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;
}

.photo-empty {

    width: 100%;

    height: 210px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef1f5;

    color: #9ca3af;

    font-size: 55px;
}


/* ============================================================
   IMAGE ACTION
============================================================ */

.photo-status {

    position: absolute;

    top: 10px;

    left: 10px;

    z-index: 5;
}

.photo-actions-top {

    position: absolute;

    top: 10px;

    right: 10px;

    z-index: 5;

    display: flex;

    gap: 5px;
}


/* ============================================================
   BODY
============================================================ */

.photo-body {

    padding: 16px;
}

.photo-name {

    color: #17395f;

    font-size: 16px;

    font-weight: 700;

    line-height: 1.4;

    min-height: 45px;

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;
}

.photo-meta {

    margin-top: 9px;

    color: #6b7280;

    font-size: 12px;
}

.photo-meta i {

    width: 18px;

    text-align: center;
}

.photo-project {

    color: #0d47a1;

    font-size: 12px;

    margin-top: 8px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* ============================================================
   FOOTER
============================================================ */

.photo-footer {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-top: 14px;

    padding-top: 12px;

    border-top:
        1px solid #edf0f4;
}

.photo-stat {

    color: #6b7280;

    font-size: 12px;
}

.photo-stat i {

    color: #0d6efd;
}

.photo-actions {

    display: flex;

    gap: 5px;
}

.photo-actions .btn {

    border-radius: 7px;

    font-size: 12px;
}


/* ============================================================
   EMPTY
============================================================ */

.empty-box {

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 12px;

    text-align: center;

    padding:
        75px
        20px;

    color: #9ca3af;
}

.empty-box i {

    display: block;

    font-size: 60px;

    margin-bottom: 15px;
}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 768px) {

    .sidebar {
        display: none;
    }

    .main {

        margin-left: 0;

        padding-left: 15px;

        padding-right: 15px;
    }

    .navbar-user {
        display: none;
    }

    .photo-cover,
    .photo-empty {
        height: 180px;
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
    class="navbar-brand"
    href="../dashboard/index.php"
>

    <i class="bi bi-images me-2"></i>

    PSU Photo System

</a>


<div class="navbar-user">

    <i class="bi bi-person-circle me-1"></i>

    <?= e($fullName) ?>


    <span
        class="badge bg-light text-primary ms-2"
    >

        <?= e($role) ?>

    </span>


    <a
        href="../auth/logout.php"
        class="btn btn-sm btn-outline-light ms-2"
    >

        <i class="bi bi-box-arrow-right"></i>

    </a>

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

    <i class="bi bi-calendar-event"></i>

    กิจกรรม / โครงการ

</a>


<a
    href="index.php"
    class="menu-link active"
>

    <i class="bi bi-images"></i>

    คลังภาพ

</a>


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF'
): ?>

<a
    href="upload.php"
    class="menu-link"
>

    <i class="bi bi-cloud-upload"></i>

    อัปโหลดภาพ

</a>

<?php endif; ?>


<div class="menu-title">
    จัดการระบบ
</div>


<?php if ($role === 'ADMIN'): ?>


<a
    href="../categories/index.php"
    class="menu-link"
>

    <i class="bi bi-tags"></i>

    หมวดหมู่

</a>


<a
    href="../users/index.php"
    class="menu-link"
>

    <i class="bi bi-people"></i>

    ผู้ใช้งาน

</a>


<a
    href="../approvals/index.php"
    class="menu-link"
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


<?php endif; ?>


<?php if ($role === 'EXECUTIVE'): ?>


<a
    href="../reports/index.php"
    class="menu-link"
>

    <i class="bi bi-bar-chart"></i>

    รายงาน / สถิติ

</a>


<?php endif; ?>


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


<div class="page-title">

    <i class="bi bi-images me-2"></i>

    คลังภาพถ่าย

</div>


<div class="page-subtitle">

    จัดเก็บและจัดการภาพถ่ายกิจกรรม / โครงการ

</div>


</div>


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF'
): ?>

<a
    href="upload.php"
    class="btn btn-primary"
>

    <i
        class="bi bi-cloud-upload me-1"
    ></i>

    อัปโหลดภาพ

</a>

<?php endif; ?>


</div>


<!-- ============================================================
     ALERT
============================================================ -->

<?php if ($deleted): ?>

<div
    class="
        alert
        alert-success
        alert-dismissible
        fade
        show
    "
>

    <i class="bi bi-check-circle me-1"></i>

    ลบภาพเรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if ($uploaded): ?>

<div
    class="
        alert
        alert-success
        alert-dismissible
        fade
        show
    "
>

    <i class="bi bi-check-circle me-1"></i>

    อัปโหลดภาพเรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if ($updated): ?>

<div
    class="
        alert
        alert-success
        alert-dismissible
        fade
        show
    "
>

    <i class="bi bi-check-circle me-1"></i>

    แก้ไขข้อมูลภาพเรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<!-- ============================================================
     FILTER
============================================================ -->

<div class="filter-card mb-4">


<form
    method="GET"
    action="index.php"
>


<div class="row g-2">


<!-- SEARCH -->

<div class="col-md-5">

<label
    class="
        form-label
        small
        fw-semibold
    "
>

    ค้นหา

</label>


<div class="input-group">

<span class="input-group-text">

    <i class="bi bi-search"></i>

</span>


<input
    type="text"
    name="search"
    class="form-control"
    placeholder="ชื่อภาพ ชื่อไฟล์ หรือชื่อกิจกรรม..."
    value="<?= e($search) ?>"
>

</div>

</div>


<!-- PROJECT -->

<div class="col-md-3">

<label
    class="
        form-label
        small
        fw-semibold
    "
>

    กิจกรรม / โครงการ

</label>


<select
    name="project_id"
    class="form-select"
>

<option value="0">

    -- ทุกโครงการ --

</option>


<?php foreach (
    $projects as $project
): ?>

<option
    value="<?= (int)$project['project_id'] ?>"
    <?= (
        $projectId ==
        $project['project_id']
    )
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


<!-- STATUS -->

<div class="col-md-2">

<label
    class="
        form-label
        small
        fw-semibold
    "
>

    สถานะ

</label>


<select
    name="status"
    class="form-select"
>


<option value="">

    -- ทุกสถานะ --

</option>


<option
    value="PENDING"
    <?= $status === 'PENDING'
        ? 'selected'
        : '' ?>
>

    รอตรวจสอบ

</option>


<option
    value="APPROVED"
    <?= $status === 'APPROVED'
        ? 'selected'
        : '' ?>
>

    อนุมัติ

</option>


<option
    value="PUBLISHED"
    <?= $status === 'PUBLISHED'
        ? 'selected'
        : '' ?>
>

    เผยแพร่

</option>


<option
    value="REJECTED"
    <?= $status === 'REJECTED'
        ? 'selected'
        : '' ?>
>

    ไม่อนุมัติ

</option>


</select>

</div>


<!-- BUTTON -->

<div
    class="
        col-md-2
        d-flex
        align-items-end
    "
>

<button
    type="submit"
    class="
        btn
        btn-primary
        w-100
    "
>

    <i class="bi bi-search me-1"></i>

    ค้นหา

</button>

</div>


</div>


</form>


</div>


<!-- ============================================================
     RESULT HEADER
============================================================ -->

<div
    class="
        d-flex
        justify-content-between
        align-items-center
        mb-3
    "
>


<div>

<strong>

    รายการภาพถ่าย

</strong>


<span
    class="badge bg-primary ms-2"
>

    <?= number_format(
        $photoCount
    ) ?>

</span>

</div>


<?php if (
    $search !== '' ||
    $projectId > 0 ||
    $status !== ''
): ?>

<a
    href="index.php"
    class="
        btn
        btn-sm
        btn-outline-secondary
    "
>

    <i class="bi bi-x-circle me-1"></i>

    ล้างตัวกรอง

</a>

<?php endif; ?>


</div>


<!-- ============================================================
     PHOTO LIST
============================================================ -->

<?php if (empty($photos)): ?>


<div class="empty-box">


<i class="bi bi-images"></i>


<div
    class="
        fw-semibold
        text-dark
    "
>

    ไม่พบภาพถ่าย

</div>


<div class="small mt-1">

    ยังไม่มีข้อมูลตามเงื่อนไขที่ค้นหา

</div>


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF'
): ?>

<a
    href="upload.php"
    class="btn btn-primary mt-3"
>

    <i class="bi bi-cloud-upload me-1"></i>

    อัปโหลดภาพ

</a>

<?php endif; ?>


</div>


<?php else: ?>


<div class="row g-3">


<?php foreach (
    $photos as $photo
): ?>


<?php

$coverPath =
    !empty($photo['thumbnail_path'])
        ? $photo['thumbnail_path']
        : $photo['file_path'];

$coverUrl =
    imageUrl($coverPath);

$canManage =
    canManagePhoto(
        $role,
        $userId,
        $photo
    );

$displayName =
    !empty($photo['photo_name'])
        ? $photo['photo_name']
        : (
            !empty($photo['original_name'])
                ? $photo['original_name']
                : $photo['file_name']
        );

?>


<div
    class="
        col-12
        col-md-6
        col-xl-4
    "
>


<div class="photo-card">


<!-- ========================================================
     COVER
========================================================= -->

<?php if (
    $coverUrl !== ''
): ?>


<a
    href="<?= e($coverUrl) ?>"
    target="_blank"
    title="เปิดภาพขนาดเต็ม"
>


<div class="photo-cover">


<img
    src="<?= e($coverUrl) ?>"
    alt="<?= e($displayName) ?>"
    loading="lazy"
    onerror="
        this.style.display='none';
        this.nextElementSibling.style.display='flex';
    "
>


<div
    class="photo-empty"
    style="display:none;"
>

    <i class="bi bi-image"></i>

</div>


<!-- STATUS -->

<div class="photo-status">

<span
    class="
        badge
        <?= e(
            statusClass(
                $photo['status']
            )
        ) ?>
    "
>

    <?= e(
        statusLabel(
            $photo['status']
        )
    ) ?>

</span>

</div>


</div>

</a>


<?php else: ?>


<div class="photo-cover">


<div class="photo-empty">

    <i class="bi bi-images"></i>

</div>


<div class="photo-status">

<span
    class="
        badge
        <?= e(
            statusClass(
                $photo['status']
            )
        ) ?>
    "
>

    <?= e(
        statusLabel(
            $photo['status']
        )
    ) ?>

</span>

</div>


</div>


<?php endif; ?>


<!-- ========================================================
     BODY
========================================================= -->

<div class="photo-body">


<!-- NAME -->

<div
    class="photo-name"
    title="<?= e($displayName) ?>"
>

    <?= e($displayName) ?>

</div>


<!-- PROJECT -->

<?php if (
    !empty($photo['project_name'])
): ?>


<div
    class="photo-project"
    title="<?= e(
        $photo['project_name']
    ) ?>"
>

    <i
        class="
            bi
            bi-folder2-open
            me-1
        "
    ></i>

    <?= e(
        $photo['project_name']
    ) ?>

</div>


<?php endif; ?>


<!-- DATE -->

<?php if (
    !empty($photo['photo_date'])
): ?>

<div class="photo-meta">

    <i class="bi bi-calendar3"></i>

    <?= e(
        $photo['photo_date']
    ) ?>

</div>

<?php endif; ?>


<!-- UPLOADER -->

<div class="photo-meta">

    <i class="bi bi-person"></i>

    <?= e(
        !empty($photo['uploader_name'])
            ? $photo['uploader_name']
            : '-'
    ) ?>

</div>


<!-- SIZE -->

<?php if (
    !empty($photo['width']) &&
    !empty($photo['height'])
): ?>

<div class="photo-meta">

    <i class="bi bi-aspect-ratio"></i>

    <?= (int)$photo['width'] ?>

    ×

    <?= (int)$photo['height'] ?>

    px

</div>

<?php endif; ?>


<!-- FOOTER -->

<div class="photo-footer">


<div class="photo-stat">

    <i class="bi bi-download me-1"></i>

    <?= number_format(
        (int)$photo['download_count']
    ) ?>

</div>


<div class="photo-actions">


<!-- VIEW -->

<a
    href="<?= e($coverUrl) ?>"
    target="_blank"
    class="
        btn
        btn-sm
        btn-outline-primary
    "
    title="ดูภาพ"
>

    <i class="bi bi-eye"></i>

</a>


<!-- DOWNLOAD -->

<a
    href="download.php?id=<?= (int)$photo['photo_id'] ?>"
    class="
        btn
        btn-sm
        btn-outline-primary
    "
    title="ดาวน์โหลด"
>

    <i class="bi bi-download"></i>

</a>


<?php if ($canManage): ?>


<!-- EDIT -->

<a
    href="edit.php?id=<?= (int)$photo['photo_id'] ?>"
    class="
        btn
        btn-sm
        btn-warning
    "
    title="แก้ไข"
>

    <i class="bi bi-pencil"></i>

</a>


<!-- DELETE -->

<a
    href="delete.php?id=<?= (int)$photo['photo_id'] ?>"
    class="
        btn
        btn-sm
        btn-danger
    "
    title="ลบ"
    onclick="
        return confirm(
            'ยืนยันการลบภาพนี้หรือไม่?'
        );
    "
>

    <i class="bi bi-trash"></i>

</a>


<?php endif; ?>


</div>


</div>


</div>


</div>


</div>


<?php endforeach; ?>


</div>


<?php endif; ?>


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

    |

    โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
    (ฝ่ายมัธยมศึกษา)

</div>


</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>