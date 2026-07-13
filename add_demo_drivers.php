<?php
session_start();
require 'config.php';

// مصفوفة السائقين التجريبيين الفاخرة مع توزيعهم على مناطق حقيقية في بغداد لإضفاء لمسة واقعية على الخريطة
$demo_drivers = [
    [
        'fullname' => 'مصطفى الجبوري (كرين سطحة)',
        'phone' => '07711223344',
        'province' => 'بغداد',
        'email' => 'driver1@test.com',
        'type' => 'driver', // سائق كرين
        'wheel_number' => '54321 بغداد',
        'wheel_type' => 'كرين سطحة هيدروليك',
        'wheel_color' => 'أصفر',
        'wheel_model' => '2022',
        'area' => 'المنصور',
        'lat' => 33.3252, // إحداثيات منطقة المنصور
        'lng' => 44.3461
    ],
    [
        'fullname' => 'عمر الخفاجي (ونش إنقاذ)',
        'phone' => '07722334455',
        'province' => 'بغداد',
        'email' => 'driver2@test.com',
        'type' => 'driver', // سائق كرين
        'wheel_number' => '98765 بغداد',
        'wheel_type' => 'ونش سحب تلسكوبي',
        'wheel_color' => 'أحمر وبورسلين',
        'wheel_model' => '2020',
        'area' => 'الكرادة',
        'lat' => 33.3012, // إحداثيات الكرادة
        'lng' => 44.4261
    ],
    [
        'fullname' => 'كرار الساعدي (كيا حمل)',
        'phone' => '07733445566',
        'province' => 'بغداد',
        'email' => 'kia1@test.com',
        'type' => 'kia', // سائق كيا
        'car_number' => '12123 بغداد',
        'car_model' => 'كيا بنجو 2021',
        'area' => 'الجادرية',
        'lat' => 33.2752, // إحداثيات الجادرية قرب الجامعة
        'lng' => 44.3761
    ],
    [
        'fullname' => 'حسين الزبيدي (كيا مسطحة)',
        'phone' => '07744556677',
        'province' => 'بغداد',
        'email' => 'kia2@test.com',
        'type' => 'kia', // سائق كيا
        'car_number' => '84849 بغداد',
        'car_model' => 'كيا بورتير 2019',
        'area' => 'الدورة',
        'lat' => 33.2552, // إحداثيات منطقة الدورة
        'lng' => 44.3961
    ]
];

$success_count = 0;
$skipped_count = 0;
$log = [];
$is_submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($is_submitted) {
    $hashed_password = password_hash('123456', PASSWORD_DEFAULT); // كلمة مرور موحدة وسهلة للفحص والتجريب

    foreach ($demo_drivers as $driver) {
        $table = ($driver['type'] === 'driver') ? 'drivers' : 'kias';
        
        // التحقق مما إذا كان البريد الإلكتروني مسجلاً مسبقاً لمنع تكرار البيانات
        $check = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
        $check->execute([$driver['email']]);
        
        if ($check->fetch()) {
            $skipped_count++;
            $log[] = [
                'name' => $driver['fullname'],
                'email' => $driver['email'],
                'status' => 'skipped',
                'msg' => '⚠️ السائق مضاف مسبقاً في النظام.'
            ];
            continue;
        }

        try {
            // إدراج السائق في جدول السائقين المناسب كحساب مفعل وجاهز للعمل مباشرة (is_verified = 1)
            if ($driver['type'] === 'driver') {
                $stmt = $pdo->prepare("INSERT INTO drivers 
                    (fullname, phone, province, email, password, wheel_number, wheel_type, wheel_color, wheel_model, verification_code, is_verified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 1)");
                $stmt->execute([
                    $driver['fullname'], $driver['phone'], $driver['province'], $driver['email'], $hashed_password,
                    $driver['wheel_number'], $driver['wheel_type'], $driver['wheel_color'], $driver['wheel_model']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO kias 
                    (fullname, phone, province, email, password, car_number, car_model, verification_code, is_verified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 1)");
                $stmt->execute([
                    $driver['fullname'], $driver['phone'], $driver['province'], $driver['email'], $hashed_password,
                    $driver['car_number'], $driver['car_model']
                ]);
            }

            $driver_id = $pdo->lastInsertId();

            // إدراج موقع السائق الجغرافي لكي يظهر فوراً على لوحة تحكم الخريطة للعميل
            // ملاحظة: جدول driver_locations في مشروعك يربط بمفتاح خارجي driver_id، ويصلح لكلا نوعي المركبات لإظهارهما
            $loc_stmt = $pdo->prepare("INSERT INTO driver_locations (driver_id, latitude, longitude) VALUES (?, ?, ?)");
            $loc_stmt->execute([$driver_id, $driver['lat'], $driver['lng']]);

            $success_count++;
            $log[] = [
                'name' => $driver['fullname'],
                'email' => $driver['email'],
                'status' => 'success',
                'msg' => "✅ تم الإنشاء وتحديد موقعه في منطقة ({$driver['area']}) بنجاح!"
            ];

        } catch (PDOException $e) {
            $log[] = [
                'name' => $driver['fullname'],
                'email' => $driver['email'],
                'status' => 'error',
                'msg' => '❌ خطأ أثناء الكتابة في قاعدة البيانات: ' . $e->getMessage()
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أداة توليد السائقين التجريبيين - تطبيق كرين</title>
    <!-- استدعاء Tailwind CSS الأنيق -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen py-12 px-4">

    <div class="max-w-3xl w-full bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
        
        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
            <span class="text-3xl">🚜</span>
            <span class="inline-block px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                لوحة التحكم الإدارية
            </span>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">توليد سائقين تجريبيين (بشكل واقعي)</h1>
            <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                تقوم هذه الأداة بإنشاء وتوزيع حسابات سائقي كرين وسائقي كيا افتراضيين في أهم مناطق العاصمة بغداد لدعم اختبارات الخرائط ونظام تحديد المواقع GPS تلقائياً.
            </p>
        </div>

        <!-- عرض نتائج المعالجة إن وجدت -->
        <?php if ($is_submitted): ?>
            <div class="mb-8 p-5 bg-slate-50 rounded-2xl border border-gray-200">
                <h3 class="font-bold text-slate-700 mb-3 flex items-center gap-2">
                    📊 ملخص التوليد والتشغيل:
                </h3>
                <div class="grid grid-cols-2 gap-4 mb-4 text-center">
                    <div class="bg-green-50 border border-green-100 p-3 rounded-xl">
                        <span class="block text-2xl font-black text-green-600"><?= $success_count ?></span>
                        <span class="text-xs text-green-700 font-semibold">حسابات جديدة جاهزة</span>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 p-3 rounded-xl">
                        <span class="block text-2xl font-black text-amber-600"><?= $skipped_count ?></span>
                        <span class="text-xs text-amber-700 font-semibold">حسابات مكررة تم تخطيها</span>
                    </div>
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <?php foreach ($log as $item): ?>
                        <div class="p-2.5 rounded-lg text-xs font-semibold flex justify-between items-center bg-white border border-gray-100">
                            <span class="text-slate-800"><?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['email']) ?>)</span>
                            <span><?= $item['msg'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- استعراض قائمة السائقين المقترح توليدهم -->
        <div class="mb-8 border border-gray-100 rounded-2xl overflow-hidden">
            <div class="bg-slate-50 p-4 border-b border-gray-100">
                <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">السائقون المقترح إدراجهم في قاعدة البيانات:</h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                <?php foreach ($demo_drivers as $d): ?>
                    <div class="p-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2 text-sm">
                        <div>
                            <p class="font-bold text-slate-800"><?= htmlspecialchars($d['fullname']) ?></p>
                            <p class="text-xs text-gray-400">البريد: <?= htmlspecialchars($d['email']) ?> | هاتف: <?= htmlspecialchars($d['phone']) ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border bg-gray-100 text-gray-700">
                                📍 <?= $d['area'] ?>
                            </span>
                            <?php if ($d['type'] === 'driver'): ?>
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border bg-amber-50 text-amber-800 border-amber-100">
                                    سائق كرين
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border bg-sky-50 text-sky-800 border-sky-100">
                                    سائق كيا
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl mb-8 flex items-start gap-3">
            <span class="text-lg">💡</span>
            <div>
                <p class="text-xs font-bold text-blue-800">معلومات تسجيل الدخول السريع للاختبار:</p>
                <p class="text-[11px] text-blue-700 mt-1 leading-relaxed">
                    يمكنك تسجيل الدخول بأي من الحسابات المذكورة أعلاه فور تفعيلها، حيث تم ضبط كلمة المرور الموحدة لها لتكون: <b class="font-bold underline text-blue-900">123456</b>.
                </p>
            </div>
        </div>

        <!-- زر التوليد التفاعلي الممتع -->
        <form method="POST">
            <button type="submit" class="w-full py-4 px-6 bg-amber-500 hover:bg-amber-600 active:scale-[0.99] text-white font-bold rounded-2xl shadow-lg shadow-amber-500/20 transition-all duration-150 flex items-center justify-center gap-2">
                🚀 ابدأ تشغيل وتوليد السائقين التجريبيين الآن
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="customer_dashboard.php" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition">
                ⬅ العودة لخريطة لوحة العميل
            </a>
        </div>

    </div>

</body>
</html>
