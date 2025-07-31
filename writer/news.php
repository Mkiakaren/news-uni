<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isWriter()) {
    redirect('../auth/login.php');
}

$message = '';
$action = $_GET['action'] ?? 'list';

// حذف خبر
if (isset($_GET['delete'])) {
    $newsId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ? AND author_id = ?");
    if ($stmt->execute([$newsId, $_SESSION['user_id']])) {
        $message = 'خبر با موفقیت حذف شد.';
    } else {
        $message = 'خطا در حذف خبر.';
    }
}

// افزودن خبر جدید
if ($_POST && $action == 'add') {
    $title = sanitize($_POST['title']);
    $summary = sanitize($_POST['summary']);
    $content = sanitize($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $image_url = sanitize($_POST['image_url']);
    $slug = createSlug($title);
    
    if (empty($title) || empty($content) || empty($category_id)) {
        $message = 'لطفا تمام فیلدهای اجباری را پر کنید.';
    } else {
        // چک کردن یکتا بودن slug
        $stmt = $pdo->prepare("SELECT id FROM news WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        $stmt = $pdo->prepare("INSERT INTO news (title, slug, summary, content, image_url, author_id, category_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$title, $slug, $summary, $content, $image_url, $_SESSION['user_id'], $category_id])) {
            $message = 'خبر جدید با موفقیت اضافه شد.';
            $action = 'list';
        }
    }
}

// ویرایش خبر
if ($_POST && $action == 'edit') {
    $newsId = (int)$_POST['news_id'];
    $title = sanitize($_POST['title']);
    $summary = sanitize($_POST['summary']);
    $content = sanitize($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $image_url = sanitize($_POST['image_url']);
    $slug = createSlug($title);
    
    if (empty($title) || empty($content) || empty($category_id)) {
        $message = 'لطفا تمام فیلدهای اجباری را پر کنید.';
    } else {
        // چک کردن یکتا بودن slug
        $stmt = $pdo->prepare("SELECT id FROM news WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $newsId]);
        
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        $stmt = $pdo->prepare("UPDATE news SET title = ?, slug = ?, summary = ?, content = ?, image_url = ?, category_id = ?, status = 'pending' WHERE id = ? AND author_id = ?");
        if ($stmt->execute([$title, $slug, $summary, $content, $image_url, $category_id, $newsId, $_SESSION['user_id']])) {
            $message = 'خبر با موفقیت ویرایش شد.';
            $action = 'list';
        }
    }
}

// دریافت لیست اخبار نویسنده
if ($action == 'list') {
    $where = 'WHERE n.author_id = ?';
    $params = [$_SESSION['user_id']];
    
    if (isset($_GET['category'])) {
        $where .= ' AND n.category_id = ?';
        $params[] = (int)$_GET['category'];
    } elseif (isset($_GET['status'])) {
        $where .= ' AND n.status = ?';
        $params[] = $_GET['status'];
    }
    
    $stmt = $pdo->prepare("
        SELECT n.*, u.full_name as author_name, c.name as category_name 
        FROM news n 
        JOIN users u ON n.author_id = u.id 
        JOIN categories c ON n.category_id = c.id 
        $where 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute($params);
    $news = $stmt->fetchAll();
}

// دریافت اطلاعات خبر برای ویرایش
if ($action == 'edit' && isset($_GET['id'])) {
    $newsId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND author_id = ?");
    $stmt->execute([$newsId, $_SESSION['user_id']]);
    $editNews = $stmt->fetch();
    
    if (!$editNews) {
        $action = 'list';
        $message = 'خبر یافت نشد یا متعلق به شما نیست.';
    }
}

// دریافت دسته‌بندی‌ها برای فرم
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت اخبار - پنل نویسندگی</title>
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
                <div class="sidebar bg-success text-white">
                    <div class="p-3">
                        <h5><i class="fas fa-pen"></i> پنل نویسندگی</h5>
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
                            <a class="nav-link text-white active" href="news.php">
                                <i class="fas fa-newspaper"></i> اخبار من
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="news.php?action=add">
                                <i class="fas fa-plus"></i> خبر جدید
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
                    <?php if ($message): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($action == 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>مدیریت اخبار</h2>
                        <a href="?action=add" class="btn btn-success">
                            <i class="fas fa-plus"></i> افزودن خبر جدید
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>عنوان</th>
                                            <th>دسته‌بندی</th>
                                            <th>وضعیت</th>
                                            <th>بازدید</th>
                                            <th>تاریخ</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($news && count($news) > 0): ?>
                                        <?php foreach ($news as $item): ?>
                                        <tr>
                                            <td><?= $item['id'] ?></td>
                                            <td><strong><?= truncateText($item['title'], 40) ?></strong></td>
                                            <td><?= $item['category_name'] ?></td>
                                            <td>
                                                <?php if ($item['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">تایید شده</span>
                                                <?php elseif ($item['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">در انتظار</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">رد شده</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $item['views'] ?></td>
                                            <td><?= formatPersianDate($item['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../news.php?slug=<?= $item['slug'] ?>" class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger"
                                                       onclick="return confirm('آیا از حذف این خبر اطمینان دارید؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">هیچ خبری یافت نشد.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($action == 'add'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>افزودن خبر جدید</h2>
                        <a href="news.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">عنوان خبر *</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">دسته‌بندی *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">انتخاب دسته‌بندی</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">خلاصه خبر</label>
                                    <textarea name="summary" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">متن کامل خبر *</label>
                                    <textarea name="content" class="form-control" rows="8" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">آدرس تصویر</label>
                                    <input type="text" name="image_url" class="form-control">
                                    <small class="form-text text-muted">آدرس URL تصویر (اختیاری)</small>
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> خبر پس از ثبت در حالت انتظار تایید قرار می‌گیرد.
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> ثبت
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php elseif ($action == 'edit' && isset($editNews)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>ویرایش خبر</h2>
                        <a href="news.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="news_id" value="<?= $editNews['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">عنوان خبر *</label>
                                    <input type="text" name="title" class="form-control" value="<?= $editNews['title'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">دسته‌بندی *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">انتخاب دسته‌بندی</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $category['id'] == $editNews['category_id'] ? 'selected' : '' ?>>
                                            <?= $category['name'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">خلاصه خبر</label>
                                    <textarea name="summary" class="form-control" rows="3"><?= $editNews['summary'] ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">متن کامل خبر *</label>
                                    <textarea name="content" class="form-control" rows="8" required><?= $editNews['content'] ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">آدرس تصویر</label>
                                    <input type="text" name="image_url" class="form-control" value="<?= $editNews['image_url'] ?>">
                                    <small class="form-text text-muted">آدرس URL تصویر (اختیاری)</small>
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> خبر پس از ویرایش در حالت انتظار تایید قرار می‌گیرد.
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