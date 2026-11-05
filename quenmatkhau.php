<?php
session_start();
require_once 'ADMIN/config/connect.php';

$message = "";

if (isset($_POST['btn_khoiphuc'])) {
    $taikhoan = $conn->real_escape_string($_POST['taikhoan']);
    $sodienthoai_email = $conn->real_escape_string($_POST['sodienthoai_email']);
    $matkhaumoi = $conn->real_escape_string($_POST['matkhaumoi']);

    // Kiểm tra thông tin khớp không
    $sql = "SELECT * FROM nguoi_dung WHERE taikhoan = '$taikhoan' AND sodienthoai_email = '$sodienthoai_email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $update_sql = "UPDATE nguoi_dung SET matkhau = '$matkhaumoi' WHERE taikhoan = '$taikhoan'";
        if ($conn->query($update_sql) === TRUE) {
            $message = "<p class='success-msg' style='color:#155724; background:#d4edda; padding:12px; border-radius:5px; margin-top:20px; text-align:center;'>Đổi mật khẩu thành công! <a href='dangnhap.php' style='font-weight:bold; color:#155724; text-decoration:underline;'>Đăng nhập</a></p>";
        } else {
            $message = "<p class='error-msg'>Có lỗi xảy ra!</p>";
        }
    } else {
        $message = "<p class='error-msg'>Tài khoản hoặc SĐT/Email không đúng!</p>";
    }
}

include 'header.php';
?>

</main> <!-- Đóng thẻ main của header -->

<div class="auth-wrapper-fullscreen">
    <div class="auth-box split-layout">
        
        <!-- Cột Trái -->
        <div class="login-left">
            <div class="login-left-content">
                <i class="fas fa-unlock-alt" style="font-size: 70px; margin-bottom: 20px;"></i>
                <h2 style="letter-spacing: 1px;">Khôi Phục</h2>
                <p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">Lấy lại quyền truy cập tài khoản</p>
            </div>
        </div>

        <!-- Cột Phải -->
        <div class="login-right">
            <div class="login-title-shape">quên mật khẩu</div>
            
            <?php echo $message; ?>
            
            <form action="" method="POST" style="margin-top: 40px;">
                <div class="input-wrapper">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" name="taikhoan" required placeholder="Nhập tài khoản của bạn">
                </div>
                
                <div class="input-wrapper">
                    <i class="fas fa-envelope icon-left"></i>
                    <input type="text" name="sodienthoai_email" required placeholder="Nhập SĐT hoặc Email đã đăng ký">
                </div>
                
                <div class="input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="matkhaumoi" id="matkhau_reset" required placeholder="Nhập mật khẩu mới">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('matkhau_reset', this)"></i>
                </div>
                
                <button type="submit" name="btn_khoiphuc" class="btn-login-submit" style="margin-top: 10px;">Đổi Mật Khẩu</button>
                
                <div class="signup-link">
                    <a href="dangnhap.php"><i class="fas fa-arrow-left"></i> Quay lại Đăng nhập</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<main style="display: none;"> <!-- Mở lại thẻ main -->

<?php include 'footer.php'; ?>