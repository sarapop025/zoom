<?php
/**
 * ============================================================
 * projects/view.php
 * ============================================================
 * รายละเอียดกิจกรรม / โครงการ
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
// โหลดข้อมูลโครงการ
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

        u.full_name AS creator_name

    FROM projects p

    LEFT JOIN categories c
        ON p.category_id = c.category_id

    LEFT JOIN users u
        ON p.created_by = u.user_id

    WHERE p.project_id = ?

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $projectId
]);

$project = $stmt->fetch(PDO::FETCH_ASSOC);


// ============================================================
// ไม่พบโครงการ
// ============================================================

if (!$project) {

    http_response_code(404);

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
            ไม่พบข้อมูล
        </title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
            rel="stylesheet"
        >

    </head>

    <body class="bg-light">

        <div class="container py-5">

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center p-5">

                    <i
                        class="bi bi-calendar-x text-secondary"
                        style="font-size:60px;"
                    ></i>

                    <h3 class="mt-3">
                        ไม่พบกิจกรรม / โครงการ
                    </h3>

                    <p class="text-muted">
                        ไม่พบข้อมูลโครงการที่ต้องการ
                    </p>

                    <a
                        href="index.php"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        กลับรายการโครงการ
                    </a>

                </div>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}


// ============================================================
// โหลดภาพ
//
// ใช้เฉพาะคอลัมน์ที่จำเป็น
// photo_id
// project_id
// file_name
// file_path
// uploaded_by
// created_at
// ============================================================

$sqlPhotos = "
    SELECT

        ph.photo_id,

        ph.project_id,

        ph.file_name,

        ph.file_path,

        ph.uploaded_by,

        ph.created_at,

        u.full_name AS uploader_name

    FROM photos ph

    LEFT JOIN users u
        ON ph.uploaded_by = u.user_id

    WHERE ph.project_id = ?

    ORDER BY ph.created_at DESC
";

$stmt = $pdo->prepare($sqlPhotos);

$stmt->execute([
    $projectId
]);

$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// จำนวนภาพ
// ============================================================

$photoCount = count($photos);


// ============================================================
// Image URL
// ============================================================

function imageUrl($path)
{
    if (empty($path)) {
        return '';
    }

    // เปลี่ยน \ เป็น /
    $path = str_replace(
        '\\',
        '/',
        $path
    );


    // --------------------------------------------------------
    // URL
    // --------------------------------------------------------

    if (
        strpos($path, 'http://') === 0 ||
        strpos($path, 'https://') === 0
    ) {

        return $path;
    }


    // --------------------------------------------------------
    // uploads/photos/...
    // --------------------------------------------------------

    if (
        strpos($path, 'uploads/photos/') === 0
    ) {

        return '../' . $path;
    }


    // --------------------------------------------------------
    // photos/...
    // --------------------------------------------------------

    if (
        strpos($path, 'photos/') === 0
    ) {

        return '../uploads/' . $path;
    }


    // --------------------------------------------------------
    // ชื่อไฟล์
    // --------------------------------------------------------

    return '../uploads/photos/' .
        ltrim($path, '/');
}


// ============================================================
// Status Badge
// ============================================================

function statusBadge($status)
{
    switch ($status) {

        case 'PUBLISHED':

            return '
                <span class="badge bg-success">
                    <i class="bi bi-globe2 me-1"></i>
                    เผยแพร่
                </span>
            ';

        case 'APPROVED':

            return '
                <span class="badge bg-primary">
                    <i class="bi bi-check-circle me-1"></i>
                    อนุมัติ
                </span>
            ';

        case 'PENDING':

            return '
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-clock me-1"></i>
                    รอตรวจสอบ
                </span>
            ';

        case 'REJECTED':

            return '
                <span class="badge bg-danger">
                    <i class="bi bi-x-circle me-1"></i>
                    ไม่อนุมัติ
                </span>
            ';

        case 'INACTIVE':

            return '
                <span class="badge bg-secondary">
                    <i class="bi bi-eye-slash me-1"></i>
                    ปิดใช้งาน
                </span>
            ';

        case 'DRAFT':

            return '
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-pencil me-1"></i>
                    แบบร่าง
                </span>
            ';

        default:

            return '
                <span class="badge bg-secondary">
                    ' . e($status) . '
                </span>
            ';
    }
}


// ============================================================
// สิทธิ์แก้ไข
// ============================================================

$canEdit = false;


// ADMIN

if ($role === 'ADMIN') {

    $canEdit = true;
}


// STAFF
// แก้ไขเฉพาะโครงการที่ตัวเองสร้าง

if (
    $role === 'STAFF' &&
    (int) $project['created_by'] === $userId
) {

    $canEdit = true;
}


// ============================================================
// Flash Message
// ============================================================

$createdMessage =
    isset($_GET['created']) &&
    $_GET['created'] == '1';

$updatedMessage =
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

        <?= e($project['project_name']) ?>

        | PSU Photo System

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

            line-height: 1.4;
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

            border: 1px solid #e5e7eb;

            border-radius: 13px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, .04);

            overflow: hidden;
        }


        .card-header-custom {

            padding:
                17px
                20px;

            border-bottom:
                1px solid #e5e7eb;

            font-weight: 700;

            color: #17395f;
        }


        .card-body-custom {

            padding: 20px;
        }


        /* ==================================================
           INFORMATION
        ================================================== */

        .info-item {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 12px 0;

            border-bottom:
                1px solid #f0f1f3;
        }


        .info-item:last-child {

            border-bottom: 0;
        }


        .info-icon {

            width: 36px;

            height: 36px;

            flex: 0 0 36px;

            border-radius: 8px;

            background: #eef5ff;

            color: #0d47a1;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .info-label {

            color: #8a94a3;

            font-size: 11px;

            margin-bottom: 2px;
        }


        .info-value {

            color: #27364a;

            font-size: 14px;

            font-weight: 500;
        }


        /* ==================================================
           PHOTO
        ================================================== */

        .photo-card {

            position: relative;

            background: #fff;

            border:
                1px solid #e5e7eb;

            border-radius: 10px;

            overflow: hidden;

            transition: .2s;
        }


        .photo-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, .10);
        }


        .photo-image {

            display: block;

            width: 100%;

            height: 210px;

            object-fit: cover;

            background: #eef1f5;
        }


        .photo-info {

            padding: 10px;
        }


        .photo-name {

            font-size: 12px;

            color: #374151;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .photo-meta {

            color: #9ca3af;

            font-size: 10px;

            margin-top: 4px;
        }


        .photo-actions {

            position: absolute;

            top: 8px;

            right: 8px;
        }


        .empty-photo {

            padding:
                60px
                20px;

            text-align: center;

            color: #9ca3af;
        }


        .empty-photo i {

            display: block;

            font-size: 50px;

            margin-bottom: 10px;
        }


        /* ==================================================
           BUTTON
        ================================================== */

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


            .photo-image {

                height: 180px;
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


    <!-- ==================================================
         HEADER
    =================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <div class="page-title">

                <?= e(
                    $project['project_name']
                ) ?>

            </div>


            <div class="page-subtitle">

                รายละเอียดกิจกรรม / โครงการ

            </div>

        </div>


        <div class="d-flex gap-2">


            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >

                <i
                    class="bi bi-arrow-left me-1"
                ></i>

                กลับ

            </a>


            <?php if ($canEdit): ?>


                <a
                    href="edit.php?id=<?= $projectId ?>"
                    class="btn btn-warning"
                >

                    <i
                        class="bi bi-pencil me-1"
                    ></i>

                    แก้ไข

                </a>


            <?php endif; ?>


        </div>

    </div>


    <!-- ==================================================
         SUCCESS
    =================================================== -->

    <?php if ($createdMessage): ?>


        <div
            class="alert alert-success alert-dismissible fade show"
        >

            <i
                class="bi bi-check-circle me-1"
            ></i>

            เพิ่มกิจกรรม / โครงการเรียบร้อยแล้ว


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>


    <?php endif; ?>


    <?php if ($updatedMessage): ?>


        <div
            class="alert alert-success alert-dismissible fade show"
        >

            <i
                class="bi bi-check-circle me-1"
            ></i>

            แก้ไขกิจกรรม / โครงการเรียบร้อยแล้ว


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>


    <?php endif; ?>


    <!-- ==================================================
         PROJECT INFO
    =================================================== -->

    <div class="row g-4 mb-4">


        <!-- =================================================
             LEFT
        ================================================== -->

        <div class="col-lg-8">


            <div class="card-custom">


                <div class="card-header-custom">

                    <i
                        class="bi bi-info-circle me-2"
                    ></i>

                    ข้อมูลกิจกรรม / โครงการ

                </div>


                <div class="card-body-custom">


                    <!-- Date -->

                    <div class="info-item">

                        <div class="info-icon">

                            <i
                                class="bi bi-calendar-event"
                            ></i>

                        </div>


                        <div>

                            <div class="info-label">

                                วันที่จัดกิจกรรม

                            </div>


                            <div class="info-value">

                                <?= e(
                                    $project['project_date']
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- Location -->

                    <div class="info-item">

                        <div class="info-icon">

                            <i
                                class="bi bi-geo-alt"
                            ></i>

                        </div>


                        <div>

                            <div class="info-label">

                                สถานที่

                            </div>


                            <div class="info-value">

                                <?php if (
                                    !empty(
                                        $project['project_location']
                                    )
                                ): ?>

                                    <?= e(
                                        $project['project_location']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">

                                        ไม่ระบุ

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <!-- Category -->

                    <div class="info-item">

                        <div class="info-icon">

                            <i class="bi bi-tags"></i>

                        </div>


                        <div>

                            <div class="info-label">

                                หมวดหมู่

                            </div>


                            <div class="info-value">

                                <?= e(
                                    isset(
                                        $project['category_name']
                                    )
                                        ? $project['category_name']
                                        : '-'
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- Creator -->

                    <div class="info-item">

                        <div class="info-icon">

                            <i
                                class="bi bi-person"
                            ></i>

                        </div>


                        <div>

                            <div class="info-label">

                                ผู้รับผิดชอบ

                            </div>


                            <div class="info-value">

                                <?= e(
                                    isset(
                                        $project['creator_name']
                                    )
                                        ? $project['creator_name']
                                        : '-'
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- Created -->

                    <div class="info-item">

                        <div class="info-icon">

                            <i
                                class="bi bi-clock-history"
                            ></i>

                        </div>


                        <div>

                            <div class="info-label">

                                สร้างเมื่อ

                            </div>


                            <div class="info-value">

                                <?= e(
                                    $project['created_at']
                                ) ?>

                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>


        <!-- =================================================
             RIGHT
        ================================================== -->

        <div class="col-lg-4">


            <div class="card-custom">


                <div class="card-header-custom">

                    <i
                        class="bi bi-bar-chart me-2"
                    ></i>

                    สรุปข้อมูล

                </div>


                <div class="card-body-custom">


                    <div
                        class="text-center mb-4"
                    >

                        <div
                            class="display-4 fw-bold text-primary"
                        >

                            <?= number_format(
                                $photoCount
                            ) ?>

                        </div>


                        <div class="text-muted">

                            ภาพทั้งหมด

                        </div>

                    </div>


                    <hr>


                    <div class="mb-3">

                        <div
                            class="text-muted small mb-2"
                        >

                            สถานะ

                        </div>


                        <?= statusBadge(
                            $project['status']
                        ) ?>

                    </div>


                    <div>

                        <div
                            class="text-muted small mb-2"
                        >

                            รหัสโครงการ

                        </div>


                        <strong>

                            #<?= $projectId ?>

                        </strong>

                    </div>


                </div>

            </div>


        </div>


    </div>


    <!-- ==================================================
         PHOTOS
    =================================================== -->

    <div class="card-custom">


        <div class="card-header-custom">


            <div
                class="d-flex justify-content-between align-items-center"
            >


                <div>

                    <i
                        class="bi bi-images me-2"
                    ></i>

                    ภาพถ่ายกิจกรรม


                    <span
                        class="badge bg-primary ms-2"
                    >

                        <?= number_format(
                            $photoCount
                        ) ?>

                    </span>

                </div>


                <?php if (
                    $role === 'ADMIN' ||
                    (
                        $role === 'STAFF' &&
                        (int) $project['created_by']
                        === $userId
                    )
                ): ?>


                    <a
                        href="../photos/upload.php?project_id=<?= $projectId ?>"
                        class="btn btn-primary btn-sm"
                    >

                        <i
                            class="bi bi-cloud-upload me-1"
                        ></i>

                        อัปโหลดภาพ

                    </a>


                <?php endif; ?>


            </div>


        </div>


        <div class="card-body-custom">


            <?php if (empty($photos)): ?>


                <div class="empty-photo">


                    <i
                        class="bi bi-images"
                    ></i>


                    <div class="fw-semibold">

                        ยังไม่มีภาพถ่าย

                    </div>


                    <div class="small">

                        โครงการนี้ยังไม่มีภาพที่อัปโหลด

                    </div>


                    <?php if (
                        $role === 'ADMIN' ||
                        (
                            $role === 'STAFF' &&
                            (int) $project['created_by']
                            === $userId
                        )
                    ): ?>


                        <a
                            href="../photos/upload.php?project_id=<?= $projectId ?>"
                            class="btn btn-primary mt-3"
                        >

                            <i
                                class="bi bi-cloud-upload me-1"
                            ></i>

                            อัปโหลดภาพแรก

                        </a>


                    <?php endif; ?>


                </div>


            <?php else: ?>


                <div class="row g-3">


                    <?php foreach (
                        $photos
                        as $photo
                    ): ?>


                        <?php

                        $photoUrl = imageUrl(
                            $photo['file_path']
                        );

                        ?>


                        <div
                            class="col-6 col-md-4 col-lg-3"
                        >


                            <div
                                class="photo-card"
                            >


                                <!-- IMAGE -->

                                <?php if (
                                    $photoUrl !== ''
                                ): ?>


                                    <a
                                        href="<?= e(
                                            $photoUrl
                                        ) ?>"
                                        target="_blank"
                                        title="เปิดภาพขนาดเต็ม"
                                    >

                                        <img
                                            src="<?= e(
                                                $photoUrl
                                            ) ?>"
                                            class="photo-image"
                                            alt="<?= e(
                                                $photo['file_name']
                                            ) ?>"
                                            loading="lazy"
                                        >

                                    </a>


                                <?php else: ?>


                                    <div
                                        class="photo-image d-flex align-items-center justify-content-center"
                                    >

                                        <i
                                            class="bi bi-image text-secondary"
                                            style="font-size:40px;"
                                        ></i>

                                    </div>


                                <?php endif; ?>


                                <!-- ACTION -->

                                <?php if (
                                    $role === 'ADMIN' ||
                                    (
                                        $role === 'STAFF' &&
                                        (int) $photo['uploaded_by']
                                        === $userId
                                    )
                                ): ?>


                                    <div
                                        class="photo-actions"
                                    >

                                        <a
                                            href="../photos/edit.php?id=<?= (int) $photo['photo_id'] ?>"
                                            class="btn btn-sm btn-warning"
                                            title="แก้ไขข้อมูลภาพ"
                                        >

                                            <i
                                                class="bi bi-pencil"
                                            ></i>

                                        </a>

                                    </div>


                                <?php endif; ?>


                                <!-- INFO -->

                                <div
                                    class="photo-info"
                                >


                                    <div
                                        class="photo-name"
                                        title="<?= e(
                                            $photo['file_name']
                                        ) ?>"
                                    >

                                        <?= e(
                                            $photo['file_name']
                                        ) ?>

                                    </div>


                                    <div
                                        class="photo-meta"
                                    >

                                        <i
                                            class="bi bi-person"
                                        ></i>

                                        <?= e(
                                            isset(
                                                $photo['uploader_name']
                                            )
                                                ? $photo['uploader_name']
                                                : '-'
                                        ) ?>

                                    </div>


                                    <div
                                        class="photo-meta"
                                    >

                                        <i
                                            class="bi bi-clock"
                                        ></i>

                                        <?= e(
                                            $photo['created_at']
                                        ) ?>

                                    </div>


                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>

    </div>


    <!-- ==================================================
         FOOTER
    =================================================== -->

    <div
        class="text-center text-muted small mt-4"
    >

        ระบบจัดเก็บภาพถ่ายกิจกรรม/โครงการ

        |

        โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
        (ฝ่ายมัธยมศึกษา)

    </div>


</main>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>