<?php
/**
 * In Hóa Đơn / Phiếu Giao Hàng (Print Invoice)
 * Đường dẫn: ADMIN/in_donhang.php?id=...
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/connect.php';

$from = $_GET['from'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

if ($from === 'user' || strpos($referer, 'donhang_cuatoi.php') !== false) {
    $back_url = "../donhang_cuatoi.php";
    $back_txt = "Quay lại đơn hàng của tôi";
} elseif ($from === 'admin' || strpos($referer, 'ADMIN/') !== false) {
    $back_url = "donhang.php";
    $back_txt = "Quay lại quản lý đơn hàng";
} else {
    $is_admin_user = (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] === 'admin');
    $back_url = $is_admin_user ? "donhang.php" : "../donhang_cuatoi.php";
    $back_txt = $is_admin_user ? "Quay lại quản lý đơn hàng" : "Quay lại đơn hàng của tôi";
}

$donhang_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($donhang_id <= 0) {
    die("Mã đơn hàng không hợp lệ!");
}

// Truy vấn thông tin đơn hàng
$sql_dh = "SELECT d.*, nd.taikhoan, v.ma as ma_voucher 
           FROM don_hang d 
           LEFT JOIN nguoi_dung nd ON d.nguoidung_id = nd.nguoidung_id 
           LEFT JOIN voucher v ON d.voucher_id = v.voucher_id 
           WHERE d.donhang_id = $donhang_id LIMIT 1";
$res_dh = $conn->query($sql_dh);

if (!$res_dh || $res_dh->num_rows == 0) {
    die("Không tìm thấy thông tin đơn hàng!");
}

$dh = $res_dh->fetch_assoc();

// Trạng thái đơn hàng
$status_map = [
    'choduyet' => 'Chờ duyệt',
    'dangxuly' => 'Đang xử lý',
    'danggiao' => 'Đang giao hàng',
    'hoanthanh' => 'Hoàn thành',
    'dahuy' => 'Đã hủy'
];
$trangthai_txt = $status_map[$dh['trangthai']] ?? $dh['trangthai'];

// Lấy sản phẩm trong đơn hàng
$sql_ct = "SELECT c.*, s.tensach, s.tacgia 
           FROM chi_tiet_don_hang c 
           JOIN sach s ON c.sach_id = s.sach_id 
           WHERE c.donhang_id = $donhang_id";
$res_ct = $conn->query($sql_ct);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa Đơn Đơn Hàng #DH-<?= $donhang_id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
            color: #333;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 14px;
            line-height: 24px;
            background: #fff;
            border-radius: 8px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #C92127;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #C92127;
        }
        .invoice-title {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            background: #fafafa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .item-table th {
            background: #C92127;
            color: #fff;
            text-align: left;
            padding: 10px;
            font-size: 13px;
        }
        .item-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .total-section {
            text-align: right;
            margin-top: 15px;
            font-size: 15px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            text-align: center;
        }
        .signature-block {
            width: 200px;
        }
        .btn-print {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 10px 22px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 20px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .btn-print:hover {
            background: #218838;
        }

        /* Khi bấm In (Print) -> Ẩn các nút điều khiển */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .invoice-box {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div style="max-width: 800px; margin: 0 auto; text-align: right;" class="no-print">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> IN HÓA ĐƠN NÀY</button>
        <a href="<?= $back_url ?>" style="color: #666; font-size: 13px; margin-left: 15px; text-decoration: none;">&larr; <?= $back_txt ?></a>
    </div>

    <div class="invoice-box">
        
        <!-- Đầu trang Hóa Đơn -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="company-name"><i class="fas fa-book-open"></i> BOOKSTORE</div>
                    <div style="font-size: 12.5px; color: #666;">Cửa hàng bán sách trực tuyến uy tín hàng đầu</div>
                    <div style="font-size: 12.5px; color: #666;">Hotline: 0123.456.789 | Email: hotro@bookstore.com</div>
                </td>
                <td class="invoice-title">
                    HÓA ĐƠN GIAO HÀNG<br>
                    <span style="font-size: 14px; font-weight: normal; color: #C92127;">#DH-<?= $donhang_id ?></span>
                </td>
            </tr>
        </table>

        <!-- Thông tin Đơn Hàng & Người Nhận -->
        <div class="info-grid">
            <div>
                <h4 style="margin: 0 0 8px 0; color: #C92127; border-bottom: 1px solid #ddd; padding-bottom: 4px;">THÔNG TIN NGƯỜI NHẬN</h4>
                <div><b>Họ và tên:</b> <?= htmlspecialchars($dh['ten_nguoinhan']) ?></div>
                <div><b>Số điện thoại:</b> <?= htmlspecialchars($dh['sdt_nguoinhan']) ?></div>
                <div><b>Địa chỉ giao:</b> <?= htmlspecialchars($dh['diachi_giaohang']) ?></div>
                <?php if (!empty($dh['ghichu'])): ?>
                    <div style="color: #666; margin-top: 4px;"><b>Ghi chú:</b> <?= htmlspecialchars($dh['ghichu']) ?></div>
                <?php endif; ?>
            </div>
            <div>
                <h4 style="margin: 0 0 8px 0; color: #C92127; border-bottom: 1px solid #ddd; padding-bottom: 4px;">THÔNG TIN ĐƠN HÀNG</h4>
                <div><b>Mã đơn hàng:</b> #DH-<?= $donhang_id ?></div>
                <div><b>Ngày đặt hàng:</b> <?= date('H:i - d/m/Y', strtotime($dh['ngaydat'])) ?></div>
                <div><b>Phương thức TT:</b> <?= htmlspecialchars($dh['phuongthuc_thanhtoan']) ?></div>
                <div><b>Trạng thái đơn:</b> <b style="color: #0F62FE;"><?= $trangthai_txt ?></b></div>
            </div>
        </div>

        <!-- Bảng danh sách sản phẩm -->
        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">STT</th>
                    <th>Tên Sách / Tác Giả</th>
                    <th style="width: 90px; text-align: right;">Đơn Giá</th>
                    <th style="width: 60px; text-align: center;">SL</th>
                    <th style="width: 100px; text-align: right;">Thành Tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                $tong_hang = 0;
                if ($res_ct && $res_ct->num_rows > 0):
                    while ($item = $res_ct->fetch_assoc()): 
                        $tt = $item['gia'] * $item['soluong'];
                        $tong_hang += $tt;
                ?>
                    <tr>
                        <td style="text-align: center;"><?= $stt++ ?></td>
                        <td>
                            <b><?= htmlspecialchars($item['tensach']) ?></b>
                            <div style="font-size: 12px; color: #777;"><?= htmlspecialchars($item['tacgia']) ?></div>
                        </td>
                        <td style="text-align: right;"><?= number_format($item['gia'], 0, ',', '.') ?> đ</td>
                        <td style="text-align: center; font-weight: bold;">x<?= $item['soluong'] ?></td>
                        <td style="text-align: right; font-weight: bold; color: #333;"><?= number_format($tt, 0, ',', '.') ?> đ</td>
                    </tr>
                <?php 
                    endwhile; 
                endif;
                ?>
            </tbody>
        </table>

        <!-- Tổng thanh toán -->
        <div class="total-section">
            <div>Tạm tính tiền hàng: <b><?= number_format($tong_hang, 0, ',', '.') ?> đ</b></div>
            <div>Phí vận chuyển: <b style="color: #28a745;">Miễn phí</b></div>
            <?php if (floatval($dh['sotien_giam']) > 0): ?>
                <div style="color: #e84393;">Giảm giá Voucher (<?= htmlspecialchars($dh['ma_voucher'] ?? '') ?>): <b>-<?= number_format($dh['sotien_giam'], 0, ',', '.') ?> đ</b></div>
            <?php endif; ?>
            <div style="font-size: 18px; color: #C92127; font-weight: bold; margin-top: 8px; border-top: 2px dashed #ddd; padding-top: 8px;">
                TỔNG THANH TOÁN: <?= number_format($dh['tongtien'], 0, ',', '.') ?> VNĐ
            </div>
        </div>

        <!-- Chữ ký -->
        <div class="signatures">
            <div class="signature-block">
                <b>Người Lập Hóa Đơn</b>
                <div style="font-size: 12px; color: #888; margin-top: 50px;">(Ký & ghi rõ họ tên)</div>
            </div>
            <div class="signature-block">
                <b>Khách Hàng Nhận Hàng</b>
                <div style="font-size: 12px; color: #888; margin-top: 50px;">(Ký & ghi rõ họ tên)</div>
            </div>
        </div>

    </div>

</body>
</html>
