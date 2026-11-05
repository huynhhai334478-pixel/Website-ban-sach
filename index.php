<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php'; 

// Lấy danh sách Banner động đang kích hoạt (trangthai = 1)
$sql_banner = "SELECT * FROM banner_slide WHERE trangthai = 1 ORDER BY thutu ASC, banner_id ASC";
$res_banner = $conn->query($sql_banner);

// Hàm hiển thị lưới sách chuẩn Fahasa
function renderBookGrid($result) {
    if ($result && $result->num_rows > 0) {
        echo '<div class="book-grid">';
        while ($row = $result->fetch_assoc()) {
            $gia_goc = $row['gia'];
            $phantram_giam = intval($row['phantram_giamgia']);
            $gia_ban = ($phantram_giam > 0) ? ($gia_goc * (1 - $phantram_giam / 100)) : $gia_goc;
            $hien_thi_gia_ban = number_format($gia_ban, 0, ',', '.') . ' đ';
            $hien_thi_gia_goc = number_format($gia_goc, 0, ',', '.') . ' đ';
            $hinhanh = "ADMIN/" . $row['hinhanh'];
            $daban = intval($row['daban']);
            $sach_id = $row['sach_id'];

            // Tính % thanh tiến độ "Đã bán"
            $percent = ($daban > 0) ? min(95, max(25, $daban * 10)) : 15;

            echo '
            <div class="book-card">
                <a href="chitiet.php?id='.$sach_id.'" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                    <div class="book-img-wrapper">
                        <img src="'.$hinhanh.'" alt="'.htmlspecialchars($row['tensach']).'" class="book-img" onerror="this.src=\'https://via.placeholder.com/150\';">
                    </div>
                    <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <h4 class="book-title" title="'.htmlspecialchars($row['tensach']).'">
                            '.htmlspecialchars($row['tensach']).'
                        </h4>
                        <div>
                            <div class="price-area">
                                <div class="price-top-row">
                                    <span class="price-current">'.$hien_thi_gia_ban.'</span>';
                                    if ($phantram_giam > 0) {
                                        echo '<span class="discount-badge">-'.$phantram_giam.'%</span>';
                                    }
            echo '              </div>';
                                if ($phantram_giam > 0) {
                                    echo '<div class="price-old">'.$hien_thi_gia_goc.'</div>';
                                }
            echo '          </div>
                            <div class="sold-progress-container">
                                <div class="sold-progress-fill" style="width: '.$percent.'%;"></div>
                                <span class="sold-progress-text">Đã bán '.$daban.'</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>';
        }
        echo '</div>';
    } else {
        echo '<p style="text-align: center; color: #777; padding: 20px;">Chưa có sản phẩm nào trong mục này.</p>';
    }
}
?>

<!-- 1. Banner Quảng Cáo Slide Động -->
<?php if ($res_banner && $res_banner->num_rows > 0): ?>
    <div class="banner-slider-container">
        <div class="banner-slides-wrapper" id="bannerSlidesWrapper">
            <?php 
            $slide_count = 0;
            while ($b = $res_banner->fetch_assoc()):
                $img_path = $b['hinhanh'];
                if (strpos($img_path, 'ADMIN/') !== 0 && strpos($img_path, 'http') !== 0) {
                    $img_path = 'ADMIN/' . $img_path;
                }
                $link = !empty($b['lienket']) ? $b['lienket'] : '#sach-ban-chay';
                $slide_count++;
            ?>
                <div class="banner-slide-item">
                    <a href="<?php echo htmlspecialchars($link); ?>">
                        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($b['tieude'] ?? 'Banner Quảng Cáo'); ?>">
                        <?php if (!empty($b['tieude'])): ?>
                            <div class="banner-slide-caption">
                                <h2><?php echo htmlspecialchars($b['tieude']); ?></h2>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($slide_count > 1): ?>
            <button class="slider-prev-btn" onclick="moveSlide(-1)"><i class="fas fa-chevron-left"></i></button>
            <button class="slider-next-btn" onclick="moveSlide(1)"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots-container" id="sliderDotsContainer">
                <?php for($i = 0; $i < $slide_count; $i++): ?>
                    <span class="slider-dot <?php echo $i===0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let currentSlide = 0;
        const totalSlides = <?php echo $slide_count; ?>;
        const wrapper = document.getElementById('bannerSlidesWrapper');
        const dots = document.querySelectorAll('.slider-dot');

        function updateSlidePosition() {
            if (wrapper) {
                wrapper.style.transform = `translateX(-${currentSlide * 100}%)`;
            }
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function moveSlide(direction) {
            currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
            updateSlidePosition();
        }

        function goToSlide(slideIndex) {
            currentSlide = slideIndex;
            updateSlidePosition();
        }

        if (totalSlides > 1) {
            setInterval(() => { moveSlide(1); }, 4000);
        }
    </script>
<?php endif; ?>

<!-- 2. 📑 DANH MỤC SẢN PHẨM (CHUẨN FAHASA) -->
<div class="category-block-container">
    <div class="category-block-header">
        <i class="fas fa-th-large" style="color: #C92127; font-size: 20px;"></i>
        <h3>Danh mục sản phẩm</h3>
    </div>
    <div class="category-grid-list">
        <?php
        $cat_icons = [
            'Văn học' => 'fa-book-open',
            'Kinh tế' => 'fa-chart-line',
            'Công nghệ' => 'fa-laptop-code',
            'Văn học kinh điển' => 'fa-feather-alt',
            'Tiểu thuyết' => 'fa-book-reader',
            'Trinh thám' => 'fa-user-secret',
            'Khoa học viễn tưởng' => 'fa-rocket',
            'Kỹ năng sống' => 'fa-lightbulb',
            'Tài chính cá nhân' => 'fa-wallet',
            'Lịch sử' => 'fa-landmark',
            'Tâm lý học' => 'fa-brain',
            'Truyện tranh' => 'fa-images',
            'Thiếu nhi' => 'fa-child'
        ];
        $res_cats = $conn->query("SELECT * FROM the_loai LIMIT 12");
        if ($res_cats && $res_cats->num_rows > 0):
            while ($cat = $res_cats->fetch_assoc()):
                $cat_name = htmlspecialchars($cat['tentheloai']);
                $icon_class = $cat_icons[$cat['tentheloai']] ?? 'fa-book';
        ?>
            <a href="sach_tatca.php?theloai=<?= $cat['theloai_id'] ?>" class="category-item-card">
                <i class="fas <?= $icon_class ?>"></i>
                <span><?= $cat_name ?></span>
            </a>
        <?php 
            endwhile;
        endif; 
        ?>
    </div>
</div>

<!-- 4. 🔥 Sách Bán Chạy Nhất (Best Sellers Widget Chuẩn Fahasa) -->
<section id="sach-ban-chay" class="bestseller-widget-wrapper">
    
    <!-- Header & Category Tabs -->
    <div class="bestseller-widget-header">
        <h3 class="bestseller-header-title">
            <i class="fas fa-fire"></i> Best Sellers
        </h3>
        <div class="bestseller-tabs-list">
            <span class="bestseller-tab-item active" onclick="switchBestsellerTab(0, this)">Tất cả</span>
            <?php
            $tab_cats = $conn->query("SELECT * FROM the_loai LIMIT 6");
            if ($tab_cats && $tab_cats->num_rows > 0):
                while ($tc = $tab_cats->fetch_assoc()):
            ?>
                <span class="bestseller-tab-item" onclick="switchBestsellerTab(<?= $tc['theloai_id'] ?>, this)"><?= htmlspecialchars($tc['tentheloai']) ?></span>
            <?php 
                endwhile;
            endif;
            ?>
        </div>
    </div>

    <!-- Container 2 cột: Danh sách trái (Rank 1-5) & Xem trước phải -->
    <div id="bestsellerContainer" class="bestseller-split-container">
        <?php
        // Lấy Top 5 sách bán chạy nhất
        $sql_bs_top = "SELECT * FROM sach ORDER BY daban DESC, sach_id DESC LIMIT 5";
        $res_bs_top = $conn->query($sql_bs_top);
        $bs_books = [];
        if ($res_bs_top && $res_bs_top->num_rows > 0) {
            while ($b = $res_bs_top->fetch_assoc()) {
                $bs_books[] = $b;
            }
        }
        ?>

        <?php if (!empty($bs_books)): ?>
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
        <?php endif; ?>
    </div>

</section>

<!-- Script xử lý chọn sách & chuyển Tab Best Sellers -->
<script>
function selectBestsellerBook(index, cardElem) {
    document.querySelectorAll('.bestseller-rank-card').forEach(c => c.classList.remove('active'));
    cardElem.classList.add('active');

    const ds = cardElem.dataset;
    
    document.getElementById('bsPreviewTitle').innerText = ds.title;
    document.getElementById('bsPreviewImg').src = ds.img;
    document.getElementById('bsPreviewSold').innerText = ds.sold;
    document.getElementById('bsPreviewAuthor').innerText = ds.author;
    document.getElementById('bsPreviewNxb').innerText = ds.nxb;
    document.getElementById('bsPreviewPrice').innerText = ds.price;
    document.getElementById('bsPreviewDesc').innerText = ds.desc;

    const linkImg = document.getElementById('bsPreviewLinkImg');
    const linkTitle = document.getElementById('bsPreviewLinkTitle');
    const linkBtn = document.getElementById('bsPreviewBtnLink');
    const detailUrl = 'chitiet.php?id=' + ds.id;

    if (linkImg) linkImg.href = detailUrl;
    if (linkTitle) linkTitle.href = detailUrl;
    if (linkBtn) linkBtn.href = detailUrl;

    const elOld = document.getElementById('bsPreviewOldPrice');
    const elDisc = document.getElementById('bsPreviewDiscount');

    if (ds.oldprice) {
        elOld.innerText = ds.oldprice;
        elOld.style.display = 'inline';
    } else {
        elOld.style.display = 'none';
    }

    if (ds.discount) {
        elDisc.innerText = ds.discount;
        elDisc.style.display = 'inline';
    } else {
        elDisc.style.display = 'none';
    }
}

function switchBestsellerTab(catId, tabElem) {
    document.querySelectorAll('.bestseller-tab-item').forEach(t => t.classList.remove('active'));
    tabElem.classList.add('active');

    let url = 'ajax/lay_bestseller_tab_ajax.php';
    if (catId > 0) url += '?theloai=' + catId;

    fetch(url)
        .then(r => r.text())
        .then(html => {
            document.getElementById('bestsellerContainer').innerHTML = html;
        });
}
</script>

<!-- 5. 💖 Sách Được Yêu Thích Nhất (Most Favorited) -->
<section id="sach-yeu-thich" class="book-section">
    <div class="section-header-row">
        <h3 class="section-title" style="border-left-color: #e84393;">💖 Sách Được Yêu Thích Nhất</h3>
        <a href="sach_tatca.php?sort=fav_desc" class="section-view-more" style="color: #e84393;">Xem tất cả <i class="fas fa-chevron-right" style="font-size: 11px;"></i></a>
    </div>
    <?php
    $sql_yeuthich = "SELECT s.*, COUNT(y.yeuthich_id) as tong_fav 
                     FROM sach s 
                     LEFT JOIN yeu_thich y ON s.sach_id = y.sach_id 
                     GROUP BY s.sach_id 
                     ORDER BY tong_fav DESC, s.daban DESC 
                     LIMIT 5";
    renderBookGrid($conn->query($sql_yeuthich));
    ?>
</section>

<!-- 6. 👀 Sách Xem Nhiều Nhất (Most Viewed) -->
<section id="sach-xem-nhieu" class="book-section">
    <div class="section-header-row">
        <h3 class="section-title" style="border-left-color: #2980b9;">👀 Sách Được Xem Nhiều Nhất</h3>
        <a href="sach_tatca.php?sort=price_desc" class="section-view-more" style="color: #2980b9;">Xem tất cả <i class="fas fa-chevron-right" style="font-size: 11px;"></i></a>
    </div>
    <?php
    $sql_xemnhieu = "SELECT * FROM sach ORDER BY luotxem DESC, sach_id DESC LIMIT 5";
    renderBookGrid($conn->query($sql_xemnhieu));
    ?>
</section>

<!-- 7. ✨ Sách Mới Cập Nhật (New Arrival) -->
<section id="sach-moi" class="book-section">
    <div class="section-header-row">
        <h3 class="section-title" style="border-left-color: #28a745;">✨ Sách Mới Cập Nhật</h3>
        <a href="sach_tatca.php" class="section-view-more" style="color: #28a745;">Xem tất cả <i class="fas fa-chevron-right" style="font-size: 11px;"></i></a>
    </div>
    <?php
    $sql_moinhat = "SELECT * FROM sach ORDER BY ngaytao DESC LIMIT 5";
    renderBookGrid($conn->query($sql_moinhat));
    ?>
</section>

<?php
// Gọi footer
include 'footer.php';
?>