# 🚀 ابدأ بسرعة (Quick Start)

## ⚡ 5 دقائق فقط للبدء

### خطوة 1️⃣: التثبيت (30 ثانية)
```bash
cd /home/kali/Documents/soc_manger
bash install.sh
```

### خطوة 2️⃣: التكوين (2 دقيقة)
```bash
# افتح ملف الإعدادات
nano .env

# أضف بيانات التليجرام (اختياري):
# TELEGRAM_TOKEN=YOUR_TOKEN_HERE
# TELEGRAM_CHAT_ID=YOUR_CHAT_ID_HERE
```

### خطوة 3️⃣: التشغيل (30 ثانية)
```bash
# في Terminal الأول
python mine.py

# يجب أن ترى:
# * Running on http://127.0.0.1:5000
```

### خطوة 4️⃣: الاختبار (1 دقيقة)
```bash
# في Terminal الثاني
python test_waf.py

# يجب أن ترى:
# ✅ جميع الاختبارات نجحت
```

### خطوة 5️⃣: الزيارة
```
http://localhost:5000/
http://localhost:5000/api/stats
http://localhost:5000/api/health
```

---

## 🎯 الاستخدام الأساسي

### تشغيل بسيط:
```bash
python mine.py
```

### تشغيل احترافي:
```bash
gunicorn -w 4 -b 0.0.0.0:5000 mine:application
```

### مع Docker:
```bash
docker-compose up -d
curl http://localhost:5000/
```

---

## 📡 الـ APIs المتاحة

### الصفحة الرئيسية
```bash
curl http://localhost:5000/
```

### الإحصائيات
```bash
curl http://localhost:5000/api/stats
```

### فحص الصحة
```bash
curl http://localhost:5000/api/health
```

### فك الحظر
```bash
curl -X POST http://localhost:5000/api/unban/192.168.1.100 \
  -H "X-Admin-Password: your_strong_password" \
  -H "Content-Type: application/json" \
  -d '{"password":"your_strong_password"}'
```

---

## 🧪 اختبار الحماية

### اختبار SQL Injection:
```bash
curl "http://localhost:5000/?q='+OR+'1'='1"
# النتيجة: 🛡️ Blocked: SQLi
```

### اختبار XSS:
```bash
curl "http://localhost:5000/?q=<script>alert('XSS')</script>"
# النتيجة: 🛡️ Blocked: XSS
```

### اختبار Command Injection:
```bash
curl "http://localhost:5000/?cmd=;+ls"
# النتيجة: 🛡️ Blocked: CommandInjection
```

---

## 📊 المراقبة

### عرض السجلات:
```bash
tail -f waf_security.log
```

### البحث عن هجمة معينة:
```bash
grep "SQLi" waf_security.log
```

### عد الهجمات:
```bash
wc -l waf_security.log
```

---

## 🛑 الإيقاف

### إيقاف التطبيق:
```bash
# في Terminal، اضغط Ctrl+C
```

### إيقاف Docker:
```bash
docker-compose down
```

---

## ⚙️ الأوامر المفيدة

```bash
# تثبيت المكتبات فقط
pip install -r requirements.txt

# اختبار الملف
python -m py_compile mine.py

# تنسيق الكود
black mine.py

# فحص الأخطاء
pylint mine.py

# عرض المساعدة
make help
```

---

## 🔐 الأمان

### تغيير كلمة المرور الإدارية:
```bash
nano .env
# غيّر: ADMIN_PASSWORD=your_strong_password
```

### تفعيل التنبيهات:
```bash
nano .env
# ضع: ENABLE_TELEGRAM=True
```

---

## ❓ الأسئلة الشائعة

**س: كيف أعرف أن كل شيء يعمل؟**
ج: شغّل `python test_waf.py`

**س: أين أجد السجلات؟**
ج: في ملف `waf_security.log` أو اضغط `tail -f waf_security.log`

**س: كيف أشغل جدار الحماية مع موقعي؟**
ج: اقرأ `DEPLOYMENT.md` لطرق التكامل

**س: كيف أضيف قاعدة جديدة؟**
ج: عدّل `RULES` في `mine.py`

---

## 📚 الملفات المهمة

| الملف | الهدف |
|------|-------|
| `mine.py` | جدار الحماية الرئيسي |
| `.env` | الإعدادات والبيانات الحساسة |
| `test_waf.py` | اختبار الحماية |
| `README.md` | الدليل الشامل |
| `DEPLOYMENT.md` | النشر على الخوادم |

---

## 🔧 تحتاج للمزيد؟

- 📖 **README.md** - دليل كامل
- 🚀 **DEPLOYMENT.md** - طرق النشر
- 📝 **SUMMARY.md** - ملخص المشروع
- 📁 **FILES_GUIDE.md** - شرح الملفات
- ❓ **هنا** - أسئلة سريعة

---

**استمتع بحماية موقعك! 🛡️**
