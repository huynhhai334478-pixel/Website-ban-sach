<?php
/**
 * Quản lý Sách (Books Management)
 * Bảng: sach, sach_theloai
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = ''; $error = '';
$uploadDir = __DIR__ . '/image/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// 1. XỬ LÝ XÓA
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM sach_theloai WHERE sach_id = $id");
    $conn->query("DELETE FROM yeu_thich WHERE sach_id = $id");
    $conn->query("DELETE FROM sach WHERE sach_id = $id");
    header("Location: sach.php?msg=deleted"); exit;
}

// 2. XỬ LÝ LƯU (Thêm/Sửa)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $theloai_ids = $_POST['theloai_id'] ?? [];
    $tensach = $conn->real_escape_string($_POST['tensach']);
    $tacgia = $conn->real_escape_string($_POST['tacgia']);
    $nhaxuatban = $conn->real_escape_string($_POST['nhaxuatban']);
    $namxuatban = (isset($_POST['namxuatban']) && strlen(trim($_POST['namxuatban'])) > 0) ? intval($_POST['namxuatban']) : "NULL";
    $so_trang = (isset($_POST['so_trang']) && strlen(trim($_POST['so_trang'])) > 0) ? intval($_POST['so_trang']) : "NULL";
    $gia = floatval($_POST['gia']);
    $phantram_giamgia = intval($_POST['phantram_giamgia']);
    $daban = intval($_POST['daban']);
    $luotxem = intval($_POST['luotxem'] ?? 0);
    $kho_id = intval($_POST['kho_id']);
    $mota = $conn->real_escape_string($_POST['mota']);
    $hinhanh = $_POST['existing_hinhanh'] ?? '';

    $errors = [];
    $is_add = !isset($_POST['id']);
    $co_up_anh = isset($_FILES['hinhanh_file']) && $_FILES['hinhanh_file']['error'] === UPLOAD_ERR_OK;

    $check_name_sql = "SELECT sach_id FROM sach WHERE tensach='$tensach'";
    if (!$is_add) $check_name_sql .= " AND sach_id != " . intval($_POST['id']);
    if ($conn->query($check_name_sql)->num_rows > 0) $errors[] = "Tên sách này đã tồn tại.";

    if (trim($_POST['tensach'] ?? '') === '') $errors[] = "Chưa nhập tên sách.";
    if (empty($theloai_ids)) $errors[] = "Chưa chọn thể loại.";
    if (trim($_POST['gia'] ?? '') === '') {
        $errors[] = "Chưa nhập giá bán.";
    } elseif (floatval($_POST['gia']) < 0) {
        $errors[] = "Giá bán không được là số âm.";
    }
    if (trim($_POST['namxuatban'] ?? '') === '') $errors[] = "Chưa nhập năm xuất bản.";
    if (trim($_POST['so_trang'] ?? '') === '') $errors[] = "Chưa nhập số trang.";
    if (trim($_POST['tacgia'] ?? '') === '') $errors[] = "Chưa nhập tác giả.";
    if (trim($_POST['nhaxuatban'] ?? '') === '') $errors[] = "Chưa nhập nhà xuất bản.";
    if ($phantram_giamgia < 0 || $phantram_giamgia > 100) $errors[] = "Giảm giá phải từ 0-100%.";
    if (!$co_up_anh && empty($hinhanh)) $errors[] = "Chưa có hình ảnh bìa sách. Vui lòng tải ảnh lên.";

    if (!empty($errors)) {
        if ($co_up_anh && count($errors) > 0) {
            $error = "<strong>Lỗi: Bạn đã thêm hình ảnh nhưng chưa nhập đủ các thông tin bắt buộc:</strong><br> - " . implode("<br> - ", $errors);
        } else {
            $error = "<strong>Vui lòng kiểm tra lại các thông tin sau:</strong><br> - " . implode("<br> - ", $errors);
        }
        $action = $is_add ? 'add' : 'edit';
    } else {
        if (isset($_FILES['hinhanh_file']) && $_FILES['hinhanh_file']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($_FILES['hinhanh_file']['name']);
            move_uploaded_file($_FILES['hinhanh_file']['tmp_name'], $uploadDir . $fileName);
            $hinhanh = 'image/' . $fileName;
        }

        if (empty($theloai_ids)) {
            $error = "Vui lòng chọn ít nhất một thể loại!";
        } else {
            if (isset($_POST['id'])) { // SỬA
                $id = intval($_POST['id']);
                $conn->query("UPDATE sach SET tensach='$tensach', tacgia='$tacgia', nhaxuatban='$nhaxuatban', namxuatban=$namxuatban, so_trang=$so_trang, gia=$gia, phantram_giamgia=$phantram_giamgia, daban=$daban, luotxem=$luotxem, kho_id=$kho_id, mota='$mota', hinhanh='$hinhanh' WHERE sach_id=$id");
                $conn->query("DELETE FROM sach_theloai WHERE sach_id=$id");
            } else { // THÊM
                $conn->query("INSERT INTO sach (tensach, tacgia, nhaxuatban, namxuatban, so_trang, gia, phantram_giamgia, daban, luotxem, kho_id, mota, hinhanh) VALUES ('$tensach', '$tacgia', '$nhaxuatban', $namxuatban, $so_trang, $gia, $phantram_giamgia, $daban, $luotxem, $kho_id, '$mota', '$hinhanh')");
                $id = $conn->insert_id;
            }
            foreach ($theloai_ids as $tl_id) {
                $conn->query("INSERT INTO sach_theloai (sach_id, theloai_id) VALUES ($id, " . intval($tl_id) . ")");
            }
            header("Location: sach.php?msg=success"); exit;
        }
    }
}

// 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$book = null; $selected_categories = [];
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $book = $conn->query("SELECT * FROM sach WHERE sach_id=$id")->fetch_assoc();
    $res_tl = $conn->query("SELECT theloai_id FROM sach_theloai WHERE sach_id=$id");
    while ($r = $res_tl->fetch_assoc()) $selected_categories[] = $r['theloai_id'];
}
$categories = $conn->query("SELECT * FROM the_loai");
$warehouses = $conn->query("SELECT * FROM kho");

// SẮP XẾP SÁCH (Theo Lượt Xem Cao Nhất / Lượt Mua Nhiều Nhất / ID ASC)
$sort = $_GET['sort'] ?? 'id_asc';
$order_by = "s.sach_id ASC";

if ($sort === 'views_desc') {
    $order_by = "s.luotxem DESC";
} elseif ($sort === 'sold_desc') {
    $order_by = "s.daban DESC";
} elseif ($sort === 'price_desc') {
    $order_by = "s.gia DESC";
} elseif ($sort === 'price_asc') {
    $order_by = "s.gia ASC";
}

$page_title = "Quản lý Sách - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin-top: 30px; margin-bottom: 30px;">📖 Quản lý Sách</h2>
    
    <?php if (isset($_GET['msg']) && empty($error) && $_SERVER['REQUEST_METHOD'] !== 'POST') echo "<div class='alert alert-success'>Thao tác xử lý dữ liệu sách thành công!</div>"; ?>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <?php if ($action == 'list'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
            <a href="?action=add" class="btn btn-primary">➕ Thêm Sách Mới</a>

            <!-- Thanh Sắp Xếp Sách -->
            <form method="GET" style="display: flex; align-items: center; gap: 10px; padding: 0; margin: 0; background: none; box-shadow: none;">
                <label for="sort" style="margin: 0; font-weight: bold;">Sắp xếp theo:</label>
                <select name="sort" id="sort" onchange="this.form.submit()" style="padding: 8px 12px; margin: 0; width: 230px;">
                    <option value="id_asc" <?= $sort === 'id_asc' ? 'selected' : '' ?>>Mã sách (Tăng dần)</option>
                    <option value="views_desc" <?= $sort === 'views_desc' ? 'selected' : '' ?>>🔥 Lượt xem nhiều nhất</option>
                    <option value="sold_desc" <?= $sort === 'sold_desc' ? 'selected' : '' ?>>🛒 Lượt mua (bán) nhiều nhất</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>💵 Giá cao nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>💵 Giá thấp nhất</option>
                </select>
            </form>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hình Ảnh</th>
                        <th>Tên Sách</th>
                        <th>Tác Giả / NXB</th>
                        <th>Năm XB / Số Trang</th>
                        <th>Giá Bán & Ưu Đãi</th>
                        <th>Đã Bán / Lượt Xem</th>
                        <th>Thể Loại</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $res = $conn->query("SELECT s.*, GROUP_CONCAT(t.tentheloai SEPARATOR ', ') as tl 
                                         FROM sach s 
                                         LEFT JOIN sach_theloai st ON s.sach_id = st.sach_id 
                                         LEFT JOIN the_loai t ON st.theloai_id = t.theloai_id 
                                         GROUP BY s.sach_id
                                         ORDER BY $order_by");
                    if ($res && $res->num_rows > 0):
                        while ($row = $res->fetch_assoc()):
                            $img_src = !empty($row['hinhanh']) ? $row['hinhanh'] : 'image/default.jpg';
                    ?>
                    <tr>
                        <td><strong>#<?= $row['sach_id'] ?></strong></td>
                        <td><img src='<?= $img_src ?>' style='width: 50px; height: 65px; object-fit: contain; border-radius: 4px; border: 1px solid #ddd;'></td>
                        <td>
                            <strong><?= htmlspecialchars($row['tensach']) ?></strong><br>
                            <small style='color:#7f8c8d;'><?= substr(htmlspecialchars($row['mota'] ?? ''), 0, 40) ?>...</small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['tacgia']) ?></strong><br>
                            <small style='color:#666;'><?= htmlspecialchars($row['nhaxuatban']) ?></small>
                        </td>
                        <td>
                            Năm: <?= $row['namxuatban'] ?? 'N/A' ?><br>
                            Trang: <?= $row['so_trang'] ?? 'N/A' ?>
                        </td>
                        <td>
                            <strong style='color:#e74c3c;'><?= number_format($row['gia']) ?> đ</strong>
                            <?php if ($row['phantram_giamgia'] > 0): ?>
                                <br><span class="badge badge-danger">-<?= $row['phantram_giamgia'] ?>%</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            Bán: <strong style="color: #28a745;"><?= $row['daban'] ?></strong><br>
                            Xem: <strong style="color: #2980b9;"><?= $row['luotxem'] ?></strong>
                        </td>
                        <td><span class='badge badge-info'><?= htmlspecialchars($row['tl'] ?? 'Chưa phân loại') ?></span></td>
                        <td>
                            <a href='?action=edit&id=<?= $row['sach_id'] ?>' class='btn btn-warning btn-small'>✏️ Sửa</a>
                            <a href='?action=delete&id=<?= $row['sach_id'] ?>' class='btn btn-danger btn-small' onclick="return confirmAction(event, 'Bạn có chắc chắn muốn xóa sách này không?', 'Xác nhận xóa Sách', 'Xác nhận xóa');">🗑️ Xóa</a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                        echo "<tr><td colspan='9' style='text-align:center;'>Chưa có sách nào trong CSDL</td></tr>";
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="form-container">
            <h2><?= ($action == 'edit') ? '✏️ Cập Nhật Thông Tin Sách' : '➕ Thêm Sách Mới' ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if($action=='edit') echo "<input type='hidden' name='id' value='{$book['sach_id']}'>"; ?>
                
                <div class="form-group">
                    <label for="tensach">Tên Sách:</label> 
                    <input type="text" id="tensach" name="tensach" value="<?= htmlspecialchars($book['tensach'] ?? '') ?>" required placeholder="Nhập tên cuốn sách">
                </div>
                
                <div class="form-group">
                    <label>Thể Loại Sách (Có thể chọn nhiều):</label>
                    <div class="checkbox-grid">
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="theloai_id[]" value="<?= $cat['theloai_id'] ?>" 
                                <?= in_array($cat['theloai_id'], $selected_categories) ? 'checked' : '' ?>> <?= htmlspecialchars($cat['tentheloai']) ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kho_id">Liên Kết Kho Hàng:</label>
                    <select id="kho_id" name="kho_id">
                        <?php 
                        $warehouses->data_seek(0);
                        while ($wh = $warehouses->fetch_assoc()): 
                        ?>
                            <option value="<?= $wh['kho_id'] ?>" <?= (isset($book['kho_id']) && $book['kho_id'] == $wh['kho_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wh['tensach']) ?> (Còn tồn: <?= $wh['soluong'] ?> sản phẩm)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="tacgia">Tác Giả:</label> 
                        <input type="text" id="tacgia" name="tacgia" value="<?= htmlspecialchars($book['tacgia'] ?? '') ?>" placeholder="Tên tác giả">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="nhaxuatban">Nhà Xuất Bản (NXB):</label> 
                        <input type="text" id="nhaxuatban" name="nhaxuatban" value="<?= htmlspecialchars($book['nhaxuatban'] ?? '') ?>" placeholder="NXB Trẻ, NXB Kim Đồng...">
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="namxuatban">Năm Xuất Bản:</label> 
                        <input type="number" id="namxuatban" name="namxuatban" value="<?= $book['namxuatban'] ?? '' ?>" placeholder="2024">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="so_trang">Số Trang:</label> 
                        <input type="number" id="so_trang" name="so_trang" value="<?= $book['so_trang'] ?? '' ?>" placeholder="300">
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="gia">Giá Bán (VND):</label> 
                        <input type="number" min="0" step="1000" id="gia" name="gia" value="<?= isset($book['gia']) ? intval($book['gia']) : '' ?>" required placeholder="150000">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="phantram_giamgia">Giảm Giá (%):</label> 
                        <input type="number" min="0" max="100" id="phantram_giamgia" name="phantram_giamgia" value="<?= $book['phantram_giamgia'] ?? '0' ?>" required>
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="daban">Số Lượng Đã Bán:</label> 
                        <input type="number" id="daban" name="daban" value="<?= $book['daban'] ?? '0' ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="luotxem">Lượt Xem Sách:</label> 
                        <input type="number" id="luotxem" name="luotxem" value="<?= $book['luotxem'] ?? '0' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="mota">Mô Tả Sản Phẩm:</label> 
                    <textarea id="mota" name="mota" style="height: 120px;"><?= htmlspecialchars($book['mota'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="hinhanh_file">Hình Ảnh Bìa Sách:</label> 
                    <input type="file" id="hinhanh_file" name="hinhanh_file" accept="image/*">
                    <?php if (!empty($book['hinhanh'])): ?>
                        <div style="margin-top: 10px;">
                            <p style="margin: 0 0 5px 0; font-size: 12px; color: #666;">Ảnh bìa hiện tại:</p>
                            <img src="<?= $book['hinhanh'] ?>" width="90" style="border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="existing_hinhanh" value="<?= htmlspecialchars($book['hinhanh'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-success">💾 Lưu Sách</button>
                <a href="sach.php" class="btn btn-info">↩️ Quay Lại</a>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php include_once 'footer.php'; ?>