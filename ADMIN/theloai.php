<?php
/**
 * Quản lý thể loại sách (Categories Management)
 * Bảng: the_loai
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// Xóa thể loại
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM sach_theloai WHERE theloai_id = $id");
    $sql = "DELETE FROM the_loai WHERE theloai_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa thể loại thành công!";
        $action = 'list';
    } else {
        $error = "Lỗi xóa thể loại: " . $conn->error;
        $action = 'list';
    }
}

// Thêm hoặc cập nhật thể loại
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tentheloai = $conn->real_escape_string($_POST['tentheloai']);
    $mota = $conn->real_escape_string($_POST['mota']);

    $is_duplicate = false;

    // 1. KIỂM TRA TRÙNG LẶP TÊN THỂ LOẠI
    if ($action == 'edit' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        // Khi sửa: Tìm xem có thể loại khác trùng tên không (ngoại trừ thể loại hiện tại)
        $check_sql = "SELECT tentheloai FROM the_loai WHERE tentheloai='$tentheloai' AND theloai_id != $id LIMIT 1";
    } else {
        // Khi thêm mới: Kiểm tra tên đã tồn tại chưa
        $check_sql = "SELECT tentheloai FROM the_loai WHERE tentheloai='$tentheloai' LIMIT 1";
    }

    $check_result = $conn->query($check_sql);

    if ($check_result && $check_result->num_rows > 0) {
        $is_duplicate = true;
        $error = "Lỗi: Tên thể loại '$tentheloai' đã tồn tại trong hệ thống!";
        // Giữ lại form (Sửa hoặc Thêm) thay vì đá về danh sách
        $action = isset($_POST['id']) ? 'edit' : 'add';
    }

    // 2. NẾU KHÔNG TRÙNG THÌ TIẾN HÀNH LƯU
    if (!$is_duplicate) {
        if ($action == 'edit' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $sql = "UPDATE the_loai SET tentheloai='$tentheloai', mota='$mota' WHERE theloai_id=$id";
            if ($conn->query($sql)) {
                $message = "Cập nhật thể loại thành công!";
                $action = 'list';
            } else {
                $error = "Lỗi cập nhật: " . $conn->error;
                $action = 'edit';
            }
        } else {
            $sql = "INSERT INTO the_loai (tentheloai, mota) VALUES ('$tentheloai', '$mota')";
            if ($conn->query($sql)) {
                $message = "Thêm thể loại thành công!";
                $action = 'list';
            } else {
                $error = "Lỗi thêm thể loại: " . $conn->error;
                $action = 'add';
            }
        }
    }
}

$category = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM the_loai WHERE theloai_id=$id");
    $category = $result->fetch_assoc();
}

// Cấu hình tiêu đề trang
$page_title = "Quản lý Thể Loại - Admin";

// Gọi Header đặt cùng cấp thư mục (Cách 2)
include_once 'header.php';
?>

    <div class="container">
        <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">📚 Quản lý Thể Loại Sách</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div style="margin-bottom: 20px;">
                <a href="?action=add" class="btn btn-primary">➕ Thêm Thể Loại Mới</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Thể Loại</th>
                            <th>Mô Tả</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM the_loai ORDER BY theloai_id ASC");
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['theloai_id']}</td>
                                    <td><strong>{$row['tentheloai']}</strong></td>
                                    <td>" . mb_substr($row['mota'] ?? '', 0, 50, 'UTF-8') . "...</td>
                                    <td>
                                         <a href='?action=edit&id={$row['theloai_id']}' class='btn btn-warning btn-small'>✏️ Sửa</a>
                                         <a href='?action=delete&id={$row['theloai_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa thể loại này không?', 'Xác nhận xóa Thể Loại', 'Xác nhận xóa');\">🗑️ Xóa</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>Không có dữ liệu</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="form-container">
                <h2><?php echo ($action == 'edit') ? '✏️ Cập Nhật Thể Loại' : '➕ Thêm Thể Loại Mới'; ?></h2>
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $category['theloai_id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="tentheloai">Tên Thể Loại:</label>
                        <input type="text" id="tentheloai" name="tentheloai" required value="<?php echo $category['tentheloai'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="mota">Mô Tả:</label>
                        <textarea id="mota" name="mota"><?php echo $category['mota'] ?? ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">💾 Lưu</button>
                    <a href="theloai.php" class="btn btn-info">↩️ Quay Lại</a>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php 
// Gọi Footer đặt cùng cấp thư mục (Cách 2)
include_once 'footer.php'; 
?>