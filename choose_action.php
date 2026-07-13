<?php
session_start();
$action = isset($_GET['action']) ? $_GET['action'] : 'register'; // الافتراضي هو التسجيل
$title = ($action === 'login') ? 'تسجيل الدخول - اختر نوع حسابك' : 'إنشاء حساب جديد - اختر نوع حسابك';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?> | تطبيق كرين العراقي</title>
    <!-- استدعاء Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { 
            font-family: 'Cairo', sans-serif; 
        }
        /* تأثير التوهج المخصص للأزرار والكروت */
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
        /* تأثير خلفية الشبكة الفخم */
        .bg-grid {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen bg-grid flex flex-col justify-between">

    <!-- Header / شريط الملاحة العلوي المتناسق -->
    <header class="w-full border-b border-slate-900 bg-slate-950/80 backdrop-blur-md">
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
        <!-- طبقات توهج خلفية لإضفاء العمق اللوني الفاخر -->
        <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl -z-10"></div>
        <div class="absolute top-1/2 right-1/4 -translate-y-1/2 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl -z-10"></div>

        <div class="max-w-5xl w-full space-y-10">
            <!-- نصوص ترحيبية تفاعلية -->
            <div class="text-center space-y-4">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    🛡️ نظام آمن ومشفر بالكامل لحماية خصوصيتك
                </span>
                
                <?php if ($action === 'login'): ?>
                    <h2 class="text-3xl sm:text-5xl font-black text-white leading-tight">
                        مرحباً بك مجدداً! <span class="text-amber-500">اختر نوع حسابك</span>
                    </h2>
                    <p class="text-slate-400 text-xs sm:text-sm max-w-lg mx-auto leading-relaxed">
                        يرجى اختيار البوابة المناسبة لنوع حسابك للدخول المباشر إلى لوحة التحكم الخاصة بك.
                    </p>
                <?php else: ?>
                    <h2 class="text-3xl sm:text-5xl font-black text-white leading-tight">
                        ابدأ معنا اليوم! <span class="text-amber-500">اختر نوع الحساب</span>
                    </h2>
                    <p class="text-slate-400 text-xs sm:text-sm max-w-lg mx-auto leading-relaxed">
                        انضم إلى مئات السواق والعملاء المسجلين في كافة محافظات العراق بخطوات بسيطة وفورية.
                    </p>
                <?php endif; ?>
            </div>

            <!-- الكروت التفاعلية الثلاثة (عميل - سائق كرين - سائق كيا) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- كارت 1: العميل / المستخدم العادي -->
                <a href="<?= ($action === 'login') ? 'login.php?user_type=customer' : 'register.php?user_type=customer' ?>" 
                   class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800/80 hover:border-green-500/40 transition duration-300 transform hover:-translate-y-1 glow-card-customer flex flex-col justify-between h-72 group text-right">
                    <div class="space-y-4">
                        <span class="text-4xl p-3 bg-green-500/10 text-green-500 rounded-2xl inline-block group-hover:scale-110 transition duration-300">👤</span>
                        <h4 class="text-lg font-black text-white group-hover:text-green-400 transition">المستخدم العادي (العميل)</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            لطلب أقرب ونش إنقاذ أو كيا حمل بشكل فوري وتتبع السائق على الخريطة حياً.
                        </p>
                    </div>
                    <span class="text-xs text-green-500 font-bold flex items-center gap-1 group-hover:underline mt-4">
                        <?= ($action === 'login') ? 'تسجيل دخول كعميل' : 'إنشاء حساب عميل' ?> ⬅️
                    </span>
                </a>

                <!-- كارت 2: سائق الكرين / الونش -->
                <a href="<?= ($action === 'login') ? 'login.php?user_type=driver' : 'register.php?user_type=driver' ?>" 
                   class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800/80 hover:border-amber-500/40 transition duration-300 transform hover:-translate-y-1 glow-card-driver flex flex-col justify-between h-72 group text-right">
                    <div class="space-y-4">
                        <span class="text-4xl p-3 bg-amber-500/10 text-amber-500 rounded-2xl inline-block group-hover:scale-110 transition duration-300">🚜</span>
                        <h4 class="text-lg font-black text-white group-hover:text-amber-500 transition">سائق كرين (ونش)</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            مخصص لمالكي كراين السحب بجميع أنواعها لاستقبال طلبات الإنقاذ وتحقيق دخل إضافي ممتاز.
                        </p>
                    </div>
                    <span class="text-xs text-amber-500 font-bold flex items-center gap-1 group-hover:underline mt-4">
                        <?= ($action === 'login') ? 'تسجيل دخول كسائق كرين' : 'انضمام كصاحب كرين' ?> ⬅️
                    </span>
                </a>

                <!-- كارت 3: سائق الكيا حمل -->
                <a href="<?= ($action === 'login') ? 'login.php?user_type=kia' : 'register.php?user_type=kia' ?>" 
                   class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800/80 hover:border-sky-500/40 transition duration-300 transform hover:-translate-y-1 glow-card-kia flex flex-col justify-between h-72 group text-right">
                    <div class="space-y-4">
                        <span class="text-4xl p-3 bg-sky-500/10 text-sky-400 rounded-2xl inline-block group-hover:scale-110 transition duration-300">🚚</span>
                        <h4 class="text-lg font-black text-white group-hover:text-sky-400 transition">سائق كيا حمل</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            مخصص لأصحاب سيارات الحمل لنقل الأغراض والمعدات وتلبية طلبات بضائع العملاء بمرونة.
                        </p>
                    </div>
                    <span class="text-xs text-sky-400 font-bold flex items-center gap-1 group-hover:underline mt-4">
                        <?= ($action === 'login') ? 'تسجيل دخول كسائق كيا' : 'انضمام كصاحب كيا' ?> ⬅️
                    </span>
                </a>

            </div>

            <!-- ذيل لوحة التحكم - التبديل التفاعلي بين الدخول والتسجيل -->
            <div class="text-center pt-6">
                <?php if ($action === 'login'): ?>
                    <p class="text-slate-400 text-xs">
                        ليس لديك حساب مسبق في النظام؟ 
                        <a href="choose_action.php?action=register" class="text-amber-500 font-bold hover:underline">اضغط هنا لإنشاء حساب جديد مجاناً</a>
                    </p>
                <?php else: ?>
                    <p class="text-slate-400 text-xs">
                        لديك حساب مسجل بالفعل؟ 
                        <a href="choose_action.php?action=login" class="text-amber-500 font-bold hover:underline">اضغط هنا لتسجيل الدخول الفوري</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer / التذييل السفلي المتطابق مع الهوم -->
    <footer class="py-8 border-t border-slate-900 bg-slate-950/80 text-center text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-2">
            <p class="text-slate-500 leading-relaxed max-w-md mx-auto">
                جميع البيانات مشفرة وتخضع لبروتوكول التحقق الصارم لحماية مستخدمي تطبيق كرين في العراق 🇮🇶
            </p>
            <p class="text-slate-600">
                حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>.
            </p>
        </div>
    </footer>

</body>
</html>
