<?php
require_once '../config/database.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    redirect('../auth/login.php');
}

// Handle comment deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $comment_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        if ($stmt->execute([$comment_id])) {
            $message = 'نظر با موفقیت حذف شد.';
        } else {
            $message = 'خطا در حذف نظر.';
        }
    } else {
        $message = 'شما مجاز به حذف این نظر نیستید.';
    }
}

// Fetch user's comments
$stmt = $pdo->prepare("
    SELECT c.*, n.title as news_title, n.slug as news_slug
    FROM comments c 
    JOIN news n ON c.news_id = n.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل کاربری - سایت خبری</title>
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
                        <h5><i class="fas fa-user"></i> پنل کاربری</h5>
                        <hr>
                        <p class="mb-0">خوش آمدید<br><strong><?= $_SESSION['full_name'] ?></strong></p>
                    </div>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> داشبورد
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
                    <h2 class="mb-4">نظرات من</h2>

                    <?php if (isset($message)): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>خبر</th>
                                            <th>متن</th>
                                            <th>وضعیت</th>
                                            <th>تاریخ</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($comments && count($comments) > 0): ?>
                                        <?php foreach ($comments as $comment): ?>
                                        <tr>
                                            <td><?= $comment['id'] ?></td>
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
                                            <td>
                                                <a href="?action=delete&id=<?= $comment['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا مطمئن هستید که می‌خواهید این نظر را حذف کنید؟')">
                                                    <i class="fas fa-trash"></i> حذف
                                                </a>
                                            </td>
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>