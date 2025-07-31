<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isWriter()) {
    redirect('../auth/login.php');
}

$message = '';
$action = $_GET['action'] ?? 'list';

// دریافت لیست کامنت‌ها روی اخبار نویسنده
if ($action == 'list') {
    $where = '';
    $params = [$_SESSION['user_id']];
    
    if (isset($_GET['status'])) {
        $where = 'AND c.status = ?';
        $params[] = $_GET['status'];
    }
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as user_name, n.title as news_title, n.slug as news_slug
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN news n ON c.news_id = n.id 
        WHERE n.author_id = ? $where 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $comments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظرات - پنل نویسندگی</title>
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
                            <a class="nav-link text-white active" href="comments.php">
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
                        <h2>نظرات</h2>
                        <a href="?status=pending" class="btn btn-info">
                            <i class="fas fa-clock"></i> نمایش نظرات در انتظار
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>کاربر</th>
                                            <th>خبر</th>
                                            <th>متن</th>
                                            <th>وضعیت</th>
                                            <th>تاریخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($comments && count($comments) > 0): ?>
                                        <?php foreach ($comments as $comment): ?>
                                        <tr>
                                            <td><?= $comment['id'] ?></td>
                                            <td><?= $comment['user_name'] ?></td>
                                            <td><a href="../news.php?slug=<?= $comment['news_slug'] ?>" target="_blank"><?= truncateText($comment['news_title'], 30) ?></a></td>
                                            <td><?= truncateText($comment['content'], 50) ?></td>
                                            <td>
                                                <?php if ($comment['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">تایید شده</span>
                                                <?php elseif ($comment['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">در انتظار</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">رد شده</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatPersianDate($comment['created_at']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">هیچ نظری یافت نشد.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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