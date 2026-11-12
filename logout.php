<?php
session_start();

// Xóa toàn bộ dữ liệu phiên và cookie phiên
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Không cho trình duyệt lưu cache, tránh bấm nút Back quay lại trang cũ
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: login.php?logout=1');
exit;