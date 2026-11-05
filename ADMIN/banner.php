<?php
/**
 * Quản lý Banner Slide Quảng Cáo Trang Chủ
 * Bảng: banner_slide
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';
$uploadDir = __DIR__ . '/image/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. XÓA BANNER
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Lấy thông tin file ảnh để xóa
    $q = $conn->query("SELECT hinhanh FROM banner_slide WHERE banner_id = $id");
    if ($q && $q->num_rows > 0) {
        $row = $q->fetch_assoc();
        if (!empty($row['hinhanh']) && file_exists(__DIR__ . '/' . $row['hinhanh'])) {
            @unlink(__DIR__ . '/' . $row['hinhanh']);
        }
    }
    
    $sql = "DELETE FROM banner_slide WHERE banner_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa banner thành công!";
        $action = 'list';
    } else {
        $error = "Lỗi xóa banner: " . $conn->error;
        $action = 'list';
    }
}

// 2. BẬT / TẮT TRẠNG THÁI
if ($action == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE banner_slide SET trangthai = IF(trangthai=1, 0, 1) WHERE banner_id = $id");
    header("Location: banner.php");
    exit();
}

// 3. THÊM / CẬP NHẬT BANNER
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tieude = $conn->real_escape_string($_POST['tieude'] ?? '');
    $lienket = $conn->real_escape_string($_POST['lienket'] ?? '');
    $thutu = intval($_POST['thutu'] ?? 0);
    $trangthai = intval($_POST['trangthai'] ?? 1);
    $hinhanh = $_POST['existing_hinhanh'] ?? '';

    // Xử lý upload ảnh
    if (isset($_FILES['hinhanh_file']) && $_FILES['hinhanh_file']['error'] === UPLOAD_ERR_OK) {
        $fileExt = strtolower(pathinfo($_FILES['hinhanh_file']['name'], PATHINFO_EXTENSION));
        $allowedExts = array('jpg', 'jpeg', 'png', 'webp', 'gif');
        if (in_array($fileExt, $allowedExts)) {
            $fileName = 'banner_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['hinhanh_file']['tmp_name'], $uploadDir . $fileName)) {
                $hinhanh = 'image/' . $fileName;
            } else {
                $error = "Lỗi không thể lưu tập tin hình ảnh!";
            }
        } else {
            $error = "Định dạng file không hợp lệ (Chỉ chấp nhận JPG, PNG, WEBP, GIF)!";
        }
    }

    if (empty($hinhanh) && empty($error)) {
        $error = "Vui lòng chọn hình ảnh banner!";
    }

    if (empty($error)) {
        if (isset($_POST['id']) && intval($_POST['id']) > 0) { // CẬP NHẬT
            $id = intval($_POST['id']);
            $sql = "UPDATE banner_slide SET tieude='$tieude', hinhanh='$hinhanh', lienket='$lienket', thutu=$thutu, trangthai=$trangthai WHERE banner_id=$id";
            if ($conn->query($sql)) {
                $message = "Cập nhật banner thành công!";
                $action = 'list';
            } else {
                $error = "Lỗi cập nhật: " . $conn->error;
                $action = 'edit';
            }
        } else { // THÊM MỚI
            $sql = "INSERT INTO banner_slide (tieude, hinhanh, lienket, thutu, trangthai) VALUES ('$tieude', '$hinhanh', '$lienket', $thutu, $trangthai)";
            if ($conn->query($sql)) {
                $message = "Thêm banner mới thành công!";
                $action = 'list';
            } else {
                $error = "Lỗi thêm banner: " . $conn->error;
                $action = 'add';
            }
        }
    }
}

// Lấy thông tin sửa
$banner = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM banner_slide WHERE banner_id = $id");
    if ($result && $result->num_rows > 0) {
        $banner = $result->fetch_assoc();
    }
}

$page_title = "Quản lý Banner & Slide - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">🖼️ Quản lý Banner Slide Quảng Cáo</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div style="margin-bottom: 20px;">
            <a href="?action=add" class="btn btn-primary">➕ Thêm Banner Mới</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hình Ảnh</th>
                        <th>Tiêu Đề</th>
                        <th>Liên Kết</th>
                        <th>Thứ Tự</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM banner_slide ORDER BY thutu ASC, banner_id ASC");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $img_src = !empty($row['hinhanh']) ? $row['hinhanh'] : 'image/default.jpg';
                            $status_badge = ($row['trangthai'] == 1) 
                                ? "<a href='?action=toggle&id={$row['banner_id']}' class='badge badge-success' style='text-decoration:none;'>Hiển Thị</a>"
                                : "<a href='?action=toggle&id={$row['banner_id']}' class='badge badge-secondary' style='text-decoration:none;'>Ẩn</a>";
                            
                            echo "<tr>
                                <td>{$row['banner_id']}</td>
                                <td><img src='{$img_src}' style='width: 120px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;'></td>
                                <td><strong>" . htmlspecialchars($row['tieude'] ?? '') . "</strong></td>
                                <td><a href='" . htmlspecialchars($row['lienket'] ?? '#') . "' target='_blank' style='color:#3498db;'>" . htmlspecialchars($row['lienket'] ?? '') . "</a></td>
                                <td>{$row['thutu']}</td>
                                <td>{$status_badge}</td>
                                <td>
                                     <a href='?action=edit&id={$row['banner_id']}' class='btn btn-warning btn-small'>✏️ Sửa</a>
                                     <a href='?action=delete&id={$row['banner_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa banner này không?', 'Xác nhận xóa Banner', 'Xác nhận xóa');\">🗑️ Xóa</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center;'>Chưa có banner nào</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="form-container">
            <h2><?php echo ($action == 'edit') ? '✏️ Cập Nhật Banner' : '➕ Thêm Banner Mới'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $banner['banner_id']; ?>">
                    <input type="hidden" name="existing_hinhanh" value="<?php echo htmlspecialchars($banner['hinhanh'] ?? ''); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="tieude">Tiêu Đề Banner:</label>
                    <input type="text" id="tieude" name="tieude" value="<?php echo htmlspecialchars($banner['tieude'] ?? ''); ?>" placeholder="Nhập tiêu đề hoặc mô tả banner">
                </div>

                <div class="form-group">
                    <label for="hinhanh_file">Hình Ảnh Banner:</label>
                    <input type="file" id="hinhanh_file" name="hinhanh_file" accept="image/*">
                    <?php if (!empty($banner['hinhanh'])): ?>
                        <div style="margin-top: 10px;">
                            <p style="margin: 0 0 5px 0; font-size: 12px; color: #666;">Ảnh hiện tại:</p>
                            <img src="<?php echo $banner['hinhanh']; ?>" style="max-width: 250px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="lienket">Đường Dẫn Liên Kết (URL):</label>
                    <input type="text" id="lienket" name="lienket" value="<?php echo htmlspecialchars($banner['lienket'] ?? ''); ?>" placeholder="https://khuyenmai.example.com">
                </div>

                <div class="form-group">
                    <label for="thutu">Thứ Tự Hiển Thị:</label>
                    <input type="number" id="thutu" name="thutu" value="<?php echo $banner['thutu'] ?? 0; ?>" min="0">
                </div>

                <div class="form-group">
                    <label for="trangthai">Trạng Thái:</label>
                    <select id="trangthai" name="trangthai">
                        <option value="1" <?php echo (($banner['trangthai'] ?? 1) == 1) ? 'selected' : ''; ?>>Hiển Thị</option>
                        <option value="0" <?php echo (($banner['trangthai'] ?? 1) == 0) ? 'selected' : ''; ?>>Ẩn Banner</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">💾 Lưu Banner</button>
                <a href="banner.php" class="btn btn-info">↩️ Quay Lại</a>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
