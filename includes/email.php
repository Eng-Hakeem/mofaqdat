<?php

function sendEmail($user_id, $subject, $body, $type = 'system') {
    global $pdo;

    if (!shouldSendEmail($user_id, $type)) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) return false;

    $email = $user['email'];
    $name = $user['full_name'];

    $html_body = buildEmailTemplate($subject, $body, $name, $type);

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <no-reply@mofaqdat.com>\r\n";
    $headers .= "Reply-To: info@mofaqdat.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $full_subject = "=?UTF-8?B?" . base64_encode("[" . SITE_NAME . "] " . $subject) . "?=";

    try {
        $sent = @mail($email, $full_subject, $html_body, $headers);

        $pdo->prepare("
            INSERT INTO email_log (user_id, email, subject, body, type, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$user_id, $email, $subject, $body, $type, $sent ? 'sent' : 'failed']);

        return $sent;
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

function shouldSendEmail($user_id, $type) {
    global $pdo;

    $pref_map = [
        'match' => 'email_on_match',
        'new_found' => 'email_on_new_found',
        'delivery_request' => 'email_on_delivery',
        'delivery_confirmed' => 'email_on_delivery_confirmed',
        'delivery_pending' => 'email_on_delivery_confirmed',
        'status' => 'email_on_status',
        'system' => 'email_on_system'
    ];

    $pref_col = $pref_map[$type] ?? null;
    if (!$pref_col) return true;

    try {
        $stmt = $pdo->prepare("SELECT $pref_col FROM email_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row) {
            return !empty($row[$pref_col]);
        }
    } catch (Exception $e) {
    }

    return true;
}

function retryFailedEmails($limit = 20) {
    global $pdo;

    $failed = $pdo->prepare("SELECT * FROM email_log WHERE status = 'failed' ORDER BY created_at ASC LIMIT ?");
    $failed->execute([$limit]);
    $failed = $failed->fetchAll();

    $retried = 0;
    $succeeded = 0;

    foreach ($failed as $log) {
        $result = retrySingleEmail($log);
        $retried++;
        if ($result) $succeeded++;
    }

    return ['retried' => $retried, 'succeeded' => $succeeded];
}

function retrySingleEmail($log_entry) {
    global $pdo;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <no-reply@mofaqdat.com>\r\n";
    $headers .= "Reply-To: info@mofaqdat.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $full_subject = "=?UTF-8?B?" . base64_encode("[" . SITE_NAME . "] " . $log_entry['subject']) . "?=";

    $name = '';
    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$log_entry['user_id']]);
        $row = $stmt->fetch();
        if ($row) $name = $row['full_name'];
    } catch (Exception $e) {}

    $html_body = buildEmailTemplate($log_entry['subject'], $log_entry['body'], $name, $log_entry['type']);
    $sent = @mail($log_entry['email'], $full_subject, $html_body, $headers);

    $pdo->prepare("UPDATE email_log SET status = ?, created_at = NOW() WHERE id = ?")
        ->execute([$sent ? 'sent' : 'failed', $log_entry['id']]);

    return $sent;
}

function buildEmailTemplate($title, $content, $name, $type) {
    $colors = [
        'match' => '#16a34a',
        'delivery_request' => '#2563eb',
        'delivery_confirmed' => '#16a34a',
        'delivery_pending' => '#f59e0b',
        'new_match' => '#7c3aed',
        'new_found' => '#7c3aed',
        'status' => '#0ea5e9',
        'system' => '#64748b'
    ];
    $color = $colors[$type] ?? '#2563eb';

    $icons = [
        'match' => '🔔',
        'delivery_request' => '📦',
        'delivery_confirmed' => '✅',
        'delivery_pending' => '⏳',
        'new_match' => '🤝',
        'new_found' => '🤝',
        'status' => 'ℹ️',
        'system' => '📢'
    ];
    $icon = $icons[$type] ?? '📢';

    $name_html = $name ? htmlspecialchars($name) : '';
    $content_html = nl2br(htmlspecialchars($content));

    return "
    <!DOCTYPE html>
    <html dir='rtl'>
    <head><meta charset='UTF-8'></head>
    <body style='margin:0;padding:0;background:#f1f5f9;font-family:Tajawal,Arial,sans-serif;'>
    <div style='max-width:600px;margin:20px auto;background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background:$color;padding:30px;text-align:center;'>
            <span style='font-size:2.5rem;'>$icon</span>
            <h1 style='color:white;margin:10px 0 0;font-size:1.3rem;'>$title</h1>
        </div>
        <div style='padding:30px;'>
            <p style='font-size:1rem;color:#475569;margin-bottom:15px;'>مرحباً <strong style='color:#1e293b;'>$name_html</strong>,</p>
            <div style='background:#f8fafc;border-radius:10px;padding:20px;margin:15px 0;border:1px solid #e2e8f0;'>
                <p style='font-size:0.95rem;color:#334155;line-height:1.8;margin:0;'>$content_html</p>
            </div>
            <div style='text-align:center;margin-top:25px;'>
                <a href='" . SITE_URL . "' style='display:inline-block;background:$color;color:white;padding:12px 30px;border-radius:8px;text-decoration:none;font-weight:600;'>فتح الموقع</a>
            </div>
        </div>
        <div style='background:#f8fafc;padding:20px;text-align:center;border-top:1px solid #e2e8f0;'>
            <p style='font-size:0.8rem;color:#94a3b8;margin:0;'>هذا إشعار تلقائي من منصة " . SITE_NAME . "</p>
            <p style='font-size:0.75rem;color:#cbd5e1;margin:5px 0 0;'>لإلغاء الاشتراك، قم بتعديل إعدادات حسابك في صفحة الملف الشخصي</p>
        </div>
    </div>
    </body>
    </html>";
}
