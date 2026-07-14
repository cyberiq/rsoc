<?php
session_start();

// إظهار الأخطاء أثناء التطوير والربط
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'config.php';

// استدعاء مكتبة PHPMailer لربط النظام ببريد Gmail الآمن
// نتحقق من وجود autoload أولاً
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = 'error';

$user_type = $_GET['user_type'] ?? 'customer';
if (!in_array($user_type, ['kia', 'driver', 'customer'])) {
    $user_type = 'customer';
}

// مصفوفة الألوان والأنماط التفاعلية حسب نوع الحساب لجمالية متناسقة مع صفحات الدخول
$theme_classes = [
    'driver' => [
        'title' => 'سائق كرين (ونش)',
        'bg_badge' => 'bg-amber-100 text-amber-800 border-amber-200',
        'focus_ring' => 'focus:ring-amber-500 focus:border-amber-500',
        'btn' => 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20 focus:ring-amber-500',
        'link' => 'text-amber-600 hover:text-amber-700'
    ],
    'kia' => [
        'title' => 'سائق كيا حمل',
        'bg_badge' => 'bg-sky-100 text-sky-800 border-sky-200',
        'focus_ring' => 'focus:ring-sky-500 focus:border-sky-500',
        'btn' => 'bg-sky-500 hover:bg-sky-600 shadow-sky-500/20 focus:ring-sky-500',
        'link' => 'text-sky-600 hover:text-sky-700'
    ],
    'customer' => [
        'title' => 'مستخدم عادي',
        'bg_badge' => 'bg-green-100 text-green-800 border-green-200',
        'focus_ring' => 'focus:ring-green-500 focus:border-green-500',
        'btn' => 'bg-green-500 hover:bg-green-600 shadow-green-500/20 focus:ring-green-500',
        'link' => 'text-green-600 hover:text-green-700'
    ]
];

$current_theme = $theme_classes[$user_type];

function findAccountTableByEmail(PDO $pdo, string $email): ?array {
    $table_map = ['customer' => 'customers', 'driver' => 'drivers', 'kia' => 'kias'];
    foreach ($table_map as $type => $table) {
        $stmt = $pdo->prepare("SELECT fullname FROM $table WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return ['type' => $type, 'table' => $table, 'fullname' => $user['fullname']];
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $selected_user_type = $_POST['user_type'] ?? 'customer';

    if (empty($email)) {
        $message = "❌ يرجى كتابة البريد الإلكتروني للمتابعة.";
        $message_type = "error";
    } else {
        $table = ($selected_user_type === 'driver') ? 'drivers' : (($selected_user_type === 'kia') ? 'kias' : 'customers');

        try {
           // التحقق من وجود الحساب المسجل بهذا البريد أولاً وفق النوع المحدد
           $stmt = $pdo->prepare("SELECT fullname FROM $table WHERE email = ?");
           $stmt->execute([$email]);
           $user = $stmt->fetch();

           if (!$user) {
               $found = findAccountTableByEmail($pdo, $email);
               if ($found) {
                   $selected_user_type = $found['type'];
                   $table = $found['table'];
                   $user = ['fullname' => $found['fullname']];
               }
           }

           if ($user) {
                // توليد توكن آمن وقوي لاستعادة كلمة المرور
                $token = bin2hex(random_bytes(32));
                // صلاحية التوكن 1 ساعة من الآن
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // تنظيف التوكنات القديمة لهذا البريد لتفادي التراكم بقاعدة البيانات
                $clean_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $clean_stmt->execute([$email]);

                // إدراج التوكن الجديد في جدول توكنات الاستعادة
                $insert_stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->execute([$email, $token, $expires_at]);

                // بناء رابط إعادة تعيين كلمة المرور
                // يحصل الرابط على نوع المستخدم وتوكن الاستعادة كمحددات
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $relative_reset_path = $basePath . '/reset_password.php?token=' . $token . '&user_type=' . $selected_user_type;
                $appUrl = getenv('APP_URL') ?: ($protocol . $host);
                $reset_link = rtrim($appUrl, '/') . $relative_reset_path;

                // التحقق من توفر مكتبة PHPMailer وإرسال الرسالة البريدية الفخمة
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    try {
                        $mail = new PHPMailer(true);
                         
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USER;
                        $mail->Password   = SMTP_PASS;
                        $mail->SMTPSecure = SMTP_SECURE;
                        $mail->Port       = SMTP_PORT;
                        $mail->CharSet    = 'UTF-8';
 
                        $mail->setFrom(SMTP_USER, 'نظام تطبيق كرين');
                        $mail->addAddress($email, $user['fullname']);
                        $mail->isHTML(true);
                        $mail->Subject = 'رابط إعادة تعيين كلمة المرور - تطبيق كرين';
                         
                        // تصميم راقٍ وسينمائي لبريد التفعيل المستلم في بريد الجيميل
                        $mail->Body = "
                    <div dir='rtl' style='font-family: Tahoma, Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #f8fafc;'>
                        <div style='text-align: center; margin-bottom: 25px;'>
                            <h2 style='color: #d97706; margin: 0;'>تطبيق كرين سحب وإنقاذ</h2>
                            <p style='font-size: 12px; color: #64748b; margin-top: 5px;'>طلب استعادة وتأمين الحساب</p>
                        </div>
                        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;' />
                        <p style='font-size: 15px; color: #1e293b; line-height: 1.6;'>أهلاً بك يا <b>" . htmlspecialchars($user['fullname']) . "</b>،</p>
                        <p style='font-size: 14px; color: #475569; line-height: 1.6;'>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في تطبيق كرين. يرجى الضغط على الزر أدناه لتحديث كلمة مرورك بأمان وسرعة:</p>
                        <div style='text-align: center; margin: 35px 0;'>
                            <a href='" . $reset_link . "' style='background-color: #d97706; color: #ffffff; text-decoration: none; padding: 12px 30px; font-size: 14px; font-weight: bold; border-radius: 10px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.2);'>
                                🔐 تعيين كلمة مرور جديدة
                            </a>
                        </div>
                        <p style='font-size: 12px; color: #ef4444; background-color: #fef2f2; padding: 10px; border-radius: 8px; border: 1px solid #fee2e2;'>⚠️ ملاحظة أمنية: ينتهي مفعول هذا الرابط تلقائياً بعد مرور ساعة واحدة (60 دقيقة) لحماية بياناتك من الوصول غير المصرح به.</p>
                        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0 15px 0;' />
                        <p style='font-size: 11px; color: #94a3b8; text-align: center;'>إذا لم تقم بطلب إعادة التعيين بنفسك، يرجى تجاهل هذا البريد الإلكتروني وسيبقى حسابك آمناً تماماً.</p>
                    </div>";

                        $mail->send();
                        $message = "🎉 تم إرسال رابط استعادة كلمة المرور بنجاح إلى بريدك الإلكتروني. يرجى مراجعة صندوق الوارد (أو البريد المزعج).";
                        $message_type = "success";
                    } catch (Exception $e) {
                        $message = "⚠️ تم إنشاء رابط الاستعادة بنجاح، لكن حدثت مشكلة في إرسال البريد الإلكتروني: " . $e->getMessage();
                        $message_type = "warning";
                        $debug_reset_link = $relative_reset_path;
                    }
                } else {
                    $message = "⚠️ تم إنشاء رابط الاستعادة بنجاح، ولكن مكتبة PHPMailer غير موجودة في الخادم لإرسال البريد.";
                    $message_type = "warning";
                    $debug_reset_link = $relative_reset_path;
                }
 
                $hostOnly = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
                if (defined('APP_DEBUG') && APP_DEBUG === true) {
                    error_log("Reset password link (DEBUG only): $relative_reset_path");
                }

            } else {
                $message = "❌ لم نجد أي حساب مسجل بهذا البريد الإلكتروني كـ " . $current_theme['title'] . ".";
                $message_type = "error";
            }

        } catch (PDOException $e) {
            $message = "❌ حدث خطأ برمي أثناء معالجة الطلب في الخادم. يرجى مراجعة الإعدادات.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>استعادة كلمة المرور - تطبيق كرين</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');
    body { font-family: 'Cairo', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-10 px-4">
  <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
    
    <!-- روابط الملاحة للرجوع السريع -->
    <div class="flex justify-between items-center mb-6">
      <a href="login.php?user_type=<?= htmlspecialchars($user_type) ?>" class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition flex items-center gap-1">⬅️ رجوع لتسجيل الدخول</a>
    </div>

    <div class="text-center mb-6">
      <div class="inline-block px-3 py-1 text-xs font-bold rounded-full border mb-3 <?= $current_theme['bg_badge'] ?>">
        استعادة كلمة مرور: <?= $current_theme['title'] ?>
      </div>
      <h2 class="text-2xl font-bold text-gray-800">نسيت كلمة المرور؟</h2>
      <p class="text-sm text-gray-400 mt-1">اكتب بريدك الإلكتروني المعتمد لنرسل لك رابطاً مشفراً لإعادة تعيينها.</p>
    </div>

    <!-- عرض إشعارات النجاح أو الفشل -->
    <?php if ($message): ?>
      <div class="mb-5 p-4 rounded-xl text-sm font-medium border leading-relaxed <?= ($message_type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : ($message_type === 'warning' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-red-50 text-red-700 border-red-200')) ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <!-- نموذج المراسلة والتأمين -->
    <form method="POST" class="space-y-5">
      <input type="hidden" name="user_type" value="<?= htmlspecialchars($user_type) ?>">
      
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">البريد الإلكتروني المسجل (Gmail)</label>
        <input type="email" name="email" required 
               class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition text-left" 
               placeholder="example@gmail.com">
      </div>

      <button type="submit" 
              class="w-full mt-2 py-3 px-4 <?= $current_theme['btn'] ?> active:scale-[0.98] text-white font-bold rounded-xl shadow-lg transition duration-150 flex items-center justify-center gap-2">
        ✉️ إرسال رابط الاستعادة للبريد
      </button>
    </form>

    <div class="mt-8 pt-5 border-t border-gray-100 text-center text-xs text-gray-400 leading-relaxed">
        تحرص أنظمة تطبيق كرين للأمان على التحقق التام وحفظ الطلبات آمنة ومحمية بالكامل على الخوادم.
    </div>

  </div>
</body>
</html>
