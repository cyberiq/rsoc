<?php
class CSRFProtection {
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function getToken() {
        return self::generateToken();
    }
    
    public static function verifyToken($token) {
        if (!isset($_SESSION['csrf_token'])) return false;
        if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function regenerateToken() {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return self::generateToken();
    }
    
    public static function field() {
        $token = self::getToken();
        return "<input type='hidden' name='csrf_token' value='{$token}'>";
    }
}
