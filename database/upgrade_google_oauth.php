<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>تحديث: دعم تسجيل جوجل</h2><pre>";

$queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(50) DEFAULT NULL AFTER avatar",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) DEFAULT NULL AFTER avatar"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ تم بنجاح\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "● العمود موجود مسبقاً\n";
        } else {
            echo "⚠ " . $e->getMessage() . "\n";
        }
    }
}

echo "\n</pre><br><a href='" . SITE_URL . "'>العودة للرئيسية</a>";
