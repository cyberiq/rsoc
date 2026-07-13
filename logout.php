<?php
// بدء الجلسة للوصول إلى المتغيرات الحالية وتدميرها
session_start();

// إفراغ مصفوفة الجلسة بالكامل لضمان عدم بقاء أي بيانات معلقة بالذاكرة المؤقتة للسريرفر
$_SESSION = array();

// التحقق من وجود كوكيز للجلسة في متصفح العميل وحذفها لضمان الأمان الأقصى للاتصال
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// تدمير الجلسة فعلياً على الخادم
session_destroy();

// توجيه العميل أو السائق بسلاسة إلى بوابة اختيار الحساب وبدء تسجيل الدخول مجدداً
header("Location: choose_login.php");
exit;
?>
