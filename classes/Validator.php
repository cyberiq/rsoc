<?php
class Validator {
    public static function validateEmail($email) {
        $email = trim(strtolower($email));
        if (empty($email) || strlen($email) > 255) return false;
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePassword($password) {
        if (strlen($password) < 8) return false;
        return preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password) && preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password);
    }
    
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return (substr($phone, 0, 2) === '07' && strlen($phone) === 11) ||
               (substr($phone, 0, 4) === '9647' && strlen($phone) === 12);
    }
    
    public static function validateFullName($name) {
        $name = trim($name);
        return strlen($name) >= 3 && strlen($name) <= 100 && 
               preg_match('/^[\p{L}\s\-\']+$/u', $name);
    }
    
    public static function validateProvince($province) {
        $provinces = ['بغداد', 'البصرة', 'الموصل', 'أربيل', 'السليمانية', 'كركوك', 'الحلة',
                     'النجف', 'كربلاء', 'الكاظمية', 'الناصرية', 'الديوانية', 'العمارة',
                     'دهوك', 'بدرة', 'خانقين', 'الفلوجة', 'بيجي', 'سامراء'];
        return in_array($province, $provinces);
    }
    
    public static function sanitize($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateRegistration($data) {
        $errors = [];
        if (!self::validateFullName($data['fullname'] ?? '')) $errors[] = 'الاسم غير صحيح';
        if (!self::validatePhone($data['phone'] ?? '')) $errors[] = 'رقم الهاتف غير صحيح';
        if (!self::validateProvince($data['province'] ?? '')) $errors[] = 'المحافظة غير صحيحة';
        if (!self::validateEmail($data['email'] ?? '')) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (!self::validatePassword($data['password'] ?? '')) $errors[] = 'كلمة المرور ضعيفة';
        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
