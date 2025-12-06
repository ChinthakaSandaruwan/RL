<?php

function env($key, $default = null) {
    static $env = null;
    if ($env === null) {
        $env = [];
        $path = __DIR__ . '/../.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }
    }
    return $env[$key] ?? $default;
}

function app_url($path = '') {
    $url = env('APP_URL', 'http://localhost/RL');
    return rtrim($url, '/') . '/' . ltrim($path, '/');
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $db   = env('DB_DATABASE', 'rentallanka');
    $user = env('DB_USERNAME', 'root');
    $pass = env('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

function ensure_session_started(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Session hardening
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '', // Default to current domain
            'secure' => isset($_SERVER['HTTPS']), // Only secure if HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

function generate_csrf_token() {
    ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    ensure_session_started();
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function current_user() {
    ensure_session_started();
    if (!isset($_SESSION['user_id'])) return null;
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT u.*, ur.role_name FROM `user` u JOIN `user_role` ur ON u.role_id = ur.role_id WHERE u.user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout_user(): void {
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
