<?php
/**
 * ============================================================
 * users/create.php
 * ============================================================
 * เพิ่มผู้ใช้งาน
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

$username = '';

$fullNameInput = '';

$email = '';

$userRole = 'VIEWER';

$status = 'ACTIVE';

$errors = array();


// ============================================================
// POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username =
        isset($_POST['username'])
            ? trim($_POST['username'])
            : '';

    $password =
        isset($_POST['password'])
            ? $_POST['password']
            : '';

    $confirmPassword =
        isset($_POST['confirm_password'])
            ? $_POST['confirm_password']
            : '';

    $fullNameInput =
        isset($_POST['full_name'])
            ? trim($_POST['full_name'])
            : '';

    $email =
        isset($_POST['email'])
            ? trim($_POST['email'])
            : '';

    $userRole =
        isset($_POST['role'])
            ? strtoupper(
                trim($_POST['role'])
            )
            : 'VIEWER';

    $status =
        isset($_POST['status'])
            ? strtoupper(
                trim($_POST['status'])
            )
            : 'ACTIVE';


    // ========================================================
    // Validation
    // ========================================================

    if ($username === '') {

        $errors[] =
            'กรุณากรอก Username';
    }


    if (
        strlen($username) < 3
    ) {

        $errors[] =
            'Username ต้องมีอย่างน้อย 3 ตัวอักษร';
    }


    if (
        strlen($username) > 100
    ) {

        $errors[] =
            'Username ต้องไม่เกิน 100 ตัวอักษร';
    }


    // อนุญาตเฉพาะตัวอักษร ตัวเลข . _ -

    if (
        $username !== '' &&
        !preg_match(
            '/^[A-Za-z0-9._-]+$/',
            $username
        )
    ) {

        $errors[] =
            'Username ใช้ได้เฉพาะ A-Z, a-z, 0-9, จุด, ขีดกลาง และ _';
    }


    // Password

    if ($password === '') {

        $errors[] =
            'กรุณากรอกรหัสผ่าน';
    }


    if (
        strlen($password) < 6
    ) {

        $errors[] =
            'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }


    if (
        $password !== $confirmPassword
    ) {

        $errors[] =
            'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    }


    // Full name

    if ($fullNameInput === '') {

        $errors[] =
            'กรุณากรอกชื่อ-นามสกุล';
    }


    if (
        mb_strlen(
            $fullNameInput,
            'UTF-8'
        ) > 150
    ) {

        $errors[] =
            'ชื่อ-นามสกุลต้องไม่เกิน 150 ตัวอักษร';
    }


    // Email

    if (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            'รูปแบบ Email ไม่ถูกต้อง';
    }


    if (
        mb_strlen(
            $email,
            'UTF-8'
        ) > 150
    ) {

        $errors[] =
            'Email ต้องไม่เกิน 150 ตัวอักษร';
    }


    // Role

    $allowedRoles = array(
        'ADMIN',
        'STAFF',
        'VIEWER',
        'EXECUTIVE'
    );


    if (
        !in_array(
            $userRole,
            $allowedRoles,
            true
        )
    ) {

        $errors[] =
            'สิทธิ์ผู้ใช้งานไม่ถูกต้อง';

        $userRole = 'VIEWER';
    }


    // Status

    $allowedStatuses = array(
        'ACTIVE',
        'INACTIVE'
    );


    if (
        !in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {

        $errors[] =
            'สถานะไม่ถูกต้อง';

        $status = 'ACTIVE';
    }


    // ========================================================
    // Check Username Duplicate
    // ========================================================

    if (
        empty($errors)
    ) {

        try {

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username
            ]);


            if (
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                )
            ) {

                $errors[] =
                    'Username นี้มีผู้ใช้งานแล้ว';
            }

        } catch (
            PDOException $e
        ) {

            $errors[] =
                'ไม่สามารถตรวจสอบ Username ได้: ' .
                $e->getMessage();
        }
    }


    // ========================================================
    // Check Email Duplicate
    // ========================================================

    if (
        empty($errors) &&
        $email !== ''
    ) {

        try {

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);


            if (
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                )
            ) {

                $errors[] =
                    'Email นี้ถูกใช้งานแล้ว';
            }

        } catch (
            PDOException $e
        ) {

            $errors[] =
                'ไม่สามารถตรวจสอบ Email ได้: ' .
                $e->getMessage();
        }
    }


    // ========================================================
    // INSERT
    // ========================================================

    if (
        empty($errors)
    ) {

        try {

            /*
             * สำคัญ:
             * ห้ามเก็บ Password แบบ Plain Text
             */

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    username,
                    password,
                    full_name,
                    email,
                    role,
                    status
                )
                VALUES
                (
                    :username,
                    :password,
                    :full_name,
                    :email,
                    :role,
                    :status
                )
            ");


            $stmt->execute([

                ':username' =>
                    $username,

                ':password' =>
                    $passwordHash,

                ':full_name' =>
                    $fullNameInput,

                ':email' =>
                    $email !== ''
                        ? $email
                        : null,

                ':role' =>
                    $userRole,

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


        } catch (
            PDOException $e
        ) {

            $errors[] =
                'ไม่สามารถเพิ่มผู้ใช้งานได้: ' .
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
    เพิ่มผู้ใช้งาน | PSU Photo System
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
   Main
============================================================ */

.main {

    max-width: 900px;

    margin: 0 auto;

    padding:
        100px 20px 40px;
}


/* ============================================================
   Card
============================================================ */

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
}


.form-body {

    padding: 25px;
}


/* ============================================================
   Form
============================================================ */

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


.password-help {

    font-size: 12px;

    color: #6b7280;
}


/* ============================================================
   Role Information
============================================================ */

.role-info {

    background: #f8fafc;

    border:
        1px solid #e5e7eb;

    border-radius: 10px;

    padding: 15px;

    margin-top: 10px;
}


.role-item {

    font-size: 13px;

    margin-bottom: 6px;
}


.role-item:last-child {

    margin-bottom: 0;
}


/* ============================================================
   Button
============================================================ */

.btn {

    border-radius: 8px;
}


/* ============================================================
   Responsive
============================================================ */

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


<!-- ============================================================
     NAVBAR
============================================================ -->

<nav class="navbar navbar-dark fixed-top">

<div class="container-fluid">


<a
    href="index.php"
    class="navbar-brand"
>

    <i
        class="bi bi-people me-2"
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
        class="bi bi-person-plus me-2"
    ></i>

    เพิ่มผู้ใช้งาน

</h3>


<div class="text-muted small">

    สร้างบัญชีผู้ใช้งานใหม่สำหรับระบบ

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


<!-- ============================================================
     Form Card
============================================================ -->

<div class="form-card">


<div class="form-header">

<strong>

    <i
        class="bi bi-person-badge me-2"
    ></i>

    ข้อมูลผู้ใช้งาน

</strong>

</div>


<div class="form-body">


<!-- ============================================================
     Errors
============================================================ -->

<?php if (
    !empty($errors)
): ?>

<div
    class="alert alert-danger"
>

<div class="fw-semibold mb-2">

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


<!-- ============================================================
     Form
============================================================ -->

<form
    method="POST"
    action="create.php"
    autocomplete="off"
>


<!-- Username -->

<div class="mb-4">

<label
    for="username"
    class="form-label"
>

    Username

    <span class="text-danger">
        *
    </span>

</label>


<input
    type="text"
    id="username"
    name="username"
    class="form-control"
    maxlength="100"
    value="<?= e($username) ?>"
    placeholder="เช่น admin"
    autocomplete="username"
    required
>


<div class="form-text">

    ใช้ A-Z, a-z, 0-9, จุด (.), ขีด (-) และ _

</div>

</div>


<!-- Password -->

<div class="row g-3 mb-4">


<div class="col-md-6">

<label
    for="password"
    class="form-label"
>

    รหัสผ่าน

    <span class="text-danger">
        *
    </span>

</label>


<input
    type="password"
    id="password"
    name="password"
    class="form-control"
    minlength="6"
    autocomplete="new-password"
    required
>


<div class="password-help mt-1">

    อย่างน้อย 6 ตัวอักษร

</div>

</div>


<div class="col-md-6">

<label
    for="confirm_password"
    class="form-label"
>

    ยืนยันรหัสผ่าน

    <span class="text-danger">
        *
    </span>

</label>


<input
    type="password"
    id="confirm_password"
    name="confirm_password"
    class="form-control"
    minlength="6"
    autocomplete="new-password"
    required
>

</div>


</div>


<!-- Full Name -->

<div class="mb-4">

<label
    for="full_name"
    class="form-label"
>

    ชื่อ-นามสกุล

    <span class="text-danger">
        *
    </span>

</label>


<input
    type="text"
    id="full_name"
    name="full_name"
    class="form-control"
    maxlength="150"
    value="<?= e($fullNameInput) ?>"
    placeholder="เช่น แวอามีน รักอำนวยศิลป์"
    required
>

</div>


<!-- Email -->

<div class="mb-4">

<label
    for="email"
    class="form-label"
>

    Email

</label>


<input
    type="email"
    id="email"
    name="email"
    class="form-control"
    maxlength="150"
    value="<?= e($email) ?>"
    placeholder="example@psu.ac.th"
    autocomplete="email"
>


</div>


<!-- Role -->

<div class="mb-4">

<label
    for="role"
    class="form-label"
>

    สิทธิ์ผู้ใช้งาน

    <span class="text-danger">
        *
    </span>

</label>


<select
    id="role"
    name="role"
    class="form-select"
>


<option
    value="ADMIN"
    <?= $userRole === 'ADMIN'
        ? 'selected'
        : '' ?>
>

    ผู้ดูแลระบบ

</option>


<option
    value="STAFF"
    <?= $userRole === 'STAFF'
        ? 'selected'
        : '' ?>
>

    เจ้าหน้าที่

</option>


<option
    value="VIEWER"
    <?= $userRole === 'VIEWER'
        ? 'selected'
        : '' ?>
>

    ผู้ดูเว็บไซต์

</option>


<option
    value="EXECUTIVE"
    <?= $userRole === 'EXECUTIVE'
        ? 'selected'
        : '' ?>
>

    ผู้บริหาร

</option>


</select>


<!-- Role Description -->

<div class="role-info">


<div class="role-item">

    <span class="badge bg-danger me-1">
        ADMIN
    </span>

    จัดการผู้ใช้ หมวดหมู่ กิจกรรม และภาพทั้งหมด

</div>


<div class="role-item">

    <span class="badge bg-primary me-1">
        STAFF
    </span>

    เพิ่ม/แก้ไข/จัดการภาพของกิจกรรมที่รับผิดชอบ

</div>


<div class="role-item">

    <span class="badge bg-secondary me-1">
        VIEWER
    </span>

    ดูและดาวน์โหลดภาพที่เผยแพร่

</div>


<div class="role-item">

    <span class="badge bg-success me-1">
        EXECUTIVE
    </span>

    ดูภาพและสถิติภาพรวม

</div>


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

    ใช้งาน

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

    ผู้ใช้ที่ปิดใช้งานจะไม่สามารถเข้าสู่ระบบได้

</div>

</div>


<!-- ============================================================
     Buttons
============================================================ -->

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
        class="bi bi-person-plus me-1"
    ></i>

    สร้างผู้ใช้งาน

</button>


</div>


</form>


</div>


</div>


<!-- Footer -->

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
