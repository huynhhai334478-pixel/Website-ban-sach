<?php
require_once __DIR__ . '/../ADMIN/config/connect.php';

$theloai_id = isset($_GET['theloai']) ? intval($_GET['theloai']) : 0;

$join_sql = "";
$where_sql = "";
if ($theloai_id > 0) {
    $join_sql = " JOIN sach_theloai st ON s.sach_id = st.sach_id";
    $where_sql = " WHERE st.theloai_id = $theloai_id";
}

$sql_bs_top = "SELECT DISTINCT s.* FROM sach s" . $join_sql . $where_sql . " ORDER BY s.daban DESC, s.sach_id DESC LIMIT 5";
$res_bs_top = $conn->query($sql_bs_top);
$bs_books = [];
if ($res_bs_top && $res_bs_top->num_rows > 0) {
    while ($b = $res_bs_top->fetch_assoc()) {
        $bs_books[] = $b;
    }
}

if (!empty($bs_books)): 
?>
    <!-- CỘT TRÁI: TOP 1 - 5 -->
    <div class="bestseller-left-list">
        <?php foreach ($bs_books as $idx => $bk): 
            $rank = $idx + 1;
            $gia_goc = $bk['gia'];
            $phantram_giam = intval($bk['phantram_giamgia']);
            $gia_ban = ($phantram_giam > 0) ? ($gia_goc * (1 - $phantram_giam / 100)) : $gia_goc;
            $img_src = "ADMIN/" . $bk['hinhanh'];
            $daban = intval($bk['daban']);
            $s_id = $bk['sach_id'];
            $mota_clean = !empty($bk['mota']) ? strip_tags($bk['mota']) : "Sách bán chạy hàng đầu với nhiều nội dung hấp dẫn...";
        ?>
            <div class="bestseller-rank-card rank-<?= $rank ?> <?= $rank === 1 ? 'active' : '' ?>" 
                 onclick="selectBestsellerBook(<?= $idx ?>, this)"
                 data-id="<?= $s_id ?>"
                 data-title="<?= htmlspecialchars($bk['tensach']) ?>"
                 data-img="<?= $img_src ?>"
                 data-price="<?= number_format($gia_ban, 0, ',', '.') ?> đ"
                 data-oldprice="<?= ($phantram_giam > 0) ? number_format($gia_goc, 0, ',', '.') . ' đ' : '' ?>"
                 data-discount="<?= ($phantram_giam > 0) ? '-' . $phantram_giam . '%' : '' ?>"
                 data-sold="Đã bán <?= $daban ?>"
                 data-author="<?= htmlspecialchars($bk['tacgia']) ?>"
                 data-nxb="<?= htmlspecialchars($bk['nhaxuatban'] ?? 'NXB Trẻ') ?>"
                 data-desc="<?= htmlspecialchars($mota_clean) ?>">
                
                <div class="bestseller-rank-num"><?= $rank ?></div>
                <img src="<?= $img_src ?>" class="bestseller-thumb-img" onerror="this.src='https://via.placeholder.com/60';">
                <div class="bestseller-item-info">
                    <div class="bestseller-top-badge"><i class="fas fa-fire"></i> Top <?= $rank ?></div>
                    <div class="bestseller-item-title"><?= htmlspecialchars($bk['tensach']) ?></div>
                    <div class="bestseller-item-sold">Đã bán <?= $daban ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- CỘT PHẢI: XEM TRƯỚC SẢN PHẨM TOP 1 -->
    <?php 
        $first_b = $bs_books[0];
        $f_goc = $first_b['gia'];
        $f_giam = intval($first_b['phantram_giamgia']);
        $f_ban = ($f_giam > 0) ? ($f_goc * (1 - $f_giam / 100)) : $f_goc;
        $f_img = "ADMIN/" . $first_b['hinhanh'];
        $f_mota = !empty($first_b['mota']) ? strip_tags($first_b['mota']) : "Sách bán chạy hàng đầu với nội dung vô cùng hấp dẫn và cuốn hút người đọc...";
    ?>
    <div class="bestseller-right-preview" id="bestsellerPreview">
        <div class="bestseller-preview-top">
            <div class="bestseller-large-img-card">
                <a href="chitiet.php?id=<?= $first_b['sach_id'] ?>" id="bsPreviewLinkImg">
                    <img src="<?= $f_img ?>" id="bsPreviewImg" onerror="this.src='https://via.placeholder.com/200';">
                </a>
            </div>
            <div class="bestseller-preview-info">
                <a href="chitiet.php?id=<?= $first_b['sach_id'] ?>" id="bsPreviewLinkTitle" style="text-decoration: none;">
                    <div class="bestseller-preview-title" id="bsPreviewTitle"><?= htmlspecialchars($first_b['tensach']) ?></div>
                </a>
                <div class="bestseller-preview-sold" id="bsPreviewSold">Đã bán <?= intval($first_b['daban']) ?></div>
                
                <div class="bestseller-preview-meta">
                    <span><i class="fas fa-user" style="color: #C92127;"></i> Tác giả: <b id="bsPreviewAuthor"><?= htmlspecialchars($first_b['tacgia']) ?></b></span>
                    <span><i class="fas fa-book" style="color: #0F62FE;"></i> NXB: <b id="bsPreviewNxb"><?= htmlspecialchars($first_b['nhaxuatban'] ?? 'NXB Trẻ') ?></b></span>
                </div>

                <div class="bestseller-preview-price-row">
                    <span class="bestseller-preview-price" id="bsPreviewPrice"><?= number_format($f_ban, 0, ',', '.') ?> đ</span>
                    <span class="bestseller-preview-oldprice" id="bsPreviewOldPrice" style="<?= $f_giam > 0 ? '' : 'display:none;' ?>"><?= number_format($f_goc, 0, ',', '.') ?> đ</span>
                    <span class="bestseller-preview-discount" id="bsPreviewDiscount" style="<?= $f_giam > 0 ? '' : 'display:none;' ?>">-<?= $f_giam ?>%</span>
                </div>

                <a href="chitiet.php?id=<?= $first_b['sach_id'] ?>" id="bsPreviewBtnLink" class="btn-bestseller-addcart" style="text-decoration: none; display: inline-flex;">
                    <i class="fas fa-shopping-bag"></i> Xem Chi Tiết & Mua Hàng
                </a>
            </div>
        </div>

        <div class="bestseller-preview-desc">
            <h4>Mô tả sản phẩm</h4>
            <p id="bsPreviewDesc"><?= htmlspecialchars($f_mota) ?></p>
        </div>
    </div>
<?php else: ?>
    <div style="width: 100%; padding: 40px; text-align: center; color: #777;">Chưa có sản phẩm bán chạy trong danh mục này.</div>
<?php endif; ?>
