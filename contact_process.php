<?php
// contact_process.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config.php'; // لاستيراد إعدادات SMTP
require 'vendor/autoload.php'; // تأكد من وجود مكتبة PHPMailer

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

        $mail->setFrom(SMTP_USER, 'تطبيق كرين - اتصل بنا');
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
```

### الخطوة 2: تحديث الجافاسكربت في `home.php`
يجب أن تتأكد أن دالة الإرسال في أسفل ملف `home.php` ترسل البيانات فعلياً لهذا الملف. استبدل الدالة الموجودة في `home.php` بهذه الدالة:

```javascript
<script>
function handleContactSubmit(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('name', document.getElementById('contactName').value);
    formData.append('phone', document.getElementById('contactPhone').value);
    formData.append('email', document.getElementById('contactEmail').value);
    formData.append('message', document.getElementById('contactMessage').value);

    fetch('contact_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const successEl = document.getElementById('contactSuccess');
        successEl.innerText = data.message;
        successEl.classList.remove('hidden');
        
        if(data.success) {
            document.getElementById('contactForm').reset();
        }

        setTimeout(() => {
            successEl.classList.add('hidden');
        }, 5000);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء الإرسال');
    });
}
</script>
```

### تأكد من الآتي:
1.  **المكتبة:** تأكد أنك قمت بتنصيب `PHPMailer` (عن طريق `composer require phpmailer/phpmailer`) في مجلد مشروعك.
2.  **كلمة المرور:** في ملف `config.php` (الذي قمنا بتحديثه سابقاً)، تأكد أن `SMTP_USER` هو `eceoeceo0@gmail.com` وأن `SMTP_PASS` هي **كلمة مرور التطبيقات** (16 حرفاً) التي أنشأتها من إعدادات حساب جوجل، وليست كلمة مرور الإيميل العادية.

هل تم تنفيذ هذه الخطوات؟ سيعمل الإرسال بعدها فوراً!
