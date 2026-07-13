<?php
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Strict']);
            session_start();
        }
        self::checkTimeout();
        self::regenerateSession();
    }
    
    public static function checkTimeout() {
        $timeout = (int)getenv('SESSION_TIMEOUT') ?: 1800;
        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
            session_destroy();
            header('Location: /kreen/login.php?error=timeout');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
    
    public static function regenerateSession() {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 300) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /kreen/choose_login.php');
            exit;
        }
    }
    
    public static function requireRole($roles) {
        self::requireLogin();
        if (!in_array($_SESSION['user_type'] ?? '', $roles)) {
            header('Location: /kreen/home.php');
            exit;
        }
    }
    
    public static function destroy() {
        session_destroy();
        header('Location: /kreen/home.php');
        exit;
    }
}
