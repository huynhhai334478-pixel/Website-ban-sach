<?php
/**
 * Quản lý người dùng (Users Management)
 * Bảng: nguoi_dung
 */

require_once 'config/connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// Bật/Tắt khóa tài khoản nhanh
if ($action == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE nguoi_dung SET trangthai = IF(trangthai=1, 0, 1) WHERE nguoidung_id = $id");
    header("Location: nguoidung.php");
    exit();
}

// Xóa người dùng
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM nguoi_dung WHERE nguoidung_id = $id";
    if ($conn->query($sql)) {
        $message = "Xóa người dùng thành công!";
        $action = 'list';
    } else {
        $error = "Lỗi xóa người dùng: " . $conn->error;
        $action = 'list';
    }
}

// Thêm hoặc cập nhật người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $taikhoan = $conn->real_escape_string($_POST['taikhoan']);
    $hoten = $conn->real_escape_string($_POST['hoten'] ?? '');
    $matkhau = $conn->real_escape_string($_POST['matkhau']);
    $sodienthoai_email = $conn->real_escape_string($_POST['sodienthoai_email']);
    $diachi = $conn->real_escape_string($_POST['diachi'] ?? '');
    $vaitro = $conn->real_escape_string($_POST['vaitro']);
    $trangthai = intval($_POST['trangthai'] ?? 1);

    $is_valid = true;

    // 1. KIỂM TRA ĐỊNH DẠNG SĐT/EMAIL
    if ($sodienthoai_email !== '') {
        // Kiểm tra nếu chuỗi chỉ toàn là số
        if (preg_match('/^[0-9]+$/', $sodienthoai_email)) {
            if (strlen($sodienthoai_email) != 10) {
                $error = "Lỗi: Số điện thoại phải bao gồm đúng 10 chữ số!";
                $is_valid = false;
            } elseif (substr($sodienthoai_email, 0, 1) !== '0') {
                $error = "Lỗi: Số điện thoại phải bắt đầu bằng số 0!";
                $is_valid = false;
            }
        } else {
            // Kiểm tra email cơ bản
            if (!filter_var($sodienthoai_email, FILTER_VALIDATE_EMAIL)) {
                $error = "Lỗi: Định dạng Email không hợp lệ!";
                $is_valid = false;
            }
        }
    } else {
        $error = "Lỗi: Vui lòng nhập Số điện thoại hoặc Email!";
        $is_valid = false;
    }

    // Nếu định dạng không hợp lệ, giữ nguyên form
    if (!$is_valid) {
        $action = isset($_POST['id']) ? 'edit' : 'add';
    } else {
        // 2. KIỂM TRA TRÙNG LẶP DỮ LIỆU
        $is_duplicate = false;
        if ($action == 'edit' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $check_sql = "SELECT taikhoan, sodienthoai_email FROM nguoi_dung 
                          WHERE (taikhoan='$taikhoan' OR sodienthoai_email='$sodienthoai_email') 
                          AND nguoidung_id != $id LIMIT 1";
        } else {
            $check_sql = "SELECT taikhoan, sodienthoai_email FROM nguoi_dung 
                          WHERE taikhoan='$taikhoan' OR sodienthoai_email='$sodienthoai_email' LIMIT 1";
        }

        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $is_duplicate = true;
            $row = $check_result->fetch_assoc();
            
            if (strtolower($row['taikhoan']) === strtolower($taikhoan)) {
                $error = "Lỗi: Tài khoản '$taikhoan' đã tồn tại trong hệ thống!";
            } else {
                $error = "Lỗi: SĐT/Email '$sodienthoai_email' đã được sử dụng!";
            }
            $action = isset($_POST['id']) ? 'edit' : 'add';
        }

        // 3. THÊM / CẬP NHẬT NẾU MỌI THỨ ĐỀU HỢP LỆ
        if (!$is_duplicate) {
            if ($action == 'edit' && isset($_POST['id'])) {
                $id = intval($_POST['id']);
                $pwd_sql = !empty($matkhau) ? ", matkhau='$matkhau'" : "";
                $sql = "UPDATE nguoi_dung SET taikhoan='$taikhoan', hoten='$hoten' $pwd_sql, 
                        sodienthoai_email='$sodienthoai_email', diachi='$diachi', vaitro='$vaitro', trangthai=$trangthai 
                        WHERE nguoidung_id=$id";
                if ($conn->query($sql)) {
                    $message = "Cập nhật người dùng thành công!";
                    $action = 'list';
                } else {
                    $error = "Lỗi cập nhật: " . $conn->error;
                    $action = 'edit';
                }
            } else {
                $sql = "INSERT INTO nguoi_dung (taikhoan, hoten, matkhau, sodienthoai_email, diachi, vaitro, trangthai) 
                        VALUES ('$taikhoan', '$hoten', '$matkhau', '$sodienthoai_email', '$diachi', '$vaitro', $trangthai)";
                if ($conn->query($sql)) {
                    $message = "Thêm người dùng thành công!";
                    $action = 'list';
                } else {
                    $error = "Lỗi thêm người dùng: " . $conn->error;
                    $action = 'add';
                }
            }
        }
    }
}

$user = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM nguoi_dung WHERE nguoidung_id=$id");
    $user = $result->fetch_assoc();
}

$page_title = "Quản lý Người Dùng - Admin";
include_once 'header.php';
?>

    <div class="container">
        <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">🔐 Quản lý Người Dùng</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <div style="margin-bottom: 20px;">
                <a href="?action=add" class="btn btn-primary">➕ Thêm Người Dùng Mới</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tài Khoản</th>
                            <th>Họ Tên</th>
                            <th>SDT/Email</th>
                            <th>Địa Chỉ</th>
                            <th>Vai Trò</th>
                            <th>Trạng Thái</th>
                            <th>Ngày Tạo</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM nguoi_dung ORDER BY nguoidung_id ASC");
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $role_badge = ($row['vaitro'] === 'admin') 
                                    ? "<span class='badge badge-danger'>Admin</span>"
                                    : "<span class='badge badge-info'>Khách Hàng</span>";
                                
                                $status_badge = ($row['trangthai'] == 1)
                                    ? "<a href='?action=toggle&id={$row['nguoidung_id']}' class='badge badge-success' style='text-decoration:none;'>Hoạt Động</a>"
                                    : "<a href='?action=toggle&id={$row['nguoidung_id']}' class='badge badge-secondary' style='text-decoration:none;'>Đã Khóa</a>";

                                echo "<tr>
                                    <td>{$row['nguoidung_id']}</td>
                                    <td><strong>" . htmlspecialchars($row['taikhoan']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['hoten'] ?? 'N/A') . "</td>
                                    <td>" . htmlspecialchars($row['sodienthoai_email']) . "</td>
                                    <td>" . htmlspecialchars($row['diachi'] ?? 'Chưa cập nhật') . "</td>
                                    <td>{$role_badge}</td>
                                    <td>{$status_badge}</td>
                                    <td>{$row['ngaytao']}</td>
                                    <td>
                                         <a href='?action=edit&id={$row['nguoidung_id']}' class='btn btn-warning btn-small'>✏️ Sửa</a>
                                         <a href='?action=delete&id={$row['nguoidung_id']}' class='btn btn-danger btn-small' onclick=\"return confirmAction(event, 'Bạn có chắc chắn muốn xóa người dùng này không?', 'Xác nhận xóa Người Dùng', 'Xác nhận xóa');\">🗑️ Xóa</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align:center;'>Không có dữ liệu</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="form-container">
                <h2><?php echo ($action == 'edit') ? '✏️ Cập Nhật Người Dùng' : '➕ Thêm Người Dùng Mới'; ?></h2>
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $user['nguoidung_id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="taikhoan">Tài Khoản:</label>
                        <input type="text" id="taikhoan" name="taikhoan" required value="<?php echo htmlspecialchars($user['taikhoan'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="hoten">Họ và Tên:</label>
                        <input type="text" id="hoten" name="hoten" value="<?php echo htmlspecialchars($user['hoten'] ?? ''); ?>" placeholder="Nguyễn Văn A">
                    </div>

                    <div class="form-group">
                        <label for="matkhau">Mật Khẩu <?php echo ($action == 'edit') ? '(Bỏ trống nếu không đổi)' : ''; ?>:</label>
                        <div style="position: relative; width: 100%;">
                            <input type="password" id="matkhau" name="matkhau" style="width: 100%; box-sizing: border-box; padding-right: 35px;" <?php echo ($action != 'edit') ? 'required' : ''; ?> value="">
                            <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;" title="Hiện/Ẩn mật khẩu">👁️</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sodienthoai_email">Số Điện Thoại / Email:</label>
                        <input type="text" id="sodienthoai_email" name="sodienthoai_email" required value="<?php echo htmlspecialchars($user['sodienthoai_email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="diachi">Địa Chỉ:</label>
                        <input type="text" id="diachi" name="diachi" value="<?php echo htmlspecialchars($user['diachi'] ?? ''); ?>" placeholder="Số nhà, đường, quận/huyện...">
                    </div>

                    <div class="form-group">
                        <label for="vaitro">Vai Trò:</label>
                        <select id="vaitro" name="vaitro">
                            <option value="khachhang" <?php echo (($user['vaitro'] ?? '') === 'khachhang') ? 'selected' : ''; ?>>Khách Hàng</option>
                            <option value="admin" <?php echo (($user['vaitro'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin (Quản trị)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="trangthai">Trạng Thái Tài Khoản:</label>
                        <select id="trangthai" name="trangthai">
                            <option value="1" <?php echo (($user['trangthai'] ?? 1) == 1) ? 'selected' : ''; ?>>Hoạt Động</option>
                            <option value="0" <?php echo (($user['trangthai'] ?? 1) == 0) ? 'selected' : ''; ?>>Khóa Tài Khoản</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">💾 Lưu Người Dùng</button>
                    <a href="nguoidung.php" class="btn btn-info">↩️ Quay Lại</a>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#matkhau');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function (e) {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
    </script>

<?php include_once 'footer.php'; ?>