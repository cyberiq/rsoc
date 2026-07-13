# 📁 دليل هيكل المشروع

## 🎯 الملفات الرئيسية

```
/home/kali/Documents/soc_manger/
│
├── 🔐 البيانات والإعدادات
│   ├── .env                    - إعدادات البيئة والبيانات الحساسة ⚠️
│   ├── .env.example            - نموذج .env بدون بيانات حساسة
│   ├── .gitignore              - ملفات يتم تجاهلها في Git
│   └── requirements.txt         - المكتبات المطلوبة
│
├── 🛡️ جدار الحماية
│   ├── mine.py                 - الملف الرئيسي (جدار الحماية الكامل)
│   ├── test_waf.py             - برنامج اختبار شامل
│   └── waf_security.log        - ملف السجلات (يُنشأ تلقائياً)
│
├── 🚀 النشر والتشغيل
│   ├── Makefile                - أوامر تسهيل التشغيل
│   ├── Dockerfile              - لبناء صورة Docker
│   ├── docker-compose.yml      - لتشغيل مع Docker Compose
│   ├── soc-waf.service         - خدمة Systemd للتشغيل
│   └── nginx.conf              - إعدادات Nginx للـ Reverse Proxy
│
└── 📚 التوثيق
    ├── README.md               - دليل الاستخدام الشامل
    ├── SUMMARY.md              - ملخص المشروع والمميزات
    ├── DEPLOYMENT.md           - دليل النشر على الخوادم
    └── this file               - دليل هيكل المشروع
```

---

## 📄 شرح كل ملف

### 1️⃣ **mine.py** (⭐ الملف الرئيسي)
**الوصف:** جدار الحماية الكامل بـ Flask
**الحجم:** ~350+ سطر
**الوظائف:**
- اكتشاف 10+ أنواع من الهجمات
- إرسال تنبيهات التليجرام
- حماية من DDoS
- نظام السمعة والحظر الذكي
- APIs للإدارة
- معالجة الأخطاء المخصصة

**الأوامر:**
```bash
python mine.py              # التشغيل البسيط
gunicorn -w 4 mine:application  # للإنتاج
```

---

### 2️⃣ **.env** (⚠️ حساس جداً)
**الوصف:** إعدادات البيئة والبيانات الحساسة
**المحتوى:**
```
TELEGRAM_TOKEN=...
TELEGRAM_CHAT_ID=...
ADMIN_PASSWORD=...
```

**⚠️ تحذير:**
- لا تضعه في Git!
- غيره مع كل نشر جديد
- احفظ نسخة احتياطية آمنة

---

### 3️⃣ **.env.example**
**الوصف:** نموذج .env آمن بدون بيانات حقيقية
**الاستخدام:** نسخه إلى .env وملأه بالبيانات الحقيقية

---

### 4️⃣ **requirements.txt**
**الوصف:** المكتبات المطلوبة
**المكتبات:**
- Flask - إطار العمل
- flask-limiter - تحديد معدل الطلبات
- requests - الطلبات HTTP
- python-dotenv - قراءة متغيرات البيئة
- gunicorn - خادم الويب

**التثبيت:**
```bash
pip install -r requirements.txt
```

---

### 5️⃣ **test_waf.py** (🧪 الاختبارات)
**الوصف:** برنامج اختبار شامل لجدار الحماية
**الاختبارات:**
- ✅ طلبات عادية
- ✅ SQL Injection
- ✅ XSS
- ✅ Path Traversal
- ✅ Command Injection
- ✅ Scanner Detection
- ✅ JSON Payloads

**التشغيل:**
```bash
python test_waf.py
```

---

### 6️⃣ **Dockerfile**
**الوصف:** لبناء صورة Docker للتطبيق
**الميزات:**
- Python 3.9 slim
- تثبيت تلقائي للمكتبات
- تشغيل آمن (بدون root)
- Gunicorn كخادم ويب

**الأوامر:**
```bash
docker build -t soc-waf .
docker run -p 5000:5000 soc-waf
```

---

### 7️⃣ **docker-compose.yml**
**الوصف:** لتشغيل التطبيق مع Nginx في حاويات منفصلة
**الخدمات:**
- WAF (الحماية)
- Nginx (الـ Reverse Proxy)
- App (التطبيق المحمي)

**الأوامر:**
```bash
docker-compose up -d
docker-compose ps
docker-compose logs -f waf
```

---

### 8️⃣ **nginx.conf**
**الوصف:** إعدادات Nginx لـ Reverse Proxy
**الميزات:**
- SSL/HTTPS
- Headers الأمان
- تحديد حجم الطلبات
- WebSocket support
- Admin panel منفصل

**التثبيت:**
```bash
sudo cp nginx.conf /etc/nginx/sites-available/soc-waf
sudo ln -s /etc/nginx/sites-available/soc-waf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

### 9️⃣ **soc-waf.service**
**الوصف:** خدمة Systemd لتشغيل WAF تلقائياً
**الميزات:**
- تشغيل تلقائي عند بدء النظام
- إعادة تشغيل تلقائية عند فشل
- حدود الموارد
- الأمان (بدون root)

**التثبيت:**
```bash
sudo cp soc-waf.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable soc-waf
sudo systemctl start soc-waf
```

---

### 🔟 **Makefile**
**الوصف:** تسهيل الأوامر الشائعة
**الأوامر الرئيسية:**
```bash
make install       # تثبيت المكتبات
make run           # التشغيل
make test          # الاختبارات
make docker-up     # Docker
make logs          # السجلات
make clean         # تنظيف
```

---

### 📖 **README.md**
**الوصف:** دليل الاستخدام الشامل
**المحتويات:**
- شرح المزايا
- المتطلبات
- التثبيت والتكوين
- طرق التشغيل
- شرح الـ APIs
- أفضل الممارسات الأمنية

---

### 📝 **SUMMARY.md** (⭐ مهم)
**الوصف:** ملخص شامل للمشروع
**المحتويات:**
- المميزات الجديدة
- قائمة الملفات
- طرق التشغيل
- الاختبارات
- الإحصائيات
- التطوير المستقبلي

---

### 🚀 **DEPLOYMENT.md**
**الوصف:** دليل النشر على الخوادم المختلفة
**طرق النشر:**
1. VPS/Dedicated Server
2. Docker
3. AWS EC2
4. DigitalOcean
5. Kubernetes

**المحتويات:**
- خطوات التثبيت
- إعدادات SSL
- المراقبة والصيانة
- استكشاف الأخطاء

---

### **.gitignore**
**الوصف:** ملفات يتم تجاهلها في Git
**يتم تجاهل:**
- .env (البيانات الحساسة) ⚠️
- __pycache__/
- *.log
- venv/
- .vscode/
- ssl/ (الشهادات)

---

## 🎯 دليل البدء السريع

### 1. التثبيت الأول
```bash
cd /home/kali/Documents/soc_manger
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 2. الإعداد
```bash
# نسخ الإعدادات
cp .env.example .env

# تحديث البيانات
nano .env
# أضف: TELEGRAM_TOKEN، TELEGRAM_CHAT_ID
```

### 3. التشغيل
```bash
# للتطوير
python mine.py

# للإنتاج
gunicorn -w 4 -b 0.0.0.0:5000 mine:application
```

### 4. الاختبار
```bash
# في نافذة أخرى
python test_waf.py
```

### 5. المراقبة
```bash
# عرض السجلات
tail -f waf_security.log
```

---

## 📊 الإحصائيات

| العنصر | القيمة |
|------|--------|
| عدد القواعس | 10+ |
| أنواع الهجمات | 10 |
| الملفات الرئيسية | 2 |
| الملفات الإضافية | 12 |
| أسطر الكود | 350+ |
| وقت التطوير | متقدم |

---

## 🔒 نقاط الأمان المهمة

### ✅ ما تم عمله:
- استخدام متغيرات البيئة للبيانات الحساسة
- إخفاء بيانات التليجرام
- كلمة مرور إدارية قابلة للتغيير
- تشغيل بدون root
- HTTPS support
- تسجيل مفصل

### ⚠️ ما يجب فعله:
1. غير الإعدادات الافتراضية في .env
2. استخدم HTTPS في الإنتاج
3. قم بتحديث المكتبات بانتظام
4. راقب السجلات يومياً
5. قم بعمل backup للسجلات
6. استخدم جدار حماية إضافي

---

## 🆘 حل المشاكل الشائعة

### المشكلة: "ModuleNotFoundError"
```bash
# الحل
pip install -r requirements.txt
```

### المشكلة: "Port already in use"
```bash
# البحث عن العملية
lsof -i :5000

# إيقاف العملية
kill -9 <PID>
```

### المشكلة: "Permission denied"
```bash
# الحل
chmod +x mine.py
sudo chown -R www-data:www-data /home/kali/Documents/soc_manger
```

---

## 📞 الدعم

- 📖 اقرأ README.md للمساعدة
- 🔍 تحقق من السجلات: `waf_security.log`
- 🐳 استخدم Docker للتطبيق الموثوق
- 📊 راقب الإحصائيات عبر `/api/stats`

---

**آخر تحديث:** 2026-07-06
**الإصدار:** 3.0
