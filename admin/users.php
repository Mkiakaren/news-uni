<?php
require_once '../config/database.php';

if(!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$message = '';
$action = $_GET['action'] ?? 'list';

// حذف کاربر
if(isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    if($userId != $_SESSION['user_id']) { // جلوگیری از حذف خود
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if($stmt->execute([$userId])) {
            $message = 'کاربر با موفقیت حذف شد.';
        }
    }
}

// تغییر وضعیت کاربر
if(isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    if($userId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
        if($stmt->execute([$userId])) {
            $message = 'وضعیت کاربر تغییر یافت.';
        }
    }
}

// ورود به عنوان کاربر
if(isset($_GET['login_as'])) {
    $userId = (int)$_GET['login_as'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if($user) {
        $_SESSION['original_admin_id'] = $_SESSION['user_id'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        if($user['role'] == 'writer') {
            redirect('../writer/dashboard.php');
        } else {
            redirect('../index.php');
        }
    }
}

// افزودن کاربر جدید
if($_POST && $action == 'add') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if(empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $message = 'لطفا تمام فیلدها را پر کنید.';
    } else {
        // چک کردن یکتا بودن
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->fetch()) {
            $message = 'نام کاربری یا ایمیل قبلاً ثبت شده است.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, role) VALUES (?, ?, ?, ?, ?)");
            
            if($stmt->execute([$username, $email, $full_name, $hashed_password, $role])) {
                $message = 'کاربر جدید با موفقیت اضافه شد.';
                $action = 'list';
            }
        }
    }
}

// ویرایش کاربر
if($_POST && $action == 'edit') {
    $userId = (int)$_POST['user_id'];
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    if(empty($username) || empty($email) || empty($full_name)) {
        $message = 'لطفا فیلدهای اجباری را پر کنید.';
    } else {
        // چک کردن یکتا بودن
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $userId]);
        
        if($stmt->fetch()) {
            $message = 'نام کاربری یا ایمیل قبلاً ثبت شده است.';
        } else {
            if(!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, password = ?, role = ? WHERE id = ?");
                $result = $stmt->execute([$username, $email, $full_name, $hashed_password, $role, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?");
                $result = $stmt->execute([$username, $email, $full_name, $role, $userId]);
            }
            
            if($result) {
                $message = 'کاربر با موفقیت ویرایش شد.';
                $action = 'list';
            }
        }
    }
}

// دریافت لیست کاربران
if($action == 'list') {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
}

// دریافت اطلاعات کاربر برای ویرایش
if($action == 'edit' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $editUser = $stmt->fetch();
    
    if(!$editUser) {
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران - پنل مدیریت</title>
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
                            <a class="nav-link text-white active" href="users.php">
                                <i class="fas fa-users"></i> کاربران
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="categories.php">
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
                        <h2>مدیریت کاربران</h2>
                        <a href="?action=add" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> افزودن کاربر جدید
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>نام کاربری</th>
                                            <th>نام کامل</th>
                                            <th>ایمیل</th>
                                            <th>نقش</th>
                                            <th>وضعیت</th>
                                            <th>تاریخ عضویت</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= $user['username'] ?></td>
                                            <td><?= $user['full_name'] ?></td>
                                            <td><?= $user['email'] ?></td>
                                            <td>
                                                <?php if($user['role'] == 'admin'): ?>
                                                    <span class="badge bg-danger">مدیر</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">نویسنده</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($user['status'] == 'active'): ?>
                                                    <span class="badge bg-success">فعال</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">غیرفعال</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatPersianDate($user['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?toggle_status=<?= $user['id'] ?>" class="btn btn-sm btn-info" 
                                                       onclick="return confirm('آیا از تغییر وضعیت این کاربر اطمینان دارید؟')">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </a>
                                                    <a href="?login_as=<?= $user['id'] ?>" class="btn btn-sm btn-secondary"
                                                       onclick="return confirm('آیا می‌خواهید به عنوان این کاربر وارد شوید؟')">
                                                        <i class="fas fa-sign-in-alt"></i>
                                                    </a>
                                                    <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger"
                                                       onclick="return confirm('آیا از حذف این کاربر اطمینان دارید؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
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
                        <h2>افزودن کاربر جدید</h2>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نام کامل</label>
                                        <input type="text" name="full_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نقش</label>
                                        <select name="role" class="form-select" required>
                                            <option value="writer">نویسنده</option>
                                            <option value="admin">مدیر</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">رمز عبور</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> ذخیره
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php elseif($action == 'edit' && isset($editUser)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>ویرایش کاربر</h2>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> بازگشت
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نام کاربری</label>
                                        <input type="text" name="username" class="form-control" value="<?= $editUser['username'] ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ایمیل</label>
                                        <input type="email" name="email" class="form-control" value="<?= $editUser['email'] ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نام کامل</label>
                                        <input type="text" name="full_name" class="form-control" value="<?= $editUser['full_name'] ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">نقش</label>
                                        <select name="role" class="form-select" required>
                                            <option value="writer" <?= $editUser['role'] == 'writer' ? 'selected' : '' ?>>نویسنده</option>
                                            <option value="admin" <?= $editUser['role'] == 'admin' ? 'selected' : '' ?>>مدیر</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">رمز عبور جدید (اختیاری)</label>
                                    <input type="password" name="password" class="form-control">
                                    <small class="form-text text-muted">برای تغییر رمز عبور پر کنید، در غیر این صورت خالی بگذارید</small>
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