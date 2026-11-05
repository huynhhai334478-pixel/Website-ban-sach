<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['nguoidung_id'])) {
    echo '
    <div style="max-width: 800px; margin: 50px auto; text-align: center; background: #fff; padding: 40px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <i class="fas fa-heart-broken" style="font-size: 60px; color: #e84393; margin-bottom: 20px;"></i>
        <h2 style="font-size: 22px; color: #333; margin-bottom: 10px;">Vui lòng đăng nhập để xem Sách Yêu Thích!</h2>
        <p style="color: #666; margin-bottom: 25px;">Đăng nhập để xem lại các cuốn sách bạn đã thả tim và lưu giữ.</p>
        <a href="dangnhap.php" class="btn-primary" style="background: #C92127; border-radius: 25px; padding: 10px 30px; font-weight: bold;">Đăng nhập ngay</a>
    </div>';
    include 'footer.php';
    exit();
}

$nguoidung_id = intval($_SESSION['nguoidung_id']);

// Lấy danh sách sản phẩm được người dùng này thả tim
$sql = "SELECT s.*, y.yeuthich_id, y.ngaytao as ngay_thich 
        FROM sach s 
        JOIN yeu_thich y ON s.sach_id = y.sach_id 
        WHERE y.nguoidung_id = $nguoidung_id 
        ORDER BY y.yeuthich_id DESC";
$result = $conn->query($sql);
?>

<div style="max-width: 1200px; margin: 25px auto; padding: 0 15px;">
    
    <!-- KHUNG TIÊU ĐỀ -->
    <div style="background: #fff; padding: 18px 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between;">
        <h2 style="font-size: 20px; color: #333; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-heart" style="color: #e84393;"></i> Sách Đã Yêu Thích Của Tôi
            <span id="fav-total-count" style="font-size: 14px; font-weight: normal; color: #777;">(<?= $result ? $result->num_rows : 0 ?> sản phẩm)</span>
        </h2>
        <a href="sach_tatca.php" style="color: #C92127; text-decoration: none; font-size: 14px; font-weight: 600;">Khám phá thêm sách &gt;</a>
    </div>

    <!-- DANH SÁCH SÁCH YÊU THÍCH -->
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="book-grid" id="fav-book-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
            <?php while ($row = $result->fetch_assoc()): 
                $gia_goc = $row['gia'];
                $phantram_giam = intval($row['phantram_giamgia']);
                $gia_ban = $phantram_giam > 0 ? $gia_goc * (1 - $phantram_giam / 100) : $gia_goc;
                $hien_thi_gia_ban = number_format($gia_ban, 0, ',', '.') . ' đ';
                $hien_thi_gia_goc = number_format($gia_goc, 0, ',', '.') . ' đ';
                $hinhanh = "ADMIN/" . $row['hinhanh'];
                $daban = intval($row['daban']);
                $sach_id = $row['sach_id'];
                $percent = ($daban > 0) ? min(95, max(25, $daban * 10)) : 15;
            ?>
                <div class="book-card" id="fav-card-<?= $sach_id ?>">
                    <a href="chitiet.php?id=<?= $sach_id ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                        <div class="book-img-wrapper">
                            <img src="<?= $hinhanh ?>" alt="<?= htmlspecialchars($row['tensach']) ?>" class="book-img" onerror="this.src='https://via.placeholder.com/150';">
                        </div>
                        <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <h4 class="book-title" title="<?= htmlspecialchars($row['tensach']) ?>">
                                <?= htmlspecialchars($row['tensach']) ?>
                            </h4>
                            <div>
                                <div class="price-area">
                                    <div class="price-top-row">
                                        <span class="price-current"><?= $hien_thi_gia_ban ?></span>
                                        <?php if ($phantram_giam > 0): ?>
                                            <span class="discount-badge">-<?= $phantram_giam ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($phantram_giam > 0): ?>
                                        <div class="price-old"><?= $hien_thi_gia_goc ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="sold-progress-container">
                                    <div class="sold-progress-fill" style="width: <?= $percent ?>%;"></div>
                                    <span class="sold-progress-text">Đã bán <?= $daban ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <!-- Nút xóa nhanh khỏi Yêu thích -->
                    <button type="button" onclick="removeFavorite(<?= $sach_id ?>)" title="Bỏ yêu thích" style="position: absolute; top: 8px; right: 8px; background: rgba(255,255,255,0.9); border: 1px solid #ffdcdc; border-radius: 50%; width: 28px; height: 28px; color: #e84393; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; transition: 0.2s;">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
        <div id="empty-fav-message" style="display: none; background: #fff; padding: 50px 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <i class="far fa-heart" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
            <p style="font-size: 16px; color: #666; margin-bottom: 20px;">Bạn chưa yêu thích cuốn sách nào!</p>
            <a href="sach_tatca.php" class="btn-primary" style="background: #C92127; border-radius: 25px; padding: 9px 25px; font-weight: bold;">Khám phá ngay</a>
        </div>
    <?php else: ?>
        <div id="empty-fav-message" style="background: #fff; padding: 50px 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <i class="far fa-heart" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
            <p style="font-size: 16px; color: #666; margin-bottom: 20px;">Bạn chưa yêu thích cuốn sách nào!</p>
            <a href="sach_tatca.php" class="btn-primary" style="background: #C92127; border-radius: 25px; padding: 9px 25px; font-weight: bold;">Khám phá ngay</a>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFavorite(sachId) {
    showConfirm('Bạn có muốn bỏ cuốn sách này khỏi danh sách yêu thích?', 'Bỏ yêu thích sản phẩm', 'Xác nhận').then(confirmed => {
        if (confirmed) {
            let formData = new FormData();
            formData.append('sach_id', sachId);
            fetch('ajax/yeuthich_ajax.php', {
                method: 'POST',
                body: formData
            })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                let card = document.getElementById('fav-card-' + sachId);
                if (card) {
                    card.style.transform = 'scale(0.8)';
                    card.style.opacity = '0';
                    setTimeout(() => { 
                        card.remove(); 
                        
                        let userTotal = data.user_total !== undefined ? data.user_total : document.querySelectorAll('#fav-book-grid .book-card').length;
                        let countSpan = document.getElementById('fav-total-count');
                        if (countSpan) {
                            countSpan.innerText = '(' + userTotal + ' sản phẩm)';
                        }

                        if (userTotal === 0) {
                            let grid = document.getElementById('fav-book-grid');
                            if (grid) grid.style.display = 'none';
                            let emptyMsg = document.getElementById('empty-fav-message');
                            if (emptyMsg) emptyMsg.style.display = 'block';
                        }

                        if (typeof showToast === 'function') {
                            showToast('info', 'Đã bỏ sản phẩm khỏi danh sách yêu thích!');
                        }
                    }, 300);
                }
            } else if (data.status === 'login_required') {
                if (typeof showToast === 'function') showToast('warning', data.message); else alert(data.message);
                setTimeout(() => { window.location.href = 'dangnhap.php'; }, 1000);
            }
        })
        .catch(err => {
            console.error('Lỗi khi xóa khỏi danh sách yêu thích:', err);
        });
        }
    });
}
</script>

<?php
$conn->close();
include 'footer.php';
?>
