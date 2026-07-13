<?php
require 'autoloader.php';
require 'config-improved.php';

SessionManager::start();
SessionManager::requireRole(['admin']);

$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND role = 'admin'");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRFProtection::verifyToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $pdo->prepare("UPDATE customers SET fullname = ?, phone = ? WHERE id = ?")->execute(
            [$_POST['fullname'], $_POST['phone'], $admin_id]
        );
        $message = '✅ تم التحديث';
    } elseif ($action === 'change_password') {
        if (!password_verify($_POST['current_password'], $admin['password'])) {
            $message = '❌ كلمة المرور خاطئة';
        } else {
            $hashed = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?")->execute([$hashed, $admin_id]);
            $message = '✅ تم التحديث';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap'); body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <nav class="bg-slate-800 border-b border-slate-700">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center gap-4">
            <a href="admin-panel.php" class="text-slate-300 hover:text-white">← العودة</a>
            <h1 class="text-2xl font-bold text-white">الإعدادات</h1>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6">
        <?php if ($message): ?><div class="mb-6 p-4 rounded-xl <?= strpos($message, '✅') === 0 ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' ?>"><?= $message ?></div><?php endif; ?>

        <div class="space-y-6">
            <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
                <h2 class="text-xl font-bold text-white mb-4">👤 البيانات الشخصية</h2>
                <form method="POST" class="space-y-4">
                    <?= CSRFProtection::field() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <input type="text" name="fullname" value="<?= htmlspecialchars($admin['fullname']) ?>" class="w-full px-4 py-2 bg-slate-700 rounded-lg text-white" placeholder="الاسم">
                    <input type="tel" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>" class="w-full px-4 py-2 bg-slate-700 rounded-lg text-white" placeholder="الهاتف">
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg">✅ حفظ</button>
                </form>
            </div>

            <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
                <h2 class="text-xl font-bold text-white mb-4">🔐 تغيير كلمة المرور</h2>
                <form method="POST" class="space-y-4 max-w-md">
                    <?= CSRFProtection::field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <input type="password" name="current_password" class="w-full px-4 py-2 bg-slate-700 rounded-lg text-white" placeholder="كلمة المرور الحالية" required>
                    <input type="password" name="new_password" class="w-full px-4 py-2 bg-slate-700 rounded-lg text-white" placeholder="كلمة مرور جديدة" required>
                    <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">🔄 تحديث</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
