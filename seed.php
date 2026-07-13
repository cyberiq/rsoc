<?php
session_start();
require 'config.php';

$message = '';
$message_type = 'info';
$seeded = false;

// مصفوفة الأسماء العراقية الواقعية لتوليد البيانات بجمالية ومصداقية عالية
$iraqi_first_names = ['علي', 'أحمد', 'مصطفى', 'سجاد', 'عمر', 'حسين', 'كرار', 'حيدر', 'جعفر', 'سعد', 'ياسر', 'رائد', 'محمد', 'طه', 'عباس', 'حسن', 'أثير', 'منتظر', 'مقتدى', 'ذو الفقار'];
$iraqi_last_names = ['الخفاجي', 'الساعدي', 'الجبوري', 'الدليمي', 'التميمي', 'العبيدي', 'الزبيدي', 'الساعدي', 'الحمداني', 'الفتلاوي', 'العزاوي', 'البياتي', 'الربيعي', 'اللامي', 'الحلفي', 'الياسري', 'الأسدي'];

$provinces = ['بغداد', 'البصرة', 'الموصل', 'أربيل', 'النجف', 'كربلاء', 'بابل', 'صلاح الدين', 'الأنبار', 'ذي قار', 'الديوانية', 'واسط', 'ميسان', 'كركوك', 'دهوك', 'السليمانية'];

// إحداثيات مناطق بغداد الحيوية لنشر السائقين جغرافياً بدقة بالغة
$baghdad_locations = [
    ['area' => 'المنصور', 'lat' => 33.3252, 'lng' => 44.3461],
    ['area' => 'الكرادة', 'lat' => 33.3012, 'lng' => 44.4261],
    ['area' => 'الجادرية', 'lat' => 33.2752, 'lng' => 44.3761],
    ['area' => 'الحارثية', 'lat' => 33.3195, 'lng' => 44.3578],
    ['area' => 'زيونة', 'lat' => 33.3361, 'lng' => 44.4512],
    ['area' => 'الدورة', 'lat' => 33.2552, 'lng' => 44.3961],
    ['area' => 'الشعب', 'lat' => 33.3912, 'lng' => 44.4121],
    ['area' => 'العامرية', 'lat' => 33.2981, 'lng' => 44.2912],
    ['area' => 'القادسية', 'lat' => 33.2912, 'lng' => 44.3642],
    ['area' => 'اليرموك', 'lat' => 33.3051, 'lng' => 44.3312],
];

// إحداثيات المحافظات الأخرى لتوزيع عادل
$other_prov_locations = [
    'البصرة' => ['lat' => 30.5081, 'lng' => 47.7834],
    'أربيل' => ['lat' => 36.1912, 'lng' => 44.0091],
    'النجف' => ['lat' => 32.0252, 'lng' => 44.3412],
    'كربلاء' => ['lat' => 32.6161, 'lng' => 44.0252],
    'بابل' => ['lat' => 32.4612, 'lng' => 44.4212]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_seeder'])) {
    try {
        // تنظيف الجداول القديمة للحصول على تهيئة نظيفة 100% وتفادي أخطاء التكرار
        $pdo->exec("DELETE FROM customers;");
        $pdo->exec("DELETE FROM drivers;");
        $pdo->exec("DELETE FROM kias;");
        $pdo->exec("DELETE FROM driver_locations;");
        $pdo->exec("DELETE FROM service_requests;");
        $pdo->exec("DELETE FROM password_resets;");
        
        $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
        
        // 1. توليد حسابات المدراء (Admin)
        $stmt_cust = $pdo->prepare("INSERT INTO customers (fullname, phone, province, email, password, is_verified, role) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $stmt_cust->execute(['المدير العام للنظام', '07700000000', 'بغداد', 'admin@kreen.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        
        // 2. توليد حسابات عملاء مميزين واقعيين (Customers)
        $customers_seeded = [];
        for ($i = 1; $i <= 10; $i++) {
            $fname = $iraqi_first_names[array_rand($iraqi_first_names)];
            $lname = $iraqi_last_names[array_rand($iraqi_last_names)];
            $fullname = "$fname $lname";
            $phone = '077' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $prov = $provinces[array_rand($provinces)];
            $email = "customer{$i}@test.com";
            
            $stmt_cust->execute([$fullname, $phone, $prov, $email, $hashed_password, 'customer']);
            $customers_seeded[] = $pdo->lastInsertId();
        }

        // 3. توليد حسابات سائقي الكرين (Drivers) وتوزيع مواقعهم بالـ GPS
        $drivers_seeded = [];
        $stmt_driver = $pdo->prepare("INSERT INTO drivers (fullname, phone, province, email, password, wheel_number, wheel_type, wheel_color, wheel_model, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt_loc = $pdo->prepare("INSERT INTO driver_locations (driver_id, latitude, longitude) VALUES (?, ?, ?)");
        
        $wheel_types = ['كرين سطحة هيدروليكية', 'ونش تلسكوبي ثقيل', 'كرين شوكة هيدروليكي', 'ونش سحب معلق وسريع'];
        $wheel_colors = ['أصفر لامع', 'أحمر ناري', 'برتقالي مضيء', 'أزرق ملكي'];
        
        // توليد سائقي بغداد جغرافياً في الشوارع والمناطق المحددة مسبقاً
        foreach ($baghdad_locations as $idx => $loc) {
            $fname = $iraqi_first_names[array_rand($iraqi_first_names)];
            $lname = $iraqi_last_names[array_rand($iraqi_last_names)];
            $fullname = "$fname $lname (ونش {$loc['area']})";
            $phone = '078' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $email = "driver" . ($idx + 1) . "@test.com";
            $plate = rand(10000, 99999) . " بغداد / خصوصي";
            $type = $wheel_types[array_rand($wheel_types)];
            $color = $wheel_colors[array_rand($wheel_colors)];
            $model = rand(2018, 2025);
            
            $stmt_driver->execute([$fullname, $phone, 'بغداد', $email, $hashed_password, $plate, $type, $color, $model]);
            $drv_id = $pdo->lastInsertId();
            $drivers_seeded[] = $drv_id;
            
            // ربط موقعه الفعلي على الخريطة
            $stmt_loc->execute([$drv_id, $loc['lat'], $loc['lng']]);
        }

        // توليد سائقين في المحافظات الأخرى لتجربة التنقل الجغرافي
        $other_drv_idx = count($baghdad_locations) + 1;
        foreach ($other_prov_locations as $prov_name => $coords) {
            $fname = $iraqi_first_names[array_rand($iraqi_first_names)];
            $lname = $iraqi_last_names[array_rand($iraqi_last_names)];
            $fullname = "$fname $lname (ونش {$prov_name})";
            $phone = '075' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $email = "driver{$other_drv_idx}@test.com";
            $plate = rand(10000, 99999) . " $prov_name";
            $type = $wheel_types[array_rand($wheel_types)];
            $color = $wheel_colors[array_rand($wheel_colors)];
            $model = rand(2018, 2025);
            
            $stmt_driver->execute([$fullname, $phone, $prov_name, $email, $hashed_password, $plate, $type, $color, $model]);
            $drv_id = $pdo->lastInsertId();
            $drivers_seeded[] = $drv_id;
            
            $stmt_loc->execute([$drv_id, $coords['lat'], $coords['lng']]);
            $other_drv_idx++;
        }

        // 4. توليد سائقي الكيا حمل (Kias)
        $stmt_kia = $pdo->prepare("INSERT INTO kias (fullname, phone, province, email, password, car_number, car_model, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $kia_models = ['كيا بنجو 2021', 'كيا بورتير 2022', 'كيا بنجو مزدوجة الكابينة', 'كيا حمل 2.5 طن'];
        
        for ($i = 1; $i <= 5; $i++) {
            $fname = $iraqi_first_names[array_rand($iraqi_first_names)];
            $lname = $iraqi_last_names[array_rand($iraqi_last_names)];
            $fullname = "$fname $lname (كيا حمل)";
            $phone = '079' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $prov = $provinces[array_rand($provinces)];
            $email = "kia{$i}@test.com";
            $plate = rand(10000, 99999) . " $prov";
            $model = $kia_models[array_rand($kia_models)];
            
            $stmt_kia->execute([$fullname, $phone, $prov, $email, $hashed_password, $plate, $model]);
        }

        // 5. شحن طلبات خدمة تفاعلية بمختلف الحالات لتغذية لوحات التحليلات والتقارير
        $stmt_req = $pdo->prepare("INSERT INTO service_requests (customer_id, driver_id, latitude, longitude, status, requested_at) VALUES (?, ?, ?, ?, ?, ?)");
        
        // توليد طلبات مكتملة (Completed Requests)
        for ($i = 1; $i <= 15; $i++) {
            $cust_id = $customers_seeded[array_rand($customers_seeded)];
            $drv_id = $drivers_seeded[array_rand($drivers_seeded)];
            
            // توزيع عشوائي للإحداثيات حول بغداد
            $lat = 33.3152 + (rand(-50, 50) / 1000);
            $lng = 44.3661 + (rand(-50, 50) / 1000);
            
            // تواريخ تراجعية لإنتاج رسوم بيانية واقعية
            $req_date = date('Y-m-d H:i:s', strtotime("-" . rand(1, 25) . " days"));
            
            $stmt_req->execute([$cust_id, $drv_id, $lat, $lng, 'completed', $req_date]);
        }

        // طلب نشط مقبول حالياً لغرض التتبع الفوري
        $stmt_req->execute([$customers_seeded[0], $drivers_seeded[0], 33.3252, 44.3461, 'accepted', date('Y-m-d H:i:s')]);
        
        // طلب معلق حالياً في الساحة لكي يراه السائق فوراً
        $stmt_req->execute([$customers_seeded[1], null, 33.3012, 44.4261, 'pending', date('Y-m-d H:i:s')]);

        $message = "🎉 تم تنظيف وشحن قاعدة البيانات بنجاح! تم إنشاء حساب الإدارة، و 10 عملاء، و 14 سائق كرين موزعين جغرافياً، و 5 سائقي كيا، و 17 طلب خدمة جاهز للاختبار.";
        $message_type = 'success';
        $seeded = true;

    } catch (PDOException $e) {
        $message = "❌ فشل عملية التغذية البرمجية لقاعدة البيانات: " . $e->getMessage();
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغذية قاعدة بيانات كرين - لوحة المطور</title>
    <!-- استدعاء Tailwind CSS الأنيق -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 py-12 selection:bg-amber-500 selection:text-slate-950">

    <div class="max-w-2xl w-full bg-slate-900 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        
        <!-- توهج تزييني خلفي -->
        <div class="absolute -right-20 -bottom-20 w-72 h-72 bg-amber-500/5 rounded-full blur-3xl"></div>

        <div class="text-center mb-8 relative z-10">
            <span class="inline-block text-5xl mb-3 animate-pulse">⚡</span>
            <h1 class="text-2xl font-black text-white">مغذي قاعدة بيانات <span class="text-amber-500">كرين العراق</span></h1>
            <p class="text-xs text-slate-400 mt-2 leading-relaxed">أداة المطور لتهيئة النظام ونشر البيانات الجغرافية الحية والافتراضية لتسهيل اختبار التطبيق فوراً.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="p-4 rounded-2xl text-xs font-bold border leading-relaxed text-center mb-8 relative z-10
                <?= $message_type === 'success' ? 'bg-green-500/10 text-green-400 border-green-500/20' : '' ?>
                <?= $message_type === 'error' ? 'bg-red-500/10 text-red-400 border-red-500/20' : '' ?>
                <?= $message_type === 'warning' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '' ?>
                <?= $message_type === 'info' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : '' ?>
            ">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- كروت الحسابات الجاهزة لتسجيل الدخول السريع والاختبار -->
        <div class="bg-slate-950/80 rounded-2xl border border-slate-850 p-5 mb-8 space-y-4 relative z-10">
            <h3 class="text-xs font-black text-amber-500 uppercase tracking-wider flex items-center gap-1">
                <span>🔑</span> الحسابات التجريبية المولدة (كلمة المرور الموحدة: <span class="font-mono text-slate-200">123456</span>):
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-[11px] text-slate-400">
                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 space-y-1">
                    <span class="text-slate-500 font-bold block">👤 حساب العميل الموثق:</span>
                    <span class="text-slate-200 font-mono select-all">customer1@test.com</span>
                    <p class="text-[9px] text-slate-500">أول عميل مسجل لديه طلب تتبع نشط قيد المتابعة حالياً.</p>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 space-y-1">
                    <span class="text-amber-500 font-bold block">🚜 سائق كرين المنصور (متاح):</span>
                    <span class="text-slate-200 font-mono select-all">driver1@test.com</span>
                    <p class="text-[9px] text-slate-500">تم تحديد موقعه الفعلي على الخريطة في قلب المنصور.</p>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 space-y-1">
                    <span class="text-sky-400 font-bold block">🚚 سائق كيا حمل (نشط):</span>
                    <span class="text-slate-200 font-mono select-all">kia1@test.com</span>
                    <p class="text-[9px] text-slate-500">متاح لاستقبال ونقل الشحنات الخفيفة والمعدات.</p>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 space-y-1">
                    <span class="text-red-400 font-bold block">🛡️ مدير عام النظام (Admin):</span>
                    <span class="text-slate-200 font-mono select-all">admin@kreen.com</span>
                    <p class="text-[9px] text-red-500">كلمة المرور لهذا الحساب هي: <span class="font-bold underline select-all">admin123</span></p>
                </div>
            </div>
        </div>

        <div class="space-y-4 relative z-10">
            <form method="POST">
                <button type="submit" name="run_seeder"
                        class="w-full py-4 px-6 bg-amber-500 hover:bg-amber-600 active:scale-[0.98] text-slate-950 font-black rounded-2xl shadow-lg shadow-amber-500/10 text-center block transition-all duration-150 text-sm">
                    🚀 تهبيط وتنظيف قاعدة البيانات وشحن البيانات الفخمة
                </button>
            </form>

            <?php if ($seeded): ?>
                <a href="choose_login.php" 
                   class="w-full py-3 px-6 bg-slate-950 hover:bg-slate-850 text-slate-300 border border-slate-800 text-xs font-bold rounded-xl transition text-center block">
                    ⬅️ الدخول لبوابة تسجيل الدخول وتجربة البيانات
                </a>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8 pt-5 border-t border-slate-800/60 relative z-10">
            <a href="home.php" class="text-xs font-semibold text-slate-500 hover:text-slate-400 transition">
                🏠 العودة لصفحة التطبيق التعريفية
            </a>
        </div>

    </div>

</body>
</html>
