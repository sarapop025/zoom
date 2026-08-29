<?php
/**
 * ============================================================
 * categories/delete.php
 * ============================================================
 * ลบหมวดหมู่
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
// Permission
// ============================================================

$role = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


if ($role !== 'ADMIN') {

    http_response_code(403);

    exit('
        <div style="
            font-family:Arial;
            text-align:center;
            padding:60px;
        ">

            <h2>ไม่มีสิทธิ์ลบหมวดหมู่</h2>

            <p>
                เฉพาะผู้ดูแลระบบเท่านั้น
            </p>

            <a href="index.php">
                กลับรายการหมวดหมู่
            </a>

        </div>
    ');
}


// ============================================================
// Category ID
// ============================================================

$categoryId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($categoryId <= 0) {

    header(
        'Location: index.php'
    );

    exit;
}


// ============================================================
// Get Category
// ============================================================

try {

    $stmt = $pdo->prepare("
        SELECT
            category_id,
            category_name,
            description,
            status
        FROM categories
        WHERE category_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $categoryId
    ]);


    $category =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    die(
        '<div style="
            font-family:Arial;
            padding:40px;
        ">
            <h3>Database Error</h3>

            <pre>' .
            e($e->getMessage()) .
            '</pre>

        </div>'
    );
}


if (!$category) {

    header(
        'Location: index.php'
    );

    exit;
}


// ============================================================
// ตรวจสอบว่ามี Project ใช้งานหรือไม่
// ============================================================

try {

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_projects
        FROM projects
        WHERE category_id = ?
    ");

    $stmt->execute([
        $categoryId
    ]);


    $projectCount =
        (int) $stmt
        ->fetchColumn();


} catch (PDOException $e) {

    die(
        '<div style="
            font-family:Arial;
            padding:40px;
        ">
            <h3>Database Error</h3>

            <pre>' .
            e($e->getMessage()) .
            '</pre>

        </div>'
    );
}


// ============================================================
// ถ้ามี Project ห้ามลบ
// ============================================================

if ($projectCount > 0) {

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
            ไม่สามารถลบหมวดหมู่
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


    <body
        style="
            background:#f5f7fb;
            font-family:Tahoma,Arial,sans-serif;
        "
    >

    <div
        class="container"
        style="max-width:700px;padding-top:100px;"
    >

        <div class="card shadow-sm border-0">

            <div class="card-body p-5 text-center">

                <div
                    class="text-warning mb-3"
                    style="font-size:60px;"
                >

                    <i
                        class="bi bi-exclamation-triangle"
                    ></i>

                </div>


                <h3>

                    ไม่สามารถลบหมวดหมู่ได้

                </h3>


                <p class="text-muted">

                    หมวดหมู่

                    <strong>
                        <?= e(
                            $category['category_name']
                        ) ?>
                    </strong>

                    มีโครงการใช้งานอยู่

                </p>


                <div
                    class="alert alert-warning"
                >

                    <i
                        class="bi bi-folder2-open me-1"
                    ></i>

                    พบโครงการจำนวน

                    <strong>
                        <?= number_format(
                            $projectCount
                        ) ?>
                    </strong>

                    รายการ

                </div>


                <p class="small text-muted">

                    กรุณาย้ายโครงการไปยังหมวดหมู่อื่น
                    หรือลบโครงการที่เกี่ยวข้องก่อน

                </p>


                <div class="mt-4">

                    <a
                        href="index.php"
                        class="btn btn-primary"
                    >

                        <i
                            class="bi bi-arrow-left me-1"
                        ></i>

                        กลับรายการหมวดหมู่

                    </a>


                    <a
                        href="../projects/index.php"
                        class="btn btn-outline-secondary ms-2"
                    >

                        <i
                            class="bi bi-folder2-open me-1"
                        ></i>

                        ดูโครงการ

                    </a>

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
// Delete
// ============================================================

try {

    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        DELETE FROM categories
        WHERE category_id = ?
        LIMIT 1
    ");


    $stmt->execute([
        $categoryId
    ]);


    $pdo->commit();


    header(
        'Location: index.php?deleted=1'
    );

    exit;


} catch (
    PDOException $e
) {


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
            ลบหมวดหมู่ไม่สำเร็จ
        </title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

    </head>


    <body
        style="
            background:#f5f7fb;
            font-family:Tahoma,Arial,sans-serif;
        "
    >

    <div
        class="container"
        style="
            max-width:700px;
            padding-top:100px;
        "
    >

        <div class="alert alert-danger">

            <h4>

                ลบหมวดหมู่ไม่สำเร็จ

            </h4>


            <p>

                <?= e(
                    $e->getMessage()
                ) ?>

            </p>


            <a
                href="index.php"
                class="btn btn-primary"
            >

                กลับรายการ

            </a>

        </div>

    </div>

    </body>

    </html>

    <?php

    exit;
}