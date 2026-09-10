<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle='Contact';
$sent=false;
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); $sent=true; }
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Contact us</span><h1>How can we help?</h1><p>This demo form shows the customer-service interface. Connect an email service later if required.</p></div></section>
<section class="section-sm"><div class="container">
<form class="form-card" method="post"><?= csrf_field() ?><?php if($sent): ?><div class="flash flash-success">Thank you. Your message has been recorded in this demo interface.</div><?php endif; ?><div class="form-grid"><div class="form-group"><label>Name</label><input class="form-control" name="name" required></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div></div><div class="form-group" style="margin-top:14px"><label>Subject</label><input class="form-control" name="subject" required></div><div class="form-group" style="margin-top:14px"><label>Message</label><textarea class="form-control" name="message" required></textarea></div><div class="form-actions"><button class="btn btn-primary">Send message</button></div></form>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
