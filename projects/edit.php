<?php
/**
 * ============================================================
 * projects/edit.php
 * ============================================================
 * แก้ไขกิจกรรม / โครงการ
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
    ? strtoupper($_SESSION['role'])
    : 'STAFF';


// ============================================================
// Project ID
// ============================================================

$projectId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($projectId <= 0) {

    header('Location: index.php');
    exit;
}


// ============================================================
// โหลด Project
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        project_id,
        project_name,
        project_date,
        project_location,
        category_id,
        created_by,
        status,
        created_at
    FROM projects
    WHERE project_id = ?
    LIMIT 1
");

$stmt->execute([
    $projectId
]);

$project = $stmt->fetch(PDO::FETCH_ASSOC);


// ============================================================
// ไม่พบ Project
// ============================================================

if (!$project) {

    http_response_code(404);

    exit('
        <div style="
            font-family:Arial;
            padding:50px;
            text-align:center;
        ">
            <h2>ไม่พบกิจกรรม / โครงการ</h2>

            <a href="index.php">
                กลับรายการกิจกรรม
            </a>
        </div>
    ');
}


// ============================================================
// ตรวจสอบสิทธิ์
// ============================================================

$canEdit = false;


// ADMIN แก้ไขได้ทั้งหมด

if ($role === 'ADMIN') {

    $canEdit = true;
}


// STAFF แก้ไขเฉพาะโครงการที่ตัวเองสร้าง

if (
    $role === 'STAFF' &&
    (int) $project['created_by'] === $userId
) {

    $canEdit = true;
}


if (!$canEdit) {

    http_response_code(403);

    exit('
        <div style="
            font-family:Arial;
            padding:50px;
            text-align:center;
        ">
            <h2>ไม่มีสิทธิ์แก้ไขกิจกรรมนี้</h2>

            <p>
                คุณสามารถแก้ไขได้เฉพาะกิจกรรมที่คุณรับผิดชอบ
            </p>

            <a href="index.php">
                กลับรายการกิจกรรม
            </a>
        </div>
    ');
}


// ============================================================
// ค่าเริ่มต้น
// ============================================================

$projectName =
    $project['project_name'];

$projectDate =
    $project['project_date'];

$projectLocation =
    $project['project_location'];

$categoryId =
    (int) $project['category_id'];

$status =
    $project['status'];

$errors = array();


// ============================================================
// Categories
// ============================================================

$categories = array();

try {

    $stmt = $pdo->query("
        SELECT
            category_id,
            category_name
        FROM categories
        WHERE status = 'ACTIVE'
        ORDER BY category_name ASC
    ");

    $categories =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $errors[] =
        'ไม่สามารถโหลดหมวดหมู่ได้';
}


// ============================================================
// POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // --------------------------------------------------------
    // รับข้อมูล
    // --------------------------------------------------------

    $projectName =
        isset($_POST['project_name'])
            ? trim($_POST['project_name'])
            : '';

    $projectDate =
        isset($_POST['project_date'])
            ? trim($_POST['project_date'])
            : '';

    $projectLocation =
        isset($_POST['project_location'])
            ? trim($_POST['project_location'])
            : '';

    $categoryId =
        isset($_POST['category_id'])
            ? (int) $_POST['category_id']
            : 0;

    $status =
        isset($_POST['status'])
            ? trim($_POST['status'])
            : 'DRAFT';


    // --------------------------------------------------------
    // Validation
    // --------------------------------------------------------

    if ($projectName === '') {

        $errors[] =
            'กรุณาระบุชื่อกิจกรรม / โครงการ';
    }


    if ($projectDate === '') {

        $errors[] =
            'กรุณาระบุวันที่จัดกิจกรรม';
    }


    if ($categoryId <= 0) {

        $errors[] =
            'กรุณาเลือกหมวดหมู่';
    }


    // --------------------------------------------------------
    // Status ที่อนุญาต
    // --------------------------------------------------------

    $allowedStatus = array(
        'DRAFT',
        'PENDING',
        'APPROVED',
        'PUBLISHED',
        'REJECTED',
        'INACTIVE'
    );


    if (
        !in_array(
            $status,
            $allowedStatus,
            true
        )
    ) {

        $errors[] =
            'สถานะไม่ถูกต้อง';

        $status = 'DRAFT';
    }


    // --------------------------------------------------------
    // STAFF ไม่ควรเปลี่ยนเป็น APPROVED/PUBLISHED เอง
    // --------------------------------------------------------

    if (
        $role === 'STAFF' &&
        (
            $status === 'APPROVED' ||
            $status === 'PUBLISHED'
        )
    ) {

        $errors[] =
            'เจ้าหน้าที่ไม่สามารถตั้งสถานะเป็นอนุมัติหรือเผยแพร่ได้';
    }


    // --------------------------------------------------------
    // ตรวจ Category
    // --------------------------------------------------------

    if ($categoryId > 0) {

        $stmt = $pdo->prepare("
            SELECT category_id
            FROM categories
            WHERE category_id = ?
            AND status = 'ACTIVE'
            LIMIT 1
        ");

        $stmt->execute([
            $categoryId
        ]);


        if (!$stmt->fetch()) {

            $errors[] =
                'ไม่พบหมวดหมู่ที่เลือก';
        }
    }


    // --------------------------------------------------------
    // UPDATE
    // --------------------------------------------------------

    if (empty($errors)) {

        try {

            $sql = "
                UPDATE projects
                SET
                    project_name = ?,
                    project_date = ?,
                    project_location = ?,
                    category_id = ?,
                    status = ?
                WHERE project_id = ?
            ";


            $stmt =
                $pdo->prepare($sql);


            $stmt->execute([

                $projectName,

                $projectDate,

                $projectLocation,

                $categoryId,

                $status,

                $projectId

            ]);


            header(
                'Location: view.php?id=' .
                $projectId .
                '&updated=1'
            );

            exit;


        } catch (PDOException $e) {

            $errors[] =
                'ไม่สามารถแก้ไขข้อมูลได้: ' .
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
        แก้ไขกิจกรรม / โครงการ | PSU Photo System
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


        /* ==================================================
           Navbar
        ================================================== */

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


        /* ==================================================
           Sidebar
        ================================================== */

        .sidebar {

            position: fixed;

            left: 0;

            top: 68px;

            bottom: 0;

            width: 240px;

            background: #fff;

            border-right: 1px solid #e5e7eb;

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

            padding: 11px 12px;

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


        /* ==================================================
           Main
        ================================================== */

        .main {

            margin-left: 240px;

            padding:
                95px
                25px
                30px;

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


        /* ==================================================
           Card
        ================================================== */

        .card-custom {

            background: #fff;

            border: 1px solid #e5e7eb;

            border-radius: 13px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, .04);
        }


        .card-header-custom {

            padding: 18px;

            border-bottom: 1px solid #e5e7eb;

            font-weight: 700;

            color: #17395f;
        }


        .card-body-custom {

            padding: 22px;
        }


        /* ==================================================
           Form
        ================================================== */

        .form-label {

            font-size: 13px;

            font-weight: 600;

            color: #374151;
        }


        .required {

            color: #dc3545;
        }


        .form-control,
        .form-select {

            min-height: 44px;

            border-radius: 8px;

            border-color: #d9dee7;
        }


        .form-control:focus,
        .form-select:focus {

            border-color: #0d6efd;

            box-shadow:
                0 0 0 .2rem
                rgba(13, 110, 253, .1);
        }


        .help-text {

            color: #8a94a3;

            font-size: 11px;

            margin-top: 5px;
        }


        .readonly-box {

            background: #f3f4f6;
        }


        /* ==================================================
           Info
        ================================================== */

        .project-info {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 10px;

            margin-right: 5px;

            margin-bottom: 5px;

            border-radius: 6px;

            background: #f3f6fa;

            color: #657184;

            font-size: 11px;
        }


        /* ==================================================
           Mobile
        ================================================== */

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

        }

    </style>

</head>


<body>


<!-- ======================================================
     NAVBAR
======================================================= -->

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


            <span class="badge bg-light text-primary ms-2">

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


<!-- ======================================================
     SIDEBAR
======================================================= -->

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
        href="index.php"
        class="menu-link active"
    >

        <i class="bi bi-calendar-event"></i>

        กิจกรรม / โครงการ

    </a>


    <a
        href="../photos/index.php"
        class="menu-link"
    >

        <i class="bi bi-images"></i>

        คลังภาพ

    </a>


    <?php if (
        $role === 'ADMIN' ||
        $role === 'STAFF'
    ): ?>

        <a
            href="../photos/upload.php"
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


<!-- ======================================================
     MAIN
======================================================= -->

<main class="main">


    <!-- Header -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <div class="page-title">

                แก้ไขกิจกรรม / โครงการ

            </div>


            <div class="page-subtitle">

                แก้ไขข้อมูลกิจกรรมหรือโครงการ

            </div>

        </div>


        <a
            href="view.php?id=<?= $projectId ?>"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-eye me-1"></i>

            ดูรายละเอียด

        </a>


    </div>


    <!-- ==================================================
         Error
    =================================================== -->

    <?php if (!empty($errors)): ?>


        <div
            class="alert alert-danger"
            role="alert"
        >

            <div class="fw-bold mb-1">

                <i
                    class="bi bi-exclamation-triangle me-1"
                ></i>

                ไม่สามารถบันทึกข้อมูลได้

            </div>


            <ul class="mb-0">

                <?php foreach (
                    $errors
                    as $error
                ): ?>

                    <li>

                        <?= e($error) ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>


    <?php endif; ?>


    <!-- ==================================================
         Project Info
    =================================================== -->

    <div class="mb-3">


        <span class="project-info">

            <i class="bi bi-hash"></i>

            ID:
            <?= $projectId ?>

        </span>


        <span class="project-info">

            <i class="bi bi-person"></i>

            ผู้สร้าง:
            <?= e(
                $project['created_by']
            ) ?>

        </span>


        <span class="project-info">

            <i class="bi bi-clock"></i>

            สร้างเมื่อ:
            <?= e(
                $project['created_at']
            ) ?>

        </span>


    </div>


    <!-- ==================================================
         Form
    =================================================== -->

    <div class="card-custom">


        <div class="card-header-custom">

            <i class="bi bi-pencil-square me-2"></i>

            ข้อมูลกิจกรรม / โครงการ

        </div>


        <div class="card-body-custom">


            <form
                method="POST"
                action="edit.php?id=<?= $projectId ?>"
            >


                <div class="row g-3">


                    <!-- Project Name -->

                    <div class="col-12">

                        <label
                            class="form-label"
                            for="project_name"
                        >

                            ชื่อกิจกรรม / โครงการ

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="project_name"
                            name="project_name"
                            class="form-control"
                            maxlength="255"
                            value="<?= e($projectName) ?>"
                            required
                        >

                    </div>


                    <!-- Date -->

                    <div class="col-md-6">

                        <label
                            class="form-label"
                            for="project_date"
                        >

                            วันที่จัดกิจกรรม

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="date"
                            id="project_date"
                            name="project_date"
                            class="form-control"
                            value="<?= e($projectDate) ?>"
                            required
                        >

                    </div>


                    <!-- Category -->

                    <div class="col-md-6">

                        <label
                            class="form-label"
                            for="category_id"
                        >

                            หมวดหมู่

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            id="category_id"
                            name="category_id"
                            class="form-select"
                            required
                        >

                            <option value="0">

                                -- เลือกหมวดหมู่ --

                            </option>


                            <?php foreach (
                                $categories
                                as $category
                            ): ?>


                                <option
                                    value="<?= (int) $category['category_id'] ?>"
                                    <?= (
                                        $categoryId ==
                                        $category['category_id']
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= e(
                                        $category['category_name']
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <!-- Location -->

                    <div class="col-12">

                        <label
                            class="form-label"
                            for="project_location"
                        >

                            สถานที่จัดกิจกรรม

                        </label>


                        <input
                            type="text"
                            id="project_location"
                            name="project_location"
                            class="form-control"
                            maxlength="255"
                            value="<?= e($projectLocation) ?>"
                            placeholder="เช่น หอประชุมโรงเรียน"
                        >

                    </div>


                    <!-- Status -->

                    <div class="col-md-6">

                        <label
                            class="form-label"
                            for="status"
                        >

                            สถานะ

                        </label>


                        <select
                            id="status"
                            name="status"
                            class="form-select"
                        >


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


                            <?php if (
                                $role === 'ADMIN'
                            ): ?>


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


                                <option
                                    value="INACTIVE"
                                    <?= $status === 'INACTIVE'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    ปิดใช้งาน

                                </option>


                            <?php endif; ?>


                        </select>


                        <?php if (
                            $role === 'STAFF'
                        ): ?>

                            <div class="help-text">

                                เจ้าหน้าที่สามารถแก้ไขข้อมูล
                                และส่งโครงการเพื่อตรวจสอบได้

                            </div>

                        <?php endif; ?>


                    </div>


                    <!-- Creator -->

                    <div class="col-md-6">

                        <label class="form-label">

                            ผู้รับผิดชอบ

                        </label>


                        <input
                            type="text"
                            class="form-control readonly-box"
                            value="<?= e($fullName) ?>"
                            readonly
                        >


                        <div class="help-text">

                            ผู้สร้างโครงการไม่สามารถเปลี่ยนจากหน้านี้ได้

                        </div>

                    </div>


                </div>


                <!-- Buttons -->

                <hr class="my-4">


                <div
                    class="d-flex justify-content-between"
                >


                    <a
                        href="index.php"
                        class="btn btn-outline-secondary"
                    >

                        <i
                            class="bi bi-arrow-left me-1"
                        ></i>

                        ย้อนกลับ

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary px-4"
                    >

                        <i
                            class="bi bi-save me-1"
                        ></i>

                        บันทึกการแก้ไข

                    </button>


                </div>


            </form>


        </div>

    </div>


    <!-- Footer -->

    <div class="text-center text-muted small mt-4">

        ระบบจัดเก็บภาพถ่ายกิจกรรม/โครงการ

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