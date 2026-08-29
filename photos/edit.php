<?php
/**
 * ============================================================
 * photos/edit.php
 * ============================================================
 * แก้ไขข้อมูลภาพถ่าย
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
// รับ Photo ID
// ============================================================

$photoId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($photoId <= 0) {

    header('Location: index.php');
    exit;
}


// ============================================================
// โหลดข้อมูลภาพ
// ============================================================

$sql = "
    SELECT

        ph.photo_id,

        ph.project_id,

        ph.file_name,

        ph.file_path,

        ph.uploaded_by,

        ph.created_at,

        p.project_name,

        p.project_date,

        p.project_location,

        p.created_by AS project_owner,

        u.full_name AS uploader_name

    FROM photos ph

    LEFT JOIN projects p
        ON ph.project_id = p.project_id

    LEFT JOIN users u
        ON ph.uploaded_by = u.user_id

    WHERE ph.photo_id = ?

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $photoId
]);

$photo = $stmt->fetch(
    PDO::FETCH_ASSOC
);


// ============================================================
// ไม่พบภาพ
// ============================================================

if (!$photo) {

    http_response_code(404);

    exit('
        <div style="
            font-family:Arial;
            padding:50px;
            text-align:center;
        ">

            <h2>ไม่พบข้อมูลภาพ</h2>

            <a href="index.php">
                กลับคลังภาพ
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


// STAFF
// แก้ไขภาพของโครงการที่ตัวเองรับผิดชอบ

if (
    $role === 'STAFF' &&
    (int) $photo['project_owner'] === $userId
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

            <h2>ไม่มีสิทธิ์แก้ไขภาพนี้</h2>

            <p>
                คุณสามารถแก้ไขภาพของโครงการที่คุณรับผิดชอบเท่านั้น
            </p>

            <a href="index.php">
                กลับคลังภาพ
            </a>

        </div>
    ');
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
        $path
    );


    if (
        strpos($path, 'http://') === 0 ||
        strpos($path, 'https://') === 0
    ) {

        return $path;
    }


    if (
        strpos($path, 'uploads/photos/') === 0
    ) {

        return '../' . $path;
    }


    if (
        strpos($path, 'photos/') === 0
    ) {

        return '../uploads/' . $path;
    }


    return '../uploads/photos/' .
        ltrim($path, '/');
}


$photoUrl =
    imageUrl(
        $photo['file_path']
    );


// ============================================================
// Variables
// ============================================================

$fileName =
    $photo['file_name'];

$errors = array();


// ============================================================
// POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fileName =
        isset($_POST['file_name'])
            ? trim($_POST['file_name'])
            : '';


    // --------------------------------------------------------
    // Validation
    // --------------------------------------------------------

    if ($fileName === '') {

        $errors[] =
            'กรุณาระบุชื่อภาพ';
    }


    if (
        strlen($fileName) > 255
    ) {

        $errors[] =
            'ชื่อภาพต้องไม่เกิน 255 ตัวอักษร';
    }


    // --------------------------------------------------------
    // UPDATE
    // --------------------------------------------------------

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                UPDATE photos
                SET
                    file_name = ?
                WHERE photo_id = ?
            ");


            $stmt->execute([

                $fileName,

                $photoId

            ]);


            header(
                'Location: index.php?updated=1'
            );

            exit;


        } catch (PDOException $e) {

            $errors[] =
                'ไม่สามารถแก้ไขข้อมูลภาพได้: ' .
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
        แก้ไขภาพ | PSU Photo System
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
           NAVBAR
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
           SIDEBAR
        ================================================== */

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


        /* ==================================================
           MAIN
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
           CARD
        ================================================== */

        .card-custom {

            background: #fff;

            border:
                1px solid #e5e7eb;

            border-radius: 13px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, .04);

            overflow: hidden;
        }


        .card-header-custom {

            padding:
                17px 20px;

            border-bottom:
                1px solid #e5e7eb;

            font-weight: 700;

            color: #17395f;
        }


        .card-body-custom {

            padding: 22px;
        }


        /* ==================================================
           IMAGE
        ================================================== */

        .preview-wrapper {

            background: #f1f3f5;

            border-radius: 12px;

            padding: 12px;

            text-align: center;
        }


        .preview-image {

            width: 100%;

            max-height: 480px;

            object-fit: contain;

            border-radius: 8px;

            background: #fff;
        }


        /* ==================================================
           INFO
        ================================================== */

        .info-box {

            padding: 13px;

            background: #f8fafc;

            border-radius: 9px;

            margin-bottom: 10px;
        }


        .info-label {

            color: #8a94a3;

            font-size: 11px;

            margin-bottom: 3px;
        }


        .info-value {

            color: #27364a;

            font-size: 13px;

            font-weight: 500;
        }


        .form-label {

            font-size: 13px;

            font-weight: 600;
        }


        .form-control {

            min-height: 44px;

            border-radius: 8px;
        }


        .btn {

            border-radius: 8px;
        }


        /* ==================================================
           MOBILE
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

            <i
                class="bi bi-images me-2"
            ></i>

            PSU Photo System

        </a>


        <div class="navbar-user">

            <i
                class="bi bi-person-circle me-1"
            ></i>

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

                <i
                    class="bi bi-box-arrow-right"
                ></i>

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

        <i
            class="bi bi-speedometer2"
        ></i>

        Dashboard

    </a>


    <a
        href="../projects/index.php"
        class="menu-link"
    >

        <i
            class="bi bi-calendar-event"
        ></i>

        กิจกรรม / โครงการ

    </a>


    <a
        href="index.php"
        class="menu-link active"
    >

        <i
            class="bi bi-images"
        ></i>

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

            <i
                class="bi bi-cloud-upload"
            ></i>

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

            <i
                class="bi bi-tags"
            ></i>

            หมวดหมู่

        </a>


        <a
            href="../users/index.php"
            class="menu-link"
        >

            <i
                class="bi bi-people"
            ></i>

            ผู้ใช้งาน

        </a>


        <a
            href="../approvals/index.php"
            class="menu-link"
        >

            <i
                class="bi bi-check2-square"
            ></i>

            ตรวจสอบภาพ

        </a>


        <a
            href="../reports/index.php"
            class="menu-link"
        >

            <i
                class="bi bi-bar-chart"
            ></i>

            รายงาน

        </a>


    <?php endif; ?>


    <?php if (
        $role === 'EXECUTIVE'
    ): ?>


        <a
            href="../reports/index.php"
            class="menu-link"
        >

            <i
                class="bi bi-bar-chart"
            ></i>

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

        <i
            class="bi bi-box-arrow-right"
        ></i>

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

                <i
                    class="bi bi-pencil-square me-2"
                ></i>

                แก้ไขข้อมูลภาพ

            </div>


            <div class="page-subtitle">

                แก้ไขชื่อภาพโดยไม่เปลี่ยนไฟล์ต้นฉบับ

            </div>

        </div>


        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >

            <i
                class="bi bi-arrow-left me-1"
            ></i>

            กลับคลังภาพ

        </a>

    </div>


    <!-- Error -->

    <?php if (
        !empty($errors)
    ): ?>


        <div class="alert alert-danger">

            <i
                class="bi bi-exclamation-triangle me-1"
            ></i>


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


    <div class="row g-4">


        <!-- ==================================================
             IMAGE
        =================================================== -->

        <div class="col-lg-7">


            <div class="card-custom">


                <div class="card-header-custom">

                    <i
                        class="bi bi-image me-2"
                    ></i>

                    ภาพตัวอย่าง

                </div>


                <div class="card-body-custom">


                    <div
                        class="preview-wrapper"
                    >


                        <?php if (
                            $photoUrl !== ''
                        ): ?>


                            <img
                                src="<?= e($photoUrl) ?>"
                                alt="<?= e($fileName) ?>"
                                class="preview-image"
                            >


                        <?php else: ?>


                            <div
                                class="py-5 text-muted"
                            >

                                <i
                                    class="bi bi-image"
                                    style="font-size:70px;"
                                ></i>


                                <div class="mt-2">

                                    ไม่พบไฟล์ภาพ

                                </div>

                            </div>


                        <?php endif; ?>


                    </div>


                </div>

            </div>


        </div>


        <!-- ==================================================
             FORM
        =================================================== -->

        <div class="col-lg-5">


            <div class="card-custom">


                <div class="card-header-custom">

                    <i
                        class="bi bi-pencil me-2"
                    ></i>

                    ข้อมูลภาพ

                </div>


                <div class="card-body-custom">


                    <form
                        method="POST"
                        action="edit.php?id=<?= $photoId ?>"
                    >


                        <!-- File Name -->

                        <div class="mb-4">

                            <label
                                for="file_name"
                                class="form-label"
                            >

                                ชื่อภาพ

                                <span
                                    class="text-danger"
                                >
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="file_name"
                                name="file_name"
                                class="form-control"
                                maxlength="255"
                                value="<?= e($fileName) ?>"
                                required
                            >


                            <div
                                class="form-text"
                            >

                                สามารถเปลี่ยนชื่อที่ใช้แสดงในระบบได้

                            </div>

                        </div>


                        <!-- Project -->

                        <div class="info-box">

                            <div class="info-label">

                                กิจกรรม / โครงการ

                            </div>


                            <div class="info-value">

                                <i
                                    class="bi bi-folder2-open me-1"
                                ></i>

                                <?= e(
                                    $photo['project_name']
                                ) ?>

                            </div>

                        </div>


                        <!-- Date -->

                        <div class="info-box">

                            <div class="info-label">

                                วันที่กิจกรรม

                            </div>


                            <div class="info-value">

                                <?= e(
                                    $photo['project_date']
                                ) ?>

                            </div>

                        </div>


                        <!-- Uploader -->

                        <div class="info-box">

                            <div class="info-label">

                                ผู้อัปโหลด

                            </div>


                            <div class="info-value">

                                <?= e(
                                    $photo['uploader_name']
                                    ?: '-'
                                ) ?>

                            </div>

                        </div>


                        <!-- Created -->

                        <div class="info-box">

                            <div class="info-label">

                                วันที่อัปโหลด

                            </div>


                            <div class="info-value">

                                <?= e(
                                    $photo['created_at']
                                ) ?>

                            </div>

                        </div>


                        <hr class="my-4">


                        <!-- Buttons -->

                        <div
                            class="d-flex justify-content-between"
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

                                บันทึกการแก้ไข

                            </button>


                        </div>


                    </form>


                </div>

            </div>


        </div>


    </div>


    <!-- Footer -->

    <div
        class="text-center text-muted small mt-4"
    >

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