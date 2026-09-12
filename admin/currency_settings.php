<?php
require_once __DIR__ . '/../includes/functions.php';
require_super_admin();
$adminPageTitle = 'Currency Rate';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $result = sync_live_usd_rate($pdo, true);
    if (!$result['ok']) {
        $error = $result['error'] ?? 'The live exchange-rate service is currently unavailable.';
    }
}

$rateState = sync_live_usd_rate($pdo, false);
$stmt = $pdo->prepare("SELECT * FROM currency_rates WHERE currency_code='USD' LIMIT 1");
$stmt->execute();
$currency = $stmt->fetch();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Automatic rate</span><h1>EUR → USD reference rate</h1><p class="muted">Shopora automatically retrieves the latest published EUR/USD rate. No manual rate entry is required.</p></div></div>
<?php if ($error): ?><div class="flash flash-error" style="margin-bottom:18px"><?= e($error) ?></div><?php endif; ?>
<div class="admin-grid-2 currency-settings-grid">
    <div class="admin-card">
        <h2 style="margin-top:0">Live automatic rate</h2>
        <?php if ($currency && (float)$currency['rate_per_eur'] > 0): ?>
            <div class="rate-display"><span>€1</span><strong>=</strong><span data-live-exchange-rate data-rate-mode="usd" data-rate-value="<?= e((string)$currency['rate_per_eur']) ?>"><?= usd_rate($currency['rate_per_eur']) ?></span></div>
            <div class="currency-preview"><span>Example conversion</span><?= dual_money(100) ?></div>
            <div class="currency-meta">
                <div><span>Source</span><strong><?= e($currency['source_name'] ?: FX_PROVIDER_NAME) ?></strong></div>
                <div><span>Published date</span><strong data-live-rate-date><?= e($currency['source_date'] ?: 'Latest available') ?></strong></div>
                <div><span>Last checked</span><strong data-live-rate-checked><?= !empty($currency['last_checked_at']) ? e(date('d M Y, h:i:s A', strtotime($currency['last_checked_at']))) : 'Not checked yet' ?></strong></div>
                <div><span>Update mode</span><strong>Automatic</strong></div>
            </div>
            <form method="post" style="margin-top:18px"><?= csrf_field() ?><button class="btn btn-ghost">Check source now</button></form>
        <?php else: ?>
            <div class="empty-state" style="padding:24px"><h3>No live rate available yet</h3><p class="muted">Shopora will keep trying automatically. Confirm that PHP has internet access.</p><form method="post" style="margin-top:14px"><?= csrf_field() ?><button class="btn btn-primary">Try now</button></form></div>
        <?php endif; ?>
    </div>
    <div class="admin-card">
        <h2 style="margin-top:0">How it works</h2>
        <div class="history-simple-list">
            <div><strong>Automatic polling</strong><p>The browser checks Shopora every minute. Shopora only contacts the external rate source when the shared database cache is due.</p></div>
            <div><strong>Database fallback</strong><p>If the internet or the external source is temporarily unavailable, the last successful EUR/USD rate remains active.</p></div>
            <div><strong>Historical order protection</strong><p>Each order still saves the exchange rate used at checkout, so later market changes never rewrite an older order.</p></div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
