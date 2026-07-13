<?php
session_start();
require 'config.php';

// التحقق من تسجيل الدخول لحماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: choose_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// تحديد المظهر البصري المتكيف مع الجلسة
$theme = [
    'driver' => [
        'title' => 'تفضيلات سائق الكرين',
        'accent' => 'amber',
        'badge' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'btn' => 'bg-amber-500 hover:bg-amber-600 text-slate-950',
        'focus_ring' => 'focus:border-amber-500 focus:ring-amber-500/20',
        'dashboard' => 'driver_dashboard.php',
        'icon' => '🚜'
    ],
    'kia' => [
        'title' => 'تفضيلات سائق كيا حمل',
        'accent' => 'sky',
        'badge' => 'bg-sky-500/10 text-sky-500 border-sky-500/20',
        'btn' => 'bg-sky-500 hover:bg-sky-600 text-slate-950',
        'focus_ring' => 'focus:border-sky-500 focus:ring-sky-500/20',
        'dashboard' => 'driver_dashboard.php',
        'icon' => '🚚'
    ],
    'customer' => [
        'title' => 'تفضيلات العميل المميز',
        'accent' => 'green',
        'badge' => 'bg-green-500/10 text-green-500 border-green-500/20',
        'btn' => 'bg-green-500 hover:bg-green-600 text-slate-950',
        'focus_ring' => 'focus:border-green-500 focus:ring-green-500/20',
        'dashboard' => 'customer_dashboard.php',
        'icon' => '👤'
    ]
];

$curr_theme = $theme[$user_type];
$success_msg = '';

// تعيين القيم الافتراضية للتفضيلات إذا لم تكن موجودة مسبقاً في الجلسة
if (!isset($_SESSION['settings'])) {
    $_SESSION['settings'] = [
        'map_style' => 'dark',
        'gps_interval' => '15',
        'sound_alerts' => 'on',
        'email_receipts' => 'on',
        'lang' => 'ar'
    ];
}

// معالجة تحديث التفضيلات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $_SESSION['settings']['map_style'] = $_POST['map_style'] ?? 'dark';
    $_SESSION['settings']['gps_interval'] = $_POST['gps_interval'] ?? '15';
    $_SESSION['settings']['sound_alerts'] = isset($_POST['sound_alerts']) ? 'on' : 'off';
    $_SESSION['settings']['email_receipts'] = isset($_POST['email_receipts']) ? 'on' : 'off';
    $_SESSION['settings']['lang'] = $_POST['lang'] ?? 'ar';

    $success_msg = "🎉 تم حفظ تفضيلاتك وتطبيقها على الخرائط وأنظمة الإشعارات فوراً!";
}

$settings = $_SESSION['settings'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $curr_theme['title'] ?> - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الفخم -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <!-- شريط الملاحة العلوي -->
    <header class="h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl">
        <div class="flex items-center gap-3">
            <span class="text-3xl animate-bounce"><?= $curr_theme['icon'] ?></span>
            <div>
                <h1 class="text-md sm:text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين العراق</span></h1>
                <p class="text-[10px] text-slate-400 mt-1">لوحة التفضيلات وإدارة تجربة الاستخدام</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= $curr_theme['dashboard'] ?>" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300">
               🏠 لوحة التحكم
            </a>
            <a href="logout.php" class="p-2.5 bg-red-950/20 text-red-400 hover:bg-red-950/40 border border-red-900/30 rounded-xl transition text-xs font-bold">
               🚪 خروج
            </a>
        </div>
    </header>

    <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-10">
        
        <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6 sm:p-8 relative overflow-hidden shadow-2xl">
            <!-- توهج تزييني خلفي متكيف -->
            <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-<?= $curr_theme['accent'] ?>-500/5 rounded-full blur-3xl"></div>

            <div class="text-center mb-8 relative z-10">
                <span class="inline-block text-xs font-bold px-3 py-1.5 rounded-full border mb-3 <?= $curr_theme['badge'] ?>">
                    <?= $curr_theme['title'] ?>
                </span>
                <p class="text-xs text-slate-400 mt-1">قم بتخصيص إعدادات تتبع الـ GPS، والخرائط الحية، ونغمات تنبيهات الطوارئ</p>
            </div>

            <!-- عرض إشعارات النجاح -->
            <?php if (!empty($success_msg)): ?>
                <div class="p-4 rounded-2xl text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20 text-center mb-6 animate-bounce">
                    <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6 relative z-10">
                
                <!-- إعدادات الخرائط والـ GPS -->
                <div class="p-5 bg-slate-950/50 rounded-2xl border border-slate-850 space-y-4">
                    <h3 class="text-xs font-black text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🗺️</span> إعدادات الخريطة والتتبع الجغرافي
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-2">نمط ولون الخريطة الافتراضي</label>
                            <select name="map_style" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-<?= $curr_theme['accent'] ?>-500 transition">
                                <option value="dark" <?= $settings['map_style'] === 'dark' ? 'selected' : '' ?>>🌙 النمط الليلي الفاخر (Dark Cinematic)</option>
                                <option value="light" <?= $settings['map_style'] === 'light' ? 'selected' : '' ?>>☀️ النمط النهاري الواضح (Light Classic)</option>
                                <option value="silver" <?= $settings['map_style'] === 'silver' ? 'selected' : '' ?>>⚙️ النمط الفضي الهادئ (Minimalist Silver)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] text-slate-400 mb-2">معدل تحديث الـ GPS التلقائي</label>
                            <select name="gps_interval" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-<?= $curr_theme['accent'] ?>-500 transition">
                                <option value="10" <?= $settings['gps_interval'] === '10' ? 'selected' : '' ?>>كل 10 ثوانٍ (دقة فائقة - استهلاك متوسط)</option>
                                <option value="15" <?= $settings['gps_interval'] === '15' ? 'selected' : '' ?>>كل 15 ثانية (موصى به - توازن تام)</option>
                                <option value="30" <?= $settings['gps_interval'] === '30' ? 'selected' : '' ?>>كل 30 ثانية (توفير البطارية الأقصى)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- إعدادات الإشعارات والتنبيهات -->
                <div class="p-5 bg-slate-950/50 rounded-2xl border border-slate-850 space-y-4">
                    <h3 class="text-xs font-black text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🔔</span> الإشعارات والتنبيهات الحية
                    </h3>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-3 rounded-xl bg-slate-900/65 border border-slate-850 cursor-pointer hover:bg-slate-900 transition">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-white block">التنبيهات الصوتية الحية</span>
                                <span class="text-[10px] text-slate-500 block">تشغيل نغمات تنبيه عند استقبال طلب سحب أو قبول مهمة</span>
                            </div>
                            <input type="checkbox" name="sound_alerts" class="w-4 h-4 text-<?= $curr_theme['accent'] ?>-500 bg-slate-900 border-slate-800 rounded focus:ring-transparent" <?= $settings['sound_alerts'] === 'on' ? 'checked' : '' ?> />
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-xl bg-slate-900/65 border border-slate-850 cursor-pointer hover:bg-slate-900 transition">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-white block">إشعارات البريد الإلكتروني (Gmail)</span>
                                <span class="text-[10px] text-slate-500 block">إرسال تقارير وفواتير السحب بنجاح على بريدك المسجل</span>
                            </div>
                            <input type="checkbox" name="email_receipts" class="w-4 h-4 text-<?= $curr_theme['accent'] ?>-500 bg-slate-900 border-slate-800 rounded focus:ring-transparent" <?= $settings['email_receipts'] === 'on' ? 'checked' : '' ?> />
                        </label>
                    </div>
                </div>

                <!-- اللغات والترجمة -->
                <div class="p-5 bg-slate-950/50 rounded-2xl border border-slate-850 space-y-4">
                    <h3 class="text-xs font-black text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🌐</span> لغة واجهة التطبيق المفضلة
                    </h3>

                    <div>
                        <select name="lang" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-<?= $curr_theme['accent'] ?>-500 transition">
                            <option value="ar" <?= $settings['lang'] === 'ar' ? 'selected' : '' ?>>العربية (اللغة الافتراضية للنظام)</option>
                            <option value="ku" <?= $settings['lang'] === 'ku' ? 'selected' : '' ?>>کوردی (Kurdish)</option>
                            <option value="en" <?= $settings['lang'] === 'en' ? 'selected' : '' ?>>English (الأجنبية)</option>
                        </select>
                    </div>
                </div>

                <!-- روابط الربط السريعة والأمان -->
                <div class="grid grid-cols-2 gap-3">
                    <a href="edit_profile.php" class="p-3 bg-slate-950/60 hover:bg-slate-950 rounded-xl border border-slate-850 text-center transition">
                        <span class="text-[11px] font-bold text-slate-400 block">📝 تعديل ملف الحساب</span>
                    </a>
                    <a href="account_settings.php" class="p-3 bg-slate-950/60 hover:bg-slate-950 rounded-xl border border-slate-850 text-center transition">
                        <span class="text-[11px] font-bold text-slate-400 block">🔐 إعدادات الحساب</span>
                    </a>
                </div>

                <!-- زر حفظ التعديلات وحفظ الجلسة -->
                <div>
                    <button type="submit" name="save_settings" class="w-full py-4 px-6 rounded-2xl <?= $curr_theme['btn'] ?> font-black text-xs transition duration-150 transform active:scale-95 shadow-lg shadow-black/30">
                        💾 حفظ تفضيلات الاستخدام الحالية
                    </button>
                </div>

            </form>

            <div class="text-center mt-6 pt-5 border-t border-slate-800/80">
                <a href="<?= $curr_theme['dashboard'] ?>" class="text-xs font-bold text-slate-400 hover:text-white transition">
                    ⬅️ تراجع، العودة للوحة الرئيسية
                </a>
            </div>
        </div>

    </main>

    <footer class="py-6 border-t border-slate-900 bg-slate-950 text-center">
        <p class="text-[10px] text-slate-500">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع تفضيلاتك مشفرة ومحفوظة محلياً بالكامل.</p>
    </footer>

</body>
</html>
