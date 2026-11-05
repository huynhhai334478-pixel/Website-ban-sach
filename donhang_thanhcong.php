<?php
header("Location: donhang_cuatoi.php");
exit();
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

$donhang_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($donhang_id <= 0) {
    echo '<div class="container" style="padding: 50px; text-align: center;"><h3>Đơn hàng không hợp lệ!</h3><a href="index.php">Quay lại trang chủ</a></div>';
    include 'footer.php';
    exit();
}

// Lấy thông tin đơn hàng
$sql_dh = "SELECT d.*, v.ma as ma_voucher 
           FROM don_hang d 
           LEFT JOIN voucher v ON d.voucher_id = v.voucher_id 
           WHERE d.donhang_id = $donhang_id LIMIT 1";
$res_dh = $conn->query($sql_dh);

if (!$res_dh || $res_dh->num_rows == 0) {
    echo '<div class="container" style="padding: 50px; text-align: center;"><h3>Không tìm thấy đơn hàng #'.$donhang_id.'!</h3><a href="index.php">Quay lại trang chủ</a></div>';
    include 'footer.php';
    exit();
}

$dh = $res_dh->fetch_assoc();

// Lấy danh sách chi tiết sản phẩm trong đơn hàng
$sql_ct = "SELECT c.*, s.tensach, s.hinhanh, s.tacgia 
           FROM chi_tiet_don_hang c 
           JOIN sach s ON c.sach_id = s.sach_id 
           WHERE c.donhang_id = $donhang_id";
$res_ct = $conn->query($sql_ct);
?>

<div style="max-width: 900px; margin: 30px auto; padding: 0 15px;">
    
    <!-- KHUNG THÔNG BÁO THÀNH CÔNG -->
    <div style="background: #fff; padding: 35px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 4px 20px rgba(0,0,0,0.06); text-align: center; margin-bottom: 25px;">
        <i class="fas fa-check-circle" style="font-size: 70px; color: #28a745; margin-bottom: 15px;"></i>
        <h2 style="font-size: 24px; color: #333; margin-bottom: 8px;">CẢM ƠN BẠN ĐÃ ĐẶT HÀNG THÀNH CÔNG!</h2>
        <p style="font-size: 15px; color: #666; margin-bottom: 5px;">Mã đơn hàng của bạn là: <strong style="color: #C92127; font-size: 18px;">#DH-<?= $donhang_id ?></strong></p>
        <p style="font-size: 13.5px; color: #888;">Chúng tôi đã ghi nhận đơn hàng và sẽ liên hệ giao hàng trong thời gian sớm nhất.</p>
    </div>

    <!-- NẾU CHỌN CHUYỂN KHOẢN QR -> HIỂN THỊ MÃ QR TỰ ĐỘNG -->
    <?php if (strpos($dh['phuongthuc_thanhtoan'], 'QR') !== false || strpos($dh['phuongthuc_thanhtoan'], 'Chuyển khoản') !== false): ?>
        <div style="background: #FFF9E6; border: 1.5px solid #FFE58F; padding: 25px; border-radius: 12px; text-align: center; margin-bottom: 25px;">
            <h3 style="font-size: 17px; color: #d48806; margin: 0 0 10px 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-qrcode"></i> Thanh Toán Qua Mã QR Ngân Hàng (VietQR)
            </h3>
            <p style="font-size: 13.5px; color: #555; margin-bottom: 15px;">Vui lòng dùng app Ngân hàng (MB, VCB, Techcombank...) quét mã QR dưới đây để hoàn tất chuyển khoản:</p>
            
            <div style="display: inline-block; background: #fff; padding: 12px; border-radius: 10px; border: 1px solid #ddd; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <img src="https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=<?= intval($dh['tongtien']) ?>&addInfo=DH<?= $donhang_id ?>&accountName=CUA_HANG_SACH_BOOKSTORE" 
                     alt="Mã QR Chuyển khoản" style="width: 230px; height: 230px; display: block; margin: 0 auto;">
            </div>

            <div style="margin-top: 15px; font-size: 13.5px; color: #333; line-height: 1.6;">
                <div>Số tiền chuyển khoản: <b style="color: #C92127; font-size: 16px;"><?= number_format($dh['tongtien'], 0, ',', '.') ?> đ</b></div>
                <div>Nội dung chuyển khoản: <b style="color: #0F62FE;">DH<?= $donhang_id ?></b></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- KHUNG CHI TIẾT ĐƠN HÀNG -->
    <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; margin-bottom: 25px;">
        <h3 style="font-size: 16px; color: #333; border-bottom: 2px solid #C92127; padding-bottom: 8px; margin: 0 0 20px 0;">
            📋 Chi Tiết Đơn Hàng #DH-<?= $donhang_id ?>
        </h3>

        <!-- BẢNG THÔNG TIN GIAO HÀNG & THANH TOÁN -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 14px; color: #444; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <div>
                <h4 style="font-size: 14px; color: #222; margin: 0 0 10px 0; font-weight: bold;">THÔNG TIN NGƯỜI NHẬN:</h4>
                <p style="margin: 4px 0;"><b>Họ tên:</b> <?= htmlspecialchars($dh['ten_nguoinhan']) ?></p>
                <p style="margin: 4px 0;"><b>Số điện thoại:</b> <?= htmlspecialchars($dh['sdt_nguoinhan']) ?></p>
                <p style="margin: 4px 0;"><b>Địa chỉ:</b> <?= htmlspecialchars($dh['diachi_giaohang']) ?></p>
                <?php if (!empty($dh['ghichu'])): ?>
                    <p style="margin: 4px 0; color: #777;"><b>Ghi chú:</b> <?= htmlspecialchars($dh['ghichu']) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <h4 style="font-size: 14px; color: #222; margin: 0 0 10px 0; font-weight: bold;">TRẠNG THÁI & THANH TOÁN:</h4>
                <p style="margin: 4px 0;"><b>Ngày đặt:</b> <?= date('H:i - d/m/Y', strtotime($dh['ngaydat'])) ?></p>
                <p style="margin: 4px 0;"><b>Phương thức:</b> <?= htmlspecialchars($dh['phuongthuc_thanhtoan']) ?></p>
                <p style="margin: 4px 0;"><b>Trạng thái đơn:</b> 
                    <span style="background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;">
                        <?= $dh['trangthai'] == 'choduyet' ? 'Chờ duyệt' : htmlspecialchars($dh['trangthai']); ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- DANH SÁCH SÁCH ĐÃ ĐẶT -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="border-bottom: 2px solid #f0f0f0; text-align: left; font-size: 13px; color: #666;">
                    <th style="padding-bottom: 10px;">Sản phẩm</th>
                    <th style="padding-bottom: 10px;">Đơn giá</th>
                    <th style="padding-bottom: 10px; text-align: center;">Số lượng</th>
                    <th style="padding-bottom: 10px; text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_ct && $res_ct->num_rows > 0): ?>
                    <?php while ($item = $res_ct->fetch_assoc()): 
                        $tt = $item['gia'] * $item['soluong'];
                        $hinhanh = "ADMIN/" . $item['hinhanh'];
                    ?>
                        <tr style="border-bottom: 1px solid #f9f9f9; font-size: 13.5px;">
                            <td style="padding: 12px 0; display: flex; align-items: center; gap: 10px;">
                                <img src="<?= $hinhanh ?>" style="width: 45px; height: 55px; object-fit: contain; border: 1px solid #eee; border-radius: 4px;">
                                <span><?= htmlspecialchars($item['tensach']) ?></span>
                            </td>
                            <td><?= number_format($item['gia'], 0, ',', '.') ?> đ</td>
                            <td style="text-align: center;"><?= $item['soluong'] ?></td>
                            <td style="text-align: right; color: #C92127; font-weight: bold;"><?= number_format($tt, 0, ',', '.') ?> đ</td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TỔNG TÍNH TIỀN -->
        <div style="border-top: 1px solid #eee; padding-top: 15px; font-size: 14px; text-align: right; color: #444; line-height: 1.8;">
            <?php if (floatval($dh['sotien_giam']) > 0): ?>
                <div>Giảm giá Voucher (<?= htmlspecialchars($dh['ma_voucher'] ?? '') ?>): <b style="color: #e84393;">-<?= number_format($dh['sotien_giam'], 0, ',', '.') ?> đ</b></div>
            <?php endif; ?>
            <div style="font-size: 16px; margin-top: 5px;">
                TỔNG THANH TOÁN: <strong style="color: #C92127; font-size: 22px; margin-left: 10px;"><?= number_format($dh['tongtien'], 0, ',', '.') ?> đ</strong>
            </div>
        </div>
    </div>

    <!-- NÚT ĐIỀU HƯỚNG -->
    <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 40px;">
        <a href="donhang_cuatoi.php" class="btn-primary" style="background: #0F62FE; padding: 12px 25px; border-radius: 25px; font-weight: bold;">
            <i class="fas fa-box"></i> Xem Đơn Hàng Của Tôi
        </a>
        <a href="index.php" style="background: #333; color: white; padding: 12px 25px; border-radius: 25px; font-weight: bold; text-decoration: none;">
            <i class="fas fa-home"></i> Quay Lại Trang Chủ
        </a>
    </div>

</div>

<?php
$conn->close();
include 'footer.php';
?>
