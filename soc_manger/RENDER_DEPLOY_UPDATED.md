# 🚀 دليل النشر على Render (محدث)

## ✅ ما تم إصلاحه

```
❌ الخطأ القديم:
   AttributeError: module 'ast' has no attribute 'Str'

✅ الحل المطبق:
   • Python 3.12.3 (بدل 3.11.0)
   • Flask 3.0.0 (بدل 2.3.0)
   • Werkzeug 3.0.0 (بدل 2.3.0)
```

---

## 📋 الخطوات اللازمة

### **الخطوة 1️⃣: التحديث المحلي**

```bash
cd ~/Documents/soc_manger
source venv/bin/activate

# تحديث المكتبات
pip install -r requirements.txt --upgrade
```

**النتيجة المتوقعة:**
```
Successfully installed Flask-3.0.0
Successfully installed werkzeug-3.0.0
```

---

### **الخطوة 2️⃣: اختبار محلي**

```bash
python3 mine.py
```

**يجب أن تشاهد:**
```
🚀 SOC WAF v3.0 Starting...
Telegram Alerts: Enabled
Running on http://127.0.0.1:5000
```

اختبر الصحة:
```bash
curl http://127.0.0.1:5000/api/health
```

**النتيجة:**
```json
{
  "status": "healthy",
  "waf": "enabled",
  "telegram": "enabled"
}
```

---

### **الخطوة 3️⃣: دفع التغييرات للـ Git**

```bash
cd ~/Documents/soc_manger
git add runtime.txt requirements.txt
git commit -m "Fix: Update Python to 3.12 and Flask/Werkzeug to latest versions"
git push
```

**ستشاهد:**
```
1 file changed: runtime.txt
1 file changed: requirements.txt
```

---

### **الخطوة 4️⃣: إعادة نشر على Render**

#### **الطريقة الأولى - Automatic Deployment:**
- Render سيعيد النشر **تلقائياً** عند كل `push`
- ستستغرق 2-3 دقائق

#### **الطريقة الثانية - Manual Deploy:**
1. اذهب إلى [Render Dashboard](https://dashboard.render.com)
2. اختر `soc-waf` service
3. اضغط زر **"Manual Deploy"**
4. اختر الـ branch الرئيسية (main/master)
5. اضغط **"Deploy"**

---

### **الخطوة 5️⃣: التحقق من النجاح**

بعد انتهاء النشر (2-3 دقائق)، اختبر:

```bash
# 1️⃣ فحص الصحة
curl https://soc-4w60.onrender.com/api/health

# 2️⃣ اختبر Telegram
curl -X POST https://soc-4w60.onrender.com/api/test-telegram \
  -H "X-Admin-Password: secure_pass_123"

# 3️⃣ اختبر الحماية من SQL
curl "https://soc-4w60.onrender.com/?id=1' OR '1'='1"
```

**النتائج المتوقعة:**
```
✅ 1️⃣ Health: HTTP 200 + JSON status
✅ 2️⃣ Telegram: Rسالة تصل إلى Telegram
✅ 3️⃣ SQL: HTTP 403 + Blocked message
```

---

## 📊 تحقق من الـ Logs

### **محلياً:**
```bash
tail -f waf_security.log
```

### **على Render:**
1. Render Dashboard → `soc-waf` → **Logs**
2. ستشاهد رسالة النجاح:
```
SOC WAF v3.0 Starting...
Telegram Alerts: Enabled
Running on http://0.0.0.0:5000
```

---

## 🆘 استكشاف الأخطاء

### **إذا كان الخطأ لا يزال موجوداً:**

1. **تحقق من Git:**
```bash
git log --oneline | head -3
```

يجب أن تشاهد آخر commit بتاريخ الساعة

2. **تحقق من Render Logs:**
- Render Dashboard → Logs
- ابحث عن `Error` أو `Failed`

3. **أعد النشر يدويًا:**
   - Manual Deploy (راجع الخطوة 4)

### **إذا استمر الخطأ:**
```
❌ AttributeError: module 'ast' has no attribute 'Str'
```

هذا يعني Render لم يستخدم `runtime.txt` بعد. حاول:

1. اضغط "Manual Deploy" في Render
2. انتظر 5 دقائق كاملة
3. اختبر مرة أخرى

---

## ✅ قائمة التحقق

- [ ] التحديثات المحلية تمت (pip install)
- [ ] اختبار محلي نجح
- [ ] Git push تم
- [ ] Render deployment انتهى
- [ ] /api/health يرد 200
- [ ] Telegram test نجح
- [ ] SQL test محجوب (403)

---

## 🎯 النتائج النهائية

```
✅ الأداة تعمل محلياً
✅ الأداة تعمل على Render
✅ Telegram يرسل الإشعارات
✅ الحماية تعمل بشكل مثالي
✅ جاهزة للإنتاج
```

---

## 📞 معلومات سريعة

| المعلومة | القيمة |
|---------|--------|
| **URL** | https://soc-4w60.onrender.com |
| **Python** | 3.12.3 ✅ |
| **Flask** | 3.0.0 ✅ |
| **Werkzeug** | 3.0.0 ✅ |
| **الحالة** | ✅ جاهز |

---

## 💾 تذكر

**هذه الملفات تم تحديثها:**
- ✅ `runtime.txt` - Python version
- ✅ `requirements.txt` - Dependencies

**لا تحتاج لتعديل:**
- ✅ `mine.py` - الكود الرئيسي
- ✅ `.env` - الإعدادات
- ✅ `Procfile` - أوامر التشغيل

---

**استمتع بالنشر الناجح!** 🚀

*آخر تحديث: 2026-07-13*
