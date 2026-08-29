<?php

require_once __DIR__ . '/config/database.php';

$username = 'admin';
$password = 'Admin@123';
$full_name = 'ผู้ดูแลระบบ';
$email = 'admin@satit.psu.ac.th';
$role = 'ADMIN';
$status = 'ACTIVE';

// ตรวจสอบว่ามี username อยู่แล้วหรือไม่
$stmt = $pdo->prepare("
    SELECT user_id
    FROM users
    WHERE username = ?
    LIMIT 1
");

$stmt->execute([$username]);

if ($stmt->fetch()) {

    die('Username admin มีอยู่ในระบบแล้ว');

}

// เข้ารหัส Password
$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// เพิ่ม Admin
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
    (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $username,
    $password_hash,
    $full_name,
    $email,
    $role,
    $status
]);

echo "<h2>สร้าง Admin สำเร็จ</h2>";
echo "<p>Username: <strong>admin</strong></p>";
echo "<p>Password: <strong>Admin@123</strong></p>";
echo "<p>Role: <strong>ADMIN</strong></p>";