<?php
require_once __DIR__ . '/realtime.php';

define('APP_NAME', 'Shopora');
define('BASE_URL', '/online_shop');
define('CURRENCY', '€');

// Automatic EUR -> USD reference-rate sync.
// Frankfurter's public v2 endpoint requires no API key. The browser polls the
// local API every minute; the server performs at most one upstream request per
// refresh interval and keeps the last successful rate in MySQL as a fallback.
define('FX_AUTO_UPDATE_ENABLED', true);
define('FX_PROVIDER_NAME', 'Frankfurter');
define('FX_PROVIDER_URL', 'https://api.frankfurter.dev/v2/rate/eur/usd');
define('FX_REFRESH_INTERVAL_SECONDS', 60);
define('FX_BROWSER_POLL_MS', 60000);
define('SESSION_CONTEXT_PARAM', 'ctx');
define('SESSION_CONTEXT_DEFAULT', 'main');
define('SESSION_CONTEXT_WINDOW_PREFIX', 'SHOPORA_CTX_');

/*
 * Independent link / multi-account session isolation
 * --------------------------------------------------
 * Shopora intentionally treats every PUBLIC/RAW URL that has no ?ctx= value as
 * a request for a brand-new browser workspace. The bootstrap page creates a
 * random 16-character context, binds it to the current tab via window.name and
 * then redirects to the same URL with ?ctx=<context>.
 *
 * Result:
 *   http://localhost/online_shop/                 -> always a NEW guest workspace
 *   http://localhost/online_shop/?ctx=abc...      -> one isolated workspace
 *
 * Each context uses a different PHP session cookie name. Therefore a Super
 * Admin, Admin and multiple Customers can remain signed in simultaneously in
 * the same browser without sharing authentication, cart or CSRF state.
 *
 * The tab-ownership guard in the HTML headers also prevents a context URL copied
 * from an authenticated tab from being silently adopted by a different/new tab.
 */
function shopora_emit_context_bootstrap(): never
{
    $fallbackContext = bin2hex(random_bytes(8));
    $prefix = SESSION_CONTEXT_WINDOW_PREFIX;

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $fallbackJson = json_encode($fallbackContext, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $prefixJson = json_encode($prefix, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $paramJson = json_encode(SESSION_CONTEXT_PARAM, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex"><title>Opening Shopora...</title></head><body>';
    echo '<script>(function(){';
    echo 'var fallback=' . $fallbackJson . ';';
    echo 'var prefix=' . $prefixJson . ';';
    echo 'var param=' . $paramJson . ';';
    echo 'var ctx="";';
    echo 'try{var a=new Uint8Array(8);crypto.getRandomValues(a);ctx=Array.from(a,function(b){return b.toString(16).padStart(2,"0");}).join("");}catch(e){ctx=fallback;}';
    echo 'window.name=prefix+ctx;';
    echo 'try{sessionStorage.setItem("shopora_session_context",ctx);}catch(e){}';
    echo 'var u=new URL(window.location.href);u.searchParams.set(param,ctx);window.location.replace(u.toString());';
    echo '})();</script>';
    echo '<noscript><p>JavaScript is required for isolated multi-account sessions. <a href="?ctx=' . rawurlencode($fallbackContext) . '">Continue</a></p></noscript>';
    echo '</body></html>';
    exit;
}

$incomingContext = $_GET[SESSION_CONTEXT_PARAM] ?? $_POST[SESSION_CONTEXT_PARAM] ?? null;
$rawContext = is_string($incomingContext) ? strtolower(trim($incomingContext)) : '';
$validContext = (bool)preg_match('/^[a-f0-9]{16}$/', $rawContext);
$isCli = PHP_SAPI === 'cli';
$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!$isCli && !$validContext) {
    if ($requestMethod === 'GET') {
        // Never reuse an already-open tab/account when the clean public URL is
        // entered again. A raw GET always starts a new independent guest context.
        shopora_emit_context_bootstrap();
    }

    // All legitimate Shopora POST forms carry ctx through csrf_field(). Refuse
    // context-less POST requests instead of falling back to a shared PHPSESSID.
    http_response_code(400);
    die('Missing or invalid Shopora session context. Reopen the site from ' . BASE_URL . '/ and try again.');
}

if ($isCli && !$validContext) {
    $rawContext = SESSION_CONTEXT_DEFAULT;
}

define('SESSION_CONTEXT', $rawContext);

if (session_status() === PHP_SESSION_NONE) {
    if (SESSION_CONTEXT === SESSION_CONTEXT_DEFAULT) {
        session_name('SHOPORA_CLI');
    } else {
        session_name('SHOPORA_' . strtoupper(SESSION_CONTEXT));
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Check config/config.php and make sure MySQL is running.');
}
