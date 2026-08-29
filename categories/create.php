<?php
/**
 * ============================================================
 * categories/create.php
 * ============================================================
 * เพิ่มหมวดหมู่กิจกรรม / โครงการ
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

            <p>
                เฉพาะผู้ดูแลระบบเท่านั้น
            </p>

            <a href="../dashboard/index.php">
                กลับ Dashboard
            </a>

        </div>
    ');
}


// ============================================================
// Variables
// ============================================================

$categoryName = '';

$description = '';

$status = 'ACTIVE';

$errors = array();


// ============================================================
// POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $categoryName =
        isset($_POST['category_name'])
            ? trim($_POST['category_name'])
            : '';

    $description =
        isset($_POST['description'])
            ? trim($_POST['description'])
            : '';

    $status =
        isset($_POST['status'])
            ? strtoupper(trim($_POST['status']))
            : 'ACTIVE';


    // ========================================================
    // Validation
    // ========================================================

    if ($categoryName === '') {

        $errors[] =
            'กรุณากรอกชื่อหมวดหมู่';
    }


    if (mb_strlen($categoryName, 'UTF-8') > 150) {

        $errors[] =
            'ชื่อหมวดหมู่ต้องไม่เกิน 150 ตัวอักษร';
    }


    if (
        !in_array(
            $status,
            array(
                'ACTIVE',
                'INACTIVE'
            ),
            true
        )
    ) {

        $errors[] =
            'สถานะไม่ถูกต้อง';

        $status = 'ACTIVE';
    }


    // ========================================================
    // ตรวจชื่อซ้ำ
    // ========================================================

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT category_id
                FROM categories
                WHERE category_name = ?
                LIMIT 1
            ");

            $stmt->execute([
                $categoryName
            ]);


            if ($stmt->fetch(PDO::FETCH_ASSOC)) {

                $errors[] =
                    'มีหมวดหมู่ชื่อนี้อยู่แล้ว';

            }

        } catch (PDOException $e) {

            $errors[] =
                'ไม่สามารถตรวจสอบข้อมูลได้: ' .
                $e->getMessage();
        }
    }


    // ========================================================
    // INSERT
    // ========================================================

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO categories
                (
                    category_name,
                    description,
                    status
                )
                VALUES
                (
                    :category_name,
                    :description,
                    :status
                )
            ");


            $stmt->execute([

                ':category_name' =>
                    $categoryName,

                ':description' =>
                    $description !== ''
                        ? $description
                        : null,

                ':status' =>
                    $status

            ]);


            // ==================================================
            // Success
            // ==================================================

            header(
                'Location: index.php?created=1'
            );

            exit;


        } catch (PDOException $e) {

            $errors[] =
                'ไม่สามารถบันทึกหมวดหมู่ได้: ' .
                $e->getMessage();
        }
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
    เพิ่มหมวดหมู่ | PSU Photo System
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


/* ==========================================================
   Navbar
========================================================== */

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


/* ==========================================================
   Main
========================================================== */

.main {

    max-width: 900px;

    margin: 0 auto;

    padding:
        100px 20px 40px;
}


/* ==========================================================
   Card
========================================================== */

.form-card {

    background: #fff;

    border:
        1px solid #e5e7eb;

    border-radius: 14px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);

    overflow: hidden;
}


.form-header {

    padding:
        22px 25px;

    border-bottom:
        1px solid #e5e7eb;

    background: #fff;
}


.form-body {

    padding: 25px;
}


/* ==========================================================
   Form
========================================================== */

.form-label {

    font-weight: 600;

    color: #374151;
}


.form-control,
.form-select {

    min-height: 46px;

    border-radius: 9px;

    border-color: #d1d5db;
}


.form-control:focus,
.form-select:focus {

    border-color: #0d6efd;

    box-shadow:
        0 0 0 .2rem
        rgba(13,110,253,.10);
}


textarea.form-control {

    min-height: 130px;

    resize: vertical;
}


/* ==========================================================
   Buttons
========================================================== */

.btn {

    border-radius: 8px;
}


/* ==========================================================
   Info
========================================================== */

.info-box {

    background: #f8fafc;

    border:
        1px solid #e5e7eb;

    border-radius: 10px;

    padding: 14px;

    color: #64748b;

    font-size: 13px;
}


/* ==========================================================
   Responsive
========================================================== */

@media (
    max-width: 576px
) {

    .main {

        padding:
            90px 12px 30px;
    }


    .form-body {

        padding: 18px;
    }

}

</style>

</head>


<body>


<!-- ==========================================================
     NAVBAR
=========================================================== -->

<nav class="navbar navbar-dark fixed-top">

<div class="container-fluid">


<a
    href="index.php"
    class="navbar-brand"
>

    <i
        class="bi bi-tags me-2"
    ></i>

    PSU Photo System

</a>


<div class="text-white small">

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


<!-- ==========================================================
     MAIN
=========================================================== -->

<main class="main">


<!-- ==========================================================
     Header
=========================================================== -->

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
        class="bi bi-plus-circle me-2"
    ></i>

    เพิ่มหมวดหมู่

</h3>


<div class="text-muted small">

    เพิ่มหมวดหมู่สำหรับกิจกรรมและโครงการ

</div>

</div>


<a
    href="index.php"
    class="btn btn-outline-secondary"
>

    <i
        class="bi bi-arrow-left me-1"
    ></i>

    กลับรายการ

</a>


</div>


<!-- ==========================================================
     FORM CARD
=========================================================== -->

<div class="form-card">


<div class="form-header">

<strong>

    <i
        class="bi bi-tag me-2"
    ></i>

    ข้อมูลหมวดหมู่

</strong>

</div>


<div class="form-body">


<!-- ========================================================
     Errors
========================================================= -->

<?php if (!empty($errors)): ?>

<div
    class="alert alert-danger"
>

<div class="fw-semibold mb-1">

    <i
        class="bi bi-exclamation-triangle me-1"
    ></i>

    ไม่สามารถบันทึกข้อมูลได้

</div>


<?php foreach (
    $errors
    as $error
): ?>

<div>

    <?= e($error) ?>

</div>

<?php endforeach; ?>


</div>

<?php endif; ?>


<!-- ========================================================
     Form
========================================================= -->

<form
    method="POST"
    action="create.php"
    autocomplete="off"
>


<!-- Category Name -->

<div class="mb-4">

<label
    for="category_name"
    class="form-label"
>

    ชื่อหมวดหมู่

    <span class="text-danger">
        *
    </span>

</label>


<input
    type="text"
    id="category_name"
    name="category_name"
    class="form-control"
    maxlength="150"
    value="<?= e($categoryName) ?>"
    placeholder="เช่น กิจกรรมวิชาการ"
    required
>


<div class="form-text">

    ชื่อหมวดหมู่ไม่เกิน 150 ตัวอักษร

</div>

</div>


<!-- Description -->

<div class="mb-4">

<label
    for="description"
    class="form-label"
>

    รายละเอียด

</label>


<textarea
    id="description"
    name="description"
    class="form-control"
    placeholder="รายละเอียดของหมวดหมู่ เช่น ใช้สำหรับกิจกรรมด้านวิชาการ การเรียนรู้ หรือการแข่งขัน..."
><?= e($description) ?></textarea>


<div class="form-text">

    สามารถเว้นว่างได้

</div>

</div>


<!-- Status -->

<div class="mb-4">

<label
    for="status"
    class="form-label"
>

    สถานะ

</label>


<select
    id="status"
    name="status"
    class="form-select"
>


<option
    value="ACTIVE"
    <?= $status === 'ACTIVE'
        ? 'selected'
        : '' ?>
>

    เปิดใช้งาน

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


<div class="form-text">

    หมวดหมู่ที่เปิดใช้งานจะสามารถนำไปเลือกใช้กับกิจกรรม/โครงการได้

</div>

</div>


<!-- Info -->

<div class="info-box mb-4">

    <i
        class="bi bi-info-circle me-1"
    ></i>

    หลังจากสร้างหมวดหมู่แล้ว
    สามารถนำไปใช้ในการจัดกลุ่มกิจกรรม
    และค้นหาภาพถ่ายได้ง่ายขึ้น

</div>


<!-- Buttons -->

<div
    class="
        d-flex
        justify-content-between
        gap-2
    "
>


<a
    href="index.php"
    class="btn btn-outline-secondary"
>

    <i
        class="bi bi-x-circle me-1"
    ></i>

    ยกเลิก

</a>


<button
    type="submit"
    class="btn btn-primary px-4"
>

    <i
        class="bi bi-save me-1"
    ></i>

    บันทึกหมวดหมู่

</button>


</div>


</form>


</div>


</div>


<!-- ==========================================================
     Footer
=========================================================== -->

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


</body>

</html>
