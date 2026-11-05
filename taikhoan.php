<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['nguoidung_id'])) {
    echo '
    <div style="max-width: 800px; margin: 50px auto; text-align: center; background: #fff; padding: 40px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <i class="fas fa-user-lock" style="font-size: 60px; color: #C92127; margin-bottom: 20px;"></i>
        <h2 style="font-size: 22px; color: #333; margin-bottom: 10px;">Vui lòng đăng nhập để xem thông tin tài khoản!</h2>
        <a href="dangnhap.php" class="btn-primary" style="background: #C92127; border-radius: 25px; padding: 10px 30px; font-weight: bold; text-decoration: none; color: white;">Đăng nhập ngay</a>
    </div>';
    include 'footer.php';
    exit();
}

$nguoidung_id = intval($_SESSION['nguoidung_id']);
$success_msg = "";
$error_msg = "";

// 1. Xử lý CẬP NHẬT THÔNG TIN CÁ NHÂN
if (isset($_POST['btn_update_info'])) {
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $sodienthoai_email = $conn->real_escape_string($_POST['sodienthoai_email']);
    $diachi = $conn->real_escape_string($_POST['diachi']);

    $sql_up = "UPDATE nguoi_dung SET hoten = '$hoten', sodienthoai_email = '$sodienthoai_email', diachi = '$diachi' WHERE nguoidung_id = $nguoidung_id";
    if ($conn->query($sql_up)) {
        $success_msg = "Cập nhật thông tin cá nhân thành công!";
    } else {
        $error_msg = "Lỗi cập nhật: " . $conn->error;
    }
}

// 2. Xử lý ĐỔI MẬT KHẨU
if (isset($_POST['btn_change_password'])) {
    $old_pass = $conn->real_escape_string($_POST['old_pass']);
    $new_pass = $conn->real_escape_string($_POST['new_pass']);
    $confirm_pass = $conn->real_escape_string($_POST['confirm_pass']);

    // Kiểm tra mật khẩu cũ đúng không
    $chk = $conn->query("SELECT matkhau FROM nguoi_dung WHERE nguoidung_id = $nguoidung_id AND matkhau = '$old_pass' LIMIT 1");
    if (!$chk || $chk->num_rows == 0) {
        $error_msg = "Mật khẩu hiện tại không chính xác!";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "Mật khẩu mới và Nhập lại mật khẩu không trùng khớp!";
    } elseif (strlen($new_pass) < 4) {
        $error_msg = "Mật khẩu mới phải có ít nhất 4 ký tự!";
    } else {
        $conn->query("UPDATE nguoi_dung SET matkhau = '$new_pass' WHERE nguoidung_id = $nguoidung_id");
        $success_msg = "Đổi mật khẩu thành công!";
    }
}

// Lấy thông tin mới nhất từ CSDL
$res_user = $conn->query("SELECT * FROM nguoi_dung WHERE nguoidung_id = $nguoidung_id LIMIT 1");
$user = $res_user->fetch_assoc();
?>

<div style="max-width: 1000px; margin: 30px auto; padding: 0 15px;">
    
    <div style="background: #fff; padding: 18px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between;">
        <h2 style="font-size: 20px; color: #333; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-id-card" style="color: #C92127;"></i> Hồ Sơ Tài Khoản Của Tôi
        </h2>
        <a href="donhang_cuatoi.php" style="color: #0F62FE; text-decoration: none; font-size: 14px; font-weight: 600;">
            <i class="fas fa-box"></i> Xem đơn hàng của tôi &gt;
        </a>
    </div>

    <!-- Hộp thông báo -->
    <?php if (!empty($success_msg)): ?>
        <div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            <i class="fas fa-check-circle"></i> <?= $success_msg ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        
        <!-- CỘT 1: THÔNG TIN CÁ NHÂN -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <h3 style="font-size: 16px; color: #333; margin: 0 0 20px 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-edit" style="color: #C92127;"></i> Thông Tin Cá Nhân
            </h3>

            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #555; margin-bottom: 6px;">Tên tài khoản (Không thể sửa)</label>
                    <input type="text" value="<?= htmlspecialchars($user['taikhoan']) ?>" disabled 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #f8f9fa; color: #777;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Họ và Tên</label>
                    <input type="text" name="hoten" required placeholder="Nhập họ và tên đầy đủ" 
                           value="<?= htmlspecialchars($user['hoten'] ?? '') ?>" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Số điện thoại / Email</label>
                    <input type="text" name="sodienthoai_email" required placeholder="Nhập số điện thoại hoặc email liên hệ" 
                           value="<?= htmlspecialchars($user['sodienthoai_email'] ?? '') ?>" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Địa chỉ giao hàng mặc định</label>
                    <textarea name="diachi" rows="3" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành" 
                              style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; resize: vertical;"><?= htmlspecialchars($user['diachi'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="btn_update_info" style="width: 100%; background: #C92127; color: white; border: none; padding: 12px 0; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                    <i class="fas fa-save"></i> Lưu Thay Đổi
                </button>
            </form>
        </div>

        <!-- CỘT 2: ĐỔI MẬT KHẨU -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <h3 style="font-size: 16px; color: #333; margin: 0 0 20px 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-key" style="color: #0F62FE;"></i> Đổi Mật Khẩu
            </h3>

            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Mật khẩu hiện tại <span style="color: red;">*</span></label>
                    <input type="password" name="old_pass" required placeholder="Nhập mật khẩu hiện tại" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Mật khẩu mới <span style="color: red;">*</span></label>
                    <input type="password" name="new_pass" required placeholder="Nhập mật khẩu mới" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Xác nhận mật khẩu mới <span style="color: red;">*</span></label>
                    <input type="password" name="confirm_pass" required placeholder="Nhập lại mật khẩu mới" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                </div>

                <button type="submit" name="btn_change_password" style="width: 100%; background: #0F62FE; color: white; border: none; padding: 12px 0; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                    <i class="fas fa-lock"></i> Đổi Mật Khẩu
                </button>
            </form>
        </div>

    </div>

</div>

<?php
$conn->close();
include 'footer.php';
?>
