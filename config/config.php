<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/psu-photo-system/');

define(
    'UPLOAD_PATH',
    dirname(__DIR__) . '/uploads/photos/'
);

define(
    'MAX_FILE_SIZE',
    10 * 1024 * 1024
);

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];
