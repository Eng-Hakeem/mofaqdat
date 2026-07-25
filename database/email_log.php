<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>إنشاء جدول سجل البريد الإلكتروني</h2><pre>";

$queries = [
    "CREATE TABLE IF NOT EXISTS email_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(150) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'system',
        status ENUM('sent', 'failed') DEFAULT 'sent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ تم بنجاح\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "● الجدول موجود مسبقاً\n";
        } else {
            echo "✗ خطأ: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n</pre><br><a href='/mofaqdat/'>العودة للرئيسية</a>";
