<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>تحديث: الحذف الناعم (Soft Delete)</h2><pre>";

$queries = [
    "ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER role",
    "ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL AFTER is_deleted",
    "ALTER TABLE reports ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER status",
    "ALTER TABLE reports ADD COLUMN deleted_at TIMESTAMP NULL AFTER is_deleted",
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ نجح: $sql\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "● موجود مسبقاً\n";
        } else {
            echo "✗ خطأ: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n--- هيكل users ---\n";
foreach ($pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}

echo "\n--- هيكل reports ---\n";
foreach ($pdo->query("DESCRIBE reports")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}

echo "\n</pre><br><a href='/mofaqdat/admin/'>العودة للوحة التحكم</a>";
