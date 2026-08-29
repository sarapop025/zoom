<?php
/**
 * ============================================================
 * auth/login.php
 * ============================================================
 * ระบบจัดเก็บภาพถ่ายกิจกรรม / โครงการ
 * โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์ (ฝ่ายมัธยมศึกษา)
 * ============================================================
 */

session_start();

require_once __DIR__ . '/../config/database.php';


// ============================================================
// Helper
// ============================================================

function e($value)
{
    return htmlspecialchars(
        $value === null ? '' : (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ============================================================
// ถ้า Login แล้ว
// ============================================================

if (
    isset($_SESSION['user_id']) &&
    (int)$_SESSION['user_id'] > 0
) {
    header('Location: ../dashboard/index.php');
    exit;
}


// ============================================================
// Variables
// ============================================================

$error = '';

$username = '';


// ============================================================
// Login
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $password =
        $_POST['password'] ?? '';


    // ========================================================
    // Validate
    // ========================================================

    if ($username === '') {

        $error =
            'กรุณากรอก Username';

    } elseif ($password === '') {

        $error =
            'กรุณากรอก Password';

    } else {

        try {

            // =================================================
            // ค้นหาผู้ใช้งาน
            // =================================================

            $stmt = $pdo->prepare("
                SELECT
                    user_id,
                    username,
                    password,
                    full_name,
                    email,
                    role,
                    status
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username
            ]);

            $user =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            // =================================================
            // ไม่พบ User
            // =================================================

            if (!$user) {

                $error =
                    'Username หรือ Password ไม่ถูกต้อง';

            } else {

                $accountStatus =
                    strtoupper(
                        trim(
                            (string)$user['status']
                        )
                    );


                // =================================================
                // ตรวจสอบ Status
                // =================================================

                if (
                    $accountStatus !== 'ACTIVE'
                ) {

                    $error =
                        'บัญชีผู้ใช้งานถูกระงับ กรุณาติดต่อผู้ดูแลระบบ';

                }

                // =================================================
                // ตรวจสอบ Password
                // =================================================

                elseif (
                    !password_verify(
                        $password,
                        $user['password']
                    )
                ) {

                    $error =
                        'Username หรือ Password ไม่ถูกต้อง';

                }

                // =================================================
                // Login สำเร็จ
                // =================================================

                else {

                    // ป้องกัน Session Fixation
                    session_regenerate_id(true);


                    // =================================================
                    // Session
                    // =================================================

                    $_SESSION['user_id'] =
                        (int)$user['user_id'];

                    $_SESSION['username'] =
                        $user['username'];

                    $_SESSION['full_name'] =
                        $user['full_name'];

                    $_SESSION['email'] =
                        $user['email'];

                    $_SESSION['role'] =
                        strtoupper(
                            trim(
                                (string)$user['role']
                            )
                        );

                    $_SESSION['login_time'] =
                        date('Y-m-d H:i:s');


                    // =================================================
                    // ตรวจสอบ Role
                    // =================================================

                    $allowedRoles = [
                        'ADMIN',
                        'STAFF',
                        'EXECUTIVE'
                    ];


                    if (
                        !in_array(
                            $_SESSION['role'],
                            $allowedRoles,
                            true
                        )
                    ) {

                        session_unset();
                        session_destroy();

                        $error =
                            'ไม่พบสิทธิ์การใช้งานของบัญชีนี้';

                    } else {

                        // ทุก Role ไป Dashboard
                        header(
                            'Location: ../dashboard/index.php'
                        );

                        exit;
                    }
                }
            }

        } catch (PDOException $e) {

            // ไม่แสดงรายละเอียดฐานข้อมูลให้ผู้ใช้
            $error =
                'เกิดข้อผิดพลาดในการเข้าสู่ระบบ กรุณาลองใหม่อีกครั้ง';
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
        เข้าสู่ระบบ | PSU Photo System
    </title>


    <!-- =====================================================
         Bootstrap
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         Bootstrap Icons
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         Global CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- =====================================================
         Login CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/login.css"
    >


    <!-- Favicon -->

    <link
        rel="icon"
        href="../assets/images/favicon.png"
    >

</head>


<body class="login-page">


<div class="login-wrapper">


    <!-- =====================================================
         LOGIN CARD
    ====================================================== -->

    <div class="login-card">


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="login-header">


            <!-- Logo -->

            <div class="login-logo">

                <img
                    src="../assets/images/logo.png"
                    alt="PSU Photo System"
                    class="login-logo-image"
                    onerror="
                        this.style.display='none';
                        document.getElementById('logoFallback').style.display='flex';
                    "
                >


                <div
                    id="logoFallback"
                    class="login-logo-fallback"
                    style="display:none;"
                >

                    <i class="bi bi-images"></i>

                </div>

            </div>


            <!-- System Name -->

            <div class="system-title">

                PSU Photo System

            </div>


            <div class="system-subtitle">

                ระบบจัดเก็บภาพถ่ายกิจกรรม / โครงการ

            </div>


            <div class="school-title">

                โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
                <br>
                (ฝ่ายมัธยมศึกษา)

            </div>

        </div>


        <!-- =================================================
             BODY
        ================================================== -->

        <div class="login-body">


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if ($error !== ''): ?>

                <div
                    class="
                        alert
                        alert-danger
                        d-flex
                        align-items-center
                    "
                    role="alert"
                >

                    <i
                        class="
                            bi
                            bi-exclamation-triangle-fill
                            me-2
                        "
                    ></i>


                    <div>

                        <?= e($error) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action="login.php"
                autocomplete="off"
            >


                <!-- Username -->

                <div class="mb-3">

                    <label
                        for="username"
                        class="form-label"
                    >

                        Username

                    </label>


                    <div class="input-group">

                        <span
                            class="input-group-text"
                        >

                            <i
                                class="bi bi-person"
                            ></i>

                        </span>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="กรอก Username"
                            value="<?= e($username) ?>"
                            autocomplete="username"
                            autofocus
                            required
                        >

                    </div>

                </div>


                <!-- Password -->

                <div class="mb-4">

                    <label
                        for="password"
                        class="form-label"
                    >

                        Password

                    </label>


                    <div class="input-group">

                        <span
                            class="input-group-text"
                        >

                            <i
                                class="bi bi-lock"
                            ></i>

                        </span>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="กรอก Password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="
                                btn
                                btn-outline-secondary
                                password-toggle
                            "
                            id="togglePassword"
                            title="แสดง Password"
                        >

                            <i
                                class="bi bi-eye"
                                id="toggleIcon"
                            ></i>

                        </button>

                    </div>

                </div>


                <!-- Login Button -->

                <button
                    type="submit"
                    class="
                        btn
                        btn-primary
                        btn-login
                        w-100
                    "
                >

                    <i
                        class="
                            bi
                            bi-box-arrow-in-right
                            me-2
                        "
                    ></i>

                    เข้าสู่ระบบ

                </button>


            </form>


            <!-- =================================================
                 Register
            ================================================== -->

            <div class="text-center mt-4">

                <span class="text-muted">

                    ยังไม่มีบัญชี?

                </span>


                <a
                    href="register.php"
                    class="register-link ms-1"
                >

                    สมัครสมาชิก

                </a>

            </div>


        </div>


    </div>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <div class="login-footer">

        <div>

            <i class="bi bi-shield-lock me-1"></i>

            ระบบจัดเก็บภาพถ่ายกิจกรรม / โครงการ

        </div>


        <div class="mt-1">

            โรงเรียนสาธิตมหาวิทยาลัยสงขลานครินทร์
            (ฝ่ายมัธยมศึกษา)

        </div>


        <div class="mt-1">

            © <?= date('Y') ?> PSU Photo System

        </div>

    </div>


</div>


<!-- ========================================================
     JavaScript
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const passwordInput =
            document.getElementById(
                'password'
            );

        const togglePassword =
            document.getElementById(
                'togglePassword'
            );

        const toggleIcon =
            document.getElementById(
                'toggleIcon'
            );


        if (
            passwordInput &&
            togglePassword &&
            toggleIcon
        ) {

            togglePassword.addEventListener(
                'click',
                function () {

                    if (
                        passwordInput.type ===
                        'password'
                    ) {

                        passwordInput.type =
                            'text';

                        toggleIcon.classList.remove(
                            'bi-eye'
                        );

                        toggleIcon.classList.add(
                            'bi-eye-slash'
                        );

                        togglePassword.title =
                            'ซ่อน Password';

                    } else {

                        passwordInput.type =
                            'password';

                        toggleIcon.classList.remove(
                            'bi-eye-slash'
                        );

                        toggleIcon.classList.add(
                            'bi-eye'
                        );

                        togglePassword.title =
                            'แสดง Password';
                    }

                }
            );

        }

    }
);

</script>


</body>

</html>
