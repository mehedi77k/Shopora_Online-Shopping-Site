<?php
require_once __DIR__ . '/../config/config.php';

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function session_context(): string
{
    return defined('SESSION_CONTEXT') ? SESSION_CONTEXT : SESSION_CONTEXT_DEFAULT;
}

function session_context_field(): string
{
    if (session_context() === SESSION_CONTEXT_DEFAULT) {
        return '';
    }

    return '<input type="hidden" name="' . e(SESSION_CONTEXT_PARAM) . '" value="' . e(session_context()) . '">';
}

function append_session_context(string $target, ?string $context = null): string
{
    $context ??= session_context();
    if ($context === SESSION_CONTEXT_DEFAULT || $context === '') {
        return $target;
    }

    $fragment = '';
    $hashPos = strpos($target, '#');
    if ($hashPos !== false) {
        $fragment = substr($target, $hashPos);
        $target = substr($target, 0, $hashPos);
    }

    // Avoid adding the same workspace selector twice.
    if (!preg_match('/(?:[?&])' . preg_quote(SESSION_CONTEXT_PARAM, '/') . '=/', $target)) {
        $target .= (str_contains($target, '?') ? '&' : '?')
            . rawurlencode(SESSION_CONTEXT_PARAM) . '=' . rawurlencode($context);
    }

    return $target . $fragment;
}

function url(string $path = ''): string
{
    $target = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    return append_session_context($target);
}

function new_account_login_url(): string
{
    $context = bin2hex(random_bytes(8));
    return append_session_context(rtrim(BASE_URL, '/') . '/login.php', $context);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['user_id']);
}

function current_role(): string
{
    return (string)($_SESSION['user']['role'] ?? '');
}

function is_admin(): bool
{
    return is_logged_in() && in_array(current_role(), ['admin', 'super_admin'], true);
}

function is_super_admin(): bool
{
    return is_logged_in() && current_role() === 'super_admin';
}

function admin_exists(PDO $pdo): bool
{
    return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','super_admin')")->fetchColumn() > 0;
}

function super_admin_exists(PDO $pdo): bool
{
    return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='super_admin'")->fetchColumn() > 0;
}

function refresh_authenticated_user(PDO $pdo): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT user_id, full_name, email, role, status, profile_image FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user']['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION['user']);
        return false;
    }

    $_SESSION['user'] = [
        'user_id' => (int)$user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'profile_image' => $user['profile_image'] ?? null,
    ];

    return true;
}

function require_login(): void
{
    global $pdo;

    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please sign in to continue.';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        redirect('login.php');
    }

    if (!refresh_authenticated_user($pdo)) {
        $_SESSION['flash_error'] = 'Your account is inactive or no longer available.';
        redirect('login.php');
    }
}

function require_admin(): void
{
    global $pdo;

    if (!is_logged_in() || !refresh_authenticated_user($pdo) || !is_admin()) {
        $_SESSION['flash_error'] = 'Admin access is required.';
        redirect('login.php');
    }
}

function require_super_admin(): void
{
    global $pdo;

    if (!is_logged_in() || !refresh_authenticated_user($pdo) || !is_super_admin()) {
        $_SESSION['flash_error'] = 'Super Admin access is required for account management.';
        redirect(is_admin() ? 'admin/index.php' : 'login.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return session_context_field() . '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Invalid or expired form token. Please go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    // Success/info banner boxes were removed from the interface. Keep only
    // actionable error flashes for redirects that need user attention.
    if (in_array($type, ['success', 'info'], true)) {
        return;
    }
    $_SESSION['flash_' . $type] = $message;
}

function get_flash(string $type): ?string
{
    $key = 'flash_' . $type;
    if (!isset($_SESSION[$key])) {
        return null;
    }
    $value = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $value;
}


/**
 * Publish a non-sensitive real-time event to the local WebSocket broadcaster.
 * Failure is intentionally non-fatal: the website remains fully usable even
 * when the separate WebSocket process is not running.
 */
function realtime_notify(string $event, array $data = []): void
{
    if (!defined('REALTIME_ENABLED') || !REALTIME_ENABLED) {
        return;
    }

    $event = mb_substr(preg_replace('/[^a-zA-Z0-9._-]/', '', $event), 0, 80);
    if ($event === '') return;

    // Only allow small scalar/identifier payloads. Never broadcast message
    // bodies, passwords, addresses or other private application content.
    $safe = [];
    foreach ($data as $key => $value) {
        $key = mb_substr((string)$key, 0, 60);
        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            $safe[$key] = $value;
        } elseif (is_string($value)) {
            $safe[$key] = mb_substr($value, 0, 120);
        }
    }

    $packet = json_encode([
        'event' => $event,
        'data' => $safe,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($packet === false) return;

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        'udp://' . REALTIME_NOTIFY_HOST . ':' . REALTIME_NOTIFY_PORT,
        $errno,
        $errstr,
        0.05,
        STREAM_CLIENT_CONNECT
    );
    if (!$socket) return;
    @fwrite($socket, $packet);
    @fclose($socket);
}

function realtime_event_for_activity(string $activityType): string
{
    if (str_starts_with($activityType, 'support_')) return 'support.updated';
    if (str_starts_with($activityType, 'cart_')) return 'cart.updated';
    if (str_starts_with($activityType, 'order_') || str_starts_with($activityType, 'payment_')) return 'order.updated';
    if (str_starts_with($activityType, 'product_')) return 'product.updated';
    if (str_starts_with($activityType, 'category_')) return 'category.updated';
    if (str_starts_with($activityType, 'currency_')) return 'currency.updated';
    if (str_starts_with($activityType, 'review_')) return 'review.updated';
    if (str_starts_with($activityType, 'account_') || in_array($activityType, ['login','logout','password_changed'], true)) return 'account.updated';
    return 'activity.updated';
}

function money(float|int|string $amount): string
{
    return CURRENCY . number_format((float)$amount, 2);
}

function usd_money(float|int|string $amount): string
{
    return '$' . number_format((float)$amount, 2);
}

function usd_rate(float|int|string $rate): string
{
    return '$' . number_format((float)$rate, 4);
}

function fetch_live_eur_usd_rate(): ?array
{
    if (!defined('FX_AUTO_UPDATE_ENABLED') || !FX_AUTO_UPDATE_ENABLED) {
        return null;
    }

    $url = FX_PROVIDER_URL;
    $body = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Shopora/1.0'],
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status < 200 || $status >= 300) {
            $body = false;
        }
    }

    if ($body === false && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => "Accept: application/json\r\nUser-Agent: Shopora/1.0\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
    }

    if (!is_string($body) || $body === '') {
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return null;
    }

    $rate = isset($data['rate']) ? (float)$data['rate'] : 0.0;
    if ($rate <= 0 || $rate > 10) {
        return null;
    }

    $date = isset($data['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data['date'])
        ? (string)$data['date']
        : null;

    return [
        'rate' => $rate,
        'date' => $date,
        'source' => FX_PROVIDER_NAME,
        'url' => $url,
    ];
}

function sync_live_usd_rate(PDO $pdo, bool $force = false): array
{
    $row = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM currency_rates WHERE currency_code='USD' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return ['ok' => false, 'rate' => null, 'live' => false, 'error' => 'Currency table is unavailable.'];
    }

    $cachedRate = ($row && (float)($row['rate_per_eur'] ?? 0) > 0) ? (float)$row['rate_per_eur'] : null;
    $lastChecked = $row['last_checked_at'] ?? null;
    $checkedTs = $lastChecked ? strtotime((string)$lastChecked) : false;
    $stale = !$checkedTs || (time() - $checkedTs) >= FX_REFRESH_INTERVAL_SECONDS;

    if (!$force && (!$stale || !FX_AUTO_UPDATE_ENABLED)) {
        return [
            'ok' => $cachedRate !== null,
            'rate' => $cachedRate,
            'live' => false,
            'source' => $row['source_name'] ?? FX_PROVIDER_NAME,
            'source_date' => $row['source_date'] ?? null,
            'last_checked_at' => $lastChecked,
        ];
    }

    $live = fetch_live_eur_usd_rate();
    if ($live) {
        $oldRate = $cachedRate;
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO currency_rates
                    (currency_code,currency_name,currency_symbol,rate_per_eur,is_active,updated_by,source_name,source_url,source_date,last_checked_at,auto_update)
                 VALUES ('USD','US Dollar','$',?,1,NULL,?,?,?,?,1)
                 ON DUPLICATE KEY UPDATE
                    rate_per_eur=VALUES(rate_per_eur),
                    is_active=1,
                    updated_by=NULL,
                    source_name=VALUES(source_name),
                    source_url=VALUES(source_url),
                    source_date=VALUES(source_date),
                    last_checked_at=VALUES(last_checked_at),
                    auto_update=1"
            );
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$live['rate'], $live['source'], $live['url'], $live['date'], $now]);
            $cachedRate = (float)$live['rate'];
            $changed = $oldRate === null || abs($oldRate - $cachedRate) >= 0.000001;
            if ($changed) {
                realtime_notify('currency.updated', [
                    'currency' => 'USD',
                    'rate' => $cachedRate,
                    'source_date' => $live['date'] ?? '',
                ]);
            }
            return [
                'ok' => true,
                'rate' => $cachedRate,
                'live' => true,
                'changed' => $changed,
                'source' => $live['source'],
                'source_date' => $live['date'],
                'last_checked_at' => $now,
            ];
        } catch (Throwable $e) {
            // Fall through to the cached value below.
        }
    }

    // Back off for one refresh interval after an upstream failure so every page
    // request does not block waiting for the same unavailable service.
    if ($row) {
        try {
            $pdo->prepare("UPDATE currency_rates SET last_checked_at=CURRENT_TIMESTAMP WHERE currency_code='USD'")->execute();
        } catch (Throwable $e) {
        }
    }

    return [
        'ok' => $cachedRate !== null,
        'rate' => $cachedRate,
        'live' => false,
        'source' => $row['source_name'] ?? FX_PROVIDER_NAME,
        'source_date' => $row['source_date'] ?? null,
        'last_checked_at' => date('Y-m-d H:i:s'),
        'error' => 'Live rate source is temporarily unavailable; using the last saved rate.',
    ];
}

function current_usd_rate(PDO $pdo, bool $forceRefresh = false): ?float
{
    $result = sync_live_usd_rate($pdo, $forceRefresh);
    return isset($result['rate']) && (float)$result['rate'] > 0 ? (float)$result['rate'] : null;
}

function eur_to_usd(float|int|string $amount, ?float $rate = null): ?float
{
    global $pdo;
    $rate ??= current_usd_rate($pdo);
    if (!$rate || $rate <= 0) {
        return null;
    }
    return round((float)$amount * $rate, 2);
}

function dual_money(float|int|string $eurAmount, ?float $usdAmount = null, ?float $rate = null, bool $approx = true): string
{
    $eur = money($eurAmount);
    $usdAmount ??= eur_to_usd($eurAmount, $rate);
    $secondary = $usdAmount === null
        ? '<span class="price-secondary">USD rate not set</span>'
        : '<span class="price-secondary">' . ($approx ? '≈ ' : '') . usd_money($usdAmount) . '</span>';

    return '<span class="dual-price"><span class="price-primary">' . $eur . '</span>' . $secondary . '</span>';
}

function order_usd_amount(array $order): ?float
{
    if (isset($order['total_usd']) && $order['total_usd'] !== null && $order['total_usd'] !== '') {
        return (float)$order['total_usd'];
    }
    $rate = isset($order['usd_exchange_rate']) && (float)$order['usd_exchange_rate'] > 0
        ? (float)$order['usd_exchange_rate']
        : null;
    return eur_to_usd((float)$order['total_amount'], $rate);
}

function order_rate(array $order): ?float
{
    global $pdo;
    if (isset($order['usd_exchange_rate']) && (float)$order['usd_exchange_rate'] > 0) {
        return (float)$order['usd_exchange_rate'];
    }
    return current_usd_rate($pdo);
}

function stored_image_url(?string $path, string $placeholder): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return url($placeholder);
    }

    // Preserve legacy/external image URLs when present.
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $clean = ltrim(str_replace('\\', '/', $path), '/');
    $fullPath = __DIR__ . '/../' . $clean;

    // Never emit a broken local image URL. Missing files fall back cleanly.
    if (is_file($fullPath)) {
        return url($clean);
    }

    return url($placeholder);
}

function product_image(?string $path): string
{
    return stored_image_url($path, 'assets/img/product-placeholder.svg');
}

function category_image(?string $path): string
{
    return stored_image_url($path, 'assets/img/category-placeholder.svg');
}

function profile_image(?string $path): string
{
    return stored_image_url($path, 'assets/img/profile-placeholder.svg');
}

function fetch_categories(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
}

function get_or_create_cart_id(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT cart_id FROM carts WHERE user_id = ? ORDER BY cart_id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();

    if ($cartId) {
        return (int)$cartId;
    }

    $stmt = $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)');
    $stmt->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

function add_cart_item(PDO $pdo, int $productId, int $quantity = 1): void
{
    $quantity = max(1, $quantity);

    if (is_logged_in()) {
        $cartId = get_or_create_cart_id($pdo, (int)$_SESSION['user']['user_id']);
        $stmt = $pdo->prepare(
            'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );
        $stmt->execute([$cartId, $productId, $quantity]);
    } else {
        $_SESSION['guest_cart'] ??= [];
        $_SESSION['guest_cart'][$productId] = ($_SESSION['guest_cart'][$productId] ?? 0) + $quantity;
    }
}

function merge_guest_cart(PDO $pdo, int $userId): void
{
    $guest = $_SESSION['guest_cart'] ?? [];
    if (!$guest) {
        return;
    }

    $cartId = get_or_create_cart_id($pdo, $userId);
    $stmt = $pdo->prepare(
        'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
    );

    foreach ($guest as $productId => $quantity) {
        $stmt->execute([$cartId, (int)$productId, max(1, (int)$quantity)]);
    }

    unset($_SESSION['guest_cart']);
}

function cart_items(PDO $pdo): array
{
    if (is_logged_in()) {
        $cartId = get_or_create_cart_id($pdo, (int)$_SESSION['user']['user_id']);
        $stmt = $pdo->prepare(
            'SELECT ci.product_id, ci.quantity, p.product_name, p.price, p.stock, p.image, p.status,
                    (p.price * ci.quantity) AS line_total
             FROM cart_items ci
             JOIN products p ON p.product_id = ci.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.cart_item_id DESC'
        );
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }

    $guest = $_SESSION['guest_cart'] ?? [];
    if (!$guest) {
        return [];
    }

    $ids = array_map('intval', array_keys($guest));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT product_id, product_name, price, stock, image, status FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product['quantity'] = (int)($guest[$product['product_id']] ?? 1);
        $product['line_total'] = (float)$product['price'] * $product['quantity'];
    }
    unset($product);

    return $products;
}

function cart_count(PDO $pdo): int
{
    if (is_logged_in()) {
        $cartId = get_or_create_cart_id($pdo, (int)$_SESSION['user']['user_id']);
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?');
        $stmt->execute([$cartId]);
        return (int)$stmt->fetchColumn();
    }

    return array_sum(array_map('intval', $_SESSION['guest_cart'] ?? []));
}

function cart_total(PDO $pdo): float
{
    $total = 0.0;
    foreach (cart_items($pdo) as $item) {
        $total += (float)$item['line_total'];
    }
    return $total;
}

function clear_cart(PDO $pdo): void
{
    if (is_logged_in()) {
        $cartId = get_or_create_cart_id($pdo, (int)$_SESSION['user']['user_id']);
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?');
        $stmt->execute([$cartId]);
    } else {
        unset($_SESSION['guest_cart']);
    }
}

function can_review_product(PDO $pdo, int $userId, int $productId): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.order_id
         WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Delivered'"
    );
    $stmt->execute([$userId, $productId]);
    return (int)$stmt->fetchColumn() > 0;
}

function uploaded_image_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is larger than the server upload limit.',
        UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server upload folder is unavailable.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded image.',
        UPLOAD_ERR_EXTENSION => 'The image upload was blocked by a server extension.',
        default => 'Image upload failed.',
    };
}

function store_uploaded_image(array $file, string $folder): ?string
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(uploaded_image_error_message($error));
    }

    if (!in_array($folder, ['products', 'categories', 'profiles'], true)) {
        throw new RuntimeException('Invalid image destination.');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 4 * 1024 * 1024) {
        throw new RuntimeException('Image must be a valid JPG, PNG or WEBP file up to 4 MB.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('The uploaded image could not be verified.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpName);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG and WEBP images are allowed.');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
        throw new RuntimeException('The selected file is not a valid image.');
    }

    $destinationDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
        throw new RuntimeException('Could not create the image upload folder.');
    }
    if (!is_writable($destinationDir)) {
        throw new RuntimeException('The image upload folder is not writable by PHP.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = $destinationDir . '/' . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    @chmod($destination, 0644);

    return 'uploads/' . $folder . '/' . $filename;
}

function delete_uploaded_image(?string $path): void
{
    $path = ltrim(str_replace('\\', '/', trim((string)$path)), '/');
    if (!preg_match('~^uploads/(products|categories|profiles)/[a-f0-9]{24,64}\.(?:jpg|png|webp)$~i', $path)) {
        return;
    }

    $file = __DIR__ . '/../' . $path;
    if (is_file($file)) {
        @unlink($file);
    }
}

// Backward-compatible wrapper for any older code that still calls this helper.
function save_uploaded_image(array $file, ?string $oldPath = null): ?string
{
    $newPath = store_uploaded_image($file, 'products');
    return $newPath ?? $oldPath;
}

/**
 * Record a meaningful account action for the searchable user-history screen.
 * Logging failures are intentionally non-fatal so normal shopping actions are
 * not interrupted if the audit table has not been migrated yet.
 */
function log_user_activity(PDO $pdo, ?int $userId, string $activityType, string $description, array $metadata = [], ?int $actorUserId = null): void
{
    if ($actorUserId === null && is_logged_in()) {
        $actorUserId = (int)$_SESSION['user']['user_id'];
    }
    if ($userId === null && $actorUserId === null) {
        return;
    }

    $activityType = mb_substr(trim($activityType), 0, 64);
    $description = mb_substr(trim($description), 0, 255);
    $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO user_activity_logs (user_id, actor_user_id, activity_type, description, metadata_json)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $actorUserId, $activityType, $description, $metadataJson]);
    } catch (Throwable $e) {
        // Keep the primary application action working even before migration.
    }

    $eventPayload = [
        'user_id' => $userId,
        'actor_user_id' => $actorUserId,
        'activity_type' => $activityType,
    ];
    foreach (['conversation_id','order_id','product_id','category_id','status'] as $key) {
        if (array_key_exists($key, $metadata)) $eventPayload[$key] = $metadata[$key];
    }
    realtime_notify(realtime_event_for_activity($activityType), $eventPayload);
}

function support_counts_for_user(PDO $pdo, int $userId): array
{
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT c.conversation_id) AS total,
                    COUNT(DISTINCT CASE WHEN c.status <> 'Closed' THEN c.conversation_id END) AS active,
                    COALESCE(SUM(CASE WHEN m.message_type='staff' AND m.seen_by_requester=0 THEN 1 ELSE 0 END),0) AS unread
             FROM contact_conversations c
             LEFT JOIN contact_messages m ON m.conversation_id=c.conversation_id
             WHERE c.user_id=?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch() ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'unread' => (int)($row['unread'] ?? 0),
        ];
    } catch (Throwable $e) {
        return ['total' => 0, 'active' => 0, 'unread' => 0];
    }
}

function support_unread_for_staff(PDO $pdo): int
{
    try {
        return (int)$pdo->query(
            "SELECT COUNT(*) FROM contact_messages WHERE message_type='requester' AND seen_by_staff=0"
        )->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function support_role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'customer' => 'Customer',
        default => 'Guest',
    };
}
