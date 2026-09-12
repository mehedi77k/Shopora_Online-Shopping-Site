</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand footer-brand" href="<?= url('index.php') ?>"><img class="brand-logo brand-logo-footer" src="<?= url('assets/images/shopora-logo.png') ?>" alt="<?= e(APP_NAME) ?> logo"><span><?= e(APP_NAME) ?></span></a>
            <p>A clean, modern online shopping experience powered by PHP and MySQL.</p>
        </div>
        <div>
            <h4>Shop</h4>
            <a href="<?= url('shop.php') ?>">All products</a>
            <a href="<?= url('cart.php') ?>">Shopping cart</a>
            <a href="<?= url('account.php') ?>">My orders</a>
        </div>
        <div>
            <h4>Information</h4>
            <a href="<?= url('about.php') ?>">About us</a>
            <a href="<?= url('contact.php') ?>">Contact</a>
            <a href="<?= url('privacy.php') ?>">Privacy</a>
        </div>
        <div>
            <h4>Customer care</h4>
            <p>Email: support@shopora.local</p>
            <p>Phone: +880 1700-000000</p>
            <p>Sat–Thu, 9:00–18:00</p>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
        <span>Online Shopping Management System</span>
    </div>
</footer>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
