<?php
/**
 * Quản lý Đơn Hàng (Orders Management)
 * Bảng: don_hang
 */

require_once 'config/connect.php';

// 1. Logic xử lý trạng thái đơn hàng & Tự động Trừ / Hoàn Kho
$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Lấy trạng thái hiện tại của đơn hàng
    $dh_curr = $conn->query("SELECT trangthai FROM don_hang WHERE donhang_id = $id")->fetch_assoc();
    $old_st = $dh_curr['trangthai'] ?? '';

    // Không cho phép thao tác nếu đơn hàng đã Hoàn thành hoặc Đã hủy
    if ($old_st == 'hoanthanh' || $old_st == 'dahuy') {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Không thể thao tác',
            'message' => 'Đơn hàng đã Hoàn thành hoặc Đã hủy, không thể thay đổi trạng thái nữa!'
        ];
        header("Location: donhang.php");
        exit();
    }

    if (in_array($action, ['approve', 'shipping', 'complete'])) {
        $new_st = 'dangxuly';
        if ($action == 'shipping') $new_st = 'danggiao';
        if ($action == 'complete') $new_st = 'hoanthanh';

        // Nếu đơn hàng đang ở trạng thái 'choduyet' (chưa trừ kho trước đây) -> kiểm tra và trừ kho
        if ($old_st == 'choduyet') {
            // Kiểm tra số lượng tồn trong bảng kho
            $details = $conn->query("SELECT c.*, s.tensach, s.kho_id, k.soluong as tonkho 
                                     FROM chi_tiet_don_hang c 
                                     JOIN sach s ON c.sach_id = s.sach_id 
                                     LEFT JOIN kho k ON s.kho_id = k.kho_id 
                                     WHERE c.donhang_id = $id");
            $out_of_stock = [];
            
            if ($details && $details->num_rows > 0) {
                while ($item = $details->fetch_assoc()) {
                    if (intval($item['tonkho']) < intval($item['soluong'])) {
                        $out_of_stock[] = $item['tensach'] . " (Kho còn: " . intval($item['tonkho']) . ", Đơn đặt: " . intval($item['soluong']) . ")";
                    }
                }
            }

            if (!empty($out_of_stock)) {
                $err_msg = "Không thể duyệt đơn #" . $id . "! Kho không đủ hàng: " . implode(", ", $out_of_stock);
                $_SESSION['toast'] = [
                    'type' => 'error',
                    'title' => 'Kho không đủ hàng',
                    'message' => $err_msg
                ];
                header("Location: donhang.php");
                exit();
            }

            // Trừ số lượng tồn kho trong bảng `kho`
            $details = $conn->query("SELECT c.*, s.kho_id FROM chi_tiet_don_hang c JOIN sach s ON c.sach_id = s.sach_id WHERE c.donhang_id = $id");
            while ($item = $details->fetch_assoc()) {
                $k_id = intval($item['kho_id']);
                $sl = intval($item['soluong']);
                if ($k_id > 0) {
                    $conn->query("UPDATE kho SET soluong = GREATEST(soluong - $sl, 0) WHERE kho_id = $k_id");
                }
            }
        }

        // Cập nhật trạng thái đơn hàng mới
        $conn->query("UPDATE don_hang SET trangthai='$new_st' WHERE donhang_id=$id");

        // Nếu chuyển sang Hoàn thành (và chưa từng hoàn thành trước đó) -> Cộng lượt bán sách & ghi nhận Doanh Thu
        if ($action == 'complete' && $old_st != 'hoanthanh') {
            $details = $conn->query("SELECT c.*, s.tensach, s.kho_id FROM chi_tiet_don_hang c JOIN sach s ON c.sach_id = s.sach_id WHERE c.donhang_id = $id");
            if ($details && $details->num_rows > 0) {
                while ($item = $details->fetch_assoc()) {
                    $s_id = intval($item['sach_id']);
                    $k_id = intval($item['kho_id']);
                    $sl = intval($item['soluong']);
                    $gia_ban = floatval($item['gia']);
                    $tensach = $conn->real_escape_string($item['tensach']);

                    $kho_info = $conn->query("SELECT gianhap FROM kho WHERE kho_id = $k_id")->fetch_assoc();
                    $gia_nhap = $kho_info['gianhap'] ?? 0;

                    $conn->query("UPDATE sach SET daban = daban + $sl WHERE sach_id = $s_id");
                    $conn->query("INSERT INTO doanh_thu (kho_id, sach_id, tensach, soluongban, gianhap, giaban, ngayban) VALUES ($k_id, $s_id, '$tensach', $sl, $gia_nhap, $gia_ban, CURDATE())");
                }
            }
        }

        // Thông báo Toast thành công tương ứng
        if ($action == 'approve') {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Duyệt đơn thành công',
                'message' => '✔ Đã duyệt đơn hàng #' . $id . '! Số lượng trong kho đã tự động trừ theo đơn.'
            ];
        } elseif ($action == 'shipping') {
            $_SESSION['toast'] = [
                'type' => 'info',
                'title' => 'Đang giao hàng',
                'message' => '✔ Đã chuyển trạng thái đơn hàng #' . $id . ' sang Đang Giao Hàng!'
            ];
        } elseif ($action == 'complete') {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Hoàn thành đơn hàng',
                'message' => '✔ Đã hoàn thành đơn hàng #' . $id . '! Tự động cộng lượt bán và ghi nhận doanh thu.'
            ];
        }

        header("Location: donhang.php");
        exit();

    } elseif ($action == 'cancel') {
        // Nếu đơn hàng đã được duyệt trước đó (đã bị trừ kho) mà bây giờ bị Hủy -> Hoàn lại số lượng vào kho
        if ($old_st != 'choduyet' && $old_st != 'dahuy') {
            $details = $conn->query("SELECT c.*, s.kho_id FROM chi_tiet_don_hang c JOIN sach s ON c.sach_id = s.sach_id WHERE c.donhang_id = $id");
            while ($item = $details->fetch_assoc()) {
                $k_id = intval($item['kho_id']);
                $sl = intval($item['soluong']);
                if ($k_id > 0) {
                    $conn->query("UPDATE kho SET soluong = soluong + $sl WHERE kho_id = $k_id");
                }
            }
        }

        $conn->query("UPDATE don_hang SET trangthai='dahuy' WHERE donhang_id=$id");

        $_SESSION['toast'] = [
            'type' => 'warning',
            'title' => 'Hủy đơn hàng',
            'message' => '✖ Đã hủy đơn hàng #' . $id . ' thành công! (Số lượng đã hoàn lại vào kho nếu đã duyệt).'
        ];

        header("Location: donhang.php");
        exit();
    }
}

// 2. Lấy bộ lọc Tháng/Năm, Tìm kiếm, Trạng thái & Phân trang
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10; // 10 đơn hàng/trang

// 3. Xây dựng câu lệnh SQL có bộ lọc
$where_clauses = ["1=1"];
if ($filter_month > 0) $where_clauses[] = "MONTH(d.ngaydat) = $filter_month";
if ($filter_year > 0) $where_clauses[] = "YEAR(d.ngaydat) = $filter_year";
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
        "nd.taikhoan LIKE '%$s_esc%'",
        "d.diachi_giaohang LIKE '%$s_esc%'"
    ];
    if (!empty($clean_id)) {
        $or_subs[] = "d.donhang_id = " . intval($clean_id);
    }
    $or_subs[] = "EXISTS (
        SELECT 1 FROM chi_tiet_don_hang ct 
        JOIN sach s ON ct.sach_id = s.sach_id 
        WHERE ct.donhang_id = d.donhang_id AND s.tensach LIKE '%$s_esc%'
    )";
    $where_clauses[] = "(" . implode(" OR ", $or_subs) . ")";
}
$where_sql = implode(" AND ", $where_clauses);

// 4. Tính tổng số record & phân trang
$count_res = $conn->query("SELECT COUNT(DISTINCT d.donhang_id) as total FROM don_hang d LEFT JOIN nguoi_dung nd ON d.nguoidung_id = nd.nguoidung_id WHERE $where_sql");
$total_items = ($count_res && $row_c = $count_res->fetch_assoc()) ? intval($row_c['total']) : 0;
$total_pages = max(1, ceil($total_items / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$sql = "SELECT d.*, nd.taikhoan, nd.sodienthoai_email 
        FROM don_hang d 
        LEFT JOIN nguoi_dung nd ON d.nguoidung_id = nd.nguoidung_id 
        WHERE $where_sql 
        ORDER BY d.donhang_id DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$page_title = "Quản lý Đơn Hàng - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin: 30px 0;">📋 Quản lý Đơn Hàng</h2>

    <!-- BỘ LỌC VÀ TÌM KIẾM ADMIN -->
    <div style="background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; background: none; padding: 0; box-shadow: none; max-width: 100%;">
            
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Tìm theo mã đơn, khách hàng, SĐT, tên sách..." 
                   style="flex: 1; min-width: 220px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 13.5px; outline: none;">

            <select name="status" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 13.5px; outline: none;">
                <option value="">-- Trạng thái --</option>
                <option value="choduyet" <?= $status == 'choduyet' ? 'selected' : '' ?>>Chờ Duyệt</option>
                <option value="dangxuly" <?= $status == 'dangxuly' ? 'selected' : '' ?>>Đang Xử Lý</option>
                <option value="danggiao" <?= $status == 'danggiao' ? 'selected' : '' ?>>Đang Giao</option>
                <option value="hoanthanh" <?= $status == 'hoanthanh' ? 'selected' : '' ?>>Hoàn Thành</option>
                <option value="dahuy" <?= $status == 'dahuy' ? 'selected' : '' ?>>Đã Hủy</option>
            </select>

            <select name="month" style="padding: 8px; width: 140px; border: 1px solid #ccc; border-radius: 6px; font-size: 13.5px;">
                <option value="0">-- Tháng --</option>
                <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $filter_month == $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                <?php endfor; ?>
            </select>

            <select name="year" style="padding: 8px; width: 130px; border: 1px solid #ccc; border-radius: 6px; font-size: 13.5px;">
                <option value="0">-- Năm --</option>
                <?php for($y=2025; $y<=date('Y'); $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">🔍 Tìm & Lọc</button>
            <a href="donhang.php" class="btn btn-info" style="padding: 8px 16px;">🔄 Reset</a>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Khách Hàng</th>
                    <th>Tổng Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Đặt</th>
                    <th style="text-align: center;">Chi Tiết</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $trangthai = $row['trangthai'];
                        $badge_class = 'badge-secondary';
                        $trangthai_str = $trangthai;

                        if ($trangthai === 'choduyet') { $badge_class = 'badge-warning'; $trangthai_str = 'Chờ Duyệt'; }
                        elseif ($trangthai === 'dangxuly') { $badge_class = 'badge-info'; $trangthai_str = 'Đang Xử Lý'; }
                        elseif ($trangthai === 'danggiao') { $badge_class = 'badge-warning'; $trangthai_str = 'Đang Giao'; }
                        elseif ($trangthai === 'hoanthanh') { $badge_class = 'badge-success'; $trangthai_str = 'Hoàn Thành'; }
                        elseif ($trangthai === 'dahuy') { $badge_class = 'badge-danger'; $trangthai_str = 'Đã Hủy'; }

                        $ten_nguoinhan = !empty($row['ten_nguoinhan']) ? htmlspecialchars($row['ten_nguoinhan']) : htmlspecialchars($row['taikhoan'] ?? 'Khách lẻ');

                        echo "<tr>
                            <td><strong>#{$row['donhang_id']}</strong></td>
                            <td>
                                <strong>{$ten_nguoinhan}</strong><br>
                                <small style='color:#7f8c8d;'>TK: " . htmlspecialchars($row['taikhoan'] ?? 'khach') . "</small>
                            </td>
                            <td><strong style='color:#e74c3c;'>" . number_format($row['tongtien'], 0, ',', '.') . " đ</strong></td>
                            <td><span class='badge {$badge_class}'>{$trangthai_str}</span></td>
                            <td><small>{$row['ngaydat']}</small></td>
                            <td style='text-align:center;'>
                                <button type='button' class='btn btn-info btn-small' title='Xem chi tiết đơn hàng (SĐT, Địa chỉ, Sản phẩm)' onclick='viewAdminOrderDetail({$row['donhang_id']})' style='padding: 6px 10px; font-size: 14px;'>👁️</button>
                                <a href='in_donhang.php?id={$row['donhang_id']}&from=admin' target='_blank' class='btn btn-secondary btn-small' title='In hóa đơn đơn hàng' style='padding: 6px 10px; font-size: 14px; background: #6c757d; color: white; border-radius: 6px; text-decoration: none; display: inline-block; margin-left: 4px;'>🖨️</a>
                            </td>
                            <td>";
                        
                        if ($trangthai === 'hoanthanh' || $trangthai === 'dahuy') {
                            echo "<span style='color:#888; font-size:12.5px; font-weight:bold;'><i class='fas fa-lock'></i> Đã hoàn tất</span>";
                        } else {
                            echo "<select onchange='changeOrderStatus({$row['donhang_id']}, this.value)' style='padding: 6px 8px; border-radius: 6px; border: 1px solid #0F62FE; font-weight: bold; font-size: 13px; color: #0F62FE; background: #f0f4ff; cursor: pointer; outline: none;'>
                                <option value=''>-- Chọn hành động --</option>";
                            if ($trangthai === 'choduyet') {
                                echo "<option value='approve'>✅ Duyệt đơn hàng</option>";
                            }
                            if ($trangthai != 'danggiao') {
                                echo "<option value='shipping'>🚚 Giao hàng</option>";
                            }
                            echo "<option value='complete'>📦 Hoàn thành</option>
                                  <option value='cancel'>❌ Hủy đơn hàng</option>
                            </select>";
                        }
                        
                        echo "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center;'>Chưa có đơn hàng nào trong CSDL</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- PHÂN TRANG ADMIN (PAGINATION) -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; align-items: center; gap: 6px; margin: 25px 0;">
            <?php if ($page > 1): ?>
                <a href="donhang.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&month=<?= $filter_month ?>&year=<?= $filter_year ?>&page=<?= $page - 1 ?>" class="btn btn-secondary btn-small">&laquo; Trước</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p == $page): ?>
                    <span class="btn btn-primary btn-small" style="font-weight: bold;"><?= $p ?></span>
                <?php else: ?>
                    <a href="donhang.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&month=<?= $filter_month ?>&year=<?= $filter_year ?>&page=<?= $p ?>" class="btn btn-secondary btn-small"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="donhang.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&month=<?= $filter_month ?>&year=<?= $filter_year ?>&page=<?= $page + 1 ?>" class="btn btn-secondary btn-small">Sau &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL XEM CHI TIẾT ĐƠN HÀNG TRONG ADMIN -->
<div id="adminOrderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 15px;">
    <div style="background: #fff; border-radius: 8px; width: 100%; max-width: 650px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
        <div style="background: #343a40; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; color: #fff;">📋 Chi Tiết Đơn Hàng</h3>
            <button type="button" onclick="closeAdminOrderModal()" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <div id="adminOrderBody" style="padding: 20px; max-height: 450px; overflow-y: auto;">
            Đang tải...
        </div>
        <div style="background: #f8f9fa; padding: 10px 20px; text-align: right; border-top: 1px solid #eee;">
            <button type="button" onclick="closeAdminOrderModal()" class="btn btn-secondary">Đóng</button>
        </div>
    </div>
</div>

<script>
function changeOrderStatus(orderId, action) {
    if (!action) return;
    const actionMap = {
        'approve': 'Duyệt đơn hàng (Tự động trừ số lượng sách tương ứng trong kho)',
        'shipping': 'Chuyển đơn sang ĐANG GIAO HÀNG',
        'complete': 'Xác nhận HOÀN THÀNH (Tự động cộng lượt bán & Ghi nhận doanh thu)',
        'cancel': 'HỦY đơn hàng (Nếu đơn đã duyệt trước đó, số lượng sẽ được hoàn lại kho)'
    };
    const label = actionMap[action] || action;
    showConfirm('Bạn có chắc chắn muốn ' + label + ' cho đơn hàng #' + orderId + '?', 'Xác nhận thao tác đơn hàng', 'Xác nhận').then(confirmed => {
        if (confirmed) {
            window.location.href = 'donhang.php?action=' + action + '&id=' + orderId;
        }
    });
}

function viewAdminOrderDetail(id) {
    const modal = document.getElementById('adminOrderModal');
    const modalBody = document.getElementById('adminOrderBody');
    modalBody.innerHTML = '<div style="text-align:center; padding:20px;">Đang tải chi tiết đơn hàng...</div>';
    modal.style.display = 'flex';
    fetch('../ajax/lay_chitiet_donhang_ajax.php?id=' + id)
        .then(r => r.text())
        .then(html => { modalBody.innerHTML = html; })
        .catch(err => { modalBody.innerHTML = '<p style="color:red; text-align:center;">Không thể tải dữ liệu đơn hàng!</p>'; });
}
function closeAdminOrderModal() {
    document.getElementById('adminOrderModal').style.display = 'none';
}
</script>

<?php include_once 'footer.php'; ?>