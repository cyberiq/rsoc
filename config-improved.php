<?php
require_once __DIR__ . '/env-loader.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Baghdad'));

$db_path = env('DB_PATH', '/var/www/html/kreen/kreen.db');

try {
    $pdo = new PDO("sqlite:{$db_path}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("❌ خطأ في قاعدة البيانات");
}

define('APP_DEBUG', env('APP_DEBUG') === 'true');
