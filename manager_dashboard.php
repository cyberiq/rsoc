<?php
session_start();
require 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function format_iqd($amount): string {
    return number_format((int) $amount) . ' د.ع';
}

function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earth_radius = 6371;
    $lat_delta = deg2rad($lat2 - $lat1);
    $lon_delta = deg2rad($lon2 - $lon1);
    $a = sin($lat_delta / 2) * sin($lat_delta / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * sin($lon_delta / 2) * sin($lon_delta / 2);

    return $earth_radius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: choose_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, fullname, email, phone, province, is_verified, role FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = null;
}

if (!$user) {
    session_destroy();
    header('Location: choose_login.php');
    exit;
}

$flash_message = '';
$flash_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $flash_message = '❌ تحقق الأمان فشل، أعد المحاولة.';
        $flash_type = 'error';
    } else {
        $account_type = $_POST['account_type'] ?? '';
        $account_id = (int) ($_POST['account_id'] ?? 0);
        $amount_iqd = (int) ($_POST['amount_iqd'] ?? 0);
        $allowed_tables = [
            'customer' => 'customers',
            'driver' => 'drivers',
            'kia' => 'kias',
        ];

        if (!isset($allowed_tables[$account_type]) || $account_id <= 0 || $amount_iqd <= 0) {
            $flash_message = '❌ بيانات شحن الرصيد غير صحيحة.';
            $flash_type = 'error';
        } else {
            try {
                $pdo->beginTransaction();

                $table = $allowed_tables[$account_type];
                $stmt = $pdo->prepare("UPDATE $table SET balance_iqd = balance_iqd + ? WHERE id = ?");
                $stmt->execute([$amount_iqd, $account_id]);

                $trx = $pdo->prepare("INSERT INTO balance_transactions (account_type, account_id, amount_iqd, transaction_kind, reason, admin_id) VALUES (?, ?, ?, 'credit', ?, ?)");
                $trx->execute([$account_type, $account_id, $amount_iqd, 'شحن من لوحة المدير', $user_id]);

                $pdo->commit();
                $flash_message = '✅ تم إضافة الرصيد بنجاح: ' . format_iqd($amount_iqd);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flash_message = '❌ فشل تحديث الرصيد.';
                $flash_type = 'error';
            }
        }
    }
}

$stats = [
    'customers' => 0,
    'drivers' => 0,
    'kias' => 0,
    'requests' => 0,
    'pending_requests' => 0,
    'balances' => 0,
];

$recent_users = [];
$recent_requests = [];

try {
    $stats['customers'] = (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE COALESCE(role, '') != 'admin'")->fetchColumn();
    $stats['drivers'] = (int) $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    $stats['kias'] = (int) $pdo->query("SELECT COUNT(*) FROM kias")->fetchColumn();
    $stats['requests'] = (int) $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();

    $pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE status IN ('pending', 'accepted', 'in_progress')");
    $pending_stmt->execute();
    $stats['pending_requests'] = (int) $pending_stmt->fetchColumn();

    $recent_users_sql = "
        SELECT account_id, account_type_key, fullname, email, phone, province, account_type, created_at, latitude, longitude, balance_iqd FROM (
            SELECT
                c.id AS account_id,
                'customer' AS account_type_key,
                c.fullname,
                c.email,
                c.phone,
                c.province,
                'عميل' AS account_type,
                c.created_at,
                c.balance_iqd,
                (
                    SELECT sr.latitude
                    FROM service_requests sr
                    WHERE sr.customer_id = c.id
                    ORDER BY datetime(sr.requested_at) DESC
                    LIMIT 1
                ) AS latitude,
                (
                    SELECT sr.longitude
                    FROM service_requests sr
                    WHERE sr.customer_id = c.id
                    ORDER BY datetime(sr.requested_at) DESC
                    LIMIT 1
                ) AS longitude
            FROM customers c
            WHERE COALESCE(c.role, '') != 'admin'

            UNION ALL

            SELECT
                d.id AS account_id,
                'driver' AS account_type_key,
                d.fullname,
                d.email,
                d.phone,
                d.province,
                'سائق ونش' AS account_type,
                d.created_at,
                d.balance_iqd,
                dl.latitude,
                dl.longitude
            FROM drivers d
            LEFT JOIN driver_locations dl ON dl.driver_id = d.id

            UNION ALL

            SELECT
                k.id AS account_id,
                'kia' AS account_type_key,
                k.fullname,
                k.email,
                k.phone,
                k.province,
                'سائق كيا' AS account_type,
                k.created_at,
                k.balance_iqd,
                dlk.latitude,
                dlk.longitude
            FROM kias k
            LEFT JOIN driver_locations dlk ON dlk.driver_id = k.id
        ) all_users
        ORDER BY datetime(created_at) DESC
        LIMIT 20
    ";
    $recent_users = $pdo->query($recent_users_sql)->fetchAll(PDO::FETCH_ASSOC);

    $recent_requests_sql = "
        SELECT
            sr.id,
            sr.status,
            sr.charge_applied,
            sr.latitude,
            sr.longitude,
            sr.requested_at,
            c.fullname AS customer_name,
            COALESCE(d.fullname, k.fullname) AS provider_name,
            COALESCE(dl.latitude, dlk.latitude) AS provider_latitude,
            COALESCE(dl.longitude, dlk.longitude) AS provider_longitude
        FROM service_requests sr
        LEFT JOIN customers c ON c.id = sr.customer_id
        LEFT JOIN drivers d ON d.id = sr.driver_id
        LEFT JOIN kias k ON k.id = sr.driver_id
        LEFT JOIN driver_locations dl ON dl.driver_id = d.id
        LEFT JOIN driver_locations dlk ON dlk.driver_id = k.id
        ORDER BY datetime(sr.requested_at) DESC
        LIMIT 20
    ";
    $recent_requests = $pdo->query($recent_requests_sql)->fetchAll(PDO::FETCH_ASSOC);

    $stats['balances'] = (int) $pdo->query("SELECT COALESCE((SELECT SUM(balance_iqd) FROM customers), 0) + COALESCE((SELECT SUM(balance_iqd) FROM drivers), 0) + COALESCE((SELECT SUM(balance_iqd) FROM kias), 0)")->fetchColumn();
} catch (PDOException $e) {
    $recent_users = [];
    $recent_requests = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المدير العام - تطبيق كرين</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        #internalMap { height: 360px; width: 100%; border-radius: 14px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-amber-500 text-sm font-bold">🛡️ لوحة المدير العام</p>
                <h1 class="text-2xl font-black text-white">تطبيق كرين - الإدارة المركزية</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 text-sm hover:border-amber-500 hover:text-amber-400 transition">↩️ العودة</a>
                <a href="logout.php" class="px-4 py-2 rounded-xl bg-red-600/20 text-red-300 border border-red-600/30 text-sm hover:bg-red-600/30 transition">🚪 تسجيل الخروج</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($flash_message): ?>
            <div class="mb-6 rounded-2xl border px-4 py-3 text-sm font-bold <?= $flash_type === 'error' ? 'border-red-500/30 bg-red-500/10 text-red-300' : 'border-green-500/30 bg-green-500/10 text-green-300' ?>">
                <?= htmlspecialchars($flash_message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-6">
            <section class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-sm text-amber-400 font-bold">مرحباً بك</p>
                        <h2 class="text-xl font-black text-white"><?= htmlspecialchars($user['fullname']) ?></h2>
                    </div>
                    <div class="px-3 py-1 rounded-full border border-amber-500/30 bg-amber-500/10 text-amber-400 text-sm font-bold">مدير عام</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <p class="text-slate-500 text-xs">الحالة</p>
                        <p class="text-lg font-black text-white">✅ مفعل</p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <p class="text-slate-500 text-xs">البريد</p>
                        <p class="text-sm font-bold text-white"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <p class="text-slate-500 text-xs">المحافظة</p>
                        <p class="text-lg font-black text-white"><?= htmlspecialchars($user['province']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 md:col-span-3">
                        <p class="text-slate-500 text-xs">إجمالي الأرصدة داخل النظام</p>
                        <p class="text-xl font-black text-emerald-400"><?= format_iqd($stats['balances']) ?></p>
                    </div>
                </div>
            </section>

            <aside class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6">
                <h3 class="text-lg font-black text-white mb-4">إجراءات سريعة</h3>
                <div class="space-y-3">
                    <a href="create_admin.php" class="block rounded-2xl border border-slate-800 bg-slate-950/70 p-4 hover:border-amber-500 transition">
                        <p class="font-bold text-white">➕ إنشاء أو تحديث حساب مدير</p>
                        <p class="text-sm text-slate-500">إدارة الحسابات العليا والتهيئة الأولية.</p>
                    </a>
                    <a href="home.php" class="block rounded-2xl border border-slate-800 bg-slate-950/70 p-4 hover:border-sky-500 transition">
                        <p class="font-bold text-white">🏠 العودة للصفحة الرئيسية</p>
                        <p class="text-sm text-slate-500">عرض الموقع العام والعملاء.</p>
                    </a>
                </div>
            </aside>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                <p class="text-sm text-slate-400">إدارة المستخدمين</p>
                <h4 class="text-xl font-black text-white mt-2">العملاء والسائقين</h4>
                <p class="text-sm text-slate-500 mt-3">عرض مباشر لأعداد الحسابات وسجل أحدث المستخدمين.</p>
                <div class="grid grid-cols-3 gap-3 mt-4 text-center">
                    <div class="rounded-xl bg-slate-950/70 border border-slate-800 py-3">
                        <p class="text-[11px] text-slate-400">عملاء</p>
                        <p class="text-lg font-black text-emerald-400"><?= (int) $stats['customers'] ?></p>
                    </div>
                    <div class="rounded-xl bg-slate-950/70 border border-slate-800 py-3">
                        <p class="text-[11px] text-slate-400">ونش</p>
                        <p class="text-lg font-black text-amber-400"><?= (int) $stats['drivers'] ?></p>
                    </div>
                    <div class="rounded-xl bg-slate-950/70 border border-slate-800 py-3">
                        <p class="text-[11px] text-slate-400">كيا</p>
                        <p class="text-lg font-black text-sky-400"><?= (int) $stats['kias'] ?></p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <a href="profile.php" class="inline-flex items-center rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-emerald-500 hover:text-emerald-400 transition">👥 ملف الحسابات</a>
                    <a href="account_settings.php" class="inline-flex items-center rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-amber-500 hover:text-amber-400 transition">⚙️ تعديل البيانات</a>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                <p class="text-sm text-slate-400">الطلبات</p>
                <h4 class="text-xl font-black text-white mt-2">استلام وإدارة الطلبات</h4>
                <p class="text-sm text-slate-500 mt-3">مؤشرات حية للطلبات مع وصول مباشر للشاشة التشغيلية.</p>
                <div class="grid grid-cols-2 gap-3 mt-4 text-center">
                    <div class="rounded-xl bg-slate-950/70 border border-slate-800 py-3">
                        <p class="text-[11px] text-slate-400">كل الطلبات</p>
                        <p class="text-lg font-black text-indigo-400"><?= (int) $stats['requests'] ?></p>
                    </div>
                    <div class="rounded-xl bg-slate-950/70 border border-slate-800 py-3">
                        <p class="text-[11px] text-slate-400">نشطة</p>
                        <p class="text-lg font-black text-orange-400"><?= (int) $stats['pending_requests'] ?></p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <a href="request_service.php" class="inline-flex items-center rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-indigo-500 hover:text-indigo-400 transition">🧭 متابعة الطلبات</a>
                    <a href="customer_dashboard.php" class="inline-flex items-center rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-cyan-500 hover:text-cyan-400 transition">🗺️ خريطة التنفيذ</a>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                <p class="text-sm text-slate-400">الإعدادات</p>
                <h4 class="text-xl font-black text-white mt-2">التحكم المركزي</h4>
                <p class="text-sm text-slate-500 mt-3">ربط كامل مع أدوات التهيئة والإدارة والصيانة اليومية.</p>
                <div class="mt-4 space-y-2">
                    <a href="create_admin.php" class="block rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-amber-500 hover:text-amber-400 transition">🛡️ إدارة حسابات المدراء</a>
                    <a href="setup_db.php" class="block rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-emerald-500 hover:text-emerald-400 transition">🧱 تهيئة قاعدة البيانات</a>
                    <a href="seed.php" class="block rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-sky-500 hover:text-sky-400 transition">🌱 توليد بيانات تجريبية</a>
                    <a href="add_demo_drivers.php" class="block rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-200 hover:border-pink-500 hover:text-pink-400 transition">🚗 إضافة سائقين تجريبيين</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-black text-white">آخر الحسابات المسجلة</h3>
                    <a href="profile.php" class="text-xs text-emerald-400 hover:text-emerald-300">عرض الكل</a>
                </div>
                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" data-target="users" data-filter="all" class="filter-btn rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs text-emerald-300">الكل</button>
                    <button type="button" data-target="users" data-filter="active" class="filter-btn rounded-lg border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">نشط</button>
                    <button type="button" data-target="users" data-filter="inactive" class="filter-btn rounded-lg border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">خامل</button>
                    <button type="button" id="toggleDemoRows" class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-300">تنظيف العرض التجريبي</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800">
                                <th class="text-right py-2 px-2">الاسم</th>
                                <th class="text-right py-2 px-2">النوع</th>
                                <th class="text-right py-2 px-2">الرصيد</th>
                                <th class="text-right py-2 px-2">المحافظة</th>
                                <th class="text-right py-2 px-2">الموقع</th>
                                <th class="text-right py-2 px-2">إضافة رصيد</th>
                                <th class="text-right py-2 px-2">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_users)): ?>
                                <tr>
                                    <td colspan="7" class="py-4 px-2 text-center text-slate-500">لا توجد بيانات مستخدمين حديثة.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_users as $u): ?>
                                    <?php
                                        $user_has_location = !empty($u['latitude']) && !empty($u['longitude']);
                                        $user_state = $user_has_location ? 'active' : 'inactive';
                                        $user_text_for_demo = ($u['fullname'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['phone'] ?? '');
                                        $user_is_demo = preg_match('/demo|test|تجريبي|تجربة/i', $user_text_for_demo) ? 1 : 0;
                                    ?>
                                    <tr class="border-b border-slate-900/70 hover:bg-slate-950/40" data-table="users" data-state="<?= $user_state ?>" data-demo="<?= $user_is_demo ?>">
                                        <td class="py-2 px-2 text-white font-semibold"><?= htmlspecialchars($u['fullname'] ?? '-') ?></td>
                                        <td class="py-2 px-2 text-slate-300"><?= htmlspecialchars($u['account_type'] ?? '-') ?></td>
                                        <td class="py-2 px-2 text-emerald-300 font-bold\"><?= format_iqd((int) ($u['balance_iqd'] ?? 0)) ?></td>
                                        <td class="py-2 px-2 text-slate-400"><?= htmlspecialchars($u['province'] ?? '-') ?></td>
                                        <td class="py-2 px-2 text-slate-400">
                                            <?php if (!empty($u['latitude']) && !empty($u['longitude'])): ?>
                                                <button type="button" class="open-map-btn inline-flex rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[11px] text-cyan-300 hover:border-cyan-400 hover:text-cyan-200 transition" data-lat="<?= htmlspecialchars((string) $u['latitude']) ?>" data-lng="<?= htmlspecialchars((string) $u['longitude']) ?>" data-label="<?= htmlspecialchars(($u['fullname'] ?? 'مستخدم') . ' - ' . ($u['account_type'] ?? ''), ENT_QUOTES) ?>">📍 عرض</button>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-600">غير متاح</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-2">
                                            <form method="POST" class="flex items-center gap-2">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="add_balance" value="1">
                                                <input type="hidden" name="account_type" value="<?= htmlspecialchars($u['account_type_key']) ?>">
                                                <input type="hidden" name="account_id" value="<?= (int) $u['account_id'] ?>">
                                                <input type="number" min="1000" step="1000" name="amount_iqd" value="5000" class="w-24 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white">
                                                <button type="submit" class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-xs text-emerald-300 hover:border-emerald-400">شحن</button>
                                            </form>
                                        </td>
                                        <td class="py-2 px-2 text-slate-500 text-xs"><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-black text-white">آخر الطلبات المستلمة</h3>
                    <a href="request_service.php" class="text-xs text-indigo-400 hover:text-indigo-300">عرض التفاصيل</a>
                </div>
                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" data-target="requests" data-filter="all" class="filter-btn rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs text-indigo-300">الكل</button>
                    <button type="button" data-target="requests" data-filter="active" class="filter-btn rounded-lg border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">نشط</button>
                    <button type="button" data-target="requests" data-filter="inactive" class="filter-btn rounded-lg border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">خامل</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800">
                                <th class="text-right py-2 px-2">#</th>
                                <th class="text-right py-2 px-2">العميل</th>
                                <th class="text-right py-2 px-2">المزوّد</th>
                                <th class="text-right py-2 px-2">الحالة</th>
                                <th class="text-right py-2 px-2">المسافة</th>
                                <th class="text-right py-2 px-2">الموقع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_requests)): ?>
                                <tr>
                                    <td colspan="5" class="py-4 px-2 text-center text-slate-500">لا توجد طلبات حديثة.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_requests as $r): ?>
                                    <?php
                                        $request_status = strtolower((string) ($r['status'] ?? ''));
                                        $request_state = in_array($request_status, ['pending', 'accepted', 'in_progress'], true) ? 'active' : 'inactive';
                                        $request_text_for_demo = ($r['customer_name'] ?? '') . ' ' . ($r['provider_name'] ?? '');
                                        $request_is_demo = preg_match('/demo|test|تجريبي|تجربة/i', $request_text_for_demo) ? 1 : 0;
                                        $route_distance = null;
                                        if (!empty($r['provider_latitude']) && !empty($r['provider_longitude']) && !empty($r['latitude']) && !empty($r['longitude'])) {
                                            $route_distance = haversine_km((float) $r['provider_latitude'], (float) $r['provider_longitude'], (float) $r['latitude'], (float) $r['longitude']);
                                        }
                                    ?>
                                    <tr class="border-b border-slate-900/70 hover:bg-slate-950/40" data-table="requests" data-state="<?= $request_state ?>" data-demo="<?= $request_is_demo ?>">
                                        <td class="py-2 px-2 text-white font-semibold">#<?= (int) ($r['id'] ?? 0) ?></td>
                                        <td class="py-2 px-2 text-slate-300"><?= htmlspecialchars($r['customer_name'] ?? 'غير محدد') ?></td>
                                        <td class="py-2 px-2 text-slate-400"><?= htmlspecialchars($r['provider_name'] ?? 'بانتظار الإسناد') ?></td>
                                        <td class="py-2 px-2">
                                            <span class="inline-flex rounded-full border border-indigo-500/30 bg-indigo-500/10 px-2 py-1 text-xs text-indigo-300"><?= htmlspecialchars($r['status'] ?? '-') ?></span>
                                        </td>
                                        <td class="py-2 px-2 text-xs text-amber-300"><?= $route_distance !== null ? number_format($route_distance, 2) . ' كم' : 'بانتظار الإسناد' ?></td>
                                        <td class="py-2 px-2">
                                            <?php if (!empty($r['latitude']) && !empty($r['longitude'])): ?>
                                                <button type="button" class="open-map-btn inline-flex rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-2 py-1 text-[11px] text-indigo-300 hover:border-indigo-400 hover:text-indigo-200 transition" data-lat="<?= htmlspecialchars((string) $r['latitude']) ?>" data-lng="<?= htmlspecialchars((string) $r['longitude']) ?>" data-origin-lat="<?= htmlspecialchars((string) ($r['provider_latitude'] ?? '')) ?>" data-origin-lng="<?= htmlspecialchars((string) ($r['provider_longitude'] ?? '')) ?>" data-label="<?= htmlspecialchars('طلب #' . ((int) ($r['id'] ?? 0)) . ' - ' . ($r['customer_name'] ?? 'عميل'), ENT_QUOTES) ?>">🗺️ فتح</button>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-600">غير متاح</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div id="mapModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 px-4">
            <div class="w-full max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
                <div class="mb-3 flex items-center justify-between">
                    <h4 id="mapModalTitle" class="text-sm font-bold text-white">موقع مباشر داخل النظام</h4>
                    <button id="closeMapModal" type="button" class="rounded-lg border border-slate-700 px-3 py-1 text-xs text-slate-300 hover:border-red-500 hover:text-red-400">إغلاق</button>
                </div>
                <div id="internalMap"></div>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        let hiddenDemoRows = false;
        let currentFilters = { users: 'all', requests: 'all' };

        const filterButtons = document.querySelectorAll('.filter-btn');
        const demoToggleBtn = document.getElementById('toggleDemoRows');

        function applyTableFilter(target) {
            const rows = document.querySelectorAll('tr[data-table="' + target + '"]');
            const wanted = currentFilters[target] || 'all';

            rows.forEach((row) => {
                const rowState = row.dataset.state || 'inactive';
                const isDemo = row.dataset.demo === '1';

                const statePass = wanted === 'all' || rowState === wanted;
                const demoPass = !hiddenDemoRows || !isDemo;
                row.style.display = statePass && demoPass ? '' : 'none';
            });
        }

        filterButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.target;
                const filter = btn.dataset.filter;
                currentFilters[target] = filter;

                document.querySelectorAll('.filter-btn[data-target="' + target + '"]').forEach((sibling) => {
                    sibling.className = 'filter-btn rounded-lg border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300';
                });

                const activeClass = target === 'users'
                    ? 'filter-btn rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs text-emerald-300'
                    : 'filter-btn rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs text-indigo-300';
                btn.className = activeClass;

                applyTableFilter(target);
            });
        });

        if (demoToggleBtn) {
            demoToggleBtn.addEventListener('click', () => {
                hiddenDemoRows = !hiddenDemoRows;
                demoToggleBtn.textContent = hiddenDemoRows ? 'إظهار العرض التجريبي' : 'تنظيف العرض التجريبي';
                applyTableFilter('users');
                applyTableFilter('requests');
            });
        }

        applyTableFilter('users');
        applyTableFilter('requests');

        const mapModal = document.getElementById('mapModal');
        const closeMapModalBtn = document.getElementById('closeMapModal');
        const mapModalTitle = document.getElementById('mapModalTitle');

        let internalMap = null;
        let internalMarker = null;
        let routeLine = null;
        let originMarker = null;

        function ensureMap() {
            if (internalMap) {
                return;
            }

            if (typeof L === 'undefined') {
                throw new Error('LeafletNotLoaded');
            }

            internalMap = L.map('internalMap').setView([33.3152, 44.3661], 12);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO'
            }).addTo(internalMap);
        }

        function openInternalMap(lat, lng, label, originLat, originLng) {
            mapModal.classList.remove('hidden');
            mapModal.classList.add('flex');

            try {
                ensureMap();
            } catch (e) {
                window.open('https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng), '_blank');
                return;
            }

            if (internalMarker) {
                internalMap.removeLayer(internalMarker);
            }

            if (originMarker) {
                internalMap.removeLayer(originMarker);
                originMarker = null;
            }

            if (routeLine) {
                internalMap.removeLayer(routeLine);
                routeLine = null;
            }

            internalMarker = L.marker([lat, lng]).addTo(internalMap).bindPopup(label).openPopup();

            if (originLat && originLng) {
                originMarker = L.marker([originLat, originLng]).addTo(internalMap).bindPopup('موقع مزود الخدمة');
                routeLine = L.polyline([[originLat, originLng], [lat, lng]], { color: '#f59e0b', weight: 5, opacity: 0.75 }).addTo(internalMap);
                internalMap.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
            } else {
                internalMap.flyTo([lat, lng], 15, { animate: true, duration: 1.1 });
            }

            mapModalTitle.textContent = label;

            setTimeout(() => {
                internalMap.invalidateSize();
            }, 120);
        }

        function closeInternalMap() {
            mapModal.classList.add('hidden');
            mapModal.classList.remove('flex');
        }

        document.addEventListener('click', (event) => {
            const btn = event.target.closest('.open-map-btn');
            if (!btn) {
                return;
            }

            const lat = parseFloat(btn.dataset.lat || '0');
            const lng = parseFloat(btn.dataset.lng || '0');
            const originLat = parseFloat(btn.dataset.originLat || '0');
            const originLng = parseFloat(btn.dataset.originLng || '0');
            const label = btn.dataset.label || 'الموقع';

            if (!lat || !lng) {
                return;
            }

            openInternalMap(lat, lng, label, originLat || null, originLng || null);
        });

        if (closeMapModalBtn) {
            closeMapModalBtn.addEventListener('click', closeInternalMap);
        }

        if (mapModal) {
            mapModal.addEventListener('click', (e) => {
                if (e.target === mapModal) {
                    closeInternalMap();
                }
            });
        }
    </script>
</body>
</html>
