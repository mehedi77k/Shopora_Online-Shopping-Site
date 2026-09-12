<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Checkout';
$items = cart_items($pdo);
if (!$items) { flash('error','Your cart is empty.'); redirect('cart.php'); }
$total = cart_total($pdo);
$currentRate = current_usd_rate($pdo);
$errors = [];
$address = '';
$paymentMethod = 'Cash on Delivery';

if (!$currentRate) {
    $errors[] = 'USD reference rate is not configured yet. Please contact the store administrator before checkout.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $address = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'Cash on Delivery';
    $allowed = ['Cash on Delivery','Card','Mobile Banking'];
    if (mb_strlen($address) < 10) $errors[] = 'Please enter a complete shipping address.';
    if (!in_array($paymentMethod, $allowed, true)) $errors[] = 'Choose a valid payment method.';
    $currentRate = current_usd_rate($pdo);
    if (!$currentRate) $errors[] = 'USD reference rate is not configured yet.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $freshItems = cart_items($pdo);
            if (!$freshItems) throw new RuntimeException('Your cart is empty.');
            $calculatedTotal = 0.0;
            foreach ($freshItems as $item) {
                $lock = $pdo->prepare("SELECT stock,status,price FROM products WHERE product_id=? FOR UPDATE");
                $lock->execute([(int)$item['product_id']]);
                $p = $lock->fetch();
                if (!$p || $p['status'] !== 'available' || (int)$p['stock'] < (int)$item['quantity']) {
                    throw new RuntimeException($item['product_name'] . ' does not have enough stock.');
                }
                $calculatedTotal += (float)$p['price'] * (int)$item['quantity'];
            }

            $rateStmt = $pdo->prepare("SELECT rate_per_eur FROM currency_rates WHERE currency_code='USD' AND is_active=1 FOR UPDATE");
            $rateStmt->execute();
            $lockedRate = (float)$rateStmt->fetchColumn();
            if ($lockedRate <= 0) throw new RuntimeException('USD exchange rate is unavailable.');
            $totalUsd = round($calculatedTotal * $lockedRate, 2);

            $orderStmt = $pdo->prepare("INSERT INTO orders (user_id,total_amount,base_currency,usd_exchange_rate,total_usd,shipping_address,payment_method,order_status) VALUES (?,?,?,?,?,?,?, 'Pending')");
            $orderStmt->execute([(int)$_SESSION['user']['user_id'],$calculatedTotal,'EUR',$lockedRate,$totalUsd,$address,$paymentMethod]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock=stock-? WHERE product_id=?');
            foreach ($freshItems as $item) {
                $subtotal = (float)$item['price'] * (int)$item['quantity'];
                $itemStmt->execute([$orderId,(int)$item['product_id'],(int)$item['quantity'],$item['price'],$subtotal]);
                $stockStmt->execute([(int)$item['quantity'],(int)$item['product_id']]);
            }

            $payStmt = $pdo->prepare("INSERT INTO payments (order_id,amount,payment_method,payment_status) VALUES (?,?,?, 'Pending')");
            $payStmt->execute([$orderId,$calculatedTotal,$paymentMethod]);
            clear_cart($pdo);
            $pdo->commit();
            $uid=(int)$_SESSION['user']['user_id'];
            log_user_activity($pdo, $uid, 'order_placed', 'Placed order #' . $orderId . ' for ' . money($calculatedTotal) . '.', ['order_id'=>$orderId,'total_eur'=>$calculatedTotal,'total_usd'=>$totalUsd,'payment_method'=>$paymentMethod], $uid);
            realtime_notify('cart.updated', ['user_id'=>$uid, 'source'=>'checkout']);
            realtime_notify('product.updated', ['source'=>'checkout_stock']);
            $_SESSION['last_order_id'] = $orderId;
            redirect('order_success.php?id=' . $orderId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Almost there</span><h1>Checkout</h1><p>Confirm delivery and payment information.</p></div></section>
<section class="section-sm"><div class="container checkout-layout">
<form class="form-card" style="margin:0;width:100%" method="post"><?= csrf_field() ?><h2>Delivery details</h2>
<?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', array_unique($errors))) ?></div><?php endif; ?>
<div class="form-group"><label>Shipping address</label><textarea class="form-control" name="shipping_address" required placeholder="House, road, area, city, district"><?= e($address) ?></textarea></div>
<div class="form-group" style="margin-top:16px"><label>Payment method</label><select class="form-control" name="payment_method"><option <?= $paymentMethod==='Cash on Delivery'?'selected':'' ?>>Cash on Delivery</option><option <?= $paymentMethod==='Card'?'selected':'' ?>>Card</option><option <?= $paymentMethod==='Mobile Banking'?'selected':'' ?>>Mobile Banking</option></select><span class="form-note">The selected payment method and payment status are recorded with the order. External gateway processing is not enabled.</span></div>
<div class="form-actions"><button class="btn btn-primary" <?= !$currentRate ? 'disabled' : '' ?>>Place order →</button><a class="btn btn-ghost" href="<?= url('cart.php') ?>">Back to cart</a></div></form>
<aside class="summary-card"><h3>Your order</h3><?php foreach ($items as $item): ?><div class="summary-row"><span><?= e($item['product_name']) ?> × <?= (int)$item['quantity'] ?></span><strong><?= dual_money($item['line_total']) ?></strong></div><?php endforeach; ?><div class="summary-row total"><span>Total</span><span><?= dual_money($total) ?></span></div><?php if ($currentRate): ?><div class="currency-checkout-note"><strong>Exchange rate</strong><span>€1 = <?= usd_rate($currentRate) ?></span><small>USD is shown as a reference. Final payment is recorded in EUR. This rate will be saved with your order.</small></div><?php endif; ?></aside>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
