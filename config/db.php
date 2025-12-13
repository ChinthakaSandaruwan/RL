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
        // Session configuration: 1 week lifetime
        $lifetime = 7 * 24 * 60 * 60; // 604,800 seconds

        ini_set('session.gc_maxlifetime', $lifetime);
        
        session_set_cookie_params([
            'lifetime' => $lifetime,
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
    $user = $stmt->fetch();
    
    if (!$user) {
        // Invalid session (User deleted or DB changed) - Force Logout
        logout_user();
        return null;
    }
    
    return $user;
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

/**
 * Check if owner has an active package with available quota for a specific type
 * @param int $userId - Owner's user ID
 * @param string $type - 'property', 'room', or 'vehicle'
 * @return array Returns ['success' => bool, 'message' => string, 'package_id' => int|null]
 */
function check_owner_package_quota($userId, $type) {
    $pdo = get_pdo();
    
    // Map type to column name
    $columnMap = [
        'property' => 'remaining_properties',
        'room' => 'remaining_rooms',
        'vehicle' => 'remaining_vehicles'
    ];
    
    if (!isset($columnMap[$type])) {
        return ['success' => false, 'message' => 'Invalid listing type.', 'package_id' => null];
    }
    
    $column = $columnMap[$type];
    
    // Find active package with remaining quota
    // status_id = 1 (active subscription), payment_status_id = 2 or 4 (paid/success)
    // expires_date NULL or future date
    $stmt = $pdo->prepare("
        SELECT bp.bought_package_id, bp.$column, bp.expires_date, p.package_name
        FROM bought_package bp
        JOIN package p ON bp.package_id = p.package_id
        WHERE bp.user_id = ? 
          AND bp.status_id = 1 
          AND bp.payment_status_id IN (2, 4)
          AND bp.$column > 0
          AND (bp.expires_date IS NULL OR bp.expires_date > NOW())
        ORDER BY bp.expires_date ASC, bp.bought_package_id ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $package = $stmt->fetch();
    
    if (!$package) {
        return [
            'success' => false, 
            'message' => 'You must purchase an ads package before adding a ' . $type . '. Please buy a package to continue.',
            'package_id' => null,
            'redirect_url' => app_url('owner/ads_package/buy/buy.php')
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Package available with ' . $package[$column] . ' ' . $type . '(s) remaining.',
        'package_id' => $package['bought_package_id'],
        'package_name' => $package['package_name'],
        'remaining' => $package[$column]
    ];
}


/**
 * Decrement package quota after successful listing creation
 * @param int $packageId - bought_package_id
 * @param string $type - 'property', 'room', or 'vehicle'
 * @return bool
 */
function decrement_package_quota($packageId, $type) {
    $pdo = get_pdo();
    
    $columnMap = [
        'property' => 'remaining_properties',
        'room' => 'remaining_rooms',
        'vehicle' => 'remaining_vehicles'
    ];
    
    if (!isset($columnMap[$type])) {
        return false;
    }
    
    $column = $columnMap[$type];
    
    $stmt = $pdo->prepare("
        UPDATE bought_package 
        SET $column = $column - 1 
        WHERE bought_package_id = ? AND $column > 0
    ");
    return $stmt->execute([$packageId]);
}

/**
 * Increment package quota after listing rejection or deletion
 * @param int $userId
 * @param string $type - 'property', 'room', or 'vehicle'
 * @return bool
 */
function increment_package_quota($userId, $type) {
    $pdo = get_pdo();
    
    $columnMap = [
        'property' => 'remaining_properties',
        'room' => 'remaining_rooms',
        'vehicle' => 'remaining_vehicles'
    ];
    
    if (!isset($columnMap[$type])) {
        return false;
    }
    
    $column = $columnMap[$type];
    
    // Find the latest approved package for this user (Active or Expired)
    // We prioritize Active/Future packages, but fallback to Expired ones if necessary
    $stmt = $pdo->prepare("
        SELECT bought_package_id FROM bought_package 
        WHERE user_id = ? 
          AND payment_status_id IN (2, 4)
        ORDER BY (expires_date IS NULL OR expires_date > NOW()) DESC, expires_date ASC, created_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $package = $stmt->fetch();
    
    if ($package) {
        $stmt = $pdo->prepare("
            UPDATE bought_package 
            SET $column = $column + 1 
            WHERE bought_package_id = ?
        ");
        return $stmt->execute([$package['bought_package_id']]);
    }
    
    return false;
}

/**
 * Create a notification for a user
 * @param int $userId
 * @param string $title
 * @param string $message
 * @param int $typeId (default 1)
 * @param int|null $propertyId (optional)
 * @return bool
 */
function create_notification($userId, $title, $message, $typeId = 1, $propertyId = null) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("INSERT INTO notification (user_id, title, message, type_id, property_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $typeId, $propertyId]);
    } catch (PDOException $e) {
        // Log error if needed
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get cached type data (property_type, room_type, vehicle_type) to reduce database queries
 * Types are cached in session since they rarely change
 * @param PDO $pdo - Database connection
 * @param string $type_table - Table name ('property_type', 'room_type', or 'vehicle_type')
 * @param int $cache_duration - Cache duration in seconds (default 1 hour)
 * @return array - Array of type records
 */
function get_cached_types($pdo, $type_table, $cache_duration = 3600) {
    ensure_session_started();
    $cache_key = "types_{$type_table}";
    
    // Check if cached in session and still valid
    if (isset($_SESSION[$cache_key]) && 
        isset($_SESSION[$cache_key . '_time']) && 
        (time() - $_SESSION[$cache_key . '_time']) < $cache_duration) {
        return $_SESSION[$cache_key];
    }
    
    // Fetch from database if not cached or expired
    $stmt = $pdo->query("SELECT * FROM {$type_table} ORDER BY type_name ASC");
    $data = $stmt->fetchAll();
    
    // Cache in session
    $_SESSION[$cache_key] = $data;
    $_SESSION[$cache_key . '_time'] = time();
    
    return $data;
}

/**
 * Clear type cache - call this when types are added/updated/deleted
 * @param string|null $type_table - Specific table to clear, or null to clear all
 */
function clear_type_cache($type_table = null) {
    ensure_session_started();
    if ($type_table) {
        unset($_SESSION["types_{$type_table}"]);
        unset($_SESSION["types_{$type_table}_time"]);
    } else {
        // Clear all type caches
        $tables = ['property_type', 'room_type', 'vehicle_type'];
        foreach ($tables as $table) {
            unset($_SESSION["types_{$table}"]);
            unset($_SESSION["types_{$table}_time"]);
        }
    }
}

function get_chat_flag_path() {
    return __DIR__ . '/../chat_config.json';
}

function is_chat_enabled(): bool {
    // Cache the result in a static variable to avoid repeated file reads
    static $cached_result = null;
    
    if ($cached_result !== null) {
        return $cached_result;
    }
    
    $path = get_chat_flag_path();
    if (!is_readable($path)) {
        $cached_result = true;
        return true;
    }

    $content = file_get_contents($path);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        $cached_result = true;
        return true;
    }

    if (!array_key_exists('enabled', $data)) {
        $cached_result = true;
        return true;
    }

    $cached_result = (bool)$data['enabled'];
    return $cached_result;
}

function set_chat_enabled(bool $enabled): void {
    $path = get_chat_flag_path();
    $data = [
        'enabled' => $enabled,
        'updated_at' => date('c')
    ];
    file_put_contents($path, json_encode($data));
}

?>
