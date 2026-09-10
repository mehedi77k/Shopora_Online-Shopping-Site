<?php
require_once __DIR__ . '/../includes/functions.php';
require_super_admin();
$adminPageTitle = 'Currency Settings';
$errors = [];

$stmt = $pdo->prepare("SELECT cr.*, u.full_name AS updated_by_name FROM currency_rates cr LEFT JOIN users u ON u.user_id=cr.updated_by WHERE cr.currency_code='EUR' LIMIT 1");
$stmt->execute();
$currency = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rate = filter_input(INPUT_POST, 'rate_to_bdt', FILTER_VALIDATE_FLOAT);
    if ($rate === false || $rate === null || $rate <= 0) {
        $errors[] = 'Enter a valid EUR to BDT exchange rate greater than zero.';
    } elseif ($rate > 10000) {
        $errors[] = 'The exchange rate is outside the accepted range.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO currency_rates (currency_code,currency_name,currency_symbol,rate_to_bdt,is_active,updated_by)
             VALUES ('EUR','Euro','€',?,1,?)
             ON DUPLICATE KEY UPDATE rate_to_bdt=VALUES(rate_to_bdt), is_active=1, updated_by=VALUES(updated_by), updated_at=CURRENT_TIMESTAMP"
        );
        $stmt->execute([(float)$rate, (int)$_SESSION['user']['user_id']]);
        flash('success', 'EUR exchange rate updated successfully. New product and cart conversions now use this rate. Existing orders keep their purchase-time rate.');
        redirect('admin/currency_settings.php');
    }
}

$stmt = $pdo->prepare("SELECT cr.*, u.full_name AS updated_by_name FROM currency_rates cr LEFT JOIN users u ON u.user_id=cr.updated_by WHERE cr.currency_code='EUR' LIMIT 1");
$stmt->execute();
$currency = $stmt->fetch();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Super Admin only</span><h1>Currency settings</h1><p class="muted">BDT is the store's base currency. EUR is displayed as a reference currency.</p></div></div>
<div class="admin-grid-2 currency-settings-grid">
    <div class="admin-card">
        <h2 style="margin-top:0">Exchange rate</h2>
        <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="currency-pair-card"><div><span class="currency-code">BDT</span><strong>Bangladeshi Taka</strong><small>Base currency</small></div><div class="currency-arrow">↔</div><div><span class="currency-code">EUR</span><strong>Euro</strong><small>Reference currency</small></div></div>
            <div class="form-group" style="margin-top:20px">
                <label>1 EUR equals</label>
                <div class="currency-input"><span>৳</span><input class="form-control" type="number" min="0.000001" max="10000" step="0.000001" name="rate_to_bdt" value="<?= e($currency['rate_to_bdt'] ?? '') ?>" required></div>
                <span class="form-note">Example: if €1 = ৳143.50, enter 143.50. Only the Super Admin can change this value.</span>
            </div>
            <div class="form-actions"><button class="btn btn-primary">Update exchange rate</button></div>
        </form>
    </div>
    <div class="admin-card">
        <h2 style="margin-top:0">Current rate</h2>
        <?php if ($currency && (float)$currency['rate_to_bdt'] > 0): ?>
            <div class="rate-display"><span>€1</span><strong>=</strong><span><?= money($currency['rate_to_bdt']) ?></span></div>
            <div class="currency-preview"><span>Example conversion</span><?= dual_money(10000) ?></div>
            <div class="currency-meta"><div><span>Last updated</span><strong><?= e(date('d M Y, h:i A', strtotime($currency['updated_at']))) ?></strong></div><div><span>Updated by</span><strong><?= e($currency['updated_by_name'] ?: 'System initial rate') ?></strong></div></div>
        <?php else: ?>
            <div class="empty-state" style="padding:24px"><h3>No EUR rate configured</h3><p class="muted">Set the exchange rate before accepting new orders.</p></div>
        <?php endif; ?>
        <div class="currency-policy"><strong>Historical order protection</strong><p>Every new order saves the EUR rate used at checkout. Changing the rate here never changes the EUR value already stored for an older order.</p></div>
    </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
