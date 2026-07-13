<?php
session_start();
require 'config.php';

// حماية الصفحة: لا يُسمح بالوصول إليها إلا لمدير النظام (Admin) المسجل دخوله
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // توجيه غير المصرح لهم إلى بوابة الدخول
    header("Location: choose_login.php");
    exit;
}

// جلب تفاصيل سريعة عن بيئة تشغيل الخادم
$php_version = PHP_VERSION;
$sqlite_loaded = extension_loaded('pdo_sqlite') ? '✅ مفعل ونشط' : '❌ غير مفعل';
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time') . ' ثانية';

// في حال طلب المدير عرض صفحة phpinfo() الافتراضية والكاملة بشكل مؤقت
if (isset($_GET['raw_info']) && $_GET['raw_info'] === 'true') {
    phpinfo();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تشخيص النظام - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الأنيق -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <!-- هيدر اللوحة -->
    <header class="h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl">
        <div class="flex items-center gap-3">
            <span class="text-3xl animate-pulse">⚙️</span>
            <div>
                <h1 class="text-md sm:text-lg font-black text-white leading-none">لوحة تشخيص خادم <span class="text-amber-500">كرين</span></h1>
                <p class="text-[10px] text-slate-400 mt-1">خاص بالإدارة العامة والمطورين فقط</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="dashboard.php" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300">
               🏠 الرئيسية
            </a>
            <a href="logout.php" class="p-2.5 bg-red-950/20 text-red-400 hover:bg-red-950/40 border border-red-900/30 rounded-xl transition text-xs font-bold">
               🚪 خروج
            </a>
        </div>
    </header>

    <!-- المحتوى الرئيسي للمعلومات -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-10">
        
        <div class="bg-slate-900 rounded-3xl border border-slate-800 p-8 shadow-2xl relative overflow-hidden">
            <!-- توهج تزييني خلفي -->
            <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-amber-500/5 rounded-full blur-3xl"></div>

            <div class="text-center mb-8 relative z-10">
                <span class="inline-block px-3 py-1 text-xs font-bold rounded-full bg-red-500/10 text-red-400 border border-red-500/20 mb-3">
                    🛡️ منطقة إدارية آمنة
                </span>
                <h2 class="text-xl font-black text-white">تفاصيل ومواصفات الخادم الحالية</h2>
                <p class="text-xs text-slate-400 mt-1">مؤشرات تشغيل نظام PHP وقاعدة بيانات SQLite لتطبيق كرين العراقي.</p>
            </div>

            <!-- شبكة تفاصيل الخادم -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 relative z-10">
                
                <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-850">
                    <span class="text-[10px] text-slate-500 block">إصدار PHP الحالي:</span>
                    <span class="text-sm font-bold text-amber-500 font-mono"><?= $php_version ?></span>
                </div>

                <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-850">
                    <span class="text-[10px] text-slate-500 block">مشغل قاعدة بيانات SQLite (PDO):</span>
                    <span class="text-sm font-bold text-slate-200"><?= $sqlite_loaded ?></span>
                </div>

                <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-850">
                    <span class="text-[10px] text-slate-500 block">الحد الأقصى لرفع الملفات (Upload Limit):</span>
                    <span class="text-sm font-bold text-slate-200 font-mono"><?= $upload_max ?></span>
                </div>

                <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-850">
                    <span class="text-[10px] text-slate-500 block">الحد الأقصى للبيانات المرسلة (Post Limit):</span>
                    <span class="text-sm font-bold text-slate-200 font-mono"><?= $post_max ?></span>
                </div>

                <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-850">
                    <span class="text-[10px] text-slate-500 block">الحد الأقصى للذاكرة المخصصة (Memory Limit):</span>
                    <span class="text-sm font-bold text-slate-200 font-mono"><?= $memory_limit ?></span>
                </div>

                <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-850">
                    <span class="text-[10px] text-slate-500 block">الحد الأقصى لزمن تنفيذ السكربت:</span>
                    <span class="text-sm font-bold text-slate-200"><?= $max_execution_time ?></span>
                </div>

            </div>

            <!-- أزرار خيارات المطور -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row gap-4 justify-between items-center relative z-10">
                <p class="text-[10px] text-slate-500">ملاحظة أمنية: لا تشارك تفاصيل هذه الصفحة مع أي جهة غير موثوقة.</p>
                
                <a href="?raw_info=true" target="_blank"
                   class="py-2.5 px-5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black rounded-xl text-xs transition duration-150 transform active:scale-95 shadow-lg shadow-amber-500/10 flex items-center gap-1.5">
                    📂 استعراض ملف PHPInfo الكامل ⬅️
                </a>
            </div>

        </div>

    </main>

    <!-- الفوتر -->
    <footer class="py-6 border-t border-slate-900 bg-slate-950 text-center">
        <p class="text-[10px] text-slate-500">حقوق الإدارة محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع الجلسات والبيانات مشفرة بالكامل.</p>
    </footer>

</body>
</html>
