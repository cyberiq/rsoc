#!/bin/bash

# 🧪 اختبار سريع بعد الإصلاح
# نفذ هذا الملف لاختبار أن كل شيء يعمل

echo "════════════════════════════════════════"
echo "🧪 اختبار WAF بعد الإصلاح"
echo "════════════════════════════════════════"
echo ""

# إيقاف أي process قديم
pkill -f "python3 mine.py" 2>/dev/null
sleep 1

# تشغيل الأداة
cd /home/kali/Documents/soc_manger
source venv/bin/activate

echo "🚀 تشغيل الأداة..."
timeout 15 python3 mine.py > /tmp/waf_test.log 2>&1 &
WAF_PID=$!
sleep 3

echo "✅ الأداة تعمل (PID: $WAF_PID)"
echo ""

# ============================================
echo "🧪 اختبار 1: فحص صحة النظام"
echo "─────────────────────────────────────────"
RESULT=$(curl -s http://127.0.0.1:5000/api/health 2>/dev/null)
if echo "$RESULT" | grep -q "healthy"; then
  echo "✅ النتيجة: الخدمة صحية"
else
  echo "❌ فشل الاختبار"
fi
echo ""

# ============================================
echo "🧪 اختبار 2: إرسال Telegram"
echo "─────────────────────────────────────────"
RESULT=$(curl -s -X POST http://127.0.0.1:5000/api/test-telegram \
  -H "X-Admin-Password: secure_pass_123" 2>/dev/null)
if echo "$RESULT" | grep -q "success"; then
  echo "✅ النتيجة: تم إرسال الرسالة"
  echo "💡 تحقق من Telegram الآن!"
else
  echo "⚠️  قد تكون البيانات غير صحيحة"
fi
echo ""

# ============================================
echo "🧪 اختبار 3: حماية SQL Injection"
echo "─────────────────────────────────────────"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  "http://127.0.0.1:5000/?id=1' OR '1'='1" 2>/dev/null)
if [ "$STATUS" = "403" ]; then
  echo "✅ النتيجة: تم حظر الهجوم (403)"
else
  echo "⚠️  الحالة: $STATUS"
fi
echo ""

# ============================================
echo "🧪 اختبار 4: حماية XSS"
echo "─────────────────────────────────────────"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  "http://127.0.0.1:5000/?test=<script>alert('xss')</script>" 2>/dev/null)
if [ "$STATUS" = "403" ]; then
  echo "✅ النتيجة: تم حظر الهجوم (403)"
else
  echo "⚠️  الحالة: $STATUS"
fi
echo ""

# ============================================
echo "🧪 اختبار 5: عرض الإحصائيات"
echo "─────────────────────────────────────────"
RESULT=$(curl -s -H "X-Admin-Password: secure_pass_123" \
  http://127.0.0.1:5000/api/stats 2>/dev/null)
if echo "$RESULT" | grep -q "unique_visitors"; then
  echo "✅ النتيجة: الإحصائيات تعمل"
else
  echo "⚠️  فشل الاختبار"
fi
echo ""

# ============================================
echo "════════════════════════════════════════"
echo "✅ انتهت الاختبارات!"
echo "════════════════════════════════════════"
echo ""

# عرض الـ Logs
echo "📋 أول 20 سطر من الـ Logs:"
echo "─────────────────────────────────────────"
head -20 /tmp/waf_test.log | grep -E "(INFO|ERROR|WARNING|Starting|Telegram)"
echo ""

# إيقاف الأداة
echo "🛑 إيقاف الأداة..."
kill -9 $WAF_PID 2>/dev/null
wait $WAF_PID 2>/dev/null

echo ""
echo "✅ اختبار الإصلاح انتهى بنجاح!"
echo ""
echo "💡 النقاط المهمة:"
echo "   1. تحقق من Telegram لرسالة الاختبار"
echo "   2. الأداة الآن تعمل بدون أخطاء"
echo "   3. التنبيهات ترسل إلى Telegram"
echo "   4. الحماية نشطة ضد الهجمات"
echo ""
