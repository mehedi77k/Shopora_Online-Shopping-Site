<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$adminPageTitle = 'Categories';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $id = max(1, (int)($_POST['id'] ?? 0));
        $stmt = $pdo->prepare('SELECT category_name,image FROM categories WHERE category_id = ?');
        $stmt->execute([$id]);
        $oldCategory = $stmt->fetch();
        $oldImage = $oldCategory['image'] ?? null;

        $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
        $stmt->execute([$id]);
        if ($oldImage) {
            delete_uploaded_image($oldImage);
        }
        if ($oldCategory) { log_user_activity($pdo,(int)$_SESSION['user']['user_id'],'category_deleted','Deleted category #' . $id . ': ' . $oldCategory['category_name'],['category_id'=>$id],(int)$_SESSION['user']['user_id']); }

        flash('success', 'Category deleted. Products in it are now uncategorized.');
        redirect('admin/categories.php');
    }

    $id = max(0, (int)($_POST['id'] ?? 0));
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $removeImage = !empty($_POST['remove_image']);

    $originalImage = null;
    if ($id) {
        $stmt = $pdo->prepare('SELECT image FROM categories WHERE category_id = ?');
        $stmt->execute([$id]);
        $originalImage = $stmt->fetchColumn() ?: null;
    }

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    $newUploadedImage = null;
    try {
        $newUploadedImage = store_uploaded_image($_FILES['image'] ?? [], 'categories');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    $finalImage = $newUploadedImage !== null
        ? $newUploadedImage
        : ($removeImage ? null : $originalImage);

    if (!$errors) {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE categories SET category_name = ?, description = ?, image = ? WHERE category_id = ?');
                $stmt->execute([$name, $description, $finalImage, $id]);
                $message = 'Category updated successfully.';
                log_user_activity($pdo,(int)$_SESSION['user']['user_id'],'category_updated','Updated category #' . $id . ': ' . $name,['category_id'=>$id],(int)$_SESSION['user']['user_id']);
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (category_name, description, image) VALUES (?, ?, ?)');
                $stmt->execute([$name, $description, $finalImage]);
                $id=(int)$pdo->lastInsertId();
                $message = 'Category added successfully.';
                log_user_activity($pdo,(int)$_SESSION['user']['user_id'],'category_created','Created category #' . $id . ': ' . $name,['category_id'=>$id],(int)$_SESSION['user']['user_id']);
            }

            if ($id && $originalImage && $originalImage !== $finalImage) {
                delete_uploaded_image($originalImage);
            }

            flash('success', $message);
            redirect('admin/categories.php');
        } catch (PDOException $e) {
            if ($newUploadedImage) {
                delete_uploaded_image($newUploadedImage);
            }
            $errors[] = 'Category name must be unique.';
        }
    } elseif ($newUploadedImage) {
        delete_uploaded_image($newUploadedImage);
    }
}

$editId = max(0, (int)($_GET['edit'] ?? 0));
$edit = ['category_id' => 0, 'category_name' => '', 'description' => '', 'image' => null];
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: $edit;
}

$categories = $pdo->query(
    'SELECT c.*, COUNT(p.product_id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.category_id
     GROUP BY c.category_id
     ORDER BY c.category_name'
)->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar">
    <div><span class="eyebrow">Catalog</span><h1>Categories</h1></div>
</div>

<div class="admin-grid-2 category-admin-grid">
    <div class="admin-card">
        <h2>All categories</h2>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Category</th><th>Products</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <div class="category-admin-cell">
                                <img class="category-mini" src="<?= e(category_image($c['image'] ?? null)) ?>" alt="">
                                <strong><?= e($c['category_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= (int)$c['product_count'] ?></td>
                        <td><?= e($c['description'] ?: '—') ?></td>
                        <td>
                            <a class="btn btn-ghost btn-small" href="<?= url('admin/categories.php?edit=' . (int)$c['category_id']) ?>">Edit</a>
                            <form class="inline-form" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$c['category_id'] ?>">
                                <button class="btn btn-danger btn-small" data-confirm="Delete this category? Products will not be deleted.">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$categories): ?>
                    <tr><td colspan="4" class="muted">No categories found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card category-editor-card">
        <h2><?= $editId ? 'Edit category' : 'Add category' ?></h2>
        <?php if ($errors): ?>
            <div class="flash flash-error" style="margin-bottom:16px"><?= e(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$edit['category_id'] ?>">

            <div class="form-group">
                <label>Name</label>
                <input class="form-control" name="category_name" value="<?= e($edit['category_name']) ?>" required>
            </div>

            <div class="form-group" style="margin-top:14px">
                <label>Description</label>
                <textarea class="form-control" name="description"><?= e($edit['description']) ?></textarea>
            </div>

            <div class="form-group" style="margin-top:14px">
                <label>Category image</label>
                <div class="image-manager compact" data-image-manager>
                    <div class="image-preview-frame category-preview-frame">
                        <img
                            data-image-preview
                            src="<?= e(category_image($edit['image'] ?? null)) ?>"
                            data-placeholder="<?= e(url('assets/img/category-placeholder.svg')) ?>"
                            alt="Category image preview"
                        >
                    </div>
                    <div class="image-upload-actions">
                        <label class="btn btn-ghost image-upload-button">
                            Choose image
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input hidden>
                        </label>
                        <span class="image-file-name" data-image-file-name>No new image selected</span>
                    </div>
                    <span class="form-note">Recommended 1200×800 px or similar landscape image. JPG, PNG or WEBP, maximum 4 MB.</span>
                    <?php if (!empty($edit['image'])): ?>
                        <label class="image-remove-option">
                            <input type="checkbox" name="remove_image" value="1" data-image-remove>
                            Remove current image
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary"><?= $editId ? 'Save changes' : 'Add category' ?></button>
                <?php if ($editId): ?>
                    <a class="btn btn-ghost" href="<?= url('admin/categories.php') ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
