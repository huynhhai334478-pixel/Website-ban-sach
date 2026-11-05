<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

$msg_success = "";
$msg_error = "";

// Lấy thông tin tài khoản nếu đã đăng nhập để tự động điền sẵn
$user_info = [
    'hoten' => '',
    'email' => '',
    'sodienthoai' => ''
];

if (isset($_SESSION['nguoidung_id'])) {
    $uid = intval($_SESSION['nguoidung_id']);
    $u_res = $conn->query("SELECT * FROM nguoi_dung WHERE nguoidung_id = $uid LIMIT 1");
    if ($u_res && $u_res->num_rows > 0) {
        $u_data = $u_res->fetch_assoc();
        $user_info['hoten'] = $u_data['hoten'] ?? $u_data['taikhoan'] ?? '';
        $user_info['email'] = (filter_var($u_data['sodienthoai_email'] ?? '', FILTER_VALIDATE_EMAIL)) ? $u_data['sodienthoai_email'] : '';
        $user_info['sodienthoai'] = (!filter_var($u_data['sodienthoai_email'] ?? '', FILTER_VALIDATE_EMAIL)) ? $u_data['sodienthoai_email'] : '';
    }
}

// Xử lý gửi tin nhắn liên hệ
if (isset($_POST['btn_send_contact'])) {
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $email = $conn->real_escape_string($_POST['email']);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $noidung = $conn->real_escape_string($_POST['noidung']);

    if (empty($hoten) || empty($noidung)) {
        $msg_error = "Vui lòng điền đầy đủ Họ tên và Nội dung tin nhắn!";
    } else {
        $sql_in = "INSERT INTO lien_he (hoten, email, sodienthoai, noidung, ngaygui, trangthai) 
                   VALUES ('$hoten', '$email', '$sodienthoai', '$noidung', NOW(), 'chuxuly')";
        if ($conn->query($sql_in)) {
            $msg_success = "Cảm ơn bạn đã liên hệ! Tin nhắn của bạn đã được gửi thành công tới Ban quản trị.";
        } else {
            $msg_error = "Lỗi gửi tin nhắn: " . $conn->error;
        }
    }
}
?>

<div style="max-width: 1100px; margin: 30px auto; padding: 0 15px;">
    
    <div style="background: #fff; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between;">
        <h2 style="font-size: 22px; color: #333; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-paper-plane" style="color: #C92127;"></i> Liên Hệ Ban Quản Trị
        </h2>
        <span style="font-size: 13.5px; color: #666;">Chúng tôi luôn sẵn sàng lắng nghe ý kiến của bạn!</span>
    </div>

    <!-- Thông báo kết quả -->
    <?php if (!empty($msg_success)): ?>
        <div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 14px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle fa-lg"></i> <?= $msg_success ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($msg_error)): ?>
        <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 14px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle fa-lg"></i> <?= $msg_error ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 30px; align-items: start;">
        
        <!-- CỘT TRÁI: THÔNG TIN LIÊN HỆ CỬA HÀNG -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <h3 style="font-size: 16px; color: #333; margin: 0 0 20px 0; border-bottom: 2px solid #C92127; padding-bottom: 8px; text-transform: uppercase;">
                Thông Tin Cửa Hàng
            </h3>

            <div style="display: flex; flex-direction: column; gap: 18px; font-size: 14px; color: #444;">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fas fa-building" style="color: #C92127; font-size: 18px; margin-top: 3px;"></i>
                    <div>
                        <strong>BOOKSTORE</strong>
                        <div style="color: #666; font-size: 13px; margin-top: 2px;">Cửa hàng sách trực tuyến uy tín hàng đầu.</div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fas fa-map-marker-alt" style="color: #C92127; font-size: 18px; margin-top: 3px;"></i>
                    <div>
                        <strong>Địa chỉ:</strong>
                        <div style="color: #666; font-size: 13px; margin-top: 2px;">Cần Thơ, Việt Nam</div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fas fa-phone-alt" style="color: #28a745; font-size: 18px; margin-top: 3px;"></i>
                    <div>
                        <strong>Điện thoại Hotline:</strong>
                        <div style="color: #666; font-size: 13px; margin-top: 2px;">0123.456.789 (8h00 - 21h00 các ngày trong tuần)</div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <i class="fas fa-envelope" style="color: #0F62FE; font-size: 18px; margin-top: 3px;"></i>
                    <div>
                        <strong>Email hỗ trợ:</strong>
                        <div style="color: #666; font-size: 13px; margin-top: 2px;">hotro@bookstore.com</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #eee; font-size: 13px; color: #777;">
                <i class="fas fa-info-circle" style="color: #0F62FE;"></i> Mọi tin nhắn gửi qua biểu mẫu sẽ được gửi trực tiếp đến trang Quản trị Admin để kiểm tra và xử lý sớm nhất.
            </div>
        </div>

        <!-- CỘT PHẢI: FORM GỬI TIN NHẮN -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <h3 style="font-size: 16px; color: #333; margin: 0 0 20px 0; border-bottom: 2px solid #C92127; padding-bottom: 8px; text-transform: uppercase;">
                Gửi Tin Nhắn Cho Chúng Tôi
            </h3>

            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Họ và Tên <span style="color: red;">*</span></label>
                    <input type="text" name="hoten" required placeholder="Nhập họ và tên của bạn" 
                           value="<?= htmlspecialchars($user_info['hoten']) ?>" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Email</label>
                        <input type="email" name="email" placeholder="Địa chỉ email (nếu có)" 
                               value="<?= htmlspecialchars($user_info['email']) ?>" 
                               style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Số Điện Thoại</label>
                        <input type="text" name="sodienthoai" placeholder="Số điện thoại liên hệ" 
                               value="<?= htmlspecialchars($user_info['sodienthoai']) ?>" 
                               style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Nội Dung Góp Ý / Thắc Mắc <span style="color: red;">*</span></label>
                    <textarea name="noidung" rows="5" required placeholder="Nhập câu hỏi, thắc mắc hoặc nội dung đóng góp ý kiến cho cửa hàng..." 
                              style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; resize: vertical;"></textarea>
                </div>

                <button type="submit" name="btn_send_contact" style="width: 100%; background: #C92127; color: white; border: none; padding: 13px 0; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.25s; box-shadow: 0 4px 12px rgba(201, 33, 39, 0.25);">
                    <i class="fas fa-paper-plane"></i> GỬI TIN NHẮN LIÊN HỆ
                </button>
            </form>
        </div>

    </div>

</div>

<?php
$conn->close();
include 'footer.php';
?>
