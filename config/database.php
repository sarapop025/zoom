<?php

$host = 'localhost';
$dbname = 'psu_photo_system';
$username = 'root';
$password = '';

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    die(
        'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' .
        htmlspecialchars($e->getMessage())
    );
}