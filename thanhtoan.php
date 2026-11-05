<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Đánh dấu đã xem giỏ hàng
$_SESSION['giohang_unread'] = false;

// Bắt buộc khởi tạo giỏ hàng
if (!isset($_SESSION['giohang']) || empty($_SESSION['giohang'])) {
    header("Location: giohang.php");
    exit();
}

// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Kiểm tra người dùng đã đăng nhập chưa
$is_logged_in = isset($_SESSION['nguoidung_id']);
$user_info = null;

if ($is_logged_in) {
    $uid = intval($_SESSION['nguoidung_id']);
    $u_res = $conn->query("SELECT * FROM nguoi_dung WHERE nguoidung_id = $uid LIMIT 1");
    if ($u_res && $u_res->num_rows > 0) {
        $user_info = $u_res->fetch_assoc();
    }
}

// Lưu thông tin các sản phẩm được chọn để thanh toán từ form POST của giohang.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
    $_SESSION['checkout_items'] = array_map('intval', $_POST['selected_items']);
}

// Xác định danh sách các ID sản phẩm cần thanh toán
$selected_ids = isset($_SESSION['checkout_items']) ? $_SESSION['checkout_items'] : array_keys($_SESSION['giohang']);

// Nếu không có sản phẩm nào được chọn, chuyển hướng lại giỏ hàng
if (empty($selected_ids)) {
    header("Location: giohang.php");
    exit();
}

// Tính tổng tiền các sản phẩm được chọn
$tong_tien_hang = 0;
$cart_items = [];
foreach ($_SESSION['giohang'] as $sach_id => $soluong) {
    if (!in_array($sach_id, $selected_ids)) {
        continue; // Chỉ tính tiền và hiển thị sản phẩm được tích chọn
    }
    $q = $conn->query("SELECT * FROM sach WHERE sach_id = $sach_id LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $book = $q->fetch_assoc();
        $gia_goc = $book['gia'];
        $phantram_giam = intval($book['phantram_giamgia']);
        $gia_ban = $phantram_giam > 0 ? $gia_goc * (1 - $phantram_giam / 100) : $gia_goc;
        $thanhtien = $gia_ban * $soluong;
        $tong_tien_hang += $thanhtien;

        $cart_items[] = [
            'sach_id' => $sach_id,
            'tensach' => $book['tensach'],
            'hinhanh' => 'ADMIN/' . $book['hinhanh'],
            'gia_ban' => $gia_ban,
            'soluong' => $soluong,
            'thanhtien' => $thanhtien
        ];
    }
}

$error_msg = '';

// Xử lý khi bấm NÚT ĐẶT HÀNG (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_dathang'])) {
    $ten_nguoinhan = trim($conn->real_escape_string($_POST['ten_nguoinhan']));
    $sdt_nguoinhan = trim($conn->real_escape_string($_POST['sdt_nguoinhan']));
    $diachi_giaohang = trim($conn->real_escape_string($_POST['diachi_giaohang']));
    $phuongthuc_thanhtoan = trim($conn->real_escape_string($_POST['phuongthuc_thanhtoan'] ?? 'COD'));
    $ghichu = trim($conn->real_escape_string($_POST['ghichu'] ?? ''));
    $voucher_code = strtoupper(trim($conn->real_escape_string($_POST['voucher_code'] ?? '')));

    if (empty($ten_nguoinhan) || empty($sdt_nguoinhan) || empty($diachi_giaohang)) {
        $error_msg = "Vui lòng nhập đầy đủ Họ tên, Số điện thoại và Địa chỉ giao hàng!";
    } else {
        // Kiểm tra Voucher
        $voucher_id = "NULL";
        $sotien_giam = 0;

        if (!empty($voucher_code)) {
            $v_q = $conn->query("SELECT * FROM voucher WHERE ma = '$voucher_code' AND trangthai = 1 LIMIT 1");
            if ($v_q && $v_q->num_rows > 0) {
                $v = $v_q->fetch_assoc();
                $now = date('Y-m-d');
                $min_req = floatval($v['dieukien_toithieu']);

                if ((empty($v['ngay_batdau']) || $now >= $v['ngay_batdau']) &&
                    (empty($v['ngay_ketthuc']) || $now <= $v['ngay_ketthuc']) &&
                    intval($v['soluong']) > 0 &&
                    $tong_tien_hang >= $min_req) {

                    $voucher_id = intval($v['voucher_id']);
                    if ($v['loai_giam'] == 'phantram') {
                        $sotien_giam = ($tong_tien_hang * floatval($v['giatri'])) / 100;
                    } else {
                        $sotien_giam = floatval($v['giatri']);
                    }
                    $sotien_giam = min($sotien_giam, $tong_tien_hang);

                    // Trừ số lượng voucher
                    $conn->query("UPDATE voucher SET soluong = soluong - 1 WHERE voucher_id = $voucher_id");
                }
            }
        }

        $tong_cuoi = max(0, $tong_tien_hang - $sotien_giam);
        $nguoidung_id = $is_logged_in ? intval($_SESSION['nguoidung_id']) : "NULL";

        // Thêm đơn hàng vào CSDL
        $sql_dh = "INSERT INTO don_hang (nguoidung_id, ten_nguoinhan, sdt_nguoinhan, diachi_giaohang, phuongthuc_thanhtoan, ghichu, tongtien, voucher_id, sotien_giam, trangthai) 
                   VALUES ($nguoidung_id, '$ten_nguoinhan', '$sdt_nguoinhan', '$diachi_giaohang', '$phuongthuc_thanhtoan', '$ghichu', $tong_cuoi, $voucher_id, $sotien_giam, 'choduyet')";

        if ($conn->query($sql_dh)) {
            $donhang_id = $conn->insert_id;

            // Thêm chi tiết đơn hàng
            foreach ($cart_items as $item) {
                $sid = $item['sach_id'];
                $sl = $item['soluong'];
                $gia = $item['gia_ban'];
                $conn->query("INSERT INTO chi_tiet_don_hang (donhang_id, sach_id, soluong, gia) VALUES ($donhang_id, $sid, $sl, $gia)");
            }

            // Chỉ xóa các sản phẩm ĐÃ THANH TOÁN khỏi giỏ hàng
            foreach ($selected_ids as $sid) {
                unset($_SESSION['giohang'][$sid]);
            }
            unset($_SESSION['checkout_items']);

            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Đặt hàng thành công',
                'message' => 'Cảm ơn bạn! Đơn hàng #' . $donhang_id . ' đã được tạo thành công và đang được xử lý.'
            ];

            // Chuyển hướng sang trang đơn hàng của tôi
            echo "<script>window.location.href = 'donhang_cuatoi.php';</script>";
            exit();
        } else {
            $error_msg = "Có lỗi xảy ra khi tạo đơn hàng: " . $conn->error;
        }
    }
}
?>

<div style="max-width: 1200px; margin: 25px auto; padding: 0 15px;">
    
    <h2 style="font-size: 22px; color: #333; margin-bottom: 20px; border-bottom: 2px solid #C92127; padding-bottom: 10px;">
        💳 THANH TOÁN ĐƠN HÀNG
    </h2>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
            <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="formCheckout">
        <div style="display: flex; gap: 25px; align-items: flex-start;">
            
            <!-- CỘT TRÁI: THÔNG TIN GIAO HÀNG & PHƯƠNG THỨC THANH TOÁN -->
            <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Khung 1: Thông tin người nhận -->
                <div style="background: #fff; padding: 22px; border-radius: 10px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <h3 style="font-size: 16px; color: #333; margin: 0 0 18px 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: #C92127;"></i> 1. Thông Tin Nhận Hàng
                    </h3>

                    <?php if (!$is_logged_in): ?>
                        <div style="background: #FFF5F5; border: 1px dashed #FFDCDC; padding: 10px 14px; border-radius: 6px; font-size: 13px; color: #C92127; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Bạn chưa đăng nhập? <a href="dangnhap.php" style="color: #C92127; font-weight: bold; text-decoration: underline;">Đăng nhập ngay</a> để tích điểm và theo dõi đơn hàng dễ dàng!
                        </div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Họ và Tên Người Nhận <span style="color: red;">*</span></label>
                            <input type="text" name="ten_nguoinhan" required placeholder="Nhập họ và tên người nhận" 
                                   value="<?= htmlspecialchars($user_info['hoten'] ?? $user_info['taikhoan'] ?? '') ?>" 
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;"><i class="fas fa-phone-alt" style="color: #C92127; margin-right: 4px;"></i> Số Điện Thoại Người Nhận <span style="color: red;">*</span></label>
                            <input type="text" name="sdt_nguoinhan" required placeholder="Nhập số điện thoại người nhận" 
                                   value="<?= htmlspecialchars($user_info['sodienthoai_email'] ?? '') ?>" 
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Địa Chỉ Nhận Hàng Chi Tiết <span style="color: red;">*</span></label>
                        <input type="text" name="diachi_giaohang" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành" 
                               value="<?= htmlspecialchars($user_info['diachi'] ?? '') ?>" 
                               style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13.5px; font-weight: 600; color: #444; margin-bottom: 6px;">Ghi Chú Đơn Hàng (Nếu có)</label>
                        <textarea name="ghichu" rows="2" placeholder="Ví dụ: Giao hàng vào giờ hành chính, gọi trước khi giao..." 
                                  style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; resize: vertical;"></textarea>
                    </div>
                </div>

                <!-- Khung 2: Phương thức thanh toán -->
                <div style="background: #fff; padding: 22px; border-radius: 10px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <h3 style="font-size: 16px; color: #333; margin: 0 0 18px 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-credit-card" style="color: #C92127;"></i> 2. Phương Thức Thanh Toán
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- Lựa chọn 1: COD -->
                        <label style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border: 1px solid #e5e5e5; border-radius: 8px; cursor: pointer; transition: 0.2s;" class="payment-option">
                            <input type="radio" name="phuongthuc_thanhtoan" value="COD" checked style="width: 18px; height: 18px; accent-color: #C92127;">
                            <i class="fas fa-money-bill-wave" style="font-size: 22px; color: #28a745;"></i>
                            <div>
                                <strong style="font-size: 14px; color: #333;">Thanh toán khi nhận hàng (COD)</strong>
                                <p style="font-size: 12px; color: #777; margin: 2px 0 0 0;">Bạn sẽ thanh toán tiền mặt cho nhân viên giao hàng khi nhận sách.</p>
                            </div>
                        </label>

                        <!-- Lựa chọn 2: Chuyển khoản VietQR -->
                        <label style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border: 1px solid #e5e5e5; border-radius: 8px; cursor: pointer; transition: 0.2s;" class="payment-option">
                            <input type="radio" name="phuongthuc_thanhtoan" value="Chuyển khoản QR" style="width: 18px; height: 18px; accent-color: #C92127;">
                            <i class="fas fa-qrcode" style="font-size: 22px; color: #0F62FE;"></i>
                            <div>
                                <strong style="font-size: 14px; color: #333;">Chuyển khoản Ngân hàng (Mã VietQR Tự Động)</strong>
                                <p style="font-size: 12px; color: #777; margin: 2px 0 0 0;">Quét mã QR qua app ngân hàng (MB, Vietcombank, Techcombank, BIDV...).</p>
                            </div>
                        </label>

                        <!-- Lựa chọn 3: Ví Momo / VNPay -->
                        <label style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; border: 1px solid #e5e5e5; border-radius: 8px; cursor: pointer; transition: 0.2s;" class="payment-option">
                            <input type="radio" name="phuongthuc_thanhtoan" value="Ví Điện Tử (Momo/VNPay)" style="width: 18px; height: 18px; accent-color: #C92127;">
                            <i class="fas fa-wallet" style="font-size: 22px; color: #a29bfe;"></i>
                            <div>
                                <strong style="font-size: 14px; color: #333;">Ví Điện Tử (Momo / VNPay / ZaloPay)</strong>
                                <p style="font-size: 12px; color: #777; margin: 2px 0 0 0;">Thanh toán tiện lợi qua ví điện tử trên điện thoại.</p>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG & MÃ GIẢM GIÁ -->
            <div style="width: 380px; flex-shrink: 0; background: #fff; padding: 22px; border-radius: 10px; border: 1px solid #e5e5e5; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <h3 style="font-size: 16px; color: #333; margin: 0 0 15px 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-shopping-bag" style="color: #C92127;"></i> Đơn Hàng (<?= count($cart_items) ?> sản phẩm)
                </h3>

                <!-- Danh sách sản phẩm thu nhỏ -->
                <div style="max-height: 240px; overflow-y: auto; margin-bottom: 15px; padding-right: 5px;">
                    <?php foreach ($cart_items as $item): ?>
                        <div style="display: flex; gap: 12px; margin-bottom: 12px; border-bottom: 1px solid #fafafa; padding-bottom: 10px;">
                            <img src="<?= $item['hinhanh'] ?>" style="width: 45px; height: 55px; object-fit: contain; border: 1px solid #eee; border-radius: 4px;">
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600; color: #333; line-height: 1.3; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($item['tensach']) ?></div>
                                <div style="font-size: 12px; color: #777;">
                                    <?= number_format($item['gia_ban'], 0, ',', '.') ?>đ x <b><?= $item['soluong'] ?></b>
                                </div>
                            </div>
                            <div style="font-size: 13px; font-weight: bold; color: #C92127;">
                                <?= number_format($item['thanhtien'], 0, ',', '.') ?>đ
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Khung Nhập & Chọn Mã Giảm Giá -->
                <div style="background: #FFF0F6; border: 1.5px dashed #FFD6E7; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-size: 13px; font-weight: bold; color: #e84393; margin: 0; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-ticket-alt"></i> Mã Giảm Giá / Voucher
                        </label>
                        <button type="button" onclick="openVoucherModal()" style="background: #e84393; color: white; border: none; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            <i class="fas fa-list"></i> Xem mã hiện có
                        </button>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="inputVoucher" name="voucher_code" placeholder="Nhập hoặc chọn mã giảm giá..." style="flex: 1; padding: 9px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; text-transform: uppercase; outline: none;">
                        <button type="button" id="btnApplyVoucher" style="background: #C92127; color: white; border: none; padding: 9px 14px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer;">Áp dụng</button>
                    </div>
                    <div id="voucherAlert" style="margin-top: 8px; font-size: 12.5px; display: none;"></div>
                </div>

                <!-- Bảng tổng tiền -->
                <div style="border-top: 1px solid #eee; padding-top: 15px; font-size: 13.5px; color: #555;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Tạm tính tiền hàng:</span>
                        <span style="font-weight: bold; color: #333;"><?= number_format($tong_tien_hang, 0, ',', '.') ?> đ</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Phí vận chuyển:</span>
                        <span style="color: #28a745; font-weight: bold;">Miễn phí</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #e84393;">
                        <span>Giảm giá Voucher:</span>
                        <span id="displayDiscount" style="font-weight: bold;">-0 đ</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; border-top: 2px solid #f1f1f1; padding-top: 12px; margin-top: 10px; font-size: 16px;">
                        <strong style="color: #333;">TỔNG THANH TOÁN:</strong>
                        <strong id="displayTotalFinal" style="color: #C92127; font-size: 20px;"><?= number_format($tong_tien_hang, 0, ',', '.') ?> đ</strong>
                    </div>
                </div>

                <!-- Nút Submit Đặt Hàng -->
                <button type="submit" name="btn_dathang" style="width: 100%; background: #C92127; color: white; border: none; padding: 14px 0; border-radius: 6px; font-size: 16px; font-weight: bold; margin-top: 20px; cursor: pointer; transition: 0.25s; box-shadow: 0 4px 12px rgba(201, 33, 39, 0.25);">
                    <i class="fas fa-check"></i> XÁC NHẬN ĐẶT HÀNG
                </button>
            </div>

        </div>
    </form>
</div>

<!-- MODAL POPUP CHỌN MÃ GIẢM GIÁ (VOUCHER) -->
<div id="voucherModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 15px;">
    <div style="background: #fff; border-radius: 12px; width: 100%; max-width: 500px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
        
        <!-- Header Modal -->
        <div style="background: linear-gradient(135deg, #C92127, #e84393); padding: 15px 20px; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-ticket-alt"></i> DANH SÁCH MÃ GIẢM GIÁ HIỆN CÓ
            </h3>
            <button type="button" onclick="closeVoucherModal()" style="background: none; border: none; color: #fff; font-size: 22px; cursor: pointer;">&times;</button>
        </div>

        <!-- Body Modal -->
        <div style="padding: 20px; max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
            <?php
            $vouchers_q = $conn->query("SELECT * FROM voucher WHERE trangthai = 1 ORDER BY giatri DESC");
            if ($vouchers_q && $vouchers_q->num_rows > 0):
                while ($v = $vouchers_q->fetch_assoc()):
                    $ma_v = htmlspecialchars($v['ma']);
                    $loai = $v['loai_giam'];
                    $giatri_txt = ($loai == 'phantram') ? intval($v['giatri']) . '%' : number_format($v['giatri'], 0, ',', '.') . 'đ';
                    $toithieu_txt = number_format($v['dieukien_toithieu'], 0, ',', '.') . 'đ';
                    $soluong_v = intval($v['soluong']);
            ?>
                <div style="border: 1.5px solid #FFD6E7; background: #FFF5F8; border-radius: 10px; padding: 12px 15px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #C92127; color: #fff; font-size: 12px; font-weight: bold; padding: 2px 8px; border-radius: 4px; font-family: monospace;">
                                <?= $ma_v ?>
                            </span>
                            <span style="font-size: 13.5px; font-weight: bold; color: #e84393;">Giảm <?= $giatri_txt ?></span>
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 4px;">
                            Đơn tối thiểu: <b><?= $toithieu_txt ?></b> | Còn: <b><?= $soluong_v ?> lượt</b>
                        </div>
                    </div>
                    <button type="button" onclick="selectVoucher('<?= $ma_v ?>')" style="background: #e84393; color: white; border: none; padding: 7px 14px; border-radius: 20px; font-size: 12.5px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                        Áp dụng
                    </button>
                </div>
            <?php 
                endwhile;
            else: 
            ?>
                <p style="text-align: center; color: #777; padding: 20px;">Hiện chưa có mã giảm giá nào khả dụng.</p>
            <?php endif; ?>
        </div>

        <!-- Footer Modal -->
        <div style="background: #f8f9fa; padding: 12px 20px; text-align: right; border-top: 1px solid #eee;">
            <button type="button" onclick="closeVoucherModal()" style="background: #6c757d; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer;">Đóng</button>
        </div>

    </div>
</div>

<!-- JavaScript Áp dụng Voucher Ajax & Modal Popup -->
<script>
    function openVoucherModal() {
        document.getElementById('voucherModal').style.display = 'flex';
    }

    function closeVoucherModal() {
        document.getElementById('voucherModal').style.display = 'none';
    }

    function selectVoucher(code) {
        document.getElementById('inputVoucher').value = code;
        closeVoucherModal();
        document.getElementById('btnApplyVoucher').click();
    }

    const btnApplyVoucher = document.getElementById('btnApplyVoucher');
    const inputVoucher = document.getElementById('inputVoucher');
    const voucherAlert = document.getElementById('voucherAlert');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayTotalFinal = document.getElementById('displayTotalFinal');
    const tongTienGoc = <?= $tong_tien_hang ?>;

    if (btnApplyVoucher) {
        btnApplyVoucher.addEventListener('click', function() {
            let ma = inputVoucher.value.trim();
            if (ma === '') {
                voucherAlert.style.color = '#dc3545';
                voucherAlert.innerText = 'Vui lòng nhập mã giảm giá!';
                voucherAlert.style.display = 'block';
                return;
            }

            let formData = new FormData();
            formData.append('ma', ma);
            formData.append('tong_tien', tongTienGoc);

            fetch('ajax/kiemtra_voucher_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                voucherAlert.style.display = 'block';
                if (data.status === 'success') {
                    voucherAlert.style.color = '#28a745';
                    voucherAlert.innerHTML = '✔ ' + data.message;
                    displayDiscount.innerText = '-' + Number(data.sotien_giam).toLocaleString('vi-VN') + ' đ';
                    displayTotalFinal.innerText = Number(data.tong_sau_giam).toLocaleString('vi-VN') + ' đ';
                    if (typeof showToast === 'function') showToast('success', data.message);
                } else {
                    voucherAlert.style.color = '#dc3545';
                    voucherAlert.innerText = '✖ ' + data.message;
                    displayDiscount.innerText = '-0 đ';
                    displayTotalFinal.innerText = Number(tongTienGoc).toLocaleString('vi-VN') + ' đ';
                    if (typeof showToast === 'function') showToast('error', data.message);
                }
            })
            .catch(err => {
                console.error(err);
            });
        });
    }
</script>

<?php
$conn->close();
include 'footer.php';
?>
