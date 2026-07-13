# 🛡️ دليل تشغيل جدار الحماية (WAF) - تطبيق كرين

دليل شامل يشرح كيفية جعل كل الطلبات تمر عبر جدار الحماية وترسل التنبيهات إلى Telegram تلقائياً.

---

## 📋 الفهرس السريع

- [المتطلبات](#-المتطلبات)
- [البنية المعمارية](#-البنية-المعمارية)
- [خطوات التشغيل](#-خطوات-التشغيل)
- [ضبط Telegram](#-ضبط-telegram)
- [اختبار الوظائف](#-اختبار-الوظائف)
- [معالجة الأخطاء](#-معالجة-الأخطاء)
- [السجلات والمراقبة](#-السجلات-والمراقبة)

---

## ✅ المتطلبات

### قبل البدء، تأكد من توفر:

1. **ملف .env** معد بشكل صحيح
   ```bash
   cat /home/kali/Videos/kreen/.env
   ```

2. **حساب Telegram Bot** (للتنبيهات الأمنية)
   - أنشئ bot عبر @BotFather
   - احصل على `TELEGRAM_TOKEN` و `TELEGRAM_CHAT_ID`

3. **Docker و Docker Compose** مثبتان
   ```bash
   docker --version
   docker-compose --version
   ```

4. **صلاحيات التنفيذ على docker-entrypoint.sh**
   ```bash
   chmod +x /home/kali/Videos/kreen/docker-entrypoint.sh
   ```

---

## 🏗️ البنية المعمارية

```
📡 الطلبات من الإنترنت (Internet)
    ↓ (على المنفذ 80)
    ↓
🌐 Nginx (البوابة الأمامية)
    ↓ (proxy pass إلى 127.0.0.1:5000)
    ↓
🛡️ WAF - جدار الحماية (mine.py)
    ├─ فحص الطلب بحثاً عن الهجمات
    ├─ إرسال تنبيهات إلى Telegram
    ├─ تسجيل السجلات
    └─ توجيه الطلب الآمن إلى PHP
      ↓ (proxy pass إلى 127.0.0.1:8080)
      ↓
💻 PHP Backend (127.0.0.1:8080)
    ├─ معالجة الطلب
    └─ إرجاع الاستجابة
      ↓
🛡️ WAF يعدل الاستجابة (حسب الحاجة)
    ↓
🌐 Nginx يعيد الاستجابة للعميل
    ↓
✅ العميل يستقبل الاستجابة
```

**النقطة الأساسية:** جميع الطلبات تمر عبر WAF أولاً، لا استثناءات!

---

## 🚀 خطوات التشغيل

### الخطوة 1: التحقق من ملف .env

```bash
cd /home/kali/Videos/kreen
cat .env
```

**تأكد من أن هذه المتغيرات موجودة:**

```bash
APP_URL=http://localhost:8001
DB_PATH=/var/www/html/kreen.db
ENABLE_TELEGRAM=true
TELEGRAM_TOKEN=YOUR_BOT_TOKEN_HERE
TELEGRAM_CHAT_ID=YOUR_CHAT_ID_HERE
UPSTREAM_URL=http://127.0.0.1:8080
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

### الخطوة 2: إضافة Telegram Token و Chat ID

```bash
# إذا كانت قيم Telegram فارغة، أضفها:
nano .env

# ثم أضف/عدّل:
TELEGRAM_TOKEN=رقم_البوت_من_BotFather
TELEGRAM_CHAT_ID=رقم_الـ chat_id_من_userinfobot
ENABLE_TELEGRAM=true
```

### الخطوة 3: بناء Docker Image

```bash
cd /home/kali/Videos/kreen

# حذف الصورة القديمة إن وجدت
docker rmi kreen-app 2>/dev/null || true

# بناء صورة جديدة
docker build -t kreen-app .

# التحقق من بناء الصورة بنجاح
docker images | grep kreen-app
```

### الخطوة 4: تشغيل التطبيق

#### الخيار أ: استخدام Docker Compose (الأفضل)

```bash
cd /home/kali/Videos/kreen
docker-compose up -d

# التحقق من حالة الخدمات
docker-compose ps

# عرض السجلات
docker-compose logs -f
```

#### الخيار ب: استخدام Docker مباشرة

```bash
docker run -d \
  --name kreen-app \
  -p 8001:80 \
  -v $(pwd):/var/www/html \
  -v $(pwd)/kreen.db:/var/www/html/kreen.db \
  -e TELEGRAM_TOKEN="YOUR_TOKEN" \
  -e TELEGRAM_CHAT_ID="YOUR_CHAT_ID" \
  -e ENABLE_TELEGRAM="true" \
  --env-file .env \
  kreen-app
```

### الخطوة 5: التحقق من التشغيل

```bash
# فحص الحاويات
docker ps | grep kreen

# اختبار الوصول
curl -I http://127.0.0.1:8001

# يجب أن تحصل على: HTTP/1.1 302 Found
```

---

## 📱 ضبط Telegram

### الخطوة 1: إنشاء Telegram Bot

1. **افتح Telegram واذهب إلى @BotFather**
2. **أرسل الأمر:** `/newbot`
3. **اتبع التعليمات:**
   - اسم Bot مثل: `Kreen Security Bot`
   - username مثل: `kreen_security_bot`
4. **احصل على Token** (يبدو هكذا):
   ```
   5409174234:AAFS6dMgguqouiWAtBvXsdv6yEhN1D6n5gg
   ```

### الخطوة 2: الحصول على Chat ID

1. **افتح حساب البوت الذي أنشأته**
2. **ابدأ محادثة:** `/start`
3. **افتح رابط جديد في المتصفح:**
   ```
   https://api.telegram.org/botYOUR_TOKEN/getMe
   ```
   (استبدل YOUR_TOKEN بالـ token الخاص بك)
   
4. **أو استخدم @userinfobot:**
   - ابدأ محادثة مع `@userinfobot`
   - سيرسل لك معلوماتك تحتوي على `chat_id`

5. **أضف Chat ID إلى .env:**
   ```bash
   TELEGRAM_CHAT_ID=100886852
   ```

### الخطوة 3: اختبار الاتصال

```bash
# اختبر أن الـ Token صحيح
curl -I "https://api.telegram.org/botTOKEN/getMe"

# يجب أن تحصل على HTTP 200
```

### الخطوة 4: تفعيل التنبيهات في WAF

تأكد من أن هذه الأسطر موجودة في .env:

```bash
ENABLE_TELEGRAM=true
TELEGRAM_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_CHAT_ID=YOUR_CHAT_ID
```

ثم أعد بناء الحاوية:

```bash
docker-compose down
docker build -t kreen-app .
docker-compose up -d
```

---

## 🧪 اختبار الوظائف

### اختبار 1: تحقق من أن جميع الخدمات تعمل

```bash
# فحص حالة الخدمات
docker-compose ps

# يجب أن تُرى:
# kreen-app    Up
```

### اختبار 2: تحقق من أن WAF يستقبل الطلبات

```bash
# شغّل هذا الأمر وراقب السجلات
docker-compose logs -f gunicorn

# في نافذة أخرى، اصنع طلب:
curl -I http://127.0.0.1:8001/

# يجب أن ترى في السجلات:
# "Proxying GET / -> http://127.0.0.1:8080"
```

### اختبار 3: اختبر تنبيهات Telegram

```bash
# انتظر قليلاً بعد إعادة التشغيل
# ثم اذهب إلى دردشتك مع الـ Bot على Telegram

# يجب أن ترى تنبيهات مثل:
# "📲 طلب جديد للموقع"
# "🌐 عنوان IP: 127.0.0.1"
# "📍 الصفحة: /"
```

### اختبار 4: اختبر اكتشاف الهجمات

```bash
# اختبار SQL Injection
curl "http://127.0.0.1:8001/?id=1' OR '1'='1"

# يجب أن ترى تنبيه في Telegram:
# "🔍 نوع الهجوم: SQLi"

# اختبار XSS
curl "http://127.0.0.1:8001/?msg=<script>alert(1)</script>"

# يجب أن ترى تنبيه في Telegram:
# "🔍 نوع الهجوم: XSS"
```

### اختبار 5: اختبر تسجيل الدخول

```bash
# اذهب إلى المتصفح
http://127.0.0.1:8001/login.php

# استخدم بيانات مدير:
# البريد: admin@kreen.com
# كلمة المرور: admin123

# يجب أن تستقبل تنبيهات في Telegram:
# "👤 دخول جديد للموقع"
# "💻 نوع الجهاز: Desktop"
# "🌐 المتصفح: Chrome"
```

---

## 🔧 معالجة الأخطاء الشائعة

### ❌ مشكلة: "Connection refused" عند محاولة الوصول

**السبب:** WAF لم يبدأ بعد

**الحل:**
```bash
# فحص السجلات
docker-compose logs gunicorn

# إذا رأيت خطأ، أعد بناء الصورة
docker-compose down
docker build -t kreen-app --no-cache .
docker-compose up -d
```

---

### ❌ مشكلة: لا تصل التنبيهات إلى Telegram

**السبب المحتمل 1:** Token أو Chat ID غير صحيح

**الحل:**
```bash
# تحقق من القيم في .env
cat .env | grep TELEGRAM

# اختبر الاتصال يدوياً
curl -X POST "https://api.telegram.org/botYOUR_TOKEN/sendMessage" \
  -d "chat_id=YOUR_CHAT_ID&text=Test"

# يجب أن ترى رد إيجابي
```

**السبب المحتمل 2:** ENABLE_TELEGRAM ليست true

**الحل:**
```bash
# تأكد من ملف .env
echo "ENABLE_TELEGRAM=true" >> .env

# أعد بناء
docker-compose down
docker-compose up -d
```

---

### ❌ مشكلة: "File not found" error

**السبب:** قاعدة البيانات غير موجودة

**الحل:**
```bash
# أنشئ الملف يدوياً
touch /home/kali/Videos/kreen/kreen.db
chmod 666 /home/kali/Videos/kreen/kreen.db

# ثم أعد تشغيل
docker-compose restart
```

---

### ❌ مشكلة: "Upstream timeout"

**السبب:** PHP backend بطيء جداً

**الحل:**
```bash
# زيادة مهلة الانتظار في docker-entrypoint.sh
# ابحث عن "proxy_read_timeout" وزد القيمة

# أو فحص حالة PHP
docker-compose logs php

# إذا كانت هناك أخطاء، أعد تشغيل
docker-compose restart
```

---

## 📊 السجلات والمراقبة

### عرض السجلات الحقيقية

```bash
# عرض سجلات جميع الخدمات
docker-compose logs -f

# عرض سجلات WAF فقط
docker-compose logs -f gunicorn

# عرض سجلات PHP فقط
docker-compose logs -f | grep php

# عرض آخر 100 سطر
docker-compose logs --tail 100
```

### مراقبة استهلاك الموارد

```bash
# عرض استهلاك CPU والذاكرة
docker stats

# مراقبة مستمرة
docker stats --no-stream
```

### فحص الملفات مباشرة

```bash
# فحص سجل WAF
cat /var/log/gunicorn-error.log

# فحص سجل PHP
cat /var/log/php-error.log

# فحص سجل Nginx
cat /var/log/nginx/error.log

# البحث عن كلمة معينة
grep "SQL" /var/log/gunicorn-error.log
```

---

## 🎯 الخطوات الموصى بها لتجنب الأخطاء

### ✅ قبل كل تشغيل:

1. **التحقق من ملف .env**
   ```bash
   cat .env | grep -E "TELEGRAM|DB_PATH|UPSTREAM"
   ```

2. **التحقق من أن جميع الملفات موجودة**
   ```bash
   ls -la docker-entrypoint.sh Dockerfile docker-compose.yml
   ```

3. **التحقق من صلاحيات التنفيذ**
   ```bash
   ls -la docker-entrypoint.sh
   # يجب أن تُرى: -rwxr-xr-x
   ```

4. **إذا كانت الصلاحيات خاطئة:**
   ```bash
   chmod +x docker-entrypoint.sh
   ```

5. **حذف الحاويات القديمة**
   ```bash
   docker-compose down
   docker system prune -f
   ```

6. **البناء والتشغيل**
   ```bash
   docker-compose up -d --build
   ```

### ✅ بعد التشغيل:

1. **انتظر 5 ثوان** للتأكد من بدء جميع الخدمات

2. **فحص حالة الحاويات**
   ```bash
   docker-compose ps
   ```

3. **اختبر الوصول**
   ```bash
   curl -I http://127.0.0.1:8001/
   ```

4. **فحص السجلات**
   ```bash
   docker-compose logs --tail 50
   ```

5. **اختبر Telegram**
   - افتح الـ Bot على Telegram
   - أنتظر تنبيه
   - إذا لم يأتِ، فحص السجلات

---

## 📌 ملاحظات مهمة

### ⚠️ نقاط حرجة لا تنسها:

1. **WAF يعمل على المنفذ 5000 فقط** - لا يمكن الوصول من الخارج
2. **PHP يعمل على 127.0.0.1:8080** - لا يمكن الوصول من الخارج  
3. **Nginx هو البوابة الوحيدة** - جميع الطلبات تمر عليه
4. **جميع الطلبات تفلتر عبر WAF** - لا استثناءات

### ⚠️ عند التحديث:

```bash
# إذا عدّلت .env، أعد البناء:
docker-compose down
docker build -t kreen-app .
docker-compose up -d

# إذا عدّلت mine.py، أعد البناء:
docker-compose down
docker-compose up -d --build

# إذا عدّلت Dockerfile، أعد البناء:
docker-compose down
docker build -t kreen-app --no-cache .
docker-compose up -d
```

---

## 🎓 خلاصة التدفق

```
طلب من العميل
    ↓
البوابة: Nginx (80)
    ↓
جدار الحماية: WAF (5000)
  ├─ فحص الطلب
  ├─ تسجيل السجل
  ├─ إرسال تنبيه Telegram
  └─ (إذا كان آمن)
    ↓
معالج الطلب: PHP (8080)
  ├─ معالجة الطلب
  ├─ الوصول للقاعدة
  └─ إرجاع النتيجة
    ↓
عودة الاستجابة إلى العميل
```

---

**التطبيق جاهز الآن! ✅**

جميع الطلبات تمر عبر WAF تلقائياً وتُرسل التنبيهات إلى Telegram!
