# 📦 دليل النشر على Render

دليل شامل لنشر تطبيق كرين على منصة Render بنجاح.

## ✅ المتطلبات الأساسية

- ✅ حساب GitHub مع كود المشروع
- ✅ حساب Render (يمكن إنشاؤه مجاناً على render.com)
- ✅ حساب بريد إلكتروني (Gmail أو بديل)
- ✅ حساب Telegram Bot (اختياري لكن مهم للتنبيهات)

## 🔑 متغيرات البيئة الإلزامية

### التطبيق الأساسي
```
APP_URL=https://your-kreen-app.onrender.com
DB_PATH=/var/www/html/kreen.db
APP_DEBUG=false
SESSION_TIMEOUT=1800
ENABLE_TELEGRAM=true
```

### البريد الإلكتروني (SMTP)
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_SECURE=tls
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=تطبيق كرين
```

### التليجرام (للتنبيهات الأمنية)
```
TELEGRAM_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_CHAT_ID=YOUR_CHAT_ID
```

### الأمان
```
ADMIN_PASSWORD=use-strong-password-here
UPSTREAM_URL=http://127.0.0.1:8080
PROXY_ENABLED=true
```

## 🛠️ خطوات النشر الكاملة

### 1️⃣ تحضير المستودع

```bash
# تأكد من وجود جميع الملفات الضرورية
git status
git add .
git commit -m "Deploy configuration for Render"
git push origin main
```

### 2️⃣ إنشاء تطبيق على Render

1. **اذهب إلى Render Dashboard:** https://dashboard.render.com
2. **اضغط على "New +"** في الزاوية العلوية اليمنى
3. **اختر "Web Service"**
4. **ربط مستودع GitHub:**
   - اختر "GitHub"
   - اختر المستودع `kreen`
   - اختر الفرع `main`

### 3️⃣ إعدادات الخدمة

| الإعداد | القيمة |
|--------|--------|
| **Name** | kreen-app |
| **Environment** | Docker |
| **Plan** | Free (أو Paid حسب الاحتياج) |
| **Region** | Frankfurt (أو الأقرب لموقعك) |
| **Branch** | main |
| **Dockerfile** | ./Dockerfile |

### 4️⃣ متغيرات البيئة

انسخ جميع متغيرات البيئة من [متغيرات البيئة الإلزامية](#متغيرات-البيئة-الإلزامية) أعلاه.

⚠️ **هام:** تأكد من إدخال القيم الفعلية وليس النماذج!

### 5️⃣ التخزين الدائم (Persistent Storage)

هذا ضروري لحفظ قاعدة البيانات بين عمليات التشغيل:

1. في إعدادات الخدمة، انقر على **"Disks"**
2. اضغط على **"Add Disk"**
3. اسم المسار: `/mnt/data`
4. الحجم: **1 GB** (كافي للبدء)
5. انقر على **"Add"**

### 6️⃣ الاطلاق الأول

1. اضغط على **"Deploy"** أو **"Create"**
2. راقب السجلات (Logs) أثناء البناء والتشغيل
3. انتظر حتى يظهر ✅ "Your service is live"

## 📋 الفحوصات بعد النشر

### الاختبار 1: التحقق من الوصول
```bash
curl -I https://your-kreen-app.onrender.com
# يجب أن تحصل على HTTP 302 (redirect)
```

### الاختبار 2: تسجيل الدخول
- اذهب إلى `https://your-kreen-app.onrender.com/login.php`
- استخدم:
  - البريد: `admin@kreen.com`
  - كلمة المرور: `admin123`

### الاختبار 3: الخريطة والمراقبة
- في لوحة التحكم، يجب أن ترى:
  - عدد العملاء
  - عدد السائقين
  - عدد الطلبات المعلقة

### الاختبار 4: البريد الإلكتروني
- اختبر إعادة تعيين كلمة المرور
- تحقق من وصول البريد الإلكتروني

### الاختبار 5: التليجرام
- قم بإجراء عملية مريبة (مثل محاولة SQL Injection)
- يجب أن تستقبل تنبيهاً على Telegram

## 🔍 معالجة المشاكل الشائعة

### ❌ خطأ: "No such file or directory: /var/www/html/kreen.db"

**السبب:** قاعدة البيانات غير موجودة

**الحل:**
1. تأكد من أن `DB_PATH=/var/www/html/kreen.db` في المتغيرات
2. سيتم إنشاء الملف تلقائياً عند البداية
3. إذا استمرت المشكلة، أعد تشغيل الخدمة

### ❌ خطأ: "Connection refused" على منفذ 8080

**السبب:** خادم PHP لم يبدأ

**الحل:**
1. تحقق من السجلات: `docker logs <container_id>`
2. تأكد من أن جميع مكتبات PHP مثبتة
3. أعد بناء الصورة: `git push` لتشغيل CI/CD

### ❌ لا تصل الرسائل البريدية

**السبب:** بيانات SMTP غير صحيحة

**الحل:**
1. تحقق من بيانات Gmail (قد تحتاج App Password، ليس كلمة المرور العادية)
2. تفعّل "Access for less secure apps" إذا لزم الحال
3. اختبر الاتصال: 
```bash
telnet smtp.gmail.com 587
```

### ❌ التليجرام لا يرسل التنبيهات

**السبب:** Token أو Chat ID غير صحيح

**الحل:**
1. تحقق من `TELEGRAM_TOKEN` لا يحتوي على مسافات إضافية
2. اختبر مع bot Father: `/start`
3. احصل على Chat ID من `/userinfobot`
4. أعد تشغيل الخدمة

## 📊 مراقبة الأداء

### عرض السجلات المباشرة
في Render Dashboard:
1. اختر الخدمة `kreen-app`
2. انقر على **"Logs"**
3. انقر على **"Live"** لرؤية السجلات الحقيقية

### نقاط الفحص المهمة
- ✅ HTTP Response Time < 1000ms
- ✅ Database Connection Time < 100ms
- ✅ Memory Usage < 500MB
- ✅ CPU Usage < 50% في الوقت العادي

## 🔄 التحديثات والنشر الآلي

بمجرد ربط Render مع GitHub:
- كل دفع (push) إلى `main` سينشر تلقائياً
- السحب يستغرق 5-10 دقائق عادة
- في حالة الخطأ، سترى ❌ في لوحة التحكم

## 🔐 نصائح الأمان

- ✅ غيّر `ADMIN_PASSWORD` إلى كلمة مرور قوية
- ✅ استخدم HTTPS فقط (Render يفعل هذا تلقائياً)
- ✅ حدّث جميع المكتبات دورياً
- ✅ فعّل Telegram للتنبيهات الأمنية
- ✅ غيّر كلمة المرور الافتراضية في الحساب الإداري

## 💾 النسخ الاحتياطية

نصيحة مهمة: يجب حفظ نسخة احتياطية دورية من قاعدة البيانات:

```bash
# تحميل النسخة الاحتياطية من Render
render-download-disk kreen-app /mnt/data/kreen.db

# أو يدويًا من Render Dashboard:
# 1. اذهب إلى Disk
# 2. انقر على Download
```

## 📞 الدعم والمساعدة

إذا واجهت مشكلة:

1. **تحقق من السجلات** - اذهب إلى Logs في Render Dashboard
2. **اقرأ رسائل الخطأ بانتباه** - غالباً توضح المشكلة
3. **جرّب إعادة التشغيل** - اضغط على "Redeploy" من Dashboard
4. **اتصل بـ Render Support** - لديهم فريق دعم جيد

---

**حالة التطبيق:** ✅ جاهز للإنتاج

**آخر تحديث:** يوليو 2026
