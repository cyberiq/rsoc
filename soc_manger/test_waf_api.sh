#!/bin/bash

# 🧪 أوامر اختبار WAF السريعة
# استخدم هذا الملف لاختبار جميع الميزات

# اضبط هذا على رابطك
WAF_URL="https://soc-waf.onrender.com"
ADMIN_PASSWORD="secure_pass_123"

echo "🛡️ اختبار WAF - $WAF_URL"
echo "════════════════════════════════════════════"
echo ""

# 1. فحص صحة النظام
echo "✅ اختبار 1: فحص صحة النظام"
echo "أمر:"
echo "  curl $WAF_URL/api/health"
echo ""
echo "النتيجة:"
curl -s "$WAF_URL/api/health" | jq .
echo ""
echo "════════════════════════════════════════════"
echo ""

# 2. اختبار التليجرام
echo "✅ اختبار 2: اختبار التليجرام (تنبيه)"
echo "أمر:"
echo "  curl -X POST $WAF_URL/api/test-telegram \\"
echo "    -H 'X-Admin-Password: $ADMIN_PASSWORD'"
echo ""
echo "النتيجة:"
curl -s -X POST "$WAF_URL/api/test-telegram" \
  -H "X-Admin-Password: $ADMIN_PASSWORD" | jq .
echo ""
echo "✅ تحقق من Telegram (ستصل رسالة)"
echo ""
echo "════════════════════════════════════════════"
echo ""

# 3. عرض الإحصائيات
echo "✅ اختبار 3: عرض الإحصائيات"
echo "أمر:"
echo "  curl -H 'X-Admin-Password: $ADMIN_PASSWORD' $WAF_URL/api/stats"
echo ""
echo "النتيجة:"
curl -s -H "X-Admin-Password: $ADMIN_PASSWORD" "$WAF_URL/api/stats" | jq .
echo ""
echo "════════════════════════════════════════════"
echo ""

# 4. اختبار حماية SQL Injection
echo "⚠️ اختبار 4: محاولة SQL Injection (سيتم حظرها)"
echo "أمر:"
echo "  curl \"$WAF_URL/?id=1' OR '1'='1\""
echo ""
echo "النتيجة المتوقعة: 403 Forbidden"
echo ""
curl -s -i "$WAF_URL/?id=1' OR '1'='1" | head -15
echo ""
echo "════════════════════════════════════════════"
echo ""

# 5. اختبار حماية XSS
echo "⚠️ اختبار 5: محاولة XSS (سيتم حظرها)"
echo "أمر:"
echo "  curl \"$WAF_URL/?search=<script>alert('xss')</script>\""
echo ""
echo "النتيجة المتوقعة: 403 Forbidden"
echo ""
curl -s -i "$WAF_URL/?search=<script>alert('xss')</script>" | head -15
echo ""
echo "════════════════════════════════════════════"
echo ""

# 6. اختبار حماية Command Injection
echo "⚠️ اختبار 6: محاولة Command Injection (سيتم حظرها)"
echo "أمر:"
echo "  curl \"$WAF_URL/?cmd=cat /etc/passwd\""
echo ""
echo "النتيجة المتوقعة: 403 Forbidden"
echo ""
curl -s -i "$WAF_URL/?cmd=cat /etc/passwd" | head -15
echo ""
echo "════════════════════════════════════════════"
echo ""

# 7. اختبار Path Traversal
echo "⚠️ اختبار 7: محاولة Path Traversal (سيتم حظرها)"
echo "أمر:"
echo "  curl \"$WAF_URL/../../../etc/passwd\""
echo ""
echo "النتيجة المتوقعة: 403 Forbidden"
echo ""
curl -s -i "$WAF_URL/../../../etc/passwd" | head -15
echo ""
echo "════════════════════════════════════════════"
echo ""

# 8. طلب عادي (آمن) - لاختبار التوجيه
echo "✅ اختبار 8: طلب عادي آمن"
echo "أمر:"
echo "  curl \"$WAF_URL/home.php\""
echo ""
echo "النتيجة: سيتم توجيهه إلى https://kreen.onrender.com/home.php"
echo ""
curl -s -i "$WAF_URL/home.php" | head -20
echo ""
echo "════════════════════════════════════════════"
echo ""

echo "🎉 انتهت الاختبارات!"
echo ""
echo "📊 ملاحظات:"
echo "✅ الاختبارات 1, 2, 3, 8 يجب أن تعمل"
echo "❌ الاختبارات 4, 5, 6, 7 يجب أن ترجع 403"
echo ""
echo "💡 نصيحة: إذا لم تعمل اختبارات الهجوم، فهذا يعني الحماية تعمل! ✨"
