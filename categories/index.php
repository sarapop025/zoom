<?php
/**
 * ============================================================
 * categories/index.php
 * ============================================================
 * ระบบจัดการหมวดหมู่กิจกรรม / โครงการ
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

$fullName = isset($_SESSION['full_name'])
    ? $_SESSION['full_name']
    : 'ผู้ใช้งาน';

$role = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ============================================================
// Permission
// ============================================================

if ($role !== 'ADMIN') {

    http_response_code(403);

    exit('
        <div style="
            font-family:Arial;
            text-align:center;
            padding:60px;
        ">

            <h2>ไม่มีสิทธิ์เข้าถึงหน้านี้</h2>

            <p>เฉพาะผู้ดูแลระบบเท่านั้น</p>

            <a href="../dashboard/index.php">
                กลับ Dashboard
            </a>

        </div>
    ');
}


// ============================================================
// Search
// ============================================================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';


// ============================================================
// Status Filter
// ============================================================

$statusFilter = isset($_GET['status'])
    ? strtoupper(trim($_GET['status']))
    : '';


// ============================================================
// SQL
// ============================================================

$sql = "
    SELECT
        c.category_id,
        c.category_name,
        c.description,
        c.status,
        c.created_at,
        c.updated_at,

        (
            SELECT COUNT(*)
            FROM projects p
            WHERE p.category_id = c.category_id
        ) AS project_count

    FROM categories c

    WHERE 1 = 1
";

$params = array();


// ============================================================
// Search
// ============================================================

if ($search !== '') {

    $sql .= "
        AND (
            c.category_name LIKE ?
            OR c.description LIKE ?
        )
    ";

    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}


// ============================================================
// Status Filter
// ============================================================

if (
    $statusFilter === 'ACTIVE' ||
    $statusFilter === 'INACTIVE'
) {

    $sql .= "
        AND c.status = ?
    ";

    $params[] = $statusFilter;
}


// ============================================================
// Order
// ============================================================

$sql .= "
    ORDER BY
        c.category_id DESC
";


// ============================================================
// Execute
// ============================================================

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $categories = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    die(
        '<div style="
            font-family:Arial;
            padding:40px;
            color:#b91c1c;
        ">

            <h3>Database Error</h3>

            <pre>' .
            e($e->getMessage()) .
            '</pre>

        </div>'
    );
}


// ============================================================
// Statistics
// ============================================================

try {

    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_categories,

            SUM(
                CASE
                    WHEN status = 'ACTIVE'
                    THEN 1
                    ELSE 0
                END
            ) AS active_categories,

            SUM(
                CASE
                    WHEN status = 'INACTIVE'
                    THEN 1
                    ELSE 0
                END
            ) AS inactive_categories

        FROM categories
    ");

    $stats = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $stats = array(
        'total_categories' => 0,
        'active_categories' => 0,
        'inactive_categories' => 0
    );
}


$totalCategories = (int) (
    $stats['total_categories'] ?? 0
);

$activeCategories = (int) (
    $stats['active_categories'] ?? 0
);

$inactiveCategories = (int) (
    $stats['inactive_categories'] ?? 0
);

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
    จัดการหมวดหมู่ | PSU Photo System
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
   Navbar
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
   Sidebar
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
   Main
============================================================ */

.main {

    margin-left: 245px;

    padding:
        95px 28px 40px;

    min-height: 100vh;
}


/* ============================================================
   Cards
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
   Statistics
============================================================ */

.stat-card {

    padding: 20px;

    height: 100%;
}


.stat-icon {

    width: 50px;

    height: 50px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 23px;

    background: #eef5ff;

    color: #0d6efd;
}


.stat-number {

    margin-top: 12px;

    font-size: 28px;

    font-weight: 800;
}


.stat-label {

    color: #6b7280;

    font-size: 13px;
}


/* ============================================================
   Table
============================================================ */

.table {

    margin-bottom: 0;
}


.table th {

    padding:
        14px 16px;

    background: #f8fafc;

    color: #475569;

    font-size: 13px;

    font-weight: 700;

    white-space: nowrap;
}


.table td {

    padding:
        14px 16px;

    vertical-align: middle;

    font-size: 13px;
}


.category-name {

    font-weight: 700;

    color: #1e3a8a;
}


.description {

    max-width: 360px;

    color: #6b7280;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* ============================================================
   Action Button
============================================================ */

.btn-action {

    width: 36px;

    height: 36px;

    padding: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;
}


/* ============================================================
   Empty
============================================================ */

.empty-box {

    text-align: center;

    padding:
        70px 20px;

    color: #9ca3af;
}


.empty-box i {

    font-size: 55px;
}


/* ============================================================
   Responsive
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

    <?= e($fullName) ?>


    <span
        class="badge bg-light text-primary ms-2"
    >

        <?= e($role) ?>

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
    href="index.php"
    class="menu-link active"
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


<!-- ============================================================
     Header
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

<h3 class="mb-1">

    <i
        class="bi bi-tags me-2"
    ></i>

    จัดการหมวดหมู่

</h3>


<div class="text-muted small">

    จัดการหมวดหมู่สำหรับกิจกรรมและโครงการ

</div>

</div>


<a
    href="create.php"
    class="btn btn-primary"
>

    <i
        class="bi bi-plus-lg me-1"
    ></i>

    เพิ่มหมวดหมู่

</a>


</div>


<!-- ============================================================
     Alert
============================================================ -->

<?php if (
    isset($_GET['created'])
): ?>

<div
    class="alert alert-success alert-dismissible fade show"
>

    <i
        class="bi bi-check-circle me-1"
    ></i>

    เพิ่มหมวดหมู่เรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if (
    isset($_GET['updated'])
): ?>

<div
    class="alert alert-success alert-dismissible fade show"
>

    <i
        class="bi bi-check-circle me-1"
    ></i>

    แก้ไขข้อมูลหมวดหมู่เรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if (
    isset($_GET['deleted'])
): ?>

<div
    class="alert alert-success alert-dismissible fade show"
>

    <i
        class="bi bi-trash me-1"
    ></i>

    ลบหมวดหมู่เรียบร้อยแล้ว

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<!-- ============================================================
     Statistics
============================================================ -->

<div class="row g-3 mb-4">


<div class="col-md-4">

<div class="card-custom stat-card">

<div class="stat-icon">

    <i class="bi bi-tags"></i>

</div>


<div class="stat-number">

    <?= number_format(
        $totalCategories
    ) ?>

</div>


<div class="stat-label">

    หมวดหมู่ทั้งหมด

</div>

</div>

</div>


<div class="col-md-4">

<div class="card-custom stat-card">

<div class="stat-icon">

    <i class="bi bi-check-circle"></i>

</div>


<div class="stat-number text-success">

    <?= number_format(
        $activeCategories
    ) ?>

</div>


<div class="stat-label">

    หมวดหมู่ที่ใช้งาน

</div>

</div>

</div>


<div class="col-md-4">

<div class="card-custom stat-card">

<div class="stat-icon">

    <i class="bi bi-pause-circle"></i>

</div>


<div class="stat-number text-secondary">

    <?= number_format(
        $inactiveCategories
    ) ?>

</div>


<div class="stat-label">

    หมวดหมู่ที่ปิดใช้งาน

</div>

</div>

</div>


</div>


<!-- ============================================================
     Search
============================================================ -->

<div class="card-custom p-3 mb-4">


<form
    method="GET"
    action="index.php"
>


<div class="row g-2">


<div class="col-md-7">

<div class="input-group">


<span
    class="input-group-text bg-white"
>

    <i class="bi bi-search"></i>

</span>


<input
    type="text"
    name="search"
    class="form-control"
    placeholder="ค้นหาชื่อหมวดหมู่ หรือรายละเอียด..."
    value="<?= e($search) ?>"
>


</div>

</div>


<div class="col-md-3">

<select
    name="status"
    class="form-select"
>


<option value="">

    ทุกสถานะ

</option>


<option
    value="ACTIVE"
    <?= $statusFilter === 'ACTIVE'
        ? 'selected'
        : '' ?>
>

    ใช้งาน

</option>


<option
    value="INACTIVE"
    <?= $statusFilter === 'INACTIVE'
        ? 'selected'
        : '' ?>
>

    ปิดใช้งาน

</option>


</select>

</div>


<div class="col-md-2 d-flex gap-2">


<button
    type="submit"
    class="btn btn-primary flex-grow-1"
>

    <i
        class="bi bi-search me-1"
    ></i>

    ค้นหา

</button>


<a
    href="index.php"
    class="btn btn-outline-secondary"
    title="ล้าง"
>

    <i
        class="bi bi-arrow-counterclockwise"
    ></i>

</a>


</div>


</div>


</form>


</div>


<!-- ============================================================
     Table
============================================================ -->

<div class="card-custom">


<div class="table-responsive">


<table class="table table-hover">


<thead>

<tr>


<th style="width:70px;">

    #

</th>


<th>

    หมวดหมู่

</th>


<th>

    รายละเอียด

</th>


<th
    class="text-center"
    style="width:120px;"
>

    โครงการ

</th>


<th
    class="text-center"
    style="width:120px;"
>

    สถานะ

</th>


<th
    class="text-center"
    style="width:170px;"
>

    จัดการ

</th>


</tr>

</thead>


<tbody>


<?php if (
    empty($categories)
): ?>


<tr>

<td colspan="6">


<div class="empty-box">


<i
    class="bi bi-tags"
></i>


<div class="mt-3 fw-semibold">

    ไม่พบข้อมูลหมวดหมู่

</div>


<div class="small mt-1">

    ยังไม่มีหมวดหมู่
    หรือไม่พบข้อมูลจากการค้นหา

</div>


<a
    href="create.php"
    class="btn btn-primary mt-3"
>

    <i
        class="bi bi-plus-lg me-1"
    ></i>

    เพิ่มหมวดหมู่

</a>


</div>


</td>

</tr>


<?php else: ?>


<?php foreach (
    $categories
    as $index => $category
): ?>


<tr>


<!-- ======================================================
     Number
======================================================= -->

<td>

    <?= $index + 1 ?>

</td>


<!-- ======================================================
     Category
======================================================= -->

<td>


<div class="category-name">

    <i
        class="bi bi-tag-fill me-1"
    ></i>

    <?= e(
        $category['category_name']
    ) ?>

</div>


<div
    class="text-muted"
    style="font-size:11px;"
>

    ID:
    <?= (int) $category['category_id'] ?>

</div>


</td>


<!-- ======================================================
     Description
======================================================= -->

<td>


<?php if (
    !empty(
        $category['description']
    )
): ?>


<div
    class="description"
    title="<?= e(
        $category['description']
    ) ?>"
>

    <?= e(
        $category['description']
    ) ?>

</div>


<?php else: ?>


<span class="text-muted">

    ไม่มีรายละเอียด

</span>


<?php endif; ?>


</td>


<!-- ======================================================
     Project Count
======================================================= -->

<td class="text-center">


<?php

$projectCount =
    (int) $category['project_count'];

?>


<span
    class="
        badge
        <?= $projectCount > 0
            ? 'bg-primary'
            : 'bg-secondary'
        ?>
    "
>

    <?= number_format(
        $projectCount
    ) ?>

</span>


</td>


<!-- ======================================================
     Status
======================================================= -->

<td class="text-center">


<?php if (
    $category['status'] === 'ACTIVE'
): ?>


<span
    class="badge bg-success"
>

    <i
        class="bi bi-check-circle me-1"
    ></i>

    ใช้งาน

</span>


<?php else: ?>


<span
    class="badge bg-secondary"
>

    <i
        class="bi bi-pause-circle me-1"
    ></i>

    ปิดใช้งาน

</span>


<?php endif; ?>


</td>


<!-- ======================================================
     Actions
======================================================= -->

<td class="text-center">


<!-- Edit -->

<a
    href="edit.php?id=<?= (int) $category['category_id'] ?>"
    class="btn btn-warning btn-sm btn-action"
    title="แก้ไข"
>

    <i class="bi bi-pencil"></i>

</a>


<!-- Toggle -->

<a
    href="edit.php?id=<?= (int) $category['category_id'] ?>&action=toggle"
    class="btn btn-outline-secondary btn-sm btn-action"
    title="เปลี่ยนสถานะ"
    onclick="
        return confirm(
            'ต้องการเปลี่ยนสถานะหมวดหมู่นี้หรือไม่?'
        );
    "
>

    <i class="bi bi-power"></i>

</a>


<!-- Delete -->

<a
    href="delete.php?id=<?= (int) $category['category_id'] ?>"
    class="btn btn-danger btn-sm btn-action"
    title="ลบหมวดหมู่"
    onclick="
        return confirm(
            'ยืนยันการลบหมวดหมู่นี้หรือไม่?\\n\\nหากมีโครงการใช้งานอยู่ ระบบจะไม่อนุญาตให้ลบ'
        );
    "
>

    <i class="bi bi-trash"></i>

</a>


</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


<!-- ============================================================
     Footer
============================================================ -->

<div
    class="
        text-center
        text-muted
        small
        mt-4
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