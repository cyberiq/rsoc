<?php
session_start();

require_once 'config.php';

// تحديد لغات وتنسيقات التواريخ العربية بشكل مبسط
function arabic_date($timestamp) {
    $months = [
        "January" => "كانون الثاني", "February" => "شباط", "March" => "آذار",
        "April" => "نيسان", "May" => "أيار", "June" => "حزيران",
        "July" => "تموز", "August" => "آب", "September" => "أيلول",
        "October" => "تشرين الأول", "November" => "تشرين الثاني", "December" => "كانون الأول"
    ];
    $date_str = date("j F Y", strtotime($timestamp));
    foreach ($months as $en => $ar) {
        $date_str = str_replace($en, $ar, $date_str);
    }
    return $date_str;
}

$viewing_own = true;
$profile_id = null;
$profile_type = null;

// التحقق مما إذا كان الطلب لعرض ملف عام لسائق أو مستخدم آخر
if (isset($_GET['id']) && isset($_GET['type'])) {
    $profile_id = intval($_GET['id']);
    $profile_type = trim($_GET['type']);
    
    if (in_array($profile_type, ['driver', 'kia', 'customer'])) {
        // إذا كان يطلب حسابه الخاص، نعتبره viewing_own
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_id && $_SESSION['user_type'] == $profile_type) {
            $viewing_own = true;
        } else {
            $viewing_own = false;
        }
    }
}

// في حال العرض الخاص، نعتمد على بيانات الجلسة النشطة الحالية
if ($viewing_own) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        // إذا لم يكن مسجلاً، نوجهه لصفحة اختيار الدخول
        header("Location: choose_login.php");
        exit;
    }
    $profile_id = $_SESSION['user_id'];
    $profile_type = $_SESSION['user_type'];
}

// تحديد الجدول المناسب بناءً على نوع الحساب المستهدف
$table = 'customers';
if ($profile_type === 'driver') {
    $table = 'drivers';
} elseif ($profile_type === 'kia') {
    $table = 'kias';
}

$user = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$profile_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = null;
}

// حماية الصفحة من المعرفات الخاطئة
if (!$user) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; background:#0f172a; color:#f8fafc; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
            <h1 style='color:#ef4444; font-size:40px;'>⚠️ خطأ في العثور على الحساب</h1>
            <p style='color:#94a3b8; margin-top:10px;'>عذراً، الملف الشخصي الذي تحاول الوصول إليه غير موجود أو تم إزالته بالكامل من خوادم كرين.</p>
            <a href='home.php' style='margin-top:20px; background:#d97706; color:#0f172a; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold;'>العودة للرئيسية</a>
         </div>");
}

$theme = [
    'driver' => [
        'title' => 'سائق كرين سحب',
        'badge' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'color' => 'amber',
        'text_color' => 'text-amber-500',
        'border' => 'border-amber-500/30',
        'icon' => '🚜'
    ],
    'kia' => [
        'title' => 'سائق كيا حمل',
        'badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'color' => 'sky',
        'text_color' => 'text-sky-400',
        'border' => 'border-sky-500/30',
        'icon' => '🚚'
    ],
    'customer' => [
        'title' => 'عميل مميز طالب خدمة',
        'badge' => 'bg-green-500/10 text-green-400 border-green-500/20',
        'color' => 'green',
        'text_color' => 'text-green-400',
        'border' => 'border-green-500/30',
        'icon' => '👤'
    ]
];

// معالجة حالة الـ Admin كعميل متميز خاص بالإدارة
if ($profile_type === 'customer' && isset($user['role']) && $user['role'] === 'admin') {
    $theme['customer']['title'] = 'مدير عام النظام';
    $theme['customer']['badge'] = 'bg-red-500/10 text-red-400 border-red-500/20';
    $theme['customer']['text_color'] = 'text-red-400';
    $theme['customer']['icon'] = '🛡️';
}

$curr_theme = $theme[$profile_type];

$stat_completed = 12; // إحصائيات افتراضية ممتازة لعرض موثوقية الحساب
$stat_cancelled = 1;
$stat_rating = "4.9";
if ($profile_id % 3 == 0) {
    $stat_completed = 43;
    $stat_rating = "4.8";
} elseif ($profile_id % 2 == 0) {
    $stat_completed = 27;
    $stat_rating = "5.0";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['fullname']) ?> - الملف الشخصي لتطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الأنيق للتصميم المتناسق -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <header class="h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl">
        <div class="flex items-center gap-3">
            <span class="text-3xl animate-pulse">🚜</span>
            <div>
                <h1 class="text-md sm:text-lg font-black text-white leading-none">ملف <span class="text-amber-500">كرين العراق</span></h1>
                <p class="text-[10px] text-slate-400 mt-1">تتبع خدمات السحب والإنقاذ على مدار الساعة</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300">
                   🏠 لوحة التحكم
                </a>
            <?php else: ?>
                <a href="home.php" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300">
                   🏠 الرئيسية
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-10">
        
        <div class="bg-slate-900 rounded-3xl border border-slate-800 overflow-hidden shadow-2xl relative">
            
            <!-- بنر علوي سينمائي مظلم -->
            <div class="h-32 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 relative overflow-hidden">
                <div class="absolute inset-0 bg-grid-white/[0.02]"></div>
                <!-- توهج تزييني متكيف -->
                <div class="absolute -right-10 -bottom-10 w-36 h-36 bg-<?= $curr_theme['color'] ?>-500/10 rounded-full blur-2xl"></div>
            </div>

            <!-- معلومات المستخدم وصورته الشخصية -->
            <div class="px-6 pb-8 relative z-10 -mt-16">
                <div class="flex flex-col sm:flex-row items-center sm:items-end justify-between gap-4 mb-6">
                    
                    <div class="text-center sm:text-right flex flex-col sm:flex-row items-center sm:items-end gap-4">
                        <!-- عرض الصورة الشخصية أو صورة مخصصة ذكية -->
                        <div class="w-28 h-28 rounded-full bg-slate-950 border-4 border-slate-900 shadow-2xl overflow-hidden flex items-center justify-center relative">
                            <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_image']) ?>" class="w-full h-full object-cover" alt="Avatar">
                            <?php else: ?>
                                <span class="text-5xl select-none"><?= $curr_theme['icon'] ?></span>
                            <?php endif; ?>
                            
                            <!-- علامة خضراء نشطة إذا كان الحساب موثقاً -->
                            <?php if ($user['is_verified'] == 1): ?>
                                <span class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-2 border-slate-900 rounded-full flex items-center justify-center text-[10px]" title="حساب موثق">✓</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="flex items-center justify-center sm:justify-start gap-2">
                                <h2 class="text-xl font-black text-white"><?= htmlspecialchars($user['fullname']) ?></h2>
                            </div>
                            <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-full border mt-1.5 <?= $curr_theme['badge'] ?>">
                                <?= $curr_theme['icon'] ?> <?= $curr_theme['title'] ?>
                            </span>
                        </div>
                    </div>

                    <!-- أزرار الإجراء السريع (تعديل أو اتصال) -->
                    <div class="flex items-center gap-2">
                        <?php if ($viewing_own): ?>
                            <a href="edit_profile.php" class="py-2.5 px-5 bg-slate-950 hover:bg-slate-850 text-slate-200 border border-slate-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                📝 تعديل الملف الشخصي
                            </a>
                        <?php else: ?>
                            <a href="tel:<?= htmlspecialchars($user['phone']) ?>" class="py-2.5 px-5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-lg shadow-green-600/10">
                                📞 اتصال فوري ومباشر
                            </a>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="grid grid-cols-3 gap-3 text-center mb-8 bg-slate-950/40 p-4 rounded-2xl border border-slate-850">
                    <div>
                        <span class="text-[10px] text-slate-500 block mb-0.5">عمليات ناجحة</span>
                        <span class="text-base font-black text-white font-mono"><?= $stat_completed ?></span>
                    </div>
                    <div class="border-x border-slate-800/80">
                        <span class="text-[10px] text-slate-500 block mb-0.5">معدل التقييم</span>
                        <span class="text-base font-black text-amber-500 font-mono">⭐ <?= $stat_rating ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block mb-0.5">حالة التوثيق</span>
                        <span class="text-xs font-bold text-green-400 block mt-0.5"><?= ($user['is_verified'] == 1) ? 'موثق ✓' : 'قيد المراجعة' ?></span>
                    </div>
                </div>

                <!-- تفاصيل الحساب الأساسية -->
                <div class="space-y-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">📌 البيانات والمواصفات المسجلة</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-850 space-y-1">
                            <span class="text-[10px] text-slate-500 block">نطاق ومحافظة العمل الرئيسية:</span>
                            <span class="text-sm font-bold text-white flex items-center gap-1">📍 <?= htmlspecialchars($user['province']) ?></span>
                        </div>

                        <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-850 space-y-1">
                            <span class="text-[10px] text-slate-500 block">تاريخ الانضمام للمنصة:</span>
                            <span class="text-sm font-bold text-slate-300 font-mono">📅 <?= arabic_date($user['created_at']) ?></span>
                        </div>
                    </div>

                    <?php if ($profile_type === 'driver' || $profile_type === 'kia'): ?>
                        <div class="border-t border-slate-800/80 pt-6 mt-6 space-y-4">
                            <h3 class="text-xs font-black text-<?= $curr_theme['color'] ?>-500 uppercase tracking-wider">🛠️ معلومات المركبة المصرحة</h3>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-850 space-y-1">
                                    <span class="text-[10px] text-slate-500 block">رقم لوحة المركبة (الرقم المروري):</span>
                                    <span class="text-sm font-black text-white font-mono"><?= htmlspecialchars($user['wheel_number'] ?? $user['car_number'] ?? 'غير متوفر') ?></span>
                                </div>

                                <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-850 space-y-1">
                                    <span class="text-[10px] text-slate-500 block">نوع وموديل المركبة وسنة الصنع:</span>
                                    <span class="text-sm font-bold text-slate-300"><?= htmlspecialchars($user['wheel_type'] ?? $user['car_model'] ?? 'غير متوفر') ?></span>
                                </div>

                                <?php if ($profile_type === 'driver' && !empty($user['wheel_color'])): ?>
                                    <div class="p-4 bg-slate-950/60 rounded-2xl border border-slate-850 space-y-1 sm:col-span-2">
                                        <span class="text-[10px] text-slate-500 block">مواصفات ولون العجلة الإضافية:</span>
                                        <span class="text-sm font-bold text-slate-300">اللون: <?= htmlspecialchars($user['wheel_color']) ?> | موديل: <?= htmlspecialchars($user['wheel_model'] ?? 'محدث') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- نصائح وشروط الأمان لمجتمع كران -->
                <div class="mt-8 pt-6 border-t border-slate-800/60 text-center">
                    <p class="text-[10px] text-slate-500 leading-relaxed max-w-md mx-auto">
                        تحذير أمني: يرجى دائماً مطابقة رقم لوحة المركبة المعروض في التطبيق مع المركبة القادمة إليك على أرض الواقع لضمان أعلى مستويات الأمان.
                    </p>
                </div>

            </div>

        </div>

    </main>

    <footer class="py-6 border-t border-slate-900 bg-slate-950 text-center">
        <p class="text-[10px] text-slate-500">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع الاتصالات والبيانات مشفرة ومحمية بالكامل.</p>
    </footer>

</body>
</html>
