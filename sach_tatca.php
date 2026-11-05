<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Lấy giá trị từ URL
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$theloai_filter = isset($_GET['theloai']) ? intval($_GET['theloai']) : 0;
$loc_gia = isset($_GET['loc']) ? $_GET['loc'] : '';

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 24; // 24 sản phẩm mỗi trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 1. Lấy thông tin tên và mô tả của thể loại nếu có chọn lọc thể loại
$current_category = null;
if ($theloai_filter > 0) {
    $cat_res = $conn->query("SELECT * FROM the_loai WHERE theloai_id = $theloai_filter LIMIT 1");
    if ($cat_res && $cat_res->num_rows > 0) {
        $current_category = $cat_res->fetch_assoc();
    }
}

// 2. Xây dựng điều kiện lọc chung
$conditions = [];
if ($theloai_filter > 0) {
    $conditions[] = "st.theloai_id = $theloai_filter";
}
if ($loc_gia == 'tren_100k') {
    $conditions[] = "(s.gia * (1 - s.phantram_giamgia/100)) > 100000";
} elseif ($loc_gia == 'duoi_100k') {
    $conditions[] = "(s.gia * (1 - s.phantram_giamgia/100)) <= 100000";
}

$where_sql = "";
$join_sql = "";
if ($theloai_filter > 0) {
    $join_sql = " JOIN sach_theloai st ON s.sach_id = st.sach_id";
}
if (!empty($conditions)) {
    $where_sql = " WHERE " . implode(" AND ", $conditions);
}

// 3. Đếm tổng số sản phẩm để tính tổng số trang
$count_sql = "SELECT COUNT(DISTINCT s.sach_id) as total FROM sach s" . $join_sql . $where_sql;
$count_result = $conn->query($count_sql);
$total_row = $count_result->fetch_assoc();
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);

// 4. Xây dựng câu lệnh SQL lấy dữ liệu có giới hạn (LIMIT & OFFSET)
$sql = "SELECT DISTINCT s.* FROM sach s" . $join_sql . $where_sql;

// Sắp xếp
if ($sort == 'price_asc') {
    $sql .= " ORDER BY (s.gia * (1 - s.phantram_giamgia/100)) ASC";
} elseif ($sort == 'price_desc') {
    $sql .= " ORDER BY (s.gia * (1 - s.phantram_giamgia/100)) DESC";
} elseif ($sort == 'bestseller' || $sort == 'sold_desc') {
    $sql .= " ORDER BY s.daban DESC";
} elseif ($sort == 'views_desc') {
    $sql .= " ORDER BY s.luotxem DESC";
} elseif ($sort == 'fav_desc') {
    $sql .= " ORDER BY (SELECT COUNT(*) FROM yeu_thich y WHERE y.sach_id = s.sach_id) DESC, s.daban DESC";
} else { 
    $sql .= " ORDER BY s.sach_id DESC";
}

$sql .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
$categories = $conn->query("SELECT * FROM the_loai");
?>

<!-- Bố cục trang danh sách -->
<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px; display: flex; gap: 20px;">
    
    <!-- SIDEBAR BÊN TRÁI -->
    <aside style="width: 250px; flex-shrink: 0; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; height: fit-content;">
        <h4 style="font-size: 16px; color: #333; border-bottom: 2px solid #C92127; padding-bottom: 8px; margin-bottom: 15px;">NHÓM SẢN PHẨM</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 10px;">
                <a href="sach_tatca.php" style="text-decoration: none; color: <?= ($theloai_filter == 0 && empty($loc_gia)) ? '#C92127; font-weight: bold;' : '#333'; ?>; font-size: 14px;">Tất Cả Sản Phẩm</a>
            </li>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <li style="margin-bottom: 10px;">
                    <a href="sach_tatca.php?theloai=<?= $cat['theloai_id'] ?>" style="text-decoration: none; color: <?= ($theloai_filter == $cat['theloai_id']) ? '#C92127; font-weight: bold;' : '#555'; ?>; font-size: 14px;">
                        <?= htmlspecialchars($cat['tentheloai']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </aside>

    <!-- NỘI DUNG CHÍNH BÊN PHẢI -->
    <main style="flex: 1;">
        
        <!-- Thanh Sắp Xếp (Sort Bar) -->
        <div style="background: #fff; padding: 12px 15px; border-radius: 8px; border: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 14px; color: #555; font-weight: bold;">📚 Danh Sách Đầu Sách (<?= $total_products ?> sản phẩm)</span>
            <form method="GET" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <?php if ($theloai_filter > 0): ?>
                    <input type="hidden" name="theloai" value="<?= $theloai_filter ?>">
                <?php endif; ?>
                <?php if (!empty($loc_gia)): ?>
                    <input type="hidden" name="loc" value="<?= htmlspecialchars($loc_gia) ?>">
                <?php endif; ?>
                <label style="font-size: 13px; color: #666;">Sắp xếp theo:</label>
                <select name="sort" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                    <option value="newest" <?= ($sort == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="bestseller" <?= ($sort == 'bestseller') ? 'selected' : ''; ?>>Bán chạy nhất</option>
                    <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : ''; ?>>Giá: Thấp đến Cao</option>
                    <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : ''; ?>>Giá: Cao xuống Thấp</option>
                </select>
            </form>
        </div>

        <!-- MÔ TẢ THỂ LOẠI -->
        <?php if ($current_category): ?>
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 20px;">
                <h2 style="font-size: 20px; color: #333; margin: 0 0 8px 0;"><?= htmlspecialchars($current_category['tentheloai']) ?></h2>
                <?php if (!empty($current_category['mota'])): ?>
                    <p style="font-size: 14px; color: #666; margin: 0; line-height: 1.5;"><?= nl2br(htmlspecialchars($current_category['mota'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Lưới Hiển Thị Sản Phẩm (Định dạng chuẩn Fahasa) -->
        <div class="book-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $gia_goc = $row['gia'];
                    $phantram_giam = intval($row['phantram_giamgia']);
                    
                    if ($phantram_giam > 0) {
                        $gia_ban = $gia_goc * (1 - $phantram_giam / 100);
                    } else {
                        $gia_ban = $gia_goc;
                    }

                    $hien_thi_gia_ban = number_format($gia_ban, 0, ',', '.') . ' đ';
                    $hien_thi_gia_goc = number_format($gia_goc, 0, ',', '.') . ' đ';
                    
                    $hinhanh = "ADMIN/" . $row['hinhanh']; 
                    $daban = intval($row['daban']);
                    $sach_id = $row['sach_id'];
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
            } else {
                echo "<p style='grid-column: 1 / -1; text-align: center; color: #777; padding: 40px;'>Không tìm thấy cuốn sách nào phù hợp.</p>";
            }
            ?>
        </div>

        <!-- THANH PHÂN TRANG (PAGINATION) -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                // Tạo chuỗi query giữ lại các bộ lọc (thể loại, sắp xếp, lọc giá) khi chuyển trang
                $query_params = $_GET;
                
                // Nút chuyển về trang trước
                if ($page > 1) {
                    $query_params['page'] = $page - 1;
                    echo '<a href="sach_tatca.php?'.http_build_query($query_params).'">&laquo; Trang trước</a>';
                }

                // Vòng lặp hiển thị các số trang
                for ($i = 1; $i <= $total_pages; $i++) {
                    $query_params['page'] = $i;
                    $active_class = ($i == $page) ? 'active' : '';
                    echo '<a href="sach_tatca.php?'.http_build_query($query_params).'" class="'.$active_class.'">'.$i.'</a>';
                }

                // Nút chuyển tới trang sau
                if ($page < $total_pages) {
                    $query_params['page'] = $page + 1;
                    echo '<a href="sach_tatca.php?'.http_build_query($query_params).'">Trang sau &raquo;</a>';
                }
                ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php
$conn->close();
include 'footer.php';
?>