#!/bin/bash
# ============================================================================
# تطبيق كرين - سكريبت التشغيل المتقدم (Startup Script)
# ============================================================================
# جميع الطلبات تفلتر عبر جدار الحماية (WAF) على 127.0.0.1:5000
# WAF يفتش كل طلب ويرسل التنبيهات إلى Telegram تلقائياً

set -e
cd /var/www/html

# ============================================================================
# 📋 تهيئة البيئة
# ============================================================================
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          تطبيق كرين - بدء التشغيل المتقدم                    ║"
echo "║    جميع الطلبات تمر عبر جدار الحماية (WAF) تلقائياً          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# قراءة متغيرات البيئة
if [ -f /var/www/html/.env ]; then
    export $(cat /var/www/html/.env | grep -v '^#' | xargs)
    echo "✅ تم تحميل ملف .env"
fi

# ============================================================================
# 📂 الخطوة 1: إنشاء المجلدات والملفات الضرورية
# ============================================================================
echo ""
echo "📂 تهيئة المجلدات..."
mkdir -p /tmp/php_sessions /var/www/html/uploads /var/www/html/logs /var/www/html/sessions /var/log/php
chmod 1777 /tmp/php_sessions
chmod 755 /var/www/html/uploads /var/www/html/logs /var/www/html/sessions
echo "✅ المجلدات جاهزة"

# ============================================================================
# 🗄️  الخطوة 2: فحص قاعدة البيانات
# ============================================================================
echo ""
echo "🗄️  فحص قاعدة البيانات..."
DB_PATH="${DB_PATH:-/var/www/html/kreen.db}"
if [ ! -f "$DB_PATH" ]; then
    echo "📌 إنشاء قاعدة بيانات جديدة..."
    touch "$DB_PATH"
    chmod 666 "$DB_PATH"
    echo "✅ تم إنشاء قاعدة البيانات: $DB_PATH"
else
    echo "✅ قاعدة البيانات موجودة"
fi

# ============================================================================
# 🛡️  الخطوة 3: تشغيل WAF (جدار الحماية)
# ============================================================================
echo ""
echo "🛡️  بدء جدار الحماية (WAF)..."
cd /var/www/html/soc_manger

if [ ! -f "mine.py" ]; then
    echo "❌ خطأ: ملف mine.py غير موجود!"
    exit 1
fi

# تفعيل بيئة Python
source /opt/venv/bin/activate

# بدء WAF
/opt/venv/bin/gunicorn \
    --workers 2 \
    --worker-class sync \
    --bind 127.0.0.1:5000 \
    --timeout 60 \
    --access-logfile /var/log/gunicorn-access.log \
    --error-logfile /var/log/gunicorn-error.log \
    --log-level info \
    mine:application >/var/log/gunicorn.log 2>&1 &

WAF_PID=$!
echo "✅ WAF يعمل على 127.0.0.1:5000 (PID: $WAF_PID)"
sleep 2

if ! kill -0 $WAF_PID 2>/dev/null; then
    echo "❌ فشل تشغيل WAF!"
    tail -20 /var/log/gunicorn.log
    exit 1
fi

# ============================================================================
# 💻 الخطوة 4: تشغيل خادم PHP
# ============================================================================
echo ""
echo "💻 بدء خادم PHP على 127.0.0.1:8080..."
cd /var/www/html

php \
    -d session.save_path=/tmp/php_sessions \
    -d display_errors=0 \
    -d log_errors=1 \
    -d error_log=/var/log/php-error.log \
    -d memory_limit=256M \
    -S 127.0.0.1:8080 \
    -t /var/www/html >/var/log/php-server.log 2>&1 &

PHP_PID=$!
echo "✅ PHP يعمل على 127.0.0.1:8080 (PID: $PHP_PID)"
sleep 2

if ! kill -0 $PHP_PID 2>/dev/null; then
    echo "❌ فشل تشغيل PHP!"
    tail -20 /var/log/php-server.log
    exit 1
fi

# ============================================================================
# 🌐 الخطوة 5: تكوين Nginx
# ============================================================================
echo ""
echo "🌐 تكوين خادم Nginx..."
rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default

cat > /etc/nginx/conf.d/default.conf <<'EOF'
upstream waf_backend {
    server 127.0.0.1:5000 max_fails=3 fail_timeout=30s;
    keepalive 32;
}

upstream php_backend {
    server 127.0.0.1:8080 max_fails=3 fail_timeout=30s;
    keepalive 32;
}

server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    client_max_body_size 10M;
    
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    gzip on;
    gzip_types text/plain text/css text/javascript application/json;
    gzip_min_length 1000;

    location / {
        proxy_pass http://waf_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        proxy_pass http://php_backend;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
EOF

# فحص الإعدادات
if ! nginx -t 2>&1 | grep -q "successful"; then
    echo "❌ خطأ في إعدادات Nginx!"
    nginx -t
    exit 1
fi
echo "✅ إعدادات Nginx صحيحة"

# تشغيل Nginx
nginx -g 'daemon off;' &
NGINX_PID=$!
echo "✅ Nginx يعمل على 0.0.0.0:80 (PID: $NGINX_PID)"
sleep 2

# ============================================================================
# ✅ الخطوة 6: عرض حالة الخدمات
# ============================================================================
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                  ✅ جميع الخدمات تعمل بنجاح!                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 نقاط الوصول:"
echo "   └─ التطبيق الرئيسي: http://localhost:80 ← جميع الطلبات تمر هنا"
echo "   └─ جدار الحماية (WAF): 127.0.0.1:5000 (فقط داخلي)"
echo "   └─ خادم PHP: 127.0.0.1:8080 (فقط داخلي)"
echo ""

# ============================================================================
# 📱 معلومات Telegram
# ============================================================================
if [ -n "$TELEGRAM_TOKEN" ] && [ -n "$TELEGRAM_CHAT_ID" ]; then
    echo "📱 إعدادات Telegram:"
    echo "   ✅ التنبيهات الأمنية ENABLED"
    echo "   └─ ستستقبل:"
    echo "      • كل طلب جديد للموقع"
    echo "      • محاولات الهجمات (SQL Injection, XSS, إلخ)"
    echo "      • محاولات حظر IP"
    echo "      • هجمات DDoS"
    echo ""
else
    echo "⚠️  تحذير: Telegram غير معد!"
    echo "   └─ أضف TELEGRAM_TOKEN و TELEGRAM_CHAT_ID إلى .env"
    echo ""
fi

# ============================================================================
# 🔄 معالجات الإشارات
# ============================================================================
trap 'echo ""; echo "⏹️  إيقاف الخدمات..."; 
      kill $PHP_PID $WAF_PID $NGINX_PID 2>/dev/null || true;
      wait $PHP_PID $WAF_PID $NGINX_PID 2>/dev/null || true;
      echo "✅ تم إيقاف جميع الخدمات";
      exit 0' SIGTERM SIGINT

echo "⏹️  لإيقاف الخدمات: اضغط Ctrl+C"
echo ""
wait $NGINX_PID
