<?php
/**
 * Trang xử lý Callback đăng nhập bằng Google (OAuth 2.0)
 * Tự động kết nối tới bộ trung tâm API: /api/config.php
 */
session_start();
require_once __DIR__ . '/ADMIN/config/connect.php';
require_once __DIR__ . '/api/config.php';

// Nếu chưa nhận mã Code từ Google, chuyển hướng người dùng tới Google Login
if (!isset($_GET['code'])) {
    $auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online'
    ]);
    header("Location: $auth_url");
    exit();
}

// 2. Nhận Code từ Google và đổi lấy Access Token
$code = $_GET['code'];

$ch = curl_init("https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'code' => $code
]));
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['access_token'])) {
    // 3. Dùng Access Token lấy thông tin User (Email, Họ tên, ID Google)
    $ch = curl_init("https://www.googleapis.com/oauth2/v3/userinfo");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
    $google_user = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($google_user['email'])) {
        $email = $conn->real_escape_string($google_user['email']);
        $name = $conn->real_escape_string($google_user['name'] ?? $google_user['email']);
        $taikhoan = $conn->real_escape_string(explode('@', $email)[0]);

        // Kiểm tra xem User này đã tồn tại trong CSDL chưa
        $check = $conn->query("SELECT * FROM nguoi_dung WHERE sodienthoai_email = '$email' OR taikhoan = '$taikhoan' LIMIT 1");
        
        if ($check && $check->num_rows > 0) {
            $user = $check->fetch_assoc();
        } else {
            // Chưa có -> Tự động Đăng ký tài khoản mới cho khách
            $sql_insert = "INSERT INTO nguoi_dung (taikhoan, hoten, matkhau, sodienthoai_email, vaitro, trangthai) 
                           VALUES ('$taikhoan', '$name', 'GOOGLE_OAUTH', '$email', 'khachhang', 1)";
            $conn->query($sql_insert);
            $user_id = $conn->insert_id;
            $user = ['nguoidung_id' => $user_id, 'taikhoan' => $taikhoan, 'vaitro' => 'khachhang', 'trangthai' => 1];
        }

        // Đăng nhập thành công vào Session
        $_SESSION['nguoidung_id'] = $user['nguoidung_id'];
        $_SESSION['taikhoan'] = $user['taikhoan'];
        $_SESSION['vaitro'] = $user['vaitro'];
        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'Đăng nhập Google thành công! Chào mừng ' . htmlspecialchars($user['taikhoan']) . ' quay trở lại.'
        ];

        header("Location: index.php");
        exit();
    }
}

// Nếu lỗi -> Chuyển về trang đăng nhập kèm báo lỗi
$_SESSION['toast'] = [
    'type' => 'error',
    'message' => 'Đăng nhập bằng Google thất bại hoặc bị hủy!'
];
header("Location: dangnhap.php?error=google_failed");
exit();
