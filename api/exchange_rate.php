<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$result = sync_live_usd_rate($pdo, false);
if (!$result['ok'] || empty($result['rate'])) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'error'=>$result['error'] ?? 'Exchange rate is unavailable.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'base' => 'EUR',
    'quote' => 'USD',
    'rate' => (float)$result['rate'],
    'display' => '€1 = $' . number_format((float)$result['rate'], 4),
    'source' => $result['source'] ?? FX_PROVIDER_NAME,
    'source_date' => $result['source_date'] ?? null,
    'last_checked_at' => $result['last_checked_at'] ?? null,
    'poll_ms' => FX_BROWSER_POLL_MS,
]);
