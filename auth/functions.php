<?php

function e($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function redirect($url)
{
    header("Location: {$url}");
    exit;
}


function flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


function showFlash()
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];

    unset($_SESSION['flash']);

    echo '
    <div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show">
        ' . e($flash['message']) . '
        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
        </button>
    </div>';
}


function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


function verifyCsrf($token)
{
    return isset($_SESSION['csrf_token'])
        && hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
}


function isAdmin()
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === 'ADMIN';
}


function isStaff()
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === 'STAFF';
}


function isExecutive()
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === 'EXECUTIVE';
}