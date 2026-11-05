<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../ADMIN/config/connect.php';

$ma = isset($_POST['ma']) ? strtoupper(trim($_POST['ma'])) : '';
$tong_tien = isset($_POST['tong_tien']) ? floatval($_POST['tong_tien']) : 0;

if (empty($ma)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vui lòng nhập mã giảm giá!'
    ]);
    exit();
}

$now = date('Y-m-d');
$sql = "SELECT * FROM voucher WHERE ma = '$ma' AND trangthai = 1 LIMIT 1";
$res = $conn->query($sql);

if (!$res || $res->num_rows == 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mã giảm giá không tồn tại hoặc đã bị khóa!'
    ]);
    exit();
}

$v = $res->fetch_assoc();

// Kiểm tra thời gian sử dụng
if (!empty($v['ngay_batdau']) && $now < $v['ngay_batdau']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mã giảm giá chưa đến đợt áp dụng!'
    ]);
    exit();
}
if (!empty($v['ngay_ketthuc']) && $now > $v['ngay_ketthuc']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mã giảm giá này đã hết hạn sử dụng!'
    ]);
    exit();
}

// Kiểm tra số lượng lượt dùng
if (intval($v['soluong']) <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mã giảm giá đã hết lượt sử dụng!'
    ]);
    exit();
}

// Kiểm tra điều kiện giá trị đơn hàng tối thiểu
$min_req = floatval($v['dieukien_toithieu']);
if ($tong_tien < $min_req) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($min_req, 0, ',', '.') . 'đ để dùng mã này!'
    ]);
    exit();
}

// Tính tiền giảm
$sotien_giam = 0;
if ($v['loai_giam'] == 'phantram') {
    $phantram = floatval($v['giatri']);
    $sotien_giam = ($tong_tien * $phantram) / 100;
} else {
    $sotien_giam = floatval($v['giatri']);
}

// Không để số tiền giảm vượt quá tổng tiền
if ($sotien_giam > $tong_tien) {
    $sotien_giam = $tong_tien;
}

$tong_sau_giam = max(0, $tong_tien - $sotien_giam);

echo json_encode([
    'status' => 'success',
    'voucher_id' => $v['voucher_id'],
    'ma' => $v['ma'],
    'sotien_giam' => $sotien_giam,
    'tong_sau_giam' => $tong_sau_giam,
    'message' => 'Áp dụng mã giảm giá "' . $v['ma'] . '" thành công!'
]);
?>
