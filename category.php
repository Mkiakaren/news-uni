<?php
require_once 'config/database.php';

// اعتبارسنجی و پاک‌سازی ورودی slug
$slug = $_GET['slug'] ?? '';
if (is_array($slug)) {
    error_log("Invalid slug input: expected string, received array");
    redirect('index.php');
}
$slug = preg_replace('/[^a-zA-Z0-9-]/', '', $slug);
if (empty($slug)) {
    redirect('index.php');
}

// تنظیم صفحه‌بندی
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$limit = 9;
$offset = ($page - 1) * $limit;

try {
    // دریافت اطلاعات دسته‌بندی
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        redirect('index.php');
    }

    // شمارش کل اخبار
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE category_id = ? AND status = 'approved'");
    $stmt->execute([$category['id']]);
    $totalNews = $stmt->fetchColumn();
    $totalPages = ceil($totalNews / $limit);

    // محدود کردن صفحه به حداکثر تعداد صفحات
    if ($page > $totalPages && $totalPages > 0) {
        redirect("category.php?slug=$slug&page=$totalPages");
    }

    // دریافت اخبار این دسته‌بندی
    $stmt = $pdo->prepare("
        SELECT n.*, u.full_name as author_name 
        FROM news n 
        JOIN users u ON n.author_id = u.id 
        WHERE n.category_id = ? AND n.status = 'approved' 
        ORDER BY n.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    // صراحتاً نوع داده را برای LIMIT و OFFSET مشخص کنید
    $stmt->bindParam(1, $category['id'], PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // دریافت همه دسته‌بندی‌ها برای منو
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in category.php: " . $e->getMessage() . " | Query params: category_id={$category['id']}, limit=$limit, offset=$offset");
    redirect('error.php');
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category['name'] ?> - سایت خبری</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; }
        .news-card { transition: transform 0.2s; }
        .news-card:hover { transform: translateY(-5px); }
        .category-header { background: linear-gradient(135deg, #007bff, #0056b3); }
        .news-meta { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-newspaper"></i> سایت خبری
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">خانه</a>
                    </li>
                    <?php foreach($categories as $cat): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $cat['slug'] == $slug ? 'active' : '' ?>" href="category.php?slug=<?= $cat['slug'] ?>"><?= $cat['name'] ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= $_SESSION['full_name'] ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(isAdmin()): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">پنل مدیریت</a></li>
                                <?php elseif(isWriter()): ?>
                                    <li><a class="dropdown-item" href="writer/dashboard.php">پنل نویسندگی</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="user/dashboard.php">پنل کاربری</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">خروج</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">ورود</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/register.php">ثبت نام</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Category Header -->
    <div class="category-header text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1><i class="fas fa-tags"></i> <?= $category['name'] ?></h1>
                    <?php if($category['description']): ?>
                    <p class="lead"><?= $category['description'] ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-newspaper"></i> <?= $totalNews ?> خبر</p>
                </div>
            </div>
        </div>
    </div>

    <!-- News Grid -->
    <div class="container my-5">
        <?php if($news): ?>
        <div class="row">
            <?php foreach($news as $item): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card news-card h-100 shadow-sm">
                    <?php if($item['image_url']): ?>
                    <img src="<?= $item['image_url'] ?>" class="card-img-top" alt="<?= $item['title'] ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="news.php?slug=<?= $item['slug'] ?>" class="text-decoration-none text-dark">
                                <?= $item['title'] ?>
                            </a>
                        </h5>
                        <p class="card-text"><?= truncateText($item['summary'], 120) ?></p>
                        <div class="news-meta">
                            <small>
                                <i class="fas fa-user"></i> <?= $item['author_name'] ?>
                                <i class="fas fa-calendar ms-2"></i> <?= formatPersianDate($item['created_at']) ?>
                                <i class="fas fa-eye ms-2"></i> <?= $item['views'] ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?slug=<?= $slug ?>&page=<?= $page-1 ?>">قبلی</a>
                </li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?slug=<?= $slug ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?slug=<?= $slug ?>&page=<?= $page+1 ?>">بعدی</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
            <h4>خبری یافت نشد</h4>
            <p class="text-muted">در این دسته‌بندی هنوز خبری منتشر نشده است.</p>
            <a href="index.php" class="btn btn-primary">بازگشت به صفحه اصلی</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h5>سایت خبری</h5>
                    <p>آخرین اخبار و تحلیل‌های روز را از ما دنبال کنید</p>
                </div>
                <div class="col-md-4">
                    <h6>دسته‌بندی‌ها</h6>
                    <ul class="list-unstyled">
                        <?php foreach($categories as $cat): ?>
                        <li><a href="category.php?slug=<?= $cat['slug'] ?>" class="text-light text-decoration-none"><?= $cat['name'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?= date('Y') ?> سایت خبری. تمامی حقوق محفوظ است.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>