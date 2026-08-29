<?php
/**
 * ============================================================
 * photos/delete.php
 * ============================================================
 * ลบภาพถ่าย
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
    : '';


// ============================================================
// รับ Photo ID
// ============================================================

$photoId = 0;

if (isset($_GET['id'])) {

    $photoId = (int) $_GET['id'];

}

if (
    $photoId <= 0 &&
    isset($_POST['photo_id'])
) {

    $photoId =
        (int) $_POST['photo_id'];

}


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

    header(
        'Location: index.php?error=not_found'
    );

    exit;
}


// ============================================================
// ตรวจสอบสิทธิ์
// ============================================================

$canDelete = false;


// ADMIN ลบได้ทั้งหมด

if ($role === 'ADMIN') {

    $canDelete = true;
}


// STAFF ลบได้เฉพาะภาพของโครงการ
// ที่ตัวเองรับผิดชอบ

if (
    $role === 'STAFF' &&
    (int) $photo['project_owner'] === $userId
) {

    $canDelete = true;
}


if (!$canDelete) {

    http_response_code(403);

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
            ไม่มีสิทธิ์
        </title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

    </head>

    <body class="bg-light">

        <div
            class="container py-5"
        >

            <div
                class="row justify-content-center"
            >

                <div class="col-md-6">

                    <div
                        class="card border-0 shadow-sm"
                    >

                        <div
                            class="card-body text-center p-5"
                        >

                            <div
                                class="text-danger mb-3"
                            >

                                <i
                                    class="bi bi-shield-x"
                                    style="font-size:60px;"
                                ></i>

                            </div>


                            <h3>

                                ไม่มีสิทธิ์ลบภาพ

                            </h3>


                            <p class="text-muted">

                                คุณไม่มีสิทธิ์ลบภาพนี้

                            </p>


                            <a
                                href="index.php"
                                class="btn btn-primary"
                            >

                                กลับคลังภาพ

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}


// ============================================================
// GET → แสดงหน้าตรวจสอบก่อนลบ
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

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
            ยืนยันการลบภาพ
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


        <div
            class="container py-5"
        >

            <div
                class="row justify-content-center"
            >

                <div class="col-md-7">


                    <div
                        class="card border-0 shadow-sm"
                    >


                        <div
                            class="card-body p-4"
                        >


                            <div
                                class="text-center"
                            >

                                <i
                                    class="bi bi-trash3 text-danger"
                                    style="font-size:65px;"
                                ></i>


                                <h3 class="mt-3">

                                    ยืนยันการลบภาพ

                                </h3>


                                <p
                                    class="text-muted"
                                >

                                    การดำเนินการนี้ไม่สามารถย้อนกลับได้

                                </p>

                            </div>


                            <!-- ข้อมูลภาพ -->

                            <div
                                class="alert alert-warning mt-4"
                            >

                                <div class="fw-bold">

                                    <?= e(
                                        $photo['file_name']
                                    ) ?>

                                </div>


                                <div
                                    class="small mt-1"
                                >

                                    กิจกรรม:

                                    <?= e(
                                        $photo['project_name']
                                    ) ?>

                                </div>


                                <div
                                    class="small"
                                >

                                    ผู้อัปโหลด:

                                    <?= e(
                                        $photo['uploader_name']
                                        ?: '-'
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="alert alert-danger"
                            >

                                <i
                                    class="bi bi-exclamation-triangle me-1"
                                ></i>

                                ภาพนี้จะถูกลบออกจากระบบ
                                และไฟล์ภาพจริงจะถูกลบออกจาก
                                `uploads/photos/`

                            </div>


                            <!-- Form -->

                            <form
                                method="POST"
                                action="delete.php"
                            >


                                <input
                                    type="hidden"
                                    name="photo_id"
                                    value="<?= (int) $photoId ?>"
                                >


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

                                        ยกเลิก

                                    </a>


                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >

                                        <i
                                            class="bi bi-trash me-1"
                                        ></i>

                                        ยืนยันลบภาพ

                                    </button>


                                </div>


                            </form>


                        </div>

                    </div>


                </div>

            </div>

        </div>


    </body>

    </html>

    <?php

    exit;
}


// ============================================================
// POST → ลบข้อมูล
// ============================================================

try {

    // --------------------------------------------------------
    // Transaction
    // --------------------------------------------------------

    $pdo->beginTransaction();


    // --------------------------------------------------------
    // ลบข้อมูลจาก Database
    // --------------------------------------------------------

    $stmt = $pdo->prepare("
        DELETE FROM photos
        WHERE photo_id = ?
    ");

    $stmt->execute([
        $photoId
    ]);


    if (
        $stmt->rowCount() <= 0
    ) {

        throw new Exception(
            'ไม่สามารถลบข้อมูลภาพได้'
        );

    }


    // --------------------------------------------------------
    // Commit
    // --------------------------------------------------------

    $pdo->commit();


    // --------------------------------------------------------
    // ลบไฟล์จริง
    // --------------------------------------------------------

    $filePath =
        $photo['file_path'];


    if (
        !empty($filePath)
    ) {

        $filePath =
            str_replace(
                '\\',
                '/',
                $filePath
            );


        // URL ไม่ต้องลบ

        if (
            strpos(
                $filePath,
                'http://'
            ) !== 0 &&
            strpos(
                $filePath,
                'https://'
            ) !== 0
        ) {


            // ----------------------------------------------
            // uploads/photos/filename.jpg
            // ----------------------------------------------

            if (
                strpos(
                    $filePath,
                    'uploads/photos/'
                ) === 0
            ) {

                $physicalPath =
                    dirname(__DIR__) .
                    '/' .
                    $filePath;

            } else {


                // ------------------------------------------
                // กรณีเก็บแค่ filename
                // ------------------------------------------

                $physicalPath =
                    dirname(__DIR__) .
                    '/uploads/photos/' .
                    ltrim(
                        $filePath,
                        '/'
                    );

            }


            // ----------------------------------------------
            // ลบไฟล์
            // ----------------------------------------------

            if (
                is_file(
                    $physicalPath
                )
            ) {

                @unlink(
                    $physicalPath
                );

            }

        }

    }


    // ========================================================
    // กลับคลังภาพ
    // ========================================================

    header(
        'Location: index.php?deleted=1'
    );

    exit;


} catch (Exception $e) {


    // --------------------------------------------------------
    // Rollback
    // --------------------------------------------------------

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

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
            ไม่สามารถลบภาพ
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


        <div
            class="container py-5"
        >

            <div
                class="row justify-content-center"
            >

                <div class="col-md-7">


                    <div
                        class="card border-0 shadow-sm"
                    >

                        <div
                            class="card-body p-4"
                        >


                            <div
                                class="text-center text-danger"
                            >

                                <i
                                    class="bi bi-x-circle"
                                    style="font-size:65px;"
                                ></i>


                                <h3 class="mt-3">

                                    ไม่สามารถลบภาพได้

                                </h3>

                            </div>


                            <div
                                class="alert alert-danger mt-4"
                            >

                                <?= e(
                                    $e->getMessage()
                                ) ?>

                            </div>


                            <div
                                class="text-center"
                            >

                                <a
                                    href="index.php"
                                    class="btn btn-primary"
                                >

                                    <i
                                        class="bi bi-arrow-left me-1"
                                    ></i>

                                    กลับคลังภาพ

                                </a>

                            </div>


                        </div>

                    </div>


                </div>

            </div>

        </div>


    </body>

    </html>

    <?php

}
