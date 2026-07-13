# 🎯 ملخص الضبط الكامل - كيفية تشغيل جدار الحماية (WAF)

## ✅ ما تم إنجازه

### ✓ تم تحضير البنية التالية:

```
🌐 Nginx (البوابة الأمامية)
    ↓
🛡️ WAF (جدار الحماية) ← جميع الطلبات تمر هنا
    ├─ فحص SQL Injection
    ├─ فحص XSS
    ├─ فحص Command Injection
    ├─ فحص DDoS
    └─ إرسال تنبيهات Telegram
    ↓
💻 PHP Backend (معالج الطلبات)
    ↓
✅ العميل يستقبل الاستجابة
```

---

## 🚀 خطوات التشغيل السريعة (5 دقائق فقط)

### الخطوة 1️⃣: إعداد Telegram Bot

**هذه خطوة لمرة واحدة فقط!**

```bash
# 1. افتح Telegram واذهب إلى @BotFather
# 2. أرسل: /newbot
# 3. اتبع التعليمات واحصل على TOKEN
# 4. استخدم @userinfobot للحصول على CHAT_ID
```

### الخطوة 2️⃣: تحديث ملف .env

```bash
cd /home/kali/Videos/kreen

# افتح الملف
nano .env

# أضف أو عدّل:
TELEGRAM_TOKEN=5409174234:AAFS6dMgguqouiWAtBvXsdv6yEhN1D6n5gg
TELEGRAM_CHAT_ID=100886852
ENABLE_TELEGRAM=true

# احفظ: Ctrl+X ثم Y ثم Enter
```

### الخطوة 3️⃣: تشغيل التطبيق

```bash
cd /home/kali/Videos/kreen

# الطريقة الأولى: استخدام السكريبت السريع
bash quick-start.sh

# أو الطريقة الثانية: استخدام docker-compose
docker-compose down 2>/dev/null || true
docker-compose up -d --build
```

### الخطوة 4️⃣: التحقق من التشغيل

```bash
# فحص الحاويات
docker-compose ps

# يجب أن تُرى جميع الخدمات بحالة "Up"
```

### الخطوة 5️⃣: اختبار التطبيق

**في المتصفح:**
```
http://localhost:8001/login.php
```

**بيانات الدخول:**
- البريد: `admin@kreen.com`
- كلمة المرور: `admin123`

**تحقق من Telegram:**
- يجب أن تستقبل تنبيهات مثل:
  ```
  👤 دخول جديد للموقع
  🌐 عنوان IP: 127.0.0.1
  💻 نوع الجهاز: Desktop
  🌐 المتصفح: Chrome
  ```

---

## 📋 التحقق من أن جميع الخدمات تعمل

### فحص 1️⃣: WAF يعمل

```bash
# يجب أن ترى "UP"
docker-compose ps | grep gunicorn

# أو فحص المنفذ مباشرة
curl -s http://127.0.0.1:5000/ | head -20
```

### فحص 2️⃣: PHP يعمل

```bash
# فحص في السجلات
docker-compose logs php | tail -10

# يجب أن تُرى رسائل مثل "Listening on 127.0.0.1:8080"
```

### فحص 3️⃣: Nginx يعمل

```bash
# اختبر الوصول للتطبيق
curl -I http://127.0.0.1:8001/

# يجب أن تحصل على: HTTP/1.1 302 Found
```

### فحص 4️⃣: Telegram متصل

```bash
# افتح السجلات
docker-compose logs | grep -i telegram

# يجب أن تُرى رسائل مثل: "✅ Telegram alert sent"
```

---

## 🧪 اختبارات سريعة

### اختبار 1: طلب عادي

```bash
curl "http://127.0.0.1:8001/login.php"

# يجب أن يعود الصفحة بنجاح
```

### اختبار 2: اكتشاف SQL Injection

```bash
curl "http://127.0.0.1:8001/?id=1' OR '1'='1"

# يجب أن تستقبل تنبيه في Telegram:
# 🔍 نوع الهجوم: SQLi
```

### اختبار 3: اكتشاف XSS

```bash
curl "http://127.0.0.1:8001/?msg=<script>alert(1)</script>"

# يجب أن تستقبل تنبيه في Telegram:
# 🔍 نوع الهجوم: XSS
```

---

## 📊 مراقبة السجلات الحقيقية

```bash
# عرض جميع السجلات بالوقت الحقيقي
docker-compose logs -f

# عرض سجلات WAF فقط
docker-compose logs -f gunicorn

# عرض آخر 100 سطر
docker-compose logs --tail 100

# عرض سجلات مع تصفية
docker-compose logs | grep -i "attack\|injection\|alarm"
```

---

## ⚠️ معالجة الأخطاء الشائعة

### ❌ "Connection refused"

```bash
# الحل:
docker-compose restart
sleep 3
curl -I http://127.0.0.1:8001/
```

### ❌ لا تصل التنبيهات

```bash
# تحقق من أن TELEGRAM_TOKEN صحيح:
cat .env | grep TELEGRAM

# اختبر يدوياً:
curl -X POST "https://api.telegram.org/botTOKEN/sendMessage" \
  -d "chat_id=CHAT_ID&text=Test"
```

### ❌ "Internal Server Error"

```bash
# فحص سجلات PHP:
docker-compose logs php

# إعادة تشغيل:
docker-compose restart
```

---

## 📁 الملفات المهمة

| الملف | الوصف |
|------|-------|
| `docker-entrypoint.sh` | سكريبت البدء الرئيسي (يشغل جميع الخدمات) |
| `soc_manger/mine.py` | جدار الحماية (WAF) |
| `.env` | متغيرات البيئة (Telegram, SMTP, إلخ) |
| `docker-compose.yml` | تعريف الخدمات |
| `WAF_SETUP_GUIDE.md` | دليل كامل مفصل |
| `quick-start.sh` | سكريبت البدء السريع |

---

## 🎯 التدفق النهائي

```
1. طلب من المتصفح
   ↓
2. Nginx يستقبل على المنفذ 80
   ↓
3. Nginx يوجه إلى WAF على 127.0.0.1:5000
   ↓
4. WAF يفتش الطلب:
   ├─ فحص SQL Injection
   ├─ فحص XSS
   ├─ فحص DDoS
   └─ إرسال تنبيه Telegram (إن وجد هجوم)
   ↓
5. إذا كان آمن، يوجه إلى PHP على 127.0.0.1:8080
   ↓
6. PHP يعالج الطلب
   ↓
7. النتيجة تعود عبر نفس الطريق
   ↓
8. العميل يستقبل الاستجابة
```

**النتيجة:** جميع الطلبات محمية تلقائياً! ✅

---

## 📱 معلومات Telegram

### كيفية الحصول على Token و Chat ID؟

**للحصول على TELEGRAM_TOKEN:**
1. افتح Telegram
2. ابدأ محادثة مع @BotFather
3. أرسل: `/newbot`
4. اتبع التعليمات
5. ستحصل على Token يشبه:
   ```
   5409174234:AAFS6dMgguqouiWAtBvXsdv6yEhN1D6n5gg
   ```

**للحصول على TELEGRAM_CHAT_ID:**
1. افتح حساب الـ Bot الذي أنشأته
2. ابدأ محادثة: `/start`
3. ابدأ محادثة مع @userinfobot
4. سيرسل لك Chat ID الخاص بك

---

## 🔐 نقاط أمان مهمة

✅ جميع الطلبات تمر عبر WAF
✅ كل طلب يُفتش بحثاً عن الهجمات
✅ التنبيهات تُرسل فوراً إلى Telegram
✅ PHP لا يمكن الوصول إليه مباشرة من الإنترنت
✅ WAF لا يمكن الوصول إليه مباشرة من الإنترنت
✅ Nginx هو البوابة الوحيدة

---

## ✅ الخطوات المختصرة

```bash
# 1. عدّل .env بـ Telegram Token و Chat ID
nano .env

# 2. شغّل السكريبت السريع
bash quick-start.sh

# 3. افتح المتصفح
# http://localhost:8001

# 4. افتح Telegram وانتظر التنبيهات
```

**هذا كل شيء!** 🎉

---

**حالة التطبيق:** ✅ جاهز للإنتاج

**جميع الطلبات تمر عبر جدار الحماية والتنبيهات تُرسل تلقائياً!**
