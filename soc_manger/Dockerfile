FROM python:3.9-slim

WORKDIR /app

# تثبيت المكتبات النظامية
RUN apt-get update && apt-get install -y \
    gcc \
    && rm -rf /var/lib/apt/lists/*

# نسخ ملف المتطلبات
COPY requirements.txt .

# تثبيت مكتبات Python
RUN pip install --no-cache-dir -r requirements.txt

# نسخ الملفات
COPY mine.py .

# إنشاء مجلد للسجلات
RUN mkdir -p logs

# تعريض المنفذ
EXPOSE 5000

# الأمان - تشغيل بدون root
RUN useradd -m waf_user
USER waf_user

# الأمر الافتراضي
CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "--timeout", "120", "--access-logfile", "-", "--error-logfile", "-", "mine:application"]
