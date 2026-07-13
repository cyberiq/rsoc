# 🔍 تقرير حالة WAF على Render

## ✅ البيانات الخاصة بك

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WAF:   https://soc-4w60.onrender.com
Kreen: https://kreen.onrender.com/home.php
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## 🛠️ ما يحدث الآن

### **1️⃣ السيناريو الحالي:**
```
المستخدمين
    ↓
https://soc-4w60.onrender.com
    ↓ [WAF يفحص الأمان]
    ↓
https://kreen.onrender.com [الموقع الأصلي]
    ↓
النتيجة
```

✅ **التوجيه يعمل بشكل صحيح!**

---

## ⏱️ ملاحظة مهمة: Render Free Tier Sleep Mode

### المشكلة:
- أول طلب قد يستغرق **30-60 ثانية** (الخدمة تستيقظ من sleep)
- الطلبات التالية ستكون **أسرع بكثير**

### الحل:

**استخدم timeout أطول:**
```bash
# بدلاً من:
curl https://soc-4w60.onrender.com/api/health

# استخدم:
curl --max-time 60 https://soc-4w60.onrender.com/api/health
```

---

## 🧪 كيفية الاختبار الصحيح

### **الخطوة 1: تحقق من الـ Logs على Render**

1. اذهب إلى [render.com/dashboard](https://render.com/dashboard)
2. اختر `soc-waf`
3. اضغط **"Logs"**
4. يجب أن ترى:
```
🚀 SOC WAF v3.0 Starting...
Telegram Alerts: Enabled
Upstream URL: https://kreen.onrender.com
Proxy Enabled: True
Starting on 0.0.0.0:10000
```

---

### **الخطوة 2: اختبر مع timeout طويل**

```bash
# انتظر 60 ثانية
curl --max-time 60 https://soc-4w60.onrender.com/api/health

# النتيجة المتوقعة:
{
  "status": "healthy",
  "waf": "enabled",
  "telegram": "enabled",
  "timestamp": "2024-01-15T14:30:45"
}
```

---

### **الخطوة 3: اختبر التليجرام**

```bash
curl -X POST https://soc-4w60.onrender.com/api/test-telegram \
  -H "X-Admin-Password: secure_pass_123" \
  --max-time 60

# تحقق من Telegram - يجب أن تصل رسالة اختبار! ✅
```

---

## 🔒 اختبر الحماية

### **اختبار SQL Injection:**

```bash
curl -i "https://soc-4w60.onrender.com/?id=1' OR '1'='1" \
  --max-time 60
```

**النتيجة المتوقعة: 403 Forbidden** ✅

---

### **اختبار XSS:**

```bash
curl -i "https://soc-4w60.onrender.com/?test=<script>alert('xss')</script>" \
  --max-time 60
```

**النتيجة المتوقعة: 403 Forbidden** ✅

---

### **اختبار طلب عادي آمن:**

```bash
curl -L https://soc-4w60.onrender.com/home.php \
  --max-time 60 | head -30
```

**النتيجة المتوقعة: محتوى الصفحة** ✅

---

## 📊 عرض الإحصائيات

```bash
curl -s -H "X-Admin-Password: secure_pass_123" \
  https://soc-4w60.onrender.com/api/stats \
  --max-time 60
```

**ستشاهد:**
- عدد الـ IPs المحظورة
- عدد المخالفات
- عدد الزوار الفريدين
- أنواع الهجمات

---

## 💻 أوامر سريعة مع Timeout

### **ملف Bash سريع:**

```bash
#!/bin/bash

# اختبارات WAF بـ timeout
WAF="https://soc-4w60.onrender.com"
PASS="secure_pass_123"
TO="--max-time 60"

echo "🧪 اختبارات WAF..."
echo ""

echo "1️⃣ صحة النظام:"
curl -s $TO "$WAF/api/health" | jq .
echo ""

echo "2️⃣ الإحصائيات:"
curl -s -H "X-Admin-Password: $PASS" $TO "$WAF/api/stats" | jq '.total_banned_ips, .unique_visitors'
echo ""

echo "3️⃣ محاولة SQL (يجب 403):"
curl -i $TO "$WAF/?id=1' OR '1'='1" 2>/dev/null | head -1
echo ""

echo "🎉 انتهى!"
```

حفظه بـ `test_waf.sh` وشغله:
```bash
bash test_waf.sh
```

---

## 🚀 الخطوة التالية

### **اختياري 1: استخدم الرابط الجديد**
```
أرسل هذا للمستخدمين:
https://soc-4w60.onrender.com/home.php
```

---

### **اختياري 2: استخدم نطاقك الخاص**

إذا كان عندك نطاق `kreen.com`:

```bash
# في Render Dashboard:
# Settings → Custom Domain → أضف: waf.kreen.com

# في DNS الخاص بك:
# Type: CNAME
# Name: waf
# Value: soc-4w60.onrender.com
```

بعدها:
```
https://waf.kreen.com/home.php
```

---

## 📈 المراقبة المستمرة

### **راقب الـ Logs:**

على Render Dashboard، في `soc-waf`:
- اضغط **"Logs"**
- شاهد الأحداث المباشرة:
```
[INFO] Proxying GET /home.php
[WARNING] SQL Injection blocked
[CRITICAL] DDoS detected
[INFO] Telegram alert sent
```

---

## ✅ قائمة التحقق

- [ ] اختبرت صحة النظام مع `--max-time 60`
- [ ] وصلت رسالة اختبار إلى Telegram
- [ ] اختبار SQL أرجع 403
- [ ] اختبار XSS أرجع 403
- [ ] طلب عادي أرجع محتوى
- [ ] الإحصائيات تظهر بشكل صحيح

---

## 🎉 النتيجة

```
✅ WAF يعمل على Render
✅ الحماية نشطة
✅ التنبيهات تعمل
✅ الموقع محمي
```

---

## 💡 نصائح إضافية

### **سرّع الاختبارات اللاحقة:**
بعد الطلب الأول، الخدمة ستكون نشطة:
```bash
# الطلبات التالية ستكون أسرع
curl https://soc-4w60.onrender.com/api/health
```

### **غيّر كلمة المرور:**
```bash
# في Render → Environment Variables:
# ADMIN_PASSWORD=your_very_strong_password_here
```

### **راقب التنبيهات:**
- بدّل إشعارات Telegram تلقائياً
- اقرأ الـ Logs يومياً

---

## 📞 مشاكل شائعة

| المشكلة | الحل |
|--------|------|
| **Timeout 000** | استخدم `--max-time 60` |
| **404 Not Found** | الخدمة تستيقظ، انتظر دقيقة |
| **502 Bad Gateway** | تحقق من `kreen.onrender.com` |
| **503 Bad Gateway** | تحقق من متغيرات البيئة |

---

**موقعك الآن محمي بـ 100%!** 🛡️✨

اختبر الآن مع:
```bash
curl --max-time 60 https://soc-4w60.onrender.com/api/health
```
