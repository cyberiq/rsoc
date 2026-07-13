#!/bin/bash

# 🧪 اختبار شامل لـ WAF المنشور على Render

WAF_URL="https://soc-4w60.onrender.com"
KREEN_URL="https://kreen.onrender.com"
ADMIN_PASS="secure_pass_123"

echo "═══════════════════════════════════════════════════════"
echo "🛡️  اختبار WAF على Render"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "🌐 WAF URL: $WAF_URL"
echo "🌐 موقع أصلي: $KREEN_URL/home.php"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# اختبار 1: فحص صحة النظام
echo "✅ الاختبار 1: فحص صحة WAF"
echo "─────────────────────────────────────────"
echo "الأمر: curl $WAF_URL/api/health"
echo ""
timeout 10 curl -s "$WAF_URL/api/health" || echo "❌ لا يوجد رد سريع"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# اختبار 2: اختبار التليجرام
echo "✅ الاختبار 2: اختبار إرسال تنبيه Telegram"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -X POST $WAF_URL/api/test-telegram \\"
echo "  -H 'X-Admin-Password: $ADMIN_PASS'"
echo ""
timeout 10 curl -s -X POST "$WAF_URL/api/test-telegram" \
  -H "X-Admin-Password: $ADMIN_PASS" -H "Content-Type: application/json" || echo "❌ لا يوجد رد سريع"
echo ""
echo "💡 تحقق من Telegram لرسالة الاختبار"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# اختبار 3: التوجيه للموقع الأصلي
echo "✅ الاختبار 3: اختبار التوجيه للموقع الأصلي"
echo "─────────────────────────────────────────"
echo "الأمر: curl -L $WAF_URL/home.php"
echo ""
echo "النتيجة (أول 20 سطر):"
timeout 10 curl -s -L "$WAF_URL/home.php" | head -20 || echo "❌ لا يوجد رد سريع"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# اختبار 4: محاولة SQL Injection
echo "⚠️  الاختبار 4: محاولة SQL Injection (يجب أن ترجع 403)"
echo "─────────────────────────────────────────"
echo "الأمر: curl \"$WAF_URL/?id=1' OR '1'='1\""
echo ""
echo "النتيجة المتوقعة: 403 Forbidden"
echo ""
RESULT=$(timeout 10 curl -s -w "\n%{http_code}" "$WAF_URL/?id=1' OR '1'='1" | tail -1)
echo "الحالة: $RESULT"
if [ "$RESULT" = "403" ]; then
  echo "✅ الحماية تعمل بشكل صحيح!"
else
  echo "⚠️  الحالة غير متوقعة"
fi
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# اختبار 5: محاولة XSS
echo "⚠️  الاختبار 5: محاولة XSS (يجب أن ترجع 403)"
echo "─────────────────────────────────────────"
echo "الأمر: curl \"$WAF_URL/?test=<script>alert('xss')</script>\""
echo ""
RESULT=$(timeout 10 curl -s -w "\n%{http_code}" "$WAF_URL/?test=<script>alert('xss')</script>" | tail -1)
echo "الحالة: $RESULT"
if [ "$RESULT" = "403" ]; then
  echo "✅ الحماية تعمل بشكل صحيح!"
else
  echo "⚠️  الحالة غير متوقعة"
fi
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# اختبار 6: عرض الإحصائيات
echo "✅ الاختبار 6: عرض الإحصائيات"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -H 'X-Admin-Password: $ADMIN_PASS' $WAF_URL/api/stats"
echo ""
timeout 10 curl -s -H "X-Admin-Password: $ADMIN_PASS" "$WAF_URL/api/stats" || echo "❌ لا يوجد رد سريع"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

echo "🎉 انتهت الاختبارات!"
echo ""
echo "📊 ملخص:"
echo "✅ اختبار 1: صحة النظام"
echo "✅ اختبار 2: تنبيهات Telegram"
echo "✅ اختبار 3: التوجيه للموقع الأصلي"
echo "✅ اختبار 4 & 5: الحماية من الهجمات"
echo "✅ اختبار 6: الإحصائيات"
echo ""
echo "═══════════════════════════════════════════════════════"
