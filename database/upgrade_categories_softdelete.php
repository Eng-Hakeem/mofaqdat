<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>تحديث: حذف ناعم للفئات</h2><pre>";

$queries = [
    "ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0 AFTER description",
    "ALTER TABLE categories ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER is_deleted"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ تم بنجاح\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "● موجود مسبقاً\n";
        } else {
            echo "⚠ " . $e->getMessage() . "\n";
        }
    }
}

echo "\n</pre><br><a href='" . SITE_URL . "'>العودة للرئيسية</a>";
