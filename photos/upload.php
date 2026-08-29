<?php
/**
 * ============================================================
 * photos/upload.php
 * ============================================================
 * ระบบจัดเก็บภาพถ่ายกิจกรรม / โครงการ
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
// ตรวจสอบสิทธิ์
// ============================================================

if (
    $role !== 'ADMIN' &&
    $role !== 'STAFF'
) {

    http_response_code(403);

    exit('
        <div style="
            font-family:Arial;
            text-align:center;
            padding:60px;
        ">

            <h2>ไม่มีสิทธิ์อัปโหลดภาพ</h2>

            <p>
                เฉพาะผู้ดูแลระบบและเจ้าหน้าที่เท่านั้น
            </p>

            <a href="../dashboard/index.php">
                กลับ Dashboard
            </a>

        </div>
    ');
}


// ============================================================
// Project ID
// ============================================================

$projectId = 0;

if (isset($_GET['project_id'])) {

    $projectId =
        (int) $_GET['project_id'];
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['project_id'])
) {

    $projectId =
        (int) $_POST['project_id'];
}


if ($projectId <= 0) {

    header(
        'Location: ../projects/index.php'
    );

    exit;
}


// ============================================================
// ตรวจสอบ Project
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        project_id,
        project_name,
        project_date,
        project_location,
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


if (!$project) {

    header(
        'Location: ../projects/index.php'
    );

    exit;
}


// ============================================================
// STAFF ตรวจสอบเจ้าของโครงการ
// ============================================================

if (
    $role === 'STAFF' &&
    (int) $project['created_by'] !== $userId
) {

    http_response_code(403);

    exit('
        <div style="
            font-family:Arial;
            text-align:center;
            padding:60px;
        ">

            <h2>ไม่มีสิทธิ์อัปโหลดภาพ</h2>

            <p>
                คุณสามารถอัปโหลดภาพเฉพาะโครงการ
                ที่คุณรับผิดชอบเท่านั้น
            </p>

            <a href="../projects/index.php">
                กลับโครงการ
            </a>

        </div>
    ');
}


// ============================================================
// Upload Directory
// ============================================================

$uploadDirectory =
    dirname(__DIR__) .
    '/uploads/photos/';


// ============================================================
// ตรวจสอบ Folder
// ============================================================

if (!is_dir($uploadDirectory)) {

    die('
        <div style="
            font-family:Arial;
            max-width:760px;
            margin:60px auto;
            padding:30px;
            background:#fff3cd;
            border:1px solid #ffe69c;
            border-radius:12px;
        ">

            <h3>
                ไม่พบโฟลเดอร์จัดเก็บภาพ
            </h3>

            <p>
                กรุณาสร้างโฟลเดอร์:
            </p>

            <code>
                ' .
                e($uploadDirectory) .
                '
            </code>

            <hr>

            <pre style="
                background:#212529;
                color:#fff;
                padding:15px;
                border-radius:8px;
            ">sudo mkdir -p /Applications/XAMPP/xamppfiles/htdocs/photo-management/uploads/photos

sudo chown -R daemon:daemon /Applications/XAMPP/xamppfiles/htdocs/photo-management/uploads

sudo chmod -R 775 /Applications/XAMPP/xamppfiles/htdocs/photo-management/uploads</pre>

        </div>
    ');

    exit;
}


// ============================================================
// ตรวจสอบ Permission
// ============================================================

if (!is_writable($uploadDirectory)) {

    die('
        <div style="
            font-family:Arial;
            max-width:760px;
            margin:60px auto;
            padding:30px;
            background:#f8d7da;
            border:1px solid #f5c2c7;
            border-radius:12px;
        ">

            <h3>
                ไม่มีสิทธิ์เขียนไฟล์
            </h3>

            <p>
                Apache ไม่มีสิทธิ์เขียนลง:
            </p>

            <code>
                ' .
                e($uploadDirectory) .
                '
            </code>

            <hr>

            <pre style="
                background:#212529;
                color:#fff;
                padding:15px;
                border-radius:8px;
            ">sudo chown -R daemon:daemon /Applications/XAMPP/xamppfiles/htdocs/photo-management/uploads

sudo chmod -R 775 /Applications/XAMPP/xamppfiles/htdocs/photo-management/uploads</pre>

        </div>
    ');

    exit;
}


// ============================================================
// Upload Configuration
// ============================================================

$allowedExtensions = array(
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp'
);


$allowedMimeTypes = array(
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
);


// 10 MB ต่อไฟล์

$maxFileSize =
    10 * 1024 * 1024;


// จำนวนสูงสุดต่อครั้ง

$maxFiles = 50;


// ============================================================
// Results
// ============================================================

$successCount = 0;

$errorCount = 0;

$uploadResults = array();


// ============================================================
// POST
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    // ========================================================
    // ตรวจสอบ Files
    // ========================================================

    if (
        !isset($_FILES['photos'])
    ) {

        $uploadResults[] = array(

            'success' => false,

            'name' => '',

            'message' =>
                'กรุณาเลือกภาพที่ต้องการอัปโหลด'

        );

    } else {


        $files =
            $_FILES['photos'];


        if (
            !isset($files['name']) ||
            !is_array($files['name'])
        ) {

            $uploadResults[] = array(

                'success' => false,

                'name' => '',

                'message' =>
                    'รูปแบบข้อมูลไฟล์ไม่ถูกต้อง'

            );

        } else {


            $fileCount =
                count($files['name']);


            if (
                $fileCount > $maxFiles
            ) {

                $fileCount =
                    $maxFiles;
            }


            // =================================================
            // Loop Files
            // =================================================

            for (
                $i = 0;
                $i < $fileCount;
                $i++
            ) {


                $originalName =
                    isset(
                        $files['name'][$i]
                    )
                        ? $files['name'][$i]
                        : '';


                $tmpName =
                    isset(
                        $files['tmp_name'][$i]
                    )
                        ? $files['tmp_name'][$i]
                        : '';


                $fileError =
                    isset(
                        $files['error'][$i]
                    )
                        ? (int) $files['error'][$i]
                        : UPLOAD_ERR_NO_FILE;


                $fileSize =
                    isset(
                        $files['size'][$i]
                    )
                        ? (int) $files['size'][$i]
                        : 0;


                // ------------------------------------------------
                // ไม่มีไฟล์
                // ------------------------------------------------

                if (
                    $fileError ===
                    UPLOAD_ERR_NO_FILE
                ) {

                    continue;
                }


                // ------------------------------------------------
                // Upload Error
                // ------------------------------------------------

                if (
                    $fileError !==
                    UPLOAD_ERR_OK
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'Upload Error Code: ' .
                            $fileError

                    );


                    continue;
                }


                // ------------------------------------------------
                // Temporary File
                // ------------------------------------------------

                if (
                    !is_uploaded_file(
                        $tmpName
                    )
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'ไม่พบไฟล์ชั่วคราวของ Upload'

                    );


                    continue;
                }


                // ------------------------------------------------
                // File Size
                // ------------------------------------------------

                if (
                    $fileSize <= 0
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'ไฟล์ว่างหรือไม่ถูกต้อง'

                    );


                    continue;
                }


                if (
                    $fileSize >
                    $maxFileSize
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'ไฟล์มีขนาดเกิน 10 MB'

                    );


                    continue;
                }


                // ------------------------------------------------
                // Extension
                // ------------------------------------------------

                $extension =
                    strtolower(
                        pathinfo(
                            $originalName,
                            PATHINFO_EXTENSION
                        )
                    );


                if (
                    !in_array(
                        $extension,
                        $allowedExtensions,
                        true
                    )
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'รองรับ JPG, JPEG, PNG, GIF และ WEBP เท่านั้น'

                    );


                    continue;
                }


                // ------------------------------------------------
                // MIME Type
                // ------------------------------------------------

                $mimeType = '';


                if (
                    function_exists(
                        'finfo_open'
                    )
                ) {

                    $finfo =
                        finfo_open(
                            FILEINFO_MIME_TYPE
                        );


                    if ($finfo) {

                        $mimeType =
                            finfo_file(
                                $finfo,
                                $tmpName
                            );


                        finfo_close(
                            $finfo
                        );
                    }
                }


                if (
                    $mimeType !== '' &&
                    !in_array(
                        $mimeType,
                        $allowedMimeTypes,
                        true
                    )
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'ชนิดไฟล์ไม่ถูกต้อง: ' .
                            $mimeType

                    );


                    continue;
                }


                // ------------------------------------------------
                // ตรวจสอบรูปภาพ
                // ------------------------------------------------

                $imageInfo =
                    @getimagesize(
                        $tmpName
                    );


                if (
                    $imageInfo === false
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง'

                    );


                    continue;
                }


                // ------------------------------------------------
                // Width / Height
                // ------------------------------------------------

                $width =
                    isset($imageInfo[0])
                        ? (int) $imageInfo[0]
                        : null;


                $height =
                    isset($imageInfo[1])
                        ? (int) $imageInfo[1]
                        : null;


                // =================================================
                // สร้างชื่อไฟล์ใหม่
                // =================================================

                try {

                    $randomName =
                        bin2hex(
                            random_bytes(16)
                        );

                } catch (
                    Exception $e
                ) {

                    $randomName =
                        uniqid(
                            '',
                            true
                        );
                }


                $newFileName =
                    date('Ymd_His') .
                    '_' .
                    $randomName .
                    '.' .
                    $extension;


                // =================================================
                // Physical Path
                // =================================================

                $destination =
                    $uploadDirectory .
                    $newFileName;


                // =================================================
                // Database Path
                // =================================================

                $databasePath =
                    'uploads/photos/' .
                    $newFileName;


                // =================================================
                // Photo Name
                // =================================================

                $photoName =
                    pathinfo(
                        $originalName,
                        PATHINFO_FILENAME
                    );


                // ป้องกันชื่อยาวเกิน 255

                $photoName =
                    substr(
                        $photoName,
                        0,
                        255
                    );


                $originalName =
                    substr(
                        $originalName,
                        0,
                        255
                    );


                $newFileName =
                    substr(
                        $newFileName,
                        0,
                        255
                    );


                // =================================================
                // Move File
                // =================================================

                if (
                    !move_uploaded_file(
                        $tmpName,
                        $destination
                    )
                ) {

                    $errorCount++;


                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'ไม่สามารถย้ายไฟล์ไปยัง uploads/photos ได้'

                    );


                    continue;
                }


                // =================================================
                // Database INSERT
                // =================================================

                try {


                    $sql = "
                        INSERT INTO photos
                        (
                            project_id,
                            photo_name,
                            original_name,
                            file_name,
                            file_path,
                            thumbnail_path,
                            photo_description,
                            photo_date,
                            file_size,
                            mime_type,
                            width,
                            height,
                            uploaded_by
                        )
                        VALUES
                        (
                            :project_id,
                            :photo_name,
                            :original_name,
                            :file_name,
                            :file_path,
                            :thumbnail_path,
                            :photo_description,
                            :photo_date,
                            :file_size,
                            :mime_type,
                            :width,
                            :height,
                            :uploaded_by
                        )
                    ";


                    $stmt =
                        $pdo->prepare($sql);


                    $stmt->execute([

                        ':project_id' =>
                            $projectId,

                        ':photo_name' =>
                            $photoName,

                        ':original_name' =>
                            $originalName,

                        ':file_name' =>
                            $newFileName,

                        ':file_path' =>
                            $databasePath,

                        ':thumbnail_path' =>
                            null,

                        ':photo_description' =>
                            null,

                        ':photo_date' =>
                            date('Y-m-d'),

                        ':file_size' =>
                            $fileSize,

                        ':mime_type' =>
                            $mimeType,

                        ':width' =>
                            $width,

                        ':height' =>
                            $height,

                        ':uploaded_by' =>
                            $userId

                    ]);


                    // =================================================
                    // Success
                    // =================================================

                    $successCount++;


                    $uploadResults[] = array(

                        'success' => true,

                        'name' =>
                            $originalName,

                        'message' =>
                            'อัปโหลดและบันทึกข้อมูลสำเร็จ'

                    );


                } catch (
                    PDOException $e
                ) {


                    // =================================================
                    // DB Error → ลบไฟล์
                    // =================================================

                    if (
                        is_file(
                            $destination
                        )
                    ) {

                        @unlink(
                            $destination
                        );
                    }


                    $errorCount++;


                    // แสดง Error SQL จริง

                    $uploadResults[] = array(

                        'success' => false,

                        'name' =>
                            $originalName,

                        'message' =>
                            'SQL Error: ' .
                            $e->getMessage()

                    );
                }

            }

        }

    }


    // =========================================================
    // ถ้าสำเร็จอย่างน้อย 1 ไฟล์
    // =========================================================

    if (
        $successCount > 0
    ) {

        header(
            'Location: ../projects/view.php?id=' .
            $projectId .
            '&uploaded=' .
            $successCount
        );

        exit;
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
        อัปโหลดภาพ | PSU Photo System
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

        body {

            margin: 0;

            background: #f5f7fb;

            font-family:
                "Noto Sans Thai",
                Tahoma,
                Arial,
                sans-serif;
        }


        .navbar {

            background: #062b63;

            min-height: 68px;
        }


        .navbar-brand {

            font-weight: 700;
        }


        .main {

            max-width: 1100px;

            margin: auto;

            padding:
                100px 20px 40px;
        }


        .project-box {

            background: #eef5ff;

            border:
                1px solid #d6e6ff;

            border-radius: 12px;

            padding: 16px;

            margin-bottom: 20px;
        }


        .project-title {

            color: #0d47a1;

            font-weight: 700;

            font-size: 18px;
        }


        .project-meta {

            color: #6b7280;

            font-size: 13px;

            margin-top: 6px;
        }


        .upload-card {

            background: #fff;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.05);

            padding: 25px;
        }


        .drop-zone {

            display: block;

            text-align: center;

            border:
                2px dashed #b8c5d6;

            border-radius: 14px;

            padding:
                60px 20px;

            background: #f8fbff;

            cursor: pointer;

            transition: .2s;
        }


        .drop-zone:hover,
        .drop-zone.dragover {

            border-color: #0d6efd;

            background: #edf6ff;
        }


        .drop-icon {

            font-size: 65px;

            color: #0d6efd;
        }


        .drop-title {

            font-size: 19px;

            font-weight: 700;

            margin-top: 10px;
        }


        .drop-subtitle {

            color: #6b7280;

            font-size: 13px;

            margin-top: 7px;
        }


        #photos {

            display: none;
        }


        .preview-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(150px, 1fr)
                );

            gap: 15px;

            margin-top: 20px;
        }


        .preview-item {

            border:
                1px solid #e5e7eb;

            border-radius: 10px;

            overflow: hidden;

            background: #fff;
        }


        .preview-item img {

            width: 100%;

            height: 145px;

            object-fit: cover;

            display: block;
        }


        .preview-name {

            padding: 8px;

            font-size: 11px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .btn {

            border-radius: 8px;
        }

    </style>

</head>


<body>


<!-- ======================================================
     Navbar
======================================================= -->

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


<!-- ======================================================
     Main
======================================================= -->

<main class="main">


    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h3 class="mb-1">

                <i
                    class="bi bi-cloud-upload me-2"
                ></i>

                อัปโหลดภาพ

            </h3>


            <div class="text-muted small">

                อัปโหลดภาพกิจกรรม / โครงการหลายไฟล์

            </div>

        </div>


        <a
            href="../projects/view.php?id=<?= $projectId ?>"
            class="btn btn-outline-secondary"
        >

            <i
                class="bi bi-arrow-left me-1"
            ></i>

            กลับโครงการ

        </a>

    </div>


    <!-- ==================================================
         Project
    =================================================== -->

    <div class="project-box">

        <div class="project-title">

            <i
                class="bi bi-folder2-open me-1"
            ></i>

            <?= e(
                $project['project_name']
            ) ?>

        </div>


        <div class="project-meta">

            <i
                class="bi bi-calendar3 me-1"
            ></i>

            <?= e(
                $project['project_date']
            ) ?>


            <?php if (
                !empty(
                    $project['project_location']
                )
            ): ?>

                <span class="ms-3">

                    <i
                        class="bi bi-geo-alt me-1"
                    ></i>

                    <?= e(
                        $project['project_location']
                    ) ?>

                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- ==================================================
         Upload Card
    =================================================== -->

    <div class="upload-card">


        <!-- Results -->

        <?php if (
            !empty($uploadResults)
        ): ?>


            <?php foreach (
                $uploadResults
                as $result
            ): ?>


                <div
                    class="
                        alert
                        <?= $result['success']
                            ? 'alert-success'
                            : 'alert-danger'
                        ?>
                    "
                >

                    <i
                        class="
                            bi
                            <?= $result['success']
                                ? 'bi-check-circle'
                                : 'bi-exclamation-circle'
                            ?>
                            me-1
                        "
                    ></i>


                    <?php if (
                        $result['name'] !== ''
                    ): ?>

                        <strong>

                            <?= e(
                                $result['name']
                            ) ?>

                        </strong>

                        -

                    <?php endif; ?>


                    <?= e(
                        $result['message']
                    ) ?>

                </div>


            <?php endforeach; ?>


        <?php endif; ?>


        <!-- Form -->

        <form
            method="POST"
            action="upload.php?project_id=<?= $projectId ?>"
            enctype="multipart/form-data"
            id="uploadForm"
        >


            <input
                type="hidden"
                name="project_id"
                value="<?= $projectId ?>"
            >


            <!-- Drop Zone -->

            <label
                for="photos"
                class="drop-zone"
                id="dropZone"
            >

                <div class="drop-icon">

                    <i
                        class="bi bi-cloud-arrow-up"
                    ></i>

                </div>


                <div class="drop-title">

                    เลือกภาพ หรือลากภาพมาวาง

                </div>


                <div class="drop-subtitle">

                    JPG, JPEG, PNG, GIF, WEBP

                    <br>

                    ขนาดสูงสุด 10 MB ต่อไฟล์

                    <br>

                    สูงสุด 50 ไฟล์ต่อครั้ง

                </div>


                <div class="mt-3">

                    <span
                        class="btn btn-primary"
                    >

                        <i
                            class="bi bi-folder2-open me-1"
                        ></i>

                        เลือกไฟล์

                    </span>

                </div>

            </label>


            <input
                type="file"
                id="photos"
                name="photos[]"
                accept="image/jpeg,image/png,image/gif,image/webp"
                multiple
                required
            >


            <!-- Preview -->

            <div
                id="previewGrid"
                class="preview-grid"
            ></div>


            <!-- Buttons -->

            <hr class="my-4">


            <div
                class="d-flex justify-content-between"
            >

                <a
                    href="../projects/view.php?id=<?= $projectId ?>"
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
                    id="uploadButton"
                >

                    <i
                        class="bi bi-cloud-upload me-1"
                    ></i>

                    อัปโหลดภาพ

                </button>

            </div>


        </form>


    </div>


    <!-- Footer -->

    <div
        class="text-center text-muted small mt-4"
    >

        ระบบจัดเก็บภาพถ่ายกิจกรรม / โครงการ

        <br>

        โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
        (ฝ่ายมัธยมศึกษา)

    </div>


</main>


<!-- ======================================================
     JavaScript
======================================================= -->

<script>

const input =
    document.getElementById('photos');

const dropZone =
    document.getElementById('dropZone');

const previewGrid =
    document.getElementById('previewGrid');

const uploadForm =
    document.getElementById('uploadForm');

const uploadButton =
    document.getElementById('uploadButton');


// ==========================================================
// Preview
// ==========================================================

function showPreview(files)
{
    previewGrid.innerHTML = '';


    if (
        !files ||
        files.length === 0
    ) {

        return;
    }


    for (
        let i = 0;
        i < files.length;
        i++
    ) {

        const file =
            files[i];


        if (
            file.type.indexOf(
                'image/'
            ) !== 0
        ) {

            continue;
        }


        const reader =
            new FileReader();


        reader.onload =
            function(event)
            {

                const item =
                    document.createElement(
                        'div'
                    );


                item.className =
                    'preview-item';


                const img =
                    document.createElement(
                        'img'
                    );


                img.src =
                    event.target.result;


                img.alt =
                    file.name;


                const name =
                    document.createElement(
                        'div'
                    );


                name.className =
                    'preview-name';


                name.textContent =
                    file.name;


                item.appendChild(img);

                item.appendChild(name);

                previewGrid.appendChild(item);

            };


        reader.readAsDataURL(file);
    }
}


// ==========================================================
// Select
// ==========================================================

input.addEventListener(
    'change',
    function()
    {

        showPreview(
            this.files
        );

    }
);


// ==========================================================
// Drag Over
// ==========================================================

dropZone.addEventListener(
    'dragover',
    function(event)
    {

        event.preventDefault();

        dropZone.classList.add(
            'dragover'
        );

    }
);


// ==========================================================
// Drag Leave
// ==========================================================

dropZone.addEventListener(
    'dragleave',
    function()
    {

        dropZone.classList.remove(
            'dragover'
        );

    }
);


// ==========================================================
// Drop
// ==========================================================

dropZone.addEventListener(
    'drop',
    function(event)
    {

        event.preventDefault();

        dropZone.classList.remove(
            'dragover'
        );


        input.files =
            event.dataTransfer.files;


        showPreview(
            event.dataTransfer.files
        );

    }
);


// ==========================================================
// Submit
// ==========================================================

uploadForm.addEventListener(
    'submit',
    function()
    {

        uploadButton.disabled =
            true;


        uploadButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span> กำลังอัปโหลด...';

    }
);

</script>


</body>

</html>