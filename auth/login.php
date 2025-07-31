<?php
require_once '../config/database.php';

if(isLoggedIn()) {
    if(isAdmin()) {
        redirect('../admin/dashboard.php');
    } elseif(isWriter()) {
        redirect('../writer/dashboard.php');
    } else {
        redirect('../user/dashboard.php');
    }
}

$error = '';

if($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'لطفا تمام فیلدها را پر کنید';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            if($user['role'] == 'admin') {
                redirect('../admin/dashboard.php');
            } elseif($user['role'] == 'writer') {
                redirect('../writer/dashboard.php');
            } else {
                redirect('../user/dashboard.php');
            }
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود - سایت خبری</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #f8f9fa; }
        .login-card { box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="fas fa-sign-in-alt"></i> ورود به سیستم</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">نام کاربری</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رمز عبور</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> ورود
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p>حساب کاربری ندارید؟ <a href="register.php">ثبت نام کنید</a></p>
                            <a href="../index.php" class="text-muted">بازگشت به صفحه اصلی</a>
                        </div>
                        
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>حساب‌های نمونه:</strong><br>
                                مدیر: admin / password<br>
                                نویسنده: writer1 / password<br>
                                کاربر: user1 / password
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>