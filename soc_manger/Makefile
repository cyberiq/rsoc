.PHONY: help install run test clean deploy docker-up docker-down logs

# المتغيرات
PYTHON := python3
PIP := pip3
PORT := 5000
WORKERS := 4

help:
	@echo "🛡️  SOC WAF v3.0 - شرح الأوامر المتاحة"
	@echo ""
	@echo "الأوامر الأساسية:"
	@echo "  make install      - تثبيت المكتبات"
	@echo "  make run          - تشغيل التطبيق (للتطوير)"
	@echo "  make run-prod     - تشغيل التطبيق (للإنتاج)"
	@echo "  make test         - اختبار جدار الحماية"
	@echo "  make clean        - حذف الملفات المؤقتة"
	@echo ""
	@echo "أوامر Docker:"
	@echo "  make docker-up    - بدء الحاويات"
	@echo "  make docker-down  - إيقاف الحاويات"
	@echo "  make logs         - عرض السجلات"
	@echo ""
	@echo "أوامر النشر:"
	@echo "  make deploy       - نشر على الخادم"
	@echo "  make deploy-docker - نشر على Docker"
	@echo ""

install:
	@echo "📦 تثبيت المكتبات..."
	$(PIP) install -r requirements.txt
	@echo "✅ تم التثبيت بنجاح"

run:
	@echo "🚀 تشغيل التطبيق (وضع التطوير)..."
	$(PYTHON) mine.py

run-prod:
	@echo "🚀 تشغيل التطبيق (وضع الإنتاج)..."
	gunicorn -w $(WORKERS) -b 0.0.0.0:$(PORT) --timeout 120 mine:application

test:
	@echo "🧪 اختبار جدار الحماية..."
	@if [ -z "$$(pgrep -f 'python.*mine.py')" ] && [ -z "$$(docker ps -q -f name=soc-waf)" ]; then \
		echo "❌ التطبيق لا يعمل!"; \
		echo "   قم بتشغيل: make run"; \
		exit 1; \
	fi
	$(PYTHON) test_waf.py

clean:
	@echo "🧹 تنظيف الملفات المؤقتة..."
	find . -type d -name "__pycache__" -exec rm -rf {} + 2>/dev/null || true
	find . -type f -name "*.pyc" -delete
	find . -type f -name "*.pyo" -delete
	find . -type f -name ".DS_Store" -delete
	@echo "✅ تم التنظيف"

logs:
	@echo "📊 عرض السجلات الحية..."
	tail -f waf_security.log

docker-build:
	@echo "🐳 بناء صورة Docker..."
	docker-compose build

docker-up:
	@echo "🚀 بدء الحاويات..."
	docker-compose up -d
	@echo "✅ الحاويات تعمل"
	docker-compose ps

docker-down:
	@echo "🛑 إيقاف الحاويات..."
	docker-compose down
	@echo "✅ تم الإيقاف"

docker-logs:
	@echo "📊 سجلات Docker:"
	docker-compose logs -f waf

docker-restart:
	@echo "🔄 إعادة تشغيل الحاويات..."
	docker-compose restart
	@echo "✅ تم إعادة التشغيل"

env-setup:
	@echo "⚙️  إعداد ملف .env..."
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "✅ تم إنشاء .env من .env.example"; \
		echo "⚠️  تأكد من تحديث البيانات في .env"; \
	else \
		echo "❌ ملف .env موجود بالفعل"; \
	fi

venv:
	@echo "🐍 إنشاء Virtual Environment..."
	$(PYTHON) -m venv venv
	@echo "✅ تم الإنشاء"
	@echo "🔗 فعّل: source venv/bin/activate"

lint:
	@echo "🔍 فحص الكود..."
	$(PYTHON) -m py_compile mine.py
	@echo "✅ لا توجد أخطاء بناء"

format:
	@echo "📝 تنسيق الكود..."
	$(PYTHON) -m black mine.py 2>/dev/null || echo "⚠️  black غير مثبتة (pip install black)"

security-check:
	@echo "🔒 فحص الأمان..."
	@if [ -f .env ]; then \
		grep -q "TELEGRAM_TOKEN" .env && echo "✅ توكن التليجرام محفوظ"; \
		grep -q "ADMIN_PASSWORD" .env && echo "✅ كلمة المرور محفوظة"; \
	else \
		echo "❌ ملف .env غير موجود"; \
	fi

setup: install env-setup
	@echo "✅ تم إعداد المشروع بنجاح!"
	@echo "🚀 اتبع الخطوات التالية:"
	@echo "   1. حدّث البيانات في .env"
	@echo "   2. شغّل: make run"
	@echo "   3. في نافذة أخرى: make test"

all: clean install security-check docker-build docker-up
	@echo "✅ تم إعداد وتشغيل كل شيء!"

.PHONY: all help install run run-prod test clean logs docker-build docker-up docker-down docker-logs docker-restart env-setup venv lint format security-check setup
