<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vì file nằm trong folder ajax nên đường dẫn kết nối config phải lùi ra một cấp (../)
require_once '../ADMIN/config/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sach_id = isset($_POST['sach_id']) ? intval($_POST['sach_id']) : 0;
    $soluong = isset($_POST['soluong']) ? intval($_POST['soluong']) : 1;

    if ($sach_id > 0) {
        // Khởi tạo giỏ hàng trong session nếu chưa có
        if (!isset($_SESSION['giohang'])) {
            $_SESSION['giohang'] = [];
        }

        // Nếu sản phẩm đã có trong giỏ hàng thì cộng dồn số lượng
        if (isset($_SESSION['giohang'][$sach_id])) {
            $_SESSION['giohang'][$sach_id] += $soluong;
        } else {
            $_SESSION['giohang'][$sach_id] = $soluong;
        }

        // Đánh dấu giỏ hàng có sản phẩm mới chưa xem
        $_SESSION['giohang_unread'] = true;

        // Tính tổng số lượng sản phẩm trong giỏ
        $tong_soluong = array_sum($_SESSION['giohang']);

        echo json_encode([
            'status' => 'success',
            'total_items' => $tong_soluong
        ]);
        exit();
    }
}
echo json_encode(['status' => 'error']);