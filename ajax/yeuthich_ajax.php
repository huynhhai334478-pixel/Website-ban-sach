<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../ADMIN/config/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['nguoidung_id'])) {
    echo json_encode([
        'status' => 'login_required',
        'message' => 'Vui lòng đăng nhập để lưu sản phẩm yêu thích!'
    ]);
    exit();
}

$nguoidung_id = intval($_SESSION['nguoidung_id']);
$sach_id = isset($_POST['sach_id']) ? intval($_POST['sach_id']) : 0;

if ($sach_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Dữ liệu sách không hợp lệ!'
    ]);
    exit();
}

// Kiểm tra trạng thái yêu thích trong CSDL
$check_sql = "SELECT yeuthich_id FROM yeu_thich WHERE nguoidung_id = $nguoidung_id AND sach_id = $sach_id LIMIT 1";
$check_res = $conn->query($check_sql);

if ($check_res && $check_res->num_rows > 0) {
    // Đã thích -> Bỏ thích
    $del_sql = "DELETE FROM yeu_thich WHERE nguoidung_id = $nguoidung_id AND sach_id = $sach_id";
    $conn->query($del_sql);
    $action = 'removed';
    $message = 'Đã bỏ sản phẩm khỏi danh sách yêu thích!';
} else {
    // Chưa thích -> Thêm yêu thích
    $add_sql = "INSERT INTO yeu_thich (nguoidung_id, sach_id) VALUES ($nguoidung_id, $sach_id)";
    $conn->query($add_sql);
    $action = 'added';
    $message = 'Đã thêm sản phẩm vào danh sách yêu thích thành công!';
}

// Tính lại tổng số lượt yêu thích của cuốn sách này
$count_res = $conn->query("SELECT COUNT(*) as total FROM yeu_thich WHERE sach_id = $sach_id");
$total_fav = 0;
if ($count_res && $c_row = $count_res->fetch_assoc()) {
    $total_fav = intval($c_row['total']);
}

// Tính tổng số sản phẩm yêu thích của người dùng hiện tại
$user_count_res = $conn->query("SELECT COUNT(*) as total FROM yeu_thich WHERE nguoidung_id = $nguoidung_id");
$user_total_fav = 0;
if ($user_count_res && $u_row = $user_count_res->fetch_assoc()) {
    $user_total_fav = intval($u_row['total']);
}

echo json_encode([
    'status' => 'success',
    'action' => $action,
    'total' => $total_fav,
    'user_total' => $user_total_fav,
    'message' => $message
]);
?>
