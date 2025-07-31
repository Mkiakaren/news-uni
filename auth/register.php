<?php
require_once '../config/database.php';

if(isLoggedIn()) {
    redirect('../index.php');
}

$error = '';
$success = '';

if($_POST) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'لطفا تمام فیلدها را پر کنید';
    } elseif($password !== $confirm_password) {
        $error = 'رمز عبور و تکرار آن یکسان نیست';
    } elseif(strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل 6 کاراکتر باشد';
    } else {
        // چک کردن یکتا بودن نام کاربری و ایمیل
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->fetch()) {
            $error = 'نام کاربری یا ایمیل قبلاً ثبت شده است';
        } else {
            // ثبت کاربر جدید
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, role) VALUES (?, ?, ?, ?, 'user')");
            
            if($stmt->execute([$username, $email, $full_name, $hashed_password])) {
                $success = 'ثبت نام با موفقیت انجام شد. می‌توانید وارد شوید.';
            } else {
                $error = 'خطا در ثبت نام. لطفا دوباره تلاش کنید.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام - سایت خبری</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; background: #f8f9fa; }
        .register-card { box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card register-card">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fas fa-user-plus"></i> ثبت نام</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">نام کاربری</label>
                                <input type="text" name="username" class="form-control" value="<?= isset($_POST['username']) ? $_POST['username'] : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ایمیل</label>
                                <input type="email" name="email" class="form-control" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نام کامل</label>
                                <input type="text" name="full_name" class="form-control" value="<?= isset($_POST['full_name']) ? $_POST['full_name'] : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رمز عبور</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">تکرار رمز عبور</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-user-plus"></i> ثبت نام
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p>حساب کاربری دارید؟ <a href="login.php">وارد شوید</a></p>
                            <a href="../index.php" class="text-muted">بازگشت به صفحه اصلی</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>