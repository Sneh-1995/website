<?php
// webhook.php
// Secure webhook receiver: verifies signatures (internal & Razorpay), idempotency, logs to webhook_log.txt

// ---- CONFIG ----
// Put secrets in hosting env variables if possible
$WEBHOOK_SECRET = getenv('WEBHOOK_SECRET') ?: 'MySuperSecret_987654321!'; // for internal + Razorpay webhook verification
$LOGFILE = _DIR_ . '/webhook_log.txt';
$ID_INDEX_FILE = _DIR_ . '/webhook_ids.txt'; // stores seen event ids (simple idempotency)

// ---- helpers ----
function safe_compare($a, $b) {
    if (function_exists('hash_equals')) return hash_equals($a, $b);
    // fallback
    return $a === $b;
}

function append_log($path, $line) {
    @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ensure log file exists (attempt create)
if (!file_exists($LOGFILE)) {
    @file_put_contents($LOGFILE, "", FILE_APPEND | LOCK_EX);
}
if (!file_exists($ID_INDEX_FILE)) {
    @file_put_contents($ID_INDEX_FILE, "", FILE_APPEND | LOCK_EX);
}

// ---- read body & headers ----
$raw = file_get_contents('php://input');
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0) {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k,5)))));
        $headers[$name] = $v;
    }
}

// detect signature headers
$internal_sig = $headers['X-Webhook-Signature'] ?? null;    // format: sha256=...
$razorpay_sig = $headers['X-Razorpay-Signature'] ?? null;    // razorpay sends raw hex string

$verified = false;
$source = 'unknown';

// ---- verify internal webhook signature ----
if ($internal_sig) {
    // expected format: sha256=<hex>
    if (strpos($internal_sig, 'sha256=') === 0) {
        $expected = 'sha256=' . hash_hmac('sha256', $raw, $WEBHOOK_SECRET);
        if (safe_compare($expected, $internal_sig)) {
            $verified = true;
            $source = 'internal';
        }
    }
}

// ---- verify Razorpay webhook signature ----
if (!$verified && $razorpay_sig) {
    // Razorpay signature is HMAC-SHA256 of raw body using the webhook secret
    $expected = hash_hmac('sha256', $raw, $WEBHOOK_SECRET);
    if (safe_compare($expected, $razorpay_sig)) {
        $verified = true;
        $source = 'razorpay';
    }
}

if (!$verified) {
    http_response_code(401);
    echo "Invalid signature";
    exit;
}

// ---- decode JSON payload ----
$data = json_decode($raw, true);
if ($data === null) {
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

// Try to determine an event id for idempotency
$event_id = null;
if (isset($data['id']) && is_string($data['id'])) {
    $event_id = $data['id'];
} elseif (isset($data['payload']['payment']['entity']['id'])) {
    // Razorpay webhook payload common path
    $event_id = $data['payload']['payment']['entity']['id'];
} elseif (isset($data['payload']['payment']['id'])) {
    $event_id = $data['payload']['payment']['id'];
} else {
    // fallback: create synthetic id (not ideal for idempotency)
    $event_id = 'evt_' . substr(hash('sha256', $raw), 0, 12);
}

// ---- simple idempotency check using text file index ----
$seen_ids = @file($ID_INDEX_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($seen_ids === false) $seen_ids = [];

if (in_array($event_id, $seen_ids, true)) {
    // already processed
    http_response_code(200);
    echo json_encode(['received' => true, 'note' => 'duplicate_event', 'id' => $event_id]);
    exit;
}

// ---- log a concise single-line entry ----
$timestamp = gmdate('c');
$summary = [
    'ts' => $timestamp,
    'source' => $source,
    'event_id' => $event_id,
];
// try to extract email/name if present (best-effort)
$name = null; $email = null;
if (isset($data['data']['name'])) $name = $data['data']['name'];
if (isset($data['data']['email'])) $email = $data['data']['email'];
// Razorpay payload locations (best-effort)
if (!$email && isset($data['payload']['payment']['entity']['email'])) $email = $data['payload']['payment']['entity']['email'];
if (!$name && isset($data['payload']['payment']['entity']['notes']['name'])) $name = $data['payload']['payment']['entity']['notes']['name'] ?? $name;

// prepare log line: timestamp | source | id | email | name | raw-json
$lineParts = [
    $timestamp,
    strtoupper($source),
    $event_id,
    $email ?? '-',
    $name ?? '-',
    str_replace(["\r","\n"], ['',''], $raw) // make single-line
];
$logLine = implode(' ', $lineParts);

// append to main log and register id
append_log($LOGFILE, $logLine);
append_log($ID_INDEX_FILE, $event_id);

// ---- (optional) additional processing hooks go here ----
// e.g. insert into database, send to Slack, fire internal jobs, etc.

// send quick 200 OK
http_response_code(200);
echo json_encode(['received' => true, 'id' => $event_id]);
exit;