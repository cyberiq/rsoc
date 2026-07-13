<?php
class SecurityLogger {
    private $log_dir = '/var/www/html/kreen/logs';
    
    public function __construct() {
        if (!is_dir($this->log_dir)) mkdir($this->log_dir, 0755, true);
    }
    
    public function log($event_type, $data = []) {
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $ip = $this->getClientIP();
        $log_entry = [
            'timestamp' => $time,
            'event' => $event_type,
            'ip' => $ip,
            'user_id' => $_SESSION['user_id'] ?? null,
            'data' => json_encode($data)
        ];
        $log_file = $this->log_dir . "/security-{$date}.log";
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
    }
    
    public function logFailedLogin($email) {
        $this->log('FAILED_LOGIN', ['email' => $email]);
    }
    
    public function logSuccessfulLogin($user_id) {
        $this->log('SUCCESSFUL_LOGIN', ['user_id' => $user_id]);
    }
    
    public function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        else $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return trim($ip);
    }
}
