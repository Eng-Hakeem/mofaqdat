<?php
session_start();

define('DB_HOST', 'zephyr.proxy.rlwy.net');
define('DB_PORT', '33831');
define('DB_NAME', 'railway');
define('DB_USER', 'root');
define('DB_PASS', 'rouNFjHttsaeySQDvQqyKZJRHHoSZfTB');

define('SITE_NAME', 'مفقودات');
define('SITE_URL', 'https://mofaqdat-production.up.railway.app');

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
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
