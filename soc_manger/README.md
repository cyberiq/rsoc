# 🛡️ SOC WAF v3.0 - جدار حماية متقدم

تطبيق جدار حماية ويب (Web Application Firewall) متقدم يحمي مواقعك من:
- هجمات SQL Injection
- هجمات XSS (Cross-Site Scripting)
- هجمات Command Injection
- هجمات Path Traversal
- هجمات LDAP و NoSQL
- هجمات XXE (XML External Entity)
- هجمات DDoS
- محاولات Brute Force
- اكتشاف Web Shells
- اكتشاف أدوات الاختراق (SQLmap, Nmap, إلخ)

## 🚀 المزايا الرئيسية

✅ **اكتشاف ذكي للهجمات** - قواعس متقدمة لاكتشاف مختلف أنواع الهجمات
✅ **تنبيهات فورية** - إرسال تنبيهات عبر التليجرام فوراً عند اكتشاف هجوم
✅ **حماية من DDoS** - اكتشاف واحتواء هجمات الإغراق بالطلبات
✅ **نظام سمعة الـ IP** - حظر تلقائي للـ IPs الخطيرة
✅ **تسجيل مفصل** - تسجيل جميع محاولات الاختراق
✅ **سهل التكامل** - يعمل مع أي تطبيق Flask

## 📋 المتطلبات

- Python 3.7+
- Flask
- flask-limiter
- requests
- python-dotenv

## 🔧 التثبيت

### 1. استنساخ المستودع
```bash
cd /home/kali/Documents/soc_manger
```

### 2. تثبيت المكتبات
```bash
pip install -r requirements.txt
```

### 3. تكوين التليجرام

#### الحصول على توكن البوت:
1. اذهب إلى [@BotFather](https://t.me/botfather)
2. أرسل `/newbot`
3. اتبع التعليمات واحفظ التوكن

#### الحصول على Chat ID:
1. اذهب إلى [@userinfobot](https://t.me/userinfobot)
2. سيخبرك بـ ID الخاص بك

### 4. تحديث ملف .env
```bash
nano .env
```

أضف بيانات التليجرام:
```
TELEGRAM_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_CHAT_ID=YOUR_CHAT_ID
ENABLE_TELEGRAM=True
ADMIN_PASSWORD=secure_pass_123
```

## 🚀 التشغيل

### تشغيل بسيط (للتطوير):
```bash
python mine.py
```

### تشغيل مع Gunicorn (للإنتاج):
```bash
gunicorn -w 4 -b 127.0.0.1:5000 mine:application
```

### تشغيل مع HTTPS (آمن):
```bash
gunicorn -w 4 -b 0.0.0.0:443 --certfile=cert.pem --keyfile=key.pem mine:application
```

## 📡 API Endpoints

### الصفحة الرئيسية
```
GET /
```
معلومات عن النظام والميزات

### الإحصائيات
```
GET /api/stats
```
عرض إحصائيات الهجمات والـ IPs المحظورة

### فك الحظر
```
POST /api/unban/<IP>
Content-Type: application/json
X-Admin-Password: your_strong_password

{
  "password": "your_strong_password"
}
```

### فحص الصحة
```
GET /api/health
```

### قائمة الزوار
```
GET /api/visitors
X-Admin-Password: your_strong_password
```

### اختبار التليجرام
```
POST /api/test-telegram
X-Admin-Password: your_strong_password
```

## 🔐 كيفية الاستخدام مع تطبيقك

### الطريقة 1: Reverse Proxy (Recommended)
ضع WAF أمام تطبيقك كـ Reverse Proxy:
```
المتصفح → WAF (port 5000) → تطبيقك (port 8000)
```

### الطريقة 2: Middleware
أضف WAF كـ Middleware في تطبيقك:
```python
from mine import waf_core
from flask import Flask

app = Flask(__name__)
app.before_request(waf_core)
```

### الطريقة 3: Docker
```dockerfile
FROM python:3.9
WORKDIR /app
COPY . .
RUN pip install -r requirements.txt
EXPOSE 5000
CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "mine:application"]
```

## 📊 الملفات المُنتجة

- `waf_security.log` - سجل جميع محاولات الاختراق

## ⚙️ التخصيص

### تعديل حدود الحماية
في `mine.py`:
```python
BAN_THRESHOLD = 5      # عدد المحاولات قبل الحظر
DDOS_THRESHOLD = 50    # عدد الطلبات قبل اعتبارها DDoS
```

### إضافة قاعسة جديدة
```python
RULES = {
    "YourRule": r"(?i)(pattern_here)",
}
```

## 🚨 مستويات الخطورة

- **LOW** ⚠️ - تحذيرات غير حرجة
- **MEDIUM** 🔴 - تحذيرات متوسطة
- **HIGH** 🚨 - تحذيرات خطيرة
- **CRITICAL** 🔥 - تحذيرات حرجة جداً

## 📝 السجلات

يتم حفظ جميع محاولات الاختراق في `waf_security.log`

عرض السجلات:
```bash
tail -f waf_security.log
```

البحث عن هجوم معين:
```bash
grep "SQLi" waf_security.log
```

## 🔍 استكشاف الأخطاء

### المشكلة: لا تأتي التنبيهات
- تأكد من `ENABLE_TELEGRAM=True`
- تأكد من صحة التوكن والـ Chat ID
- اختبر الاتصال: `python -c "import requests; requests.get('https://api.telegram.org/botTOKEN/getMe')"`

### المشكلة: الخادم بطيء
- ازد عدد workers في Gunicorn: `-w 8`
- استخدم caching

### المشكلة: أخطاء في الملفات المرفوعة
- تأكد من امتدادات الملفات المسموحة
- تحقق من صلاحيات المجلد

## 🛡️ أفضل الممارسات الأمان

1. غير كلمة المرور الإدارية في `.env`
2. استخدم HTTPS في الإنتاج
3. قم بتحديث القواعس بانتظام
4. راقب السجلات يومياً
5. قم بعمل backup للسجلات
6. استخدم جدار حماية إضافي على مستوى النظام

## 📞 الدعم والتطوير

لإضافة ميزات جديدة:
- قم بتعديل `mine.py`
- أضف قواعس جديدة
- اختبرها جيداً قبل النشر

## 📄 الترخيص

هذا المشروع للاستخدام الأمني والتعليمي فقط.

---

**آخر تحديث:** 2026-07-06
**الإصدار:** 3.0
