<?php
declare(strict_types=1);

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Strict',
        'path' => '/',
    ]);
    session_start();
}

function requireAdmin(): void
{
    startAdminSession();
    if (empty($_SESSION['admin_authenticated'])) {
        jsonResponse(401, ['ok' => false, 'error' => 'Administrator sign-in required.']);
    }
}

function handleAdminLogin(array $input): never
{
    global $ADMIN_USERNAME, $ADMIN_PASSWORD_HASH;
    startAdminSession();

    if ($ADMIN_PASSWORD_HASH === '') {
        jsonResponse(503, ['ok' => false, 'error' => 'Configure ADMIN_PASSWORD_HASH before using the admin dashboard.']);
    }

    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($username === '' || $password === '' ||
        !hash_equals((string)$ADMIN_USERNAME, $username) ||
        !password_verify($password, $ADMIN_PASSWORD_HASH)) {
        usleep(250000);
        jsonResponse(401, ['ok' => false, 'error' => 'Invalid administrator credentials.']);
    }

    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_username'] = $username;

    jsonResponse(200, ['ok' => true]);
}

function handleAdminLogout(): never
{
    startAdminSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], '', $params['secure'], $params['httponly']);
    }
    session_destroy();
    jsonResponse(200, ['ok' => true]);
}
