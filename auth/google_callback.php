<?php
$pageTitle = 'تسجيل عبر جوجل';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/google_oauth.php';

if (isLoggedIn()) {
    redirect(SITE_URL);
}

$error = $_GET['error'] ?? '';

if (!empty($error)) {
    setFlash('error', 'فشل التسجيل عبر جوجل: ' . $error);
    redirect(SITE_URL . '/auth/login.php');
}

$code = $_GET['code'] ?? '';

if (empty($code)) {
    setFlash('error', 'لم يتم الحصول على تفاصيل الحساب من جوجل');
    redirect(SITE_URL . '/auth/login.php');
}

$token_data = exchangeCodeForToken($code);

if (!$token_data || isset($token_data['error'])) {
    $err_msg = $token_data['error_description'] ?? 'فشل получения التوكن';
    setFlash('error', 'فشل المصادقة: ' . $err_msg);
    redirect(SITE_URL . '/auth/login.php');
}

$user_info = getGoogleUserInfo($token_data['access_token']);

if (!$user_info || !isset($user_info['email'])) {
    setFlash('error', 'فشل في جلب بيانات الحساب من جوجل');
    redirect(SITE_URL . '/auth/login.php');
}

$email = $user_info['email'];
$google_id = $user_info['id'] ?? '';
$full_name = $user_info['name'] ?? '';
$avatar_url = $user_info['picture'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    if ($user['is_deleted']) {
        $pdo->prepare("UPDATE users SET is_deleted = 0, deleted_at = NULL WHERE id = ?")->execute([$user['id']]);
    }

    $pdo->prepare("UPDATE users SET google_id = ?, avatar_url = ? WHERE id = ?")
        ->execute([$google_id, $avatar_url, $user['id']]);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['avatar'] = $user['avatar'];

    setFlash('success', 'مرحباً ' . $user['full_name'] . '! (تم الدخول عبر جوجل)');
    redirect(SITE_URL);
} else {
    $_SESSION['google_register'] = [
        'google_id' => $google_id,
        'email'     => $email,
        'full_name' => $full_name,
        'avatar_url'=> $avatar_url
    ];

    setFlash('success', 'تم التحقق من حسابك! أكمل التسجيل أدناه.');
    redirect(SITE_URL . '/auth/register_google.php');
}

function exchangeCodeForToken($code) {
    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code'
        ]),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function getGoogleUserInfo($access_token) {
    $ch = curl_init(GOOGLE_USERINFO_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
