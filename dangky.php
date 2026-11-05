<?php
session_start();
require_once 'ADMIN/config/connect.php';

$error = "";
$success = "";

if (isset($_POST['btn_dangky'])) {
    $taikhoan = $conn->real_escape_string($_POST['taikhoan']);
    $matkhau = $conn->real_escape_string($_POST['matkhau']);
    $nhaplaimatkhau = $_POST['nhaplaimatkhau'];
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai_email']);

    // 1. Kiểm tra độ mạnh mật khẩu (Ít nhất 1 hoa, 1 thường, 1 đặc biệt)
    $co_chu_hoa = preg_match('/[A-Z]/', $matkhau);
    $co_chu_thuong = preg_match('/[a-z]/', $matkhau);
    $co_ky_tu_dac_biet = preg_match('/[^a-zA-Z0-9]/', $matkhau);

    // 2. Kiểm tra định dạng Số điện thoại hoặc Email
    $is_phone = preg_match('/^0[0-9]{9}$/', $sodienthoai);
    $is_email = (filter_var($sodienthoai, FILTER_VALIDATE_EMAIL) && strpos($sodienthoai, '@gmail.com') !== false);

    // BẮT ĐẦU CHUỖI KIỂM TRA LỖI
    if (!$is_phone && !$is_email) {
        $error = "Vui lòng nhập Số điện thoại hợp lệ (10 số, bắt đầu bằng 0) hoặc Email (có đuôi @gmail.com)!";
    }
    elseif (strlen($taikhoan) < 8 || strlen($taikhoan) > 20) {
        $error = "Tên đăng nhập phải từ 8 đến 20 ký tự!";
    }
    elseif (!$co_chu_hoa || !$co_chu_thuong || !$co_ky_tu_dac_biet) {
        $error = "Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường và 1 ký tự đặc biệt!";
    } 
    elseif ($matkhau !== $nhaplaimatkhau) {
        $error = "Mật khẩu nhập lại không khớp!";
    } else {
        // Kiểm tra trùng lặp cả tên tài khoản VÀ Số điện thoại/Email
        $check_sql = "SELECT taikhoan, sodienthoai_email FROM nguoi_dung WHERE taikhoan = '$taikhoan' OR sodienthoai_email = '$sodienthoai' LIMIT 1";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if (strtolower($row['taikhoan']) === strtolower($taikhoan)) {
                $error = "Tên tài khoản này đã được sử dụng!";
            } else {
                $error = "Số điện thoại hoặc Email này đã được đăng ký!";
            }
        } else {
            // Thêm người dùng mới vào cơ sở dữ liệu (mặc định trangthai = 1)
            $insert_sql = "INSERT INTO nguoi_dung (taikhoan, matkhau, sodienthoai_email, vaitro, trangthai) VALUES ('$taikhoan', '$matkhau', '$sodienthoai', 'khachhang', 1)";
            if ($conn->query($insert_sql) === TRUE) {
                $success = "Đăng ký thành công! Đang chuyển hướng sang trang đăng nhập...";
                header("refresh:2;url=dangnhap.php");
                include 'header.php';
                echo "<div class='auth-wrapper-fullscreen'><div class='auth-box'><p class='success-msg' style='padding:20px; text-align:center; background:#d4edda; color:#155724; border-radius:5px;'>$success</p></div></div>";
                include 'footer.php';
                exit();
            } else {
                $error = "Có lỗi xảy ra trong quá trình tạo tài khoản, vui lòng thử lại!";
            }
        }
    }
}

// Gọi header
include 'header.php';
?>

</main> <!-- Đóng thẻ main của header để bung nền tràn viền -->

<div class="auth-wrapper-fullscreen">
    <div class="auth-box split-layout">
        
        <!-- Cột Trái (Đỏ) -->
        <div class="login-left">
            <div class="login-left-content">
                <i class="fas fa-user-plus" style="font-size: 70px; margin-bottom: 20px;"></i>
                <h2 style="letter-spacing: 2px;">Tham Gia BookStore</h2>
                <p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">Cùng khám phá hàng ngàn cuốn sách hay</p>
            </div>
        </div>

        <!-- Cột Phải (Form) -->
        <div class="login-right">
            <div class="login-title-shape">Sign Up</div>
            
            <?php 
            if ($error != "") echo "<p class='error-msg'>$error</p>"; 
            if ($success != "") echo "<p class='success-msg' style='color:#155724; background:#d4edda; padding:12px; border-radius:5px; margin-top:20px; text-align:center;'>$success</p>";
            ?>
            
            <form action="" method="POST" style="margin-top: 30px;">
                <div class="input-wrapper">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" name="taikhoan" required minlength="8" maxlength="20" placeholder="Tên tài khoản (8-20 ký tự)" value="<?php echo htmlspecialchars($_POST['taikhoan'] ?? ''); ?>">
                </div>
                
                <div class="input-wrapper" style="margin-bottom: 15px;">
                    <i class="fas fa-envelope icon-left"></i>
                    <input type="text" name="sodienthoai_email" required placeholder="SĐT (10 số) hoặc Email (@gmail.com)" value="<?php echo htmlspecialchars($_POST['sodienthoai_email'] ?? ''); ?>">
                </div>
                
                <div class="input-wrapper" style="margin-bottom: 15px;">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="matkhau" id="matkhau_dangky" required placeholder="Tạo mật khẩu">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('matkhau_dangky', this)"></i>
                </div>
                <!-- Gợi ý mật khẩu -->
                <small style="color: #777; font-size: 11.5px; display: block; margin-bottom: 20px; margin-top:-10px; padding-left: 10px;">VD: BookStore@123 (Có chữ hoa, thường & ký tự đặc biệt)</small>
                
                <div class="input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="nhaplaimatkhau" id="nhaplaimatkhau_dangky" required placeholder="Xác nhận lại mật khẩu">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('nhaplaimatkhau_dangky', this)"></i>
                </div>
                
                <button type="submit" name="btn_dangky" class="btn-login-submit" style="margin-top: 10px;">Đăng Ký</button>
                
                <div class="signup-link">
                    Đã có tài khoản? <a href="dangnhap.php">Đăng nhập</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<main style="display: none;"> <!-- Mở lại thẻ main ẩn để footer không bị lỗi -->

<?php include 'footer.php'; ?>