<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isWriter()) {
    redirect('../auth/login.php');
}

// آمار نویسنده
$stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE author_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalNews = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE author_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$approvedNews = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE author_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pendingNews = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(views) FROM news WHERE author_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$totalViews = $stmt->fetchColumn() ?: 0;

// تعداد کامنت‌های در انتظار روی اخبار نویسنده
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM comments c 
    JOIN news n ON c.news_id = n.id 
    WHERE n.author_id = ? AND c.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$pendingComments = $stmt->fetchColumn();

// آخرین اخبار نویسنده
$stmt = $pdo->prepare("
    SELECT n.*, c.name as category_name 
    FROM news n 
    JOIN categories c ON n.category_id = c.id 
    WHERE n.author_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$latestNews = $stmt->fetchAll();

// آخرین کامنت‌ها روی اخبار نویسنده
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as user_name, n.title as news_title 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    JOIN news n ON c.news_id = n.id 
    WHERE n.author_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$latestComments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل نویسندگی - سایت خبری</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #f8f9fa; }
        .sidebar { min-height: 100vh; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
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
                            <a class="nav-link text-white active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> داشبورد
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="news.php">
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
                                <?php if ($pendingComments > 0): ?>
                                <span class="badge bg-warning"><?= $pendingComments ?></span>
                                <?php endif; ?>
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
                    <h2 class="mb-4">داشبورد نویسندگی</h2>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $totalNews ?></h4>
                                            <p class="mb-0">کل اخبار</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-newspaper fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $approvedNews ?></h4>
                                            <p class="mb-0">اخبار تایید شده</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $pendingNews ?></h4>
                                            <p class="mb-0">در انتظار تایید</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= number_format($totalViews) ?></h4>
                                            <p class="mb-0">کل بازدیدها</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-eye fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Latest News -->
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5><i class="fas fa-newspaper"></i> آخرین اخبار من</h5>
                                    <a href="news.php" class="btn btn-sm btn-primary">مشاهده همه</a>
                                </div>
                                <div class="card-body">
                                    <?php if ($latestNews): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>عنوان</th>
                                                    <th>دسته‌بندی</th>
                                                    <th>وضعیت</th>
                                                    <th>تاریخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($latestNews as $news): ?>
                                                <tr>
                                                    <td><?= truncateText($news['title'], 40) ?></td>
                                                    <td><?= $news['category_name'] ?></td>
                                                    <td>
                                                        <?php if ($news['status'] == 'approved'): ?>
                                                            <span class="badge bg-success">تایید شده</span>
                                                        <?php elseif ($news['status'] == 'pending'): ?>
                                                            <span class="badge bg-warning">در انتظار</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">رد شده</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= formatPersianDate($news['created_at']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted">خبری یافت نشد.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Latest Comments -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5><i class="fas fa-comments"></i> آخرین نظرات</h5>
                                    <a href="comments.php" class="btn btn-sm btn-primary">مشاهده همه</a>
                                </div>
                                <div class="card-body">
                                    <?php if ($latestComments): ?>
                                    <?php foreach ($latestComments as $comment): ?>
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <strong><?= $comment['user_name'] ?></strong>
                                            <?php if ($comment['status'] == 'approved'): ?>
                                                <span class="badge bg-success">تایید شده</span>
                                            <?php elseif ($comment['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">در انتظار</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">رد شده</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="small mb-1"><?= truncateText($comment['content'], 60) ?></p>
                                        <small class="text-muted">
                                            روی: <?= truncateText($comment['news_title'], 30) ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <p class="text-muted">نظری یافت نشد.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-bolt"></i> عملیات سریع</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <a href="news.php?action=add" class="btn btn-success w-100">
                                                <i class="fas fa-plus"></i> افزودن خبر جدید
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="news.php?status=pending" class="btn btn-warning w-100">
                                                <i class="fas fa-clock"></i> اخبار در انتظار
                                                <?php if ($pendingNews > 0): ?>
                                                <span class="badge bg-light text-dark"><?= $pendingNews ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="comments.php?status=pending" class="btn btn-info w-100">
                                                <i class="fas fa-comments"></i> نظرات در انتظار
                                                <?php if ($pendingComments > 0): ?>
                                                <span class="badge bg-light text-dark"><?= $pendingComments ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>