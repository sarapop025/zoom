<?php
/**
 * ============================================================
 * includes/navbar.php
 * ============================================================
 * Navbar กลางของระบบ PSU Photo System
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// Session
// ============================================================

$navbarUserId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;

$navbarUsername = isset($_SESSION['username'])
    ? $_SESSION['username']
    : '';

$navbarFullName = isset($_SESSION['full_name'])
    ? $_SESSION['full_name']
    : 'ผู้ใช้งาน';

$navbarRole = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ============================================================
// Helper
// ============================================================

if (!function_exists('navbarEscape')) {

    function navbarEscape($value)
    {
        return htmlspecialchars(
            $value === null ? '' : $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


// ============================================================
// Current Page
// ============================================================

$currentPage = basename(
    $_SERVER['PHP_SELF']
);


// ============================================================
// Role Label
// ============================================================

function navbarRoleLabel($role)
{
    switch ($role) {

        case 'ADMIN':
            return 'ผู้ดูแลระบบ';

        case 'STAFF':
            return 'เจ้าหน้าที่';

        case 'VIEWER':
            return 'ผู้ดูเว็บไซต์';

        case 'EXECUTIVE':
            return 'ผู้บริหาร';

        default:
            return 'ผู้ใช้งาน';
    }
}


// ============================================================
// Base Path
// ============================================================

/*
 * navbar.php อยู่ใน:
 *
 * photo-management/includes/navbar.php
 *
 * ดังนั้นหน้าในระดับ:
 *
 * dashboard/index.php
 * projects/index.php
 * photos/index.php
 *
 * ต้องใช้ ../
 *
 * ส่วน index.php หน้า root ใช้:
 *
 * ./ 
 */

$navbarBase = '../';


// ตรวจสอบว่าอยู่หน้า root หรือไม่

$navbarScript = isset(
    $_SERVER['SCRIPT_NAME']
)
    ? $_SERVER['SCRIPT_NAME']
    : '';


// ถ้าอยู่ที่ /photo-management/index.php

if (
    preg_match(
        '#/photo-management/index\.php$#',
        $navbarScript
    )
) {

    $navbarBase = '';
}

?>

<!-- ============================================================
     NAVBAR
============================================================ -->

<nav
    class="navbar navbar-expand-lg navbar-dark fixed-top"
    style="
        background:
            linear-gradient(
                135deg,
                #062b63,
                #0b4f9c
            );
        min-height:68px;
        box-shadow:
            0 2px 10px
            rgba(0,0,0,.12);
    "
>

<div class="container-fluid">


<!-- ==========================================================
     BRAND
=========================================================== -->

<a
    class="navbar-brand d-flex align-items-center"
    href="<?= $navbarBase ?>dashboard/index.php"
>

<?php if (
    file_exists(
        __DIR__ .
        '/../assets/images/logo.png'
    )
): ?>

<img
    src="<?= $navbarBase ?>assets/images/logo.png"
    alt="Logo"
    style="
        width:38px;
        height:38px;
        object-fit:contain;
        margin-right:10px;
        border-radius:8px;
    "
>

<?php else: ?>

<span
    style="
        width:38px;
        height:38px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        margin-right:10px;
        border-radius:8px;
        background:rgba(255,255,255,.15);
    "
>

    <i class="bi bi-images"></i>

</span>

<?php endif; ?>


<span>

    <strong>
        PSU Photo System
    </strong>

    <small
        class="d-none d-md-block"
        style="
            font-size:10px;
            opacity:.75;
            font-weight:400;
        "
    >

        ระบบจัดเก็บภาพกิจกรรม / โครงการ

    </small>

</span>

</a>


<!-- ==========================================================
     MOBILE TOGGLE
=========================================================== -->

<button
    class="navbar-toggler"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#mainNavbar"
    aria-controls="mainNavbar"
    aria-expanded="false"
    aria-label="เปิดเมนู"
>

    <span class="navbar-toggler-icon"></span>

</button>


<!-- ==========================================================
     NAVBAR MENU
=========================================================== -->

<div
    class="collapse navbar-collapse"
    id="mainNavbar"
>


<ul class="navbar-nav ms-auto align-items-lg-center">


<!-- Dashboard -->

<li class="nav-item">

<a
    class="nav-link
        <?= $currentPage === 'index.php'
            && strpos(
                $navbarScript,
                '/dashboard/'
            ) !== false
            ? 'active'
            : ''
        ?>
    "
    href="<?= $navbarBase ?>dashboard/index.php"
>

    <i class="bi bi-speedometer2 me-1"></i>

    Dashboard

</a>

</li>


<!-- Projects -->

<li class="nav-item">

<a
    class="nav-link"
    href="<?= $navbarBase ?>projects/index.php"
>

    <i class="bi bi-folder2-open me-1"></i>

    โครงการ

</a>

</li>


<!-- Photos -->

<li class="nav-item">

<a
    class="nav-link"
    href="<?= $navbarBase ?>photos/index.php"
>

    <i class="bi bi-images me-1"></i>

    คลังภาพ

</a>

</li>


<!-- ========================================================
     MANAGEMENT DROPDOWN
========================================================= -->

<li class="nav-item dropdown">


<a
    class="nav-link dropdown-toggle"
    href="#"
    role="button"
    data-bs-toggle="dropdown"
    aria-expanded="false"
>

    <i class="bi bi-gear me-1"></i>

    จัดการระบบ

</a>


<ul class="dropdown-menu dropdown-menu-end">


<!-- Categories -->

<li>

<a
    class="dropdown-item"
    href="<?= $navbarBase ?>categories/index.php"
>

    <i
        class="bi bi-tags me-2"
    ></i>

    หมวดหมู่

</a>

</li>


<?php if (
    $navbarRole === 'ADMIN'
): ?>

<li>

<a
    class="dropdown-item"
    href="<?= $navbarBase ?>users/index.php"
>

    <i
        class="bi bi-people me-2"
    ></i>

    ผู้ใช้งาน

</a>

</li>

<?php endif; ?>


<?php if (
    $navbarRole === 'ADMIN'
    ||
    $navbarRole === 'STAFF'
): ?>

<li>

<a
    class="dropdown-item"
    href="<?= $navbarBase ?>approvals/index.php"
>

    <i
        class="bi bi-check2-square me-2"
    ></i>

    ตรวจสอบภาพ

</a>

</li>

<?php endif; ?>


<li>

<a
    class="dropdown-item"
    href="<?= $navbarBase ?>reports/index.php"
>

    <i
        class="bi bi-bar-chart me-2"
    ></i>

    รายงาน

</a>

</li>


</ul>

</li>


<!-- ========================================================
     USER
========================================================= -->

<li class="nav-item dropdown ms-lg-2">


<a
    class="nav-link dropdown-toggle d-flex align-items-center"
    href="#"
    role="button"
    data-bs-toggle="dropdown"
    aria-expanded="false"
>


<span
    class="
        rounded-circle
        bg-white
        text-primary
        d-inline-flex
        align-items-center
        justify-content-center
        me-2
    "
    style="
        width:34px;
        height:34px;
    "
>

    <i class="bi bi-person-fill"></i>

</span>


<span class="d-none d-lg-inline">

    <?= navbarEscape(
        $navbarFullName
    ) ?>

</span>


</a>


<ul class="dropdown-menu dropdown-menu-end">


<li>

<div
    class="px-3 py-2"
    style="min-width:220px;"
>

<div class="fw-bold">

    <?= navbarEscape(
        $navbarFullName
    ) ?>

</div>


<div
    class="text-muted small"
>

    @<?= navbarEscape(
        $navbarUsername
    ) ?>

</div>


<div class="mt-2">

<span
    class="badge bg-primary"
>

    <?= navbarEscape(
        navbarRoleLabel(
            $navbarRole
        )
    ) ?>

</span>

</div>

</div>

</li>


<li>
    <hr class="dropdown-divider">
</li>


<li>

<a
    class="dropdown-item"
    href="<?= $navbarBase ?>dashboard/index.php"
>

    <i
        class="bi bi-speedometer2 me-2"
    ></i>

    Dashboard

</a>

</li>


<li>

<a
    class="dropdown-item"
    href="<?= $navbarBase ?>photos/index.php"
>

    <i
        class="bi bi-images me-2"
    ></i>

    คลังภาพ

</a>

</li>


<li>
    <hr class="dropdown-divider">
</li>


<li>

<a
    class="dropdown-item text-danger"
    href="<?= $navbarBase ?>auth/logout.php"
    onclick="
        return confirm(
            'ต้องการออกจากระบบหรือไม่?'
        );
    "
>

    <i
        class="bi bi-box-arrow-right me-2"
    ></i>

    ออกจากระบบ

</a>

</li>


</ul>

</li>


</ul>

</div>


</div>

</nav>


<!-- ============================================================
     NAVBAR SPACE
============================================================ -->

<div style="height:68px;"></div>
