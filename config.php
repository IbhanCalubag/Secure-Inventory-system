<?php

define('ON_NEOCITIES', true);
define('IS_PRODUCTION', true);

define('DB_PATH', __DIR__ . '/database.sqlite');
define('DB_TYPE', 'sqlite');

if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false); 
}
if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', 5);
}
if (!defined('LOGIN_TIMEOUT_MINUTES')) {
    define('LOGIN_TIMEOUT_MINUTES', 15);
}
if (!defined('SESSION_TIMEOUT_MINUTES')) {
    define('SESSION_TIMEOUT_MINUTES', 30);
}
if (!defined('CSRF_TOKEN_LIFETIME')) {
    define('CSRF_TOKEN_LIFETIME', 3600);
}
if (!defined('MAX_REQUEST_SIZE')) {
    define('MAX_REQUEST_SIZE', 10485760); // 10MB
}
if (!defined('RATE_LIMIT_REQUESTS')) {
    define('RATE_LIMIT_REQUESTS', 100);
}
if (!defined('RATE_LIMIT_WINDOW')) {
    define('RATE_LIMIT_WINDOW', 60);
}
if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 8);
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5242880); // 5MB
}
if (!defined('ALLOWED_FILE_TYPES')) {
    define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
}


ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT_MINUTES * 60);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/php_errors.log');
}

if (!headers_sent()) {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    header("Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https:; " .
           "font-src 'self' https://cdnjs.cloudflare.com; " .
           "connect-src 'self';");
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', 
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > MAX_REQUEST_SIZE) {
    error_log("Request size exceeded: " . $_SERVER['CONTENT_LENGTH']);
    http_response_code(413);
    die('Request too large');
}

if (ON_NEOCITIES && !file_exists(DB_PATH) && is_writable(dirname(DB_PATH))) {
    try {
        touch(DB_PATH);
        chmod(DB_PATH, 0644);
        error_log("Created database file: " . DB_PATH);
    } catch (Exception $e) {
        error_log("Failed to create database: " . $e->getMessage());
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}


function is_logged_in() {
    return isset($_SESSION['user_id'], $_SESSION['last_activity']) && 
           (time() - $_SESSION['last_activity'] < SESSION_TIMEOUT_MINUTES * 60);
}


function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
    $_SESSION['last_activity'] = time();
}


function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}


function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    if (isset($_SESSION['csrf_token_time']) && 
        time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}


function log_security_event($type, $description, $user_id = null) {
    $log_file = __DIR__ . '/security_events.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = "[$timestamp] [$ip] [$type] [User: $user_id] $description [Agent: $user_agent]\n";
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>
