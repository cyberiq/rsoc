<?php
session_start();
require 'config.php';

// تحديد ما إذا كان الطلب قادماً عبر تقنية AJAX/Fetch لتقديم استجابة JSON ذكية
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == 1);

// التحقق من صلاحيات الوصول وتأمين المعالج للسائقين الفعليين فقط (كرين أو كيا)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['driver', 'kia'])) {
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => '❌ غير مصرح لك بالوصول. يرجى تسجيل الدخول أولاً.']);
        exit;
    }
    header('Location: login.php?user_type=driver');
    exit;
}

$driver_id = $_SESSION['user_id'];
$driver_type = $_SESSION['user_type'];
$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;

if (!$request_id) {
    $msg = "❌ معرّف الطلب غير صحيح أو غير موجود بالطلب.";
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['error_msg'] = $msg;
    header('Location: driver_dashboard.php');
    exit;
}

try {
    // منع قبول أكثر من طلب نشط لنفس السائق لضمان سير العمل (قبول -> إنهاء)
    $active_driver_stmt = $pdo->prepare("SELECT id FROM service_requests WHERE driver_id = ? AND status = 'accepted' LIMIT 1");
    $active_driver_stmt->execute([$driver_id]);
    $active_driver_request = $active_driver_stmt->fetch(PDO::FETCH_ASSOC);

    if ($active_driver_request) {
        $msg = "⚠️ لديك طلب نشط حالياً. يرجى إنهاء الطلب الحالي قبل قبول طلب جديد.";
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['error_msg'] = $msg;
        header('Location: driver_dashboard.php');
        exit;
    }

    // فحص حالة الطلب في قاعدة البيانات لضمان عدم وجود تلاعب أو قبول مسبق
    $check_stmt = $pdo->prepare("SELECT status, driver_id FROM service_requests WHERE id = ?");
    $check_stmt->execute([$request_id]);
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $msg = "❌ عذراً، هذا الطلب لم يعد متوفراً أو تم حذفه من النظام.";
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['error_msg'] = $msg;
        header('Location: driver_dashboard.php');
        exit;
    }

    if ($request['status'] !== 'pending') {
        if ($request['driver_id'] == $driver_id) {
            $msg = "ℹ️ لقد قمت بقبول هذا الطلب مسبقاً وهو بانتظار تواصلك مع العميل.";
            $success_state = true;
        } else {
            $msg = "❌ عذراً، تم قبول هذا الطلب مسبقاً من سائق آخر.";
            $success_state = false;
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $success_state, 'message' => $msg]);
            exit;
        }
        if ($success_state) {
            $_SESSION['success_msg'] = $msg;
        } else {
            $_SESSION['error_msg'] = $msg;
        }
        header('Location: driver_dashboard.php');
        exit;
    }

    // نقوم بتحديث حالة الطلب مع ربطه بهوية السائق الحالي
    // نضمن بقاء الشرط status = 'pending' داخل الاستعلام لتجنب مشاكل التحديث المتزامن لأكثر من سائق بالثانية نفسها
    $update_stmt = $pdo->prepare("UPDATE service_requests 
                                  SET status = 'accepted', driver_id = ?, updated_at = CURRENT_TIMESTAMP 
                                  WHERE id = ? AND status = 'pending'");
    $update_stmt->execute([$driver_id, $request_id]);

    // التأكد من أن التحديث قد تم فعلياً على السجل
    if ($update_stmt->rowCount() > 0) {
        $msg = "🎉 ممتاز! تم قبول الطلب بنجاح. يرجى مراجعة تفاصيل العميل والاتصال به فوراً.";
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }
        $_SESSION['success_msg'] = $msg;
    } else {
        $msg = "❌ عذراً، تم خطف وقبول الطلب من سائق آخر متصل قبل أجزاء من الثانية.";
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['error_msg'] = $msg;
    }

} catch (PDOException $e) {
    $msg = "❌ خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى لاحقاً.";
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $msg, 'debug' => $e->getMessage()]);
        exit;
    }
    $_SESSION['error_msg'] = $msg;
}

// التوجيه للوحة التحكم عند استخدام الطريقة الكلاسيكية للنماذج
header('Location: driver_dashboard.php');
exit;
?>
