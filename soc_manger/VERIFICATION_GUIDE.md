# ✅ دليل التحقق والمراقبة - WAF على Render

## 🎯 البيانات الخاصة بك

```
WAF URL:      https://soc-4w60.onrender.com
Kreen URL:    https://kreen.onrender.com
Admin Pass:   secure_pass_123
```

---

## 🔍 الخطوة 1: التحقق من لوحة تحكم Render

### المراقبة المباشرة:

1. اذهب إلى [render.com/dashboard](https://render.com/dashboard)
2. اختر الخدمة `soc-waf`
3. اضغط على **"Logs"** لرؤية السجلات المباشرة
4. ستشاهد الرسائل مثل:
   ```
   🚀 SOC WAF v3.0 Starting...
   Telegram Alerts: Enabled
   Starting on 0.0.0.0:10000
   ```

---

## 🧪 الاختبارات (مع توقيت أطول)

### **اختبار 1: فحص صحة النظام**

```bash
curl -s --max-time 30 https://soc-4w60.onrender.com/api/health
```

**النتيجة المتوقعة:**
```json
{
  "status": "healthy",
  "waf": "enabled",
  "telegram": "enabled",
  "timestamp": "2024-..."
}
```

---

### **اختبار 2: إرسال تنبيه Telegram**

```bash
curl -s -X POST https://soc-4w60.onrender.com/api/test-telegram \
  -H "X-Admin-Password: secure_pass_123" \
  -H "Content-Type: application/json" \
  --max-time 30
```

**ماذا تتوقع:**
- ✅ رسالة تنبيه تصل لـ Telegram فوراً
- ✅ تظهر رسالة اختبار في الـ Chat

---

### **اختبار 3: التوجيه للموقع الأصلي**

```bash
curl -v -L https://soc-4w60.onrender.com/home.php --max-time 30 | head -30
```

**النتيجة المتوقعة:**
- الطلب يمر من WAF
- يوجه إلى `kreen.onrender.com/home.php`
- ترجع محتوى الصفحة

---

### **اختبار 4: محاولة SQL Injection**

```bash
curl -i "https://soc-4w60.onrender.com/?id=1' OR '1'='1" --max-time 30
```

**النتيجة المتوقعة:**
```
HTTP/1.1 403 Forbidden

{
  "error": "Access Denied",
  "message": "🛡️ Blocked: SQLi"
}
```

---

### **اختبار 5: محاولة XSS**

```bash
curl -i "https://soc-4w60.onrender.com/?search=<script>alert('xss')</script>" --max-time 30
```

**النتيجة المتوقعة:**
```
HTTP/1.1 403 Forbidden

{
  "error": "Access Denied",
  "message": "🛡️ Blocked: XSS"
}
```

---

### **اختبار 6: محاولة Command Injection**

```bash
curl -i "https://soc-4w60.onrender.com/?cmd=cat%20/etc/passwd" --max-time 30
```

**النتيجة المتوقعة:**
```
HTTP/1.1 403 Forbidden
```

---

### **اختبار 7: عرض الإحصائيات**

```bash
curl -s -H "X-Admin-Password: secure_pass_123" \
  https://soc-4w60.onrender.com/api/stats --max-time 30
```

**النتيجة المتوقعة:**
```json
{
  "total_banned_ips": 2,
  "total_violations": 15,
  "unique_visitors": 45,
  "top_attackers": [...],
  "ddos_suspected": {},
  "proxy_vpn_hits": {...}
}
```

---

## 📊 مراقبة متقدمة

### **عرض الـ Logs مباشرة من Render:**

في Render Dashboard:
- اضغط على `soc-waf` service
- اضغط على **"Logs"**
- ستشاهد كل الأحداث:
  ```
  [CRITICAL] SQLi Attack from 192.168.1.100
  [CRITICAL] DDoS suspected from 203.0.113.45
  [WARNING] XSS attempt blocked
  [INFO] Request proxied successfully
  ```

---

### **تتبع الهجمات:**

```bash
# عرض الـ IPs المحظورة والهجمات
curl -s -H "X-Admin-Password: secure_pass_123" \
  https://soc-4w60.onrender.com/api/stats | grep -i "banned\|violations\|attacks"
```

---

## 🔧 استكشاف الأخطاء

### **المشكلة: الخادم بطيء جداً (Timeout)**

**السبب:** Render Free Tier يضع الخدمة في "sleep" بعد 15 دقيقة عدم استخدام

**الحل:**
1. الاستخدام الأول قد يستغرق 30 ثانية
2. استخدم `--max-time 60` في curl
3. بعد الاستخدام الأول، ستكون أسرع

---

### **المشكلة: 404 Not Found**

**السبب:** قد تكون الخدمة لم تنتهِ من البدء بعد

**الحل:**
1. تحقق من Logs على Render
2. انتظر 2-3 دقائق
3. جرّب `/api/health` أولاً

---

### **المشكلة: 502 Bad Gateway**

**السبب:** الموقع الأصلي (`kreen.onrender.com`) معطل أو غير متاح

**الحل:**
1. تحقق من أن `kreen.onrender.com` يعمل:
   ```bash
   curl https://kreen.onrender.com
   ```
2. إذا كان معطل، أصلحه أولاً
3. تحديث `UPSTREAM_URL` إذا لزم الأمر

---

### **المشكلة: 503 Service Unavailable**

**السبب:** متغيرات البيئة ناقصة أو غير صحيحة

**الحل:**
1. تحقق من Render Dashboard → Environment
2. تأكد من:
   - `UPSTREAM_URL=https://kreen.onrender.com`
   - `ADMIN_PASSWORD` موجود
   - `TELEGRAM_TOKEN` و `CHAT_ID` موجودان

---

## 📋 قائمة التحقق النهائية

- [ ] WAF يستجيب على `/api/health`
- [ ] Telegram test يرسل رسالة
- [ ] طلب SQL Injection يرجع 403
- [ ] طلب XSS يرجع 403
- [ ] طلب عادي يوجه للموقع الأصلي
- [ ] الإحصائيات تظهر بشكل صحيح
- [ ] الـ Logs تظهر على Render Dashboard
- [ ] الموقع الأصلي `kreen.onrender.com` يعمل

---

## 🚀 الخطوات التالية

### **اختياري 1: استخدم الرابط الجديد**

أخبر مستخدميك عن الرابط الجديد المحمي:
```
https://soc-4w60.onrender.com/home.php
```

---

### **اختياري 2: استخدم نطاقك الخاص**

إذا كان عندك نطاق `kreen.com`:

1. أضف subdomain: `waf.kreen.com`
2. في Render Dashboard:
   - Settings → Custom Domain
   - أضف `waf.kreen.com`
3. في DNS الخاص بك:
   - Type: CNAME
   - Name: waf
   - Value: soc-4w60.onrender.com

---

### **اختياري 3: راقب الإحصائيات**

أنشئ موقع خاص لعرض الإحصائيات:

```bash
# كل ساعة، عرض الإحصائيات
watch -n 3600 'curl -s -H "X-Admin-Password: secure_pass_123" https://soc-4w60.onrender.com/api/stats | jq .'
```

---

## 💡 نصائح مهمة

1. **أول استخدام قد يكون بطيء:**
   - Render يستيقظ الخدمة من sleep
   - استخدم `--max-time 60` أو `--connect-timeout 30`

2. **راقب الـ Logs بانتظام:**
   - شاهد Logs على Render Dashboard
   - ابحث عن هجمات أو تحذيرات

3. **غيّر كلمة المرور:**
   - `secure_pass_123` ضعيفة
   - غيّرها إلى شيء قوي!

4. **عدّل القواعس حسب احتياجاتك:**
   - إذا كان عندك false positives، عدّل الـ Rules
   - إذا كان عندك DDoS كثيرة، عدّل `DDOS_THRESHOLD`

---

## 📞 الدعم

**إذا حصلت على مشكلة:**

1. اقرأ الـ Logs على Render
2. اختبر `kreen.onrender.com` مباشرة
3. تحقق من متغيرات البيئة
4. اقرأ رسالة الخطأ بعناية

---

## 🎉 النتيجة النهائية

```
✅ WAF يحمي جميع الطلبات
✅ الهجمات يتم حظرها تلقائياً
✅ تنبيهات Telegram فورية
✅ إحصائيات شاملة
✅ لوحة إدارة قوية
```

**موقعك الآن محمي بـ 100%!** 🛡️

---

## 📞 الأوامر السريعة

```bash
# فحص صحة WAF
curl https://soc-4w60.onrender.com/api/health

# إرسال تنبيه تليجرام
curl -X POST https://soc-4w60.onrender.com/api/test-telegram \
  -H "X-Admin-Password: secure_pass_123"

# عرض الإحصائيات
curl -H "X-Admin-Password: secure_pass_123" \
  https://soc-4w60.onrender.com/api/stats

# اختبار حماية SQL
curl "https://soc-4w60.onrender.com/?id=1' OR '1'='1"

# اختبار حماية XSS
curl "https://soc-4w60.onrender.com/?test=<script>alert('xss')</script>"
```

---

*تم التحديث: 2024-01-15*
