<?php
// إظهار كافة الأخطاء للتشخيص
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db_file = __DIR__ . '/kreen.db';
$status_messages = [];

// 1. تنظيف عميق: نحاول حذف الملف القديم فوراً
if (file_exists($db_file)) {
    if (is_writable($db_file)) {
        if (@unlink($db_file)) {
            $status_messages[] = ["type" => "success", "text" => "🧹 تم مسح ملف القاعدة القديم/التالف بنجاح."];
        } else {
            $status_messages[] = ["type" => "error", "text" => "❌ تعذر مسح الملف! يرجى حذفه يدوياً عبر مدير الملفات."];
        }
    } else {
        $status_messages[] = ["type" => "error", "text" => "❌ الملف موجود ولكن ليس لديك صلاحية حذفه! يرجى حذفه يدوياً."];
    }
}

// 2. محاولة إنشاء الاتصال
try {
    // التأكد من أن المجلد نفسه قابل للكتابة قبل إنشاء الملف
    if (!is_writable(__DIR__)) {
        throw new Exception("المجلد الرئيسي غير قابل للكتابة. يرجى تعديل الصلاحيات (Chmod 755/777).");
    }

    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $status_messages[] = ["type" => "success", "text" => "🟢 تم إنشاء ملف قاعدة بيانات جديد وسليم."];
    
    // بناء الجداول
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY AUTOINCREMENT, fullname TEXT NOT NULL, phone TEXT NOT NULL, province TEXT NOT NULL, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, verification_code TEXT, is_verified INTEGER DEFAULT 0, role TEXT DEFAULT 'customer', profile_image TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS drivers (id INTEGER PRIMARY KEY AUTOINCREMENT, fullname TEXT NOT NULL, phone TEXT NOT NULL, province TEXT NOT NULL, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, wheel_number TEXT, wheel_type TEXT, wheel_color TEXT, wheel_model TEXT, verification_code TEXT, is_verified INTEGER DEFAULT 0, profile_image TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kias (id INTEGER PRIMARY KEY AUTOINCREMENT, fullname TEXT NOT NULL, phone TEXT NOT NULL, province TEXT NOT NULL, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, car_number TEXT, car_model TEXT, verification_code TEXT, is_verified INTEGER DEFAULT 0, profile_image TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS driver_locations (driver_id INTEGER PRIMARY KEY, latitude REAL NOT NULL, longitude REAL NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER NOT NULL, driver_id INTEGER, latitude REAL NOT NULL, longitude REAL NOT NULL, status TEXT DEFAULT 'pending', requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL, token TEXT NOT NULL, expires_at DATETIME NOT NULL);");
    
    $status_messages[] = ["type" => "success", "text" => "⚙️ تم بناء الجداول بنجاح."];

    // إعادة التغذية (Seeding)
    $hashed_password = password_hash("123456", PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO customers (fullname, phone, province, email, password, is_verified, role) VALUES (?, ?, ?, ?, ?, 1, 'customer')")->execute(['علي الرافدين', '07721738815', 'بغداد', 'customer@test.com', $hashed_password]);
    $pdo->prepare("INSERT INTO customers (fullname, phone, province, email, password, is_verified, role) VALUES (?, ?, ?, ?, ?, 1, 'admin')")->execute(['المدير العام', '07700000000', 'بغداد', 'admin@kreen.com', password_hash("admin123", PASSWORD_DEFAULT)]);
    
    $status_messages[] = ["type" => "success", "text" => "🎉 تم شحن البيانات بنجاح."];

} catch (Exception $e) {
    $status_messages[] = ["type" => "error", "text" => "❌ خطأ فادح: " . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<body style="font-family: sans-serif; padding: 20px;">
    <h2>تقرير إعداد قاعدة البيانات</h2>
    <?php foreach ($status_messages as $m): ?>
        <p style="color: <?= $m['type'] === 'success' ? 'green' : 'red' ?>; font-weight: bold;"><?= $m['text'] ?></p>
    <?php endforeach; ?>
    <hr>
    <p>إذا استمرت المشكلة، يرجى حذف ملف <code>kreen.db</code> يدوياً وتأكد من أن المجلد يمتلك صلاحيات الكتابة (755 أو 777).</p>
</body>
</html>
