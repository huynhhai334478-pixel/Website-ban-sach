<?php
/**
 * Trang chủ Admin
 * Admin Dashboard
 */

require_once 'config/connect.php';

// 1. TÍNH TOÁN DOANH THU & LỢI NHUẬN (Công thức chuẩn: Doanh thu - Giá vốn hàng đã bán = Lãi/Lỗ)
// Lấy dữ liệu từ bảng doanh_thu (kết hợp bảng kho đề phòng các đơn hàng cũ chưa có gianhap)
$tongThu = 0;
$tongVon = 0;

$thuResult = $conn->query("
    SELECT dt.soluongban, dt.giaban, COALESCE(dt.gianhap, k.gianhap, 0) AS gianhap_thucte
    FROM doanh_thu dt
    LEFT JOIN kho k ON dt.kho_id = k.kho_id
");

if ($thuResult && $thuResult->num_rows > 0) {
    while ($row = $thuResult->fetch_assoc()) {
        $tongThu += (float)($row['giaban'] * $row['soluongban']);
        $tongVon += (float)($row['gianhap_thucte'] * $row['soluongban']);
    }
}
$laiTong = $tongThu - $tongVon;

// Tính riêng tổng giá trị tài sản đang nằm trong kho (không trừ vào doanh thu)
$tonKhoResult = $conn->query("SELECT COALESCE(SUM(gianhap * soluong), 0) AS tong_ton_kho FROM kho");
$tonKhoRow = $tonKhoResult ? $tonKhoResult->fetch_assoc() : array('tong_ton_kho' => 0);
$tongTonKho = (float)($tonKhoRow['tong_ton_kho'] ?? 0);


// 2. BIỂU ĐỒ LÃI/LỖ THEO THÁNG TRONG NĂM HIỆN TẠI
$namHienTai = date('Y');
$labelsThang = array('T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12');
$laiTheoThang = array_fill(0, 12, 0);

$chartResult = $conn->query("
    SELECT MONTH(dt.ngayban) AS thang, 
           dt.soluongban, dt.giaban, COALESCE(dt.gianhap, k.gianhap, 0) AS gianhap_thucte
    FROM doanh_thu dt
    LEFT JOIN kho k ON dt.kho_id = k.kho_id
    WHERE YEAR(dt.ngayban) = $namHienTai
");

if ($chartResult && $chartResult->num_rows > 0) {
    while ($row = $chartResult->fetch_assoc()) {
        $thangIndex = (int)$row['thang'] - 1;
        if ($thangIndex >= 0 && $thangIndex < 12) {
            $thu_dong = (float)($row['giaban'] * $row['soluongban']);
            $von_dong = (float)($row['gianhap_thucte'] * $row['soluongban']);
            $laiTheoThang[$thangIndex] += ($thu_dong - $von_dong);
        }
    }
}


// 3. TỔNG SỐ LƯỢNG BẢN GHI (Kèm link để click vào)
$thongKeTong = array(
    'nguoi_dung' => array('label' => 'Người dùng', 'link' => 'nguoidung.php', 'icon' => '👥', 'color' => '#3498db'),
    'the_loai' => array('label' => 'Thể loại', 'link' => 'theloai.php', 'icon' => '📚', 'color' => '#9b59b6'),
    'sach' => array('label' => 'Sách', 'link' => 'sach.php', 'icon' => '📖', 'color' => '#34495e'),
    'kho' => array('label' => 'Kho Hàng', 'link' => 'kho.php', 'icon' => '🏭', 'color' => '#e67e22'),
    'nha_cung_cap' => array('label' => 'Nhà Cung Cấp', 'link' => 'nhacungcap.php', 'icon' => '🏢', 'color' => '#d35400'),
    'don_hang' => array('label' => 'Đơn hàng', 'link' => 'donhang.php', 'icon' => '📋', 'color' => '#f1c40f'),
    'voucher' => array('label' => 'Voucher', 'link' => 'voucher.php', 'icon' => '🎟️', 'color' => '#16a085'),
    'danh_gia' => array('label' => 'Đánh giá', 'link' => 'danhgia.php', 'icon' => '⭐', 'color' => '#f39c12'),
    'banner_slide' => array('label' => 'Banner Slide', 'link' => 'banner.php', 'icon' => '🖼️', 'color' => '#2980b9'),
    'lien_he' => array('label' => 'Tin Liên Hệ', 'link' => 'lienhe.php', 'icon' => '✉️', 'color' => '#e74c3c'),
    'yeu_thich' => array('label' => 'Lượt Yêu Thích', 'link' => 'yeuthich.php', 'icon' => '💖', 'color' => '#e84393'),
);

foreach ($thongKeTong as $table => $info) {
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM $table");
    $countRow = $countResult ? $countResult->fetch_assoc() : array('total' => 0);
    $thongKeTong[$table]['count'] = (int)($countRow['total'] ?? 0);
}

// Cấu hình tiêu đề trang
$page_title = "Bảng Điều Khiển Admin - Web Bán Sách";

// Nhúng phần đầu trang dùng chung
include_once 'header.php';
?>

    <div class="container">
        <h2 style="text-align: center; margin-top: 30px; margin-bottom: 40px;">
            Chào mừng đến Admin Panel
        </h2>

        <h3 style="margin: 10px 0 20px 0;">📌 Truy cập Nhanh & Thống Kê</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px;">
            <?php foreach ($thongKeTong as $table => $data): ?>
                <a href="<?php echo $data['link']; ?>" style="text-decoration: none; display: block; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';">
                    <div style="background: white; padding: 20px 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-bottom: 4px solid <?php echo $data['color']; ?>;">
                        <div style="font-size: 28px; margin-bottom: 10px;"><?php echo $data['icon']; ?></div>
                        <h3 style="color: <?php echo $data['color']; ?>; margin: 0; font-size: 24px;"><?php echo $data['count']; ?></h3>
                        <p style="color: #555; margin: 8px 0 0 0; font-weight: bold;"><?php echo $data['label']; ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <h3 style="margin: 10px 0 20px 0;">💰 Tình Hình Tài Chính</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 5px solid #27ae60;">
                <h3 style="color: #27ae60; margin: 0; font-size: 22px;"><?php echo number_format($tongThu, 0, ',', '.'); ?>đ</h3>
                <p style="color: #7f8c8d; margin: 10px 0 0 0; font-weight: bold;">Tổng Doanh Thu</p>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 5px solid #e67e22;">
                <h3 style="color: #e67e22; margin: 0; font-size: 22px;"><?php echo number_format($tongVon, 0, ',', '.'); ?>đ</h3>
                <p style="color: #7f8c8d; margin: 10px 0 0 0; font-weight: bold;">Tổng Vốn (Đã bán)</p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 5px solid <?php echo $laiTong >= 0 ? '#2980b9' : '#e74c3c'; ?>;">
                <h3 style="color: <?php echo $laiTong >= 0 ? '#2980b9' : '#e74c3c'; ?>; margin: 0; font-size: 22px;"><?php echo number_format($laiTong, 0, ',', '.'); ?>đ</h3>
                <p style="color: #7f8c8d; margin: 10px 0 0 0; font-weight: bold;">Lợi Nhuận Thực Tế</p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 5px solid #8e44ad;">
                <h3 style="color: #8e44ad; margin: 0; font-size: 22px;"><?php echo number_format($tongTonKho, 0, ',', '.'); ?>đ</h3>
                <p style="color: #7f8c8d; margin: 10px 0 0 0; font-weight: bold;">Giá Trị Tồn Kho</p>
            </div>
        </div>

        <div style="background: white; padding: 30px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 40px;">
            <h3 style="margin-bottom: 20px;">📊 Biểu Đồ Lợi Nhuận Theo Tháng (<?php echo $namHienTai; ?>)</h3>
            <div style="position: relative; height: 360px;">
                <canvas id="financeChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const financeChart = document.getElementById('financeChart');
        if (financeChart) {
            new Chart(financeChart, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labelsThang, JSON_UNESCAPED_UNICODE); ?>,
                    datasets: [{
                        label: 'Lợi nhuận (VND)',
                        data: <?php echo json_encode($laiTheoThang, JSON_NUMERIC_CHECK); ?>,
                        backgroundColor: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            return value < 0 ? '#e74c3c' : '#2980b9';
                        },
                        borderColor: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            return value < 0 ? '#c0392b' : '#1f618d';
                        },
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 60
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y || 0;
                                    return new Intl.NumberFormat('vi-VN').format(value) + ' VND';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>

<?php 
// Nhúng phần cuối trang dùng chung
include_once 'footer.php'; 
?>