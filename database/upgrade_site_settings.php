<?php
require_once __DIR__ . '/../config/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'select') DEFAULT 'text',
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$defaults = [
    ['site_name', 'مفقودات', 'text', 'general'],
    ['site_description', 'منصة البحث عن المفقودات والمعثور عليه', 'text', 'general'],
    ['contact_email', 'info@mofaqdat.com', 'text', 'general'],
    ['maintenance_mode', '0', 'boolean', 'general'],
    ['matching_threshold', '40', 'number', 'matching'],
    ['new_found_alert_threshold', '30', 'number', 'matching'],
    ['enable_email_notifications', '1', 'boolean', 'notifications'],
    ['enable_google_oauth', '1', 'boolean', 'integrations'],
    ['google_client_id', '', 'text', 'integrations'],
    ['google_client_secret', '', 'text', 'integrations'],
    ['smtp_enabled', '0', 'boolean', 'email'],
    ['reports_per_page', '15', 'number', 'general'],
    ['max_image_size_mb', '5', 'number', 'general'],
    ['auto_expire_days', '30', 'number', 'general'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, setting_group) VALUES (?, ?, ?, ?)");
foreach ($defaults as $d) {
    $stmt->execute($d);
}

echo "تم إنشاء/تحديث جدول site_settings بنجاح<br>";
echo "تم إضافة " . count($defaults) . " إعداد افتراضي<br>";
