<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/google_oauth.php';

if (isLoggedIn()) {
    redirect(SITE_URL);
}

$scope = implode(' ', ['openid', 'email', 'profile']);

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => $scope,
    'access_type'   => 'offline',
    'prompt'        => 'consent',
    'state'         => bin2hex(random_bytes(16))
]);

header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
exit;
