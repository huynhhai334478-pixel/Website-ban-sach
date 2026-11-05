<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../ADMIN/config/connect.php';

// Kiểm tra xem người dùng đã đăng nhập chưa cho mọi request Ajax
if (!isset($_SESSION['taikhoan'])) {
    echo json_encode(['status' => 'not_login', 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này!']);
    exit();
}

$username_s = $_SESSION['taikhoan'];
$u_query = $conn->query("SELECT nguoidung_id FROM nguoi_dung WHERE taikhoan = '$username_s' LIMIT 1");
if (!$u_query || $u_query->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin tài khoản!']);
    exit();
}
$current_user_id = intval($u_query->fetch_assoc()['nguoidung_id']);

// 1. Xử lý gửi đánh giá mới qua Ajax
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gui_danhgia_ajax'])) {
    $sach_id = isset($_POST['sach_id']) ? intval($_POST['sach_id']) : 0;
    $sosao = isset($_POST['sosao']) ? intval($_POST['sosao']) : 5;
    $binhluan = isset($_POST['binhluan']) ? trim($_POST['binhluan']) : '';

    if (empty($binhluan)) {
        echo json_encode(['status' => 'error', 'message' => 'Nội dung bình luận không được để trống!']);
        exit();
    }

    // 1. Kiểm tra số hóa đơn thành công (Hoàn thành) người dùng đã mua sản phẩm này
    $q_mua = $conn->query("SELECT COUNT(DISTINCT d.donhang_id) as total_mua 
                           FROM don_hang d 
                           JOIN chi_tiet_don_hang c ON d.donhang_id = c.donhang_id 
                           WHERE d.nguoidung_id = $current_user_id 
                             AND c.sach_id = $sach_id 
                             AND d.trangthai = 'hoanthanh'");
    $so_lan_mua = 0;
    if ($q_mua && $row_m = $q_mua->fetch_assoc()) {
        $so_lan_mua = intval($row_m['total_mua']);
    }

    if ($so_lan_mua == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Chỉ khách hàng đã mua và hoàn thành đơn hàng mới được gửi đánh giá!']);
        exit();
    }

    // 2. Kiểm tra số lần đã bình luận cho cuốn sách này
    $q_dg = $conn->query("SELECT COUNT(*) as total_dg FROM danh_gia WHERE sach_id = $sach_id AND nguoidung_id = $current_user_id");
    $so_lan_danhgia = 0;
    if ($q_dg && $row_d = $q_dg->fetch_assoc()) {
        $so_lan_danhgia = intval($row_d['total_dg']);
    }

    if ($so_lan_danhgia >= $so_lan_mua) {
        echo json_encode(['status' => 'error', 'message' => 'Bạn đã bình luận cho tất cả hóa đơn đã mua (' . $so_lan_danhgia . '/' . $so_lan_mua . ' hóa đơn). Vui lòng mua thêm sản phẩm trong đơn hàng mới để tiếp tục viết nhận xét!']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO danh_gia (nguoidung_id, sach_id, sosao, binhluan, trangthai, ngaytao, luotthich, baocao) VALUES (?, ?, ?, ?, 'Hiển thị', NOW(), 0, 0)");
    $stmt->bind_param("iiis", $current_user_id, $sach_id, $sosao, $binhluan);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra khi lưu đánh giá.']);
    }
    $stmt->close();
    exit();
}

// 2. Xử lý Thích / Báo cáo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $dg_id = intval($_POST['danhgia_id']);

    // Kiểm tra xem bình luận này có phải của chính người đang đăng nhập không
    $owner_check = $conn->query("SELECT nguoidung_id FROM danh_gia WHERE danhgia_id = $dg_id LIMIT 1");
    if ($owner_check && $owner_check->num_rows > 0) {
        $owner_row = $owner_check->fetch_assoc();
        if (intval($owner_row['nguoidung_id']) === $current_user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không thể thích hoặc báo cáo bình luận của chính mình!']);
            exit();
        }
    }

    if (!isset($_SESSION['liked_reviews'])) {
        $_SESSION['liked_reviews'] = [];
    }

    if ($_POST['action_type'] === 'like') {
        if (in_array($dg_id, $_SESSION['liked_reviews'])) {
            $conn->query("UPDATE danh_gia SET luotthich = GREATEST(luotthich - 1, 0) WHERE danhgia_id = $dg_id");
            $_SESSION['liked_reviews'] = array_diff($_SESSION['liked_reviews'], [$dg_id]);
            $is_liked = false;
        } else {
            $conn->query("UPDATE danh_gia SET luotthich = luotthich + 1 WHERE danhgia_id = $dg_id");
            $_SESSION['liked_reviews'][] = $dg_id;
            $is_liked = true;
        }

        $res = $conn->query("SELECT luotthich FROM danh_gia WHERE danhgia_id = $dg_id");
        $row = $res->fetch_assoc();

        echo json_encode([
            'status' => 'success', 
            'count' => $row['luotthich'],
            'is_liked' => $is_liked
        ]);
        exit();
    } 
    elseif ($_POST['action_type'] === 'report') {
        $conn->query("UPDATE danh_gia SET baocao = baocao + 1 WHERE danhgia_id = $dg_id");
        echo json_encode(['status' => 'success']);
        exit();
    }
}
echo json_encode(['status' => 'error']);