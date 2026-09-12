<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'My Account';
$userId = (int)$_SESSION['user']['user_id'];
$errors = [];

$bloodGroups = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$genderOptions = ['', 'Male', 'Female', 'Other', 'Prefer not to say'];

function load_account_profile(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT user_id,full_name,email,phone,profile_number,profile_image,address,blood_group,joining_date,gender,role,status,created_at FROM users WHERE user_id=? LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [];
}

function load_mobile_numbers(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT mobile_id,label,mobile_number,sort_order FROM user_mobile_numbers WHERE user_id=? ORDER BY sort_order,mobile_id');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

$profile = load_account_profile($pdo, $userId);
$mobileNumbers = load_mobile_numbers($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $profileNumber = trim($_POST['profile_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $joiningDate = trim($_POST['joining_date'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $removeImage = !empty($_POST['remove_profile_image']);

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Full name must be between 2 and 100 characters.';
    if (mb_strlen($profileNumber) > 50) $errors[] = 'Profile / ID number is too long.';
    if (mb_strlen($phone) > 30) $errors[] = 'Primary phone number is too long.';
    if (mb_strlen($address) > 1000) $errors[] = 'Address is too long.';
    if (!in_array($bloodGroup, $bloodGroups, true)) $errors[] = 'Choose a valid blood group.';
    if (!in_array($gender, $genderOptions, true)) $errors[] = 'Choose a valid gender option.';

    if ($joiningDate !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $joiningDate);
        if (!$date || $date->format('Y-m-d') !== $joiningDate) {
            $errors[] = 'Choose a valid joining date.';
        }
    }

    $labels = is_array($_POST['mobile_labels'] ?? null) ? $_POST['mobile_labels'] : [];
    $numbers = is_array($_POST['mobile_numbers'] ?? null) ? $_POST['mobile_numbers'] : [];
    $submittedMobiles = [];
    $count = min(max(count($labels), count($numbers)), 8);
    for ($i = 0; $i < $count; $i++) {
        $label = trim((string)($labels[$i] ?? ''));
        $number = trim((string)($numbers[$i] ?? ''));
        if ($label === '' && $number === '') continue;
        if ($number === '') {
            $errors[] = 'Every mobile entry needs a mobile number.';
            continue;
        }
        if (mb_strlen($label) > 40 || mb_strlen($number) > 30) {
            $errors[] = 'A mobile label or number is too long.';
            continue;
        }
        $submittedMobiles[] = [
            'label' => $label !== '' ? $label : 'Mobile',
            'number' => $number,
        ];
    }

    $newImage = null;
    if (!$errors && isset($_FILES['profile_image'])) {
        try {
            $newImage = store_uploaded_image($_FILES['profile_image'], 'profiles');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $oldImage = $profile['profile_image'] ?? null;
        $finalImage = $newImage ?: ($removeImage ? null : $oldImage);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'UPDATE users SET full_name=?,profile_number=?,phone=?,address=?,blood_group=?,joining_date=?,gender=?,profile_image=? WHERE user_id=?'
            );
            $stmt->execute([
                $name,
                $profileNumber !== '' ? $profileNumber : null,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                $bloodGroup !== '' ? $bloodGroup : null,
                $joiningDate !== '' ? $joiningDate : null,
                $gender !== '' ? $gender : null,
                $finalImage,
                $userId,
            ]);

            $pdo->prepare('DELETE FROM user_mobile_numbers WHERE user_id=?')->execute([$userId]);
            if ($submittedMobiles) {
                $insert = $pdo->prepare('INSERT INTO user_mobile_numbers (user_id,label,mobile_number,sort_order) VALUES (?,?,?,?)');
                foreach ($submittedMobiles as $index => $mobile) {
                    $insert->execute([$userId, $mobile['label'], $mobile['number'], $index]);
                }
            }

            $pdo->commit();

            if ($newImage && $oldImage && $oldImage !== $newImage) delete_uploaded_image($oldImage);
            if ($removeImage && !$newImage && $oldImage) delete_uploaded_image($oldImage);

            refresh_authenticated_user($pdo);
            log_user_activity($pdo, $userId, 'account_profile_updated', 'Profile information was updated.', ['mobile_count' => count($submittedMobiles)], $userId);
            realtime_notify('account.updated', ['user_id' => $userId]);
            redirect('account.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($newImage) delete_uploaded_image($newImage);
            $errors[] = 'Profile could not be updated. Please try again.';
        }
    }

    // Keep submitted values visible when validation fails.
    $profile = array_merge($profile, [
        'full_name' => $name,
        'profile_number' => $profileNumber,
        'phone' => $phone,
        'address' => $address,
        'blood_group' => $bloodGroup,
        'joining_date' => $joiningDate,
        'gender' => $gender,
    ]);
    $mobileNumbers = array_map(fn($m, $i) => ['mobile_id'=>0,'label'=>$m['label'],'mobile_number'=>$m['number'],'sort_order'=>$i], $submittedMobiles, array_keys($submittedMobiles));
}

$ordersStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY order_id DESC');
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();
$spendStmt = $pdo->prepare("SELECT COUNT(*) order_count, COALESCE(SUM(total_amount),0) total_eur, COALESCE(SUM(COALESCE(total_usd,total_amount*NULLIF(usd_exchange_rate,0))),0) total_usd FROM orders WHERE user_id=? AND order_status<>'Cancelled'");
$spendStmt->execute([$userId]);
$spend = $spendStmt->fetch();
$supportCounts = support_counts_for_user($pdo, $userId);
$latestSupport = [];
try {
    $supportStmt = $pdo->prepare("SELECT c.*, COALESCE(SUM(CASE WHEN m.message_type='staff' AND m.seen_by_requester=0 THEN 1 ELSE 0 END),0) unread_count FROM contact_conversations c LEFT JOIN contact_messages m ON m.conversation_id=c.conversation_id WHERE c.user_id=? GROUP BY c.conversation_id ORDER BY c.last_message_at DESC LIMIT 3");
    $supportStmt->execute([$userId]);
    $latestSupport = $supportStmt->fetchAll();
} catch (Throwable $e) {
    $latestSupport = [];
}

if (!$mobileNumbers) {
    $mobileNumbers = [['mobile_id'=>0,'label'=>'Mobile','mobile_number'=>'','sort_order'=>0]];
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Dashboard</span><h1>Hello, <?= e($profile['full_name']) ?></h1><p>Manage your profile, support conversations and orders.</p></div></section>
<section class="section-sm"><div class="container account-grid">
<aside class="account-card profile-summary-card">
    <div class="profile-avatar-large">
        <?php if (!empty($profile['profile_image'])): ?><img src="<?= e(profile_image($profile['profile_image'])) ?>" alt="<?= e($profile['full_name']) ?>"><?php else: ?><span><?= e(strtoupper(substr($profile['full_name'],0,1))) ?></span><?php endif; ?>
    </div>
    <h3><?= e($profile['full_name']) ?></h3>
    <p class="muted" style="margin:0"><?= e($profile['email']) ?></p>
    <p class="muted"><?= e(support_role_label($profile['role'])) ?></p>
    <?php if (!empty($profile['phone'])): ?><div class="profile-summary-line"><span>Primary phone</span><strong><?= e($profile['phone']) ?></strong></div><?php endif; ?>
    <?php if (!empty($profile['profile_number'])): ?><div class="profile-summary-line"><span>Profile / ID</span><strong><?= e($profile['profile_number']) ?></strong></div><?php endif; ?>
    <?php if (is_admin()): ?><a class="btn btn-primary btn-block" href="<?= url('admin/index.php') ?>">Admin dashboard</a><?php endif; ?>
</aside>
<div>
<?php if ($errors): ?><div class="flash flash-error" style="margin-bottom:18px"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<div class="admin-card profile-edit-card" id="profile-settings">
    <div class="section-heading"><div><span class="eyebrow">Profile</span><h2>Personal information</h2><p>These details belong only to this logged-in account.</p></div></div>
    <form method="post" enctype="multipart/form-data" data-profile-form>
        <?= csrf_field() ?><input type="hidden" name="action" value="update_profile">
        <div class="profile-edit-layout">
            <div class="profile-photo-editor" data-image-manager>
                <div class="profile-avatar-preview"><img data-image-preview data-placeholder="<?= e(url('assets/img/profile-placeholder.svg')) ?>" src="<?= e(profile_image($profile['profile_image'] ?? null)) ?>" alt="Profile preview"></div>
                <label class="btn btn-ghost btn-small profile-upload-button">Choose image<input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" data-image-input hidden></label>
                <span class="form-note" data-image-file-name>No new image selected</span>
                <?php if (!empty($profile['profile_image'])): ?><label class="checkbox-row"><input type="checkbox" name="remove_profile_image" value="1" data-image-remove> Remove current image</label><?php endif; ?>
                <span class="form-note">JPG, PNG or WEBP · up to 4 MB.</span>
            </div>
            <div class="profile-fields">
                <div class="form-grid">
                    <div class="form-group"><label>Full name</label><input class="form-control" name="full_name" value="<?= e($profile['full_name']) ?>" required maxlength="100" autocomplete="name"></div>
                    <div class="form-group"><label>Email address</label><input class="form-control" value="<?= e($profile['email']) ?>" disabled><span class="form-note">Login email is kept read-only here.</span></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Profile / ID number</label><input class="form-control" name="profile_number" value="<?= e($profile['profile_number'] ?? '') ?>" maxlength="50" placeholder="Optional ID or profile number"></div>
                    <div class="form-group"><label>Primary phone / number</label><input class="form-control" name="phone" value="<?= e($profile['phone'] ?? '') ?>" maxlength="30" autocomplete="tel" placeholder="Primary contact number"></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Gender</label><select class="form-control" name="gender"><?php foreach($genderOptions as $g): ?><option value="<?= e($g) ?>" <?= ($profile['gender'] ?? '')===$g?'selected':'' ?>><?= $g===''?'Select gender':e($g) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Blood group</label><select class="form-control" name="blood_group"><?php foreach($bloodGroups as $b): ?><option value="<?= e($b) ?>" <?= ($profile['blood_group'] ?? '')===$b?'selected':'' ?>><?= $b===''?'Select blood group':e($b) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="form-group"><label>Joining date</label><input class="form-control" type="date" name="joining_date" value="<?= e($profile['joining_date'] ?? '') ?>"></div>
                <div class="form-group"><label>Address</label><textarea class="form-control" name="address" rows="4" maxlength="1000" placeholder="Current address"><?= e($profile['address'] ?? '') ?></textarea></div>

                <div class="mobile-numbers-section" data-mobile-list>
                    <div class="mobile-numbers-heading"><div><label>Mobile numbers</label><span class="form-note">Add multiple mobile numbers if needed.</span></div><button class="btn btn-ghost btn-small" type="button" data-add-mobile>+ Add mobile</button></div>
                    <div class="mobile-number-list" data-mobile-rows>
                        <?php foreach($mobileNumbers as $m): ?>
                        <div class="mobile-number-row" data-mobile-row>
                            <input class="form-control" name="mobile_labels[]" value="<?= e($m['label'] ?? 'Mobile') ?>" maxlength="40" placeholder="Label (e.g. Personal)">
                            <input class="form-control" name="mobile_numbers[]" value="<?= e($m['mobile_number'] ?? '') ?>" maxlength="30" placeholder="Mobile number" autocomplete="tel">
                            <button class="btn btn-ghost btn-small" type="button" data-remove-mobile>Remove</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary">Save profile</button></div>
            </div>
        </div>
    </form>
</div>

<div class="spending-summary"><div><span class="eyebrow">Lifetime spending</span><h2><?= money($spend['total_eur']) ?></h2><div class="spending-eur"><?= usd_money($spend['total_usd']) ?></div></div><div class="spending-orders"><strong><?= (int)$spend['order_count'] ?></strong><span>Non-cancelled orders</span></div></div>
<div class="account-support-card"><div><span class="eyebrow">Support Center</span><h2>My support messages</h2><p class="muted">Replies from Admin and Super Admin stay connected to your account.</p></div><div class="account-support-stats"><span><strong><?= $supportCounts['total'] ?></strong> conversations</span><span><strong><?= $supportCounts['unread'] ?></strong> unread replies</span></div><a class="btn btn-primary" href="<?= url('support.php') ?>">Open Support Center</a></div>
<?php if($latestSupport): ?><div class="support-list compact-support-list" style="margin-bottom:24px"><?php foreach($latestSupport as $c): ?><a class="support-list-item <?= (int)$c['unread_count']>0?'has-unread':'' ?>" href="<?= url('support_view.php?id='.(int)$c['conversation_id']) ?>"><div class="support-list-main"><div class="support-list-title"><strong>#<?= (int)$c['conversation_id'] ?> · <?= e($c['subject']) ?></strong><?php if((int)$c['unread_count']>0): ?><span class="message-badge"><?= (int)$c['unread_count'] ?> new</span><?php endif; ?></div><div class="muted"><?= e(date('d M Y, h:i A',strtotime($c['last_message_at']))) ?></div></div><span class="support-status <?= strtolower($c['status']) ?>"><?= e($c['status']) ?></span></a><?php endforeach; ?></div><?php endif; ?>
<div class="section-heading"><div><h2>Order history</h2><p><?= count($orders) ?> order<?= count($orders)==1?'':'s' ?> found.</p></div><a class="btn btn-ghost btn-small" href="<?= url('shop.php') ?>">Shop more</a></div>
<?php if ($orders): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $order): ?><tr><td><strong>#<?= (int)$order['order_id'] ?></strong></td><td><?= e(date('d M Y',strtotime($order['order_date']))) ?></td><td><?= dual_money($order['total_amount'], order_usd_amount($order), order_rate($order), false) ?></td><td><?= e($order['payment_method']) ?></td><td><span class="status <?= strtolower($order['order_status']) ?>"><?= e($order['order_status']) ?></span></td><td><a class="btn btn-ghost btn-small" href="<?= url('order_details.php?id='.(int)$order['order_id']) ?>">View</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty-state"><div class="icon">📦</div><h3>No orders yet</h3><p class="muted">Your completed checkouts will appear here.</p><a class="btn btn-primary" href="<?= url('shop.php') ?>">Browse products</a></div><?php endif; ?>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
