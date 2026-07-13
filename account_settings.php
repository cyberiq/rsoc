<?php
session_start();
require 'config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: choose_login.php");
    exit;
}

// توليد CSRF token إذا لم يكن موجوداً
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// تحديد الجدول بناءً على نوع المستخدم - استخدام قائمة بيضاء للحماية من SQL Injection
$allowed_tables = ['customers', 'drivers', 'kias'];
$table_map = [
    'driver' => 'drivers',
    'kia' => 'kias',
    'customer' => 'customers'
];

if (!array_key_exists($user_type, $table_map)) {
    session_destroy();
    header("Location: choose_login.php");
    exit;
}

$table = $table_map[$user_type];

$theme_classes = [
    'driver' => [
        'bg_badge' => 'bg-amber-100 text-amber-800 border-amber-200',
        'focus_ring' => 'focus:ring-amber-500 focus:border-amber-500',
        'btn' => 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20 focus:ring-amber-500',
        'link' => 'text-amber-600 hover:text-amber-700',
        'dashboard' => 'driver_dashboard.php',
        'title' => 'إعدادات حساب السائق (كرين)'
    ],
    'kia' => [
        'bg_badge' => 'bg-sky-100 text-sky-800 border-sky-200',
        'focus_ring' => 'focus:ring-sky-500 focus:border-sky-500',
        'btn' => 'bg-sky-500 hover:bg-sky-600 shadow-sky-500/20 focus:ring-sky-500',
        'link' => 'text-sky-600 hover:text-sky-700',
        'dashboard' => 'driver_dashboard.php', // سائق الكيا يعود للوحة السائقين
        'title' => 'إعدادات حساب السائق (كيا)'
    ],
    'customer' => [
        'bg_badge' => 'bg-green-100 text-green-800 border-green-200',
        'focus_ring' => 'focus:ring-green-500 focus:border-green-500',
        'btn' => 'bg-green-500 hover:bg-green-600 shadow-green-500/20 focus:ring-green-500',
        'link' => 'text-green-600 hover:text-green-700',
        'dashboard' => 'customer_dashboard.php',
        'title' => 'إعدادات حساب العميل'
    ]
];

$current_theme = $theme_classes[$user_type];

// ===== دوال التحقق من صحة المدخلات =====

/**
 * التحقق من صيغة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 255;
}

/**
 * التحقق من صيغة رقم الهاتف العراقي
 */
function validatePhoneNumber($phone) {
    // يقبل أرقام عراقية بصيغ مختلفة
    $cleaned = preg_replace('/[^0-9+\-()]/', '', $phone);
    return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
}

/**
 * التحقق من قوة كلمة المرور
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "يجب أن تكون كلمة المرور 8 أحرف على الأقل";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "يجب أن تحتوي على أحرف صغيرة (a-z)";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "يجب أن تحتوي على أحرف كبيرة (A-Z)";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "يجب أن تحتوي على أرقام (0-9)";
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "يجب أن تحتوي على رمز خاص (!@#$%^&* إلخ)";
    }
    
    return $errors;
}

/**
 * التحقق من صحة اسم المستخدم
 */
function validateFullname($name) {
    $name = trim($name);
    if (strlen($name) < 3 || strlen($name) > 100) {
        return false;
    }
    // يسمح بالأحرف والأرقام والمسافات والشرطات
    return preg_match('/^[\p{L}\s\-\d]{3,100}$/u', $name) === 1;
}

/**
 * التحقق من حجم ونوع الملف المرفوع
 */
function validateImageFile($file) {
    $max_size = 5 * 1024 * 1024; // 5 MB
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'حجم الصورة يجب أن يكون أقل من 5 MB'];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'صيغة الصورة غير مدعومة. استخدم JPG أو PNG أو GIF'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        return ['success' => false, 'error' => 'امتداد الملف غير صحيح'];
    }
    
    // التحقق من محتوى الملف
    $file_info = getimagesize($file['tmp_name']);
    if ($file_info === false) {
        return ['success' => false, 'error' => 'الملف ليس صورة صالحة'];
    }
    
    return ['success' => true];
}

// ===== انتهاء دوال التحقق =====

// جلب بيانات المستخدم الحالية
$stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: choose_login.php");
    exit;
}

$success = '';
$error = '';

$provinces = [
    'بغداد','البصرة','الموصل','أربيل','النجف','كربلاء',
    'ذي قار','الديوانية','صلاح الدين','كركوك','ميسان',
    'الأنبار','دهوك','السليمانية','القادسية','واسط',
    'بابل','حلبجة','الحلة'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "❌ خطأ أمان: طلبك غير صحيح. يرجى إعادة المحاولة.";
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $province = $_POST['province'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        // ===== التحقق من الصحة =====
        
        // 1. التحقق من الاسم
        if (!validateFullname($fullname)) {
            $error = "❌ الاسم الكامل غير صحيح. يجب أن يكون بين 3 و 100 حرف.";
        }
        
        // 2. التحقق من البريد الإلكتروني
        elseif (!validateEmail($email)) {
            $error = "❌ البريد الإلكتروني غير صحيح. يرجى إدخال بريد صحيح.";
        }
        
        // 3. التحقق من رقم الهاتف
        elseif (!validatePhoneNumber($phone)) {
            $error = "❌ رقم الهاتف غير صحيح. يرجى إدخال رقم هاتف عراقي صحيح.";
        }
        
        // 4. التحقق من المحافظة
        elseif (!in_array($province, $provinces)) {
            $error = "❌ المحافظة المحددة غير صحيحة.";
        }
        
        // 5. التحقق من كلمة المرور الحالية
        elseif (!password_verify($current_password, $user['password'])) {
            $error = "❌ كلمة المرور الحالية غير صحيحة. لا يمكن حفظ التغييرات بدونها.";
        }
        
        // 6. التحقق من كلمة المرور الجديدة (إذا تم إدخالها)
        elseif (!empty($new_password)) {
            $password_errors = validatePasswordStrength($new_password);
            if (!empty($password_errors)) {
                $error = "❌ كلمة المرور ضعيفة. المتطلبات:\n" . implode("\n", $password_errors);
            }
        }
        
        // 7. التحقق من فرادة البريد الإلكتروني الجديد
        if (empty($error)) {
            $check = $pdo->prepare("SELECT id FROM $table WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->fetch()) {
                $error = "❌ البريد الإلكتروني مستخدم بالفعل من قبل حساب آخر.";
            }
        }

        // ===== معالجة الملفات =====
        if (empty($error)) {
            $profile_image_name = $user['profile_image'] ?? '';
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                    $error = "❌ خطأ في رفع الصورة. يرجى المحاولة مجدداً.";
                } else {
                    $validation = validateImageFile($_FILES['profile_image']);
                    if (!$validation['success']) {
                        $error = $validation['error'];
                    } else {
                        // إنشاء مجلد التحميل إذا لم يكن موجوداً
                        $upload_dir = __DIR__ . "/uploads/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // إنشاء اسم ملف آمن
                        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                        $new_image_name = "profile_" . $user_id . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                        $upload_path = $upload_dir . $new_image_name;
                        
                        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            $error = "❌ فشل رفع الصورة. يرجى التحقق من صلاحيات المجلد.";
                        } else {
                            // تحديث اسم الصورة للحفظ في قاعدة البيانات
                            $profile_image_name = "uploads/" . $new_image_name;
                        }
                    }
                }
            }
        }

        // ===== حفظ البيانات في قاعدة البيانات =====
        if (empty($error)) {
            try {
                $pdo->beginTransaction();
                
                if (!empty($new_password)) {
                    // تحديث مع كلمة مرور جديدة
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE $table SET fullname = ?, phone = ?, email = ?, province = ?, password = ?, profile_image = ? WHERE id = ?");
                    $update->execute([$fullname, $phone, $email, $province, $hashed_password, $profile_image_name, $user_id]);
                } else {
                    // تحديث بدون تغيير كلمة المرور
                    $update = $pdo->prepare("UPDATE $table SET fullname = ?, phone = ?, email = ?, province = ?, profile_image = ? WHERE id = ?");
                    $update->execute([$fullname, $phone, $email, $province, $profile_image_name, $user_id]);
                }
                
                $pdo->commit();
                
                // تحديث بيانات الجلسة
                $_SESSION['user_email'] = $email;
                
                $success = "🎉 تم تحديث بيانات حسابك بنجاح!";
                
                // إعادة جلب البيانات المُحدثة
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // إعادة تعيين CSRF token بعد النجاح
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "❌ حدث خطأ في قاعدة البيانات. يرجى المحاولة لاحقاً.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $current_theme['title'] ?> - تطبيق كرين</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');
    body { font-family: 'Cairo', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-12 px-4">
  <div class="max-w-xl w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
    
    <!-- زر الرجوع الذكي للوحة التحكم المتوافقة -->
    <div class="flex justify-between items-center mb-6">
      <a href="<?= $current_theme['dashboard'] ?>" class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition flex items-center gap-1">⬅️ العودة للوحة التحكم</a>
      <span class="inline-block px-3 py-1 text-xs font-bold rounded-full border <?= $current_theme['bg_badge'] ?>">
        نوع الحساب: <?= ($user_type === 'driver' ? 'سائق كرين' : ($user_type === 'kia' ? 'سائق كيا' : 'مستخدم عادي')) ?>
      </span>
    </div>

    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-800">إعدادات الحساب الشخصي</h2>
      <p class="text-sm text-gray-400 mt-1">تعديل بيانات ملفك الشخصي وتأمين حسابك بسهولة</p>
    </div>

    <!-- عرض رسائل النجاح أو الأخطاء -->
    <?php if ($success): ?>
      <div class="mb-5 p-4 rounded-xl text-sm font-medium bg-green-50 text-green-700 border border-green-200">
        <?= $success ?>
      </div>
    <?php elseif ($error): ?>
      <div class="mb-5 p-4 rounded-xl text-sm font-medium bg-red-50 text-red-700 border border-red-200">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <!-- نموذج تحديث الإعدادات -->
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
      
      <!-- قسم إدارة ورفع الصورة الشخصية -->
      <div class="flex flex-col items-center justify-center bg-gray-50 p-4 rounded-2xl border border-gray-100">
        <div class="relative w-24 h-24 mb-3">
          <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
            <img id="profilePreview" src="<?= htmlspecialchars($user['profile_image']) ?>" class="w-full h-full rounded-full object-cover border-2 border-gray-200" alt="Profile Image">
          <?php else: ?>
            <img id="profilePreview" src="images/man.png" class="w-full h-full rounded-full object-cover border-2 border-gray-200" alt="Default Profile">
          <?php endif; ?>
          <label class="absolute bottom-0 right-0 bg-white p-1.5 rounded-full shadow-md border border-gray-100 cursor-pointer hover:bg-gray-50 transition">
            📸
            <input type="file" name="profile_image" id="profileInput" accept="image/*" class="hidden" onchange="previewImage(event)">
          </label>
        </div>
        <p class="text-xs text-gray-400">انقر على الأيقونة لتحديث صورتك الشخصية</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">الاسم الكامل</label>
          <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required 
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الهاتف</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required 
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition text-left">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">البريد الإلكتروني</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required 
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition text-left">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">المحافظة</label>
          <select name="province" required 
                  class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition bg-white">
            <?php foreach ($provinces as $prov): ?>
              <option value="<?= htmlspecialchars($prov) ?>" <?= (($user['province'] ?? '') === $prov) ? 'selected' : '' ?>><?= htmlspecialchars($prov) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- إظهار حقول المركبة إذا كان الحساب سائقاً لجمالية العرض والتوثيق -->
      <?php if ($user_type === 'driver' || $user_type === 'kia'): ?>
        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
          <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">معلومات المركبة المسجلة (للقراءة فقط)</h3>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-gray-500 block">رقم المركبة/العجلة:</span>
              <span class="font-bold text-gray-800"><?= htmlspecialchars($user['wheel_number'] ?? $user['car_number'] ?? 'غير محدد') ?></span>
            </div>
            <div>
              <span class="text-gray-500 block">الموديل/النوع:</span>
              <span class="font-bold text-gray-800"><?= htmlspecialchars($user['wheel_type'] ?? $user['car_model'] ?? 'غير محدد') ?></span>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="border-t border-gray-100 pt-5 space-y-4">
        <h3 class="text-sm font-bold text-gray-700">تغيير كلمة المرور أو التأكيد</h3>
        
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">كلمة المرور الجديدة (اتركها فارغة إذا لا تريد تغييرها)</label>
          <input type="password" name="new_password" placeholder="••••••••"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">كلمة المرور الحالية (مطلوبة لحفظ التغييرات) <span class="text-red-500">*</span></label>
          <input type="password" name="current_password" required placeholder="••••••••"
                 class="w-full px-4 py-2.5 rounded-xl border border-red-300 focus:ring-2 <?= $current_theme['focus_ring'] ?> text-gray-800 outline-none transition">
        </div>
      </div>

      <!-- زر الحفظ المتكيف -->
      <button type="submit" 
              class="w-full py-3 px-4 <?= $current_theme['btn'] ?> active:scale-[0.98] text-white font-bold rounded-xl shadow-lg transition duration-150 flex items-center justify-center gap-2">
        💾 حفظ التعديلات وتحديث الحساب
      </button>
    </form>

    <!-- خيار حذف الحساب مع لافتة تنبيهية واضحة -->
    <div class="mt-8 pt-5 border-t border-gray-100 flex items-center justify-between">
      <p class="text-xs text-gray-400">جميع بياناتك محفوظة ومشفرة بالكامل.</p>
      <a href="delete_account.php" onclick="return confirm('⚠️ تحذير: هل أنت متأكد تماماً من رغبتك بحذف حسابك نهائياً من تطبيق كرين؟ لا يمكن التراجع عن هذا الإجراء.')" 
         class="text-xs font-bold text-red-500 hover:text-red-700 transition">❌ حذف الحساب نهائياً</a>
    </div>

  </div>

  <script>
    // ===== دوال التحقق من الصحة على جانب العميل =====
    
    /**
     * التحقق من قوة كلمة المرور
     */
    function checkPasswordStrength(password) {
      const requirements = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^a-zA-Z0-9]/.test(password)
      };
      
      // تحديث عناصر المتطلبات
      document.getElementById('req-length').className = requirements.length ? 'text-green-600' : 'text-gray-400';
      document.getElementById('req-length').innerHTML = (requirements.length ? '✅' : '❌') + ' 8 أحرف على الأقل';
      
      document.getElementById('req-lower').className = requirements.lowercase ? 'text-green-600' : 'text-gray-400';
      document.getElementById('req-lower').innerHTML = (requirements.lowercase ? '✅' : '❌') + ' أحرف صغيرة (a-z)';
      
      document.getElementById('req-upper').className = requirements.uppercase ? 'text-green-600' : 'text-gray-400';
      document.getElementById('req-upper').innerHTML = (requirements.uppercase ? '✅' : '❌') + ' أحرف كبيرة (A-Z)';
      
      document.getElementById('req-number').className = requirements.number ? 'text-green-600' : 'text-gray-400';
      document.getElementById('req-number').innerHTML = (requirements.number ? '✅' : '❌') + ' أرقام (0-9)';
      
      document.getElementById('req-special').className = requirements.special ? 'text-green-600' : 'text-gray-400';
      document.getElementById('req-special').innerHTML = (requirements.special ? '✅' : '❌') + ' رمز خاص (!@#$%^&*)';
      
      // حساب عدد المتطلبات المستوفاة
      const metRequirements = Object.values(requirements).filter(v => v).length;
      
      // تحديث مؤشر القوة
      const strengthBar = document.getElementById('passwordStrengthBar');
      const strengthText = document.getElementById('passwordStrengthText');
      const percentage = (metRequirements / 5) * 100;
      
      strengthBar.style.width = percentage + '%';
      
      if (metRequirements <= 2) {
        strengthBar.className = 'h-full w-0 transition-all duration-300 bg-red-500';
        strengthText.textContent = 'ضعيفة جداً';
        strengthText.className = 'text-xs font-bold text-red-600';
      } else if (metRequirements === 3) {
        strengthBar.className = 'h-full w-0 transition-all duration-300 bg-yellow-500';
        strengthText.textContent = 'متوسطة';
        strengthText.className = 'text-xs font-bold text-yellow-600';
      } else if (metRequirements === 4) {
        strengthBar.className = 'h-full w-0 transition-all duration-300 bg-blue-500';
        strengthText.textContent = 'قوية';
        strengthText.className = 'text-xs font-bold text-blue-600';
      } else {
        strengthBar.className = 'h-full w-0 transition-all duration-300 bg-green-500';
        strengthText.textContent = 'قوية جداً';
        strengthText.className = 'text-xs font-bold text-green-600';
      }
      
      return metRequirements === 5;
    }
    
    /**
     * التحقق من صيغة البريد الإلكتروني
     */
    function validateEmail(email) {
      const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return regex.test(email);
    }
    
    /**
     * التحقق من صيغة رقم الهاتف
     */
    function validatePhone(phone) {
      const cleaned = phone.replace(/[^0-9+\-()]/g, '');
      return cleaned.length >= 10 && cleaned.length <= 15;
    }
    
    /**
     * معاينة الصورة الفورية
     */
    function previewImage(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      // التحقق من حجم الملف (5 MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('❌ حجم الصورة يجب أن يكون أقل من 5 MB');
        event.target.value = '';
        return;
      }
      
      // التحقق من نوع الملف
      const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        alert('❌ صيغة الصورة غير مدعومة. استخدم JPG أو PNG أو GIF');
        event.target.value = '';
        return;
      }
      
      // معاينة الصورة
      const reader = new FileReader();
      reader.onload = function() {
        document.getElementById('profilePreview').src = reader.result;
      }
      reader.readAsDataURL(file);
    }
    
    /**
     * التحقق من صحة النموذج قبل الإرسال
     */
    function validateForm(event) {
      const fullname = document.getElementById('fullname').value.trim();
      const email = document.getElementById('email').value.trim();
      const phone = document.getElementById('phone').value.trim();
      const newPassword = document.getElementById('newPassword').value;
      const currentPassword = document.getElementById('currentPassword').value;
      
      // التحقق من الاسم
      if (fullname.length < 3 || fullname.length > 100) {
        alert('❌ الاسم الكامل يجب أن يكون بين 3 و 100 حرف');
        event.preventDefault();
        return false;
      }
      
      // التحقق من البريد الإلكتروني
      if (!validateEmail(email)) {
        alert('❌ البريد الإلكتروني غير صحيح');
        event.preventDefault();
        return false;
      }
      
      // التحقق من رقم الهاتف
      if (!validatePhone(phone)) {
        alert('❌ رقم الهاتف غير صحيح. يجب أن يكون 10-15 أرقام');
        event.preventDefault();
        return false;
      }
      
      // التحقق من كلمة المرور الجديدة إذا تم إدخالها
      if (newPassword && !checkPasswordStrength(newPassword)) {
        alert('❌ كلمة المرور لا تستوفي جميع المتطلبات. تحقق من الرسالة أعلاه.');
        event.preventDefault();
        return false;
      }
      
      // التحقق من إدخال كلمة المرور الحالية
      if (!currentPassword) {
        alert('❌ كلمة المرور الحالية مطلوبة');
        event.preventDefault();
        return false;
      }
      
      // تعطيل الزر أثناء الإرسال
      document.getElementById('submitBtn').disabled = true;
      document.getElementById('submitBtnText').classList.add('hidden');
      document.getElementById('submitBtnSpinner').classList.remove('hidden');
      
      return true;
    }
    
    // ===== معالجات الأحداث =====
    
    // مراقبة إدخال كلمة المرور الجديدة
    document.getElementById('newPassword').addEventListener('input', function(e) {
      const container = document.getElementById('passwordStrengthContainer');
      if (e.target.value) {
        container.classList.remove('hidden');
        checkPasswordStrength(e.target.value);
      } else {
        container.classList.add('hidden');
      }
    });
    
    // معاينة الصورة
    document.getElementById('profileInput').addEventListener('change', previewImage);
    
    // التحقق من النموذج عند الإرسال
    document.getElementById('settingsForm').addEventListener('submit', validateForm);
    
    // رسالة خطأ بلطف عند ترك البريد الإلكتروني
    document.getElementById('email').addEventListener('blur', function() {
      if (this.value && !validateEmail(this.value)) {
        this.parentElement.querySelector('small')?.remove();
        const small = document.createElement('small');
        small.className = 'text-red-500 text-xs mt-1 block';
        small.textContent = '❌ البريد الإلكتروني غير صحيح';
        this.parentElement.appendChild(small);
      }
    });
    
    // رسالة خطأ بلطف عند ترك رقم الهاتف
    document.getElementById('phone').addEventListener('blur', function() {
      if (this.value && !validatePhone(this.value)) {
        this.parentElement.querySelector('small')?.remove();
        const small = document.createElement('small');
        small.className = 'text-red-500 text-xs mt-1 block';
        small.textContent = '❌ رقم الهاتف غير صحيح (10-15 أرقام)';
        this.parentElement.appendChild(small);
      }
    });
  </script>
</body>
</html>
