<?php
/**
 * Quản lý Voucher (Mã giảm giá)
 * Bảng: voucher
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// Xóa voucher
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM voucher WHERE voucher_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa mã giảm giá thành công!";
        $action = 'list';
    } else {
        $error = "Lỗi xóa mã giảm giá: " . $conn->error;
        $action = 'list';
    }
}

// Khóa / Mở khóa voucher
if ($action == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE voucher SET trangthai = 1 - trangthai WHERE voucher_id = $id";
    if ($conn->query($sql)) {
        $message = "Đổi trạng thái voucher thành công!";
    } else {
        $error = "Lỗi đổi trạng thái: " . $conn->error;
    }
    $action = 'list';
}

// Thêm hoặc cập nhật voucher khi submit form (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $ma = strtoupper(trim($conn->real_escape_string($_POST['ma'])));
    $loai_giam = $conn->real_escape_string($_POST['loai_giam']);
    $giatri = floatval(str_replace(',', '.', $_POST['giatri'])); // Xử lý dấu phẩy thành dấu chấm để tránh lỗi số thực
    $dieukien_toithieu = floatval(str_replace(',', '.', $_POST['dieukien_toithieu']));
    $soluong = intval($_POST['soluong']);
    $ngay_batdau = $_POST['ngay_batdau'];
    $ngay_ketthuc = $_POST['ngay_ketthuc'];

    $is_duplicate = false;
    $has_error = false;

    // 1. KIỂM TRA ĐIỀU KIỆN GIÁ TRỊ GIẢM THEO PHẦN TRĂM (KHÔNG QUÁ 100%)
    if ($loai_giam == 'phantram' && ($giatri <= 0 || $giatri > 100)) {
        $has_error = true;
        $error = "Lỗi: Giá trị giảm theo phần trăm (%) phải lớn hơn 0 và không được lớn hơn 100!";
        $action = ($id > 0) ? 'edit' : 'add';
    } elseif (!empty($ngay_batdau) && !empty($ngay_ketthuc) && strtotime($ngay_ketthuc) < strtotime($ngay_batdau)) {
        $has_error = true;
        $error = "Lỗi: Ngày kết thúc không được nhỏ hơn ngày bắt đầu!";
        $action = ($id > 0) ? 'edit' : 'add';
    }

    // Nếu không lỗi về % thì tiếp tục kiểm tra trùng lặp mã code
    if (!$has_error) {
        if ($id > 0) {
            $check_sql = "SELECT ma FROM voucher WHERE ma='$ma' AND voucher_id != $id LIMIT 1";
        } else {
            $check_sql = "SELECT ma FROM voucher WHERE ma='$ma' LIMIT 1";
        }

        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $is_duplicate = true;
            $error = "Lỗi: Mã giảm giá '$ma' đã tồn tại trong hệ thống!";
            $action = ($id > 0) ? 'edit' : 'add';
        }

        // TIẾN HÀNH LƯU NẾU HỢP LỆ VÀ KHÔNG TRÙNG LẶP
        if (!$is_duplicate) {
            if ($id > 0) {
                $sql = "UPDATE voucher SET ma='$ma', loai_giam='$loai_giam', giatri=$giatri, dieukien_toithieu=$dieukien_toithieu, soluong=$soluong, ngay_batdau='$ngay_batdau', ngay_ketthuc='$ngay_ketthuc' WHERE voucher_id=$id";
                if ($conn->query($sql)) {
                    $message = "Cập nhật mã giảm giá thành công!";
                    $action = 'list';
                } else {
                    $error = "Lỗi cập nhật: " . $conn->error;
                    $action = 'edit';
                }
            } else {
                $sql = "INSERT INTO voucher (ma, loai_giam, giatri, dieukien_toithieu, soluong, ngay_batdau, ngay_ketthuc, trangthai) VALUES ('$ma', '$loai_giam', $giatri, $dieukien_toithieu, $soluong, '$ngay_batdau', '$ngay_ketthuc', 1)";
                if ($conn->query($sql)) {
                    $message = "Thêm mã giảm giá thành công!";
                    $action = 'list';
                } else {
                    $error = "Lỗi thêm mã: " . $conn->error;
                    $action = 'add';
                }
            }
        }
    }
}

// Lấy thông tin voucher khi bấm nút Sửa
$voucher_data = null;
$edit_id = 0;
if ($action == 'edit') {
    if (isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
    } elseif (isset($_POST['id'])) {
        $edit_id = intval($_POST['id']);
    }

    if ($edit_id > 0) {
        $result = $conn->query("SELECT * FROM voucher WHERE voucher_id = $edit_id");
        if ($result->num_rows > 0) {
            $voucher_data = $result->fetch_assoc();
        }
    }
}

// Cấu hình tiêu đề trang
$page_title = "Quản lý Voucher - Admin";

// Nhúng phần đầu trang dùng chung
include_once 'header.php';
?>

    <div class="container">
        <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">🎟️ Quản Lý Mã Giảm Giá (Voucher)</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div style="margin-bottom: 20px;">
                <a href="?action=add" class="btn btn-primary">➕ Thêm Voucher Mới</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã Voucher</th>
                            <th>Loại Giảm</th>
                            <th>Giá Trị</th>
                            <th>Đơn Tối Thiểu</th>
                            <th>Số Lượng</th>
                            <th>Thời Gian</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM voucher ORDER BY voucher_id ASC");
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $hien_thi_giatri = ($row['loai_giam'] == 'phantram') ? (int)$row['giatri'] . '%' : number_format($row['giatri'], 0, ',', '.') . ' VND';
                                $status_badge = ($row['trangthai'] == 1) ? 'badge-success' : 'badge-warning';
                                $status_text = ($row['trangthai'] == 1) ? 'Hoạt động' : 'Đã khóa';

                                echo "<tr>
                                    <td>{$row['voucher_id']}</td>
                                    <td><strong style='color: #C92127;'>{$row['ma']}</strong></td>
                                    <td>" . ($row['loai_giam'] == 'phantram' ? 'Phần trăm (%)' : 'Tiền mặt') . "</td>
                                    <td>{$hien_thi_giatri}</td>
                                    <td>" . number_format($row['dieukien_toithieu'], 0, ',', '.') . " VND</td>
                                    <td><span class='badge badge-info'>{$row['soluong']}</span></td>
                                    <td style='font-size: 13px;'>Từ: {$row['ngay_batdau']}<br>Đến: {$row['ngay_ketthuc']}</td>
                                    <td><span class='badge {$status_badge}'>{$status_text}</span></td>
                                    <td>";
                                
                                if ($row['trangthai'] == 1) {
                                    echo "<a href='?action=toggle&id={$row['voucher_id']}' class='btn btn-warning btn-small' style='margin-right: 5px;'>🔒 Khóa</a>";
                                } else {
                                    echo "<a href='?action=toggle&id={$row['voucher_id']}' class='btn btn-success btn-small' style='margin-right: 5px;'>🔓 Mở</a>";
                                }

                                echo "<a href='?action=edit&id={$row['voucher_id']}' class='btn btn-warning btn-small' style='margin-right: 5px;'>✏️ Sửa</a>
                                        <a href='?action=delete&id={$row['voucher_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa mã giảm giá này không?', 'Xác nhận xóa Voucher', 'Xác nhận xóa');\">🗑️ Xóa</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align:center;'>Không có dữ liệu mã giảm giá</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Form Thêm hoặc Sửa Voucher -->
            <div class="form-container">
                <h2><?php echo ($action == 'edit') ? '✏️ Cập Nhật Voucher' : '➕ Thêm Voucher Mới'; ?></h2>
                <form method="POST">
                    <?php if ($action == 'edit' && $edit_id > 0): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="ma">Mã Voucher:</label>
                        <input type="text" id="ma" name="ma" required placeholder="VD: SALE50" value="<?php echo $_POST['ma'] ?? ($voucher_data['ma'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="loai_giam">Loại Giảm Giá:</label>
                        <select id="loai_giam" name="loai_giam" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <?php $loai = $_POST['loai_giam'] ?? ($voucher_data['loai_giam'] ?? ''); ?>
                            <option value="phantram" <?php echo ($loai == 'phantram') ? 'selected' : ''; ?>>Giảm theo phần trăm (%)</option>
                            <option value="tienmat" <?php echo ($loai == 'tienmat') ? 'selected' : ''; ?>>Giảm trực tiếp bằng tiền mặt (VND)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="giatri">Giá Trị Giảm:</label>
                        <input type="text" id="giatri" name="giatri" required placeholder="Nhập số % hoặc số tiền" value="<?php echo isset($_POST['giatri']) ? $_POST['giatri'] : (isset($voucher_data['giatri']) ? (int)$voucher_data['giatri'] : ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="dieukien_toithieu">Đơn Hàng Tối Thiểu (VND):</label>
                        <input type="text" id="dieukien_toithieu" name="dieukien_toithieu" value="<?php echo isset($_POST['dieukien_toithieu']) ? $_POST['dieukien_toithieu'] : (isset($voucher_data['dieukien_toithieu']) ? (int)$voucher_data['dieukien_toithieu'] : '0'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="soluong">Số Lượng Mã:</label>
                        <input type="number" id="soluong" name="soluong" required value="<?php echo $_POST['soluong'] ?? ($voucher_data['soluong'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="ngay_batdau">Ngày Bắt Đầu:</label>
                        <input type="date" id="ngay_batdau" name="ngay_batdau" required value="<?php echo $_POST['ngay_batdau'] ?? ($voucher_data['ngay_batdau'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="ngay_ketthuc">Ngày Kết Thúc:</label>
                        <input type="date" id="ngay_ketthuc" name="ngay_ketthuc" required value="<?php echo $_POST['ngay_ketthuc'] ?? ($voucher_data['ngay_ketthuc'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-success">💾 Lưu</button>
                    <a href="voucher.php" class="btn btn-info">↩️ Quay Lại</a>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php 
// Nhúng phần cuối trang dùng chung
include_once 'footer.php'; 
?>