<?php
// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_USER', 'mkiair_admin');
define('DB_PASS', '&yS.M?FsBtxXE09k');
define('DB_NAME', 'mkiair_main');

// اتصال به دیتابیس
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// تنظیمات عمومی
define('SITE_URL', 'https://news-uni.mohammadmkia.ir');
define('UPLOAD_PATH', 'uploads/');

// شروع session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تابع تولید slug فارسی
function createSlug($string) {
    $farsi_to_english = array(
        'ا' => 'a', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 's', 'ج' => 'j',
        'چ' => 'ch', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'z', 'ر' => 'r',
        'ز' => 'z', 'ژ' => 'zh', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'z',
        'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'gh',
        'ک' => 'k', 'گ' => 'g', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'و' => 'v',
        'ه' => 'h', 'ی' => 'y', ' ' => '-'
    );
    
    $slug = str_replace(array_keys($farsi_to_english), array_values($farsi_to_english), $string);
    $slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    return strtolower($slug);
}

// تابع برش متن
function truncateText($text, $length = 150) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

// تابع تبدیل تاریخ
function formatPersianDate($date) {
    $timestamp = strtotime($date);
    return date('Y/m/d H:i', $timestamp);
}

// تابع چک کردن لاگین
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// تابع چک کردن نقش کاربر
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isWriter() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'writer';
}

// تابع redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// تابع امنیت
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>