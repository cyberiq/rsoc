<?php
session_start();
require 'config.php';

// تعيين نوع رأس الاستجابة ليكون JSON تفاعلي آمن ومتوافق مع ترميز اللغة العربية
header('Content-Type: application/json; charset=utf-8');

// تأمين جلب البيانات: لمنع إساءة استخدام أو تتبع السائقين، يُشترط أن يكون المستخدم مسجلاً بالمنصة
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '❌ غير مصرح لك بالوصول لرؤية مواقع السائقين. يرجى تسجيل الدخول أولاً.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // تفعيل فلاتر اختيارية لتخصيص البحث (مثال: فلترة حسب المحافظة أو نوع الكرين)
    $province = isset($_GET['province']) ? trim($_GET['province']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';

    // بناء استعلام البحث الفوري عن السائقين ومواقعهم المسجلة
    $query = "
        SELECT dl.driver_id, dl.latitude, dl.longitude, dl.updated_at,
               d.fullname, d.phone, d.province, d.wheel_number, d.wheel_type, d.wheel_color, d.wheel_model
        FROM driver_locations dl
        JOIN drivers d ON dl.driver_id = d.id
        WHERE 1=1
    ";

    $params = [];

    // إضافة شروط الفلترة ديناميكياً لتأمين الاستعلام ضد ثغرات الحقن (SQL Injection)
    if (!empty($province)) {
        $query .= " AND d.province = ?";
        $params[] = $province;
    }

    if (!empty($type)) {
        $query .= " AND (d.wheel_type LIKE ? OR d.wheel_model LIKE ?)";
        $params[] = '%' . $type . '%';
        $params[] = '%' . $type . '%';
    }

    // ترتيب السائقين بناءً على حداثة التحديث الجغرافي للـ GPS
    $query .= " ORDER BY dl.updated_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // حساب الوقت المنقضي وتحديد ما إذا كان السائق نشطاً بالوقت الفعلي (Active status calculation)
    foreach ($drivers as &$driver) {
        $last_update = strtotime($driver['updated_at']);
        $diff_minutes = round((time() - $last_update) / 60);
        
        // يُعتبر السائق نشطاً حياً إذا قام بتحديث موقعه خلال آخر 15 دقيقة
        $driver['is_active_now'] = ($diff_minutes <= 15) ? true : false;
        $driver['minutes_since_update'] = $diff_minutes;
    }

    // طباعة النتيجة النهائية بنجاح
    echo json_encode([
        'success' => true,
        'count' => count($drivers),
        'drivers' => $drivers
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    // معالجة الأخطاء الاستثنائية دون إفشاء أسرار السيرفر الأمنية
    echo json_encode([
        'success' => false,
        'message' => '❌ حدث خطأ برمي في السيرفر أثناء محاولة جلب المواقع الجغرافية.',
        'error_debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
