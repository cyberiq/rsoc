<?php
session_start();
require 'config.php';

// التحقق من صلاحيات الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: choose_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// تحديد الجدول المناسب بناءً على نوع المستخدم لتجنب الأخطاء
$table = 'customers';
if ($user_type === 'driver') {
    $table = 'drivers';
} elseif ($user_type === 'kia') {
    $table = 'kias';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        $error = "❌ يرجى إدخال كلمة المرور لتأكيد الهوية.";
    } else {
        try {
            // جلب كلمة المرور المشفرة للتحقق منها
            $stmt = $pdo->prepare("SELECT password FROM $table WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                
                // إذا كان سائقاً، نقوم بحذف موقعه الجغرافي أولاً لضمان سلامة القيود الجغرافية
                if ($user_type === 'driver') {
                    $del_loc = $pdo->prepare("DELETE FROM driver_locations WHERE driver_id = ?");
                    $del_loc->execute([$user_id]);
                }

                // حذف المستخدم النهائي من جدوله الرئيسي
                $delete_stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
                $delete_stmt->execute([$user_id]);

                // تدمير الجلسة وتسجيل الخروج بالكامل
                $_SESSION = [];
                session_destroy();

                // التوجيه للرئيسية مع معلمة ترحيبية أو إعلامية
                header('Location: home.php?account_deleted=1');
                exit;
            } else {
                $error = "❌ كلمة المرور غير صحيحة. فشل تأكيد الهوية.";
            }
        } catch (PDOException $e) {
            $error = "❌ حدث خطأ أثناء محاولة حذف الحساب. يرجى المحاولة لاحقاً.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد حذف الحساب - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الفاخر -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-slate-900 rounded-3xl border border-slate-800 p-8 shadow-2xl relative overflow-hidden group">
        <!-- تأثير توهج خلفي أحمر هادئ للتحذير -->
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-red-500/5 rounded-full blur-3xl transition duration-500"></div>

        <div class="text-center mb-6">
            <span class="inline-block text-5xl mb-3 animate-pulse">⚠️</span>
            <h1 class="text-xl font-black text-white">تحذير أمني حساس</h1>
            <p class="text-xs text-red-400 mt-2 font-bold uppercase tracking-wider">طلب حذف الحساب نهائياً</p>
        </div>

        <div class="bg-red-950/20 border border-red-900/30 rounded-2xl p-4 mb-6 space-y-2 text-xs text-slate-350 leading-relaxed">
            <p class="font-bold text-red-400">🚨 يرجى الانتباه للتالي قبل المتابعة:</p>
            <ul class="list-disc list-inside space-y-1 pr-1 text-slate-400">
                <li>سيتم مسح كافة بياناتك الشخصية من السجلات بشكل نهائي.</li>
                <li>ستفقد إمكانية الوصول إلى طلباتك السابقة وتفاصيل حسابك.</li>
                <li>إذا كنت سائقاً، فسيتم إزالة موقعك الجغرافي وحذف معلومات مركبتك بالكامل من خرائط العملاء.</li>
                <li>هذا الإجراء <span class="font-bold text-red-400 underline">نهائي ولا يمكن التراجع عنه</span> بأي شكل من الأشكال.</li>
            </ul>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-3.5 rounded-xl text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20 text-center mb-5">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">أدخل كلمة المرور الحالية لتأكيد رغبتك بالحذف</label>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white outline-none focus:border-red-500 transition font-mono" />
            </div>

            <button type="submit" name="confirm_delete"
                    class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl transition transform active:scale-[0.99] shadow-lg shadow-red-600/10 flex items-center justify-center gap-2 text-xs">
                ❌ نعم، أنا متأكد من حذف حسابي نهائياً
            </button>
        </form>

        <div class="text-center mt-6 pt-5 border-t border-slate-800/80">
            <!-- العودة الآمنة للوحة التوجيه المناسبة -->
            <a href="account_settings.php" class="text-xs font-bold text-slate-400 hover:text-white transition">
                ⬅️ تراجع، العودة للإعدادات
            </a>
        </div>
    </div>

</body>
</html>
