<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'mofaqdat');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'مفقودات');
define('SITE_URL', 'http://localhost/mofaqdat');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

$site_settings = [];
try {
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $settings_stmt->fetch()) {
        $site_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $site_settings = [];
}

function getSetting($key, $default = '') {
    global $site_settings;
    return $site_settings[$key] ?? $default;
}
