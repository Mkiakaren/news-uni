<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if(empty($slug)) {
    redirect('index.php');
}

// دریافت اطلاعات خبر
$stmt = $pdo->prepare("
    SELECT n.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
    FROM news n 
    JOIN users u ON n.author_id = u.id 
    JOIN categories c ON n.category_id = c.id 
    WHERE n.slug = ? AND n.status = 'approved'
");
$stmt->execute([$slug]);
$news = $stmt->fetch();

if(!$news) {
    redirect('index.php');
}

// افزایش تعداد بازدید
$stmt = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
$stmt->execute([$news['id']]);

// دریافت کامنت‌های تایید شده
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as user_name 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.news_id = ? AND c.status = 'approved' 
    ORDER BY c.created_at DESC
");
$stmt->execute([$news['id']]);
$comments = $stmt->fetchAll();

// ذخیره کامنت جدید
if($_POST && isLoggedIn()) {
    $content = sanitize($_POST['content']);
    
    if(!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (news_id, user_id, content) VALUES (?, ?, ?)");
        if($stmt->execute([$news['id'], $_SESSION['user_id'], $content])) {
            $success = 'نظر شما ثبت شد و پس از تایید نمایش داده خواهد شد.';
        }
    }
}

// دریافت اخبار مرتبط
$stmt = $pdo->prepare("
    SELECT n.*, u.full_name as author_name 
    FROM news n 
    JOIN users u ON n.author_id = u.id 
    WHERE n.category_id = ? AND n.id != ? AND n.status = 'approved' 
    ORDER BY n.created_at DESC 
    LIMIT 3
");
$stmt->execute([$news['category_id'], $news['id']]);
$relatedNews = $stmt->fetchAll();

// دریافت همه دسته‌بندی‌ها برای منو
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $news['title'] ?> - سایت خبری</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; }
        .news-content { line-height: 1.8; font-size: 1.1rem; }
        .news-meta { color: #666; }
        .comment-box { background: #f8f9fa; }
        .related-news-card { transition: transform 0.2s; }
        .related-news-card:hover { transform: translateY(-3px); }
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
                        <a class="nav-link <?= $cat['slug'] == $news['category_slug'] ? 'active' : '' ?>" href="category.php?slug=<?= $cat['slug'] ?>"><?= $cat['name'] ?></a>
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

    <!-- Breadcrumb -->
    <nav class="bg-light py-2">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">خانه</a></li>
                <li class="breadcrumb-item"><a href="category.php?slug=<?= $news['category_slug'] ?>"><?= $news['category_name'] ?></a></li>
                <li class="breadcrumb-item active"><?= truncateText($news['title'], 50) ?></li>
            </ol>
        </div>
    </nav>

    <!-- News Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <article>
                    <!-- News Header -->
                    <header class="mb-4">
                        <span class="badge bg-primary mb-2"><?= $news['category_name'] ?></span>
                        <h1 class="display-5 mb-3"><?= $news['title'] ?></h1>
                        <div class="news-meta mb-3">
                            <span><i class="fas fa-user"></i> <?= $news['author_name'] ?></span>
                            <span class="ms-3"><i class="fas fa-calendar"></i> <?= formatPersianDate($news['created_at']) ?></span>
                            <span class="ms-3"><i class="fas fa-eye"></i> <?= $news['views'] + 1 ?></span>
                        </div>
                        <?php if($news['summary']): ?>
                        <div class="alert alert-info">
                            <strong>خلاصه:</strong> <?= $news['summary'] ?>
                        </div>
                        <?php endif; ?>
                    </header>

                    <!-- News Image -->
                    <?php if($news['image_url']): ?>
                    <div class="mb-4">
                        <img src="<?= $news['image_url'] ?>" class="img-fluid rounded" alt="<?= $news['title'] ?>">
                    </div>
                    <?php endif; ?>

                    <!-- News Content -->
                    <div class="news-content">
                        <?= nl2br($news['content']) ?>
                    </div>
                </article>

                <!-- Comments Section -->
                <section class="mt-5">
                    <h4><i class="fas fa-comments"></i> نظرات (<?= count($comments) ?>)</h4>
                    
                    <?php if(isLoggedIn()): ?>
                    <!-- Comment Form -->
                    <div class="comment-box p-4 rounded mb-4">
                        <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">نظر شما</label>
                                <textarea name="content" class="form-control" rows="4" placeholder="نظر خود را بنویسید..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> ارسال نظر
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        برای ثبت نظر لطفا <a href="auth/login.php">وارد شوید</a> یا <a href="auth/register.php">ثبت نام کنید</a>.
                    </div>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <?php if($comments): ?>
                    <div class="comments-list">
                        <?php foreach($comments as $comment): ?>
                        <div class="comment-box p-3 rounded mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong><i class="fas fa-user"></i> <?= $comment['user_name'] ?></strong>
                                <small class="text-muted"><?= formatPersianDate($comment['created_at']) ?></small>
                            </div>
                            <p class="mb-0"><?= nl2br($comment['content']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">هنوز نظری ثبت نشده است.</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <?php if($relatedNews): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-newspaper"></i> اخبار مرتبط</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($relatedNews as $related): ?>
                        <div class="related-news-card mb-3 pb-3 border-bottom">
                            <h6><a href="news.php?slug=<?= $related['slug'] ?>" class="text-decoration-none"><?= $related['title'] ?></a></h6>
                            <small class="text-muted">
                                <?= $related['author_name'] ?> - <?= formatPersianDate($related['created_at']) ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Categories -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tags"></i> دسته‌بندی‌ها</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($categories as $cat): ?>
                        <a href="category.php?slug=<?= $cat['slug'] ?>" class="btn btn-outline-primary btn-sm mb-2"><?= $cat['name'] ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
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