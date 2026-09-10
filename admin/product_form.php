<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = max(0, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
$product = [
    'product_name' => '',
    'category_id' => '',
    'description' => '',
    'price' => '',
    'stock' => 0,
    'image' => null,
    'status' => 'available',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        flash('error', 'Product not found.');
        redirect('admin/products.php');
    }
}

$errors = [];
$originalImage = $product['image'] ?? null;
$newUploadedImage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $product['product_name'] = trim($_POST['product_name'] ?? '');
    $product['category_id'] = (int)($_POST['category_id'] ?? 0) ?: null;
    $product['description'] = trim($_POST['description'] ?? '');
    $product['price'] = (float)($_POST['price'] ?? 0);
    $product['stock'] = max(0, (int)($_POST['stock'] ?? 0));
    $product['status'] = in_array($_POST['status'] ?? '', ['available', 'unavailable'], true)
        ? $_POST['status']
        : 'available';
    $removeImage = !empty($_POST['remove_image']);

    if ($product['product_name'] === '') {
        $errors[] = 'Product name is required.';
    }
    if ($product['price'] < 0) {
        $errors[] = 'Price cannot be negative.';
    }

    try {
        $newUploadedImage = store_uploaded_image($_FILES['image'] ?? [], 'products');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($newUploadedImage !== null) {
        $product['image'] = $newUploadedImage;
    } elseif ($removeImage) {
        $product['image'] = null;
    } else {
        $product['image'] = $originalImage;
    }

    if (!$errors) {
        try {
            if ($id) {
                $stmt = $pdo->prepare(
                    'UPDATE products
                     SET category_id = ?, product_name = ?, description = ?, price = ?, stock = ?, image = ?, status = ?
                     WHERE product_id = ?'
                );
                $stmt->execute([
                    $product['category_id'],
                    $product['product_name'],
                    $product['description'],
                    $product['price'],
                    $product['stock'],
                    $product['image'],
                    $product['status'],
                    $id,
                ]);
                $message = 'Product updated successfully.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (category_id, product_name, description, price, stock, image, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $product['category_id'],
                    $product['product_name'],
                    $product['description'],
                    $product['price'],
                    $product['stock'],
                    $product['image'],
                    $product['status'],
                ]);
                $message = 'Product added successfully.';
            }

            if ($id && $originalImage && $originalImage !== $product['image']) {
                delete_uploaded_image($originalImage);
            }

            flash('success', $message);
            redirect('admin/products.php');
        } catch (Throwable $e) {
            if ($newUploadedImage) {
                delete_uploaded_image($newUploadedImage);
                $product['image'] = $originalImage;
            }
            $errors[] = 'Could not save the product. Please verify the information and try again.';
        }
    } elseif ($newUploadedImage) {
        delete_uploaded_image($newUploadedImage);
        $product['image'] = $originalImage;
    }
}

$categories = fetch_categories($pdo);
$adminPageTitle = $id ? 'Edit Product' : 'Add Product';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar">
    <div>
        <span class="eyebrow">Catalog</span>
        <h1><?= $id ? 'Edit product' : 'Add product' ?></h1>
    </div>
    <a class="btn btn-ghost" href="<?= url('admin/products.php') ?>">← Products</a>
</div>

<form class="admin-card" method="post" enctype="multipart/form-data" style="max-width:920px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <?php if ($errors): ?>
        <div class="flash flash-error" style="margin-bottom:18px"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label>Product name</label>
            <input class="form-control" name="product_name" value="<?= e($product['product_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select class="form-control" name="category_id">
                <option value="">No category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>" <?= (int)$product['category_id'] === (int)$c['category_id'] ? 'selected' : '' ?>>
                        <?= e($c['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group" style="margin-top:16px">
        <label>Description</label>
        <textarea class="form-control" name="description"><?= e($product['description']) ?></textarea>
    </div>

    <div class="form-grid" style="margin-top:16px">
        <div class="form-group">
            <label>Price (BDT)</label>
            <input class="form-control" type="number" step="0.01" min="0" name="price" value="<?= e((string)$product['price']) ?>" required>
        </div>
        <div class="form-group">
            <label>Stock quantity</label>
            <input class="form-control" type="number" min="0" name="stock" value="<?= (int)$product['stock'] ?>" required>
        </div>
    </div>

    <div class="form-grid image-form-grid" style="margin-top:16px">
        <div class="form-group">
            <label>Product image</label>
            <div class="image-manager" data-image-manager>
                <div class="image-preview-frame">
                    <img
                        data-image-preview
                        src="<?= e(product_image($product['image'])) ?>"
                        data-placeholder="<?= e(url('assets/img/product-placeholder.svg')) ?>"
                        alt="Product image preview"
                    >
                </div>
                <div class="image-upload-actions">
                    <label class="btn btn-ghost image-upload-button">
                        Choose image
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input hidden>
                    </label>
                    <span class="image-file-name" data-image-file-name>No new image selected</span>
                </div>
                <span class="form-note">Recommended square image, ideally 800×800 px or larger. JPG, PNG or WEBP, maximum 4 MB.</span>
                <?php if (!empty($product['image'])): ?>
                    <label class="image-remove-option">
                        <input type="checkbox" name="remove_image" value="1" data-image-remove>
                        Remove current image and use the default placeholder
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select class="form-control" name="status">
                <option value="available" <?= $product['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="unavailable" <?= $product['status'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
            </select>
            <span class="form-note">Available products can appear on the storefront when stock is configured.</span>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary"><?= $id ? 'Save changes' : 'Add product' ?></button>
        <a class="btn btn-ghost" href="<?= url('admin/products.php') ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
