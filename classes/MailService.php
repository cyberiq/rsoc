<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $this->mailer->Port = (int)(getenv('SMTP_PORT') ?: 587);
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = getenv('SMTP_USER') ?: '';
            $this->mailer->Password = getenv('SMTP_PASS') ?: '';
            $this->mailer->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Timeout = (int)(getenv('SMTP_TIMEOUT') ?: 10);
            $this->mailer->SMTPKeepAlive = false;
            $this->mailer->SMTPAutoTLS = getenv('SMTP_AUTO_TLS') !== 'false';
        } catch (Exception $e) {
            error_log("MailService Error: " . $e->getMessage());
        }
    }
    
    public function sendVerificationEmail($email, $name, $code) {
        try {
            $this->mailer->setFrom(getenv('SMTP_FROM_EMAIL') ?: 'noreply@kreen.local', 'تطبيق كرين');
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = 'تأكيد بريدك الإلكتروني - تطبيق كرين';
            
            $link = "http://192.168.1.24/kreen/verify-email.php?code=" . urlencode($code) . "&email=" . urlencode($email);
            $html = "
            <html dir='rtl'><body style='font-family: Cairo, sans-serif;'>
                <h2>مرحباً $name!</h2>
                <p>شكراً للتسجيل. اضغط الزر أدناه للتحقق من بريدك:</p>
                <p><a href='$link' style='background: #f97316; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>✅ تأكيد البريد</a></p>
                <p>أو استخدم: $link</p>
                <p style='color: #999; font-size: 12px;'>ينتهي صلاحيته بعد 24 ساعة</p>
            </body></html>";
            
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html;
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPasswordReset($email, $name, $token, $link) {
        try {
            $this->mailer->setFrom(getenv('SMTP_FROM_EMAIL') ?: 'noreply@kreen.local', 'تطبيق كرين');
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = 'استعادة كلمة المرور';
            
            $html = "<html dir='rtl'><body style='font-family: Cairo, sans-serif;'>
                <h2>استعادة كلمة المرور</h2>
                <p>مرحباً $name،</p>
                <p>اضغط الزر أدناه لاستعادة كلمة مرورك:</p>
                <p><a href='$link' style='background: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 استعادة</a></p>
                <p style='color: #999; font-size: 12px;'>ينتهي بعد ساعة واحدة</p>
            </body></html>";
            
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html;
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return false;
        }
    }
}
