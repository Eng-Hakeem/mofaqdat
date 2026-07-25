<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function uploadImage($file, $folder = 'reports') {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($file['type'], $allowed)) {
        return ['success' => false, 'error' => 'نوع الملف غير مدعوم'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'حجم الملف يتجاوز الحد الأقصى'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadDir = UPLOAD_PATH . $folder . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $folder . '/' . $filename];
    }

    return ['success' => false, 'error' => 'فشل رفع الملف'];
}

function formatDate($date, $format = 'Y-m-d') {
    $formats = [
        'ar' => 'd/m/Y',
        'en' => 'Y-m-d',
        'full' => 'd F Y'
    ];
    return date($formats[$format] ?? $format, strtotime($date));
}

function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' سنة مضت';
    if ($diff->m > 0) return $diff->m . ' شهر مضى';
    if ($diff->d > 0) return $diff->d . ' يوم مضى';
    if ($diff->h > 0) return $diff->h . ' ساعة مضت';
    if ($diff->i > 0) return $diff->i . ' دقيقة مضت';
    return 'الآن';
}

function getReportStatusText($status) {
    $statuses = [
        'active' => 'نشط',
        'matched' => 'تمت المطابقة',
        'resolved' => 'تم الحل',
        'expired' => 'منتهي الصلاحية'
    ];
    return $statuses[$status] ?? $status;
}

function getReportTypeText($type) {
    return $type === 'lost' ? 'مفقود' : 'معثور عليه';
}

function getReportTypeColor($type) {
    return $type === 'lost' ? 'danger' : 'success';
}

function getStatusColor($status) {
    $colors = [
        'active' => 'primary',
        'matched' => 'warning',
        'resolved' => 'success',
        'expired' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

function getCategoryName($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name_ar FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $row = $stmt->fetch();
    return $row ? $row['name_ar'] : 'غير معروف';
}

function getCategoryIcon($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT icon FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $row = $stmt->fetch();
    return $row ? $row['icon'] : 'fa-question';
}

function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    $masked = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 3));
    return $masked . '@' . $domain;
}

function maskPhone($phone) {
    if (strlen($phone) <= 4) return str_repeat('*', strlen($phone));
    return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
}

function generateMatchScore($report1, $report2) {
    $score = 0;
    $reasons = [];

    if ($report1['category_id'] == $report2['category_id']) {
        $score += 30;
        $reasons[] = 'الفئة متطابقة';
    }

    if (!empty($report1['color']) && !empty($report2['color']) && 
        strtolower($report1['color']) === strtolower($report2['color'])) {
        $score += 20;
        $reasons[] = 'اللون متطابق';
    }

    if (!empty($report1['brand']) && !empty($report2['brand']) && 
        strtolower($report1['brand']) === strtolower($report2['brand'])) {
        $score += 20;
        $reasons[] = 'العلامة التجارية متطابقة';
    }

    if (!empty($report1['model']) && !empty($report2['model']) && 
        strtolower($report1['model']) === strtolower($report2['model'])) {
        $score += 15;
        $reasons[] = 'الطراز متطابق';
    }

    if (!empty($report1['location_name']) && !empty($report2['location_name'])) {
        similar_text($report1['location_name'], $report2['location_name'], $percent);
        if ($percent > 60) {
            $score += 15;
            $reasons[] = 'الموقع قريب';
        }
    }

    if (!empty($report1['date_occurred']) && !empty($report2['date_occurred'])) {
        $diff = abs(strtotime($report1['date_occurred']) - strtotime($report2['date_occurred']));
        $days = $diff / 86400;
        if ($days <= 3) {
            $score += 10;
            $reasons[] = 'التاريخ قريب';
        }
    }

    if (!empty($report1['distinguishing_marks']) && !empty($report2['distinguishing_marks'])) {
        similar_text($report1['distinguishing_marks'], $report2['distinguishing_marks'], $percent);
        if ($percent > 50) {
            $score += 10;
            $reasons[] = 'العلامات المميزة مشابهة';
        }
    }

    return [
        'score' => min($score, 100),
        'reasons' => $reasons
    ];
}
