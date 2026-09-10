<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle='Privacy';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Privacy</span><h1>Privacy information</h1><p>This academic project stores account and order information only for the operation of the shopping system.</p></div></section>
<section class="section-sm"><div class="container content-card" style="padding:28px;max-width:850px"><h2>Data stored</h2><p class="muted">The system stores customer name, email, optional phone number, hashed password, cart data, shipping address, orders, payment records and product reviews.</p><h2>Passwords</h2><p class="muted">Passwords are stored using PHP password hashing rather than plain text.</p><h2>Payments</h2><p class="muted">No real payment gateway is included in this project package. Payment method and status are management records only.</p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
