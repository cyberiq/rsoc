<?php
session_start();
require 'config.php';

$message = '';
$message_type = '';

$saved_email = $_SESSION['verify_email'] ?? ($_GET['email'] ?? ($_POST['email'] ?? ''));
$saved_user_type = $_SESSION['verify_user_type'] ?? ($_GET['user_type'] ?? ($_POST['user_type'] ?? ''));

$detected_user_type = '';
if (!empty($saved_email) && filter_var($saved_email, FILTER_VALIDATE_EMAIL)) {
    foreach (['customer', 'driver', 'kia'] as $candidate_type) {
        $table = ($candidate_type === 'driver') ? 'drivers' : (($candidate_type === 'kia') ? 'kias' : 'customers');
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ? LIMIT 1");
        $stmt->execute([$saved_email]);
        if ($stmt->fetch()) {
            $detected_user_type = $candidate_type;
            break;
        }
    }
}

if (empty($saved_user_type) && !empty($detected_user_type)) {
    $saved_user_type = $detected_user_type;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $code = trim($_POST['code'] ?? '');
    $requested_user_type = $_POST['user_type'] ?? $saved_user_type;
    $user_type = $requested_user_type;

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        foreach (['customer', 'driver', 'kia'] as $candidate_type) {
            $table = ($candidate_type === 'driver') ? 'drivers' : (($candidate_type === 'kia') ? 'kias' : 'customers');
            $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $user_type = $candidate_type;
                break;
            }
        }
    }

    if (!in_array($user_type, ['driver', 'customer', 'kia'], true)) {
        $message = '❌ نوع الحساب المختار غير صحيح.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '❌ البريد الإلكتروني غير صحيح.';
        $message_type = 'error';
    } elseif (strlen($code) !== 6 || !ctype_digit($code)) {
        $message = '❌ الرمز يجب أن يكون 6 أرقام.';
        $message_type = 'error';
    } else {
        $table = ($user_type === 'driver') ? 'drivers' : (($user_type === 'kia') ? 'kias' : 'customers');

        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ? AND verification_code = ? LIMIT 1");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['is_verified'] == 1) {
                $message = '✅ حسابك مفعل مسبقاً!';
                $message_type = 'success';
            } else {
                $update = $pdo->prepare("UPDATE $table SET is_verified = 1, verification_code = NULL WHERE email = ?");
                $update->execute([$email]);
                unset($_SESSION['verify_email']);
                unset($_SESSION['verify_user_type']);
                header('Location: login.php?msg=' . urlencode('✅ تم تأكيد حسابك بنجاح! يمكنك الآن تسجيل الدخول باستخدام البريد وكلمة المرور.'));
                exit;
            }
        } else {
            $message = '❌ الرمز المدخل أو البريد الإلكتروني غير صحيح.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>تفعيل الحساب</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap');
        
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: #0f172a; 
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-card {
            background-color: #1e293b;
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
        }

        h2 { text-align: center; color: white; font-weight: 900; font-size: 24px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 12px; color: #94a3b8; font-weight: bold; margin-bottom: 8px; }
        
        input, select {
            width: 100%;
            padding: 12px;
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            color: white;
            box-sizing: border-box;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #d97706;
            color: #0f172a;
            font-weight: 900;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-submit:hover { background-color: #b45309; }

        .message {
            padding: 15px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        .success { background-color: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .error { background-color: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

        a { color: #94a3b8; text-decoration: none; font-size: 12px; font-weight: bold; display: block; text-align: center; margin-top: 20px; }
        a:hover { color: white; }
    </style>
</head>
<body>

    <div class="login-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:16px;">
            <a href="home.php" style="color:#94a3b8; text-decoration:none; font-size:12px;">⬅️ الرجوع للرئيسية</a>
            <a href="register.php" style="color:#94a3b8; text-decoration:none; font-size:12px;">🔄 إنشاء حساب جديد</a>
        </div>
        <h2>تفعيل الحساب</h2>

        <?php if ($message): ?>
            <div class="message <?= $message_type === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= htmlspecialchars($saved_email) ?>" required>
            </div>

            <div class="form-group">
                <label>رمز التفعيل (6 أرقام)</label>
                <input type="text" name="code" required maxlength="6" style="text-align: center; font-size: 20px; letter-spacing: 5px;">
            </div>

            <div class="message success" style="margin-bottom: 12px; background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); text-align: right;">
                سيتم التعرف على نوع الحساب تلقائياً من البريد الإلكتروني عند التفعيل.
            </div>

            <button type="submit" class="btn-submit">✅ تأكيد التفعيل</button>
        </form>

        <a href="choose_login.php">انتقل لتسجيل الدخول ⬅️</a>
    </div>

</body>
</html>
