<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>تحديث: إعدادات البريد الإلكتروني</h2><pre>";

$queries = [
    "CREATE TABLE IF NOT EXISTS email_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        email_on_match TINYINT(1) DEFAULT 1,
        email_on_new_found TINYINT(1) DEFAULT 1,
        email_on_delivery TINYINT(1) DEFAULT 1,
        email_on_delivery_confirmed TINYINT(1) DEFAULT 1,
        email_on_status TINYINT(1) DEFAULT 1,
        email_on_system TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ تم بنجاح\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "● الجدول موجود مسبقاً\n";
        } else {
            echo "✗ خطأ: " . $e->getMessage() . "\n";
        }
    }
}

$users = $pdo->query("SELECT id FROM users WHERE is_deleted = 0")->fetchAll();
$inserted = 0;
foreach ($users as $user) {
    try {
        $pdo->prepare("
            INSERT IGNORE INTO email_preferences (user_id) VALUES (?)
        ")->execute([$user['id']]);
        if ($pdo->rowCount() > 0) $inserted++;
    } catch (Exception $e) {
        echo "⚠ خطأ للمستخدم {$user['id']}: " . $e->getMessage() . "\n";
    }
}
echo "\n✓ تم إنشاء إعدادات افتراضية لـ $inserted مستخدم\n";

echo "\n</pre><br><a href='" . SITE_URL . "'>العودة للرئيسية</a>";
