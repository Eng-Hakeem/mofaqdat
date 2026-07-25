<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>تحديث: إضافة أعمدة مفقودة</h2><pre>";

$queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS contact_method ENUM('phone', 'email', 'both') DEFAULT 'both' AFTER phone",
    "ALTER TABLE reports ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0 AFTER views",
    "ALTER TABLE reports ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER is_deleted",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0 AFTER role",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER is_deleted",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(50) DEFAULT NULL AFTER avatar",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) DEFAULT NULL AFTER google_id"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ تم بنجاح: " . substr($sql, 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "● موجود مسبقاً: " . substr($sql, 0, 60) . "...\n";
        } else {
            echo "⚠ " . $e->getMessage() . "\n";
        }
    }
}

echo "\n</pre><br><a href='" . SITE_URL . "'>العودة للرئيسية</a>";
