# 🚕 تطبيق كرين - خدمات السحب والإنقاذ في العراق

تطبيق ويب متكامل يربط بين العملاء والسائقين لخدمات السحب والنقل في العراق.

## ✨ المزايا الرئيسية

- ✅ **نظام تسجيل موحد** - دخول آمن بالبريد الإلكتروني
- ✅ **خريطة تفاعلية** - تتبع السائقين بالوقت الحقيقي
- ✅ **طلبات فورية** - إنشاء الطلبات وتتبعها
- ✅ **نظام إداري متقدم** - إدارة كاملة للمستخدمين والطلبات
- ✅ **جدار حماية (WAF)** - حماية من الهجمات الإلكترونية
- ✅ **تنبيهات فورية** - إشعارات عبر التليجرام
- ✅ **تصميم احترافي** - واجهة سهلة الاستخدام

## 🛠️ المتطلبات

- Docker و Docker Compose
- Git
- حساب على Render (للنشر)

## 🚀 التشغيل المحلي

### 1. استنساخ المستودع
```bash
git clone https://github.com/yourusername/kreen.git
cd kreen
```

### 2. إنشاء ملف .env
```bash
cp .env.example .env
```

### 3. تشغيل مع Docker Compose
```bash
docker-compose up -d
```

### 4. الوصول للتطبيق
```
http://localhost:8001
```

## 📋 حسابات الاختبار

### حساب مدير (Admin)
- **البريد:** admin@kreen.com
- **كلمة المرور:** admin123

## 🌐 النشر على Render

### الخطوة 1: دفع الكود إلى GitHub
```bash
git add .
git commit -m "Initial commit: Deploy to Render"
git push -u origin main
```

### الخطوة 2: إنشاء تطبيق جديد على Render
1. اذهب إلى [Render](https://render.com)
2. اضغط على "New +" ثم اختر "Web Service"
3. اختر الـ repository الخاص بك
4. اختر "Docker" كنوع البيئة
5. اضغط "Create Web Service"

### الخطوة 3: تكوين متغيرات البيئة
في لوحة تحكم Render، أضف المتغيرات التالية:

```
APP_URL=https://your-app.onrender.com
DB_PATH=/var/www/html/kreen.db
ENABLE_TELEGRAM=true
TELEGRAM_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_CHAT_ID=YOUR_CHAT_ID
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
ADMIN_PASSWORD=your-secure-password
```

### الخطوة 4: السماح بـ Persistent Storage
1. في إعدادات الخدمة، أضف "Disk"
2. حدد مسار `/var/www/html` لحفظ قاعدة البيانات

## 📱 الأدوار المختلفة

### 👤 العميل
- عرض السائقين على الخريطة
- إنشاء طلبات جديدة
- تتبع الطلب بالوقت الحقيقي
- تقييم الخدمة

### 🚕 السائق
- عرض الطلبات المعلقة
- قبول الطلبات
- تحديث الموقع الحالي
- إكمال الطلب والحصول على الأجرة

### 🛡️ المدير
- إدارة المستخدمين
- عرض الإحصائيات
- إضافة رصيد للحسابات
- تسجيل الطلبات

## 🔐 الأمان

- **اختبار المدخلات** - منع SQL Injection و XSS
- **حماية CSRF** - رموز توكن فريدة لكل جلسة
- **تشفير كلمات المرور** - استخدام bcrypt
- **جدار حماية ويب** - اكتشاف الهجمات والحماية منها
- **حظر الـ IP** - حظر تلقائي للمهاجمين

## 📂 هيكل المشروع

```
kreen/
├── classes/                 # فئات المساعدة
│   ├── SessionManager.php
│   ├── CSRFProtection.php
│   ├── Validator.php
│   └── MailService.php
├── soc_manger/             # جدار الحماية (WAF)
│   ├── mine.py
│   └── requirements.txt
├── uploads/                # الملفات المرفوعة
├── logs/                   # السجلات
├── *.php                   # صفحات التطبيق الرئيسية
├── Dockerfile              # إعدادات Docker
├── docker-compose.yml      # إعدادات Compose
├── render.yaml             # إعدادات Render
└── .env.example           # نموذج متغيرات البيئة
```

## 🔧 التقنيات المستخدمة

- **PHP 8.2** - لغة البرمجة الخادم
- **SQLite** - قاعدة البيانات
- **Flask** - جدار الحماية (WAF)
- **Tailwind CSS** - التصميم
- **Leaflet Maps** - الخرائط التفاعلية
- **Docker** - الحاويات
- **Nginx** - خادم الويب
- **Gunicorn** - خادم التطبيقات

## 📞 الدعم والمساعدة

إذا واجهت مشكلة:
1. تحقق من السجلات: `docker logs kreen-app`
2. اختبر الاتصال بالخادم
3. تأكد من متغيرات البيئة
4. اقرأ ملف [DEPLOYMENT.md](./DEPLOYMENT.md)

## 📝 الترخيص

هذا المشروع مفتوح المصدر ومتاح للاستخدام التعليمي والتجاري.

## 👨‍💻 المطور

تطبيق كرين - خدمات السحب والإنقاذ المتكاملة

---

**حالة التطبيق:** ✅ جاهز للإنتاج

**آخر تحديث:** يوليو 2026
