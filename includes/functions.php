<?php
require_once __DIR__ . '/../config/config.php';

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
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

    $stmt = $pdo->prepare('SELECT user_id, full_name, email, role, status FROM users WHERE user_id = ? LIMIT 1');
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
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
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

function money(float|int|string $amount): string
{
    return CURRENCY . number_format((float)$amount, 2);
}

function eur_money(float|int|string $amount): string
{
    return '€' . number_format((float)$amount, 2);
}

function current_eur_rate(PDO $pdo): ?float
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }
    $loaded = true;

    try {
        $stmt = $pdo->prepare("SELECT rate_to_bdt FROM currency_rates WHERE currency_code='EUR' AND is_active=1 LIMIT 1");
        $stmt->execute();
        $rate = $stmt->fetchColumn();
        if ($rate !== false && (float)$rate > 0) {
            $cached = (float)$rate;
        }
    } catch (Throwable $e) {
        $cached = null;
    }

    return $cached;
}

function bdt_to_eur(float|int|string $amount, ?float $rate = null): ?float
{
    global $pdo;
    $rate ??= current_eur_rate($pdo);
    if (!$rate || $rate <= 0) {
        return null;
    }
    return round((float)$amount / $rate, 2);
}

function dual_money(float|int|string $bdtAmount, ?float $eurAmount = null, ?float $rate = null, bool $approx = true): string
{
    $bdt = money($bdtAmount);
    $eurAmount ??= bdt_to_eur($bdtAmount, $rate);
    $secondary = $eurAmount === null
        ? '<span class="price-secondary">EUR rate not set</span>'
        : '<span class="price-secondary">' . ($approx ? '≈ ' : '') . eur_money($eurAmount) . '</span>';

    return '<span class="dual-price"><span class="price-primary">' . $bdt . '</span>' . $secondary . '</span>';
}

function order_eur_amount(array $order): ?float
{
    if (isset($order['total_eur']) && $order['total_eur'] !== null && $order['total_eur'] !== '') {
        return (float)$order['total_eur'];
    }
    $rate = isset($order['eur_exchange_rate']) && (float)$order['eur_exchange_rate'] > 0
        ? (float)$order['eur_exchange_rate']
        : null;
    return bdt_to_eur((float)$order['total_amount'], $rate);
}

function order_rate(array $order): ?float
{
    global $pdo;
    if (isset($order['eur_exchange_rate']) && (float)$order['eur_exchange_rate'] > 0) {
        return (float)$order['eur_exchange_rate'];
    }
    return current_eur_rate($pdo);
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

    if (!in_array($folder, ['products', 'categories'], true)) {
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
    if (!preg_match('~^uploads/(products|categories)/[a-f0-9]{24,64}\.(?:jpg|png|webp)$~i', $path)) {
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
