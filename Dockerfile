FROM php:8.2-cli

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx python3 python3-venv python3-pip gcc libzip-dev unzip sqlite3 libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

# إعداد بيئة افتراضية للـ Python وتثبيت مكتبات WAF داخلها
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --upgrade pip setuptools wheel \
    && /opt/venv/bin/pip install --no-cache-dir Flask==3.0.0 flask-limiter==3.5.0 requests==2.31.0 werkzeug==3.0.0 python-dotenv==1.0.0 gunicorn==21.2.0

# نسخ ملفات التطبيق وWAF
COPY . /var/www/html/

RUN chmod +x /var/www/html/docker-entrypoint.sh

ENV PATH="/opt/venv/bin:$PATH"
ENV UPSTREAM_URL=http://127.0.0.1:8080
EXPOSE 80
CMD ["/var/www/html/docker-entrypoint.sh"]
