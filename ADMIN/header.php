<?php
ob_start();
// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền truy cập: Nếu chưa đăng nhập HOẶC không phải là admin
if (!isset($_SESSION['taikhoan']) || $_SESSION['vaitro'] !== 'admin') {
    header("Location: ../dangnhap.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Quản Lý Web Bán Sách - Admin'; ?></title>
    
    <!-- Thư viện FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Toast & Style -->
    <link rel="stylesheet" href="../css/base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- KHUNG THÔNG BÁO TOAST ADMIN GÓC PHẢI -->
    <div id="toast-container"></div>

    <script>
    function showToast(type, message, title = '') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const titles = {
            success: 'Thành công',
            error: 'Lỗi',
            warning: 'Cảnh báo',
            info: 'Thông báo'
        };

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const toastTitle = title || titles[type] || 'Thông báo';
        const iconClass = icons[type] || icons.info;

        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon"><i class="${iconClass}"></i></div>
            <div class="toast-content">
                <div class="toast-title">${toastTitle}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="closeToast(this.parentElement)">&times;</button>
            <div class="toast-progress"><div class="toast-progress-bar"></div></div>
        `;

        container.appendChild(toast);

        const timer = setTimeout(() => {
            closeToast(toast);
        }, 2800);

        toast.dataset.timer = timer;
    }

    function closeToast(toast) {
        if (!toast || toast.classList.contains('toast-out')) return;
        if (toast.dataset.timer) clearTimeout(toast.dataset.timer);
        toast.classList.add('toast-out');
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 350);
    }

    function showConfirm(message, title = 'Xác nhận thao tác', confirmBtnText = 'Xác nhận') {
        return new Promise((resolve) => {
            const modal = document.getElementById('custom-confirm-modal');
            const titleEl = document.getElementById('confirm-modal-title');
            const msgEl = document.getElementById('confirm-modal-message');
            const btnOk = document.getElementById('confirm-modal-ok');
            const btnCancel = document.getElementById('confirm-modal-cancel');

            if (!modal) {
                resolve(confirm(message));
                return;
            }

            titleEl.innerText = title;
            msgEl.innerText = message;
            btnOk.innerText = confirmBtnText;

            modal.classList.add('active');

            function cleanup() {
                modal.classList.remove('active');
                btnOk.removeEventListener('click', handleOk);
                btnCancel.removeEventListener('click', handleCancel);
            }

            function handleOk() {
                cleanup();
                resolve(true);
            }

            function handleCancel() {
                cleanup();
                resolve(false);
            }

            btnOk.addEventListener('click', handleOk);
            btnCancel.addEventListener('click', handleCancel);
        });
    }

    function confirmAction(e, message, title = 'Xác nhận xóa', confirmBtnText = 'Xác nhận xóa', targetUrl = null) {
        if (e) e.preventDefault();
        const url = targetUrl || (e && e.currentTarget ? e.currentTarget.href : null);

        showConfirm(message, title, confirmBtnText).then(confirmed => {
            if (confirmed && url) {
                window.location.href = url;
            }
        });
        return false;
    }
    </script>

    <!-- HỘP THOẠI XÁC NHẬN SANG TRỌNG (CUSTOM CONFIRM MODAL) -->
    <div id="custom-confirm-modal" class="confirm-modal-overlay">
        <div class="confirm-modal-box">
            <div class="confirm-modal-icon" id="confirm-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirm-modal-title" id="confirm-modal-title">Xác nhận thao tác</h3>
            <p class="confirm-modal-message" id="confirm-modal-message">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
            <div class="confirm-modal-actions">
                <button type="button" class="btn-confirm-cancel" id="confirm-modal-cancel">Hủy bỏ</button>
                <button type="button" class="btn-confirm-ok" id="confirm-modal-ok">Xác nhận</button>
            </div>
        </div>
    </div>

    <?php 
    // Tự động kiểm tra và hiển thị Toast cho tất cả các dạng thông báo trong Admin
    $toast_to_show = null;

    if (isset($_SESSION['toast'])) {
        $toast_to_show = $_SESSION['toast'];
        unset($_SESSION['toast']);
    } elseif (!empty($error)) {
        $toast_to_show = ['type' => 'error', 'title' => 'Lỗi', 'message' => $error];
    } elseif (!empty($message)) {
        $toast_to_show = ['type' => 'success', 'title' => 'Thành công', 'message' => $message];
    } elseif (isset($_GET['error']) && !empty($_GET['error'])) {
        $toast_to_show = ['type' => 'error', 'title' => 'Thao tác thất bại', 'message' => $_GET['error']];
    } elseif (isset($_GET['msg'])) {
        $msg_code = $_GET['msg'];
        $msg_map = [
            'success' => 'Thao tác lưu dữ liệu thành công!',
            'approved' => 'Đã duyệt thành công!',
            'hidden' => 'Đã ẩn mục này thành công!',
            'deleted' => 'Đã xóa dữ liệu thành công!',
            'completed' => 'Đã hoàn thành đơn hàng!',
            'cancelled' => 'Đã hủy đơn hàng!'
        ];
        $msg_text = $msg_map[$msg_code] ?? $msg_code;
        $type = ($msg_code == 'cancelled' || $msg_code == 'hidden') ? 'warning' : 'success';
        $toast_to_show = ['type' => $type, 'title' => 'Thông báo', 'message' => $msg_text];
    }

    if ($toast_to_show): 
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast(
                    '<?= $toast_to_show['type'] ?>', 
                    '<?= addslashes($toast_to_show['message']) ?>', 
                    '<?= isset($toast_to_show['title']) ? addslashes($toast_to_show['title']) : '' ?>'
                );
            });
        </script>
    <?php endif; ?>

    <nav id="sidebar">
        <ul>
            <li><a href="index.php"><span class="icon">📊</span><span class="text">Trang Chủ</span></a></li>
            <li><a href="nguoidung.php"><span class="icon">👥</span><span class="text">Người Dùng</span></a></li>
            <li><a href="theloai.php"><span class="icon">📚</span><span class="text">Thể Loại</span></a></li>
            <li><a href="sach.php"><span class="icon">📖</span><span class="text">Sách</span></a></li>
            <li><a href="kho.php"><span class="icon">🏭</span><span class="text">Kho</span></a></li>
            <li><a href="nhacungcap.php"><span class="icon">🏢</span><span class="text">Nhà Cung Cấp</span></a></li>
            <li><a href="donhang.php"><span class="icon">📋</span><span class="text">Đơn Hàng</span></a></li>
            <li><a href="voucher.php"><span class="icon">🎟️</span><span class="text">Voucher</span></a></li>
            <li><a href="danhgia.php"><span class="icon">⭐</span><span class="text">Đánh Giá</span></a></li>
            <li><a href="banner.php"><span class="icon">🖼️</span><span class="text">Banner Slide</span></a></li>
            <li><a href="lienhe.php"><span class="icon">✉️</span><span class="text">Liên Hệ</span></a></li>
            <li><a href="yeuthich.php"><span class="icon">💖</span><span class="text">Yêu Thích</span></a></li>
            <li><a href="doanhthu.php"><span class="icon">💰</span><span class="text">Doanh Thu</span></a></li>
            
            <!-- Đường kẻ ngang phân cách -->
            <hr style="border: 0.5px solid #555; margin: 15px 15px;">
            
            <!-- Nút ra trang người dùng (mở tab mới) -->
            <li><a href="../index.php" target="_blank"><span class="icon">🌐</span><span class="text">Trang Khách</span></a></li>
            
            <!-- Nút đăng xuất -->
            <li><a href="../dangxuat.php"><span class="icon">🚪</span><span class="text">Đăng Xuất</span></a></li>
        </ul>
    </nav>
    <div id="main-wrapper">
        <main id="main-content">