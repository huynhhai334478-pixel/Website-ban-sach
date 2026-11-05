<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Lấy ID sản phẩm từ URL và kiểm tra an toàn
$sach_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sach_id <= 0) {
    echo '<div class="container" style="padding: 50px; text-align: center;"><h3>Sản phẩm không tồn tại hoặc đã bị xóa!</h3><a href="sach_tatca.php" style="color: #C92127; text-decoration: underline;">Quay lại danh sách sản phẩm</a></div>';
    include 'footer.php';
    exit();
}

// Truy vấn thông tin chi tiết sách
$sql = "SELECT s.* FROM sach s WHERE s.sach_id = $sach_id LIMIT 1";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    echo '<div class="container" style="padding: 50px; text-align: center;"><h3>Không tìm thấy thông tin cuốn sách này!</h3><a href="sach_tatca.php" style="color: #C92127; text-decoration: underline;">Quay lại danh sách sản phẩm</a></div>';
    include 'footer.php';
    exit();
}

$book = $result->fetch_assoc();

// Tính toán giá bán sau khi giảm
$gia_goc = $book['gia'];
$phantram_giam = intval($book['phantram_giamgia']);
$gia_ban = $phantram_giam > 0 ? $gia_goc * (1 - $phantram_giam / 100) : $gia_goc;

$hien_thi_gia_ban = number_format($gia_ban, 0, ',', '.') . ' đ';
$hien_thi_gia_goc = number_format($gia_goc, 0, ',', '.') . ' đ';
$hinhanh = "ADMIN/" . $book['hinhanh'];
$daban = intval($book['daban']);
$nhaxuatban = !empty($book['nhaxuatban']) ? $book['nhaxuatban'] : 'Đang cập nhật';

// Lấy thể loại của sách
$cat_query = $conn->query("SELECT t.tentheloai, t.theloai_id FROM the_loai t JOIN sach_theloai st ON t.theloai_id = st.theloai_id WHERE st.sach_id = $sach_id");
$theloai_str = "";
$theloai_id_first = 0;
if ($cat_query && $cat_query->num_rows > 0) {
    $cats = [];
    while($c = $cat_query->fetch_assoc()) {
        $cats[] = '<a href="sach_tatca.php?theloai='.$c['theloai_id'].'" style="color: #C92127; text-decoration: none;">'.$c['tentheloai'].'</a>';
        if ($theloai_id_first == 0) $theloai_id_first = $c['theloai_id'];
    }
    $theloai_str = implode(', ', $cats);
}

// Lấy tổng số đánh giá và điểm trung bình sao từ bảng danh_gia
$dg_count_q = $conn->query("SELECT COUNT(*) as total_dg, AVG(sosao) as avg_sao FROM danh_gia WHERE sach_id = $sach_id");
$dg_data = $dg_count_q->fetch_assoc();
$total_danhgia = intval($dg_data['total_dg']);
$avg_sao = $dg_data['avg_sao'] ? round($dg_data['avg_sao'], 1) : 0;

// Kiểm tra trạng thái yêu thích của người dùng hiện tại
$is_logged_in = isset($_SESSION['nguoidung_id']);
$nguoidung_id = $is_logged_in ? intval($_SESSION['nguoidung_id']) : 0;
$is_favorite = false;

if ($is_logged_in) {
    $fav_check = $conn->query("SELECT yeuthich_id FROM yeu_thich WHERE nguoidung_id = $nguoidung_id AND sach_id = $sach_id LIMIT 1");
    if ($fav_check && $fav_check->num_rows > 0) {
        $is_favorite = true;
    }
}

// Tính tổng số lượt yêu thích của sản phẩm này
$fav_count_q = $conn->query("SELECT COUNT(*) as total_fav FROM yeu_thich WHERE sach_id = $sach_id");
$fav_count_data = $fav_count_q->fetch_assoc();
$total_fav = intval($fav_count_data['total_fav']);
?>

<!-- Khung chi tiết sản phẩm -->
<div style="max-width: 1200px; margin: 20px auto; padding: 0 15px; font-family: Arial, sans-serif;">
    
    <!-- Breadcrumb điều hướng -->
    <div style="font-size: 13px; color: #777; margin-bottom: 15px;">
        <a href="index.php" style="color: #555; text-decoration: none;">Trang chủ</a> &gt; 
        <span><?= !empty($theloai_str) ? $theloai_str : 'Sách'; ?></span> &gt; 
        <span style="color: #333; font-weight: 500;"><?= htmlspecialchars($book['tensach']) ?></span>
    </div>

    <!-- Khung chính 2 cột -->
    <div style="display: flex; gap: 30px; align-items: flex-start;">
        
        <!-- CỘT TRÁI: Hình ảnh chính -->
        <div style="width: 400px; flex-shrink: 0; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
            <div style="width: 100%; height: 420px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; background: #fafafa;">
                <img id="productImg" src="<?= $hinhanh ?>" alt="<?= htmlspecialchars($book['tensach']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        </div>

        <!-- CỘT PHẢI: Thông tin chi tiết sách -->
        <div style="flex: 1; background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #eee;">
            <h1 style="font-size: 22px; color: #222; margin: 0 0 15px 0; font-weight: 700; line-height: 1.4;"><?= htmlspecialchars($book['tensach']) ?></h1>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13.5px; color: #555; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                <div>Tác giả: <b style="color: #000;"><?= htmlspecialchars($book['tacgia']) ?></b></div>
                <div>Nhà xuất bản: <b style="color: #000;"><?= htmlspecialchars($book['nhaxuatban']) ?></b></div>
                <div>Đã bán: <b style="color: #C92127;"><?= $daban ?></b></div>
                <div>Thể loại: <span><?= !empty($theloai_str) ? $theloai_str : 'Đang cập nhật'; ?></span></div>
            </div>

            <!-- Đánh giá sao -->
            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #777; margin-bottom: 15px;">
                <div style="color: #ffc107;">
                    <?php 
                    $sao_in = round($avg_sao);
                    echo str_repeat('★', $sao_in) . str_repeat('☆', 5 - $sao_in);
                    ?>
                </div>
                <span>(<?= $total_danhgia ?> đánh giá)</span>
                <span>| Đã bán <?= $daban ?></span>
            </div>

            <!-- Giá tiền -->
            <div style="background: #FFF5F5; border: 1px solid #FFDCDC; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; align-items: baseline; gap: 15px;">
                    <span style="font-size: 28px; color: #C92127; font-weight: bold;"><?= $hien_thi_gia_ban ?></span>
                    <?php if ($phantram_giam > 0): ?>
                        <span style="font-size: 15px; color: #888; text-decoration: line-through;"><?= $hien_thi_gia_goc ?></span>
                        <span style="background: #C92127; color: white; padding: 2px 6px; font-size: 12px; font-weight: bold; border-radius: 4px;">-<?= $phantram_giam ?>%</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vận chuyển -->
            <div style="background: #f8f9fa; padding: 12px 15px; border-radius: 6px; font-size: 13.5px; color: #444; margin-bottom: 20px;">
                <div style="font-weight: bold; margin-bottom: 5px; color: #333;"><i class="fas fa-truck" style="color: #28a745; margin-right: 5px;"></i> Thông tin vận chuyển</div>
                <div>Giao hàng toàn quốc</div>
                <div style="color: #28a745; margin-top: 3px;">✔ Giao tiêu chuẩn nhanh chóng</div>
            </div>

            <!-- Số lượng -->
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <span style="font-size: 14px; color: #333; font-weight: 500;">Số lượng:</span>
                <div style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                    <button type="button" onclick="giamSoLuong()" style="width: 32px; height: 36px; background: #f1f1f1; border: none; cursor: pointer; font-size: 16px;">-</button>
                    <input type="text" id="boxSoluong" value="1" readonly style="width: 45px; height: 36px; text-align: center; border: none; font-size: 14px; background: #fff;">
                    <button type="button" onclick="tangSoLuong()" style="width: 32px; height: 36px; background: #f1f1f1; border: none; cursor: pointer; font-size: 16px;">+</button>
                </div>
            </div>

            <!-- Form nút bấm Mua Hàng & Yêu Thích (Đã di chuyển xuống dưới Số Lượng) -->
            <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 12px; max-width: 450px;">
                <div style="display: flex; gap: 12px;">
                    <button type="button" id="btnAddCart" class="btn-cart">
                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ hàng
                    </button>
                    <button type="button" id="btnBuyNow" class="btn-buy">
                        <i class="fas fa-bolt"></i> Mua Ngay
                    </button>
                </div>
                <button type="button" id="btnFavorite" class="btn-favorite <?= $is_favorite ? 'active' : ''; ?>">
                    <i class="<?= $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i> 
                    <span id="favText"><?= $is_favorite ? 'Đã yêu thích sản phẩm' : 'Thêm vào yêu thích'; ?></span>
                    <span id="favCount" class="fav-count-badge"><?= $total_fav ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mô Tả Sách -->
    <div style="background: #fff; border-radius: 8px; border: 1px solid #eee; padding: 25px; margin-top: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <h3 style="font-size: 16px; color: #333; border-bottom: 2px solid #C92127; padding-bottom: 8px; margin: 0 0 15px 0; text-transform: uppercase;">Mô tả sản phẩm</h3>
        <div style="font-size: 14px; color: #444; line-height: 1.6;">
            <?= !empty($book['mota']) ? nl2br(htmlspecialchars($book['mota'])) : "<p style='color: #777; font-style: italic;'>Đang cập nhật nội dung mô tả...</p>"; ?>
        </div>
    </div>

    <!-- REQUIRE NHÚNG FILE ĐÁNH GIÁ VÀO ĐÂY -->
    <?php require_once 'danh_gia.php'; ?>

</div>

<!-- JavaScript xử lý hiệu ứng ảnh bay và gọi tệp trong thư mục ajax -->
<script>
    function tangSoLuong() {
        let box = document.getElementById('boxSoluong');
        let val = parseInt(box.value);
        if (val < 99) { box.value = ++val; }
    }

    function giamSoLuong() {
        let box = document.getElementById('boxSoluong');
        let val = parseInt(box.value);
        if (val > 1) { box.value = --val; }
    }

    document.getElementById('btnAddCart').addEventListener('click', function() {
        let img = document.getElementById('productImg');
        let cartIcon = document.getElementById('cartIcon');
        let soluong = document.getElementById('boxSoluong').value;

        // Hiệu ứng ảnh thu nhỏ bay vào giỏ hàng
        let imgFly = img.cloneNode(true);
        let rect = img.getBoundingClientRect();
        let cartRect = cartIcon.getBoundingClientRect();

        imgFly.style.position = 'fixed';
        imgFly.style.top = rect.top + 'px';
        imgFly.style.left = rect.left + 'px';
        imgFly.style.width = rect.width + 'px';
        imgFly.style.height = rect.height + 'px';
        imgFly.style.zIndex = '9999';
        imgFly.style.transition = 'all 0.8s cubic-bezier(0.2, 1, 0.3, 1)';
        imgFly.style.borderRadius = '6px';
        imgFly.style.objectFit = 'contain';
        imgFly.style.background = '#fff';

        document.body.appendChild(imgFly);

        setTimeout(() => {
            imgFly.style.top = cartRect.top + 'px';
            imgFly.style.left = cartRect.left + 'px';
            imgFly.style.width = '20px';
            imgFly.style.height = '20px';
            imgFly.style.opacity = '0.5';
        }, 50);

        setTimeout(() => {
            imgFly.remove();
        }, 850);

        // Gọi Ajax trỏ đúng vào thư mục ajax/them_giohang_ajax.php
        let formData = new FormData();
        formData.append('sach_id', <?= $sach_id ?>);
        formData.append('soluong', soluong);

        fetch('ajax/them_giohang_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                let badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.innerText = data.total_items;
                    badge.style.display = 'inline-block';
                    badge.style.transform = 'scale(1.4)';
                    setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
                }
            }
        });
    });

    document.getElementById('btnBuyNow').addEventListener('click', function() {
        let soluong = document.getElementById('boxSoluong').value;
        let formData = new FormData();
        formData.append('sach_id', <?= $sach_id ?>);
        formData.append('soluong', soluong);

        fetch('ajax/them_giohang_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            window.location.href = 'thanhtoan.php';
        });
    });

    // Xử lý Thêm / Bỏ yêu thích
    const btnFav = document.getElementById('btnFavorite');
    if (btnFav) {
        btnFav.addEventListener('click', function() {
            let isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
            if (!isLoggedIn) {
                if (typeof showToast === 'function') showToast('warning', 'Vui lòng đăng nhập để lưu sản phẩm yêu thích!');
                window.location.href = 'dangnhap.php';
                return;
            }

            let favText = document.getElementById('favText');
            let favCount = document.getElementById('favCount');
            let icon = btnFav.querySelector('i');

            let formData = new FormData();
            formData.append('sach_id', <?= $sach_id ?>);

            fetch('ajax/yeuthich_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'added') {
                        btnFav.classList.add('active');
                        icon.className = 'fas fa-heart';
                        favText.innerText = 'Đã yêu thích sản phẩm';
                    } else {
                        btnFav.classList.remove('active');
                        icon.className = 'far fa-heart';
                        favText.innerText = 'Thêm vào yêu thích';
                    }
                    if (favCount) favCount.innerText = data.total;
                    if (typeof showToast === 'function') {
                        showToast(data.action === 'added' ? 'success' : 'info', data.message);
                    }
                } else if (data.status === 'login_required') {
                    if (typeof showToast === 'function') showToast('warning', data.message); else alert(data.message);
                    setTimeout(() => { window.location.href = 'dangnhap.php'; }, 1000);
                }
            })
            .catch(err => {
                console.error('Lỗi yêu thích:', err);
            });
        });
    }
</script>

<?php
$conn->close();
include 'footer.php';
?>