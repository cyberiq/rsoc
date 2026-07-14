<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
$flashMessage = $_SESSION['verify_success_msg'] ?? '';
unset($_SESSION['verify_success_msg']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة تسجيل الدخول - اختر نوع حسابك | تطبيق كرين العراقي</title>
    <!-- استدعاء Tailwind CSS الأنيق -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { 
            font-family: 'Cairo', sans-serif; 
        }
        /* تأثير التوهج المخصص للأزرار والكروت عند التحليق */
        .glow-button {
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);
            transition: all 0.3s ease;
        }
        .glow-button:hover {
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.6);
        }
        .glow-card-customer:hover {
            box-shadow: 0 0 25px rgba(34, 197, 94, 0.15);
        }
        .glow-card-driver:hover {
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.15);
        }
        .glow-card-kia:hover {
            box-shadow: 0 0 25px rgba(14, 165, 233, 0.15);
        }
        /* تأثير خلفية الشبكة الفخم الموحد للنظام */
        .bg-grid {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen bg-grid flex flex-col justify-between">

    <!-- Header / شريط الملاحة العلوي الفخم المتناسق -->
    <header class="w-full border-b border-slate-900 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <a href="home.php" class="flex items-center gap-3 hover:opacity-90 transition">
                    <span class="text-3xl">🚜</span>
                    <div>
                        <h1 class="text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين</span></h1>
                        <p class="text-[9px] text-slate-400 mt-1">الإنقاذ السريع في العراق</p>
                    </div>
                </a>
                <a href="home.php" class="text-xs font-bold text-slate-400 hover:text-amber-500 transition border border-slate-800 bg-slate-900/40 px-4 py-2 rounded-xl">
                    ⬅️ الرجوع للرئيسية
                </a>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي للملف -->
    <main class="flex-grow flex items-center justify-center px-4 py-12 relative overflow-hidden">
        <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl -z-10"></div>
        <div class="absolute top-1/2 right-1/4 -translate-y-1/2 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl -z-10"></div>

        <div class="max-w-3xl w-full space-y-8">
            <div class="text-center space-y-4">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    🛡️ بوابة دخول موحدة وآمنة
                </span>

                <h2 class="text-3xl sm:text-5xl font-black text-white leading-tight">
                    مرحباً بك مجدداً! <span class="text-amber-500">سنعرف نوع حسابك تلقائياً</span>
                </h2>
                <p class="text-slate-400 text-xs sm:text-sm max-w-xl mx-auto leading-relaxed">
                    لا حاجة لاختيار نوع الحساب في كل مرة. استخدم نفس صفحة الدخول، وسيتعرف النظام على حسابك من البريد الإلكتروني ثم يوجهك إلى اللوحة المناسبة.
                </p>
            </div>

            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-8 space-y-6 text-right">
                <?php if (!empty($flashMessage)): ?>
                    <div class="mb-4 p-4 rounded-2xl bg-green-500/10 text-green-200 border border-green-500/20 text-sm">
                        <?= htmlspecialchars($flashMessage) ?>
                    </div>
                <?php endif; ?>
                <div class="space-y-3">
                    <h3 class="text-xl font-black text-white">الذهاب مباشرة إلى لوحة الدخول</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        أدخل بريدك الإلكتروني وكلمة المرور فقط. إذا كان حسابك عميلًا أو سائقًا أو سائق كيا، سيتم توجيهك تلقائيًا إلى لوحتك الصحيحة.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="login.php" class="flex-1 text-center bg-gradient-to-r from-amber-500 to-orange-600 text-white font-black px-5 py-3 rounded-2xl hover:opacity-90 transition">
                        🔐 تسجيل الدخول
                    </a>
                    <a href="register.php" class="flex-1 text-center border border-slate-700 text-slate-300 font-black px-5 py-3 rounded-2xl hover:border-amber-500 hover:text-amber-400 transition">
                        📝 إنشاء حساب جديد
                    </a>
                </div>
            </div>

            <div class="text-center pt-2">
                <p class="text-slate-400 text-xs">
                    إذا كنت تريد العودة إلى الصفحة الرئيسية،
                    <a href="home.php" class="text-amber-500 font-bold hover:underline">اضغط هنا</a>
                </p>
            </div>
        </div>
    </main>

    <!-- Footer / التذييل السفلي المتطابق مع بقية الصفحات -->
    <footer class="py-8 border-t border-slate-900 bg-slate-950/80 text-center text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-2">
            <p class="text-slate-500 leading-relaxed max-w-md mx-auto">
                جميع الاتصالات والبيانات مشفرة بالكامل وتخضع لبروتوكولات الأمان الصارمة لتطبيق كرين في العراق 🇮🇶
            </p>
            <p class="text-slate-600">
                حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>.
            </p>
        </div>
    </footer>

</body>
</html>
