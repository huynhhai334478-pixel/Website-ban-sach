<?php
/**
 * Xuất file Excel báo cáo doanh thu & lợi nhuận
 * Định dạng .xls (Microsoft Excel / Google Sheets UTF-8)
 */

require_once 'config/connect.php';

// Kiểm tra quyền Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['taikhoan']) || $_SESSION['vaitro'] !== 'admin') {
    die("Từ chối truy cập! Quyền Admin là bắt buộc.");
}

// Xử lý bộ lọc Tháng và Năm từ URL
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$where_clauses = [];
if ($filter_month > 0) {
    $where_clauses[] = "MONTH(d.ngayban) = $filter_month";
}
if ($filter_year > 0) {
    $where_clauses[] = "YEAR(d.ngayban) = $filter_year";
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Truy vấn dữ liệu
$sql = "SELECT d.*, k.gianhap as kho_gianhap 
        FROM doanh_thu d 
        LEFT JOIN kho k ON d.kho_id = k.kho_id 
        $where_sql
        ORDER BY d.ngayban ASC, d.doanhthu_id ASC";
$result = $conn->query($sql);

// Đặt tên file xuất ra
$filename = "Bao_Cao_Doanh_Thu_";
if ($filter_month > 0) $filename .= "Thang_" . $filter_month . "_";
if ($filter_year > 0) $filename .= "Nam_" . $filter_year;
else $filename .= "Tat_Ca";
$filename .= ".xls";

// Thiết lập Headers tải file Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ghi chuỗi UTF-8 BOM để Excel hiển thị đúng tiếng Việt không bị lỗi font
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th { background-color: #C92127; color: #ffffff; border: 1px solid #999; padding: 10px; text-align: center; font-weight: bold; }
        td { border: 1px solid #ccc; padding: 8px; vertical-align: middle; }
        .number { text-align: right; }
        .center { text-align: center; }
        .total-row { background-color: #e9ecef; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <h2 style="text-align: center; color: #C92127; margin-bottom: 5px;">BÁO CÁO DOANH THU & LỢI NHUẬN WEB BÁN SÁCH</h2>
    <p style="text-align: center; color: #555; margin-top: 0;">
        <strong>Khoảng thời gian:</strong> 
        <?php 
        if ($filter_month > 0 && $filter_year > 0) echo "Tháng $filter_month / Năm $filter_year";
        elseif ($filter_month > 0) echo "Tháng $filter_month";
        elseif ($filter_year > 0) echo "Năm $filter_year";
        else echo "Tất cả các thời điểm";
        ?>
    </p>
    
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã Đơn DT</th>
                <th>Ngày Bán</th>
                <th>Tên Sách</th>
                <th>Số Lượng Bán</th>
                <th>Đơn Giá Bán (VND)</th>
                <th>Giá Vốn Nhập (VND)</th>
                <th>Tổng Doanh Thu (VND)</th>
                <th>Lợi Nhuận Lãi/Lỗ (VND)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                $stt = 1;
                $tong_doanh_thu = 0;
                $tong_loi_nhuan = 0;
                $tong_soluong = 0;

                while ($row = $result->fetch_assoc()) {
                    $tong_sanpham = $row['soluongban'] * $row['giaban'];
                    $gia_nhap_thucte = !empty($row['gianhap']) ? $row['gianhap'] : ($row['kho_gianhap'] ?? 0);
                    $tong_von = $row['soluongban'] * $gia_nhap_thucte;
                    $lai_lo = $tong_sanpham - $tong_von;

                    $tong_doanh_thu += $tong_sanpham;
                    $tong_loi_nhuan += $lai_lo;
                    $tong_soluong += $row['soluongban'];

                    echo "<tr>";
                    echo "<td class='center'>{$stt}</td>";
                    echo "<td class='center'>#{$row['doanhthu_id']}</td>";
                    echo "<td class='center'>" . date('d/m/Y', strtotime($row['ngayban'])) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($row['tensach']) . "</strong></td>";
                    echo "<td class='center'>{$row['soluongban']}</td>";
                    echo "<td class='number'>" . number_format($row['giaban'], 0, ',', '.') . "</td>";
                    echo "<td class='number'>" . number_format($gia_nhap_thucte, 0, ',', '.') . "</td>";
                    echo "<td class='number'><strong>" . number_format($tong_sanpham, 0, ',', '.') . "</strong></td>";
                    echo "<td class='number' style='color: " . ($lai_lo >= 0 ? '#1e7e34' : '#c92127') . ";'><strong>" . ($lai_lo >= 0 ? '+' : '') . number_format($lai_lo, 0, ',', '.') . "</strong></td>";
                    echo "</tr>";
                    $stt++;
                }
                echo "<tr class='total-row'>";
                echo "<td colspan='4' style='text-align: right;'>TỔNG CỘNG THỜI GIAN LỌC:</td>";
                echo "<td class='center'>{$tong_soluong}</td>";
                echo "<td></td>";
                echo "<td></td>";
                echo "<td class='number' style='color:#1d6fba;'><strong>" . number_format($tong_doanh_thu, 0, ',', '.') . " đ</strong></td>";
                echo "<td class='number' style='color: " . ($tong_loi_nhuan >= 0 ? '#1e7e34' : '#c92127') . ";'><strong>" . ($tong_loi_nhuan >= 0 ? '+' : '') . number_format($tong_loi_nhuan, 0, ',', '.') . " đ</strong></td>";
                echo "</tr>";
            } else {
                echo "<tr><td colspan='9' class='center'>Không có dữ liệu báo cáo doanh thu trong khoảng thời gian này.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
