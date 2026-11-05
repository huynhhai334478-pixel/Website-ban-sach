<?php
/**
 * Quản lý Nhà Cung Cấp (Supplier Management)
 * Bảng: nha_cung_cap
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// 1. XÓA NHÀ CUNG CẤP
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Chuyển nhacungcap_id về NULL trong kho trước khi xóa
    $conn->query("UPDATE kho SET nhacungcap_id = NULL WHERE nhacungcap_id = $id");
    
    $sql = "DELETE FROM nha_cung_cap WHERE nhacungcap_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa nhà cung cấp thành công!";
        $action = 'list';
    } else {
        $error = "Lỗi xóa nhà cung cấp: " . $conn->error;
        $action = 'list';
    }
}

// 2. THÊM HOẶC CẬP NHẬT NHÀ CUNG CẤP
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten_ncc = $conn->real_escape_string($_POST['ten_ncc'] ?? '');
    $sdt = $conn->real_escape_string($_POST['sdt'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $diachi = $conn->real_escape_string($_POST['diachi'] ?? '');
    $ghichu = $conn->real_escape_string($_POST['ghichu'] ?? '');

    if (empty($ten_ncc)) {
        $error = "Vui lòng nhập tên nhà cung cấp!";
        $action = isset($_POST['id']) ? 'edit' : 'add';
    } else {
        $is_duplicate = false;
        if (isset($_POST['id']) && intval($_POST['id']) > 0) {
            $id = intval($_POST['id']);
            $check_sql = "SELECT ten_ncc FROM nha_cung_cap WHERE ten_ncc='$ten_ncc' AND nhacungcap_id != $id LIMIT 1";
        } else {
            $check_sql = "SELECT ten_ncc FROM nha_cung_cap WHERE ten_ncc='$ten_ncc' LIMIT 1";
        }

        $check_result = $conn->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            $is_duplicate = true;
            $error = "Lỗi: Tên nhà cung cấp '$ten_ncc' đã tồn tại!";
            $action = isset($_POST['id']) ? 'edit' : 'add';
        }

        if (!$is_duplicate) {
            if (isset($_POST['id']) && intval($_POST['id']) > 0) { // CẬP NHẬT
                $id = intval($_POST['id']);
                $sql = "UPDATE nha_cung_cap SET ten_ncc='$ten_ncc', sdt='$sdt', email='$email', diachi='$diachi', ghichu='$ghichu' WHERE nhacungcap_id=$id";
                if ($conn->query($sql)) {
                    $message = "Cập nhật nhà cung cấp thành công!";
                    $action = 'list';
                } else {
                    $error = "Lỗi cập nhật: " . $conn->error;
                    $action = 'edit';
                }
            } else { // THÊM MỚI
                $sql = "INSERT INTO nha_cung_cap (ten_ncc, sdt, email, diachi, ghichu) VALUES ('$ten_ncc', '$sdt', '$email', '$diachi', '$ghichu')";
                if ($conn->query($sql)) {
                    $message = "Thêm nhà cung cấp thành công!";
                    $action = 'list';
                } else {
                    $error = "Lỗi thêm nhà cung cấp: " . $conn->error;
                    $action = 'add';
                }
            }
        }
    }
}

// Lấy thông tin sửa
$ncc = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM nha_cung_cap WHERE nhacungcap_id = $id");
    if ($result && $result->num_rows > 0) {
        $ncc = $result->fetch_assoc();
    }
}

$page_title = "Quản lý Nhà Cung Cấp - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">🏢 Quản lý Nhà Cung Cấp (Đối Tác)</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div style="margin-bottom: 20px;">
            <a href="?action=add" class="btn btn-primary">➕ Thêm Nhà Cung Cấp Mới</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Nhà Cung Cấp</th>
                        <th>Số Điện Thoại</th>
                        <th>Email</th>
                        <th>Địa Chỉ</th>
                        <th>Ghi Chú</th>
                        <th>Ngày Tạo</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM nha_cung_cap ORDER BY nhacungcap_id ASC");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>#{$row['nhacungcap_id']}</td>
                                <td><strong>" . htmlspecialchars($row['ten_ncc']) . "</strong></td>
                                <td>" . htmlspecialchars($row['sdt'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['diachi'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['ghichu'] ?? '') . "</td>
                                <td>{$row['ngaytao']}</td>
                                <td>
                                    <a href='?action=edit&id={$row['nhacungcap_id']}' class='btn btn-warning btn-small'>✏️ Sửa</a>
                                    <a href='?action=delete&id={$row['nhacungcap_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa nhà cung cấp này không?', 'Xác nhận xóa Nhà Cung Cấp', 'Xác nhận xóa');\">🗑️ Xóa</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center;'>Chưa có nhà cung cấp nào trong CSDL</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="form-container">
            <h2><?php echo ($action == 'edit') ? '✏️ Cập Nhật Nhà Cung Cấp' : '➕ Thêm Nhà Cung Cấp Mới'; ?></h2>
            <form method="POST">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $ncc['nhacungcap_id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="ten_ncc">Tên Nhà Cung Cấp / Đối Tác:</label>
                    <input type="text" id="ten_ncc" name="ten_ncc" required value="<?php echo htmlspecialchars($ncc['ten_ncc'] ?? ''); ?>" placeholder="VD: NXB Trẻ, Công ty Tiền Phong...">
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="sdt">Số Điện Thoại Liên Hệ:</label>
                        <input type="text" id="sdt" name="sdt" value="<?php echo htmlspecialchars($ncc['sdt'] ?? ''); ?>" placeholder="028 1234 5678">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="email">Email Đối Tác:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($ncc['email'] ?? ''); ?>" placeholder="contact@nxb.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="diachi">Địa Chỉ Trụ Sở / Kho Hàng NCC:</label>
                    <input type="text" id="diachi" name="diachi" value="<?php echo htmlspecialchars($ncc['diachi'] ?? ''); ?>" placeholder="Địa chỉ chi tiết">
                </div>

                <div class="form-group">
                    <label for="ghichu">Ghi Chú Chi Tiết:</label>
                    <textarea id="ghichu" name="ghichu" style="height: 100px;"><?php echo htmlspecialchars($ncc['ghichu'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-success">💾 Lưu Nhà Cung Cấp</button>
                <a href="nhacungcap.php" class="btn btn-info">↩️ Quay Lại</a>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
