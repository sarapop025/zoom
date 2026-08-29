<?php
/**
 * ============================================================
 * includes/sidebar.php
 * ============================================================
 * Sidebar กลางของระบบ PSU Photo System
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// Session
// ============================================================

$sidebarUserId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;

$sidebarUsername = isset($_SESSION['username'])
    ? $_SESSION['username']
    : '';

$sidebarFullName = isset($_SESSION['full_name'])
    ? $_SESSION['full_name']
    : 'ผู้ใช้งาน';

$sidebarRole = isset($_SESSION['role'])
    ? strtoupper($_SESSION['role'])
    : '';


// ============================================================
// Current Page
// ============================================================

$sidebarPage = basename(
    $_SERVER['PHP_SELF']
);


// ============================================================
// Current Directory
// ============================================================

$sidebarDirectory = basename(
    dirname($_SERVER['PHP_SELF'])
);


// ============================================================
// Base Path
// ============================================================

$sidebarBase = '../';


// Root page

$sidebarScript = isset(
    $_SERVER['SCRIPT_NAME']
)
    ? $_SERVER['SCRIPT_NAME']
    : '';


if (
    preg_match(
        '#/photo-management/index\.php$#',
        $sidebarScript
    )
) {

    $sidebarBase = '';
}


// ============================================================
// Helper
// ============================================================

if (!function_exists('sidebarEscape')) {

    function sidebarEscape($value)
    {
        return htmlspecialchars(
            $value === null ? '' : $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


// ============================================================
// Active Menu
// ============================================================

function sidebarActive($directory)
{
    global $sidebarDirectory;

    if ($sidebarDirectory === $directory) {
        return 'active';
    }

    return '';
}


// ============================================================
// Role Label
// ============================================================

function sidebarRoleLabel($role)
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

?>

<!-- ============================================================
     SIDEBAR
============================================================ -->

<aside
    id="mainSidebar"
    class="main-sidebar"
>


<!-- ==========================================================
     USER PROFILE
=========================================================== -->

<div class="sidebar-profile">


<div class="sidebar-avatar">

    <i class="bi bi-person-fill"></i>

</div>


<div class="sidebar-user-info">

<div class="sidebar-user-name">

    <?= sidebarEscape(
        $sidebarFullName
    ) ?>

</div>


<div class="sidebar-user-role">

    <?= sidebarEscape(
        sidebarRoleLabel(
            $sidebarRole
        )
    ) ?>

</div>

</div>

</div>


<!-- ==========================================================
     MENU
=========================================================== -->

<nav class="sidebar-menu">


<!-- ========================================================
     MAIN MENU
========================================================= -->

<div class="sidebar-title">

    เมนูหลัก

</div>


<!-- Dashboard -->

<a
    href="<?= $sidebarBase ?>dashboard/index.php"
    class="
        sidebar-link
        <?= sidebarActive('dashboard') ?>
    "
>

    <i class="bi bi-speedometer2"></i>

    <span>Dashboard</span>

</a>


<!-- Projects -->

<?php if (
    in_array(
        $sidebarRole,
        array(
            'ADMIN',
            'STAFF'
        ),
        true
    )
): ?>

<a
    href="<?= $sidebarBase ?>projects/index.php"
    class="
        sidebar-link
        <?= sidebarActive('projects') ?>
    "
>

    <i class="bi bi-folder2-open"></i>

    <span>กิจกรรม / โครงการ</span>

</a>

<?php endif; ?>


<!-- Photos -->

<a
    href="<?= $sidebarBase ?>photos/index.php"
    class="
        sidebar-link
        <?= sidebarActive('photos') ?>
    "
>

    <i class="bi bi-images"></i>

    <span>คลังภาพ</span>

</a>


<!-- ========================================================
     MANAGEMENT
========================================================= -->

<div class="sidebar-title">

    จัดการระบบ

</div>


<!-- Categories -->

<?php if (
    in_array(
        $sidebarRole,
        array(
            'ADMIN',
            'STAFF'
        ),
        true
    )
): ?>

<a
    href="<?= $sidebarBase ?>categories/index.php"
    class="
        sidebar-link
        <?= sidebarActive('categories') ?>
    "
>

    <i class="bi bi-tags"></i>

    <span>หมวดหมู่</span>

</a>

<?php endif; ?>


<!-- Users -->

<?php if (
    $sidebarRole === 'ADMIN'
): ?>

<a
    href="<?= $sidebarBase ?>users/index.php"
    class="
        sidebar-link
        <?= sidebarActive('users') ?>
    "
>

    <i class="bi bi-people"></i>

    <span>ผู้ใช้งาน</span>

</a>

<?php endif; ?>


<!-- Approvals -->

<?php if (
    in_array(
        $sidebarRole,
        array(
            'ADMIN',
            'STAFF'
        ),
        true
    )
): ?>

<a
    href="<?= $sidebarBase ?>approvals/index.php"
    class="
        sidebar-link
        <?= sidebarActive('approvals') ?>
    "
>

    <i class="bi bi-check2-square"></i>

    <span>ตรวจสอบภาพ</span>


<?php
/*
 * แสดงจำนวนภาพที่รอตรวจสอบ
 */
?>

<?php

$pendingCount = 0;


if (
    isset($pdo) &&
    $pdo instanceof PDO
) {

    try {

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM photos
            WHERE status = 'PENDING'
        ");

        $pendingCount =
            (int)$stmt->fetchColumn();

    } catch (Exception $e) {

        $pendingCount = 0;
    }
}

?>


<?php if (
    $pendingCount > 0
): ?>

<span
    class="
        sidebar-badge
        bg-warning
        text-dark
    "
>

    <?= number_format(
        $pendingCount
    ) ?>

</span>

<?php endif; ?>


</a>

<?php endif; ?>


<!-- Reports -->

<?php if (
    in_array(
        $sidebarRole,
        array(
            'ADMIN',
            'STAFF',
            'EXECUTIVE'
        ),
        true
    )
): ?>

<a
    href="<?= $sidebarBase ?>reports/index.php"
    class="
        sidebar-link
        <?= sidebarActive('reports') ?>
    "
>

    <i class="bi bi-bar-chart-line"></i>

    <span>รายงานและสถิติ</span>

</a>

<?php endif; ?>


<!-- ========================================================
     VIEWER MENU
========================================================= -->

<?php if (
    $sidebarRole === 'VIEWER'
): ?>


<div class="sidebar-title">

    การใช้งาน

</div>


<a
    href="<?= $sidebarBase ?>photos/index.php"
    class="
        sidebar-link
        <?= sidebarActive('photos') ?>
    "
>

    <i class="bi bi-images"></i>

    <span>ดูคลังภาพ</span>

</a>


<a
    href="<?= $sidebarBase ?>projects/index.php"
    class="
        sidebar-link
        <?= sidebarActive('projects') ?>
    "
>

    <i class="bi bi-folder2-open"></i>

    <span>ดูโครงการ</span>

</a>


<?php endif; ?>


<!-- ========================================================
     EXECUTIVE MENU
========================================================= -->

<?php if (
    $sidebarRole === 'EXECUTIVE'
): ?>


<div class="sidebar-title">

    ผู้บริหาร

</div>


<a
    href="<?= $sidebarBase ?>dashboard/index.php"
    class="
        sidebar-link
        <?= sidebarActive('dashboard') ?>
    "
>

    <i class="bi bi-speedometer2"></i>

    <span>ภาพรวมระบบ</span>

</a>


<a
    href="<?= $sidebarBase ?>reports/index.php"
    class="
        sidebar-link
        <?= sidebarActive('reports') ?>
    "
>

    <i class="bi bi-graph-up"></i>

    <span>รายงานสถิติ</span>

</a>


<a
    href="<?= $sidebarBase ?>photos/index.php"
    class="
        sidebar-link
        <?= sidebarActive('photos') ?>
    "
>

    <i class="bi bi-images"></i>

    <span>ภาพถ่ายทั้งหมด</span>

</a>


<?php endif; ?>


<!-- ========================================================
     ACCOUNT
========================================================= -->

<div class="sidebar-title">

    บัญชีผู้ใช้งาน

</div>


<a
    href="<?= $sidebarBase ?>auth/logout.php"
    class="sidebar-link sidebar-logout"
    onclick="
        return confirm(
            'ต้องการออกจากระบบหรือไม่?'
        );
    "
>

    <i class="bi bi-box-arrow-right"></i>

    <span>ออกจากระบบ</span>

</a>


</nav>


<!-- ==========================================================
     VERSION
=========================================================== -->

<div class="sidebar-footer">

    <div>
        PSU Photo System
    </div>

    <small>
        Version 1.0.0
    </small>

</div>


</aside>


<!-- ============================================================
     SIDEBAR CSS
============================================================ -->

<style>

.main-sidebar {

    position: fixed;

    top: 68px;

    left: 0;

    bottom: 0;

    width: 245px;

    background: #ffffff;

    border-right:
        1px solid #e5e7eb;

    overflow-y: auto;

    z-index: 900;

    transition:
        transform .25s ease,
        width .25s ease;

    display: flex;

    flex-direction: column;
}


/* ============================================================
   PROFILE
============================================================ */

.sidebar-profile {

    display: flex;

    align-items: center;

    padding:
        18px 15px;

    border-bottom:
        1px solid #eef0f3;
}


.sidebar-avatar {

    width: 42px;

    height: 42px;

    flex-shrink: 0;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #0d47a1,
            #1976d2
        );

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;
}


.sidebar-user-info {

    min-width: 0;

    margin-left: 10px;
}


.sidebar-user-name {

    font-size: 13px;

    font-weight: 700;

    color: #1f2937;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.sidebar-user-role {

    margin-top: 2px;

    font-size: 11px;

    color: #6b7280;
}


/* ============================================================
   MENU
============================================================ */

.sidebar-menu {

    padding:
        12px 10px;

    flex: 1;
}


.sidebar-title {

    padding:
        13px 10px 7px;

    color: #9ca3af;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;
}


.sidebar-link {

    position: relative;

    display: flex;

    align-items: center;

    width: 100%;

    min-height: 43px;

    padding:
        9px 12px;

    margin-bottom: 3px;

    border-radius: 9px;

    color: #4b5563;

    text-decoration: none;

    font-size: 13px;

    transition:
        background .18s ease,
        color .18s ease,
        transform .18s ease;
}


.sidebar-link i {

    width: 24px;

    min-width: 24px;

    margin-right: 8px;

    text-align: center;

    font-size: 16px;
}


.sidebar-link span:not(.sidebar-badge) {

    flex: 1;
}


.sidebar-link:hover {

    color: #0d47a1;

    background: #eef5ff;

    transform: translateX(2px);
}


.sidebar-link.active {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #0d47a1,
            #1565c0
        );

    box-shadow:
        0 4px 10px
        rgba(13,71,161,.18);
}


.sidebar-link.active:hover {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #0d47a1,
            #1565c0
        );
}


/* ============================================================
   BADGE
============================================================ */

.sidebar-badge {

    min-width: 22px;

    height: 20px;

    padding:
        2px 6px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 700;

    text-align: center;
}


/* ============================================================
   LOGOUT
============================================================ */

.sidebar-logout {

    color: #dc3545;
}


.sidebar-logout:hover {

    color: #dc3545;

    background: #fff1f2;
}


/* ============================================================
   FOOTER
============================================================ */

.sidebar-footer {

    padding:
        14px 15px;

    border-top:
        1px solid #eef0f3;

    color: #9ca3af;

    font-size: 10px;

    line-height: 1.5;
}


.sidebar-footer small {

    font-size: 9px;
}


/* ============================================================
   MOBILE
============================================================ */

@media (
    max-width: 991px
) {

    .main-sidebar {

        transform:
            translateX(-100%);
    }


    .main-sidebar.show {

        transform:
            translateX(0);

        box-shadow:
            4px 0 20px
            rgba(0,0,0,.15);
    }

}


/* ============================================================
   DESKTOP
============================================================ */

@media (
    min-width: 992px
) {

    .main-sidebar {

        transform: translateX(0);
    }

}

</style>