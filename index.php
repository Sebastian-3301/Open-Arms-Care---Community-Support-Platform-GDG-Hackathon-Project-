<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/backend/config/database.php';

function jsonResponse(int $status, array $payload): never
{
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/* If PHP itself crashes before the normal exception handler, still return JSON. */
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('Open Arms fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Server configuration error. Check the Apache/PHP error log.']);
    }
});

function requestPath(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    // Strip the actual project directory when installed in XAMPP/htdocs/OPENARMSTEST.
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    return '/' . trim($path, '/');
}

function readJsonBody(int $maxBytes = 1048576): array
{
    $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length > $maxBytes) {
        throw new InvalidArgumentException('Request is too large.');
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > $maxBytes) {
        throw new InvalidArgumentException('Request is too large.');
    }

    if (trim($raw) === '') {
        throw new InvalidArgumentException('Request body is empty.');
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('Please send valid JSON form data.');
    }

    return $data;
}

function failException(Throwable $e): never
{
    if ($e instanceof InvalidArgumentException) {
        jsonResponse(400, ['ok' => false, 'error' => $e->getMessage()]);
    }

    error_log('Open Arms API error: ' . $e->getMessage());
    jsonResponse(500, ['ok' => false, 'error' => 'Unable to process this request. Check the Apache/PHP error log.']);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = requestPath();

if (isset($_GET['api'])) {
    $api = trim((string)$_GET['api'], '/');
    $path = '/api/' . $api;
}

try {
    if ($method === 'GET' && $path === '/api/health') {
        require_once __DIR__ . '/backend/api/health.php';
        handleHealth();
    }

    if ($method === 'POST' && str_starts_with($path, '/api/registrations/')) {
        require_once __DIR__ . '/backend/api/registrations.php';
        handleRegistration($path, readJsonBody(22 * 1024 * 1024));
    }

    if ($method === 'POST' && str_starts_with($path, '/api/donations/')) {
        require_once __DIR__ . '/backend/api/donations.php';
        handleDonation($path, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/contact') {
        require_once __DIR__ . '/backend/api/contact.php';
        handleContact(readJsonBody());
    }

    if ($path === '/api/admin/login' && $method === 'POST') {
        require_once __DIR__ . '/backend/admin/auth.php';
        handleAdminLogin(readJsonBody());
    }

    if ($path === '/api/admin/logout' && $method === 'POST') {
        require_once __DIR__ . '/backend/admin/auth.php';
        handleAdminLogout();
    }

    if ($path === '/api/admin/summary' && $method === 'GET') {
        require_once __DIR__ . '/backend/admin/auth.php';
        requireAdmin();
        require_once __DIR__ . '/backend/admin/summary.php';
        handleAdminSummary();
    }

    if ($path === '/api/admin/records' && $method === 'GET') {
        require_once __DIR__ . '/backend/admin/auth.php';
        requireAdmin();
        require_once __DIR__ . '/backend/admin/records.php';
        handleAdminRecords();
    }

    if ($path === '/api/admin/records' && $method === 'PATCH') {
        require_once __DIR__ . '/backend/admin/auth.php';
        requireAdmin();
        require_once __DIR__ . '/backend/admin/records.php';
        handleAdminRecordPatch(readJsonBody());
    }

    if (preg_match('#^/api/admin/documents/(\d+)$#', $path, $m) && $method === 'GET') {
        require_once __DIR__ . '/backend/admin/auth.php';
        requireAdmin();
        require_once __DIR__ . '/backend/admin/documents.php';
        handleAdminDocument((int)$m[1]);
    }

    if (str_starts_with($path, '/api/')) {
        jsonResponse(404, ['ok' => false, 'error' => 'API endpoint not found.']);
    }

    if ($path === '/admin') {
        header('Location: admin.html', true, 302);
        exit;
    }

    if ($method === 'GET' && $path === '/') {
        readfile(__DIR__ . '/openarms-website.html');
        exit;
    }

    http_response_code(404);
    echo 'Not found.';
} catch (Throwable $e) {
    failException($e);
}
