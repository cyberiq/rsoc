#!/bin/bash

# 🧪 اختبارات سريعة لـ WAF على Render
# انسخ الأوامر التالية مباشرة في Terminal

echo "═══════════════════════════════════════════════════════"
echo "🛡️  أوامر اختبار WAF السريعة"
echo "═══════════════════════════════════════════════════════"
echo ""

# ============================================
echo "📌 البيانات الخاصة بك:"
echo ""
echo "WAF URL:      https://soc-4w60.onrender.com"
echo "Kreen URL:    https://kreen.onrender.com/home.php"
echo "Admin Pass:   secure_pass_123"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# ============================================
echo "🧪 اختبار 1: فحص صحة WAF"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl --max-time 60 https://soc-4w60.onrender.com/api/health"
echo ""
echo "انسخ والصق الأمر أعلاه في Terminal ↑"
echo ""

# ============================================
echo "🧪 اختبار 2: إرسال تنبيه Telegram"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -X POST https://soc-4w60.onrender.com/api/test-telegram \\"
echo "  -H 'X-Admin-Password: secure_pass_123' --max-time 60"
echo ""
echo "انسخ والصق الأمر أعلاه في Terminal ↑"
echo "💡 تحقق من Telegram لرسالة الاختبار"
echo ""

# ============================================
echo "🧪 اختبار 3: عرض الإحصائيات"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -s -H 'X-Admin-Password: secure_pass_123' \\"
echo "  https://soc-4w60.onrender.com/api/stats --max-time 60"
echo ""
echo "انسخ والصق الأمر أعلاه في Terminal ↑"
echo ""

# ============================================
echo "🔒 اختبار 4: محاولة SQL Injection (يجب 403)"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -i \"https://soc-4w60.onrender.com/?id=1' OR '1'='1\" --max-time 60"
echo ""
echo "النتيجة المتوقعة: HTTP/1.1 403 Forbidden ✅"
echo ""

# ============================================
echo "🔒 اختبار 5: محاولة XSS (يجب 403)"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -i \"https://soc-4w60.onrender.com/?test=<script>alert('xss')</script>\" --max-time 60"
echo ""
echo "النتيجة المتوقعة: HTTP/1.1 403 Forbidden ✅"
echo ""

# ============================================
echo "📄 اختبار 6: طلب عادي آمن"
echo "─────────────────────────────────────────"
echo "الأمر:"
echo "curl -L https://soc-4w60.onrender.com/home.php --max-time 60 | head -20"
echo ""
echo "النتيجة المتوقعة: محتوى الصفحة ✅"
echo ""

# ============================================
echo "═══════════════════════════════════════════════════════"
echo ""
echo "💡 نصائح:"
echo ""
echo "1️⃣  أول طلب قد يستغرق 30-60 ثانية"
echo "2️⃣  استخدم --max-time 60 دائماً"
echo "3️⃣  الطلبات التالية ستكون أسرع"
echo "4️⃣  راقب الـ Logs على Render Dashboard"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""
echo "✅ كيفية الاستخدام:"
echo ""
echo "انسخ أي أمر من الأعلى وضعه في Terminal"
echo "مثال:"
echo ""
echo "  $ curl --max-time 60 https://soc-4w60.onrender.com/api/health"
echo ""
echo "═══════════════════════════════════════════════════════"
