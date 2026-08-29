<?php
/**
 * ============================================================
 * photos/download.php
 * ============================================================
 * ดาวน์โหลดภาพถ่าย
 * โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์ (ฝ่ายมัธยมศึกษา)
 * ============================================================
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_auth.php';


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
        p.status AS project_status

    FROM photos ph

    LEFT JOIN projects p
        ON ph.project_id = p.project_id

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
// ไม่พบข้อมูล
// ============================================================

if (!$photo) {

    http_response_code(404);

    exit('
        <div style="
            font-family:Arial;
            text-align:center;
            padding:50px;
        ">

            <h2>
                ไม่พบภาพที่ต้องการดาวน์โหลด
            </h2>

            <a href="index.php">
                กลับคลังภาพ
            </a>

        </div>
    ');
}


// ============================================================
// ตรวจสอบสถานะ
// ============================================================
//
// ผู้ใช้ทั่วไปควรดาวน์โหลดเฉพาะภาพที่เผยแพร่แล้ว
//
// ADMIN / STAFF / EXECUTIVE
// สามารถดาวน์โหลดภาพในระบบได้
//
// VIEWER
// ดาวน์โหลดเฉพาะโครงการที่เผยแพร่
// ============================================================

$role = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ถ้าไม่ใช่ ADMIN / STAFF / EXECUTIVE
// และโครงการไม่ได้เผยแพร่

if (
    $role !== 'ADMIN' &&
    $role !== 'STAFF' &&
    $role !== 'EXECUTIVE'
) {

    $publicStatus = array(
        'PUBLISHED',
        'APPROVED'
    );


    if (
        !in_array(
            strtoupper(
                $photo['project_status']
            ),
            $publicStatus,
            true
        )
    ) {

        http_response_code(403);

        exit('
            <div style="
                font-family:Arial;
                text-align:center;
                padding:50px;
            ">

                <h2>
                    ภาพนี้ยังไม่เผยแพร่
                </h2>

                <p>
                    ไม่สามารถดาวน์โหลดภาพนี้ได้
                </p>

                <a href="index.php">
                    กลับคลังภาพ
                </a>

            </div>
        ');
    }
}


// ============================================================
// Path
// ============================================================

$filePath = $photo['file_path'];


// เปลี่ยน \ เป็น /

$filePath = str_replace(
    '\\',
    '/',
    $filePath
);


// ============================================================
// ป้องกัน URL ภายนอก
// ============================================================

if (
    strpos($filePath, 'http://') === 0 ||
    strpos($filePath, 'https://') === 0
) {

    http_response_code(400);

    exit('
        ไม่สามารถดาวน์โหลดไฟล์จาก URL ภายนอกได้
    ');
}


// ============================================================
// สร้าง Physical Path
// ============================================================

$basePath = dirname(__DIR__);


// ------------------------------------------------------------
// กรณี Database เก็บ:
//
// uploads/photos/filename.jpg
// ------------------------------------------------------------

if (
    strpos(
        $filePath,
        'uploads/photos/'
    ) === 0
) {

    $physicalPath =
        $basePath .
        '/' .
        $filePath;

} else {

    // --------------------------------------------------------
    // กรณี Database เก็บเฉพาะชื่อไฟล์
    // --------------------------------------------------------

    $physicalPath =
        $basePath .
        '/uploads/photos/' .
        ltrim(
            $filePath,
            '/'
        );
}


// ============================================================
// ตรวจสอบไฟล์
// ============================================================

if (
    !is_file($physicalPath)
) {

    http_response_code(404);

    exit('
        <div style="
            font-family:Arial;
            text-align:center;
            padding:50px;
        ">

            <h2>
                ไม่พบไฟล์ภาพในเซิร์ฟเวอร์
            </h2>

            <p>
                ไฟล์อาจถูกลบหรือย้ายออกจากระบบ
            </p>

            <a href="index.php">
                กลับคลังภาพ
            </a>

        </div>
    ');
}


// ============================================================
// ตรวจสอบว่าอ่านไฟล์ได้
// ============================================================

if (
    !is_readable($physicalPath)
) {

    http_response_code(403);

    exit('
        ไม่สามารถอ่านไฟล์ภาพได้
    ');
}


// ============================================================
// File Name
// ============================================================

$downloadName =
    $photo['file_name'];


// ป้องกัน Header Injection

$downloadName =
    str_replace(
        array(
            "\r",
            "\n"
        ),
        '',
        $downloadName
    );


// ============================================================
// ถ้าไม่มีชื่อไฟล์
// ============================================================

if (
    trim($downloadName) === ''
) {

    $downloadName =
        basename(
            $physicalPath
        );
}


// ============================================================
// MIME Type
// ============================================================

$mimeType =
    'application/octet-stream';


if (
    function_exists('finfo_open')
) {

    $finfo =
        finfo_open(
            FILEINFO_MIME_TYPE
        );


    if ($finfo) {

        $detectedType =
            finfo_file(
                $finfo,
                $physicalPath
            );


        if (
            $detectedType !== false &&
            $detectedType !== ''
        ) {

            $mimeType =
                $detectedType;
        }


        finfo_close($finfo);
    }

}


// ============================================================
// กำหนดขนาดไฟล์
// ============================================================

$fileSize =
    filesize($physicalPath);


// ============================================================
// Clear Output Buffer
// ============================================================

while (
    ob_get_level() > 0
) {

    ob_end_clean();

}


// ============================================================
// Headers
// ============================================================

header(
    'Content-Description: File Transfer'
);

header(
    'Content-Type: ' . $mimeType
);

header(
    'Content-Disposition: attachment; filename="' .
    basename($downloadName) .
    '"'
);

header(
    'Content-Transfer-Encoding: binary'
);

header(
    'Content-Length: ' . $fileSize
);

header(
    'Cache-Control: private, no-cache, no-store, must-revalidate'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


// ============================================================
// ส่งไฟล์
// ============================================================

readfile(
    $physicalPath
);

exit;