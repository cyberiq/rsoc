<?php
require 'autoloader.php';
require 'config-improved.php';

SessionManager::start();
SessionManager::requireRole(['admin']);

$user_type = $_GET['type'] ?? 'customer';
$allowed_types = ['customer', 'driver', 'kia'];
if (!in_array($user_type, $allowed_types)) $user_type = 'customer';

$table = ['customer' => 'customers', 'driver' => 'drivers', 'kia' => 'kias'][$user_type];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRFProtection::verifyToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['user_id']]);
        $message = '✅ تم الحذف';
    }
}

$stmt = $pdo->prepare("SELECT * FROM $table " . ($user_type === 'customer' ? "WHERE role != 'admin'" : "") . " LIMIT 100");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap'); body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <nav class="bg-slate-800 border-b border-slate-700">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">
            <a href="admin-panel.php" class="text-slate-300 hover:text-white">← العودة</a>
            <h1 class="text-2xl font-bold text-white">إدارة المستخدمين</h1>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6">
        <?php if ($message): ?><div class="mb-6 p-4 rounded-xl bg-green-500/10 text-green-400"><?= $message ?></div><?php endif; ?>

        <div class="flex gap-3 mb-6">
            <a href="?type=customer" class="px-4 py-2 rounded-lg font-semibold <?= $user_type === 'customer' ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300' ?>">👤 عملاء</a>
            <a href="?type=driver" class="px-4 py-2 rounded-lg font-semibold <?= $user_type === 'driver' ? 'bg-amber-600 text-white' : 'bg-slate-700 text-slate-300' ?>">🚕 سائقون</a>
            <a href="?type=kia" class="px-4 py-2 rounded-lg font-semibold <?= $user_type === 'kia' ? 'bg-green-600 text-white' : 'bg-slate-700 text-slate-300' ?>">🚙 شركات</a>
        </div>

        <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-right text-slate-300">الاسم</th>
                        <th class="px-6 py-3 text-right text-slate-300">البريد</th>
                        <th class="px-6 py-3 text-right text-slate-300">الهاتف</th>
                        <th class="px-6 py-3 text-right text-slate-300">الحالة</th>
                        <th class="px-6 py-3 text-right text-slate-300">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b border-slate-700 hover:bg-slate-700/30">
                        <td class="px-6 py-4 text-white"><?= htmlspecialchars($user['fullname']) ?></td>
                        <td class="px-6 py-4 text-slate-300"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="px-6 py-4 text-slate-300"><?= htmlspecialchars($user['phone']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-sm <?= $user['is_verified'] ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' ?>">
                                <?= $user['is_verified'] ? '✅' : '⏳' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" style="display: inline;">
                                <?= CSRFProtection::field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="text-red-400 hover:text-red-300 text-sm" onclick="return confirm('حذف؟')">حذف</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
