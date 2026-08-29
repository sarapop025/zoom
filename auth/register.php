<?php

session_start();

require_once __DIR__ . '/../config/database.php';


// ถ้า Login แล้วไม่ให้เข้าหน้าสมัครสมาชิก
if (isset($_SESSION['user_id'])) {

    header('Location: ../dashboard/index.php');
    exit;
}


$error = '';
$success = '';

$username = '';
$full_name = '';
$email = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    // ตรวจสอบข้อมูล

    if (
        $username === '' ||
        $full_name === '' ||
        $password === ''
    ) {

        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ';

    }

    elseif (strlen($username) < 4) {

        $error =
            'Username ต้องมีอย่างน้อย 4 ตัวอักษร';

    }

    elseif (strlen($password) < 8) {

        $error =
            'Password ต้องมีอย่างน้อย 8 ตัวอักษร';

    }

    elseif ($password !== $confirm_password) {

        $error =
            'Password และยืนยัน Password ไม่ตรงกัน';

    }

    elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error =
            'รูปแบบ Email ไม่ถูกต้อง';

    }

    else {

        try {

            // ตรวจสอบ Username ซ้ำ

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username
            ]);

            if ($stmt->fetch()) {

                $error =
                    'Username นี้ถูกใช้งานแล้ว';

            } else {

                // Hash Password

                $password_hash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                /*
                 * ผู้สมัครใหม่จะเป็น STAFF
                 * ไม่อนุญาตให้สมัครเป็น ADMIN
                 */

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
                    (?, ?, ?, ?, 'STAFF', 'ACTIVE')
                ");


                $stmt->execute([
                    $username,
                    $password_hash,
                    $full_name,
                    $email !== '' ? $email : null
                ]);


                $success =
                    'สมัครสมาชิกสำเร็จ สามารถเข้าสู่ระบบได้';


                // ล้างข้อมูล

                $username = '';
                $full_name = '';
                $email = '';
            }

        } catch (PDOException $e) {

            $error =
                'เกิดข้อผิดพลาดในการสมัครสมาชิก';
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
        สมัครสมาชิก | PSU Photo System
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

        body {

            min-height: 100vh;

            margin: 0;

            background:
                linear-gradient(
                    135deg,
                    #0d47a1,
                    #1565c0,
                    #1976d2
                );

            font-family:
                "Noto Sans Thai",
                Tahoma,
                Arial,
                sans-serif;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .register-wrapper {

            width: 100%;

            max-width: 480px;

            padding: 20px;
        }


        .register-card {

            border: none;

            border-radius: 22px;

            overflow: hidden;

            box-shadow:
                0 25px 60px
                rgba(0, 0, 0, .25);
        }


        .register-header {

            background: #fff;

            text-align: center;

            padding: 30px 25px 15px;
        }


        .logo {

            width: 80px;

            height: 80px;

            margin: 0 auto 15px;

            border-radius: 50%;

            background: #0d47a1;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 38px;
        }


        .system-title {

            color: #0d47a1;

            font-size: 22px;

            font-weight: 700;
        }


        .school-title {

            color: #6c757d;

            font-size: 13px;

            line-height: 1.7;
        }


        .register-body {

            background: #fff;

            padding: 25px 30px 30px;
        }


        .form-control {

            height: 48px;

            border-radius: 10px;
        }


        .input-group-text {

            min-width: 48px;

            justify-content: center;

            background: #f8f9fa;
        }


        .btn-register {

            height: 50px;

            border-radius: 10px;

            background: #0d47a1;

            border-color: #0d47a1;

            font-weight: 600;
        }


        .btn-register:hover {

            background: #083b8a;

            border-color: #083b8a;
        }


        .footer {

            color: #fff;

            text-align: center;

            font-size: 12px;

            margin-top: 15px;
        }

    </style>

</head>


<body>


<div class="register-wrapper">

    <div class="register-card">


        <!-- Header -->

        <div class="register-header">

            <div class="logo">

                <i class="bi bi-person-plus"></i>

            </div>


            <div class="system-title">

                สมัครสมาชิก

            </div>


            <div class="school-title mt-2">

                ระบบจัดเก็บภาพถ่ายกิจกรรม/โครงการ

                <br>

                โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์

                <br>

                (ฝ่ายมัธยมศึกษา)

            </div>

        </div>


        <!-- Body -->

        <div class="register-body">


            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">

                    <i
                        class="bi bi-exclamation-triangle-fill me-2"
                    ></i>

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if ($success !== ''): ?>

                <div class="alert alert-success">

                    <i
                        class="bi bi-check-circle-fill me-2"
                    ></i>

                    <?= htmlspecialchars(
                        $success,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <!-- ชื่อ -->

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        ชื่อ - นามสกุล
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-person"></i>

                        </span>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            placeholder="ชื่อ - นามสกุล"
                            value="<?= htmlspecialchars(
                                $full_name,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- Username -->

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Username
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-person-badge"></i>

                        </span>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="อย่างน้อย 4 ตัวอักษร"
                            value="<?= htmlspecialchars(
                                $username,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- Email -->

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Email
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-envelope"></i>

                        </span>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="example@email.com"
                            value="<?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                </div>


                <!-- Password -->

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                    >
                        Password
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-lock"></i>

                        </span>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="อย่างน้อย 8 ตัวอักษร"
                            required
                        >

                    </div>

                </div>


                <!-- Confirm Password -->

                <div class="mb-4">

                    <label
                        class="form-label fw-semibold"
                    >
                        ยืนยัน Password
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-lock-fill"></i>

                        </span>

                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-control"
                            placeholder="กรอก Password อีกครั้ง"
                            required
                        >

                    </div>

                </div>


                <!-- Role -->

                <div class="alert alert-info py-2">

                    <small>

                        <i class="bi bi-info-circle me-1"></i>

                        บัญชีที่สมัครใหม่จะได้รับสิทธิ์
                        <strong>เจ้าหน้าที่ (STAFF)</strong>

                    </small>

                </div>


                <!-- Submit -->

                <button
                    type="submit"
                    class="btn btn-primary btn-register w-100"
                >

                    <i class="bi bi-person-plus me-2"></i>

                    สมัครสมาชิก

                </button>


                <!-- Back Login -->

                <div class="text-center mt-4">

                    <span class="text-muted">

                        มีบัญชีอยู่แล้ว?

                    </span>

                    <a
                        href="login.php"
                        class="text-decoration-none fw-semibold"
                    >

                        เข้าสู่ระบบ

                    </a>

                </div>


            </form>

        </div>

    </div>


    <div class="footer">

        © <?= date('Y') ?>

        โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
        (ฝ่ายมัธยมศึกษา)

    </div>

</div>


</body>

</html>