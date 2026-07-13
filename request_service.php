<?php
session_start();
require 'config.php';

// تأمين وصول العملاء فقط
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: choose_login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$message = '';
$message_type = 'error';
$customer_account = null;

try {
    $customer_stmt = $pdo->prepare("SELECT fullname, balance_iqd FROM customers WHERE id = ? LIMIT 1");
    $customer_stmt->execute([$customer_id]);
    $customer_account = $customer_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customer_account = ['fullname' => 'عميل', 'balance_iqd' => 0];
}

// -------------------------------------------------------------------------
// ميزة الفحص الفوري الصامت (AJAX Polling API)
// -------------------------------------------------------------------------
if (isset($_GET['check_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $status_stmt = $pdo->prepare("SELECT id, status, driver_id FROM service_requests WHERE customer_id = ? AND status IN ('pending', 'accepted') ORDER BY requested_at DESC LIMIT 1");
        $status_stmt->execute([$customer_id]);
        $req = $status_stmt->fetch();

        if ($req) {
            $drv = null;
            if ($req['status'] === 'accepted' && $req['driver_id']) {
                $drv_stmt = $pdo->prepare("SELECT fullname, phone, wheel_number, wheel_type, wheel_color, wheel_model FROM drivers WHERE id = ?");
                $drv_stmt->execute([$req['driver_id']]);
                $drv = $drv_stmt->fetch();
            }
            echo json_encode(['active' => true, 'status' => $req['status'], 'request_id' => $req['id'], 'driver' => $drv], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['active' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['active' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------------------
// معالجة إلغاء الطلب النشط
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    try {
        $cancel_stmt = $pdo->prepare("UPDATE service_requests SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND customer_id = ? AND status IN ('pending', 'accepted')");
        $cancel_stmt->execute([$request_id, $customer_id]);
        $message = "⚠️ تم إلغاء طلب السحب بنجاح وإخلاء السائقين.";
        $message_type = 'warning';
    } catch (PDOException $e) {
        $message = "❌ فشل إلغاء الطلب في قاعدة البيانات.";
    }
}

// -------------------------------------------------------------------------
// معالجة إنشاء طلب خدمة جديد
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $gps_accuracy = floatval($_POST['gps_accuracy'] ?? 0);

    if ($latitude == 0 || $longitude == 0) {
        $message = "❌ يرجى تحديد موقع عطل أو حادث عجلتك بدقة على الخريطة أولاً.";
    } elseif ($gps_accuracy > 120) {
        $message = "⚠️ دقة GPS منخفضة حالياً ({$gps_accuracy}م). يرجى إعادة تحديد الموقع من مكان مفتوح.";
    } elseif ((int) ($customer_account['balance_iqd'] ?? 0) < SERVICE_FEE_IQD) {
        $message = "❌ الرصيد غير كافٍ. تحتاج إلى " . number_format(SERVICE_FEE_IQD) . " د.ع على الأقل لطلب الخدمة.";
    } else {
        try {
            // التحقق من عدم وجود طلب فعال في ساحة الانتظار لمنع تكرار الطلبات وتشتيت السائقين
            $check_stmt = $pdo->prepare("SELECT id FROM service_requests WHERE customer_id = ? AND status IN ('pending', 'accepted') LIMIT 1");
            $check_stmt->execute([$customer_id]);
            
            if ($check_stmt->fetch()) {
                $message = "❌ عذراً، لديك طلب نشط بالفعل قيد المراجعة أو المتابعة الآن.";
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO service_requests (customer_id, latitude, longitude, status) VALUES (?, ?, ?, 'pending')");
                $insert_stmt->execute([$customer_id, $latitude, $longitude]);
                $message = "🎉 تم إرسال طلب الإنقاذ بنجاح! رادار المنصة يبحث عن أقرب كرين سحب متصل الآن.";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "❌ حدث خطأ داخلي أثناء معالجة وحفظ الطلب الجغرافي.";
        }
    }
}

// -------------------------------------------------------------------------
// جلب الطلب الفعال الحالي للعميل
// -------------------------------------------------------------------------
$active_request = null;
$driver = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE customer_id = ? AND status IN ('pending', 'accepted') ORDER BY requested_at DESC LIMIT 1");
    $stmt->execute([$customer_id]);
    $active_request = $stmt->fetch();

    if ($active_request && $active_request['status'] === 'accepted' && $active_request['driver_id']) {
        // جلب تفاصيل السائق المقبول للمهمة
        $driver_stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
        $driver_stmt->execute([$active_request['driver_id']]);
        $driver = $driver_stmt->fetch();
    }
} catch (PDOException $e) {
    $active_request = null;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب رافعة إنقاذ - تطبيق كرين</title>
    <!-- استدعاء أحدث ميزات Tailwind CSS وخرائط Leaflet التفاعلية -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        #requestMap { height: 340px; width: 100%; border-radius: 20px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

    <!-- شريط الملاحة العلوي -->
    <header class="h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl">
        <div class="flex items-center gap-3">
            <span class="text-3xl animate-pulse">🚨</span>
            <div>
                <h1 class="text-md sm:text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين العراق</span></h1>
                <p class="text-[10px] text-slate-400 mt-1">طلب فوري وتتبع حي ومباشر على الخريطة</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="customer_dashboard.php" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300">
               🏠 الخريطة الرئيسية
            </a>
            <a href="logout.php" class="p-2.5 bg-red-950/20 text-red-400 hover:bg-red-950/40 border border-red-900/30 rounded-xl transition text-xs font-bold">
               🚪 خروج
            </a>
        </div>
    </header>

    <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-8">
        
        <!-- عرض رسائل التنبيه والنجاح المخصصة -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl text-xs font-bold border leading-relaxed text-center animate-bounce
                <?= $message_type === 'success' ? 'bg-green-500/10 text-green-400 border-green-500/20' : '' ?>
                <?= $message_type === 'warning' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '' ?>
                <?= $message_type === 'error' ? 'bg-red-500/10 text-red-400 border-red-500/20' : '' ?>
            ">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- الحالة الأولى: لا يوجد طلب فعال حالياً (واجهة إنشاء طلب جديد) -->
        <!-- ========================================================================= -->
        <?php if (!$active_request): ?>
            <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6 sm:p-8 space-y-6 shadow-2xl relative overflow-hidden">
                <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-amber-500/5 rounded-full blur-3xl"></div>

                <div class="text-center relative z-10">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20 mb-3">
                       📍 تحديد موقع العطل بدقة
                    </span>
                    <h2 class="text-xl font-black text-white">تأكيد طلب كرين سحب وإنقاذ</h2>
                    <p class="text-xs text-slate-400 mt-1.5">قم بسحب الدبوس لتحديد مكانك الفعلي، ثم اضغط على زر الإرسال للمتابعة.</p>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-right">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-[11px] text-slate-500">رصيدك الحالي</p>
                            <p class="text-lg font-black text-emerald-400"><?= number_format((int) ($customer_account['balance_iqd'] ?? 0)) ?> د.ع</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-[11px] text-slate-500">رسوم الخدمة عند الإكمال</p>
                            <p class="text-lg font-black text-amber-400"><?= number_format(SERVICE_FEE_IQD) ?> د.ع</p>
                        </div>
                    </div>
                </div>

                <!-- خريطة تفاعلية لتحديد موقع العطل الجغرافي للعميل -->
                <div class="border border-slate-800 rounded-2xl overflow-hidden relative z-10">
                    <div id="requestMap"></div>
                    <button type="button" id="locateMeBtn" class="absolute bottom-4 left-4 z-[1000] p-3 bg-slate-950/90 text-white rounded-xl border border-slate-800 shadow-xl hover:bg-slate-900 transition text-xs font-bold flex items-center gap-1.5">
                        📍 حدد موقعي بالـ GPS
                    </button>
                </div>
                <p id="gpsAccuracyText" class="text-xs text-slate-400 text-center">دقة GPS: لم يتم القياس بعد</p>

                <!-- نموذج الإرسال لقاعدة البيانات -->
                <form method="POST" class="space-y-4 relative z-10">
                    <input type="hidden" name="latitude" id="latInput" value="">
                    <input type="hidden" name="longitude" id="lngInput" value="">
                    <input type="hidden" name="gps_accuracy" id="accuracyInput" value="0">

                    <a id="googlePreviewLink" href="#" target="_blank" rel="noopener noreferrer" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 bg-slate-950 border border-slate-800 text-slate-200 font-bold rounded-xl text-xs hover:border-indigo-500 hover:text-indigo-300 transition">
                        🗺️ عرض الموقع على خرائط Google
                    </a>

                    <button id="submitRequestBtn" type="submit" name="create_request" 
                            class="w-full py-4 px-4 bg-amber-500 hover:bg-amber-600 active:scale-[0.98] text-slate-950 font-black rounded-xl shadow-lg shadow-amber-500/20 transition-all duration-150 text-xs flex items-center justify-center gap-2">
                        🚜 تأكيد موقعي وإرسال طلب كرين سحب عاجل
                    </button>
                </form>
            </div>

        <!-- ========================================================================= -->
        <!-- الحالة الثانية: يوجد طلب فعال وهو قيد الانتظار (رادار البحث المتطور) -->
        <!-- ========================================================================= -->
        <?php elseif ($active_request['status'] === 'pending'): ?>
            <div class="bg-slate-900 rounded-3xl border border-slate-800 p-8 text-center space-y-8 shadow-2xl relative overflow-hidden">
                <!-- تأثير الرادار والنبض بالخلفية -->
                <div class="absolute inset-0 bg-grid-white/[0.01]"></div>
                
                <div class="relative flex items-center justify-center h-48">
                    <!-- حلقات نبض الرادار المتراكبة والمتحركة -->
                    <div class="absolute w-44 h-44 rounded-full border border-amber-500/20 animate-ping"></div>
                    <div class="absolute w-32 h-32 rounded-full border border-amber-500/30 animate-pulse"></div>
                    <div class="absolute w-20 h-20 rounded-full bg-amber-500/10 border border-amber-500/40 flex items-center justify-center text-4xl">
                        🚜
                    </div>
                </div>

                <div class="space-y-2 relative z-10">
                    <h2 class="text-lg font-black text-white animate-pulse">جاري البحث عن أقرب سائق كرين...</h2>
                    <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
                        تم تعميم طلبك على كافة السائقين المتصلين بالقرب منك. ستقوم لوحة التحكم بتحديث هذه الشاشة فوراً بمجرد قبول الطلب من أحد الأبطال.
                    </p>
                </div>

                <form method="POST" class="relative z-10">
                    <input type="hidden" name="request_id" value="<?= $active_request['id'] ?>">
                    <button type="submit" name="cancel_request" class="py-3 px-6 bg-red-600/15 hover:bg-red-600/35 text-red-400 border border-red-500/20 font-bold rounded-xl text-xs transition duration-150 transform active:scale-95">
                        ❌ إلغاء طلب السحب والإنقاذ
                    </button>
                </form>
            </div>

        <!-- ========================================================================= -->
        <!-- الحالة الثالثة: تم قبول الطلب بنجاح (تفاصيل السائق والاتصال الفوري) -->
        <!-- ========================================================================= -->
        <?php elseif ($active_request['status'] === 'accepted' && $driver): ?>
            <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6 sm:p-8 space-y-6 shadow-2xl relative overflow-hidden">
                <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-green-500/5 rounded-full blur-3xl"></div>

                <div class="text-center">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-full bg-green-500/10 text-green-400 border border-green-500/20 mb-3 animate-bounce">
                       🤝 تم قبول طلب السحب والإنقاذ بنجاح!
                    </span>
                    <h2 class="text-lg font-black text-white">كرين السحب في طريقه إليك الآن</h2>
                    <p class="text-xs text-slate-400 mt-1">يرجى مطابقة رقم اللوحة والتواصل مع السائق لتسهيل الوصول.</p>
                </div>

                <!-- بطاقة تفاصيل بطل السحب المعتمد -->
                <div class="p-5 bg-slate-950 rounded-2xl border border-slate-850 flex flex-col sm:flex-row items-center gap-4">
                    <div class="w-16 h-16 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center text-3xl">
                        👤
                    </div>
                    
                    <div class="flex-1 text-center sm:text-right space-y-1">
                        <h3 class="text-sm font-black text-white"><?= htmlspecialchars($driver['fullname']) ?></h3>
                        <p class="text-xs text-amber-500 font-bold">🚜 نوع الكرين: <span class="text-slate-300"><?= htmlspecialchars($driver['wheel_type'] ?? 'سطحة هيدروليكية') ?></span></p>
                        <p class="text-[10px] text-slate-500 font-mono">اللوحة المرورية: <?= htmlspecialchars($driver['wheel_number'] ?? 'بغداد / خصوصي') ?> | اللون: <?= htmlspecialchars($driver['wheel_color'] ?? 'أصفر لامع') ?></p>
                    </div>

                    <div class="w-full sm:w-auto">
                        <a href="tel:<?= htmlspecialchars($driver['phone']) ?>" class="w-full py-3 px-5 bg-green-600 hover:bg-green-700 text-white text-xs font-black rounded-xl transition duration-150 flex items-center justify-center gap-1.5 shadow-lg shadow-green-600/10">
                            📞 اتصال بالسائق
                        </a>
                    </div>
                </div>

                <!-- خريطة مصغرة توضح موقع العطل الجغرافي للعميل -->
                <div class="border border-slate-800 rounded-2xl overflow-hidden relative">
                    <div id="requestMap"></div>
                </div>

                <a href="https://www.google.com/maps?q=<?= floatval($active_request['latitude']) ?>,<?= floatval($active_request['longitude']) ?>" target="_blank" rel="noopener noreferrer" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 bg-slate-950 border border-slate-800 text-slate-200 font-bold rounded-xl text-xs hover:border-indigo-500 hover:text-indigo-300 transition">
                    🗺️ فتح موقع العطل على خرائط Google
                </a>

                <form method="POST" class="text-center">
                    <input type="hidden" name="request_id" value="<?= $active_request['id'] ?>">
                    <button type="submit" name="cancel_request" class="py-2.5 px-6 bg-slate-950 hover:bg-red-600/20 text-slate-500 hover:text-red-400 border border-slate-850 hover:border-red-500/20 font-bold rounded-xl text-xs transition duration-150 transform active:scale-95">
                        ❌ إلغاء الطلب والتراجع
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <footer class="py-6 border-t border-slate-900 bg-slate-950 text-center">
        <p class="text-[10px] text-slate-500">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. جميع الجلسات والبيانات مشفرة ومؤمنة بالكامل.</p>
    </footer>

    <!-- Leaflet JS لإدارة الخرائط والمواقع -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        // إعدادات الخريطة التفاعلية بحسب حالة الطلب النشط
        <?php if (!$active_request): ?>
            var defaultLat = 33.3152; // بغداد افتراضياً
            var defaultLng = 44.3661;

            var map = L.map('requestMap', { zoomControl: false }).setView([defaultLat, defaultLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            var pinIcon = L.divIcon({
                html: `<div class="relative flex items-center justify-center w-12 h-12">
                         <div class="absolute inset-0 rounded-full bg-amber-500 opacity-20 animate-ping"></div>
                         <div class="relative bg-slate-950 border-2 border-slate-800 rounded-full w-10 h-10 shadow-2xl flex items-center justify-center text-lg">
                           🚨
                         </div>
                       </div>`,
                className: '',
                iconSize: [48, 48],
                iconAnchor: [24, 24]
            });

            var marker = L.marker([defaultLat, defaultLng], { icon: pinIcon, draggable: true }).addTo(map);
            var locateButton = document.getElementById('locateMeBtn');
            var preciseGpsWatchId = null;
            var preciseGpsTimeoutId = null;
            var bestGpsFix = null;
            var desiredAccuracy = 35;

            // تحديث الإحداثيات المدخلة فورياً في حقول النموذج الخفية عند سحب الدبوس
            function updateInputs(lat, lng) {
                document.getElementById('latInput').value = lat;
                document.getElementById('lngInput').value = lng;
                var googleLink = document.getElementById('googlePreviewLink');
                if (googleLink) {
                    googleLink.href = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng);
                }
            }

            function updateAccuracyState(accuracy) {
                var accText = document.getElementById('gpsAccuracyText');
                var accInput = document.getElementById('accuracyInput');
                var submitBtn = document.getElementById('submitRequestBtn');

                if (accInput) {
                    accInput.value = accuracy > 0 ? accuracy : 0;
                }

                if (!accText || !submitBtn) {
                    return;
                }

                if (accuracy <= 0) {
                    accText.innerText = 'دقة GPS: تحديد يدوي';
                    accText.className = 'text-xs text-slate-400 text-center';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                    return;
                }

                if (accuracy <= 20) {
                    accText.innerText = 'دقة GPS ممتازة جداً: ' + Math.round(accuracy) + 'م';
                    accText.className = 'text-xs text-emerald-400 text-center font-bold';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                } else if (accuracy <= 50) {
                    accText.innerText = 'دقة GPS ممتازة: ' + Math.round(accuracy) + 'م';
                    accText.className = 'text-xs text-green-400 text-center font-bold';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                } else if (accuracy > 120) {
                    accText.innerText = 'دقة GPS منخفضة: ' + Math.round(accuracy) + 'م (المطلوب <= 120م)';
                    accText.className = 'text-xs text-red-400 text-center font-bold';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
                } else {
                    accText.innerText = 'دقة GPS متوسطة: ' + Math.round(accuracy) + 'م';
                    accText.className = 'text-xs text-yellow-400 text-center font-bold';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            }

            function stopPreciseLocation() {
                if (preciseGpsWatchId !== null) {
                    navigator.geolocation.clearWatch(preciseGpsWatchId);
                    preciseGpsWatchId = null;
                }

                if (preciseGpsTimeoutId !== null) {
                    clearTimeout(preciseGpsTimeoutId);
                    preciseGpsTimeoutId = null;
                }
            }

            function setLocateButtonState(label, disabled) {
                if (!locateButton) {
                    return;
                }

                locateButton.innerText = label;
                locateButton.disabled = disabled;
                locateButton.classList.toggle('opacity-60', disabled);
                locateButton.classList.toggle('cursor-not-allowed', disabled);
            }

            function finalizePreciseLocation(fix, bestAvailableOnly) {
                stopPreciseLocation();

                if (!fix) {
                    setLocateButtonState('📍 حدد موقعي بالـ GPS', false);
                    updateAccuracyState(999);
                    return;
                }

                map.flyTo([fix.lat, fix.lng], 17, { animate: true, duration: 1.4 });
                marker.setLatLng([fix.lat, fix.lng]);
                updateInputs(fix.lat, fix.lng);
                updateAccuracyState(fix.accuracy || 0);

                if (bestAvailableOnly && fix.accuracy > desiredAccuracy) {
                    document.getElementById('gpsAccuracyText').innerText = 'تم اختيار أفضل دقة متاحة: ' + Math.round(fix.accuracy) + 'م';
                    document.getElementById('gpsAccuracyText').className = 'text-xs text-yellow-400 text-center font-bold';
                }

                setLocateButtonState('📍 إعادة تحديد موقعي', false);
            }

            function startPreciseLocationCapture() {
                if (!navigator.geolocation) {
                    return;
                }

                stopPreciseLocation();
                bestGpsFix = null;
                setLocateButtonState('⏳ جارٍ تثبيت أفضل دقة...', true);
                document.getElementById('gpsAccuracyText').innerText = 'جارٍ جمع قراءات GPS عالية الدقة...';
                document.getElementById('gpsAccuracyText').className = 'text-xs text-amber-400 text-center font-bold';

                preciseGpsWatchId = navigator.geolocation.watchPosition(
                    function(position) {
                        var fix = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            accuracy: position.coords.accuracy || 0
                        };

                        if (!bestGpsFix || fix.accuracy < bestGpsFix.accuracy) {
                            bestGpsFix = fix;
                            marker.setLatLng([fix.lat, fix.lng]);
                            updateInputs(fix.lat, fix.lng);
                            updateAccuracyState(fix.accuracy);
                        }

                        if (fix.accuracy > 0 && fix.accuracy <= desiredAccuracy) {
                            finalizePreciseLocation(fix, false);
                        }
                    },
                    function(error) {
                        stopPreciseLocation();
                        setLocateButtonState('📍 حدد موقعي بالـ GPS', false);
                        document.getElementById('gpsAccuracyText').innerText = 'تعذر الحصول على GPS دقيق: ' + error.message;
                        document.getElementById('gpsAccuracyText').className = 'text-xs text-red-400 text-center font-bold';
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
                );

                preciseGpsTimeoutId = setTimeout(function() {
                    finalizePreciseLocation(bestGpsFix, true);
                }, 18000);
            }

            updateInputs(defaultLat, defaultLng);
            updateAccuracyState(0);

            marker.on('dragend', function(e) {
                var pos = marker.getLatLng();
                updateInputs(pos.lat, pos.lng);
                updateAccuracyState(0);
            });

            // تفعيل نظام تحديد الموقع الفوري بالـ GPS للمتصفح عند النقر
            document.getElementById('locateMeBtn').addEventListener('click', function() {
                if (navigator.geolocation) {
                    startPreciseLocationCapture();
                }
            });

        <?php elseif ($active_request['status'] === 'accepted'): ?>
            // رسم الخريطة التفصيلية للطلب المقبول لعرض موقع العقد
            var customerLat = <?= floatval($active_request['latitude']) ?>;
            var customerLng = <?= floatval($active_request['longitude']) ?>;

            var map = L.map('requestMap', { zoomControl: false }).setView([customerLat, customerLng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            var activeIcon = L.divIcon({
                html: `<div class="relative flex items-center justify-center w-12 h-12">
                         <div class="absolute inset-0 rounded-full bg-green-500 opacity-20 animate-ping"></div>
                         <div class="relative bg-slate-950 border-2 border-slate-850 rounded-full w-10 h-10 shadow-2xl flex items-center justify-center text-lg">
                           🚨
                         </div>
                       </div>`,
                className: '',
                iconSize: [48, 48],
                iconAnchor: [24, 24]
            });

            L.marker([customerLat, customerLng], { icon: activeIcon }).addTo(map)
             .bindPopup("<div class='text-center p-1 font-bold text-slate-900'>موقع تعطل عجلتك الفعلي</div>")
             .openPopup();
        <?php endif; ?>

        // =========================================================================
        // نظام الفحص الفوري الصامت (AJAX Polling) للتحديث التلقائي كل 6 ثوانٍ
        // =========================================================================
        <?php if ($active_request): ?>
            function checkRequestStatus() {
                fetch('request_service.php?check_status=1')
                .then(response => response.json())
                .then(data => {
                    // إذا لم يعد الطلب نشطاً (تم إنهاؤه أو إلغاؤه من جهة أخرى) أو تحول من pending إلى accepted
                    if (!data.active) {
                        window.location.reload();
                    } else if (data.status === 'accepted' && '<?= $active_request['status'] ?>' === 'pending') {
                        // إعادة تحميل الصفحة فوراً لتحديث الواجهة وعرض تفاصيل السائق المقبول
                        window.location.reload();
                    }
                })
                .catch(err => console.warn("Polling Check Failed: ", err));
            }
            setInterval(checkRequestStatus, 6000); // فحص مستمر كل 6 ثوانٍ
        <?php endif; ?>
    </script>

</body>
</html>
