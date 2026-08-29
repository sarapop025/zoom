<?php
/**
 * ============================================================
 * projects/index.php
 * ============================================================
 * รายการกิจกรรม / โครงการ
 * โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์ (ฝ่ายมัธยมศึกษา)
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
    ? strtoupper(trim($_SESSION['role']))
    : '';


// ============================================================
// Search
// ============================================================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status = isset($_GET['status'])
    ? strtoupper(trim($_GET['status']))
    : '';


// ============================================================
// WHERE
// ============================================================

$where = array();
$params = array();


// ============================================================
// STAFF
// ============================================================
// STAFF เห็นเฉพาะโครงการที่ตัวเองสร้าง
// ============================================================

if ($role === 'STAFF') {

    $where[] = 'p.created_by = ?';

    $params[] = $userId;
}


// ============================================================
// SEARCH
// ============================================================

if ($search !== '') {

    $where[] = "
        (
            p.project_name LIKE ?
            OR p.project_location LIKE ?
        )
    ";

    $keyword = '%' . $search . '%';

    $params[] = $keyword;
    $params[] = $keyword;
}


// ============================================================
// STATUS
// ============================================================

$allowedStatus = array(
    'DRAFT',
    'PENDING',
    'APPROVED',
    'PUBLISHED',
    'INACTIVE'
);

if (
    $status !== '' &&
    in_array($status, $allowedStatus, true)
) {

    $where[] = 'p.status = ?';

    $params[] = $status;

} else {

    $status = '';
}


// ============================================================
// SQL
// ============================================================

$sql = "
    SELECT

        p.project_id,
        p.project_name,
        p.project_date,
        p.project_location,
        p.category_id,
        p.created_by,
        p.status,
        p.created_at,

        c.category_name,

        u.full_name AS creator_name,

        (
            SELECT COUNT(*)
            FROM photos ph
            WHERE ph.project_id = p.project_id
        ) AS photo_count,

        (
            SELECT ph.file_path
            FROM photos ph
            WHERE ph.project_id = p.project_id
            ORDER BY ph.photo_id DESC
            LIMIT 1
        ) AS cover_path

    FROM projects p

    LEFT JOIN categories c
        ON p.category_id = c.category_id

    LEFT JOIN users u
        ON p.created_by = u.user_id
";


// ============================================================
// WHERE
// ============================================================

if (!empty($where)) {

    $sql .=
        ' WHERE ' .
        implode(' AND ', $where);
}


// ============================================================
// ORDER
// ============================================================

$sql .= "
    ORDER BY
        p.project_date DESC,
        p.project_id DESC
";


// ============================================================
// Execute
// ============================================================

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $projects = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    die(
        '<div style="
            padding:30px;
            font-family:Arial;
        ">
            <h3>Database Error</h3>
            <pre>' .
            e($e->getMessage()) .
            '</pre>
        </div>'
    );
}


// ============================================================
// Image URL
// ============================================================

function imageUrl($path)
{
    if (empty($path)) {
        return '';
    }

    $path = str_replace(
        '\\',
        '/',
        trim($path)
    );

    // URL
    if (
        strpos($path, 'http://') === 0 ||
        strpos($path, 'https://') === 0
    ) {
        return $path;
    }

    // Absolute web path
    if (strpos($path, '/') === 0) {
        return $path;
    }

    /*
     * uploads/photos/filename.jpg
     */
    if (
        strpos(
            $path,
            'uploads/photos/'
        ) === 0
    ) {
        return '../' . $path;
    }

    /*
     * uploads/filename.jpg
     */
    if (
        strpos(
            $path,
            'uploads/'
        ) === 0
    ) {
        return '../' . $path;
    }

    /*
     * photos/filename.jpg
     *
     * กรณีฐานข้อมูลเก็บเป็น photos/...
     */
    if (
        strpos(
            $path,
            'photos/'
        ) === 0
    ) {
        return '../uploads/' . $path;
    }

    /*
     * filename อย่างเดียว
     */
    return '../uploads/photos/' .
        ltrim($path, '/');
}


// ============================================================
// Status Label
// ============================================================

function statusLabel($status)
{
    $status = strtoupper(
        trim((string) $status)
    );

    switch ($status) {

        case 'PUBLISHED':
            return 'เผยแพร่';

        case 'APPROVED':
            return 'อนุมัติ';

        case 'DRAFT':
            return 'แบบร่าง';

        case 'PENDING':
            return 'รอตรวจสอบ';

        case 'INACTIVE':
            return 'ปิดใช้งาน';

        default:

            return $status !== ''
                ? $status
                : 'ไม่ระบุ';
    }
}


// ============================================================
// Status Badge
// ============================================================

function statusClass($status)
{
    $status = strtoupper(
        trim((string) $status)
    );

    switch ($status) {

        case 'PUBLISHED':
        case 'APPROVED':
            return 'bg-success';

        case 'PENDING':
            return 'bg-warning text-dark';

        case 'DRAFT':
            return 'bg-secondary';

        case 'INACTIVE':
            return 'bg-danger';

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
    กิจกรรม / โครงการ | PSU Photo System
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


/* ============================================================
   PAGE HEADER
============================================================ */

.page-title {

    color: #082d63;

    font-size: 24px;

    font-weight: 700;
}


.page-subtitle {

    color: #6b7280;

    font-size: 13px;

    margin-top: 3px;
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


.btn {

    border-radius: 8px;
}


/* ============================================================
   PROJECT CARD
============================================================ */

.project-card {

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


.project-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 10px 25px
        rgba(0, 0, 0, .10);
}


/* ============================================================
   COVER
============================================================ */

.cover {

    width: 100%;

    height: 210px;

    object-fit: cover;

    display: block;

    background: #eef1f5;
}


.cover-empty {

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
   PROJECT BODY
============================================================ */

.project-body {

    padding: 16px;
}


.project-name {

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


.project-meta {

    margin-top: 9px;

    color: #6b7280;

    font-size: 12px;
}


.project-meta i {

    width: 18px;

    text-align: center;
}


.project-category {

    color: #0d47a1;

    font-size: 12px;

    margin-top: 8px;
}


/* ============================================================
   FOOTER
============================================================ */

.project-footer {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 8px;

    margin-top: 14px;

    padding-top: 12px;

    border-top:
        1px solid #edf0f4;
}


.photo-count {

    color: #6b7280;

    font-size: 12px;

    white-space: nowrap;
}


.photo-count i {

    color: #0d6efd;
}


/* ============================================================
   ACTIONS
============================================================ */

.project-actions {

    display: flex;

    gap: 5px;
}


.project-actions .btn {

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

    .main {

        margin-left: 0;

        padding-left: 15px;

        padding-right: 15px;
    }


    .cover,
    .cover-empty {

        height: 180px;
    }

}

</style>

</head>


<body>


<!-- ============================================================
     NAVBAR
============================================================ -->

<?php

$navbarFile =
    __DIR__ .
    '/../includes/navbar.php';

if (file_exists($navbarFile)) {

    require_once $navbarFile;

}

?>


<!-- ============================================================
     SIDEBAR
============================================================ -->

<?php

$sidebarFile =
    __DIR__ .
    '/../includes/sidebar.php';

if (file_exists($sidebarFile)) {

    require_once $sidebarFile;

}

?>


<!-- ============================================================
     MAIN
============================================================ -->

<main class="main">


<!-- ============================================================
     HEADER
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

<div class="page-title">

    <i
        class="bi bi-calendar-event me-2"
    ></i>

    กิจกรรม / โครงการ

</div>


<div class="page-subtitle">

    จัดการกิจกรรมและโครงการสำหรับจัดเก็บภาพถ่าย

</div>

</div>


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF'
): ?>

<a
    href="create.php"
    class="btn btn-primary"
>

    <i
        class="bi bi-plus-lg me-1"
    ></i>

    เพิ่มกิจกรรม / โครงการ

</a>

<?php endif; ?>


</div>


<!-- ============================================================
     FILTER
============================================================ -->

<div class="filter-card mb-4">


<form
    method="GET"
    action="index.php"
>


<div class="row g-2">


<!-- Search -->

<div class="col-md-6">

<label
    class="form-label small fw-semibold"
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
    placeholder="ชื่อกิจกรรม หรือสถานที่..."
    value="<?= e($search) ?>"
>

</div>

</div>


<!-- Status -->

<div class="col-md-4">

<label
    class="form-label small fw-semibold"
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
    value="DRAFT"
    <?= $status === 'DRAFT'
        ? 'selected'
        : '' ?>
>

    แบบร่าง

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
    value="INACTIVE"
    <?= $status === 'INACTIVE'
        ? 'selected'
        : '' ?>
>

    ปิดใช้งาน

</option>

</select>

</div>


<!-- Search -->

<div
    class="
        col-md-2
        d-flex
        align-items-end
    "
>

<button
    type="submit"
    class="btn btn-primary w-100"
>

    <i
        class="bi bi-search me-1"
    ></i>

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

    รายการกิจกรรม / โครงการ

</strong>


<span
    class="badge bg-primary ms-2"
>

    <?= number_format(
        count($projects)
    ) ?>

</span>

</div>


<?php if (
    $search !== '' ||
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

    <i
        class="bi bi-x-circle me-1"
    ></i>

    ล้างตัวกรอง

</a>


<?php endif; ?>


</div>


<!-- ============================================================
     PROJECTS
============================================================ -->

<?php if (
    empty($projects)
): ?>


<div class="empty-box">


<i
    class="bi bi-calendar-x"
></i>


<div
    class="
        fw-semibold
        text-dark
    "
>

    ไม่พบกิจกรรม / โครงการ

</div>


<div class="small mt-1">

    ยังไม่มีข้อมูลตามเงื่อนไขที่ค้นหา

</div>


<?php if (
    $role === 'ADMIN' ||
    $role === 'STAFF'
): ?>


<a
    href="create.php"
    class="btn btn-primary mt-3"
>

    <i
        class="bi bi-plus-lg me-1"
    ></i>

    เพิ่มกิจกรรม / โครงการ

</a>


<?php endif; ?>


</div>


<?php else: ?>


<div class="row g-3">


<?php foreach (
    $projects as $project
): ?>


<?php

$coverUrl =
    imageUrl(
        $project['cover_path']
    );


$canManage = false;


// ADMIN สามารถจัดการทั้งหมด

if ($role === 'ADMIN') {

    $canManage = true;
}


// STAFF จัดการเฉพาะโครงการตัวเอง

if (
    $role === 'STAFF' &&
    (int) $project['created_by'] === $userId
) {

    $canManage = true;
}

?>


<div
    class="
        col-12
        col-md-6
        col-xl-4
    "
>


<div class="project-card">


<!-- ========================================================
     COVER
========================================================= -->

<?php if (
    $coverUrl !== ''
): ?>


<a
    href="view.php?id=<?= (int) $project['project_id'] ?>"
>

<img
    src="<?= e($coverUrl) ?>"
    class="cover"
    alt="<?= e(
        $project['project_name']
    ) ?>"
    loading="lazy"
    onerror="
        this.style.display='none';
        this.nextElementSibling.style.display='flex';
    "
>


<div
    class="cover-empty"
    style="display:none;"
>

    <i class="bi bi-images"></i>

</div>

</a>


<?php else: ?>


<a
    href="view.php?id=<?= (int) $project['project_id'] ?>"
    class="text-decoration-none"
>

<div class="cover-empty">

    <i class="bi bi-images"></i>

</div>

</a>


<?php endif; ?>


<!-- ========================================================
     BODY
========================================================= -->

<div class="project-body">


<!-- Status -->

<div class="mb-2">

<span
    class="
        badge
        <?= e(
            statusClass(
                $project['status']
            )
        ) ?>
    "
>

    <?= e(
        statusLabel(
            $project['status']
        )
    ) ?>

</span>

</div>


<!-- Name -->

<div
    class="project-name"
    title="<?= e(
        $project['project_name']
    ) ?>"
>

    <?= e(
        $project['project_name']
    ) ?>

</div>


<!-- Date -->

<div class="project-meta">

    <i class="bi bi-calendar3"></i>

    <?= !empty(
        $project['project_date']
    )
        ? e(
            $project['project_date']
        )
        : '-'
    ?>

</div>


<!-- Location -->

<?php if (
    !empty(
        $project['project_location']
    )
): ?>


<div class="project-meta">

    <i class="bi bi-geo-alt"></i>

    <?= e(
        $project['project_location']
    ) ?>

</div>


<?php endif; ?>


<!-- Category -->

<?php if (
    !empty(
        $project['category_name']
    )
): ?>


<div class="project-category">

    <i class="bi bi-tag me-1"></i>

    <?= e(
        $project['category_name']
    ) ?>

</div>


<?php endif; ?>


<!-- Creator -->

<?php if (
    !empty(
        $project['creator_name']
    )
): ?>


<div class="project-meta">

    <i class="bi bi-person"></i>

    <?= e(
        $project['creator_name']
    ) ?>

</div>


<?php endif; ?>


<!-- Footer -->

<div class="project-footer">


<div class="photo-count">

    <i class="bi bi-images me-1"></i>

    <?= number_format(
        (int) $project['photo_count']
    ) ?>

    ภาพ

</div>


<div class="project-actions">


<!-- View -->

<a
    href="view.php?id=<?= (int) $project['project_id'] ?>"
    class="
        btn
        btn-sm
        btn-outline-primary
    "
    title="ดูโครงการ"
>

    <i class="bi bi-eye"></i>

</a>


<?php if ($canManage): ?>


<!-- Upload -->

<a
    href="../photos/upload.php?project_id=<?= (int) $project['project_id'] ?>"
    class="
        btn
        btn-sm
        btn-primary
    "
    title="อัปโหลดภาพ"
>

    <i class="bi bi-cloud-upload"></i>

</a>


<!-- Edit -->

<a
    href="edit.php?id=<?= (int) $project['project_id'] ?>"
    class="
        btn
        btn-sm
        btn-warning
    "
    title="แก้ไข"
>

    <i class="bi bi-pencil"></i>

</a>


<!-- Delete -->

<a
    href="delete.php?id=<?= (int) $project['project_id'] ?>"
    class="
        btn
        btn-sm
        btn-danger
    "
    title="ลบ"
    onclick="
        return confirm(
            'ยืนยันการลบกิจกรรม/โครงการนี้หรือไม่?'
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