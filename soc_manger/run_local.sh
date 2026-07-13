#!/bin/bash

# 🧪 اختبار سريع لـ WAF قبل النشر على Render

echo "🧪 اختبار WAF بشكل محلي..."
echo ""

# التأكد من أن البيئة الافتراضية نشطة
if [ -z "$VIRTUAL_ENV" ]; then
    echo "⚠️  تفعيل البيئة الافتراضية..."
    source venv/bin/activate
fi

# التحقق من المكتبات
echo "📦 التحقق من المكتبات..."
pip list | grep -q "Flask" && echo "✅ Flask مثبت" || echo "❌ Flask غير مثبت"
pip list | grep -q "gunicorn" && echo "✅ Gunicorn مثبت" || echo "❌ Gunicorn غير مثبت"

echo ""
echo "🚀 تشغيل WAF محلياً..."
echo "⏳ اضغط Ctrl+C للإيقاف"
echo ""

export FLASK_ENV=development
export UPSTREAM_URL="https://kreen.onrender.com"
export PROXY_ENABLED="True"

python mine.py
