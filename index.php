<?php
// نقطة الدخول الرئيسية لتطبيق كرين
session_start();

// إعادة التوجيه إلى WAF إذا دخل المستخدم مباشرة على kreen.onrender.com
$wafUrl = getenv('WAF_URL') ?: 'https://soc-waf.onrender.com';
$directHosts = ['kreen.onrender.com', 'www.kreen.onrender.com'];
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

if (in_array($host, $directHosts, true)) {
    header("Location: {$wafUrl}{$requestUri}", true, 301);
    exit;
}

/**
 * ملف التوجيه والمدخل الرئيسي لتطبيق كرين (Routing Gatekeeper)
 * يقوم هذا السكربت بفحص الجلسات النشطة لتوفير تجربة مستخدم سريعة وآمنة.
 */

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    // المستخدم مسجل دخوله مسبقاً، نقوم بتوجيهه فوراً للوحة التحكم الذكية الخاصة به
    header("Location: dashboard.php");
    exit;
} else {
    // مستخدم جديد أو غير متصل، نوجهه مباشرة إلى الواجهة التعريفية والجمالية للتطبيق
    header("Location: home.php");
    exit;
}
?>
