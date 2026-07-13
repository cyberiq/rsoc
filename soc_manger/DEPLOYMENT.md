# 🚀 دليل النشر على الخادم (Deployment Guide)

## الطريقة 1: النشر المباشر على VPS/Dedicated Server

### الخطوة 1: تثبيت المتطلبات النظامية
```bash
sudo apt update
sudo apt install -y python3 python3-pip python3-venv nginx certbot python3-certbot-nginx git
```

### الخطوة 2: استنساخ الملفات
```bash
cd /opt/
sudo git clone <your-repo-url> soc-waf
cd soc-waf
```

### الخطوة 3: إنشاء Virtual Environment
```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### الخطوة 4: تكوين متغيرات البيئة
```bash
sudo nano .env
```
أضف بيانات التليجرام والإعدادات الأمنية

### الخطوة 5: إنشاء مجلد السجلات
```bash
sudo mkdir -p /var/log/soc-waf
sudo chown -R www-data:www-data /var/log/soc-waf
sudo mkdir -p /opt/soc-waf/logs
sudo chown -R www-data:www-data /opt/soc-waf/logs
```

### الخطوة 6: نسخ خدمة Systemd
```bash
sudo cp soc-waf.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable soc-waf
sudo systemctl start soc-waf
```

### الخطوة 7: التحقق من الخدمة
```bash
sudo systemctl status soc-waf
journalctl -u soc-waf -f
```

### الخطوة 8: تكوين Nginx
```bash
sudo cp nginx.conf /etc/nginx/sites-available/soc-waf
sudo ln -s /etc/nginx/sites-available/soc-waf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### الخطوة 9: الحصول على شهادة SSL
```bash
sudo certbot certonly --nginx -d your-domain.com
```

---

## الطريقة 2: النشر باستخدام Docker

### الخطوة 1: تثبيت Docker و Docker Compose
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
newgrp docker

sudo apt install docker-compose -y
```

### الخطوة 2: استعداد الملفات
```bash
cd /opt/soc-waf
sudo nano .env  # تحديث البيانات
```

### الخطوة 3: نشر الحاويات
```bash
sudo docker-compose up -d
```

### الخطوة 4: التحقق من الحالة
```bash
sudo docker-compose ps
sudo docker logs soc-waf
```

### الخطوة 5: إيقاف الحاويات (عند الحاجة)
```bash
sudo docker-compose down
```

---

## الطريقة 3: النشر على AWS

### باستخدام EC2 و Elastic Load Balancer

#### الخطوات:
1. **إطلاق EC2 Instance**
   - اختر Ubuntu 20.04 LTS
   - اختر Security Group مع ports 22, 80, 443

2. **تثبيت على EC2**
   - اتبع الخطوات الموجودة في "الطريقة 1"

3. **إعداد Elastic Load Balancer**
   - أنشئ ALB يشير إلى WAF على port 5000
   - استخدم Health Check: `/api/health`

4. **استخدام RDS للسجلات (اختياري)**
   - قم بتخزين السجلات في RDS أو S3

---

## الطريقة 4: النشر على DigitalOcean

### باستخدام Droplet

1. **إنشاء Droplet**
   ```bash
   # Ubuntu 20.04, 2GB RAM minimum
   ssh root@your-droplet-ip
   ```

2. **تثبيت المتطلبات**
   ```bash
   apt update && apt upgrade -y
   apt install -y python3 python3-pip python3-venv nginx git certbot
   ```

3. **استنساخ المشروع وتثبيته**
   ```bash
   cd /opt
   git clone <repo> soc-waf
   cd soc-waf
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

4. **تشغيل الخدمة**
   ```bash
   cp soc-waf.service /etc/systemd/system/
   systemctl enable soc-waf
   systemctl start soc-waf
   ```

---

## الطريقة 5: النشر على Kubernetes

### ملف deployment.yaml
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: soc-waf
spec:
  replicas: 3
  selector:
    matchLabels:
      app: soc-waf
  template:
    metadata:
      labels:
        app: soc-waf
    spec:
      containers:
      - name: waf
        image: your-registry/soc-waf:latest
        ports:
        - containerPort: 5000
        env:
        - name: TELEGRAM_TOKEN
          valueFrom:
            secretKeyRef:
              name: waf-secrets
              key: telegram-token
        - name: TELEGRAM_CHAT_ID
          valueFrom:
            secretKeyRef:
              name: waf-secrets
              key: telegram-chat-id
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /api/health
            port: 5000
          initialDelaySeconds: 30
          periodSeconds: 10
---
apiVersion: v1
kind: Service
metadata:
  name: soc-waf-service
spec:
  selector:
    app: soc-waf
  type: LoadBalancer
  ports:
  - port: 80
    targetPort: 5000
```

تشغيل على Kubernetes:
```bash
kubectl apply -f deployment.yaml
kubectl get pods
```

---

## 🔍 اختبار النشر

### اختبار الاتصال
```bash
curl http://localhost:5000/
curl http://your-domain.com/
```

### اختبار الحماية
```bash
python3 test_waf.py
```

### مراقبة السجلات
```bash
# Systemd
journalctl -u soc-waf -f

# Docker
docker logs -f soc-waf

# الملف
tail -f /var/log/soc-waf/error.log
```

---

## 📊 المراقبة والصيانة

### التحديثات الدورية
```bash
cd /opt/soc-waf
git pull
source venv/bin/activate
pip install -r requirements.txt
sudo systemctl restart soc-waf
```

### النسخ الاحتياطية
```bash
# نسخ السجلات
tar -czf waf_logs_$(date +%Y%m%d).tar.gz /var/log/soc-waf/

# نسخ الإعدادات
cp .env .env.backup
```

### تنظيف السجلات (أرشفة قديمة)
```bash
find /var/log/soc-waf -name "*.log" -mtime +30 -delete
```

---

## 🛡️ اعتبارات الأمان

### التحديث التلقائي
```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

### جدار حماية النظام
```bash
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### نسخة احتياطية من المفاتيح
```bash
# احفظ المفاتيح الخاصة بـ SSL بآمان
sudo cp /etc/letsencrypt/live /backup/letsencrypt.backup
```

### تتبع الأداء
```bash
# استخدم emonit أو Grafana
sudo apt install -y monit

# أو استخدم Prometheus و Grafana
```

---

## ❌ استكشاف الأخطاء

### المشكلة: الخدمة لا تبدأ
```bash
sudo systemctl status soc-waf
journalctl -u soc-waf -n 50
```

### المشكلة: بطء في الأداء
```bash
# زيادة عدد Workers
gunicorn -w 8 -b 0.0.0.0:5000 mine:application

# التحقق من استخدام الموارد
top
free -h
df -h
```

### المشكلة: لا تصل التنبيهات
```bash
# اختبر الاتصال بـ Telegram
python3 -c "import requests; print(requests.get('https://api.telegram.org/bot<TOKEN>/getMe').json())"
```

---

**آخر تحديث:** 2026-07-06
