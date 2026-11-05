<?php
session_start();
require_once 'ADMIN/config/connect.php';

$error = "";

if (isset($_POST['btn_dangnhap'])) {
    $taikhoan = $conn->real_escape_string($_POST['taikhoan']);
    $matkhau = $conn->real_escape_string($_POST['matkhau']);

    $sql = "SELECT * FROM nguoi_dung WHERE taikhoan = '$taikhoan' AND matkhau = '$matkhau'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra trạng thái tài khoản
        if (isset($user['trangthai']) && intval($user['trangthai']) === 0) {
            $error = "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên!";
        } else {
            $_SESSION['nguoidung_id'] = $user['nguoidung_id'];
            $_SESSION['taikhoan'] = $user['taikhoan'];
            $_SESSION['vaitro'] = $user['vaitro'];

            if ($user['vaitro'] == 'admin') {
                $_SESSION['toast'] = [
                    'type' => 'success',
                    'message' => 'Đăng nhập Quản trị viên thành công!'
                ];
                header("Location: ADMIN/index.php");
            } else {
                $_SESSION['toast'] = [
                    'type' => 'success',
                    'message' => 'Đăng nhập thành công! Chào mừng ' . htmlspecialchars($user['taikhoan']) . ' quay trở lại.'
                ];
                header("Location: index.php");
            }
            exit();
        }
    } else {
        $error = "Tài khoản hoặc mật khẩu không chính xác!";
    }
}

// Cấu hình đường dẫn OAuth 2.0
$google_auth_url = "google_callback.php";
$facebook_auth_url = "facebook_callback.php";

// Gọi header
include 'header.php';
?>

</main> <!-- Đóng thẻ main.container của header để form được bung viền 100% -->

<!-- Nền tràn viền 100% -->
<div class="auth-wrapper-fullscreen">
    <div class="auth-box split-layout">
        
        <!-- Cột Trái: Đỏ Tươi + Logo -->
        <div class="login-left">
            <div class="login-left-content">
                <i class="fas fa-book-reader" style="font-size: 70px; margin-bottom: 20px;"></i>
                <h2 style="letter-spacing: 2px;">BookStore</h2>
                <p style="font-size: 14px; opacity: 0.9;">Đăng nhập để nhận ngay các ưu đãi độc quyền!</p>
            </div>
        </div>

        <!-- Cột Phải: Form Đăng nhập -->
        <div class="login-right">
            <!-- Khối chữ Login bo góc chéo ở trên -->
            <div class="login-title-shape">Đăng nhập</div>
            
            <?php if ($error != "") echo "<p class='error-msg'>$error</p>"; ?>
            
            <form action="" method="POST" style="margin-top: 30px;">
                <div class="input-wrapper">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" name="taikhoan" required placeholder="Tài khoản">
                </div>
                
                <div class="input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="matkhau" id="matkhau_login" required placeholder="Mật khẩu">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('matkhau_login', this)"></i>
                </div>
                
                <div class="forgot-link">
                    <a href="quenmatkhau.php">Quên mật khẩu?</a>
                </div>
                
                <button type="submit" name="btn_dangnhap" class="btn-login-submit">Đăng Nhập</button>
                
                <!-- Phân cách đăng nhập mạng xã hội -->
                <div class="social-divider">
                    <span>Hoặc đăng nhập bằng</span>
                </div>

                <!-- Nút đăng nhập Google & Facebook dạng ICON TRÒN DÙNG INLINE SVG & FONTAWESOME -->
                <div class="social-btn-container">
                    <a href="<?php echo $google_auth_url; ?>" class="btn-social-icon btn-google" title="Đăng nhập bằng Google">
                        <svg width="22" height="22" viewBox="0 0 24 24" style="display: block;">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                    </a>
                    <a href="<?php echo $facebook_auth_url; ?>" class="btn-social-icon btn-facebook" title="Đăng nhập bằng Facebook">
                        <i class="fab fa-facebook-f" style="font-size: 20px; color: #ffffff;"></i>
                    </a>
                </div>

                <div class="signup-link">
                    Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<main style="display: none;"> <!-- Mở lại thẻ main ẩn để footer.php đóng lại không bị lỗi cấu trúc HTML -->

<?php
// Gọi footer
include 'header.php';
?>