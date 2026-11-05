<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['nguoidung_id'])) {
    echo '
    <div style="max-width: 800px; margin: 50px auto; text-align: center; background: #fff; padding: 40px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <i class="fas fa-box-open" style="font-size: 60px; color: #0F62FE; margin-bottom: 20px;"></i>
        <h2 style="font-size: 22px; color: #333; margin-bottom: 10px;">Vui lòng đăng nhập để xem đơn hàng!</h2>
        <p style="color: #666; margin-bottom: 25px;">Đăng nhập để theo dõi trạng thái vận chuyển và lịch sử mua hàng.</p>
        <a href="dangnhap.php" class="btn-primary" style="background: #C92127; border-radius: 25px; padding: 10px 30px; font-weight: bold;">Đăng nhập ngay</a>
    </div>';
    include 'footer.php';
    exit();
}

$nguoidung_id = intval($_SESSION['nguoidung_id']);

// Xử lý HỦY ĐƠN HÀNG (khi đơn hàng đang ở trạng thái 'choduyet')
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $cancel_id = intval($_GET['id']);
    $conn->query("UPDATE don_hang SET trangthai = 'dahuy' WHERE donhang_id = $cancel_id AND nguoidung_id = $nguoidung_id AND trangthai = 'choduyet'");
    $_SESSION['toast'] = [
        'type' => 'info',
        'message' => 'Đã hủy đơn hàng #' . $cancel_id . ' thành công!'
    ];
    header("Location: donhang_cuatoi.php");
    exit();
}

// 1. Nhận tham số Tìm kiếm, Bộ lọc & Phân trang
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10; // 10 đơn hàng mỗi trang theo yêu cầu

// 2. Xây dựng truy vấn SQL tìm kiếm
$where_clauses = ["d.nguoidung_id = $nguoidung_id"];

if (!empty($status)) {
    $st_esc = $conn->real_escape_string($status);
    $where_clauses[] = "d.trangthai = '$st_esc'";
}

if (!empty($search)) {
    $s_esc = $conn->real_escape_string($search);
    $clean_id = preg_replace('/[^0-9]/', '', $search);
    
    $or_subs = [
        "d.ten_nguoinhan LIKE '%$s_esc%'",
        "d.sdt_nguoinhan LIKE '%$s_esc%'",
        "d.diachi_giaohang LIKE '%$s_esc%'"
    ];
    if (!empty($clean_id)) {
        $or_subs[] = "d.donhang_id = " . intval($clean_id);
    }
    // Tìm theo tên sách trong đơn hàng
    $or_subs[] = "EXISTS (
        SELECT 1 FROM chi_tiet_don_hang ct 
        JOIN sach s ON ct.sach_id = s.sach_id 
        WHERE ct.donhang_id = d.donhang_id AND s.tensach LIKE '%$s_esc%'
    )";
    
    $where_clauses[] = "(" . implode(" OR ", $or_subs) . ")";
}

$where_sql = implode(" AND ", $where_clauses);

// 3. Tính tổng số đơn hàng & Phân trang
$count_res = $conn->query("SELECT COUNT(DISTINCT d.donhang_id) as total FROM don_hang d WHERE $where_sql");
$total_items = ($count_res && $row_c = $count_res->fetch_assoc()) ? intval($row_c['total']) : 0;

$total_pages = max(1, ceil($total_items / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

// 4. Lấy danh sách đơn hàng đã phân trang
$sql_dh = "SELECT d.*, v.ma as ma_voucher 
           FROM don_hang d 
           LEFT JOIN voucher v ON d.voucher_id = v.voucher_id 
           WHERE $where_sql 
           ORDER BY d.donhang_id DESC 
           LIMIT $limit OFFSET $offset";
$res_dh = $conn->query($sql_dh);

// Ánh xạ màu sắc & chữ hiển thị của Trạng Thái
$status_map = [
    'choduyet' => ['label' => 'Chờ duyệt', 'color' => '#856404', 'bg' => '#fff3cd'],
    'dangxuly' => ['label' => 'Đang xử lý', 'color' => '#004085', 'bg' => '#cce5ff'],
    'danggiao' => ['label' => 'Đang giao hàng', 'color' => '#0c5460', 'bg' => '#d1ecf1'],
    'hoanthanh' => ['label' => 'Hoàn thành', 'color' => '#155724', 'bg' => '#d4edda'],
    'dahuy' => ['label' => 'Đã hủy', 'color' => '#721c24', 'bg' => '#f8d7da']
];
?>

<div style="max-width: 1100px; margin: 25px auto; padding: 0 15px;">
    
    <!-- HEADER TRANG -->
    <div style="background: #fff; padding: 18px 25px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <h2 style="font-size: 20px; color: #333; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-box" style="color: #C92127;"></i> Lịch Sử Đơn Hàng Của Tôi
            <span style="font-size: 14px; font-weight: normal; color: #777;">(<?= $total_items ?> đơn hàng)</span>
        </h2>
        <a href="sach_tatca.php" style="color: #C92127; text-decoration: none; font-size: 14px; font-weight: 600;">Tiếp tục mua sắm &gt;</a>
    </div>

    <!-- KHUNG TÌM KIẾM VÀ BỘ LỌC ĐƠN HÀNG -->
    <div style="background: #fff; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <form method="GET" action="donhang_cuatoi.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            
            <!-- Input Tìm kiếm -->
            <div style="flex: 1; min-width: 250px; position: relative;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Tìm theo mã đơn (#DH-X), tên người nhận, SĐT, tên sách..." 
                       style="width: 100%; padding: 9px 12px 9px 36px; border: 1px solid #ccc; border-radius: 8px; font-size: 13.5px; outline: none;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888;"></i>
            </div>

            <!-- Dropdown Trạng thái -->
            <div style="width: 180px;">
                <select name="status" style="width: 100%; padding: 9px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff;">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="choduyet" <?= $status == 'choduyet' ? 'selected' : '' ?>>Chờ duyệt</option>
                    <option value="dangxuly" <?= $status == 'dangxuly' ? 'selected' : '' ?>>Đang xử lý</option>
                    <option value="danggiao" <?= $status == 'danggiao' ? 'selected' : '' ?>>Đang giao hàng</option>
                    <option value="hoanthanh" <?= $status == 'hoanthanh' ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="dahuy" <?= $status == 'dahuy' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>

            <!-- Nút Tìm kiếm & Reset -->
            <button type="submit" style="background: #C92127; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-filter"></i> Lọc
            </button>

            <?php if (!empty($search) || !empty($status)): ?>
                <a href="donhang_cuatoi.php" style="background: #e9ecef; color: #495057; text-decoration: none; padding: 9px 15px; border-radius: 8px; font-size: 13.5px; font-weight: bold; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-redo"></i> Xóa lọc
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- DANH SÁCH ĐƠN HÀNG GỌN GÀNG (KHÔNG HIỆN HÌNH Ở NGOÀI) -->
    <?php if ($res_dh && $res_dh->num_rows > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php while ($dh = $res_dh->fetch_assoc()): 
                $dh_id = $dh['donhang_id'];
                $st_info = $status_map[$dh['trangthai']] ?? ['label' => $dh['trangthai'], 'color' => '#333', 'bg' => '#eee'];
                
                // Lấy tóm tắt sản phẩm trong đơn hàng
                $res_ct_sum = $conn->query("SELECT c.soluong, s.tensach FROM chi_tiet_don_hang c JOIN sach s ON c.sach_id = s.sach_id WHERE c.donhang_id = $dh_id");
                $book_names = [];
                $total_qty = 0;
                if ($res_ct_sum && $res_ct_sum->num_rows > 0) {
                    while ($item_sum = $res_ct_sum->fetch_assoc()) {
                        $book_names[] = $item_sum['tensach'];
                        $total_qty += intval($item_sum['soluong']);
                    }
                }
                $summary_text = implode(', ', $book_names);
            ?>
                <div style="background: #fff; border-radius: 10px; border: 1px solid #e5e5e5; padding: 14px 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: 0.2s;">
                    
                    <!-- Dòng 1: Mã đơn, Thời gian, Trạng thái & Hủy đơn -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f2f2f2; padding-bottom: 10px; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 15px; font-weight: bold; color: #333;">Mã đơn: <strong style="color: #C92127;">#DH-<?= $dh_id ?></strong></span>
                            <span style="font-size: 12.5px; color: #888;"><i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($dh['ngaydat'])) ?></span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="background: <?= $st_info['bg'] ?>; color: <?= $st_info['color'] ?>; padding: 3px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                <?= $st_info['label'] ?>
                            </span>

                            <?php if ($dh['trangthai'] == 'choduyet'): ?>
                                <a href="donhang_cuatoi.php?action=cancel&id=<?= $dh_id ?>" onclick="return confirmAction(event, 'Bạn có chắc muốn hủy đơn hàng này?', 'Xác nhận hủy đơn', 'Xác nhận hủy');" style="color: #dc3545; font-size: 12.5px; text-decoration: underline; font-weight: 500;">
                                    Hủy đơn
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Dòng 2: Thông tin Tóm tắt Sản phẩm & Người nhận -->
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: #444; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                        <div style="flex: 1; min-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <i class="fas fa-book" style="color: #888; margin-right: 5px;"></i>
                            <span style="font-weight: 600; color: #333;"><?= htmlspecialchars($summary_text) ?></span>
                            <span style="color: #777; font-size: 12.5px;">(Tổng <?= $total_qty ?> sản phẩm)</span>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <span><i class="far fa-user" style="color: #888;"></i> <b><?= htmlspecialchars($dh['ten_nguoinhan']) ?></b> (<?= htmlspecialchars($dh['sdt_nguoinhan']) ?>)</span>
                            <span style="margin-left: 10px;"><i class="far fa-credit-card" style="color: #888;"></i> <?= htmlspecialchars($dh['phuongthuc_thanhtoan']) ?></span>
                        </div>
                    </div>

                    <!-- Dòng 3: Tổng tiền & Nút xem chi tiết -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #f2f2f2; padding-top: 8px;">
                        <div style="font-size: 14px; color: #333;">
                            Tổng thanh toán: <strong style="color: #C92127; font-size: 17px; margin-left: 4px;"><?= number_format($dh['tongtien'], 0, ',', '.') ?> đ</strong>
                        </div>
                        <button type="button" onclick="viewOrderDetail(<?= $dh_id ?>)" style="background: #C92127; color: white; border: none; padding: 6px 16px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: 0.2s;">
                            <i class="fas fa-eye"></i> Chi Tiết Đơn
                        </button>
                    </div>

                </div>
            <?php endwhile; ?>
        </div>

        <!-- PHÂN TRANG (PAGINATION) -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 6px; margin-top: 25px; flex-wrap: wrap;">
                
                <!-- Trang trước -->
                <?php if ($page > 1): ?>
                    <a href="donhang_cuatoi.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $page - 1 ?>" 
                       style="padding: 6px 12px; border: 1px solid #ddd; background: #fff; color: #333; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;">&laquo; Trước</a>
                <?php endif; ?>

                <!-- Các số trang -->
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <?php if ($p == $page): ?>
                        <span style="padding: 6px 12px; background: #C92127; color: #fff; border-radius: 6px; font-size: 13px; font-weight: bold; border: 1px solid #C92127;"><?= $p ?></span>
                    <?php else: ?>
                        <a href="donhang_cuatoi.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $p ?>" 
                           style="padding: 6px 12px; border: 1px solid #ddd; background: #fff; color: #333; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Trang sau -->
                <?php if ($page < $total_pages): ?>
                    <a href="donhang_cuatoi.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $page + 1 ?>" 
                       style="padding: 6px 12px; border: 1px solid #ddd; background: #fff; color: #333; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;">Sau &raquo;</a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    <?php else: ?>
        <div style="background: #fff; padding: 50px 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <i class="fas fa-box-open" style="font-size: 50px; color: #ddd; margin-bottom: 15px;"></i>
            <p style="font-size: 16px; color: #666; margin-bottom: 20px;">
                <?= (!empty($search) || !empty($status)) ? 'Không tìm thấy đơn hàng nào phù hợp với bộ lọc!' : 'Bạn chưa có đơn hàng nào!' ?>
            </p>
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="donhang_cuatoi.php" class="btn-primary" style="background: #6c757d; border-radius: 25px; padding: 9px 25px; font-weight: bold; margin-right: 10px;">Xem tất cả đơn hàng</a>
            <?php endif; ?>
            <a href="sach_tatca.php" class="btn-primary" style="background: #C92127; border-radius: 25px; padding: 9px 25px; font-weight: bold;">Mua sắm ngay</a>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL POPUP CHI TIẾT ĐƠN HÀNG -->
<div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 15px;">
    <div style="background: #fff; border-radius: 12px; width: 100%; max-width: 650px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
        
        <!-- Header Modal -->
        <div style="background: #C92127; padding: 15px 20px; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-receipt"></i> CHI TIẾT ĐƠN HÀNG
            </h3>
            <button type="button" onclick="closeOrderModal()" style="background: none; border: none; color: #fff; font-size: 22px; cursor: pointer;">&times;</button>
        </div>

        <!-- Body Modal -->
        <div id="orderModalBody" style="padding: 20px; max-height: 480px; overflow-y: auto;">
            <div style="text-align: center; padding: 30px; color: #666;"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Đang tải dữ liệu...</div>
        </div>

        <!-- Footer Modal -->
        <div style="background: #f8f9fa; padding: 12px 20px; text-align: right; border-top: 1px solid #eee;">
            <button type="button" onclick="closeOrderModal()" style="background: #6c757d; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer;">Đóng</button>
        </div>

    </div>
</div>

<script>
function viewOrderDetail(donhangId) {
    const modal = document.getElementById('orderModal');
    const modalBody = document.getElementById('orderModalBody');
    
    modalBody.innerHTML = '<div style="text-align: center; padding: 30px; color: #666;"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Đang tải chi tiết đơn hàng...</div>';
    modal.style.display = 'flex';

    fetch('ajax/lay_chitiet_donhang_ajax.php?id=' + donhangId)
        .then(r => r.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(err => {
            modalBody.innerHTML = '<p style="color: red; text-align: center;">Không thể tải dữ liệu đơn hàng!</p>';
        });
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}
</script>

<?php
$conn->close();
include 'footer.php';
?>
