<?php
require 'autoloader.php';
require 'config-improved.php';

SessionManager::start();

$message = '';
$message_type = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF
    if (!CSRFProtection::verifyToken($_POST['csrf_token'] ?? '')) {
        $message = '❌ خطأ أمان: طلبك غير صحيح';
        $message_type = 'error';
    } else {
        // تجميع البيانات
        $form_data = [
            'fullname' => trim($_POST['fullname'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'email' => trim(strtolower($_POST['email'] ?? '')),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'user_type' => $_POST['user_type'] ?? 'customer'
        ];
        
        // التحقق من صحة البيانات
        $validation = Validator::validateRegistration($form_data);
        
        if (!$validation['valid']) {
            $message = '❌ ' . implode('<br>❌ ', $validation['errors']);
            $message_type = 'error';
        } elseif ($form_data['password'] !== $form_data['confirm_password']) {
            $message = '❌ كلمات المرور غير متطابقة';
            $message_type = 'error';
        } else {
            // التحقق من عدم وجود بريد مكرر
            try {
                $table_map = ['customer' => 'customers', 'driver' => 'drivers', 'kia' => 'kias'];
                $table = $table_map[$form_data['user_type']];
                
                $check = $pdo->prepare("SELECT id FROM $table WHERE email = ? LIMIT 1");
                $check->execute([$form_data['email']]);
                
                if ($check->fetch()) {
                    $message = '❌ هذا البريد الإلكتروني مستخدم بالفعل';
                    $message_type = 'error';
                } else {
                    // إنشاء حساب جديد
                    $verification_code = bin2hex(random_bytes(32));
                    $hashed_password = password_hash($form_data['password'], PASSWORD_BCRYPT);
                    
                    $insert = $pdo->prepare(
                        "INSERT INTO $table (fullname, phone, province, email, password, verification_code, is_verified, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, 0, datetime('now'))"
                    );
                    
                    if ($insert->execute([
                        $form_data['fullname'],
                        $form_data['phone'],
                        $form_data['province'],
                        $form_data['email'],
                        $hashed_password,
                        $verification_code
                    ])) {
                        $message = '✅ تم إنشاء حسابك بنجاح! تحقق من بريدك الإلكتروني.';
                        $message_type = 'success';
                        $form_data = [];
                        
                        // تسجيل الحدث
                        $logger = new SecurityLogger();
                        $logger->log('ACCOUNT_CREATED', ['email' => $form_data['email'], 'type' => $form_data['user_type']]);
                    }
                }
            } catch (PDOException $e) {
                $message = '❌ حدث خطأ في الخادم';
                $message_type = 'error';
            }
        }
    }
}

$token = CSRFProtection::getToken();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap'); body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <span class="text-5xl">🚕</span>
            <h1 class="text-3xl font-black text-white mt-4">حساب جديد</h1>
            <p class="text-slate-400 mt-2">انضم إلى تطبيق كرين</p>
        </div>

        <div class="bg-slate-800 rounded-3xl border border-slate-700 p-8 shadow-2xl">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400'; ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= CSRFProtection::field() ?>
                
                <input type="text" name="fullname" value="<?= htmlspecialchars($form_data['fullname'] ?? '') ?>" 
                       placeholder="الاسم الكامل" class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white placeholder-slate-400" required>
                
                <input type="tel" name="phone" value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" 
                       placeholder="رقم الهاتف (07XXXXXXXXXX)" class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white placeholder-slate-400" required>
                
                <select name="province" class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white" required>
                    <option value="">اختر المحافظة</option>
                    <option value="بغداد">بغداد</option>
                    <option value="البصرة">البصرة</option>
                    <option value="الموصل">الموصل</option>
                </select>

                <select name="user_type" class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white" required>
                    <option value="customer">عميل</option>
                    <option value="driver">سائق كرين</option>
                    <option value="kia">سائق كيا</option>
                </select>
                
                <input type="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" 
                       placeholder="البريد الإلكتروني" class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white placeholder-slate-400" required>
                
                <input type="password" name="password" placeholder="كلمة مرور قوية (8+ أحرف)" 
                       class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white placeholder-slate-400" required>
                
                <input type="password" name="confirm_password" placeholder="تأكيد كلمة المرور" 
                       class="w-full px-4 py-3 bg-slate-700 rounded-lg text-white placeholder-slate-400" required>
                
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-bold rounded-lg transition">
                    إنشاء الحساب
                </button>
            </form>

            <div class="mt-6 text-center text-slate-400">
                <p>لديك حساب؟ <a href="login.php" class="text-amber-400 hover:text-amber-300 font-semibold">سجل دخولك</a></p>
            </div>
        </div>
    </div>
</body>
</html>
