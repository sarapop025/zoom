<?php
/**
 * ============================================================
 * projects/delete.php
 * ============================================================
 * ลบกิจกรรม / โครงการ
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

$role = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ============================================================
// ตรวจสอบสิทธิ์
// ============================================================

if ($role !== 'ADMIN') {

    http_response_code(403);

    exit('
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <title>ไม่มีสิทธิ์</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f5f7fb;
                    padding: 50px;
                    text-align: center;
                }

                .box {
                    max-width: 500px;
                    margin: auto;
                    background: #fff;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 5px 20px rgba(0,0,0,.08);
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background: #0d6efd;
                    color: #fff;
                    text-decoration: none;
                    border-radius: 7px;
                }
            </style>
        </head>

        <body>

            <div class="box">

                <h2>ไม่มีสิทธิ์เข้าถึง</h2>

                <p>
                    เฉพาะผู้ดูแลระบบเท่านั้น
                    ที่สามารถลบกิจกรรม / โครงการได้
                </p>

                <a href="index.php">
                    กลับรายการโครงการ
                </a>

            </div>

        </body>
        </html>
    ');

}


// ============================================================
// รับ Project ID
// ============================================================

$projectId = 0;


// รองรับ POST

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['project_id'])
) {

    $projectId = (int) $_POST['project_id'];

}


// รองรับ GET เผื่อเรียกโดยตรง

if (
    $projectId <= 0 &&
    isset($_GET['id'])
) {

    $projectId = (int) $_GET['id'];

}


if ($projectId <= 0) {

    header('Location: index.php');
    exit;

}


// ============================================================
// โหลดข้อมูลโครงการ
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        project_id,
        project_name,
        created_by,
        status
    FROM projects
    WHERE project_id = ?
    LIMIT 1
");

$stmt->execute([
    $projectId
]);

$project = $stmt->fetch(
    PDO::FETCH_ASSOC
);


// ============================================================
// ไม่พบโครงการ
// ============================================================

if (!$project) {

    header(
        'Location: index.php?error=not_found'
    );

    exit;
}


// ============================================================
// ถ้าเป็น GET ให้แสดงหน้าถามยืนยัน
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
            ยืนยันการลบ | PSU Photo System
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
                                class="text-center mb-4"
                            >

                                <div
                                    class="text-danger"
                                    style="font-size:60px;"
                                >

                                    <i
                                        class="bi bi-exclamation-triangle"
                                    ></i>

                                </div>


                                <h3>

                                    ยืนยันการลบ

                                </h3>


                                <p
                                    class="text-muted"
                                >

                                    คุณกำลังจะลบกิจกรรม / โครงการนี้

                                </p>

                            </div>


                            <div
                                class="alert alert-warning"
                            >

                                <strong>

                                    <?= e(
                                        $project['project_name']
                                    ) ?>

                                </strong>


                                <br>


                                <small>

                                    รหัสโครงการ:
                                    <?= (int) $project['project_id'] ?>

                                </small>


                            </div>


                            <div
                                class="alert alert-danger"
                            >

                                <i
                                    class="bi bi-info-circle me-1"
                                ></i>

                                การลบจะลบความสัมพันธ์กับภาพของโครงการ
                                และไม่สามารถย้อนกลับได้

                            </div>


                            <form
                                method="POST"
                                action="delete.php"
                            >


                                <input
                                    type="hidden"
                                    name="project_id"
                                    value="<?= (int) $projectId ?>"
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

                                        ยืนยันลบโครงการ

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
    // เริ่ม Transaction
    // --------------------------------------------------------

    $pdo->beginTransaction();


    // --------------------------------------------------------
    // 1. ดึงรายการภาพของโครงการ
    //
    // เพื่อเอา file_path ไปใช้ลบไฟล์จริง
    // --------------------------------------------------------

    $stmt = $pdo->prepare("
        SELECT
            photo_id,
            file_path
        FROM photos
        WHERE project_id = ?
    ");

    $stmt->execute([
        $projectId
    ]);

    $photos =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    // --------------------------------------------------------
    // 2. ลบข้อมูลภาพจาก Database
    // --------------------------------------------------------

    $stmt = $pdo->prepare("
        DELETE FROM photos
        WHERE project_id = ?
    ");

    $stmt->execute([
        $projectId
    ]);


    // --------------------------------------------------------
    // 3. ลบ Project
    // --------------------------------------------------------

    $stmt = $pdo->prepare("
        DELETE FROM projects
        WHERE project_id = ?
    ");

    $stmt->execute([
        $projectId
    ]);


    // --------------------------------------------------------
    // ตรวจสอบว่าลบสำเร็จ
    // --------------------------------------------------------

    if ($stmt->rowCount() <= 0) {

        throw new Exception(
            'ไม่สามารถลบกิจกรรม / โครงการได้'
        );

    }


    // --------------------------------------------------------
    // Commit
    // --------------------------------------------------------

    $pdo->commit();


    // --------------------------------------------------------
    // 4. ลบไฟล์ภาพจริง
    //
    // ทำหลัง Commit เพื่อไม่ให้ไฟล์ถูกลบ
    // หาก Database rollback
    // --------------------------------------------------------

    foreach (
        $photos
        as $photo
    ) {


        if (
            empty(
                $photo['file_path']
            )
        ) {

            continue;
        }


        $filePath =
            $photo['file_path'];


        // แปลง path

        $filePath =
            str_replace(
                '\\',
                '/',
                $filePath
            );


        // ถ้าเป็น URL ไม่ต้องลบ

        if (
            strpos(
                $filePath,
                'http://'
            ) === 0 ||
            strpos(
                $filePath,
                'https://'
            ) === 0
        ) {

            continue;
        }


        // ----------------------------------------------------
        // หา Physical Path
        // ----------------------------------------------------

        if (
            strpos(
                $filePath,
                'uploads/photos/'
            ) === 0
        ) {

            $physicalPath =
                dirname(
                    __DIR__
                ) .
                '/' .
                $filePath;

        } else {

            $physicalPath =
                dirname(
                    __DIR__
                ) .
                '/uploads/photos/' .
                ltrim(
                    $filePath,
                    '/'
                );

        }


        // ----------------------------------------------------
        // ลบไฟล์
        // ----------------------------------------------------

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


    // --------------------------------------------------------
    // กลับหน้า Index
    // --------------------------------------------------------

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


    // --------------------------------------------------------
    // แสดง Error
    // --------------------------------------------------------

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
            ไม่สามารถลบข้อมูล
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
                                    style="font-size:60px;"
                                ></i>


                                <h3 class="mt-3">

                                    ไม่สามารถลบข้อมูลได้

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
                                    href="view.php?id=<?= (int) $projectId ?>"
                                    class="btn btn-primary"
                                >

                                    <i
                                        class="bi bi-arrow-left me-1"
                                    ></i>

                                    กลับรายละเอียด

                                </a>


                                <a
                                    href="index.php"
                                    class="btn btn-outline-secondary"
                                >

                                    รายการโครงการ

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