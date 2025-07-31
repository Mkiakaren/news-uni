<?php
require_once '../config/database.php';

if(!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$message = '';
$action = $_GET['action'] ?? 'list';

// حذف دسته‌بندی
if(isset($_GET['delete'])) {
    $categoryId = (int)$_GET['delete'];
    
    // چک کردن اینکه آیا خبری در این دسته‌بندی وجود دارد
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $newsCount = $stmt->fetchColumn();
    
    if($newsCount > 0) {
        $message = 'خطا: نمی‌توان دسته‌بندی حاوی خبر را حذف کرد. ابتدا اخبار آن را منتقل کنید.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if($stmt->execute([$categoryId])) {
            $message = 'دسته‌بندی با موفقیت حذف شد.';
        }
    }
}

// افزودن دسته‌بندی جدید
if($_POST && $action == 'add') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $slug = createSlug($name);
    
    if(empty($name)) {
        $message = 'نام دسته‌بندی الزامی است.';
    } else {
        // چک کردن یکتا بودن slug
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
        if($stmt->execute([$name, $slug, $description])) {
            $message = 'دسته‌بندی جدید با موفقیت اضافه شد.';
            $action = 'list';
        }
    }
}

// ویرایش دسته‌بندی
if($_POST && $action == 'edit') {
    $categoryId = (int)$_POST['category_id'];
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $slug = createSlug($name);
    
    if(empty($name)) {
        $message = 'نام دسته‌بندی الزامی است.';
    } else {
        // چک کردن یکتا بودن slug
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $categoryId]);
        
        if($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
        if($stmt->execute([$name, $slug, $description, $categoryId])) {
            $message = 'دسته‌بندی با موفقیت ویرایش شد.';
            $action = 'list';
        }
    }
}

// دریافت لیست دسته‌بندی‌ها با تعداد اخبار
if($action == 'list') {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(n.id) as news_count 
        FROM categories c 
        LEFT JOIN news n ON c.id = n.category_id 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
}

// دریافت اطلاعات دسته‌بندی برای ویرایش
if($action == 'edit' && isset($_GET['id'])) {
    $categoryId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $editCategory = $stmt->fetch();
    
    if(!$editCategory) {
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت دسته‌بندی‌ها - پنل مدیریت</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #f8f9fa; }
        .sidebar { min-height: 100vh; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .main-content { min-height: 100vh; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar bg-primary text-white">
                    <div class="p-3">
                        <h5><i class="fas fa-user-shield"></i> پنل مدیریت</h5>
                        <hr>
                        <p class="mb-0">خوش آمدید<br><strong><?= $_SESSION['full_name'] ?></strong></p>
                    </div>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> داشبورد
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users"></i> کاربران
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="categories.php">
                                <i class="fas fa-tags"></i> دسته‌بندی‌ها
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="news.php">
                                <i class="fas fa-newspaper"></i> اخبار
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="comments.php">
                                <i class="fas fa-comments"></i> نظرات
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-white" href="../index.php">
                                <i class="fas fa-home"></i> صفحه اصلی سایت
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> خروج
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <?php if($message): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if($action == 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>مدیریت دسته‌بندی‌ها</h2>
                        <a href="?action=add" class="btn btn-success">
                            <i class="fas fa-plus"></i> افزودن دسته‌بندی جدید
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>نام</th>
                                            <th>Slug</th>
                                            <th>توضیحات</th>
                                            <th>تعداد اخبار</th>
                                            <th>تاریخ ایجاد</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($categories as $category): ?>
                                        <tr>
                                            <td><?= $category['id'] ?></td>
                                            <td><strong><?= $category['name'] ?></strong></td>
                                            <td><code><?= $category['slug'] ?></code></td>
                                            <td><?= truncateText($category['description'] ?: 'بدون توضیح', 50) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= $category['news_count'] ?></span>
                                                <?php if($category['news_count'] > 0): ?>
                                                <a href="news.php?category=<?= $category['id'] ?>" class="text-decoration-none">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatPersianDate($category['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../category.php?slug=<?= $category['slug'] ?>" class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?= $category['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?= $category['id'] ?>" class="btn btn-sm btn-danger"
                                                       onclick="return confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟<?= $category['news_count'] > 0 ? ' (این دسته‌بندی حاوی ' . $category['news_count'] . ' خبر است)' : '' ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php elseif($action == 'add'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>افزودن دسته‌بندی جدید</h2>
                        <a href="categories.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">نام دسته‌بندی *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">توضیحات</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Slug به صورت خودکار از نام دسته‌بندی تولید می‌شود.
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> ذخیره
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php elseif($action == 'edit' && isset($editCategory)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>ویرایش دسته‌بندی</h2>
                        <a href="categories.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">نام دسته‌بندی *</label>
                                    <input type="text" name="name" class="form-control" value="<?= $editCategory['name'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">توضیحات</label>
                                    <textarea name="description" class="form-control" rows="3"><?= $editCategory['description'] ?></textarea>
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Slug فعلی: <code><?= $editCategory['slug'] ?></code><br>
                                    در صورت تغییر نام، Slug جدید تولید می‌شود.
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> ویرایش
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>