# 🛡️ طرق حماية موقعك مع WAF

## 📌 الخيار 1: استخدام Render Domain (الأسهل) 🟢

### المراحل:
1. ✅ نشر WAF على Render → تحصل على رابط مثل `soc-waf.onrender.com`
2. 🔗 توجيه الزوار الجدد إلى هذا الرابط
3. 🔀 WAF يحمي الطلب ثم يمرره إلى `kreen.onrender.com`

### المميزات:
- سهل جداً ✅
- مجاني ✅
- تحديثات تلقائية ✅

### العيوب:
- الرابط غير احترافي (soc-waf.onrender.com)
- قد تحتاج لتحديث الروابط في الموقع القديم

---

## 🌐 الخيار 2: استخدام نطاقك الخاص (احترافي) 🔵

### المراحل:

#### أ) أنشئ Subdomain:
إذا كان لديك `kreen.com`:
- أضف subdomain: `waf.kreen.com`
- أشر إليه من Render

#### ب) تعديل DNS:
```
Host: waf
Type: CNAME
Value: soc-waf.onrender.com
TTL: 3600 أو Auto
```

#### ج) في Render:
- Settings → Custom Domain
- أضف `waf.kreen.com`
- انتظر التحقق (5-10 دقائق)

#### د) وجّه الزوار:
```
https://kreen.onrender.com/home.php
↓ (أضف Redirect)
https://waf.kreen.com/home.php
```

### المميزات:
- رابط احترافي ✅
- ينعكس على العلامة التجارية ✅
- سهل المراقبة ✅

---

## 🔒 الخيار 3: استخدام Cloudflare (الأقوى) 🟣

### الفوائد:
- حماية DDoS إضافية
- Caching عالمي
- SSL/TLS محسّن
- Analytics متقدم

### الخطوات:

#### 1. انقل النطاق إلى Cloudflare:
- اذهب إلى [cloudflare.com](https://cloudflare.com)
- أنشئ حساب
- أضف النطاق `kreen.com`
- انسخ Nameservers من Cloudflare
- عدّل Nameservers عند موفر النطاق القديم

#### 2. أعدادات DNS في Cloudflare:
```
Host: kreen
Type: CNAME
Value: soc-waf.onrender.com
TTL: Auto
Proxied: Orange Cloud
```

#### 3. إعدادات SSL:
- اذهب إلى SSL/TLS
- اختر "Full (strict)"

#### 4. Firewall Rules (اختياري):
```
(cf.threat_score > 50) → Block
(cf.bot_management.score < 30) → Challenge
```

---

## ⚙️ إعدادات DNS خطوة بخطوة

### **مثال 1: استخدام cPanel**

```
1. اذهب إلى Zone Editor
2. تحت domain.com، اختر "Edit"
3. أضف CNAME جديد:
   - Name: waf (أو @ للنطاق الرئيسي)
   - Type: CNAME
   - Value: soc-waf.onrender.com
4. احفظ
```

### **مثال 2: استخدام Namecheap**

```
1. اذهب إلى Dashboard
2. اختر domain.com → Manage
3. اذهب إلى Advanced DNS
4. أضف CNAME Record:
   - Type: CNAME
   - Host: waf
   - Value: soc-waf.onrender.com
   - TTL: 3600
5. احفظ
```

### **مثال 3: استخدام GoDaddy**

```
1. اذهب إلى My Domains
2. اختر domain.com → DNS
3. أضف Record:
   - Type: CNAME
   - Name: waf
   - Value: soc-waf.onrender.com
   - TTL: 3600
4. احفظ
```

---

## 🧪 اختبار الربط

بعد تعديل DNS (الانتظار 24 ساعة):

### اختبر مع nslookup:
```bash
nslookup waf.kreen.com
```

المتوقع:
```
Name: waf.kreen.com
Address: [IP الخاص بـ Render]
```

### اختبر مع curl:
```bash
curl -v https://waf.kreen.com/api/health
```

المتوقع:
```json
{
  "status": "healthy",
  "waf": "enabled"
}
```

---

## 🔄 إعادة توجيه المستخدمين

### **خيار 1: إعادة توجيه في الموقع (PHP)**
```php
<?php
// في home.php
header("Location: https://waf.kreen.com/home.php", true, 301);
exit;
?>
```

### **خيار 2: إعادة توجيه في .htaccess**
```apache
RewriteEngine On
RewriteCond %{HTTP_HOST} ^kreen\.com$ [OR]
RewriteCond %{HTTP_HOST} ^www\.kreen\.com$
RewriteRule ^(.*)$ https://waf.kreen.com$1 [L,R=301]
```

### **خيار 3: إعادة توجيه HTML**
```html
<meta http-equiv="refresh" content="0;url=https://waf.kreen.com/">
```

---

## 📊 مراقبة الحركة المرورية

### عرض الإحصائيات:
```bash
curl -H "X-Admin-Password: secure_pass_123" \
  https://waf.kreen.com/api/stats
```

### الرد:
```json
{
  "total_banned_ips": 2,
  "total_violations": 15,
  "unique_visitors": 45,
  "ddos_suspected": {},
  "top_attackers": [...],
  "proxy_vpn_hits": {...}
}
```

---

## 🚀 ملخص الخطوات النهائية

| الخطوة | الوصف | الوقت |
|--------|--------|--------|
| 1 | نشر WAF على Render | 10 دقائق |
| 2 | تعديل DNS | 5 دقائق |
| 3 | انتظار نشر DNS | 24 ساعة |
| 4 | اختبار الاتصال | 5 دقائق |
| 5 | إعادة توجيه المستخدمين | 5 دقائق |

---

## ❌ استكشاف الأخطاء

### "Connection refused"
- ✅ تحقق من أن Render Service نشط
- ✅ تحقق من UPSTREAM_URL

### "Timeout"
- ✅ قد يكون الموقع الأصلي معطل
- ✅ زد timeout في WAF

### DNS لا يعمل
- ✅ انتظر 24 ساعة
- ✅ امسح DNS Cache: `ipconfig /flushdns` (Windows) أو `sudo dscacheutil -flushcache` (Mac)

---

## 💡 نصائح أمان إضافية

1. **استخدم Strong ADMIN_PASSWORD**
   ```bash
   openssl rand -base64 32
   ```

2. **فعّل Rate Limiting**
   - غيّر DDOS_THRESHOLD حسب حركتك

3. **راقب الـ Logs يومياً**
   ```bash
   tail -f waf_security.log
   ```

4. **عدّل القواعس حسب احتياجاتك**
   - أضف قواعس جديدة للـ "False Positives"

---

🎉 **تم! موقعك الآن محمي بـ WAF قوي!**
