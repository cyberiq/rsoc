<?php
session_start();
require 'config.php';

// تعيين نوع الاستجابة كـ JSON لضمان تفاعل سلس مع الـ JavaScript
header('Content-Type: application/json; charset=utf-8');

// حماية أمنية: لا يُسمح إلا للسائقين (كرين أو كيا) بتحديث مواقعهم الجغرافية
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['driver', 'kia'])) {
    echo json_encode(['success' => false, 'message' => '⚠️ غير مصرح لك بتحديث الموقع.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = $_SESSION['user_id'];
    $lat      = floatval($_POST['latitude']  ?? 0);
    $lng      = floatval($_POST['longitude'] ?? 0);
    $accuracy = floatval($_POST['accuracy']  ?? 0);
    $max_allowed_accuracy = 120.0;

    // التحقق من صحة الإحداثيات الجغرافية
    if ($lat === 0.0 || $lng === 0.0 || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        echo json_encode(['success' => false, 'message' => '❌ إحداثيات غير صالحة.']);
        exit;
    }

    // تجاهل المواقع ذات الدقة الضعيفة للحفاظ على جودة التتبع
    if ($accuracy > 0 && $accuracy > $max_allowed_accuracy) {
        echo json_encode([
            'success' => false,
            'accuracy_rejected' => true,
            'message' => '⚠️ دقة GPS منخفضة حالياً. تحرك لمكان مفتوح وأعد المحاولة.',
            'accuracy' => $accuracy,
            'max_accuracy' => $max_allowed_accuracy
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO driver_locations (driver_id, latitude, longitude, accuracy, updated_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT(driver_id) DO UPDATE SET
            latitude   = excluded.latitude,
            longitude  = excluded.longitude,
            accuracy   = excluded.accuracy,
            updated_at = excluded.updated_at
        ");
        $stmt->execute([$driver_id, $lat, $lng, $accuracy]);

        echo json_encode([
            'success'  => true,
            'message'  => '📍 تم تحديث موقعك.',
            'accuracy' => $accuracy
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '❌ خطأ في السيرفر أثناء تحديث الموقع.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '❌ طريقة طلب غير صالحة.']);
}
