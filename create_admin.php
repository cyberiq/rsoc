<?php
session_start();
require 'config.php';

$message = '';
$message_type = 'info';
$admin_email = 'admin@kreen.com';

try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $existing_admin = $stmt->fetch();
    
    if ($existing_admin) {
        $message = "ℹ️ يوجد حساب مدير مسجل بالفعل في النظام ببريد: <b>" . htmlspecialchars($existing_admin['email']) . "</b>";
        $message_type = 'warning';
    } else {
        $message = "📢 لم يتم العثور على حساب مدير عام في النظام حالياً. يرجى ملء النموذج أدناه لإنشاء الحساب الأول للتحكم العام.";
        $message_type = 'info';
    }
} catch (PDOException $e) {
    $message = "❌ خطأ في فحص قاعدة البيانات: " . $e->getMessage();
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $fullname = trim($_POST['fullname'] ?? 'مدير النظام العام');
    $phone = trim($_POST['phone'] ?? '07700000000');
    $province = $_POST['province'] ?? 'بغداد';
    $email = trim($_POST['email'] ?? 'admin@kreen.com');
    $password_plain = $_POST['password'] ?? 'admin123';
    
    if (empty($email) || empty($password_plain)) {
        $message = "❌ يرجى ملء جميع الحقول المطلوبة لإنشاء الحساب.";
        $message_type = 'error';
    } else {
        try {
            // التحقق من تكرار البريد الإلكتروني في جدول المستخدمين
            $check_stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $message = "❌ البريد الإلكتروني مدخل مسبقاً لحساب آخر. يرجى استخدام بريد مختلف.";
                $message_type = 'error';
            } else {
                // تشفير كلمة المرور بأسلوب قوي وآمن
                $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
                $is_verified = 1;
                $role = 'admin';
                
                // إدراج الحساب الجديد مفعل تلقائياً كـ Admin
                $insert = $pdo->prepare("INSERT INTO customers (fullname, phone, province, email, password, verification_code, is_verified, role) 
                                         VALUES (?, ?, ?, ?, ?, NULL, ?, ?)");
                $insert->execute([$fullname, $phone, $province, $email, $password_hashed, $is_verified, $role]);
                
                $message = "🎉 تم إنشاء حساب مدير النظام العام بنجاح! يمكنك الآن استخدامه لتسجيل الدخول بأمان.";
                $message_type = 'success';
                
                // تحديث حالة السجل لإخفاء النموذج
                $existing_admin = true;
            }
        } catch (PDOException $e) {
            $message = "❌ فشل إدراج حساب المدير: " . $e->getMessage();
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
    <title>لوحة إنشاء مدير النظام - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الأنيق -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-slate-950 rounded-3xl shadow-2xl border border-slate-800 p-8 transition-all duration-300">
        
        <!-- الشعار والهيدر -->
        <div class="text-center mb-8">
            <span class="inline-block text-5xl mb-3 animate-bounce">🛠️</span>
            <h1 class="text-2xl font-black text-white">إعداد وإدارة نظام <span class="text-amber-500">كرين</span></h1>
            <p class="text-xs text-slate-400 mt-2 leading-relaxed">بوابة تهيئة حساب الإدارة العامة والمراقبة الشاملة للتطبيق.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="p-4 rounded-xl text-xs font-semibold leading-relaxed border mb-6 text-center 
                <?= $message_type === 'success' ? 'bg-green-500/10 text-green-400 border-green-500/20' : '' ?>
                <?= $message_type === 'error' ? 'bg-red-500/10 text-red-400 border-red-500/20' : '' ?>
                <?= $message_type === 'warning' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : '' ?>
                <?= $message_type === 'info' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : '' ?>
            ">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (!$existing_admin): ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wide">الاسم الكامل للمدير</label>
                    <input type="text" name="fullname" value="مدير النظام العام" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 text-sm text-white outline-none transition" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">رقم الهاتف</label>
                        <input type="tel" name="phone" value="07700000000" required
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 text-sm text-white outline-none transition text-left" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">المحافظة</label>
                        <input type="text" name="province" value="بغداد" required
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 text-sm text-white outline-none transition" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">البريد الإلكتروني للإدارة</label>
                    <input type="email" name="email" value="admin@kreen.com" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 text-sm text-white outline-none transition text-left" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase">كلمة المرور الحساسة</label>
                    <input type="text" name="password" value="admin123" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-amber-500 text-sm text-white font-mono outline-none transition" />
                    <span class="text-[10px] text-slate-500 mt-1 block">كلمة المرور الافتراضية المقترحة هي: <b class="text-slate-400">admin123</b></span>
                </div>

                <button type="submit" name="create_admin"
                        class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 active:scale-[0.99] text-slate-950 font-black rounded-xl transition-all duration-150 shadow-lg shadow-amber-500/10 flex items-center justify-center gap-2">
                    🚀 إنشاء حساب الإدارة الآن وتفعيله
                </button>
            </form>
        <?php else: ?>
            <div class="space-y-4 pt-2">
                <div class="bg-slate-900/60 p-4 rounded-2xl border border-slate-800/80">
                    <p class="text-xs text-slate-400 mb-1">البريد الإلكتروني المعتمد للدخول:</p>
                    <p class="text-sm font-bold text-amber-500 font-mono">admin@kreen.com</p>
                </div>
                
                <a href="choose_login.php" 
                   class="w-full py-3 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-bold rounded-xl transition text-center block border border-slate-700/50">
                    🔐 الذهاب لبوابة تسجيل الدخول
                </a>
            </div>
        <?php endif; ?>

        <!-- الفوتر السفلي -->
        <div class="text-center mt-8 pt-5 border-t border-slate-800/60">
            <a href="home.php" class="text-xs font-semibold text-slate-500 hover:text-slate-400 transition">
                🏠 العودة لصفحة التطبيق الرئيسية
            </a>
        </div>

    </div>

</body>
</html>
