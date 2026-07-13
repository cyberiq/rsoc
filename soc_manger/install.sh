#!/bin/bash
# 🛡️ سكريبت تثبيت سريع لـ SOC WAF v3.0

echo "╔════════════════════════════════════════════╗"
echo "║  🛡️  SOC WAF v3.0 - سكريبت التثبيت السريع║"
echo "╚════════════════════════════════════════════╝"
echo ""

# التحقق من Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 غير مثبتة"
    echo "📦 التثبيت الموصى به:"
    echo "   Ubuntu/Debian: sudo apt install python3 python3-pip"
    exit 1
fi

echo "✅ Python3 موجودة: $(python3 --version)"
echo ""

# التحقق من pip
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 غير مثبتة"
    exit 1
fi

echo "✅ pip3 موجودة"
echo ""

# الدخول للمشروع
echo "📂 الانتقال إلى مجلد المشروع..."
cd "$(dirname "$0")" || exit

# إنشاء Virtual Environment
echo "🐍 إنشاء Virtual Environment..."
if [ -d "venv" ]; then
    echo "⚠️  مجلد venv موجود بالفعل"
else
    python3 -m venv venv
    echo "✅ تم إنشاء venv"
fi

# تفعيل Virtual Environment
echo "🔌 تفعيل Virtual Environment..."
source venv/bin/activate

# تحديث pip
echo "📦 تحديث pip..."
pip install --upgrade pip setuptools wheel > /dev/null 2>&1

# تثبيت المكتبات
echo "📚 تثبيت المكتبات المطلوبة..."
pip install -r requirements.txt

if [ $? -eq 0 ]; then
    echo "✅ تم تثبيت المكتبات بنجاح"
else
    echo "❌ فشل تثبيت المكتبات"
    exit 1
fi

echo ""

# إعداد .env
if [ ! -f ".env" ]; then
    echo "⚙️  إنشاء ملف .env..."
    cp .env.example .env
    echo "✅ تم إنشاء .env"
    echo ""
    echo "⚠️  تحتاج لتحديث البيانات في .env"
    echo "   nano .env"
    echo ""
else
    echo "✅ ملف .env موجود بالفعل"
    echo ""
fi

# التحقق من الملف الرئيسي
echo "🔍 فحص ملف mine.py..."
python3 -m py_compile mine.py
if [ $? -eq 0 ]; then
    echo "✅ ملف mine.py سليم"
else
    echo "❌ هناك مشكلة في ملف mine.py"
    exit 1
fi

echo ""
echo "╔════════════════════════════════════════════╗"
echo "║         ✅ تم إعداد المشروع بنجاح!        ║"
echo "╚════════════════════════════════════════════╝"
echo ""
echo "📝 الخطوات التالية:"
echo ""
echo "1️⃣  تحديث البيانات في .env:"
echo "   nano .env"
echo ""
echo "2️⃣  تشغيل جدار الحماية:"
echo "   python mine.py"
echo ""
echo "3️⃣  في نافذة أخرى - اختبار الحماية:"
echo "   python test_waf.py"
echo ""
echo "4️⃣  للمزيد من الأوامر:"
echo "   make help"
echo ""
echo "📚 للمزيد من المعلومات:"
echo "   - README.md - الدليل الشامل"
echo "   - SUMMARY.md - ملخص المشروع"
echo "   - DEPLOYMENT.md - دليل النشر"
echo ""
