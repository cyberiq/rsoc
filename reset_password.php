<?php
session_start();
require 'config.php';

$message = '';
$message_type = 'error';
$is_valid_token = false;

// جلب التوكن ونوع الحساب من الرابط (GET) أو من مدخلات النموذج (POST)
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$user_type = trim($_GET['user_type'] ?? $_POST['user_type'] ?? 'customer');

if (!in_array($user_type, ['driver', 'customer', 'kia'])) {
    $user_type = 'customer';
}

// مصفوفة الأنماط والألوان التفاعلية المتناسقة مع طابع الحسابات
$theme_classes = [
    'driver' => [
        'title' => 'سائق كرين (ونش)',
        'accent' => 'amber',
        'badge' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'focus_ring' => 'focus:ring-amber-500/20 focus:border-amber-500',
        'btn' => 'bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-amber-500/10',
    ],
    'kia' => [
        'title' => 'سائق كيا حمل',
        'accent' => 'sky',
        'badge' => 'bg-sky-500/10 text-sky-500 border-sky-500/20',
        'focus_ring' => 'focus:ring-sky-500/20 focus:border-sky-500',
        'btn' => 'bg-sky-500 hover:bg-sky-600 text-slate-950 shadow-sky-500/10',
    ],
    'customer' => [
        'title' => 'مستخدم عادي (طالب خدمة)',
        'accent' => 'green',
        'badge' => 'bg-green-500/10 text-green-500 border-green-500/20',
        'focus_ring' => 'focus:ring-green-500/20 focus:border-green-500',
        'btn' => 'bg-green-500 hover:bg-green-600 text-slate-950 shadow-green-500/10',
    ]
];

$current_theme = $theme_classes[$user_type];

if (empty($token)) {
    $message = "❌ عذراً، لا يمكن الوصول لهذه الصفحة بدون رمز تحقق (Token) صالح.";
    $message_type = 'error';
} else {
    try {
        // البحث عن التوكن في قاعدة البيانات لضمان وجوده وصلاحه
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();

        if ($reset_request) {
            // التحقق من صلاحية التوكن الزمنية (أقل من ساعة واحدة)
            $expires_time = strtotime($reset_request['expires_at']);
            if (time() > $expires_time) {
                $message = "❌ عذراً، انتهت الصلاحية الأمنية لهذا الرابط (صلاحية الرابط 1 ساعة فقط). يرجى طلب استعادة كلمة المرور مجدداً.";
                $message_type = 'error';
            } else {
                $is_valid_token = true;
            }
        } else {
            $message = "❌ رمز التحقق (Token) غير صحيح أو منتهي الصلاحية.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "❌ حدث خطأ برمي أثناء التحقق من التوكن في الخادم.";
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $message = "❌ يرجى ملء كافة حقول كلمات المرور المطلوبة.";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "❌ كلمتا المرور غير متطابقتين. يرجى التأكد وإعادة المحاولة.";
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "❌ يجب أن تتكون كلمة المرور الجديدة من 6 خانات أو أكثر لتأمين حسابك.";
        $message_type = 'error';
    } else {
        try {
            // تحديد جدول قاعدة البيانات المطلوب بناءً على نوع المستخدم
            $table = ($user_type === 'driver') ? 'drivers' : (($user_type === 'kia') ? 'kias' : 'customers');
            $email = $reset_request['email'];

            // تشفير كلمة المرور الجديدة بقوة
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // تحديث كلمة المرور في الجدول المناسب
            $update_stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE email = ?");
            $update_stmt->execute([$hashed_password, $email]);

            // تدمير التوكن المستخدم فوراً لضمان عدم إعادة تشغيله مجدداً (One-time-use rule)
            $delete_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete_stmt->execute([$email]);

            // نجاح العملية وإرسال رسالة توجيهية عبر الجلسة
            $_SESSION['verify_success_msg'] = "🎉 تم إعادة تعيين كلمة مرورك بنجاح! يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.";
            header("Location: choose_login.php");
            exit;

        } catch (PDOException $e) {
            $message = "❌ فشل تحديث كلمة المرور في قاعدة البيانات. يرجى مراجعة الإدارة.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين كلمة مرور جديدة - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الأنيق والمظلم -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;850;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center py-12 px-4 selection:bg-amber-500 selection:text-slate-950">

    <div class="max-w-md w-full bg-slate-900 rounded-3xl border border-slate-800 p-8 shadow-2xl relative overflow-hidden">
        
        <!-- توهج تزييني متكيف مع لون نوع الحساب بالخلفية -->
        <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-<?= $current_theme['accent'] ?>-500/5 rounded-full blur-3xl"></div>

        <!-- ترويسة الصفحة والمعلومات الترحيبية -->
        <div class="text-center mb-8 relative z-10">
            <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full border mb-3 <?= $current_theme['badge'] ?>">
                بوابة أمان: <?= $current_theme['title'] ?>
            </span>
            <h2 class="text-2xl font-black text-white">إعادة تعيين كلمة المرور</h2>
            <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">قم بكتابة وتأكيد كلمة المرور الجديدة لتحديث بيانات حسابك بأمان.</p>
        </div>

        <!-- عرض التنبيهات والأخطاء إن وجدت -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl text-xs font-bold border leading-relaxed text-center relative z-10
                <?= $message_type === 'success' ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20' ?>
            ">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($is_valid_token): ?>
            <!-- نموذج إعادة تعيين كلمة المرور الفخم -->
            <form method="POST" class="space-y-5 relative z-10">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="user_type" value="<?= htmlspecialchars($user_type) ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">كلمة المرور الجديدة</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-transparent focus:ring-4 <?= $current_theme['focus_ring'] ?> transition" 
                           placeholder="••••••••">
                    <p class="text-[9px] text-slate-500 mt-1">يجب ألا تقل عن 6 خانات ويُفضل استخدام أرقام وحروف.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-transparent focus:ring-4 <?= $current_theme['focus_ring'] ?> transition" 
                           placeholder="••••••••">
                </div>

                <button type="submit" 
                        class="w-full mt-2 py-4 px-4 <?= $current_theme['btn'] ?> font-black rounded-xl shadow-lg transition transform active:scale-[0.98] flex items-center justify-center gap-2 text-xs">
                    🔐 حفظ كلمة المرور الجديدة وتحديث الحساب
                </button>
            </form>
        <?php else: ?>
            <!-- في حال عدم توفر التوكن أو انتهائه، نعرض زراً للرجوع وتسهيل المتابعة -->
            <div class="space-y-4 pt-2 text-center relative z-10">
                <a href="choose_login.php" 
                   class="w-full py-3 px-4 bg-slate-950 hover:bg-slate-850 text-slate-300 border border-slate-800 text-xs font-bold rounded-xl transition text-center block">
                    ⬅️ العودة لبوابة اختيار الحساب
                </a>
            </div>
        <?php endif; ?>

        <!-- الفوتر وحماية الخصوصية -->
        <div class="mt-8 pt-5 border-t border-slate-800/60 text-center relative z-10">
            <p class="text-[10px] text-slate-500">منصة كرين تضمن أعلى مستويات حماية التشفير والأمان لجميع الحسابات.</p>
        </div>

    </div>

</body>
</html>
