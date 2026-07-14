<?php
// contact_process.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config.php'; // لاستيراد إعدادات SMTP
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '❌ الاعتمادات المطلوبة غير متوفرة. يرجى تشغيل composer install ثم إعادة المحاولة.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strip_tags(trim($_POST['name'] ?? ''));
    $phone = strip_tags(trim($_POST['phone'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $message_body = strip_tags(trim($_POST['message'] ?? ''));

    if (empty($name) || empty($phone) || empty($message_body)) {
        echo json_encode(['success' => false, 'message' => '❌ يرجى ملء الحقول الإجبارية.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = SMTP_TIMEOUT;
        $mail->SMTPKeepAlive = false;
        $mail->SMTPAutoTLS = SMTP_AUTO_TLS;

        $mail->setFrom(SMTP_FROM_EMAIL, 'تطبيق كرين - اتصل بنا');
        $mail->addAddress('eceoeceo0@gmail.com'); // بريدك المعتمد

        $mail->isHTML(true);
        $mail->Subject = "رسالة جديدة من العميل: $name";
        $mail->Body    = "
            <h3>رسالة جديدة من الموقع</h3>
            <p><b>الاسم:</b> $name</p>
            <p><b>الهاتف:</b> $phone</p>
            <p><b>البريد:</b> $email</p>
            <p><b>الرسالة:</b><br>$message_body</p>
        ";

        $mail->send();
        echo json_encode(['success' => true, 'message' => '🎉 تم إرسال رسالتك بنجاح!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '❌ فشل الإرسال: ' . $mail->ErrorInfo]);
    }
}
?>
