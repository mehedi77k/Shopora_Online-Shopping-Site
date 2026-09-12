<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container page-heading">
        <span class="eyebrow">About Shopora</span>
        <h1>A simple online shopping management system.</h1>
    </div>
</section>

<section class="section-sm">
    <div class="container info-grid">
        <div class="info-card">
            <h3>Customer experience</h3>
            <p class="muted">Product browsing, search, cart, checkout, order history and reviews.</p>
        </div>

        <div class="info-card">
            <h3>Management</h3>
            <p class="muted">Admin controls for products, categories, customers, orders and payment status.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
