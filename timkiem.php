<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Lấy từ khóa, thể loại, sắp xếp và lọc giá từ URL
$tukhoa = isset($_GET['tukhoa']) ? trim($_GET['tukhoa']) : '';
$theloai_filter = isset($_GET['theloai']) ? intval($_GET['theloai']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$loc_gia = isset($_GET['loc']) ? $_GET['loc'] : '';

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 24; // 24 sản phẩm mỗi trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Xây dựng câu lệnh kết nối bảng thể loại nếu có chọn lọc thể loại
$join_sql = "";
if ($theloai_filter > 0) {
    $join_sql = " JOIN sach_theloai st ON s.sach_id = st.sach_id";
}

// Xây dựng điều kiện tìm kiếm và lọc
$conditions = [];
if (!empty($tukhoa)) {
    $tukhoa_escaped = $conn->real_escape_string(mb_strtolower($tukhoa, 'UTF-8'));
    $conditions[] = "(LOWER(s.tensach) LIKE '%$tukhoa_escaped%' OR LOWER(s.tacgia) LIKE '%$tukhoa_escaped%')";
}

if ($theloai_filter > 0) {
    $conditions[] = "st.theloai_id = $theloai_filter";
}

if ($loc_gia == 'tren_100k') {
    $conditions[] = "(s.gia * (1 - s.phantram_giamgia/100)) > 100000";
} elseif ($loc_gia == 'duoi_100k') {
    $conditions[] = "(s.gia * (1 - s.phantram_giamgia/100)) <= 100000";
}

$where_sql = "";
if (!empty($conditions)) {
    $where_sql = " WHERE " . implode(" AND ", $conditions);
}

// 1. Đếm tổng số kết quả tìm kiếm được
$count_sql = "SELECT COUNT(DISTINCT s.sach_id) as total FROM sach s" . $join_sql . $where_sql;
$count_result = $conn->query($count_sql);
$total_row = $count_result->fetch_assoc();
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);

// 2. Lấy danh sách sản phẩm phân trang
$sql = "SELECT DISTINCT s.* FROM sach s" . $join_sql . $where_sql;

// Sắp xếp
if ($sort == 'price_asc') {
    $sql .= " ORDER BY (s.gia * (1 - s.phantram_giamgia/100)) ASC";
} elseif ($sort == 'price_desc') {
    $sql .= " ORDER BY (s.gia * (1 - s.phantram_giamgia/100)) DESC";
} elseif ($sort == 'bestseller') {
    $sql .= " ORDER BY s.daban DESC";
} else { 
    $sql .= " ORDER BY s.sach_id DESC";
}

$sql .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
$categories = $conn->query("SELECT * FROM the_loai");
?>

<!-- Bố cục trang tìm kiếm dạng Sidebar bên trái + Sản phẩm bên phải -->
<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px; display: flex; gap: 20px;">
    
    <!-- SIDEBAR BÊN TRÁI: DANH MỤC THỂ LOẠI (GIỮ LẠI TỪ KHÓA TÌM KIẾM) -->
    <aside style="width: 250px; flex-shrink: 0; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; height: fit-content;">
        <h4 style="font-size: 16px; color: #333; border-bottom: 2px solid #C92127; padding-bottom: 8px; margin-bottom: 15px;">NHÓM SẢN PHẨM</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 10px;">
                <!-- Khi bấm "Tất Cả Sản Phẩm" vẫn giữ lại từ khóa tìm kiếm -->
                <a href="timkiem.php?tukhoa=<?= urlencode($tukhoa) ?>" style="text-decoration: none; color: <?= ($theloai_filter == 0) ? '#C92127; font-weight: bold;' : '#333'; ?>; font-size: 14px;">Tất Cả Sản Phẩm</a>
            </li>
            <?php 
            while ($cat = $categories->fetch_assoc()): 
                // Tạo link giữ cả 'tukhoa' lẫn 'theloai'
                $link_theloai = "timkiem.php?tukhoa=" . urlencode($tukhoa) . "&theloai=" . $cat['theloai_id'];
            ?>
                <li style="margin-bottom: 10px;">
                    <a href="<?= $link_theloai ?>" style="text-decoration: none; color: <?= ($theloai_filter == $cat['theloai_id']) ? '#C92127; font-weight: bold;' : '#555'; ?>; font-size: 14px;">
                        <?= htmlspecialchars($cat['tentheloai']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </aside>

    <!-- NỘI DUNG CHÍNH BÊN PHẢI -->
    <main style="flex: 1;">
        
        <!-- Thanh Sắp Xếp và Thông Báo Kết Quả -->
        <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 16px; color: #333; margin: 0 0 5px 0;">Kết quả tìm kiếm cho: "<?= htmlspecialchars($tukhoa) ?>"</h3>
                <span style="font-size: 13px; color: #777;">Tìm thấy <b><?= $total_products ?></b> sản phẩm phù hợp</span>
            </div>
            
            <form method="GET" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <input type="hidden" name="tukhoa" value="<?= htmlspecialchars($tukhoa) ?>">
                <?php if ($theloai_filter > 0): ?>
                    <input type="hidden" name="theloai" value="<?= $theloai_filter ?>">
                <?php endif; ?>
                <?php if (!empty($loc_gia)): ?>
                    <input type="hidden" name="loc" value="<?= htmlspecialchars($loc_gia) ?>">
                <?php endif; ?>
                <label style="font-size: 13px; color: #666;">Sắp xếp:</label>
                <select name="sort" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                    <option value="newest" <?= ($sort == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="bestseller" <?= ($sort == 'bestseller') ? 'selected' : ''; ?>>Bán chạy nhất</option>
                    <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : ''; ?>>Giá: Thấp đến Cao</option>
                    <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : ''; ?>>Giá: Cao xuống Thấp</option>
                </select>
            </form>
        </div>

        <!-- Lưới Hiển Thị Sản Phẩm (4 cột) -->
        <div class="book-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
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

                    echo '
                    <div class="book-card">
                        <a href="chitiet.php?id='.$sach_id.'" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">';
                    
                    if ($phantram_giam > 0) {
                        echo '<span class="discount-badge">-'.$phantram_giam.'%</span>';
                    }

                    echo '
                            <div class="book-img">
                                <img src="'.$hinhanh.'" alt="'.htmlspecialchars($row['tensach']).'">
                            </div>
                            <div class="book-info">
                                <h4 class="book-title" title="'.htmlspecialchars($row['tensach']).'">'.htmlspecialchars($row['tensach']).'</h4>
                                <p class="book-author">'.htmlspecialchars($row['tacgia']).'</p>';
                    
                    if ($phantram_giam > 0) {
                        echo '
                                <div class="price-box">
                                    <span class="book-price">'.$hien_thi_gia_ban.'</span>
                                    <span class="old-price">'.$hien_thi_gia_goc.'</span>
                                </div>';
                    } else {
                        echo '
                                <div class="price-box">
                                    <span class="book-price">'.$hien_thi_gia_ban.'</span>
                                </div>';
                    }

                    echo '
                                <div class="sold-box">
                                    <span>Đã bán '.$daban.'</span>
                                </div>
                            </div>
                        </a>
                    </div>';
                }
            } else {
                echo "<p style='grid-column: 1 / -1; text-align: center; color: #777; padding: 50px; background: #fff; border-radius: 8px; border: 1px solid #eee;'>Không tìm thấy sản phẩm nào phù hợp với từ khóa <b>\"" . htmlspecialchars($tukhoa) . "\"</b>.</p>";
            }
            ?>
        </div>

        <!-- THANH PHÂN TRANG KẾT QUẢ TÌM KIẾM -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $query_params = $_GET;
                
                if ($page > 1) {
                    $query_params['page'] = $page - 1;
                    echo '<a href="timkiem.php?'.http_build_query($query_params).'">&laquo; Trang trước</a>';
                }

                for ($i = 1; $i <= $total_pages; $i++) {
                    $query_params['page'] = $i;
                    $active_class = ($i == $page) ? 'active' : '';
                    echo '<a href="timkiem.php?'.http_build_query($query_params).'" class="'.$active_class.'">'.$i.'</a>';
                }

                if ($page < $total_pages) {
                    $query_params['page'] = $page + 1;
                    echo '<a href="timkiem.php?'.http_build_query($query_params).'">Trang sau &raquo;</a>';
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