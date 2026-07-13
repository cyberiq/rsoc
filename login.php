<?php
$sessionDir = __DIR__ . '/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0777, true);
}

ini_set('session.save_path', $sessionDir);
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');

session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $loginNotify = "📌 تم فتح صفحة تسجيل الدخول\n" .
                   "🌐 المضيف: `{$host}`\n" .
                   "🌎 IP: `{$remoteIp}`\n" .
                   "🕒 الوقت: `" . date('Y-m-d H:i:s') . "`\n" .
                   "🧭 User-Agent: `" . addslashes(substr($userAgent, 0, 120)) . "`";
    sendTelegramAlert($loginNotify);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$posted_email = '';
$max_attempts = 5;
$lockout_time = 15 * 60;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_last_attempt'] = 0;
}

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_elapsed = time() - $_SESSION['login_last_attempt'];
    if ($time_elapsed < $lockout_time) {
        $remaining_time = ceil(($lockout_time - $time_elapsed) / 60);
        $message = "❌ تم تجاوز عدد محاولات الدخول. يرجى المحاولة بعد $remaining_time دقيقة.";
        $message_type = 'error';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_last_attempt'] = 0;
    }
}

$message = $message ?? '';
$message_type = $message_type ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_email = trim($_POST['email'] ?? '');

    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "❌ خطأ أمان: طلب الأمان غير صالح. يرجى إعادة تحميل الصفحة والمحاولة مرة أخرى.";
        $message_type = 'error';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $email = trim(strtolower($posted_email));
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ البريد الإلكتروني غير صحيح.";
            $message_type = 'error';
        } elseif (empty($password) || strlen($password) < 6) {
            $message = "❌ كلمة المرور غير صحيحة.";
            $message_type = 'error';
        } else {
            $table_map = [
                'customer' => 'customers',
                'driver' => 'drivers',
                'kia' => 'kias'
            ];

            $found_user = null;
            $detected_user_type = null;

            foreach ($table_map as $type => $table) {
                try {
                    $columns = ['id', 'fullname', 'email', 'password', 'is_verified'];
                    if ($table === 'customers') {
                        $columns[] = 'role';
                    }
                    $stmt = $pdo->prepare("SELECT " . implode(', ', $columns) . " FROM $table WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($candidate) {
                        $found_user = $candidate;
                        $detected_user_type = $type;
                        break;
                    }
                } catch (PDOException $e) {
                    continue;
                }
            }

            if ($found_user && password_verify($password, $found_user['password'])) {
                if (empty($found_user['is_verified'])) {
                    $message = "⚠️ حسابك لم يتم التحقق منه بعد. يرجى التحقق من بريدك الإلكتروني.";
                    $message_type = 'warning';
                } else {
                    $_SESSION['user_id'] = $found_user['id'];
                    $_SESSION['user_type'] = $detected_user_type;
                    $_SESSION['user_email'] = $found_user['email'];
                    $_SESSION['user_name'] = $found_user['fullname'];
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['login_last_attempt'] = 0;

                    if (!empty($found_user['role']) && $found_user['role'] === 'admin') {
                        $_SESSION['user_type'] = 'admin';
                    }

                    if ($_SESSION['user_type'] === 'admin') {
                        header('Location: manager_dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                }
            } else {
                $message = "❌ البريد الإلكتروني أو كلمة المرور غير صحيحة.";
                $message_type = 'error';
                $_SESSION['login_attempts']++;
                $_SESSION['login_last_attempt'] = time();

                if ($_SESSION['login_attempts'] >= $max_attempts - 2) {
                    $remaining = $max_attempts - $_SESSION['login_attempts'];
                    $message .= " ⚠️ لديك $remaining محاولات متبقية قبل قفل الحساب لمدة 15 دقيقة.";
                }
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
    <title>تسجيل الدخول - تطبيق كرين</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-between mb-6">
            <a href="home.php" class="text-sm text-slate-400 hover:text-amber-400 transition">⬅️ الرجوع للرئيسية</a>
            <a href="choose_login.php" class="text-sm text-slate-400 hover:text-amber-400 transition">🔄 التحكم في الدخول</a>
        </div>
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 rounded-full mb-4">
                <span class="text-2xl font-black text-white">🚕 كرين</span>
            </div>
            <h1 class="text-3xl font-black text-white mb-2">تطبيق كرين</h1>
            <p class="text-slate-400">نظام توصيل الركاب الذكي</p>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-3xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-white text-center mb-2">تسجيل الدخول</h2>
            <p class="text-center text-slate-400 text-sm mb-6">ادخل بيانات حسابك للمتابعة</p>
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm font-medium border
                    <?php 
                    if ($message_type === 'error') echo 'bg-red-500/10 text-red-400 border-red-500/30';
                    elseif ($message_type === 'warning') echo 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30';
                    else echo 'bg-green-500/10 text-green-400 border-green-500/30';
                    ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <div class="mb-6 p-4 rounded-xl text-sm font-medium bg-green-500/10 text-green-400 border border-green-500/30">
                    ✅ <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>
            <form method="POST" id="loginForm" novalidate class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-sm text-amber-300">
                    سيتم التعرف على نوع الحساب تلقائياً من البريد الإلكتروني عند تسجيل الدخول.
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" placeholder="your@email.com" required
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none transition placeholder-slate-500 text-left"
                           dir="ltr" value="<?= htmlspecialchars($posted_email) ?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">كلمة المرور</label>
                    <input type="password" name="password" placeholder="••••••••" required
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-xl text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none transition placeholder-slate-500"
                           dir="ltr">
                </div>
                <button type="submit" id="loginBtn"
                        class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-bold rounded-xl transition duration-200 active:scale-95 flex items-center justify-center gap-2 mt-6">
                    <span id="loginBtnText">🔓 دخول</span>
                    <span id="loginBtnSpinner" class="hidden">⌛</span>
                </button>
            </form>
            <div class="my-6 flex items-center gap-4">
                <div class="flex-1 h-px bg-slate-700"></div>
                <span class="text-slate-500 text-xs">أم</span>
                <div class="flex-1 h-px bg-slate-700"></div>
            </div>
            <div class="space-y-3 text-sm">
                <a href="forgot_password.php" class="block text-center text-amber-400 hover:text-amber-300 font-semibold transition">🔑 هل نسيت كلمة المرور؟</a>
                <div class="text-center text-slate-400">
                    ليس لديك حساب؟ 
                    <a href="choose_action.php" class="text-amber-400 hover:text-amber-300 font-semibold transition">إنشاء حساب جديد</a>
                </div>
            </div>
        </div>
        <div class="mt-6 p-4 bg-slate-800 border border-slate-700 rounded-xl text-center">
            <p class="text-xs text-slate-500">🔒 بيانات حسابك آمنة وسرية تماماً</p>
        </div>
    </div>
</body>
</html>
