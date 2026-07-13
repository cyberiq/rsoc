<?php
require 'autoloader.php';
require 'config-improved.php';

SessionManager::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /kreen/choose_login.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        session_destroy();
        header('Location: /kreen/login.php');
        exit;
    }
    
    $customers_count = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE role != 'admin'")->fetch()['count'];
    $drivers_count = $pdo->query("SELECT COUNT(*) as count FROM drivers")->fetch()['count'];
    $kias_count = $pdo->query("SELECT COUNT(*) as count FROM kias")->fetch()['count'];
    $requests_count = $pdo->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'")->fetch()['count'];
    
} catch (PDOException $e) {
    die("❌ خطأ في قاعدة البيانات");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - تطبيق كرين</title>
    <script src="https://cdn.tailwindcss.com"><'/script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <nav class="bg-slate-800 border-b border-slate-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🚕</span>
                    <h1 class="text-2xl font-black text-white">لوحة التحكم</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-slate-300">مرحباً، <?= htmlspecialchars($admin['fullname']) ?></span>
                    <a href="logout.php" class="text-red-400 hover:text-red-300">تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-blue-100 text-sm font-semibold">العملاء</p>
                        <p class="text-4xl font-black mt-2"><?= $customers_count ?></p>
                    </div>
                    <span class="text-5xl">👤</span>
                </div>
                <a href="admin-users.php?type=customer" class="text-blue-100 hover:text-white text-sm mt-4 block">عرض الكل →</a>
            </div>

            <div class="bg-gradient-to-br from-amber-600 to-orange-700 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-amber-100 text-sm font-semibold">السائقون</p>
                        <p class="text-4xl font-black mt-2"><?= $drivers_count ?></p>
                    </div>
                    <span class="text-5xl">🚕</span>
                </div>
                <a href="admin-users.php?type=driver" class="text-amber-100 hover:text-white text-sm mt-4 block">عرض الكل →</a>
            </div>

            <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-green-100 text-sm font-semibold">شركات النقل</p>
                        <p class="text-4xl font-black mt-2"><?= $kias_count ?></p>
                    </div>
                    <span class="text-5xl">🚙</span>
                </div>
                <a href="admin-users.php?type=kia" class="text-green-100 hover:text-white text-sm mt-4 block">عرض الكل →</a>
            </div>

            <div class="bg-gradient-to-br from-red-600 to-pink-700 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-red-100 text-sm font-semibold">طلبات معلقة</p>
                        <p class="text-4xl font-black mt-2"><?= $requests_count ?></p>
                    </div>
                    <span class="text-5xl">⏳</span>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6 shadow-lg mb-6">
            <h2 class="text-xl font-bold text-white mb-4">⚙️ الإجراءات السريعة</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <a href="admin-users.php" class="block w-full text-center py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">
                    👥 إدارة المستخدمين
                </a>
                
                <a href="admin-settings.php" class="block w-full text-center py-3 px-4 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition">
                    ⚙️ الإعدادات
                </a>
                
                <a href="logout.php" class="block w-full text-center py-3 px-4 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition">
                    🔓 تسجيل الخروج
                </a>
            </div>
        </div>
    </div>
</body>
</html>
