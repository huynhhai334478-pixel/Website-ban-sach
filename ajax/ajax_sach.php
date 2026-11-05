<?php
// Lùi một cấp thư mục để trỏ đúng tới file kết nối CSDL
require_once '../ADMIN/config/connect.php';

if (isset($_GET['q'])) {
    $q = $conn->real_escape_string(mb_strtolower(trim($_GET['q']), 'UTF-8'));
    if (!empty($q)) {
        $sql = "SELECT sach_id, tensach, hinhanh, gia, phantram_giamgia FROM sach WHERE LOWER(tensach) LIKE '%$q%' LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gia_goc = $row['gia'];
                $phantram = intval($row['phantram_giamgia']);
                $gia_ban = $phantram > 0 ? $gia_goc * (1 - $phantram / 100) : $gia_goc;
                $formatted_price = number_format($gia_ban, 0, ',', '.') . ' đ';
                
                // Thêm dấu gạch chéo đầu tiên để tính từ thư mục gốc của website
                $img = "ADMIN/" . $row['hinhanh'];
                
                echo '<a href="chitiet.php?id='.$row['sach_id'].'" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: #333; border-bottom: 1px solid #f1f1f1;">
                        <img src="'.$img.'" style="width: 35px; height: 45px; object-fit: contain;">
                        <div>
                            <div style="font-size: 13px; font-weight: 500; color: #333;">'.htmlspecialchars($row['tensach']).'</div>
                            <div style="font-size: 12px; color: #C92127; font-weight: bold;">'.$formatted_price.'</div>
                        </div>
                      </a>';
            }
        } else {
            echo '<div style="padding: 10px 12px; font-size: 13px; color: #777;">Không tìm thấy kết quả phù hợp</div>';
        }
    }
}
?>