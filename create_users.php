<?php

require_once __DIR__ . '/config/database.php';

$users = [

    [
        'username'  => 'admin',
        'password'  => 'Admin@123',
        'full_name' => 'ผู้ดูแลระบบ',
        'email'     => 'admin@satit.psu.ac.th',
        'role'      => 'ADMIN'
    ],

    [
        'username'  => 'staff',
        'password'  => 'Staff@123',
        'full_name' => 'เจ้าหน้าที่',
        'email'     => 'staff@satit.psu.ac.th',
        'role'      => 'STAFF'
    ],

    [
        'username'  => 'viewer',
        'password'  => 'Viewer@123',
        'full_name' => 'ผู้ดูเว็บไซต์',
        'email'     => 'viewer@satit.psu.ac.th',
        'role'      => 'VIEWER'
    ],

    [
        'username'  => 'executive',
        'password'  => 'Executive@123',
        'full_name' => 'ผู้บริหาร',
        'email'     => 'executive@satit.psu.ac.th',
        'role'      => 'EXECUTIVE'
    ]

];


foreach ($users as $user) {

    // ตรวจสอบ Username ซ้ำ
    $check = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $check->execute([
        $user['username']
    ]);


    if ($check->fetch()) {

        echo "มี Username {$user['username']} อยู่แล้ว<br>";

        continue;
    }


    // Hash Password
    $passwordHash = password_hash(
        $user['password'],
        PASSWORD_DEFAULT
    );


    // เพิ่ม User
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
        (
            ?, ?, ?, ?, ?, 'ACTIVE'
        )
    ");


    $stmt->execute([

        $user['username'],

        $passwordHash,

        $user['full_name'],

        $user['email'],

        $user['role']

    ]);


    echo "
        สร้าง User สำเร็จ:
        <strong>{$user['username']}</strong>
        -
        {$user['role']}
        <br>
    ";
}

echo "<br>ดำเนินการเสร็จสิ้น";