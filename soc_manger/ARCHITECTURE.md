# 🏗️ معمارية النظام - كيفية عمل WAF مع موقعك

## 📌 الهيكل المعماري

```
┌──────────────────────────────────────────────────────────────────┐
│                          المستخدمين (الإنترنت)                   │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     ▼
        ┌───────────────────────────┐
        │    DNS Resolution         │
        │ waf.kreen.com/home.php    │
        └────────────┬──────────────┘
                     │
                     ▼
      ┌──────────────────────────────────┐
      │  Render WAF Instance             │
      │  (soc-waf.onrender.com)          │
      │                                  │
      │  ┌────────────────────────────┐ │
      │  │  mine.py (Flask App)       │ │
      │  │                            │ │
      │  │  ✓ فحص SQL Injection      │ │
      │  │  ✓ فحص XSS               │ │
      │  │  ✓ فحص Command Injection │ │
      │  │  ✓ اكتشاف DDoS           │ │
      │  │  ✓ كشف البروكسي/VPN      │ │
      │  │  ✓ فحص الملفات           │ │
      │  └────────────┬───────────────┘ │
      │               │                 │
      │               ▼                 │
      │  ┌────────────────────────────┐ │
      │  │  تنبيهات Telegram          │ │
      │  │  (إشعارات فورية)           │ │
      │  └────────────────────────────┘ │
      │               │                 │
      └───────────────┼─────────────────┘
                      │
        ┌─────────────▼─────────────┐
        │  إذا كان الطلب آمن:        │
        │  ✓ مرره إلى التطبيق       │
        └─────────────┬─────────────┘
                      │
                      ▼
      ┌──────────────────────────────────┐
      │  Render - App Instance           │
      │  (kreen.onrender.com)            │
      │                                  │
      │  ┌────────────────────────────┐ │
      │  │  home.php                  │ │
      │  │  (التطبيق الأصلي)           │ │
      │  └────────────────────────────┘ │
      └──────────────────────────────────┘
                      │
                      ▼
      ┌──────────────────────────────────┐
      │  إعادة الرد إلى المستخدم        │
      │  (محمي بواسطة WAF)               │
      └──────────────────────────────────┘
```

---

## 🔄 مراحل معالجة الطلب

### **المرحلة 1: وصول الطلب**
```
User → Domain DNS → WAF
كل طلب يذهب للـ WAF أولاً
```

### **المرحلة 2: التحليل**
```
WAF checks:
├─ هل الـ IP محظور؟ ❌ → حظر
├─ هل هجوم DDoS؟ ❌ → حظر
├─ هل بروكسي/VPN؟ ❌ → حظر (اختياري)
├─ هل فحص الملفات آمن؟ ❌ → حظر
├─ هل Payload يحتوي على هجوم؟ ❌ → حظر
└─ إذا آمن ✅ → مرره للتطبيق
```

### **المرحلة 3: التنبيه (اختياري)**
```
إذا اكتُشف هجوم:
WAF → Telegram Bot → حسابك على Telegram
✓ رسالة فورية بتفاصيل الهجوم
```

### **المرحلة 4: الحظر**
```
إذا كان الطلب خطيراً:
Attacker IP → سمعة تزداد
عند 5 مخالفات → حظر دائم
```

---

## 🌐 المسارات والـ URLs

### **المسارات المحمية:**
```
Proxy Enabled:
✓ / home.php
✓ /login.php
✓ /api/users
✓ أي رابط آخر
```

### **المسارات الإدارية (تحتاج كلمة مرور):**
```
Admin Only:
POST /api/unban/<IP>         - فك الحظر عن IP
GET  /api/visitors           - قائمة الزوار
POST /api/test-telegram      - اختبار التليجرام
```

### **مسارات عامة (بدون حماية):**
```
Public:
GET /api/health              - فحص صحة النظام
GET /api/stats               - الإحصائيات العامة
```

---

## 🔐 طبقات الأمان

```
┌─────────────────────────────────────────┐
│ الطبقة 1: IP-level Filtering            │
│ ├─ حظر IPs السيئة                       │
│ ├─ اكتشاف DDoS                          │
│ └─ كشف البروكسي/VPN                     │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│ الطبقة 2: Request Validation            │
│ ├─ فحص Headers                         │
│ ├─ فحص Query Parameters                │
│ └─ فحص Body/Payload                    │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│ الطبقة 3: Pattern Matching              │
│ ├─ SQL Injection Detection              │
│ ├─ XSS Detection                        │
│ ├─ Command Injection Detection          │
│ ├─ Path Traversal Detection             │
│ └─ Web Shell Detection                  │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│ الطبقة 4: File Upload Scanning          │
│ ├─ فحص الامتداد                         │
│ ├─ فحص Magic Bytes (التوقيع)           │
│ └─ منع الملفات التنفيذية               │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│ الطبقة 5: Rate Limiting                 │
│ ├─ 200 طلب/دقيقة                        │
│ ├─ 10000 طلب/ساعة                       │
│ └─ حظر تلقائي عند التجاوز              │
└─────────────────────────────────────────┘
                  ▼
✅ الطلب آمن → إرساله للتطبيق الأصلي
```

---

## 📊 مثال عملي: طلب SQL Injection

```
1. User makes request:
   GET /?id=1' OR '1'='1

2. WAF receives:
   ✓ Parse IP: 192.168.1.100
   ✓ Check if banned: No
   ✓ Check DDoS: Normal rate
   ✓ Analyze Payload: "1' OR '1'='1"

3. Pattern Matching:
   ✓ Matches regex: SQLi pattern found!
   ✓ Severity: CRITICAL

4. Action:
   ├─ Log: Critical - SQLi Attack from 192.168.1.100
   ├─ Alert: Send Telegram notification
   ├─ Ban: Increase IP reputation score
   │        (If score >= 5, add to permanent ban list)
   └─ Response: HTTP 403 Forbidden

5. User receives:
   {
     "error": "Access Denied",
     "message": "🛡️ Blocked: SQLi"
   }

6. Admin receives on Telegram:
   🚨 **تنبيه أمني [CRITICAL]**
   
   🛡️ **النظام:** SOC WAF v3.0
   🕐 **الوقت:** 2024-01-15 14:30:45
   🔍 **نوع الهجوم:** SQLi
   🌐 **عنوان الـ IP:** 192.168.1.100
   🔗 **الرابط:** /?id=1' OR '1'='1
```

---

## 🔧 مثال: تدفق البيانات الكامل

```
معطيات الإدخال:
├─ URL: https://waf.kreen.com/api/users?limit=10
├─ Method: GET
├─ Headers: { User-Agent: Mozilla..., Cookie: ... }
├─ Body: (فارغ)
└─ IP: 203.0.113.45

             ▼ (داخل WAF)

معالجة في mine.py:
├─ extract IP: 203.0.113.45
├─ check if banned: No
├─ detect DDoS: rate = 2/min (OK)
├─ check proxy/VPN: No
├─ collect payload:
│   limit=10, User-Agent, Headers, etc.
├─ check rules:
│   ├─ SQLi: No match ✓
│   ├─ XSS: No match ✓
│   ├─ Command: No match ✓
│   └─ Others: No match ✓
├─ upload files: No files ✓
└─ Result: SAFE ✅

             ▼ (التوجيه)

معالجة الطلب الآمن:
├─ Proxy enabled: Yes
├─ Path excluded: No
├─ Create upstream request:
│   URL: https://kreen.onrender.com/api/users?limit=10
│   Method: GET
│   Headers: Forward all headers
└─ Send to upstream

             ▼ (من التطبيق الأصلي)

إعادة الرد:
├─ Status: 200 OK
├─ Body: [{"id":1, "name":"Admin"}, ...]
├─ Headers: Forward all headers
└─ Send to user

             ▼ (المستخدم)

النتيجة النهائية:
✅ User receives: 
   [{"id":1, "name":"Admin"}, ...]
```

---

## 📈 حركة البيانات

```
في الثانية الواحدة:

Incoming Requests:
├─ Safe: 45 requests ✅
├─ Blocked (SQLi): 2 requests ❌
├─ Blocked (DDoS): 1 request ❌
├─ Blocked (XSS): 1 request ❌
└─ Blocked (Other): 1 request ❌

إحصائيات:
├─ Total IPs: 150 unique
├─ Banned IPs: 5
├─ Telegram alerts: 5 notifications
└─ Throughput: 48 requests/sec
```

---

## 🎯 الخلاصة

```
User Request
    ↓
WAF Security Layers
    ↓
    ├─ Safe → App
    │    ↓
    │ Process → Response
    │    ↓
    └── Return to User ✅
    │
    └─ Dangerous → Block & Alert
         ↓
      Error 403
         ↓
      Admin notified via Telegram
         ↓
      IP reputation increased
         ↓
      Potential ban (if >= threshold)
```

---

**الخلاصة:** كل طلب يمر بفحوصات أمان متعددة قبل الوصول لموقعك! 🛡️
