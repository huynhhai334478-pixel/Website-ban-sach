<?php
session_start();
require_once __DIR__ . '/../ADMIN/config/connect.php';

$donhang_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($donhang_id <= 0) {
    echo '<p style="text-align: center; color: red;">Mã đơn hàng không hợp lệ!</p>';
    exit();
}

$sql_dh = "SELECT d.*, v.ma as ma_voucher 
           FROM don_hang d 
           LEFT JOIN voucher v ON d.voucher_id = v.voucher_id 
           WHERE d.donhang_id = $donhang_id LIMIT 1";
$res_dh = $conn->query($sql_dh);

if (!$res_dh || $res_dh->num_rows == 0) {
    echo '<p style="text-align: center; color: red;">Không tìm thấy thông tin đơn hàng!</p>';
    exit();
}

$dh = $res_dh->fetch_assoc();

// Trạng thái đơn hàng
$status_map = [
    'choduyet' => ['label' => 'Chờ duyệt', 'color' => '#856404', 'bg' => '#fff3cd'],
    'dangxuly' => ['label' => 'Đang xử lý', 'color' => '#004085', 'bg' => '#cce5ff'],
    'danggiao' => ['label' => 'Đang giao hàng', 'color' => '#0c5460', 'bg' => '#d1ecf1'],
    'hoanthanh' => ['label' => 'Hoàn thành', 'color' => '#155724', 'bg' => '#d4edda'],
    'dahuy' => ['label' => 'Đã hủy', 'color' => '#721c24', 'bg' => '#f8d7da']
];
$st_info = $status_map[$dh['trangthai']] ?? ['label' => $dh['trangthai'], 'color' => '#333', 'bg' => '#eee'];

// Lấy sản phẩm trong đơn
$sql_ct = "SELECT c.*, s.tensach, s.hinhanh, s.tacgia 
           FROM chi_tiet_don_hang c 
           JOIN sach s ON c.sach_id = s.sach_id 
           WHERE c.donhang_id = $donhang_id";
$res_ct = $conn->query($sql_ct);

// Kiểm tra xem yêu cầu Ajax được gọi từ trang ADMIN hay trang Khách hàng
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$is_admin_call = (strpos($referer, '/ADMIN/') !== false);
?>

<div style="font-family: Arial, sans-serif; font-size: 13.5px; color: #333; box-sizing: border-box; width: 100%; overflow-x: hidden;">
    
    <!-- Tiêu đề & Trạng thái -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px; margin-bottom: 15px;">
        <h4 style="margin: 0; font-size: 16px; color: #C92127; font-weight: bold;">
            ĐƠN HÀNG #DH-<?= $donhang_id ?>
        </h4>
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="<?= $is_admin_call ? 'in_donhang.php?id='.$donhang_id.'&from=admin' : 'ADMIN/in_donhang.php?id='.$donhang_id.'&from=user' ?>" target="_blank" style="background: #28a745; color: white; border: none; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fas fa-print"></i> In hóa đơn
            </a>
            <span style="background: <?= $st_info['bg'] ?>; color: <?= $st_info['color'] ?>; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 12px;">
                <?= $st_info['label'] ?>
            </span>
        </div>
    </div>

    <!-- Thông tin người nhận -->
    <div style="background: #f8f9fa; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px; border: 1px solid #eee; box-sizing: border-box;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div><b>Người nhận:</b> <?= htmlspecialchars($dh['ten_nguoinhan']) ?></div>
            <div><b>Số điện thoại:</b> <?= htmlspecialchars($dh['sdt_nguoinhan']) ?></div>
            <div style="grid-column: 1 / -1;"><b>Địa chỉ nhận hàng:</b> <?= htmlspecialchars($dh['diachi_giaohang']) ?></div>
            <div><b>Phương thức TT:</b> <?= htmlspecialchars($dh['phuongthuc_thanhtoan']) ?></div>
            <div><b>Ngày đặt:</b> <?= date('H:i - d/m/Y', strtotime($dh['ngaydat'])) ?></div>
            <?php if (!empty($dh['ghichu'])): ?>
                <div style="grid-column: 1 / -1; color: #666;"><b>Ghi chú:</b> <?= htmlspecialchars($dh['ghichu']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bảng danh sách sách -->
    <h5 style="margin: 0 0 10px 0; font-size: 14px; color: #333; font-weight: bold;">DANH SÁCH SÁCH ĐÃ ĐẶT:</h5>
    <div style="width: 100%; overflow-x: auto; margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse; box-sizing: border-box;">
            <thead>
                <tr style="background: #f1f1f1; text-align: left; font-size: 12.5px; color: #555;">
                    <th style="padding: 8px;">Sản phẩm</th>
                    <th style="padding: 8px;">Đơn giá</th>
                    <th style="padding: 8px; text-align: center;">SL</th>
                    <th style="padding: 8px; text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_ct && $res_ct->num_rows > 0): ?>
                    <?php while ($item = $res_ct->fetch_assoc()): 
                        $tt = $item['gia'] * $item['soluong'];
                        
                        // Xử lý đường dẫn ảnh chính xác dù gọi từ Admin hay Client
                        $raw_img = ltrim($item['hinhanh'], '/');
                        if (strpos($raw_img, 'ADMIN/') === 0) {
                            $raw_img = substr($raw_img, 6);
                        }
                        $hinhanh = $is_admin_call ? $raw_img : "ADMIN/" . $raw_img;
                    ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px 8px; display: flex; align-items: center; gap: 10px;">
                                <img src="<?= $hinhanh ?>" style="width: 40px; height: 50px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; background: #fff;">
                                <div>
                                    <div style="font-weight: 600; color: #333; line-height: 1.3;"><?= htmlspecialchars($item['tensach']) ?></div>
                                    <div style="font-size: 11.5px; color: #777;"><?= htmlspecialchars($item['tacgia']) ?></div>
                                </div>
                            </td>
                            <td style="padding: 10px 8px; white-space: nowrap;"><?= number_format($item['gia'], 0, ',', '.') ?>đ</td>
                            <td style="padding: 10px 8px; text-align: center; font-weight: bold;">x<?= $item['soluong'] ?></td>
                            <td style="padding: 10px 8px; text-align: right; color: #C92127; font-weight: bold; white-space: nowrap;"><?= number_format($tt, 0, ',', '.') ?>đ</td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tổng cộng tiền -->
    <div style="text-align: right; border-top: 1px solid #eee; padding-top: 10px; font-size: 14px;">
        <?php if (floatval($dh['sotien_giam']) > 0): ?>
            <div style="color: #e84393; font-size: 13px;">Giảm giá Voucher (<?= htmlspecialchars($dh['ma_voucher'] ?? '') ?>): <b>-<?= number_format($dh['sotien_giam'], 0, ',', '.') ?>đ</b></div>
        <?php endif; ?>
        <div style="font-size: 16px; font-weight: bold; color: #333; margin-top: 5px;">
            Tổng tiền thanh toán: <span style="color: #C92127; font-size: 20px;"><?= number_format($dh['tongtien'], 0, ',', '.') ?>đ</span>
        </div>
    </div>

</div>
