# 🚀 دليل الإطلاق والنشر - تطبيق كرين

## 📋 المتطلبات

### 1. **PHP Requirements**
- PHP 8.0+ (تم اختباره على PHP 8.2)
- Extensions المطلوبة:
  - `pdo_sqlite` أو `pdo_mysql`
  - `curl` (لـ Telegram)
  - `json`
  - `openssl`

### 2. **قاعدة البيانات**
- SQLite (افتراضي): لا توجد متطلبات إضافية
- MySQL/MariaDB (اختياري): استخدم `.env` لتغيير الإعدادات

### 3. **متطلبات أخرى**
- Telegram Bot Token
- Composer (لـ PHPMailer)

## 📦 خطوات الإعداد

### الخطوة 1: استنساخ المشروع
```bash
git clone <repo-url> kreen
cd kreen
```

### الخطوة 2: تثبيت المكتبات
```bash
composer install
```

### الخطوة 3: إنشاء ملف .env
```bash
cp .env.example .env
# ثم عدّل البيانات:
# TELEGRAM_TOKEN=8915001355:AAGqgvQtAzKyhWI0rfESj4WwgG4Cg4xE6Qs
# TELEGRAM_CHAT_ID=100886852
```

### الخطوة 4: إنشاء قاعدة البيانات
```bash
# للـ SQLite (افتراضي)
touch kreen.db
chmod 666 kreen.db

# ثم قم بتشغيل SQL initialization من migrate.php (سيتم إنشاؤه)
```

### الخطوة 5: اختبار الاتصال
```bash
php -r "require 'env-loader.php'; require 'config.php'; echo '✅ تم الاتصال';"
```

## 🔐 إعدادات الأمان

### 1. ملفات .env
```bash
chmod 600 .env
chmod 600 soc_manger/.env
```

### 2. أذونات المجلدات
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 755 sessions/
chmod 666 kreen.db
```

### 3. إعدادات الويب
- استخدم HTTPS في الإنتاج
- استخدم HTTP/2
- فعّل HSTS headers

## 🐳 النشر باستخدام Docker

```bash
docker-compose up -d
```

## 📊 اختبار النظام

### 1. اختبار الاتصال بالتليجرام
```bash
curl -X POST http://localhost:5000/api/test-telegram \
  -H "Content-Type: application/json" \
  -d '{"message": "Test"}'
```

### 2. اختبار قاعدة البيانات
```bash
sqlite3 kreen.db "SELECT * FROM customers LIMIT 1;"
```

### 3. اختبار تسجيل الدخول
- افتح: http://localhost:8080/login.php
- استخدم: admin@kreen.com / admin123 (بعد إنشاء الحساب)

## 📱 ميزات التطبيق

### 1. العملاء (Customers)
- تسجيل حساب جديد
- طلب خدمة سحب/إنقاذ
- تتبع حي للسائق
- إدارة الرصيد

### 2. السائقون (Drivers)
- تسجيل معلومات المركبة
- تحديث الموقع الحي
- قبول/رفض الطلبات
- إدارة الخدمات النشطة

### 3. الإدارة (Admin)
- إدارة المستخدمين
- إدارة الأرصدة المالية
- مراقبة الخدمات
- لوحة تحليل الإحصائيات

## 🛡️ WAF (جدار الحماية)

يتم تشغيل جدار حماية متقدم في `soc_manger/mine.py`:

### الحماية من:
- SQL Injection
- XSS Attacks
- Command Injection
- DDoS Attacks
- File Upload exploits
- VPN/Proxy Detection

### تفعيل WAF:
```bash
cd soc_manger
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python mine.py
```

## 📝 ملفات مهمة

| الملف | الوصف |
|------|--------|
| `.env` | إعدادات البيئة (سري) |
| `config.php` | إعدادات الحساب |
| `autoloader.php` | تحميل الـ Classes |
| `env-loader.php` | قراءة ملف .env |
| `login.php` | صفحة تسجيل الدخول |
| `register-secure.php` | تسجيل حساب جديد |
| `soc_manger/mine.py` | جدار الحماية |

## 🚨 استكشاف الأخطاء

### خطأ: "Could not find driver"
- تأكد من تثبيت `php-sqlite3` أو `php-mysql`
- قم بتفعيل الـ extension في `php.ini`

### خطأ: "Permission denied"
- تحقق من أذونات المجلدات: `chmod 755`
- تحقق من ملف القاعدة: `chmod 666 kreen.db`

### خطأ: "Telegram connection failed"
- تأكد من صحة التوكن
- تحقق من الـ Internet connection
- تحقق من Firewall

## 📞 الدعم الفني

- الأخطاء: تحقق من `logs/` و `/var/log/php-error.log`
- الأمان: راجع `FIXES_SUMMARY.md`
- الإعدادات: راجع `.env.example`

---

✅ **تم إعداد النظام بنجاح**
