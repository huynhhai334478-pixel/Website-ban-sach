<?php
/**
 * Trang xử lý Callback đăng nhập bằng Facebook (OAuth 2.0)
 * Tự động kết nối tới bộ trung tâm API: /api/config.php
 */
session_start();
require_once __DIR__ . '/ADMIN/config/connect.php';
require_once __DIR__ . '/api/config.php';

// Nếu chưa nhận mã Code từ Facebook, chuyển hướng người dùng tới Facebook Login
if (!isset($_GET['code'])) {
    $auth_url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
        'client_id' => FB_APP_ID,
        'redirect_uri' => FB_REDIRECT_URI,
        'scope' => 'email,public_profile'
    ]);
    header("Location: $auth_url");
    exit();
}

// 2. Nhận Code từ Facebook và đổi lấy Access Token
$code = $_GET['code'];

$token_url = "https://graph.facebook.com/v18.0/oauth/access_token?" . http_build_query([
    'client_id' => FB_APP_ID,
    'client_secret' => FB_APP_SECRET,
    'redirect_uri' => FB_REDIRECT_URI,
    'code' => $code
]);

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['access_token'])) {
    // 3. Lấy thông tin tài khoản Facebook (ID, Name, Email)
    $info_url = "https://graph.facebook.com/me?fields=id,name,email&access_token=" . $response['access_token'];
    $ch = curl_init($info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $fb_user = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($fb_user['id'])) {
        $fb_id = $conn->real_escape_string($fb_user['id']);
        $email = isset($fb_user['email']) ? $conn->real_escape_string($fb_user['email']) : "fb_$fb_id@facebook.com";
        $name = $conn->real_escape_string($fb_user['name'] ?? 'Khách Facebook');
        $taikhoan = "fb_" . $fb_id;

        // Kiểm tra xem User đã có trong CSDL chưa
        $check = $conn->query("SELECT * FROM nguoi_dung WHERE sodienthoai_email = '$email' OR taikhoan = '$taikhoan' LIMIT 1");

        if ($check && $check->num_rows > 0) {
            $user = $check->fetch_assoc();
        } else {
            // Chưa có -> Tự động đăng ký mới
            $sql_insert = "INSERT INTO nguoi_dung (taikhoan, hoten, matkhau, sodienthoai_email, vaitro, trangthai) 
                           VALUES ('$taikhoan', '$name', 'FACEBOOK_OAUTH', '$email', 'khachhang', 1)";
            $conn->query($sql_insert);
            $user_id = $conn->insert_id;
            $user = ['nguoidung_id' => $user_id, 'taikhoan' => $taikhoan, 'vaitro' => 'khachhang', 'trangthai' => 1];
        }

        // Đăng nhập thành công
        $_SESSION['nguoidung_id'] = $user['nguoidung_id'];
        $_SESSION['taikhoan'] = $user['taikhoan'];
        $_SESSION['vaitro'] = $user['vaitro'];
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Đăng nhập Facebook thành công! Chào mừng ' . htmlspecialchars($user['taikhoan']) . ' quay trở lại.'
        ];

        header("Location: index.php");
        exit();
    }
}

// Nếu lỗi -> Quay lại trang đăng nhập
$_SESSION['toast'] = [
    'type' => 'error',
    'message' => 'Đăng nhập bằng Facebook thất bại hoặc bị hủy!'
];
header("Location: dangnhap.php?error=facebook_failed");
exit();
