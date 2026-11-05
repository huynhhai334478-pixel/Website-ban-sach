<?php
/**
 * Quản lý Phản Hồi / Liên Hệ Từ Khách Hàng
 * Bảng: lien_he
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// 1. XÓA LIÊN HỆ
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM lien_he WHERE lienhe_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa thông tin liên hệ thành công!";
    } else {
        $error = "Lỗi xóa liên hệ: " . $conn->error;
    }
    $action = 'list';
}

// 2. ĐỔI TRẠNG THÁI (ĐÃ XỬ LÝ / CHƯA XỬ LÝ)
if ($action == 'process' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE lien_he SET trangthai = IF(trangthai='daxuly', 'chuxuly', 'daxuly') WHERE lienhe_id = $id");
    header("Location: lienhe.php");
    exit();
}

$page_title = "Quản lý Liên Hệ & Góp Ý - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">✉️ Quản lý Liên Hệ & Phản Hồi Từ Khách Hàng</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ Tên</th>
                    <th>Email</th>
                    <th>Số Điện Thoại</th>
                    <th>Nội Dung Góp Ý</th>
                    <th>Ngày Gửi</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM lien_he ORDER BY lienhe_id ASC");
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $is_processed = ($row['trangthai'] === 'daxuly');
                        $status_badge = $is_processed 
                            ? "<span class='badge badge-success'>Đã Xử Lý</span>"
                            : "<span class='badge badge-warning'>Chưa Xử Lý</span>";
                        
                        $btn_process = $is_processed
                            ? "<a href='?action=process&id={$row['lienhe_id']}' class='btn btn-secondary btn-small'>↩️ Chưa Xử Lý</a>"
                            : "<a href='?action=process&id={$row['lienhe_id']}' class='btn btn-success btn-small'>✅ Đã Xử Lý</a>";

                        echo "<tr>
                            <td>{$row['lienhe_id']}</td>
                            <td><strong>" . htmlspecialchars($row['hoten']) . "</strong></td>
                            <td><a href='mailto:" . htmlspecialchars($row['email']) . "' style='color:#3498db;'>" . htmlspecialchars($row['email']) . "</a></td>
                            <td>" . htmlspecialchars($row['sodienthoai'] ?? 'N/A') . "</td>
                            <td style='max-width: 300px; word-wrap: break-word;'>" . nl2br(htmlspecialchars($row['noidung'])) . "</td>
                            <td>{$row['ngaygui']}</td>
                            <td>{$status_badge}</td>
                            <td>
                                 {$btn_process}
                                 <a href='?action=delete&id={$row['lienhe_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa tin nhắn liên hệ này không?', 'Xác nhận xóa Liên Hệ', 'Xác nhận xóa');\">🗑️ Xóa</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center;'>Chưa có tin nhắn liên hệ nào</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'footer.php'; ?>
