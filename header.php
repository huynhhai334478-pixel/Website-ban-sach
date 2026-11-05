<?php
ob_start();
// Bắt buộc phải có session_start() ở đầu trang để lưu trạng thái đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Kết nối cơ sở dữ liệu để lấy danh sách thể loại động
require_once __DIR__ . '/ADMIN/config/connect.php';
$result_categories = $conn->query("SELECT * FROM the_loai");

// Kiểm tra trạng thái giỏ hàng chưa xem
$giohang_unread = isset($_SESSION['giohang_unread']) && $_SESSION['giohang_unread'] === true;
$tong_giohang = isset($_SESSION['giohang']) ? array_sum($_SESSION['giohang']) : 0;
$hien_badge = ($giohang_unread && $tong_giohang > 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Cửa Hàng Sách</title>
    
    <!-- Gọi thư viện Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Gọi các file CSS -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="user-header">
    <div class="container header-container">
        
        <!-- Cụm trái: Logo -->
        <div class="logo">
            <a href="index.php">
                <h1 style="color: #C92127;"><i class="fas fa-book-reader"></i> BookStore</h1>
            </a>
        </div>

        <!-- Cụm Danh Mục & Giới Thiệu -->
        <div class="nav-extra">
            <!-- Icon Menu Danh Mục mở rộng dạng Mega Menu Ngang -->
            <div class="category-dropdown-container">
                <div class="category-menu-btn" id="menuBtn" title="Danh mục sản phẩm" style="cursor: pointer;">
                    <i class="fas fa-th-large"></i>
                </div>
                
                <!-- Bảng Mega Menu lớn dạng Ngang -->
                <div class="category-popup-menu" id="categoryMenu">
                    
                    <!-- CỘT TRÁI: Lọc nhanh -->
                    <div class="menu-col-left">
                        <h4>Khám Phá Nhanh</h4>
                        <a href="sach_tatca.php"><i class="fas fa-list" style="color: #C92127; margin-right: 6px;"></i> Tất cả sản phẩm</a>
                        <a href="sach_tatca.php?sort=sold_desc"><i class="fas fa-fire" style="color: #dc3545; margin-right: 6px;"></i> Sách bán chạy nhất</a>
                        <a href="sach_tatca.php?sort=fav_desc"><i class="fas fa-heart" style="color: #e84393; margin-right: 6px;"></i> Sách được yêu thích nhất</a>
                        <a href="sach_tatca.php?sort=views_desc"><i class="fas fa-eye" style="color: #2980b9; margin-right: 6px;"></i> Sách xem nhiều nhất</a>
                        <a href="sach_tatca.php?sort=new"><i class="fas fa-star" style="color: #28a745; margin-right: 6px;"></i> Sách mới cập nhật</a>
                        <a href="sach_tatca.php?loc=tren_100k"><i class="fas fa-arrow-up" style="color: #27ae60; margin-right: 6px;"></i> Sách trên 100.000đ</a>
                        <a href="sach_tatca.php?loc=duoi_100k"><i class="fas fa-arrow-down" style="color: #f39c12; margin-right: 6px;"></i> Sách dưới 100.000đ</a>
                    </div>

                    <!-- CỘT PHẢI: Hiển thị toàn bộ thể loại từ CSDL -->
                    <div class="menu-col-right">
                        <h4>Thể Loại Sách</h4>
                        <div class="category-grid-items">
                            <?php if ($result_categories && $result_categories->num_rows > 0): ?>
                                <?php while ($cat = $result_categories->fetch_assoc()): ?>
                                    <a href="sach_tatca.php?theloai=<?= $cat['theloai_id'] ?>">
                                        <i class="fas fa-angle-right" style="color: #888; font-size: 11px; margin-right: 4px;"></i> <?= htmlspecialchars($cat['tentheloai']) ?>
                                    </a>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
            
            <!-- Link Giới Thiệu -->
            <a href="gioithieu.php" class="nav-intro-link">Giới Thiệu</a>
        </div>

        <!-- Cụm giữa: Thanh tìm kiếm có Gợi ý (Autocomplete) -->
        <div class="header-search" style="position: relative;">
            <form action="timkiem.php" method="GET" style="position: relative;">
                <input type="text" name="tukhoa" id="searchInput" placeholder="Tìm kiếm sản phẩm, tác giả..." autocomplete="off" required>
                <button type="submit" title="Tìm kiếm"><i class="fas fa-search"></i></button>
            </form>
            <!-- Khung hiện kết quả gợi ý -->
            <div id="searchSuggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 999; margin-top: 5px; max-height: 350px; overflow-y: auto;">
            </div>
        </div>

        <!-- Cụm phải: Các tính năng -->
        <div class="header-actions">
            
            <!-- Nút Liên Hệ -->
            <a href="lienhe.php" class="action-item">
                <i class="far fa-envelope"></i>
                <span>Liên Hệ</span>
            </a>

            <!-- Nút Giỏ Hàng có id định danh -->
            <a href="giohang.php" class="action-item" id="cartIcon" style="position: relative;">
                <div class="icon-wrap">
                    <i class="fas fa-shopping-cart" style="color: #555;"></i>
                    <span class="badge" id="cartBadge" style="<?= $hien_badge ? '' : 'display: none;' ?>"><?= $tong_giohang ?></span>
                </div>
                <span>Giỏ Hàng</span>
            </a>

            <!-- Nút Tài Khoản -->
            <div class="account-dropdown action-item">
                <div style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <i class="far fa-user"></i>
                    <span>Tài khoản</span>
                </div>
                
                <div class="dropdown-content">
                    <?php if (isset($_SESSION['taikhoan'])): ?>
                        <p style="padding: 12px; font-weight: bold; border-bottom: 1px solid #eee; text-align: center; color: #333; margin:0; font-size: 14px;">
                            Chào, <?= htmlspecialchars($_SESSION['taikhoan']) ?>
                        </p>
                        
                        <?php if (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] == 'admin'): ?>
                            <a href="ADMIN/index.php"><i class="fas fa-tachometer-alt"></i> Quản trị Website</a>
                        <?php endif; ?>
                        
                        <a href="taikhoan.php"><i class="fas fa-user-cog" style="color: #28a745;"></i> Thông tin tài khoản</a>
                        <a href="donhang_cuatoi.php"><i class="fas fa-box" style="color: #0F62FE;"></i> Đơn hàng của tôi</a>
                        <a href="sach_yeuthich.php"><i class="fas fa-heart" style="color: #e84393;"></i> Sách yêu thích</a>

                        <a href="dangxuat.php" style="color: #C92127;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                    <?php else: ?>
                        <a href="dangnhap.php" class="btn-login-drop">Đăng nhập</a>
                        <a href="dangky.php" class="btn-register-drop">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</header>

<!-- Đoạn Script xử lý ẩn/hiện menu danh mục và gợi ý tìm kiếm AJAX -->
<script>
    const menuBtn = document.getElementById('menuBtn');
    const categoryMenu = document.getElementById('categoryMenu');

    if (menuBtn && categoryMenu) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (categoryMenu.style.display === 'flex') {
                categoryMenu.style.display = 'none';
            } else {
                categoryMenu.style.display = 'flex';
            }
        });

        window.addEventListener('click', function() {
            categoryMenu.style.display = 'none';
        });

        categoryMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Xử lý gợi ý tìm kiếm tức thì bằng Ajax
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            let query = this.value.trim();
            if (query.length > 0) {
                // Đã cập nhật đúng đường dẫn trỏ vào folder ajax
                fetch('ajax/ajax_sach.php?q=' + encodeURIComponent(query))
                    .then(response => response.text())
                    .then(data => {
                        searchSuggestions.innerHTML = data;
                        searchSuggestions.style.display = 'block';
                    });
            } else {
                searchSuggestions.style.display = 'none';
            }
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });
    }
</script>

<!-- KHUNG THÔNG BÁO TOAST GÓC PHẢI -->
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
    }, 2200);

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

function confirmAction(e, message, title = 'Xác nhận thao tác', confirmBtnText = 'Xác nhận', targetUrl = null) {
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

<?php if (isset($_SESSION['toast'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(
                '<?= $_SESSION['toast']['type'] ?>', 
                '<?= addslashes($_SESSION['toast']['message']) ?>', 
                '<?= isset($_SESSION['toast']['title']) ? addslashes($_SESSION['toast']['title']) : '' ?>'
            );
        });
    </script>
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<!-- Khung chứa nội dung chính -->
<main class="main-content container">