<?php

require_once __DIR__ . '/env-loader.php';

// إعادة توجيه أي وصول مباشر إلى موقع kreen إلى WAF الخارجي
$wafUrl = getenv('WAF_URL') ?: 'https://soc-waf.onrender.com';
$directHosts = ['kreen.onrender.com', 'www.kreen.onrender.com'];
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

if (in_array($host, $directHosts, true)) {
    header("Location: {$wafUrl}{$requestUri}", true, 301);
    exit;
}

function sqliteColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->query("PRAGMA table_info($table)");
    $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($columns as $info) {
        if (($info['name'] ?? '') === $column) {
            return true;
        }
    }

    return false;
}

function ensureKreenSchema(PDO $pdo): void {
    $balanceTables = ['customers', 'drivers', 'kias'];

    foreach ($balanceTables as $table) {
        if (!sqliteColumnExists($pdo, $table, 'balance_iqd')) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN balance_iqd INTEGER NOT NULL DEFAULT 0");
        }
    }

    if (!sqliteColumnExists($pdo, 'service_requests', 'charge_applied')) {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN charge_applied INTEGER NOT NULL DEFAULT 0");
    }

    if (!sqliteColumnExists($pdo, 'driver_locations', 'accuracy')) {
        $pdo->exec("ALTER TABLE driver_locations ADD COLUMN accuracy REAL DEFAULT 0");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS balance_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_type TEXT NOT NULL,
        account_id INTEGER NOT NULL,
        amount_iqd INTEGER NOT NULL,
        transaction_kind TEXT NOT NULL,
        reason TEXT,
        related_request_id INTEGER,
        admin_id INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

$db_file = __DIR__ . '/kreen_rw.db';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
    ensureKreenSchema($pdo);

} catch (PDOException $e) {
    die("❌ فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'techpluse7@gmail.com');
define('SMTP_PASS', 'lgpliqnptkhxwzsw');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', SMTP_USER);
define('SMTP_FROM_NAME', 'تطبيق كرين');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/kreen');
define('SERVICE_FEE_IQD', 5000);

function sendTelegramAlert(string $text, string $parseMode = 'Markdown'): bool {
    $token = getenv('TELEGRAM_TOKEN');
    $chatId = getenv('TELEGRAM_CHAT_ID');
    if (empty($token) || empty($chatId)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = json_encode([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => $parseMode,
    ]);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 5,
        ],
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return $result !== false;
}

?>
