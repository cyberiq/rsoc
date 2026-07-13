<?php
session_start();
require 'config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: choose_login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// تحديد الجدول المناسب والسمة البصرية بناءً على نوع المستخدم لضمان التناسق الإبداعي
$table = 'customers';
if ($user_type === 'driver') {
    $table = 'drivers';
} elseif ($user_type === 'kia') {
    $table = 'kias';
}

$theme = [
    'driver' => [
        'title' => 'تعديل ملف سائق الكرين',
        'accent' => 'amber',
        'badge' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'btn' => 'bg-amber-500 hover:bg-amber-600 text-slate-950',
        'focus_ring' => 'focus:border-amber-500 focus:ring-amber-500/20',
        'icon' => '🚜'
    ],
    'kia' => [
        'title' => 'تعديل ملف سائق كيا حمل',
        'accent' => 'sky',
        'badge' => 'bg-sky-500/10 text-sky-500 border-sky-500/20',
        'btn' => 'bg-sky-500 hover:bg-sky-600 text-slate-950',
        'focus_ring' => 'focus:border-sky-500 focus:ring-sky-500/20',
        'icon' => '🚚'
    ],
    'customer' => [
        'title' => 'تعديل حساب العميل المميز',
        'accent' => 'green',
        'badge' => 'bg-green-500/10 text-green-500 border-green-500/20',
        'btn' => 'bg-green-500 hover:bg-green-600 text-slate-950',
        'focus_ring' => 'focus:border-green-500 focus:ring-green-500/20',
        'icon' => '👤'
    ]
];

$curr_theme = $theme[$user_type];
$success_msg = '';
$error_msg = '';

$provinces = [
    'بغداد', 'البصرة', 'الموصل', 'أربيل', 'النجف', 'كربلاء',
    'ذي قار', 'الديوانية', 'صلاح الدين', 'كركوك', 'ميسان',
    'الأنبار', 'دهوك', 'السليمانية', 'القادسية', 'واسط',
    'بابل', 'حلبجة', 'الحلة'
];

// جلب البيانات الحالية للمستخدم
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

// معالجة تحديث البيانات عند إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $province = $_POST['province'] ?? '';
    
    // بيانات المركبة الخاصة بالسائقين فقط
    $wheel_number = trim($_POST['wheel_number'] ?? $_POST['car_number'] ?? '');
    $wheel_type = trim($_POST['wheel_type'] ?? $_POST['car_model'] ?? '');
    $wheel_color = trim($_POST['wheel_color'] ?? '');
    $wheel_model = trim($_POST['wheel_model'] ?? '');

    if (empty($fullname) || empty($phone) || empty($province)) {
        $error_msg = "❌ يرجى ملء كافة الحقول الأساسية المطلوبة.";
    } elseif (!in_array($province, $provinces)) {
        $error_msg = "❌ يرجى اختيار محافظة عراقية صالحة من القائمة.";
    } else {
        try {
            $profile_image_path = $user['profile_image'] ?? '';

            // معالجة رفع الصورة الشخصية وتأمينها
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['profile_image']['tmp_name'];
                $file_name = $_FILES['profile_image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($file_ext, $allowed_exts)) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    // تسمية فريدة للصورة لتفادي تكرار الأسماء
                    $new_file_name = 'avatar_' . $user_type . '_' . $user_id . '_' . time() . '.' . $file_ext;
                    $dest_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        // حذف الصورة القديمة إذا كانت موجودة لتوفير مساحة الاستضافة
                        if (!empty($profile_image_path) && file_exists($profile_image_path)) {
                            @unlink($profile_image_path);
                        }
                        $profile_image_path = $dest_path;
                    }
                } else {
                    $error_msg = "❌ صيغة الصورة غير مدعومة! يرجى رفع ملفات (JPG, PNG, WEBP) فقط.";
                }
            }

            if (empty($error_msg)) {
                // تنفيذ التحديث حسب جدول قاعدة البيانات ونوع المستخدم
                if ($user_type === 'driver') {
                    $update_stmt = $pdo->prepare("
                        UPDATE drivers 
                        SET fullname = ?, phone = ?, province = ?, wheel_number = ?, wheel_type = ?, wheel_color = ?, wheel_model = ?, profile_image = ? 
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$fullname, $phone, $province, $wheel_number, $wheel_type, $wheel_color, $wheel_model, $profile_image_path, $user_id]);
                } elseif ($user_type === 'kia') {
                    $update_stmt = $pdo->prepare("
                        UPDATE kias 
                        SET fullname = ?, phone = ?, province = ?, car_number = ?, car_model = ?, profile_image = ? 
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$fullname, $phone, $province, $wheel_number, $wheel_type, $profile_image_path, $user_id]);
                } else {
                    // العميل العادي
                    $update_stmt = $pdo->prepare("
                        UPDATE customers 
                        SET fullname = ?, phone = ?, province = ?, profile_image = ? 
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$fullname, $phone, $province, $profile_image_path, $user_id]);
                }

                // تحديث بيانات الجلسة فوراً لمزامنة واجهة العرض
                $_SESSION['fullname'] = $fullname;
                $success_msg = "🎉 تم تحديث بيانات ملفك الشخصي بنجاح!";
                
                // إعادة جلب البيانات المحدثة
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error_msg = "❌ فشل تحديث البيانات في قاعدة البيانات: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $curr_theme['title'] ?> - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الفاخر -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <!-- شريط الملاحة والهيدر الذكي -->
    <header class="h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl relative">
        <div class="flex items-center gap-3">
            <span class="text-3xl animate-bounce"><?= $curr_theme['icon'] ?></span>
            <div>
                <h1 class="text-md sm:text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين العراق</span></h1>
                <p class="text-[10px] text-slate-400 mt-1">تعديل ملف الحساب الشخصي والأمني</p>
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

    <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-10">
        
        <!-- صندوق تحرير الملف الشخصي الفخم -->
        <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6 sm:p-8 relative overflow-hidden shadow-2xl">
            <!-- تأثير توهج خلفي ديناميكي متكيف مع لون نوع الحساب -->
            <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-<?= $curr_theme['accent'] ?>-500/5 rounded-full blur-3xl"></div>

            <div class="text-center mb-8 relative z-10">
                <span class="inline-block text-xs font-bold px-3 py-1.5 rounded-full border mb-3 <?= $curr_theme['badge'] ?>">
                    <?= $curr_theme['title'] ?>
                </span>
                <p class="text-xs text-slate-400">يرجى التأكد من دقة المعلومات المدخلة لضمان جودة وتوافق الخدمة</p>
            </div>

            <!-- عرض إشعارات الحالة والنجاح -->
            <?php if (!empty($success_msg)): ?>
                <div class="p-4 rounded-2xl text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20 text-center mb-6 animate-bounce">
                    <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="p-4 rounded-2xl text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20 text-center mb-6 animate-pulse">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6 relative z-10">
                
                <!-- رفع ومعاينة الصورة الشخصية فورياً -->
                <div class="flex flex-col items-center justify-center bg-slate-950/50 p-6 rounded-2xl border border-slate-850">
                    <div class="relative w-24 h-24 mb-3">
                        <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                            <img id="avatarPreview" src="<?= htmlspecialchars($user['profile_image']) ?>" class="w-full h-full rounded-full object-cover border-2 border-slate-800 shadow-xl" alt="Profile Avatar">
                        <?php else: ?>
                            <div id="avatarPlaceholder" class="w-full h-full rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-4xl">
                                <?= $curr_theme['icon'] ?>
                            </div>
                            <img id="avatarPreview" class="hidden w-full h-full rounded-full object-cover border-2 border-slate-850 shadow-xl" alt="New Avatar">
                        <?php endif; ?>
                        
                        <label class="absolute bottom-0 right-0 bg-slate-900 text-white p-2 rounded-full shadow-lg border border-slate-800 cursor-pointer hover:bg-slate-800 transition transform hover:scale-110">
                            📷
                            <input type="file" name="profile_image" accept="image/*" class="hidden" onchange="previewFile(event)">
                        </label>
                    </div>
                    <span class="text-[10px] text-slate-500">اضغط على الأيقونة لرفع صورة ملفك الشخصي</span>
                </div>

                <!-- الحقول الأساسية المشتركة -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2">الاسم الثلاثي بالكامل</label>
                        <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-<?= $curr_theme['accent'] ?>-500 <?= $curr_theme['focus_ring'] ?> focus:ring-4" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2">رقم الهاتف النشط</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-<?= $curr_theme['accent'] ?>-500 <?= $curr_theme['focus_ring'] ?> focus:ring-4 text-left font-mono" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2">البريد الإلكتروني (للقراءة فقط)</label>
                        <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-850 text-sm text-slate-500 font-mono cursor-not-allowed outline-none text-left" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2">محافظة الخدمة الرئيسية</label>
                        <select name="province" required
                                class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-<?= $curr_theme['accent'] ?>-500 <?= $curr_theme['focus_ring'] ?> focus:ring-4 bg-slate-950">
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= htmlspecialchars($prov) ?>" <?= (($user['province'] ?? '') === $prov) ? 'selected' : '' ?>>
                                    📍 <?= htmlspecialchars($prov) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- حقول خاصة بسائق الكرين (الونش) -->
                <?php if ($user_type === 'driver'): ?>
                    <div class="border-t border-slate-800/80 pt-6 space-y-4">
                        <h3 class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-2">🛠️ معلومات الكرين (ونش السحب)</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2">رقم لوحة المركبة (الرقم المروري)</label>
                                <input type="text" name="wheel_number" value="<?= htmlspecialchars($user['wheel_number'] ?? '') ?>" required
                                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2">نوع الكرين وسيلة السحب</label>
                                <input type="text" name="wheel_type" value="<?= htmlspecialchars($user['wheel_type'] ?? '') ?>" required placeholder="مثال: سطحة هيدروليكية، ونش تلسكوبي"
                                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2">لون المركبة</label>
                                <input type="text" name="wheel_color" value="<?= htmlspecialchars($user['wheel_color'] ?? '') ?>" required
                                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2">سنة الصنع / موديل المركبة</label>
                                <input type="text" name="wheel_model" value="<?= htmlspecialchars($user['wheel_model'] ?? '') ?>" required
                                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20" />
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- حقول خاصة بسائق الكيا حمل -->
                <?php if ($user_type === 'kia'): ?>
                    <div class="border-t border-slate-800/80 pt-6 space-y-4">
                        <h3 class="text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">🚚 معلومات مركبة الكيا حمل</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2">رقم لوحة المركبة (الرقم المروري)</label>
                                <input type="text" name="car_number" value="<?= htmlspecialchars($user['car_number'] ?? '') ?>" required
                                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-500/20" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2">موديل ونوع السيارة</label>
                                <input type="text" name="car_model" value="<?= htmlspecialchars($user['car_model'] ?? '') ?>" required placeholder="مثال: كيا بنجو 2022"
                                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-500/20" />
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- زر الإرسال والحفظ المتوافق مع اللون الخاص بكل حساب -->
                <div class="pt-4">
                    <button type="submit" class="w-full py-4 px-6 rounded-2xl <?= $curr_theme['btn'] ?> font-black text-xs transition duration-150 transform active:scale-95 shadow-lg shadow-black/30">
                        💾 حفظ تعديلات الملف الشخصي
                    </button>
                </div>
            </form>

            <div class="text-center mt-6 pt-5 border-t border-slate-800/80">
                <a href="dashboard.php" class="text-xs font-bold text-slate-400 hover:text-white transition">
                    ⬅️ تراجع، العودة للوحة الرئيسية
                </a>
            </div>
        </div>

    </main>

    <footer class="py-6 border-t border-slate-900 bg-slate-950 text-center">
        <p class="text-[10px] text-slate-500">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع البيانات مشفرة بالكامل.</p>
    </footer>

    <!-- جافاسكربت لمعاينة الصورة المرفوعة حياً ومباشرة على المتصفح قبل إرسالها للخادم -->
    <script>
        function previewFile(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('avatarPreview');
                var placeholder = document.getElementById('avatarPlaceholder');
                
                output.src = reader.result;
                output.classList.remove('hidden');
                
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>
