<?php
session_start();
require 'config.php';

// تأمين وصول السائقين فقط (سائق كرين أو سائق كيا)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['driver', 'kia'])) {
    header('Location: choose_login.php');
    exit;
}

$driver_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$theme = [
    'driver' => [
        'title' => 'لوحة القيادة واستقبال الطلبات (كرين)',
        'accent' => 'amber',
        'badge' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'icon' => '🚜'
    ],
    'kia' => [
        'title' => 'لوحة القيادة واستقبال الطلبات (كيا حمل)',
        'accent' => 'sky',
        'badge' => 'bg-sky-500/10 text-sky-500 border-sky-500/20',
        'icon' => '🚚'
    ]
];

$curr_theme = $theme[$user_type];
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';

// تنظيف رسائل الجلسة فوراً بعد القراءة لمنع التكرار البصري
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// معالجة تحديث حالة الطلب النشط (إكمال الطلب أو إلغائه)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['status'] ?? '';

    if (in_array($new_status, ['completed', 'cancelled', 'pending'])) {
        try {
            if ($new_status === 'completed') {
                $pdo->beginTransaction();

                $request_stmt = $pdo->prepare("SELECT customer_id, charge_applied FROM service_requests WHERE id = ? AND driver_id = ? LIMIT 1");
                $request_stmt->execute([$request_id, $driver_id]);
                $request_row = $request_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$request_row) {
                    throw new RuntimeException('request_not_found');
                }

                if ((int) $request_row['charge_applied'] === 0) {
                    $balance_stmt = $pdo->prepare("SELECT balance_iqd FROM customers WHERE id = ? LIMIT 1");
                    $balance_stmt->execute([(int) $request_row['customer_id']]);
                    $customer_balance = (int) $balance_stmt->fetchColumn();

                    if ($customer_balance < SERVICE_FEE_IQD) {
                        throw new RuntimeException('insufficient_balance');
                    }

                    $charge_stmt = $pdo->prepare("UPDATE customers SET balance_iqd = balance_iqd - ? WHERE id = ?");
                    $charge_stmt->execute([SERVICE_FEE_IQD, (int) $request_row['customer_id']]);

                    $trx_stmt = $pdo->prepare("INSERT INTO balance_transactions (account_type, account_id, amount_iqd, transaction_kind, reason, related_request_id, admin_id) VALUES ('customer', ?, ?, 'debit', ?, ?, NULL)");
                    $trx_stmt->execute([(int) $request_row['customer_id'], SERVICE_FEE_IQD, 'رسوم خدمة مكتملة', $request_id]);
                }

                $stmt = $pdo->prepare("UPDATE service_requests SET status = 'completed', charge_applied = 1, driver_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND driver_id = ?");
                $stmt->execute([$driver_id, $request_id, $driver_id]);
                $pdo->commit();
                $success_msg = "🎉 تم إنهاء الطلب وخصم رسوم الخدمة بنجاح.";
            } else {
                $driver_field = ($new_status === 'pending') ? null : $driver_id;
                $stmt = $pdo->prepare("UPDATE service_requests SET status = ?, driver_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND driver_id = ?");
                $stmt->execute([$new_status, $driver_field, $request_id, $driver_id]);
                $success_msg = "🎉 تم تحديث حالة طلب السحب بنجاح.";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "❌ فشل تحديث حالة الطلب في قاعدة البيانات.";
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage() === 'insufficient_balance'
                ? "❌ لا يمكن إنهاء الطلب لأن رصيد العميل أقل من رسوم الخدمة."
                : "❌ تعذر العثور على الطلب المطلوب.";
        }
    }
}

// 1. جلب الطلب النشط الذي قبله السائق حالياً ولم يكتمل بعد
$active_request = null;
try {
    $active_stmt = $pdo->prepare("
        SELECT sr.*, c.fullname AS customer_name, c.phone AS customer_phone, c.province AS customer_province
        FROM service_requests sr
        JOIN customers c ON sr.customer_id = c.id
        WHERE sr.driver_id = ? AND sr.status = 'accepted'
        LIMIT 1
    ");
    $active_stmt->execute([$driver_id]);
    $active_request = $active_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $active_request = null;
}

// 2. جلب كافة الطلبات المعلقة في النظام لتظهر في ساحة الانتظار
$pending_requests = [];
try {
    $pending_stmt = $pdo->query("
        SELECT sr.*, c.fullname AS customer_name, c.phone AS customer_phone, c.province AS customer_province
        FROM service_requests sr
        JOIN customers c ON sr.customer_id = c.id
        WHERE sr.status = 'pending'
        ORDER BY sr.requested_at DESC
    ");
    $pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending_requests = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $curr_theme['title'] ?> - تطبيق كرين</title>
    <!-- Tailwind CSS الأنيق للواجهات التفاعلية -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet للخرائط وتحديد المسارات الجغرافية المباشرة -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        #miniMap { height: 280px; width: 100%; border-radius: 20px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between">

    <!-- شريط الملاحة والهيدر الذكي -->
    <header class="h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl relative">
        <div class="flex items-center gap-3">
            <span class="text-3xl animate-pulse"><?= $curr_theme['icon'] ?></span>
            <div>
                <h1 class="text-md sm:text-lg font-black text-white leading-none">بوابة سائقي <span class="text-amber-500">كرين العراق</span></h1>
                <p class="text-[10px] text-slate-400 mt-1">مرحباً بك يا بطل، <span class="text-amber-500 font-bold"><?= htmlspecialchars($_SESSION['fullname']) ?></span> 👋</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- حالة الاتصال والـ GPS الدائرية التفاعلية -->
            <div class="flex items-center gap-2 bg-slate-950/60 border border-slate-800 px-3 py-1.5 rounded-full">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span id="gpsIndicator" class="text-[9px] font-bold text-green-400">جاري فحص الـ GPS...</span>
            </div>

            <a href="dashboard.php" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300">
               🏠 الرئيسية
            </a>
            <a href="logout.php" class="p-2.5 bg-red-950/20 text-red-400 hover:bg-red-950/40 border border-red-900/30 rounded-xl transition text-xs font-bold">
               🚪 خروج
            </a>
        </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-8 space-y-6">

        <!-- لوحة مساعد المطور ومحاكاة الإحداثيات (تظهر ديناميكياً فقط عند وجود حظر للـ GPS) -->
        <section id="localGpsWarning" class="hidden bg-slate-900 border border-amber-500/20 rounded-3xl p-6 space-y-4 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="text-2xl">🛡️</span>
                <div class="space-y-1">
                    <h3 class="text-xs font-black text-amber-500 uppercase tracking-wide">مساعد التطوير المحلي ومحاكاة الـ GPS</h3>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        تمنع المتصفحات استخدام الـ GPS على الروابط غير الآمنة (مثل عناوين الـ IP المحلية <code class="bg-slate-950 px-1 py-0.5 rounded text-amber-500 font-mono">http://192.168.0.115</code>). لحل المشكلة، يمكنك اختيار أحد الحلين:
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[10px] pt-2">
                <!-- الخيار الأول: المحاكاة المباشرة -->
                <div class="bg-slate-950 p-4 rounded-2xl border border-slate-850 space-y-2">
                    <span class="text-green-400 font-bold block">⚡ الخيار الأول: تفعيل المحاكاة المباشرة (موصى به للاختبار السريع)</span>
                    <p class="text-slate-500 leading-relaxed">اختر منطقة في بغداد لتثبيت موقع السائق الافتراضي عليها وتحديث قاعدة البيانات فوراً:</p>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button onclick="activateSimulation(33.3252, 44.3461, 'المنصور')" class="py-2 px-2 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 rounded-lg border border-slate-800 transition font-bold">المنصور</button>
                        <button onclick="activateSimulation(33.3012, 44.4261, 'الكرادة')" class="py-2 px-2 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 rounded-lg border border-slate-800 transition font-bold">الكرادة</button>
                        <button onclick="activateSimulation(33.2752, 44.3761, 'الجادرية')" class="py-2 px-2 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 rounded-lg border border-slate-800 transition font-bold">الجادرية</button>
                        <button onclick="activateSimulation(33.3195, 44.3578, 'الحارثية')" class="py-2 px-2 bg-slate-900 hover:bg-amber-500 hover:text-slate-950 rounded-lg border border-slate-800 transition font-bold">الحارثية</button>
                    </div>
                    <p id="simAreaText" class="text-green-400 font-bold pt-1"></p>
                </div>

                <!-- الخيار الثاني: تفعيل العلم في المتصفح -->
                <div class="bg-slate-950 p-4 rounded-2xl border border-slate-850 space-y-1.5">
                    <span class="text-amber-500 font-bold block">⚙️ الخيار الثاني: السماح بالـ GPS الحقيقي عبر متصفح Chrome/Edge</span>
                    <p class="text-slate-500 leading-relaxed">إذا كنت تريد تشغيل الـ GPS الحقيقي من هاتفك المتصل بنفس الشبكة، اتبع الخطوات التالية في متصفحك:</p>
                    <ol class="list-decimal list-inside space-y-1 text-slate-400">
                        <li>افتح الرابط التالي في المتصفح: <code class="bg-slate-900 text-amber-500 px-1 rounded select-all font-mono">chrome://flags/#unsafely-treat-insecure-origin-as-secure</code></li>
                        <li>قم بتحويل الخيار إلى <b class="text-white">Enabled</b>.</li>
                        <li>في الحقل النصي، اكتب الرابط الخاص بسيرفرك: <code class="bg-slate-900 text-amber-500 px-1 rounded select-all font-mono">http://192.168.0.115</code></li>
                        <li>انقر على زر <b class="text-white">Relaunch</b> لإعادة تشغيل المتصفح، وسيعمل الـ GPS الحقيقي فوراً!</li>
                    </ol>
                </div>
            </div>
        </section>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- العمود الجانبي الأيمن: يعرض الطلب النشط الذي يقبله السائق حالياً وموقعه الجغرافي -->
            <section class="lg:col-span-5 w-full space-y-6">
                
                <!-- عرض رسائل التنبيهات الفورية الفخمة -->
                <?php if (!empty($success_msg)): ?>
                    <div class="p-4 rounded-xl text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20 text-center animate-bounce">
                        <?= $success_msg ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="p-4 rounded-xl text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20 text-center animate-pulse">
                        <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6 relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-<?= $curr_theme['accent'] ?>-500/5 rounded-full blur-2xl"></div>

                    <h2 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        🎯 المهمة النشطة حالياً
                    </h2>

                    <?php if ($active_request): ?>
                        <!-- بطاقة تفاصيل المهمة الحالية للعميل المتضرر -->
                        <div class="space-y-4 relative z-10">
                            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500">اسم العميل:</span>
                                    <span class="text-xs font-black text-white"><?= htmlspecialchars($active_request['customer_name']) ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500">محافظة الخدمة:</span>
                                    <span class="text-xs font-bold text-amber-500">📍 <?= htmlspecialchars($active_request['customer_province']) ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500">رقم الهاتف للعميل:</span>
                                    <a href="tel:<?= htmlspecialchars($active_request['customer_phone']) ?>" class="text-xs font-mono font-bold text-green-400 hover:underline">
                                        <?= htmlspecialchars($active_request['customer_phone']) ?> 📞
                                    </a>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                    <div class="rounded-xl bg-slate-900/80 border border-slate-800 p-2">
                                        <p class="text-[10px] text-slate-500">موقع العطل (العميل)</p>
                                        <p class="text-[11px] font-mono text-amber-400">
                                            <?= number_format((float) $active_request['latitude'], 5) ?>,
                                            <?= number_format((float) $active_request['longitude'], 5) ?>
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-slate-900/80 border border-slate-800 p-2">
                                        <p class="text-[10px] text-slate-500">موقعك الحالي</p>
                                        <p id="driverCoords" class="text-[11px] font-mono text-cyan-400">جاري تحديد موقعك...</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between rounded-xl bg-slate-900/80 border border-slate-800 p-2">
                                    <p id="distanceToCustomer" class="text-[11px] text-slate-300">المسافة للعميل: جارٍ الحساب...</p>
                                    <a id="navToCustomer" href="#" target="_blank" rel="noopener noreferrer" class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300">🧭 مسار Google</a>
                                </div>
                                <div class="flex items-center justify-end">
                                    <a id="openCurrentOnGoogle" href="#" target="_blank" rel="noopener noreferrer" class="text-[11px] font-bold text-cyan-400 hover:text-cyan-300">🗺️ موقعي على خرائط Google</a>
                                </div>
                            </div>

                            <!-- خريطة مصغرة لتتبع موقع العميل بدبوس جغرافي -->
                            <div class="border border-slate-800 rounded-2xl overflow-hidden relative">
                                <div id="miniMap"></div>
                            </div>

                            <!-- أزرار الإجراءات للتحكم بإنهاء أو إلغاء السحب -->
                            <div class="grid grid-cols-2 gap-3">
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="request_id" value="<?= $active_request['id'] ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" name="update_status" class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-black rounded-xl text-xs transition duration-150 transform active:scale-95 shadow-lg shadow-green-600/10">
                                        ✅ إنهاء الطلب
                                    </button>
                                </form>

                                <form method="POST" class="w-full">
                                    <input type="hidden" name="request_id" value="<?= $active_request['id'] ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" name="update_status" class="w-full py-3 px-4 bg-red-600/15 hover:bg-red-600/30 text-red-400 border border-red-500/20 font-bold rounded-xl text-xs transition duration-150 transform active:scale-95">
                                        ❌ إلغاء الطلب
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- رسالة في حال عدم وجود طلب نشط حالياً -->
                        <div class="text-center py-10 space-y-3">
                            <span class="text-4xl block animate-bounce">💤</span>
                            <p class="text-xs font-bold text-slate-400">لا توجد لديك مهام نشطة حالياً.</p>
                            <p class="text-[10px] text-slate-500 leading-relaxed max-w-xs mx-auto">عند قبول أي طلب من قائمة الانتظار المجاورة، ستظهر معلومات وموقع العميل هنا مباشرة لتبدأ بالتحرك لإنقاذه.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- العمود الأيسر: ساحة الطلبات المعلقة في النظام -->
            <section class="lg:col-span-7 w-full space-y-6">
                <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-sm font-black text-white">ساحة استقبال طلبات السحب المعلقة</h2>
                            <p class="text-[10px] text-slate-500 mt-1">تحديث حي ومستمر للطلبات القريبة منك في كافة المحافظات</p>
                        </div>
                        <span class="text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20 px-3 py-1 rounded-full">
                            <?= count($pending_requests) ?> طلب متاح حالياً
                        </span>
                    </div>

                    <?php if (!empty($pending_requests)): ?>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1">
                            <?php foreach ($pending_requests as $req): ?>
                                <div class="p-5 bg-slate-950 rounded-2xl border border-slate-800/80 hover:border-amber-500/40 transition duration-150 flex flex-col sm:flex-row justify-between sm:items-center gap-4 group">
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                            <h3 class="text-xs font-black text-white group-hover:text-amber-500 transition">طلب سحب عاجل #<?= htmlspecialchars($req['id']) ?></h3>
                                        </div>
                                        <p class="text-[10px] text-slate-400 leading-relaxed">
                                            👤 العميل: <span class="font-bold text-slate-200"><?= htmlspecialchars($req['customer_name']) ?></span> | 📍 المحافظة: <span class="font-bold text-amber-500"><?= htmlspecialchars($req['customer_province']) ?></span>
                                        </p>
                                        <span class="inline-block text-[9px] font-mono text-slate-500 bg-slate-900/60 px-2 py-0.5 rounded border border-slate-850">
                                            تاريخ الطلب: <?= htmlspecialchars($req['requested_at']) ?>
                                        </span>
                                    </div>

                                    <div>
                                        <form method="POST" action="accept_request.php">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="w-full sm:w-auto py-2 px-5 bg-amber-500 hover:bg-amber-600 active:scale-[0.98] text-slate-950 font-black rounded-xl text-xs transition duration-150 shadow-lg shadow-amber-500/5">
                                                🤝 قبول الطلب فوراً
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- حالة عدم وجود طلبات معلقة بالانتظار -->
                        <div class="p-10 text-center border border-dashed border-slate-800 rounded-2xl bg-slate-950/20 space-y-3">
                            <span class="text-4xl block">📭</span>
                            <h4 class="text-xs font-bold text-slate-400">الساحة هادئة ومستقرة حالياً</h4>
                            <p class="text-[10px] text-slate-500 leading-relaxed max-w-xs mx-auto">لا توجد طلبات سحب معلقة بانتظار استجابتك حالياً. يمكنك تفعيل حساب العميل لطلب ونش ورؤية الطلب فوراً هنا!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </main>

    <footer class="py-6 border-t border-slate-900 bg-slate-950 text-center">
        <p class="text-[10px] text-slate-500">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع الاتصالات والبيانات مشفرة ومحمية بالكامل.</p>
    </footer>

    <!-- Leaflet JS لإدارة الخرائط والمواقع الجغرافية -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        // إعداد وتفعيل نظام الخريطة المصغرة في حال وجود مهمة نشطة حالياً
        <?php if ($active_request): ?>
            var customerLat = <?= floatval($active_request['latitude']) ?>;
            var customerLng = <?= floatval($active_request['longitude']) ?>;

            var map = L.map('miniMap', { zoomControl: false }).setView([customerLat, customerLng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            var createCustomIcon = function(colorClass, emoji) {
                return L.divIcon({
                    html: `<div class="relative flex items-center justify-center w-12 h-12">
                             <div class="absolute inset-0 rounded-full ${colorClass} opacity-20 animate-ping"></div>
                             <div class="relative bg-slate-950 border-2 border-slate-850 rounded-full w-10 h-10 shadow-2xl flex items-center justify-center text-lg">
                               ${emoji}
                             </div>
                           </div>`,
                    className: '',
                    iconSize: [48, 48],
                    iconAnchor: [24, 24]
                });
            };

            var customerIcon = createCustomIcon('bg-red-500', '👤');
            var driverIcon = createCustomIcon('bg-cyan-500', '🚜');

            var customerMarker = L.marker([customerLat, customerLng], { icon: customerIcon })
             .addTo(map)
             .bindPopup("<div class='text-center p-1 font-semibold text-slate-900'>موقع العميل المتضرر</div>")
             .openPopup();

            var driverMarker = null;

            function haversineDistanceKm(lat1, lon1, lat2, lon2) {
                var R = 6371;
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLon = (lon2 - lon1) * Math.PI / 180;
                var a =
                    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            function refreshMissionMap(driverLat, driverLon) {
                if (!driverMarker) {
                    driverMarker = L.marker([driverLat, driverLon], { icon: driverIcon })
                        .addTo(map)
                        .bindPopup("<div class='text-center p-1 font-semibold text-slate-900'>موقعك الحالي</div>");
                } else {
                    driverMarker.setLatLng([driverLat, driverLon]);
                }

                var distKm = haversineDistanceKm(driverLat, driverLon, customerLat, customerLng);
                document.getElementById('driverCoords').innerText = driverLat.toFixed(5) + ", " + driverLon.toFixed(5);
                document.getElementById('distanceToCustomer').innerText = "المسافة للعميل: " + distKm.toFixed(2) + " كم";
                document.getElementById('navToCustomer').href =
                    "https://www.google.com/maps/dir/?api=1&origin=" + encodeURIComponent(driverLat + "," + driverLon) +
                    "&destination=" + encodeURIComponent(customerLat + "," + customerLng) + "&travelmode=driving";
                document.getElementById('openCurrentOnGoogle').href =
                    "https://www.google.com/maps?q=" + encodeURIComponent(driverLat + "," + driverLon);
            }
        <?php endif; ?>

        // =========================================================================
        // تفعيل وتحديث موقع السائق الجغرافي بالخلفية تلقائياً
        // =========================================================================
        let isSimulationMode = false;
        let simulatedLat = 33.3152;
        let simulatedLng = 44.3661;

        let watchId = null; // معرّف watchPosition للتوقف عند الحاجة
        let bestDriverFix = null;
        let lastSentAccuracy = null;
        let desiredDriverAccuracy = 35;

        function updateDriverLocation() {
            // في حال تم تفعيل وضع المحاكاة، نرسل الإحداثيات الوهمية الافتراضية
            if (isSimulationMode) {
                sendLocationUpdate(simulatedLat, simulatedLng, 0, "محاكاة نشطة");
                return;
            }

            if (!navigator.geolocation) {
                document.getElementById('localGpsWarning').classList.remove('hidden');
                return;
            }

            // إيقاف watchPosition السابق إن وجد قبل البدء بجديد
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }

            // watchPosition يتابع الموقع فور أي تغيير - أدق من getCurrentPosition
            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    var lat      = position.coords.latitude;
                    var lon      = position.coords.longitude;
                    var accuracy = Math.round(position.coords.accuracy); // دقة الموقع بالمتر

                    if (!bestDriverFix || accuracy < bestDriverFix.accuracy) {
                        bestDriverFix = { lat: lat, lon: lon, accuracy: accuracy };
                    }

                    var chosenFix = bestDriverFix || { lat: lat, lon: lon, accuracy: accuracy };

                    if (chosenFix.accuracy > 120) {
                        let ind = document.getElementById('gpsIndicator');
                        ind.innerText = "⚠️ دقة ضعيفة (" + chosenFix.accuracy + "م) - انتقل لمكان مفتوح";
                        ind.className = "text-[9px] font-bold text-red-400 animate-pulse";
                        return;
                    }

                    if (lastSentAccuracy === null || chosenFix.accuracy < lastSentAccuracy || chosenFix.accuracy <= desiredDriverAccuracy) {
                        lastSentAccuracy = chosenFix.accuracy;
                        sendLocationUpdate(chosenFix.lat, chosenFix.lon, chosenFix.accuracy, "GPS دقة " + chosenFix.accuracy + "م");
                    }
                    <?php if ($active_request): ?>
                    refreshMissionMap(chosenFix.lat, chosenFix.lon);
                    <?php endif; ?>
                },
                function(error) {
                    let msg = "❌ GPS: ";
                    switch(error.code) {
                        case 1: msg += "تم رفض الإذن"; break;
                        case 2: msg += "الموقع غير متاح"; break;
                        case 3: msg += "انتهت المهلة"; break;
                    }
                    document.getElementById('gpsIndicator').innerText = msg;
                    document.getElementById('gpsIndicator').className = "text-[9px] font-bold text-red-400 animate-pulse";
                    document.getElementById('localGpsWarning').classList.remove('hidden');
                },
                {
                    enableHighAccuracy: true, // يستخدم GPS الحقيقي وليس IP أو WiFi
                    timeout: 10000,           // 10 ثوانٍ كحد أقصى للانتظار
                    maximumAge: 0             // لا يقبل موقع محفوظ مسبقاً - دائماً يحصل على موقع جديد
                }
            );
        }

        // إرسال الإحداثيات للسيرفر عبر AJAX
        function sendLocationUpdate(lat, lon, accuracy, typeText) {
            fetch('update_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'latitude='  + encodeURIComponent(lat)
                    + '&longitude=' + encodeURIComponent(lon)
                    + '&accuracy='  + encodeURIComponent(accuracy)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let ind = document.getElementById('gpsIndicator');
                    ind.innerText = "📍 موقع نشط (" + typeText + ") - " + new Date().toLocaleTimeString('ar-IQ');
                    ind.className = accuracy > 0 && accuracy <= 50
                        ? "text-[9px] font-bold text-green-400"   // دقة عالية ≤ 50م
                        : "text-[9px] font-bold text-yellow-400"; // دقة متوسطة
                } else if (data.accuracy_rejected) {
                    let ind = document.getElementById('gpsIndicator');
                    ind.innerText = "⚠️ تم رفض تحديث الموقع (دقة " + Math.round(data.accuracy) + "م)";
                    ind.className = "text-[9px] font-bold text-red-400 animate-pulse";
                }
            })
            .catch(err => console.warn("⚠️ فشل إرسال الموقع:", err));
        }

        // تفعيل المحاكاة اليدوية للمناطق
        function activateSimulation(lat, lng, areaName) {
            // إيقاف watchPosition الحقيقي عند تفعيل المحاكاة
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            isSimulationMode = true;
            simulatedLat = lat;
            simulatedLng = lng;
            document.getElementById('simAreaText').innerText = "محاكاة في: " + areaName;
            updateDriverLocation();
            <?php if ($active_request): ?>
            refreshMissionMap(lat, lng);
            <?php endif; ?>
        }

        // تشغيل watchPosition فور تحميل الصفحة (يتحدث تلقائياً عند تغير الموقع)
        updateDriverLocation();
    </script>

</body>
</html>
