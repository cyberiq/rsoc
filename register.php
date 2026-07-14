<?php
session_start();
require 'config.php';

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $autoloadFound = false;
} else {
    require $autoloadPath;
    $autoloadFound = true;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';
$debug_info = '';

$selected_user_type = $_GET['user_type'] ?? 'customer';
if (!in_array($selected_user_type, ['customer', 'driver', 'kia'], true)) {
    $selected_user_type = 'customer';
}

$fullname = '';
$phone = '';
$province = '';
$email = '';
$user_type = $selected_user_type;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'customer';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '❌ البريد الإلكتروني غير صحيح.';
        $message_type = 'error';
    } elseif (empty($fullname) || empty($phone) || empty($province) || empty($password) || strlen($password) < 6) {
        $message = '❌ يرجى إدخال البيانات كاملة وكلمة مرور لا تقل عن 6 أحرف.';
        $message_type = 'error';
    } elseif (!in_array($user_type, ['customer', 'driver', 'kia'], true)) {
        $message = '❌ نوع الحساب غير صحيح.';
        $message_type = 'error';
    } elseif (!$autoloadFound) {
        $message = '❌ لم يتم تثبيت الاعتمادات المطلوبة. يرجى تشغيل composer install ثم إعادة محاولة التسجيل.';
        $message_type = 'error';
    } else {
        $table_map = [
            'customer' => 'customers',
            'driver' => 'drivers',
            'kia' => 'kias'
        ];
        $table = $table_map[$user_type];
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $verification_code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $existing_tables = ['customers', 'drivers', 'kias'];
            $already_exists = false;
            $existing_label = 'هذا الحساب';
            foreach ($existing_tables as $existing_table) {
                $check = $pdo->prepare("SELECT id, fullname FROM $existing_table WHERE email = ? LIMIT 1");
                $check->execute([$email]);
                $existing_user = $check->fetch();
                if ($existing_user) {
                    $already_exists = true;
                    $existing_label = ($existing_table === 'customers') ? 'مستخدم عادي' : (($existing_table === 'drivers') ? 'سائق كرين' : 'سائق كيا');
                    break;
                }
            }

            if ($already_exists) {
                $message = '❌ هذا البريد مسجل مسبقاً كـ ' . $existing_label . '.';
                $message_type = 'error';
            } else {
                $insert = $pdo->prepare("INSERT INTO $table (fullname, phone, province, email, password, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $insert->execute([$fullname, $phone, $province, $email, $password_hash, $verification_code]);

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->CharSet = 'UTF-8';
                $mail->Timeout = SMTP_TIMEOUT;
                $mail->SMTPKeepAlive = false;
                $mail->SMTPAutoTLS = SMTP_AUTO_TLS;
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $fullname);
                $mail->Subject = 'رمز تفعيل حسابك في تطبيق كرين';
                $mail->isHTML(true);
                $mail->Body = '<h3>مرحبا ' . $fullname . '</h3><p>رمز التفعيل الخاص بك هو:</p><h1 style="font-size:32px; letter-spacing:4px;">' . $verification_code . '</h1><p>أو استخدم هذا الرابط:</p><p><a href="' . APP_URL . '/verify.php?email=' . urlencode($email) . '&user_type=' . $user_type . '">تفعيل الحساب الآن</a></p>';
                $mail->send();
 
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_user_type'] = $user_type;
 
                header('Location: verify.php');
                exit;
            }
        } catch (PDOException $e) {
            $message = '❌ حدث خطأ في حفظ البيانات: ' . $e->getMessage();
            $message_type = 'error';
        } catch (Exception $e) {
            $message = '❌ تعذر إرسال البريد الإلكتروني: ' . $e->getMessage();
            $message_type = 'warning';
            if (defined('APP_DEBUG') && APP_DEBUG === true) {
                $debug_info = 'DEBUG: رمز التفعيل هو ' . $verification_code . '. يمكنك تفعيل الحساب يدوياً عبر الرابط: ' . APP_URL . '/verify.php?email=' . urlencode($email) . '&user_type=' . $user_type;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f172a, #111827);
            color: #f8fafc;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.35);
        }
        .top-links {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .top-links a {
            color: #94a3b8;
            text-decoration: none;
        }
        .top-links a:hover { color: #fbbf24; }
        h2 { margin: 0 0 8px; font-size: 24px; }
        .subtitle { color: #94a3b8; margin-bottom: 20px; font-size: 14px; }
        .message {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }
        .message.error { background: rgba(248, 113, 113, 0.12); color: #fca5a5; border: 1px solid rgba(248, 113, 113, 0.25); }
        .message.success { background: rgba(74, 222, 128, 0.12); color: #86efac; border: 1px solid rgba(74, 222, 128, 0.25); }
        input, select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            margin-bottom: 12px;
            border-radius: 12px;
            border: 1px solid #334155;
            background: #020617;
            color: #f8fafc;
        }
        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #111827;
            font-weight: 900;
            cursor: pointer;
        }
        .small { font-size: 13px; color: #94a3b8; margin-top: 12px; text-align: center; }
        .small a { color: #fbbf24; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="top-links">
            <a href="home.php">⬅️ الرجوع للرئيسية</a>
            <a href="login.php">🔐 تسجيل الدخول</a>
        </div>
        <h2>إنشاء حساب جديد</h2>
        <p class="subtitle">أدخل بياناتك وسنرسل لك رمز تحقق بالبريد الإلكتروني.</p>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php if (!empty($debug_info)): ?>
                <div class="message success" style="background: rgba(56, 189, 248, 0.1); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.25); word-break: break-word;">
                    <?= htmlspecialchars($debug_info) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="fullname" placeholder="الاسم الكامل" value="<?= htmlspecialchars($fullname) ?>" required>
            <input type="text" name="phone" placeholder="رقم الهاتف" value="<?= htmlspecialchars($phone) ?>" required>
            <input type="text" name="province" placeholder="المحافظة" value="<?= htmlspecialchars($province) ?>" required>
            <input type="email" name="email" placeholder="البريد الإلكتروني" value="<?= htmlspecialchars($email) ?>" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <select name="user_type" required>
                <option value="customer" <?= $user_type === 'customer' ? 'selected' : '' ?>>مستخدم عادي</option>
                <option value="driver" <?= $user_type === 'driver' ? 'selected' : '' ?>>سائق كرين</option>
                <option value="kia" <?= $user_type === 'kia' ? 'selected' : '' ?>>سائق كيا</option>
            </select>
            <button type="submit">📝 إنشاء الحساب</button>
        </form>

        <p class="small">
            لديك حساب بالفعل؟ <a href="login.php">سجل دخول الآن</a>
        </p>
    </div>
</body>
</html>
