<?php
session_start();
require 'config.php';

// تأكيد تسجيل الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: choose_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// تحديد الجدول والستايل حسب نوع الحساب لضمان التناسق البصري الفخم
$table = ($user_type === 'driver') ? 'drivers' : (($user_type === 'kia') ? 'kias' : 'customers');

try {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = null;
}

if (!$user) {
    session_destroy();
    header("Location: choose_login.php");
    exit;
}

$theme = [
    'driver' => [
        'title' => 'لوحة السائق (كرين سحب)',
        'accent' => 'amber',
        'color_code' => '#f59e0b',
        'badge' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'btn' => 'bg-amber-500 hover:bg-amber-600 text-slate-950',
        'icon' => '🚜',
        'target_dashboard' => 'driver_dashboard.php',
        'dashboard_desc' => 'افتح اللوحة الجغرافية فوراً للبدء باستقبال وتأكيد طلبات سحب وإنقاذ السيارات على الخريطة.'
    ],
    'kia' => [
        'title' => 'لوحة السائق (كيا حمل)',
        'accent' => 'sky',
        'color_code' => '#0ea5e9',
        'badge' => 'bg-sky-500/10 text-sky-500 border-sky-500/20',
        'btn' => 'bg-sky-500 hover:bg-sky-600 text-slate-950',
        'icon' => '🚚',
        'target_dashboard' => 'driver_dashboard.php',
        'dashboard_desc' => 'ادخل للوحة التحكم لتتبع الطلبات الحالية ونقل الأحمال الخفيفة المتاحة في منطقتك.'
    ],
    'customer' => [
        'title' => 'بوابة العميل المميز',
        'accent' => 'green',
        'color_code' => '#10b981',
        'badge' => 'bg-green-500/10 text-green-500 border-green-500/20',
        'btn' => 'bg-green-500 hover:bg-green-600 text-slate-950',
        'icon' => '👤',
        'target_dashboard' => 'customer_dashboard.php',
        'dashboard_desc' => 'افتح الخريطة التفاعلية لتتبع مواقع السائقين القريبين منك واطلب ونش الإنقاذ بلمسة واحدة.'
    ]
];

$curr_theme = $theme[$user_type];
$verified_status = ($user['is_verified'] == 1) ? '✅ حساب موثق ومفعل' : '❌ قيد التحقق';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $curr_theme['title'] ?> - تطبيق كرين</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
    body { font-family: 'Cairo', sans-serif; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

  <header class="h-20 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-xl">
    <div class="flex items-center gap-3">
      <span class="text-3xl animate-bounce">🚜</span>
      <div>
        <h1 class="text-md sm:text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين العراق</span></h1>
        <p class="text-[10px] text-slate-400 mt-1">منصة إنقاذ وسحب العجلات على مدار الساعة</p>
      </div>
    </div>
    
    <div>
      <a href="logout.php" class="px-4 py-2 bg-red-950/40 hover:bg-red-900/40 border border-red-900/30 text-red-400 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
         🚪 تسجيل خروج
      </a>
    </div>
  </header>

  <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-10 flex flex-col md:flex-row gap-8 items-start justify-center">
    
    <!-- الكرت الجانبي: الملف الشخصي والبيانات للقرائة والتوثيق -->
    <div class="w-full md:w-80 bg-slate-900/60 rounded-3xl border border-slate-800 p-6 space-y-6">
      <div class="text-center space-y-3">
        <!-- صورة الملف الشخصي أو صورة بديلة -->
        <div class="relative w-20 h-20 mx-auto">
          <?php if (!empty($user['profile_image'])): ?>
            <img src="<?= htmlspecialchars($user['profile_image']) ?>" class="w-full h-full rounded-full object-cover border-2 border-slate-750" alt="Avatar">
          <?php else: ?>
            <div class="w-full h-full rounded-full bg-slate-800 flex items-center justify-center text-3xl border-2 border-slate-700">
              <?= $curr_theme['icon'] ?>
            </div>
          <?php endif; ?>
          <span class="absolute bottom-0 right-0 w-5 h-5 rounded-full bg-green-500 border-2 border-slate-900" title="متصل الآن"></span>
        </div>
        
        <div>
          <h2 class="text-md font-black text-white"><?= htmlspecialchars($user['fullname']) ?></h2>
          <span class="inline-block mt-1 px-3 py-0.5 text-[10px] font-bold rounded-full border <?= $curr_theme['badge'] ?>">
            <?= $curr_theme['title'] ?>
          </span>
        </div>
      </div>

      <div class="border-t border-slate-800/80 pt-4 space-y-3.5 text-xs">
        <div>
          <span class="text-slate-500 block mb-0.5">البريد الإلكتروني:</span>
          <span class="font-bold text-slate-300 font-mono"><?= htmlspecialchars($user['email']) ?></span>
        </div>
        <div>
          <span class="text-slate-500 block mb-0.5">رقم الهاتف:</span>
          <span class="font-bold text-slate-300 font-mono"><?= htmlspecialchars($user['phone']) ?></span>
        </div>
        <div>
          <span class="text-slate-500 block mb-0.5">المحافظة / النطاق:</span>
          <span class="font-bold text-slate-300">📍 <?= htmlspecialchars($user['province']) ?></span>
        </div>
        <div>
          <span class="text-slate-500 block mb-0.5">حالة تفعيل الحساب:</span>
          <span class="font-black text-slate-200"><?= $verified_status ?></span>
        </div>
        <div>
          <span class="text-slate-500 block mb-0.5">الرصيد الحالي:</span>
          <span class="font-black text-emerald-400"><?= number_format((int) ($user['balance_iqd'] ?? 0)) ?> د.ع</span>
        </div>
        
        <?php if($user_type==='driver' || $user_type==='kia'): ?>
          <div class="border-t border-slate-800/80 pt-4 space-y-3">
            <div>
              <span class="text-slate-500 block mb-0.5">رقم العجلة واللوحة:</span>
              <span class="font-bold text-slate-300"><?= htmlspecialchars($user['wheel_number'] ?? $user['car_number'] ?? 'غير متوفر') ?></span>
            </div>
            <div>
              <span class="text-slate-500 block mb-0.5">نوع وموديل المركبة:</span>
              <span class="font-bold text-slate-300"><?= htmlspecialchars($user['wheel_type'] ?? $user['car_model'] ?? 'غير متوفر') ?></span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="flex-1 w-full space-y-6">
      
      <!-- كرت الخدمة الأساسي (توجيه فوري للخرائط) -->
      <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 relative overflow-hidden group">
        <!-- طبقة مظهر مموجة بالخلفية -->
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-<?= $curr_theme['accent'] ?>-500/10 rounded-full blur-3xl group-hover:scale-125 transition duration-500"></div>
        
        <div class="space-y-4 relative z-10">
          <span class="text-4xl inline-block drop-shadow-lg"><?= $curr_theme['icon'] ?></span>
          <h3 class="text-xl font-black text-white">البوابه الجغرافية المباشرة للخدمات</h3>
          <p class="text-xs text-slate-400 leading-relaxed max-w-md">
            <?= $curr_theme['dashboard_desc'] ?>
          </p>
          
          <div class="pt-2">
            <a href="<?= $curr_theme['target_dashboard'] ?>" 
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl <?= $curr_theme['btn'] ?> text-xs font-black transition-all duration-150 transform active:scale-95 shadow-lg shadow-<?= $curr_theme['accent'] ?>-500/10">
               🚀 انطلق الآن إلى لوحة الخرائط الفورية
            </a>
          </div>
        </div>
      </div>

      <!-- كروت الروابط الفرعية الثنائية -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        
        <!-- كرت الإعدادات والتعديل الفاخر -->
        <a href="account_settings.php" class="p-5 bg-slate-900/40 hover:bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl transition-all duration-150 group">
          <div class="flex items-center gap-4">
            <span class="text-2xl p-3 bg-slate-950/60 rounded-xl group-hover:scale-110 transition duration-200">⚙️</span>
            <div>
              <h4 class="text-xs font-bold text-white">تعديل الملف والخصوصية</h4>
              <p class="text-[10px] text-slate-500 mt-0.5">تحديث الاسم، الهاتف، كلمة المرور والصورة الشخصية</p>
            </div>
          </div>
        </a>

        <!-- كرت الدعم الفني وطلب الاستفسار السريع -->
        <a href="home.php#contact" class="p-5 bg-slate-900/40 hover:bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl transition-all duration-150 group">
          <div class="flex items-center gap-4">
            <span class="text-2xl p-3 bg-slate-950/60 rounded-xl group-hover:scale-110 transition duration-200">📞</span>
            <div>
              <h4 class="text-xs font-bold text-white">الدعم الفني والمساعدة</h4>
              <p class="text-[10px] text-slate-500 mt-0.5">هل تحتاج لمساعدة؟ تواصل مع فريق دعم كرين الفوري</p>
            </div>
          </div>
        </a>

      </div>

    </div>

  </main>

  <footer class="py-6 border-t border-slate-850 bg-slate-950 text-center">
    <p class="text-[10px] text-slate-500">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع الاتصالات والبيانات مشفرة ومحمية بالكامل.</p>
  </footer>

</body>
</html>
