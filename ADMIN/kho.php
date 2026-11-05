<?php
/**
 * Quản lý kho (Warehouse Management)
 * Bảng: kho JOIN nha_cung_cap
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// Xóa kho
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM kho WHERE kho_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa kho thành công!";
        $action = 'list';
    } else {
        $error = "Lỗi xóa kho: " . $conn->error;
        $action = 'list';
    }
}

// Thêm hoặc cập nhật kho
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tensach = $conn->real_escape_string($_POST['tensach']);
    $gianhap = intval($_POST['gianhap']);
    $soluong = intval($_POST['soluong']);
    $nhacungcap_id = !empty($_POST['nhacungcap_id']) ? intval($_POST['nhacungcap_id']) : "NULL";

    $is_duplicate = false;

    // 1. KIỂM TRA TRÙNG LẶP TÊN SÁCH TRONG KHO
    if ($action == 'edit' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $check_sql = "SELECT tensach FROM kho WHERE tensach='$tensach' AND kho_id != $id LIMIT 1";
    } else {
        $check_sql = "SELECT tensach FROM kho WHERE tensach='$tensach' LIMIT 1";
    }

    $check_result = $conn->query($check_sql);

    if ($check_result && $check_result->num_rows > 0) {
        $is_duplicate = true;
        $error = "Lỗi: Tên sách '$tensach' đã tồn tại trong kho!";
        $action = isset($_POST['id']) ? 'edit' : 'add';
    }

    // 2. TIẾN HÀNH LƯU NẾU KHÔNG TRÙNG LẶP
    if (!$is_duplicate) {
        if ($action == 'edit' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $sql = "UPDATE kho SET tensach='$tensach', gianhap=$gianhap, soluong=$soluong, nhacungcap_id=$nhacungcap_id WHERE kho_id=$id";
            if ($conn->query($sql)) {
                $message = "Cập nhật kho thành công!";
                $action = 'list';
            } else {
                $error = "Lỗi cập nhật: " . $conn->error;
                $action = 'edit';
            }
        } else {
            $sql = "INSERT INTO kho (tensach, gianhap, soluong, nhacungcap_id) VALUES ('$tensach', $gianhap, $soluong, $nhacungcap_id)";
            if ($conn->query($sql)) {
                $message = "Thêm kho thành công!";
                $action = 'list';
            } else {
                $error = "Lỗi thêm kho: " . $conn->error;
                $action = 'add';
            }
        }
    }
}

$warehouse = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM kho WHERE kho_id=$id");
    $warehouse = $result->fetch_assoc();
}

// Lấy danh sách Nhà cung cấp để hiển thị dropdown
$suppliers = $conn->query("SELECT * FROM nha_cung_cap ORDER BY ten_ncc ASC");

// SẮP XẾP KHO (Mặc định: Số lượng tồn ít nhất trước để dễ cập nhật hàng sắp hết)
$sort = $_GET['sort'] ?? 'stock_asc';
$order_by = "k.soluong ASC";

if ($sort === 'stock_desc') {
    $order_by = "k.soluong DESC";
} elseif ($sort === 'id_asc') {
    $order_by = "k.kho_id ASC";
} elseif ($sort === 'price_desc') {
    $order_by = "k.gianhap DESC";
}

$page_title = "Quản lý Kho - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">🏭 Quản lý Kho Hàng</h2>
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
            <a href="?action=add" class="btn btn-primary">➕ Thêm Kho Mới</a>

            <!-- Thanh Sắp Xếp Kho Hàng -->
            <form method="GET" style="display: flex; align-items: center; gap: 10px; padding: 0; margin: 0; background: none; box-shadow: none;">
                <label for="sort" style="margin: 0; font-weight: bold;">Sắp xếp theo:</label>
                <select name="sort" id="sort" onchange="this.form.submit()" style="padding: 8px 12px; margin: 0; width: 240px;">
                    <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>⚠️ Số lượng tồn ít nhất (Cần nhập)</option>
                    <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>📦 Số lượng tồn nhiều nhất</option>
                    <option value="id_asc" <?= $sort === 'id_asc' ? 'selected' : '' ?>>Mã kho (Tăng dần)</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>💵 Giá nhập cao nhất</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Sách Kho</th>
                        <th>Nhà Cung Cấp</th>
                        <th>Giá Nhập</th>
                        <th>Tồn Kho</th>
                        <th>Cảnh Báo Tồn</th>
                        <th>Ngày Tạo</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT k.*, ncc.ten_ncc 
                            FROM kho k 
                            LEFT JOIN nha_cung_cap ncc ON k.nhacungcap_id = ncc.nhacungcap_id 
                            ORDER BY $order_by";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $ten_ncc = !empty($row['ten_ncc']) ? htmlspecialchars($row['ten_ncc']) : '<span style="color:#999;">Chưa gán NCC</span>';
                            
                            $stock_badge = "<span class='badge badge-success'>{$row['soluong']}</span>";
                            $alert_badge = "<span class='badge badge-success'>Dồi dào</span>";
                            if ($row['soluong'] <= 10) {
                                $stock_badge = "<span class='badge badge-danger'>{$row['soluong']}</span>";
                                $alert_badge = "<span class='badge badge-danger'>⚠️ Sắp hết hàng</span>";
                            } elseif ($row['soluong'] <= 25) {
                                $stock_badge = "<span class='badge badge-warning'>{$row['soluong']}</span>";
                                $alert_badge = "<span class='badge badge-warning'>Cần chú ý</span>";
                            }

                            echo "<tr>
                                <td>#{$row['kho_id']}</td>
                                <td><strong>" . htmlspecialchars($row['tensach']) . "</strong></td>
                                <td><span class='badge badge-info'>{$ten_ncc}</span></td>
                                <td>💵 " . number_format($row['gianhap'], 0, ',', '.') . " VND</td>
                                <td>{$stock_badge}</td>
                                <td>{$alert_badge}</td>
                                <td>{$row['ngaytao']}</td>
                                <td>
                                     <a href='?action=edit&id={$row['kho_id']}' class='btn btn-warning btn-small'>✏️ Cập nhật số lượng</a>
                                     <a href='?action=delete&id={$row['kho_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa mặt hàng kho này không?', 'Xác nhận xóa Kho', 'Xác nhận xóa');\">🗑️ Xóa</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center;'>Không có dữ liệu kho</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="form-container">
            <h2><?php echo ($action == 'edit') ? '✏️ Cập Nhật Số Lượng Kho' : '➕ Thêm Kho Mới'; ?></h2>
            <form method="POST">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $warehouse['kho_id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="tensach">Tên Sách Kho:</label>
                    <input type="text" id="tensach" name="tensach" required value="<?php echo htmlspecialchars($warehouse['tensach'] ?? ''); ?>" placeholder="Tên cuốn sách nhập kho">
                </div>

                <div class="form-group">
                    <label for="nhacungcap_id">Nhà Cung Cấp Đối Tác:</label>
                    <select id="nhacungcap_id" name="nhacungcap_id">
                        <option value="">-- Chọn Nhà Cung Cấp (Tùy chọn) --</option>
                        <?php 
                        if ($suppliers && $suppliers->num_rows > 0) {
                            while ($sp = $suppliers->fetch_assoc()) {
                                $selected = (isset($warehouse['nhacungcap_id']) && $warehouse['nhacungcap_id'] == $sp['nhacungcap_id']) ? 'selected' : '';
                                echo "<option value='{$sp['nhacungcap_id']}' {$selected}>" . htmlspecialchars($sp['ten_ncc']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="gianhap">Giá Nhập (VND):</label>
                        <input type="number" id="gianhap" name="gianhap" step="1000" required value="<?php echo $warehouse['gianhap'] ?? ''; ?>" placeholder="80000">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="soluong">Số Lượng Nhập Kho (Cập nhật số lượng mới):</label>
                        <input type="number" id="soluong" name="soluong" required value="<?php echo $warehouse['soluong'] ?? ''; ?>" placeholder="50">
                    </div>
                </div>

                <button type="submit" class="btn btn-success">💾 Lưu Kho Hàng</button>
                <a href="kho.php" class="btn btn-info">↩️ Quay Lại</a>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>