<?php
/**
 * Quản lý & Báo cáo doanh thu (Revenue Reports)
 * Bảng: doanh_thu (Chỉ đọc)
 */

require_once 'config/connect.php';

// Xử lý bộ lọc Tháng và Năm (mặc định xem tháng hiện tại)
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Xây dựng điều kiện lọc
$where_clauses = [];
if ($filter_month > 0) {
    $where_clauses[] = "MONTH(d.ngayban) = $filter_month";
}
if ($filter_year > 0) {
    $where_clauses[] = "YEAR(d.ngayban) = $filter_year";
}
$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Lấy danh sách doanh thu dựa trên bộ lọc
$sql = "SELECT d.*, k.gianhap as kho_gianhap 
        FROM doanh_thu d 
        LEFT JOIN kho k ON d.kho_id = k.kho_id 
        $where_sql
        ORDER BY d.ngayban ASC, d.doanhthu_id ASC";
$result = $conn->query($sql);

// Cấu hình tiêu đề trang
$page_title = "Báo cáo Doanh Thu - Admin";

// Nhúng Header dùng chung
include_once 'header.php';
?>

    <div class="container">
        <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">💰 Báo Cáo Doanh Thu & Lợi Nhuận</h2>

        <div style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="month" style="font-weight: bold; font-size: 14px;">Tháng:</label>
                    <select id="month" name="month" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="0">-- Tất cả --</option>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $filter_month === $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="year" style="font-weight: bold; font-size: 14px;">Năm:</label>
                    <select id="year" name="year" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="0">-- Tất cả --</option>
                        <?php 
                        $current_year = intval(date('Y'));
                        for($y = $current_year; $y >= $current_year - 5; $y--): 
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo $filter_year === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 6px 15px;">🔍 Lọc dữ liệu</button>
                <a href="doanhthu.php?month=0&year=0" class="btn btn-info" style="padding: 6px 15px; text-decoration: none; line-height: 20px;">🔄 Xem tất cả</a>
                <a href="xuat_excel_doanhthu.php?month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>" class="btn btn-success" style="padding: 6px 15px; text-decoration: none; line-height: 20px;">📥 Xuất Báo Cáo Excel</a>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ngày Bán</th>
                        <th>Tên Sách</th>
                        <th>SL Bán</th>
                        <th>Giá Bán</th>
                        <th>Giá Nhập (Vốn)</th>
                        <th>Tổng Thu</th>
                        <th>Lãi / Lỗ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        $tong_doanh_thu = 0;
                        $tong_loi_nhuan = 0;

                        while ($row = $result->fetch_assoc()) {
                            $tong_sanpham = $row['soluongban'] * $row['giaban'];
                            // Nếu đơn hàng cũ chưa có gianhap, lấy gianhap từ bảng kho làm dự phòng
                            $gia_nhap_thucte = !empty($row['gianhap']) ? $row['gianhap'] : ($row['kho_gianhap'] ?? 0);
                            $tong_von = $row['soluongban'] * $gia_nhap_thucte;
                            $lai_lo = $tong_sanpham - $tong_von;

                            $tong_doanh_thu += $tong_sanpham;
                            $tong_loi_nhuan += $lai_lo;

                            echo "<tr>
                                <td>{$row['doanhthu_id']}</td>
                                <td>" . date('d/m/Y', strtotime($row['ngayban'])) . "</td>
                                <td><strong>{$row['tensach']}</strong></td>
                                <td><span class='badge badge-info'>{$row['soluongban']}</span></td>
                                <td>💵 " . number_format($row['giaban'], 0, ',', '.') . "đ</td>
                                <td>" . number_format($gia_nhap_thucte, 0, ',', '.') . "đ</td>
                                <td><strong>" . number_format($tong_sanpham, 0, ',', '.') . "đ</strong></td>
                                <td style='font-weight: bold; color: " . ($lai_lo >= 0 ? '#27ae60' : '#e74c3c') . ";'>" . ($lai_lo >= 0 ? '+' : '') . number_format($lai_lo, 0, ',', '.') . "đ</td>
                            </tr>";
                        }
                        echo "<tr style='background-color: #f1f2f6; font-weight: bold; font-size: 15px;'>
                            <td colspan='6' style='text-align: right; color: #2c3e50;'>TỔNG THU & LỢI NHUẬN THỜI GIAN LỌC:</td>
                            <td style='color: #2980b9;'>💵 " . number_format($tong_doanh_thu, 0, ',', '.') . "đ</td>
                            <td style='color: " . ($tong_loi_nhuan >= 0 ? '#27ae60' : '#e74c3c') . ";'>" . ($tong_loi_nhuan >= 0 ? '+' : '') . number_format($tong_loi_nhuan, 0, ',', '.') . "đ</td>
                        </tr>";
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center;'>Không có dữ liệu doanh thu trong khoảng thời gian này.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<?php 
// Nhúng Footer dùng chung
include_once 'footer.php'; 
?>