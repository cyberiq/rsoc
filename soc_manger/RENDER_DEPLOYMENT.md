# 🚀 نشر WAF على Render

## 📋 المتطلبات
- حساب على [Render.com](https://render.com)
- حساب على [GitHub](https://github.com)
- Telegram Bot Token و Chat ID

---

## 🛠️ الخطوات

### **الخطوة 1: إنشاء Repository على GitHub**

```bash
cd /home/kali/Documents/soc_manger

# تهيئة Git (إذا لم يكن موجوداً)
git init
git config user.name "Your Name"
git config user.email "your-email@example.com"

# إضافة الملفات
git add .

# عمل Commit
git commit -m "Initial WAF deployment setup"

# إنشاء repository جديد على GitHub وعمل push
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/soc-waf.git
git push -u origin main
```

---

### **الخطوة 2: ربط Render مع GitHub**

1. اذهب إلى [render.com](https://render.com)
2. سجل دخول أو أنشئ حساب جديد
3. اضغط على **"New +"** → **"Web Service"**
4. اختر **"Build and deploy from a Git repository"**
5. اختر **GitHub** وادخل بيانات حسابك
6. اختر Repository `soc-waf`
7. امنح الوصول

---

### **الخطوة 3: إعدادات الخدمة**

في صفحة إنشاء الخدمة، ملء البيانات التالية:

| الحقل | القيمة |
|------|--------|
| **Name** | `soc-waf` |
| **Environment** | `Python 3` |
| **Region** | اختر أقرب منطقة |
| **Branch** | `main` |
| **Build Command** | `pip install -r requirements.txt` |
| **Start Command** | `gunicorn mine:application --workers 4 --timeout 30` |

---

### **الخطوة 4: متغيرات البيئة**

اضغط على **"Environment"** وأضف المتغيرات التالية:

```
FLASK_ENV=production
FLASK_DEBUG=False
PYTHONUNBUFFERED=1
TELEGRAM_TOKEN=5409174234:AAFS6dMgguqouiWAtBvXsdv6yEhN1D6n5gg
TELEGRAM_CHAT_ID=100886852
ENABLE_TELEGRAM=True
ADMIN_PASSWORD=secure_pass_123
DDOS_THRESHOLD=50
BAN_THRESHOLD=5
PROXY_ENABLED=True
UPSTREAM_URL=https://kreen.onrender.com
```

> **⚠️ تحذير:** لا تضع كلمات المرور الفعلية في الكود! استخدم متغيرات البيئة

---

### **الخطوة 5: النشر**

1. اضغط **"Create Web Service"**
2. انتظر عملية البناء والنشر (5-10 دقائق)
3. ستحصل على رابط مثل: `https://soc-waf.onrender.com`

---

## ✅ التحقق من عمل WAF

### **اختبار 1: فحص صحة النظام**
```bash
curl https://soc-waf.onrender.com/api/health
```

**الرد المتوقع:**
```json
{
  "status": "healthy",
  "waf": "enabled",
  "telegram": "enabled",
  "timestamp": "2024-..."
}
```

---

### **اختبار 2: اختبار التليجرام**

```bash
curl -X POST https://soc-waf.onrender.com/api/test-telegram \
  -H "X-Admin-Password: secure_pass_123" \
  -H "Content-Type: application/json"
```

**النتيجة:** ستستقبل رسالة اختبار على Telegram! ✅

---

### **اختبار 3: محاولة هجوم (سيتم حظره)**

```bash
# هجوم SQL Injection - سيتم حظره تلقائياً
curl "https://soc-waf.onrender.com/?id=1' OR '1'='1"
```

**الرد:**
```json
{
  "error": "Access Denied",
  "message": "🛡️ Blocked: SQLi"
}
```

---

## 🔗 ربط النطاق الخاص بك

إذا كان لديك نطاق مثل `waf.yoursite.com`:

1. اذهب إلى إعدادات الخدمة على Render
2. اضغط على **"Custom Domain"**
3. أضف النطاق الخاص بك
4. عدّل إعدادات DNS عند موفر النطاق:
   - أضف CNAME يشير إلى `soc-waf.onrender.com`

---

## 📊 مراقبة الأداء

- **Logs**: اذهب إلى **"Logs"** لمشاهدة جميع الطلبات والأخطاء
- **Metrics**: شاهد استهلاك الـ CPU والذاكرة
- **Redeploy**: لتحديث الخدمة اضغط **"Manual Deploy"**

---

## 🚨 استكشاف الأخطاء

### **Error 500 - Server Error**
- تحقق من الـ Logs على Render
- تأكد من وجود `application = app` في `mine.py`
- تحقق من متغيرات البيئة

### **Error 503 - Service Unavailable**
- قد يكون الموقع الأصلي معطل
- تحقق من `UPSTREAM_URL`

### **لا تصل التنبيهات**
- تحقق من `TELEGRAM_TOKEN` و `TELEGRAM_CHAT_ID`
- اختبر مع `/api/test-telegram`

---

## 📈 الخطوات التالية

1. **استخدم Redirect**: وجّه نطاقك القديم إلى WAF الجديد
2. **راقب الإحصائيات**: استخدم `/api/stats` لرؤية الهجمات المكتشفة
3. **ضبّن الـ Rules**: عدّل القواعس حسب احتياجاتك
4. **عمل Backup**: احفظ نسخة من الـ Logs بانتظام

---

## 💡 نصائح مهمة

✅ غير كلمة المرور الإدارية إلى شيء قوي  
✅ استخدم HTTPS دائماً (Render يوفره تلقائياً)  
✅ راقب الـ Logs يومياً للعثور على الهجمات  
✅ عدّل DDOS_THRESHOLD حسب حركة مرورك الطبيعية  

---

## 🎉 تم! WAF الآن محمي موقعك

كل الطلبات ستمر من خلال WAF قبل الوصول إلى الموقع الأصلي!
