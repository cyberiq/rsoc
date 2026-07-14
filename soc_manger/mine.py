import re
import logging
import requests
import os
import time
import hashlib
import json
import threading
from ipaddress import ip_address, ip_network
from urllib.parse import urlparse
from datetime import datetime, timedelta
from flask import Flask, request, abort, jsonify, Response, g
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from werkzeug.middleware.proxy_fix import ProxyFix
from collections import defaultdict
from dotenv import load_dotenv

load_dotenv()


def get_env_bool(name, default):
    """قراءة متغيرات البيئة من النوع boolean بشكل آمن."""
    value = os.getenv(name)
    if value is None:
        return default
    return value.strip().lower() == "true"


def get_env_int(name, default):
    """قراءة متغيرات البيئة من النوع integer مع fallback آمن."""
    value = os.getenv(name)
    if value is None:
        return default

    try:
        return int(value)
    except ValueError:
        return default

# --- إعدادات التليجرام من متغيرات البيئة ---
TELEGRAM_TOKEN = os.getenv("TELEGRAM_TOKEN", "").strip()
CHAT_ID = os.getenv("TELEGRAM_CHAT_ID", "").strip()
ENABLE_TELEGRAM = get_env_bool("ENABLE_TELEGRAM", True)
TELEGRAM_CONFIGURED = bool(TELEGRAM_TOKEN and CHAT_ID)

# --- إعدادات التوجيه إلى الموقع الحقيقي ---
PROXY_ENABLED = get_env_bool("PROXY_ENABLED", True)
UPSTREAM_URL = os.getenv("UPSTREAM_URL", "https://rsoc.onrender.com").rstrip("/")
if UPSTREAM_URL == "https://kreen.onrender.com":
    logger.warning("Using default UPSTREAM_URL=https://rsoc.onrender.com. Set UPSTREAM_URL in Render environment variables if needed.")
PROXY_EXCLUDE_PATHS = {"/api/stats", "/api/health", "/api/visitors", "/api/test-telegram"}
ADMIN_PASSWORD = os.getenv("ADMIN_PASSWORD", "").strip()
ADMIN_ALLOWED_IPS = tuple(
    entry.strip() for entry in os.getenv("ADMIN_ALLOWED_IPS", "127.0.0.1,::1").split(",")
    if entry.strip()
)

# --- إعدادات كشف البروكسي / VPN ---
ENABLE_PROXY_VPN_DETECTION = get_env_bool("ENABLE_PROXY_VPN_DETECTION", True)
PROXY_VPN_REFRESH_SECONDS = get_env_int("PROXY_VPN_REFRESH_SECONDS", 3600)
TRUSTED_PROXY_NETWORKS = tuple(
    entry.strip()
    for entry in os.getenv("TRUSTED_PROXY_NETWORKS", "127.0.0.1/32,::1/128").split(",")
    if entry.strip()
)
PROXY_VPN_IPS = tuple(
    entry.strip()
    for entry in os.getenv("PROXY_VPN_IPS", "").split(",")
    if entry.strip()
)
PROXY_VPN_CIDRS = tuple(
    entry.strip()
    for entry in os.getenv("PROXY_VPN_CIDRS", "").split(",")
    if entry.strip()
)
PROXY_VPN_BLOCKLIST_URLS = tuple(
    entry.strip()
    for entry in os.getenv(
        "PROXY_VPN_BLOCKLIST_URLS",
        "https://check.torproject.org/torbulkexitlist"
    ).split(",")
    if entry.strip()
)

# --- إعدادات السجل (Logging) ---
LOG_FILE_PATH = os.getenv('LOG_FILE_PATH', '/tmp/waf_security.log')

try:
    os.makedirs(os.path.dirname(LOG_FILE_PATH), exist_ok=True)
except OSError:
    pass

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE_PATH),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

if ENABLE_TELEGRAM and not TELEGRAM_CONFIGURED:
    logger.warning("Telegram alerts disabled because TELEGRAM_TOKEN or TELEGRAM_CHAT_ID are not configured")
    ENABLE_TELEGRAM = False

def send_telegram_alert(attack_type, client_ip, payload, severity="WARNING"):
    """وظيفة إرسال تنبيه فوري لهاتفك عند حدوث هجوم"""
    if not ENABLE_TELEGRAM:
        logger.warning(f"Skipped Telegram alert for {attack_type}: Telegram is disabled or not configured")
        return
    
    try:
        severity_emoji = {
            "LOW": "⚠️",
            "MEDIUM": "🔴",
            "HIGH": "🚨",
            "CRITICAL": "🔥"
        }.get(severity, "🛡️")
        
        # محاولة الحصول على الرابط من request context
        try:
            request_url = request.url[:100]
        except:
            request_url = "Unknown"
        
        message = (
            f"{severity_emoji} **تنبيه أمني [{severity}]**\n\n"
            f"🛡️ **النظام:** `SOC WAF v3.0`\n"
            f"🕐 **الوقت:** `{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}`\n"
            f"🔍 **نوع الهجوم:** `{attack_type}`\n"
            f"🌐 **عنوان الـ IP:** `{client_ip}`\n"
            f"🔗 **الرابط:** `{request_url}`\n"
            f"📝 **البيانات:** `{payload[:100]}`"
        )
        url = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage"
        response = requests.post(url, json={"chat_id": CHAT_ID, "text": message, "parse_mode": "Markdown"}, timeout=10)
        if response.status_code != 200:
            logger.error(f"Telegram Error: {response.status_code} - {response.text}")
        else:
            logger.info(f"✅ Telegram alert sent: {attack_type}")
    except Exception as e:
        logger.error(f"Telegram Alert Error: {e}")
        raise

app = Flask(__name__)
app.wsgi_app = ProxyFix(app.wsgi_app, x_for=1, x_proto=1)

# --- محدد معدل الطلبات (لحماية السيرفر من الضغط) ---
limiter = Limiter(
    app=app,
    key_func=get_remote_address,
    default_limits=["200 per minute", "10000 per hour"],
    storage_uri="memory://",
    in_memory_fallback_enabled=True
)

# --- حالة الحظر والسمعة ---
ip_reputation = defaultdict(int)
ip_last_request = defaultdict(float)
ip_requests_count = defaultdict(int)
ip_ddos_suspected = defaultdict(bool)
unique_ips = set()  # تتبع الـ IPs الفريدة التي زارت الموقع
BANNED_IPS = set()  # قائمة مفرغة - لا حظر عند البدء
BAN_THRESHOLD = get_env_int("BAN_THRESHOLD", 5)
DDOS_THRESHOLD = get_env_int("DDOS_THRESHOLD", 50)  # طلبات في دقيقة واحدة
proxy_vpn_hits = defaultdict(int)

_trusted_proxy_networks = []
_proxy_vpn_networks = []
_proxy_vpn_ips = set()
_proxy_vpn_last_refresh = 0.0
_proxy_vpn_lock = threading.Lock()

# --- قواعس الحماية المتقدمة (WAF Rules) ---
RULES = {
    # SQL Injection - متتقدم جداً
    "SQLi": r"(?i)(union\s+select|select\s+.*\s+from|insert\s+into|delete\s+from|drop\s+(table|database)|update\s+.*\s+set|exec\s*\(|execute\s*\(|'?\s*or\s*'?1'?='?1|'?\s*and\s*'?1'?='?1|--\s*$|;\s*--|\/\*.*?\*\/|xp_|sp_cmdshell)",
    
    # XSS - Cross-Site Scripting
    "XSS": r"(?i)(<script[\s\S]*?>|alert\s*\(|console\s*\.\s*log|onerror\s*=|onload\s*=|onclick\s*=|javascript\s*:|eval\s*\(|expression\s*\()",
    
    # Path Traversal و Local File Inclusion
    "PathTraversal": r"(?i)(\.\.\/|\.\.\\|\/etc\/|\/proc\/|\/sys\/|\/dev\/|\\\.\.\\|c:\\windows|c:\\winnt|file:\/\/)",
    
    # Command Injection
    "CommandInjection": r"(?i)(;\s*cat\s+|;\s*ls\s+|;\s*rm\s+|;\s*curl\s+|`.*`|\$\(.*\)|bash|sh\s+-c|cmd\.exe|powershell|\|\s*ls|\|\s*cat|\|\s*rm)",
    
    # LDAP Injection
    "LDAPi": r"(?i)(\*\s*\(|ldap:\/\/|objectClass|adminPassword)",
    
    # NoSQL Injection
    "NoSQLi": r"(?i)(\$where|\$regex|\$ne|\$gt|\$lt|\$or|\$and|\{.*\$)",
    
    # XXE - XML External Entity
    "XXE": r"(?i)(<!ENTITY|SYSTEM|PUBLIC|DTD|ext:entities|xml version)",
    
    # Scanner Detection
    "Scanners": r"(?i)(sqlmap|nmap|nikto|masscan|burp|zaproxy|acunetix|nessus|openvas)",
    
    # Web Shell Detection
    "WebShell": r"(?i)(shell\.php|c99|r57|aspshell|cmd\.aspx|system\s*\()",
}

def check_file_upload(file_data, filename):
    """فحص الملفات المرفوعة"""
    dangerous_extensions = ['.php', '.exe', '.sh', '.bat', '.cmd', '.com', '.asp', '.aspx', '.jsp']
    dangerous_mimes = ['application/x-executable', 'application/x-bash', 'application/x-sh']
    
    # فحص الامتداد
    if any(filename.lower().endswith(ext) for ext in dangerous_extensions):
        return False, "File extension not allowed"
    
    # فحص التوقيع (Magic Bytes)
    magic_bytes = file_data[:4]
    if magic_bytes.startswith(b'MZ'):  # EXE
        return False, "Executable file detected"
    if magic_bytes.startswith(b'\x7fELF'):  # ELF (Linux executable)
        return False, "Executable file detected"
    
    return True, "File OK"

def get_device_info():
    """استخراج معلومات الجهاز والمتصفح من الـ Request"""
    user_agent = request.headers.get('User-Agent', 'Unknown')
    
    # تحليل معلومات المتصفح
    browser_info = {
        'Chrome': 'Chrome' in user_agent,
        'Firefox': 'Firefox' in user_agent,
        'Safari': 'Safari' in user_agent and 'Chrome' not in user_agent,
        'Edge': 'Edg' in user_agent,
        'Opera': 'Opera' in user_agent,
        'IE': 'Trident' in user_agent,
    }
    
    os_info = {
        'Windows': 'Windows' in user_agent,
        'macOS': 'Macintosh' in user_agent,
        'Linux': 'Linux' in user_agent,
        'Android': 'Android' in user_agent,
        'iPhone/iPad': 'iPhone' in user_agent or 'iPad' in user_agent,
    }
    
    device_type = 'Mobile' if any(['Android' in user_agent, 'iPhone' in user_agent, 'iPad' in user_agent]) else 'Desktop'
    
    browser = next((k for k, v in browser_info.items() if v), 'Unknown')
    os_name = next((k for k, v in os_info.items() if v), 'Unknown')
    
    return {
        'user_agent': user_agent[:150],
        'browser': browser,
        'os': os_name,
        'device_type': device_type,
        'accept_language': request.headers.get('Accept-Language', 'Unknown'),
        'referer': request.headers.get('Referer', 'Direct'),
        'method': request.method,
    }

def send_device_alert(client_ip, device_info):
    """إرسال تنبيه بمعلومات الجهاز الجديد"""
    if not ENABLE_TELEGRAM:
        return
    
    try:
        # التحقق من هل هذا أول وصول من هذا الـ IP
        is_new_ip = client_ip not in unique_ips
        unique_ips.add(client_ip)
        
        new_badge = "🆕 **IP جديد**" if is_new_ip else ""
        
        message = (
            f"👤 **دخول جديد للموقع** {new_badge}\n\n"
            f"🛡️ **النظام:** `SOC WAF v3.0`\n"
            f"🕐 **الوقت:** `{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}`\n"
            f"🌐 **عنوان IP:** `{client_ip}`\n"
            f"💻 **نوع الجهاز:** `{device_info.get('device_type', 'Unknown')}`\n"
            f"🖥️  **نظام التشغيل:** `{device_info.get('os', 'Unknown')}`\n"
            f"🌐 **المتصفح:** `{device_info.get('browser', 'Unknown')}`\n"
            f"📱 **User-Agent:** `{device_info.get('user_agent', 'Unknown')[:80]}`\n"
            f"🗣️ **اللغة:** `{device_info.get('accept_language', 'Unknown')}`\n"
            f"🔗 **الرابط المطلوب:** `{request.path}`\n"
            f"📍 **المرجع:** `{device_info.get('referer', 'Direct')}`"
        )
        url = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage"
        requests.post(url, json={"chat_id": CHAT_ID, "text": message, "parse_mode": "Markdown"}, timeout=5)
        logger.info(f"Device alert sent for {client_ip}")
    except Exception as e:
        logger.error(f"Device Alert Error: {e}")


def get_client_ip():
    """الحصول على عنوان IP الحقيقي مع الثقة فقط بالبروكسيات المصرح بها."""
    orig = request.environ.get("werkzeug.proxy_fix.orig", {})
    socket_remote = orig.get("REMOTE_ADDR") or request.remote_addr or "0.0.0.0"
    trusted_proxy = is_ip_in_networks(socket_remote, _trusted_proxy_networks)

    # لا نعتمد X-Forwarded-For إلا إذا كان الاتصال قادماً من Proxy موثوق.
    if not trusted_proxy:
        return normalize_ip(socket_remote)

    xff = request.headers.get("X-Forwarded-For", "")
    if not xff:
        return normalize_ip(socket_remote)

    for candidate in [segment.strip() for segment in xff.split(",") if segment.strip()]:
        normalized = normalize_ip(candidate)
        if normalized:
            return normalized

    return normalize_ip(socket_remote)


def normalize_ip(value):
    """تطبيع عنوان IP وإرجاعه كسلسلة قياسية."""
    try:
        return str(ip_address(value))
    except ValueError:
        return "0.0.0.0"


def parse_networks(network_values):
    """تحويل قائمة CIDR إلى كائنات شبكة صالحة."""
    parsed = []
    for value in network_values:
        try:
            parsed.append(ip_network(value, strict=False))
        except ValueError:
            logger.warning(f"Invalid CIDR ignored: {value}")
    return parsed


def is_ip_in_networks(candidate_ip, networks):
    """التحقق إذا كان IP ضمن أي شبكة من القائمة."""
    try:
        addr = ip_address(candidate_ip)
    except ValueError:
        return False

    for net in networks:
        if addr in net:
            return True
    return False


def _parse_blocklist_text(block_text):
    """تحويل النص الخام من قوائم الحظر إلى IPs و CIDRs."""
    ips = set()
    networks = []

    for raw_line in block_text.splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        token = line.split()[0]

        try:
            if "/" in token:
                networks.append(ip_network(token, strict=False))
            else:
                ips.add(str(ip_address(token)))
        except ValueError:
            continue

    return ips, networks


def refresh_proxy_vpn_blocklists(force=False):
    """تحديث قوائم حظر البروكسي/VPN بشكل دوري مع كاش داخلي."""
    global _proxy_vpn_ips, _proxy_vpn_networks, _proxy_vpn_last_refresh

    now = time.time()
    if not force and (now - _proxy_vpn_last_refresh) < PROXY_VPN_REFRESH_SECONDS:
        return

    with _proxy_vpn_lock:
        now = time.time()
        if not force and (now - _proxy_vpn_last_refresh) < PROXY_VPN_REFRESH_SECONDS:
            return

        ips = set()
        networks = []

        # مصادر يدوية من متغيرات البيئة
        for ip_value in PROXY_VPN_IPS:
            normalized = normalize_ip(ip_value)
            if normalized != "0.0.0.0":
                ips.add(normalized)

        networks.extend(parse_networks(PROXY_VPN_CIDRS))

        # مصادر خارجية (مثل Tor exit nodes)
        for url in PROXY_VPN_BLOCKLIST_URLS:
            try:
                response = requests.get(url, timeout=5)
                response.raise_for_status()
                source_ips, source_networks = _parse_blocklist_text(response.text)
                ips.update(source_ips)
                networks.extend(source_networks)
            except requests.RequestException as exc:
                logger.warning(f"Proxy/VPN blocklist fetch failed ({url}): {exc}")

        _proxy_vpn_ips = ips
        _proxy_vpn_networks = networks
        _proxy_vpn_last_refresh = now
        logger.info(
            f"Proxy/VPN blocklists refreshed: {len(_proxy_vpn_ips)} IPs, {len(_proxy_vpn_networks)} CIDRs"
        )


def is_proxy_or_vpn_ip(client_ip):
    """التحقق مما إذا كان IP مدرجاً ضمن قوائم البروكسي/VPN."""
    refresh_proxy_vpn_blocklists(force=False)

    if client_ip in _proxy_vpn_ips:
        return True

    return is_ip_in_networks(client_ip, _proxy_vpn_networks)


def is_admin_ip_allowed(client_ip):
    """التحقق من أن الـ IP الحالي مخول لاستخدام المسارات الإدارية."""
    try:
        client_addr = ip_address(client_ip)
    except ValueError:
        logger.warning(f"Invalid client IP received for admin access check: {client_ip}")
        return False

    for allowed_ip in ADMIN_ALLOWED_IPS:
        try:
            if client_addr in ip_network(allowed_ip, strict=False):
                return True
        except ValueError:
            logger.warning(f"Invalid ADMIN_ALLOWED_IPS entry ignored: {allowed_ip}")

    return False


def get_admin_password_from_request():
    """استخراج كلمة المرور الإدارية من الهيدر أو جسم الطلب."""
    header_password = request.headers.get("X-Admin-Password", "").strip()
    if header_password:
        return header_password

    authorization = request.headers.get("Authorization", "")
    if authorization.startswith("Bearer "):
        return authorization[7:].strip()

    if request.is_json:
        payload = request.get_json(silent=True) or {}
        password = payload.get("password", "")
        if isinstance(password, str):
            return password.strip()

    form_password = request.form.get("password", "").strip()
    if form_password:
        return form_password

    return ""


def require_admin_access():
    """تطبيق التحقق على المسارات الإدارية الحساسة."""
    client_ip = get_client_ip()

    if not ADMIN_PASSWORD:
        logger.error("Admin endpoint requested but ADMIN_PASSWORD is not configured")
        abort(503, description="Admin endpoints are disabled until ADMIN_PASSWORD is configured")

    if not is_admin_ip_allowed(client_ip):
        logger.warning(f"Rejected admin access from unauthorized IP: {client_ip}")
        abort(403, description="🚫 Admin access is not allowed from this IP")

    password = get_admin_password_from_request()
    if not password or password != ADMIN_PASSWORD:
        logger.warning(f"Failed admin authentication from {client_ip}")
        abort(401, description="Invalid admin credentials")


def register_violation(client_ip, attack_type, payload, severity="HIGH", status_code=403, description=None):
    """تسجيل المخالفة، رفع السمعة، وإيقاف الطلب."""
    log_func = logger.critical if severity == "CRITICAL" else logger.warning
    log_func(f"[{severity}] {attack_type} Attack from {client_ip}: {payload[:100]}")
    send_telegram_alert(attack_type, client_ip, payload, severity)

    ip_reputation[client_ip] += 1
    if ip_reputation[client_ip] >= BAN_THRESHOLD:
        BANNED_IPS.add(client_ip)
        logger.critical(f"IP {client_ip} permanently banned")
        send_telegram_alert("Permanent Ban 🛑", client_ip, f"Exceeded {BAN_THRESHOLD} violations", "CRITICAL")

    if description is None:
        description = f"🛡️ Blocked: {attack_type}" if status_code == 403 else "Request blocked"

    abort(status_code, description=description)

def check_payload(payload: str, client_ip: str):
    """فحص المحمل (Payload) ضد جميع القواعس"""
    for name, pattern in RULES.items():
        if re.search(pattern, payload):
            severity = "CRITICAL" if name in ["SQLi", "CommandInjection", "WebShell"] else "HIGH"
            register_violation(client_ip, name, payload, severity=severity, status_code=403)


def inspect_uploaded_files(client_ip):
    """فحص الملفات المرفوعة قبل تمريرها إلى التطبيق الحقيقي."""
    for file_storage in request.files.values():
        filename = (file_storage.filename or "").strip()
        if not filename:
            continue

        file_head = file_storage.stream.read(4096)
        file_storage.stream.seek(0)
        is_safe, reason = check_file_upload(file_head, filename)
        if not is_safe:
            register_violation(
                client_ip,
                "MaliciousFileUpload",
                f"{filename}: {reason}",
                severity="CRITICAL",
                status_code=403
            )

def detect_ddos(client_ip: str):
    """اكتشاف هجمات DDoS"""
    current_time = time.time()
    
    if current_time - ip_last_request[client_ip] < 1:  # أقل من ثانية بين الطلبات
        ip_requests_count[client_ip] += 1
    else:
        ip_requests_count[client_ip] = 1
    
    ip_last_request[client_ip] = current_time
    
    if ip_requests_count[client_ip] >= DDOS_THRESHOLD:
        if not ip_ddos_suspected[client_ip]:
            ip_ddos_suspected[client_ip] = True
            logger.critical(f"🚨 DDoS Attack suspected from {client_ip}")
            send_telegram_alert("DDoS Attack Suspected", client_ip, f"{ip_requests_count[client_ip]} requests in minute", "CRITICAL")
        
        BANNED_IPS.add(client_ip)
        abort(429, description="Too many requests - DDoS protection triggered")


def detect_and_block_proxy_vpn(client_ip: str):
    """كشف وحظر عناوين البروكسي/VPN قبل أي معالجة حساسة."""
    if not ENABLE_PROXY_VPN_DETECTION:
        return

    if is_proxy_or_vpn_ip(client_ip):
        proxy_vpn_hits[client_ip] += 1
        register_violation(
            client_ip,
            "ProxyVPNDetected",
            f"IP matched proxy/vpn blocklist (hit #{proxy_vpn_hits[client_ip]})",
            severity="HIGH",
            status_code=403,
            description="🚫 Proxy/VPN IP is not allowed"
        )


def should_proxy_request():
    """تحديد ما إذا كان الطلب يجب توجيهه إلى الموقع الحقيقي"""
    if not PROXY_ENABLED:
        return False
    if request.path in PROXY_EXCLUDE_PATHS or request.path.startswith("/api/unban"):
        return False
    return True


def proxy_to_upstream():
    """توجيه الطلب إلى الموقع الحقيقي بعد فحصه"""
    target_url = UPSTREAM_URL + request.full_path
    headers = {}
    for key, value in request.headers.items():
        if key.lower() not in {"host", "content-length", "connection"}:
            headers[key] = value

    headers["X-Forwarded-For"] = request.headers.get("X-Forwarded-For", request.remote_addr)
    headers["X-Forwarded-Proto"] = request.headers.get("X-Forwarded-Proto", request.scheme)
    headers["X-Forwarded-Host"] = request.host
    headers["X-Real-IP"] = request.remote_addr or ""

    try:
        # تحسين استقبال وإعادة تمرير ملفات الـ Multi-part والبيانات الثنائية بشكل متوافق تماماً مع Flask
        # قمنا بقراءة الجسم الخام مسبقاً في before_request لتجنب فقدان بيانات POST عند المرور عبر الـ WAF
        req_data = getattr(g, 'raw_body', None) if not request.files else None
        if req_data is None and not request.files:
            req_data = request.get_data(cache=True)
        req_files = {field: (f.filename, f.stream, f.mimetype) for field, f in request.files.items()} if request.files else None

        response = requests.request(
            method=request.method,
            url=target_url,
            headers=headers,
            data=req_data,
            files=req_files,
            cookies=request.cookies,
            timeout=30,  # زيادة مهلة الاتصال لضمان استقرار المواقع الكبيرة
            allow_redirects=False,
        )
    except requests.Timeout:
        logger.error("Proxy timeout while forwarding request")
        abort(504, description="Upstream timeout")
    except requests.RequestException as e:
        logger.error(f"Proxy request failed: {e}")
        abort(502, description="Upstream unavailable")

    excluded_headers = {
        "content-encoding",
        "content-length",
        "transfer-encoding",
        "connection",
        "keep-alive",
        "proxy-authenticate",
        "proxy-authorization",
        "te",
        "trailers",
        "upgrade",
    }
    response_headers = {
        key: value for key, value in response.headers.items()
        if key.lower() not in excluded_headers
    }

    return Response(
        response.content,
        status=response.status_code,
        headers=response_headers,
        mimetype=response.headers.get("content-type") or "application/octet-stream"
    )

def send_request_alert(client_ip, path, method):
    """إرسال بلاغ عند كل طلب يُفتح"""
    if not ENABLE_TELEGRAM:
        return
    
    try:
        device_info = get_device_info()
        message = (
            f"📲 **طلب جديد للموقع**\n\n"
            f"🛡️ **النظام:** `SOC WAF v3.0`\n"
            f"🕐 **الوقت:** `{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}`\n"
            f"🌐 **عنوان IP:** `{client_ip}`\n"
            f"📍 **الصفحة:** `{path}`\n"
            f"🔧 **الطريقة:** `{method}`\n"
            f"💻 **الجهاز:** `{device_info.get('device_type', 'Unknown')}`\n"
            f"🌐 **المتصفح:** `{device_info.get('browser', 'Unknown')}`\n"
            f"🖥️  **نظام التشغيل:** `{device_info.get('os', 'Unknown')}`"
        )
        url = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage"
        requests.post(url, json={"chat_id": CHAT_ID, "text": message, "parse_mode": "Markdown"}, timeout=5)
        logger.info(f"✅ Request alert sent for {path}")
    except Exception as e:
        logger.error(f"Request Alert Error: {e}")

@app.before_request
def waf_core():
    """الدالة الرئيسية لجدار الحماية"""
    if request.path in ["/api/test-telegram", "/api/visitors", "/api/unban", "/api/stats", "/api/health"] or request.path.startswith("/api/unban"):
        return

    client_ip = get_client_ip()

    # قراءة الجسم الخام مبكراً لمنع فقدان البيانات عند تمرير الطلب
    g.raw_body = request.get_data(cache=True)
    
    # إرسال بلاغ عند كل طلب
    send_request_alert(client_ip, request.path, request.method)
    
    if client_ip in BANNED_IPS:
        logger.warning(f"Banned IP attempted access: {client_ip}")
        abort(403, description="🚫 Your IP has been blocked")

    detect_and_block_proxy_vpn(client_ip)
    detect_ddos(client_ip)

    device_info = get_device_info()
    send_device_alert(client_ip, device_info)
    inspect_uploaded_files(client_ip)

    parts = [
        str(request.args),
        str(request.form),
        str(request.get_json(silent=True) or ""),
        request.headers.get("User-Agent", ""),
        request.headers.get("Referer", ""),
        request.method,
        request.path,
    ]
    payload = " ".join(parts)
    check_payload(payload, client_ip)


@app.route("/", defaults={"path": ""}, methods=["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"])
@app.route("/<path:path>", methods=["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"])
def proxy_route(path):
    """توجيه جميع الطلبات غير الإدارية إلى الموقع الحقيقي"""
    if not should_proxy_request():
        return jsonify({
            "message": "🛡️ WAF endpoint",
            "proxy_enabled": PROXY_ENABLED,
            "upstream_url": UPSTREAM_URL,
            "status": "ok"
        })

    logger.info(f"Proxying {request.method} {request.path} -> {UPSTREAM_URL}")
    return proxy_to_upstream()

@app.route("/api/stats", methods=["GET"])
def get_stats():
    """إحصائيات الحماية"""
    return jsonify({
        "total_banned_ips": len(BANNED_IPS),
        "total_violations": sum(ip_reputation.values()),
        "unique_visitors": len(unique_ips),
        "top_attackers": sorted(ip_reputation.items(), key=lambda x: x[1], reverse=True)[:5],
        "ddos_suspected": {ip: status for ip, status in ip_ddos_suspected.items() if status},
        "proxy_vpn_hits": dict(proxy_vpn_hits),
        "proxy_vpn_blocklist_ips": len(_proxy_vpn_ips),
        "proxy_vpn_blocklist_cidrs": len(_proxy_vpn_networks),
        "recent_ips": list(unique_ips)[-20:],  # آخر 20 IP
        "timestamp": datetime.now().isoformat()
    })

@app.route("/api/visitors", methods=["GET"])
def get_visitors():
    """الحصول على قائمة الزوار الفريدين"""
    require_admin_access()

    return jsonify({
        "total_unique_ips": len(unique_ips),
        "ips": sorted(list(unique_ips)),
        "timestamp": datetime.now().isoformat()
    })

@app.route("/api/unban/<ip>", methods=["POST"])
def unban_ip(ip):
    """فك الحظر عن IP (يحتاج كلمة المرور)"""
    require_admin_access()

    if ip in BANNED_IPS:
        BANNED_IPS.remove(ip)
        ip_reputation[ip] = 0
        ip_ddos_suspected[ip] = False
        logger.info(f"IP {ip} unbanned by admin")
        send_telegram_alert("IP Unbanned", ip, "Admin unbanned this IP", "LOW")
        return jsonify({"message": f"✅ IP {ip} has been unbanned"})
    
    return jsonify({"message": f"IP {ip} is not banned"}), 400


@app.route("/api/unban/<ip>", methods=["GET"])
def unban_ip_method_not_allowed(ip):
    """منع استخدام GET مع مسار فك الحظر."""
    abort(405, description="Use POST for this endpoint")


@app.route("/api/test-telegram", methods=["POST"])
def test_telegram():
    """اختبار الاتصال بالتليجرام"""
    require_admin_access()

    try:
        if not TELEGRAM_CONFIGURED:
            return jsonify({
                "status": "error",
                "message": "❌ بيانات Telegram غير مكتملة"
            }), 400

        # اختبار الاتصال مع التليجرام
        test_message = (
            f"🧪 **اختبار الاتصال**\n\n"
            f"✅ التليجرام يعمل!\n"
            f"⏰ الوقت: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n"
            f"📍 هذه رسالة اختبار من WAF"
        )
        
        url = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage"
        response = requests.post(
            url, 
            json={"chat_id": CHAT_ID, "text": test_message, "parse_mode": "Markdown"}, 
            timeout=5
        )
        
        if response.status_code == 200:
            logger.info("✅ Telegram test message sent successfully")
            return jsonify({
                "status": "success",
                "message": "✅ تم إرسال رسالة اختبار بنجاح!"
            })
        else:
            logger.error(f"❌ Telegram error: {response.text}")
            return jsonify({
                "status": "error",
                "message": f"❌ خطأ في الاتصال: {response.text}",
                "response_status": response.status_code
            }), 400
            
    except Exception as e:
        logger.error(f"❌ Telegram connection error: {e}")
        return jsonify({
            "status": "error",
            "message": f"❌ خطأ في الاتصال: {str(e)}"
        }), 500


@app.route("/api/test-telegram", methods=["GET"])
def test_telegram_method_not_allowed():
    """منع استخدام GET مع مسار اختبار التليجرام."""
    abort(405, description="Use POST for this endpoint")

@app.route("/api/health", methods=["GET"])
def health_check():
    """فحص صحة النظام"""
    return jsonify({
        "status": "healthy",
        "waf": "enabled",
        "telegram": "enabled" if ENABLE_TELEGRAM else "disabled",
        "timestamp": datetime.now().isoformat()
    })

@app.errorhandler(403)
def forbidden(error):
    """معالج أخطاء 403"""
    return jsonify({
        "error": "Access Denied",
        "message": str(error.description),
        "timestamp": datetime.now().isoformat()
    }), 403

@app.errorhandler(429)
def too_many_requests(error):
    """معالج أخطاء DDoS (429)"""
    return jsonify({
        "error": "Too Many Requests",
        "message": "🚫 DDoS Protection Triggered",
        "timestamp": datetime.now().isoformat()
    }), 429

@app.errorhandler(400)
def bad_request(error):
    """معالج أخطاء 400"""
    return jsonify({
        "error": "Bad Request",
        "message": str(error.description),
        "timestamp": datetime.now().isoformat()
    }), 400


@app.errorhandler(401)
def unauthorized(error):
    """معالج أخطاء 401"""
    return jsonify({
        "error": "Unauthorized",
        "message": str(error.description),
        "timestamp": datetime.now().isoformat()
    }), 401


@app.errorhandler(503)
def service_unavailable(error):
    """معالج أخطاء 503"""
    return jsonify({
        "error": "Service Unavailable",
        "message": str(error.description),
        "timestamp": datetime.now().isoformat()
    }), 503


@app.errorhandler(405)
def method_not_allowed(error):
    """معالج أخطاء 405"""
    return jsonify({
        "error": "Method Not Allowed",
        "message": str(error.description),
        "timestamp": datetime.now().isoformat()
    }), 405

application = app

_trusted_proxy_networks = parse_networks(TRUSTED_PROXY_NETWORKS)
refresh_proxy_vpn_blocklists(force=True)

if __name__ == "__main__":
    logger.info("🚀 SOC WAF v3.0 Starting...")
    logger.info(f"Telegram Alerts: {'Enabled' if ENABLE_TELEGRAM else 'Disabled'}")
    logger.info(f"Upstream URL: {UPSTREAM_URL}")
    logger.info(f"Proxy Enabled: {PROXY_ENABLED}")
    
    port = int(os.getenv("PORT", 5000))
    host = "0.0.0.0"  # للـ Render والـ Deployment
    logger.info(f"Starting on {host}:{port}")
    
    app.run(host=host, port=port, debug=False, threaded=True)
