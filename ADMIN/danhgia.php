<?php
/**
 * Quản lý & Duyệt Đánh Giá Sản Phẩm (Reviews Management)
 * Bảng: danh_gia JOIN nguoi_dung JOIN sach
 */

require_once 'config/connect.php';

// Logic Duyệt/Ẩn/Xóa
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    if ($action == 'approve') {
        $conn->query("UPDATE danh_gia SET trangthai='Hiển thị' WHERE danhgia_id=$id");
        header("Location: danhgia.php?msg=approved"); exit;
    } elseif ($action == 'hide') {
        $conn->query("UPDATE danh_gia SET trangthai='Ẩn' WHERE danhgia_id=$id");
        header("Location: danhgia.php?msg=hidden"); exit;
    } elseif ($action == 'delete') {
        $conn->query("DELETE FROM danh_gia WHERE danhgia_id=$id");
        header("Location: danhgia.php?msg=deleted"); exit;
    }
}

$page_title = "Quản lý Đánh Giá - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin: 30px 0;">⭐ Quản lý Đánh Giá & Bình Luận</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php 
            if ($_GET['msg'] == 'approved') echo "Đã duyệt cho phép hiển thị đánh giá thành công!";
            elseif ($_GET['msg'] == 'hidden') echo "Đã ẩn đánh giá khỏi trang sản phẩm!";
            elseif ($_GET['msg'] == 'deleted') echo "Đã xóa bình luận đánh giá thành công!";
            ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã ĐG</th>
                    <th>Người Dùng</th>
                    <th>Sách</th>
                    <th>Đánh Giá</th>
                    <th>Nội Dung Bình Luận</th>
                    <th>Ngày Gửi</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT d.*, nd.taikhoan, s.tensach 
                        FROM danh_gia d 
                        LEFT JOIN nguoi_dung nd ON d.nguoidung_id = nd.nguoidung_id 
                        LEFT JOIN sach s ON d.sach_id = s.sach_id 
                        ORDER BY d.danhgia_id ASC";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $stars = str_repeat('⭐', intval($row['sosao']));
                        $is_visible = ($row['trangthai'] == 'Hiển thị' || $row['trangthai'] == 'duyet');
                        $status_badge = $is_visible ? 'badge-success' : 'badge-warning';
                        $status_str = $is_visible ? 'Hiển Thị' : 'Đang Ẩn';
                        
                        echo "<tr>
                            <td>#{$row['danhgia_id']}</td>
                            <td><strong>" . htmlspecialchars($row['taikhoan'] ?? 'Vô danh') . "</strong></td>
                            <td><strong>" . htmlspecialchars($row['tensach'] ?? 'N/A') . "</strong></td>
                            <td>{$stars} ({$row['sosao']}/5)</td>
                            <td>" . htmlspecialchars($row['binhluan']) . "</td>
                            <td><small>{$row['ngaytao']}</small></td>
                            <td><span class='badge {$status_badge}'>{$status_str}</span></td>
                            <td>";
                        
                        if (!$is_visible) {
                            echo "<a href='?action=approve&id={$row['danhgia_id']}' class='btn btn-success btn-small'>✅ Duyệt Hiện</a> ";
                        } else {
                            echo "<a href='?action=hide&id={$row['danhgia_id']}' class='btn btn-warning btn-small'>👁️‍🗨️ Ẩn Đi</a> ";
                        }
                        echo "<a href='?action=delete&id={$row['danhgia_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa hẳn đánh giá này không?', 'Xác nhận xóa Đánh Giá', 'Xác nhận xóa');\">🗑️ Xóa</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center;'>Chưa có bình luận đánh giá nào trong CSDL</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'footer.php'; ?>