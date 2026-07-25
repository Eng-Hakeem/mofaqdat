<?php
/**
 * سكربت تحديث قاعدة البيانات للإشعارات
 * شغّل هذا الملف مرة واحدة: http://localhost/mofaqdat/database/upgrade.php
 */
require_once __DIR__ . '/../config/database.php';

echo "<h2>تحديث قاعدة البيانات - نظام الإشعارات</h2>";
echo "<pre>";

$updates = [
    "ALTER TABLE notifications ADD COLUMN matched_report_id INT NULL AFTER report_id",
    "ALTER TABLE notifications ADD COLUMN match_id INT NULL AFTER matched_report_id",
    "ALTER TABLE notifications MODIFY COLUMN type ENUM('match', 'new_found', 'status', 'system') DEFAULT 'system'"
];

foreach ($updates as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ نجح: $sql\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "● موجود مسبقاً: " . explode('ADD COLUMN', $sql)[1] ?? $sql . "\n";
        } else {
            echo "✗ خطأ: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n--- الحالة النهائية ---\n";
$result = $pdo->query("DESCRIBE notifications")->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "\n</pre>";
echo "<br><a href='/mofaqdat/'>العودة للرئيسية</a> | <a href='/mofaqdat/notifications/'>صفحة الإشعارات</a>";
