<?php
require_once 'config/database.php';

// دریافت اخبار تایید شده برای هر دسته‌بندی
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(n.id) as news_count 
    FROM categories c 
    LEFT JOIN news n ON c.id = n.category_id AND n.status = 'approved'
    GROUP BY c.id 
    ORDER BY c.name
");
$stmt->execute();
$categories = $stmt->fetchAll();

// دریافت اخبار برتر
$stmt = $pdo->prepare("
    SELECT n.*, u.full_name as author_name, c.name as category_name 
    FROM news n 
    JOIN users u ON n.author_id = u.id 
    JOIN categories c ON n.category_id = c.id 
    WHERE n.status = 'approved' 
    ORDER BY n.views DESC, n.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$topNews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سایت خبری</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; }
        .news-card { transition: transform 0.2s; }
        .news-card:hover { transform: translateY(-5px); }
        .category-badge { font-size: 0.8rem; }
        .news-meta { color: #666; font-size: 0.9rem; }
        .navbar-brand { font-weight: bold; }
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
                        <a class="nav-link active" href="index.php">خانه</a>
                    </li>
                    <?php foreach($categories as $cat): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="category.php?slug=<?= $cat['slug'] ?>"><?= $cat['name'] ?></a>
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

    <!-- Hero Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 mb-3">به سایت خبری خوش آمدید</h1>
                    <p class="lead">آخرین اخبار و تحلیل‌های روز را دنبال کنید</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top News Section -->
    <?php if($topNews): ?>
    <div class="container my-5">
        <h2 class="mb-4">
            <i class="fas fa-fire text-danger"></i> اخبار برتر
        </h2>
        <div class="row">
            <?php foreach($topNews as $news): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card news-card h-100 shadow-sm">
                    <?php if($news['image_url']): ?>
                    <img src="<?= $news['image_url'] ?>" class="card-img-top" alt="<?= $news['title'] ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-primary category-badge mb-2"><?= $news['category_name'] ?></span>
                        <h5 class="card-title">
                            <a href="news.php?slug=<?= $news['slug'] ?>" class="text-decoration-none text-dark">
                                <?= $news['title'] ?>
                            </a>
                        </h5>
                        <p class="card-text"><?= truncateText($news['summary'], 100) ?></p>
                        <div class="news-meta">
                            <small>
                                <i class="fas fa-user"></i> <?= $news['author_name'] ?>
                                <i class="fas fa-calendar ms-2"></i> <?= formatPersianDate($news['created_at']) ?>
                                <i class="fas fa-eye ms-2"></i> <?= $news['views'] ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Categories Section -->
    <div class="container my-5">
        <?php foreach($categories as $category): ?>
        <?php
        // دریافت اخبار هر دسته‌بندی
        $stmt = $pdo->prepare("
            SELECT n.*, u.full_name as author_name 
            FROM news n 
            JOIN users u ON n.author_id = u.id 
            WHERE n.category_id = ? AND n.status = 'approved' 
            ORDER BY n.created_at DESC 
            LIMIT 3
        ");
        $stmt->execute([$category['id']]);
        $categoryNews = $stmt->fetchAll();
        ?>
        
        <?php if($categoryNews): ?>
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>
                    <i class="fas fa-tags text-primary"></i> <?= $category['name'] ?>
                </h3>
                <a href="category.php?slug=<?= $category['slug'] ?>" class="text-primary text-decoration-none">
                    مشاهده همه <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            <div class="row">
                <?php foreach($categoryNews as $news): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card news-card h-100">
                        <?php if($news['image_url']): ?>
                        <img src="<?= $news['image_url'] ?>" class="card-img-top" alt="<?= $news['title'] ?>" style="height: 180px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="news.php?slug=<?= $news['slug'] ?>" class="text-decoration-none text-dark">
                                    <?= $news['title'] ?>
                                </a>
                            </h6>
                            <p class="card-text small"><?= truncateText($news['summary'], 80) ?></p>
                            <div class="news-meta">
                                <small>
                                    <i class="fas fa-user"></i> <?= $news['author_name'] ?>
                                    <i class="fas fa-calendar ms-2"></i> <?= formatPersianDate($news['created_at']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
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