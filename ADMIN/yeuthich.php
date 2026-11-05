<?php
/**
 * Quản lý & Thống Kê Sách Yêu Thích
 * Bảng: yeu_thich JOIN sach JOIN nguoi_dung
 */

require_once 'config/connect.php';

// SẮP XẾP SÁCH YÊU THÍCH (Mặc định: Lượt yêu thích nhiều nhất DESC)
$sort = $_GET['sort'] ?? 'likes_desc';
$order_by = "tong_luot_thich DESC";

if ($sort === 'likes_asc') {
    $order_by = "tong_luot_thich ASC";
} elseif ($sort === 'id_asc') {
    $order_by = "s.sach_id ASC";
}

$page_title = "Thống Kê Sách Yêu Thích - Admin";
include_once 'header.php';
?>

<div class="container">
    <h2 style="text-align: center; margin-top: 30px; margin-bottom: 30px;">💖 Thống Kê Sách Được Yêu Thích</h2>

    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
        <!-- Thanh Sắp Xếp Sách Yêu Thích -->
        <form method="GET" style="display: flex; align-items: center; gap: 10px; padding: 0; margin: 0; background: none; box-shadow: none;">
            <label for="sort" style="margin: 0; font-weight: bold;">Sắp xếp theo:</label>
            <select name="sort" id="sort" onchange="this.form.submit()" style="padding: 8px 12px; margin: 0; width: 250px;">
                <option value="likes_desc" <?= $sort === 'likes_desc' ? 'selected' : '' ?>>❤️ Sách yêu thích nhất (Nhiều nhất)</option>
                <option value="likes_asc" <?= $sort === 'likes_asc' ? 'selected' : '' ?>>🤍 Sách ít lượt yêu thích hơn</option>
                <option value="id_asc" <?= $sort === 'id_asc' ? 'selected' : '' ?>>Mã sách (Tăng dần)</option>
            </select>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã Sách</th>
                    <th>Hình Ảnh</th>
                    <th>Tên Sách</th>
                    <th>Tác Giả</th>
                    <th>Giá Bán</th>
                    <th>Lượt Yêu Thích</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT s.sach_id, s.tensach, s.tacgia, s.gia, s.hinhanh, COUNT(y.yeuthich_id) AS tong_luot_thich
                        FROM sach s
                        INNER JOIN yeu_thich y ON s.sach_id = y.sach_id
                        GROUP BY s.sach_id
                        ORDER BY $order_by";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $img_src = !empty($row['hinhanh']) ? $row['hinhanh'] : 'image/default.jpg';
                        echo "<tr>
                            <td>#{$row['sach_id']}</td>
                            <td><img src='{$img_src}' style='width: 50px; height: 65px; object-fit: contain; border-radius: 4px; border: 1px solid #eee;'></td>
                            <td><strong>" . htmlspecialchars($row['tensach']) . "</strong></td>
                            <td>" . htmlspecialchars($row['tacgia']) . "</td>
                            <td><strong style='color:#e74c3c;'>" . number_format($row['gia'], 0, ',', '.') . "đ</strong></td>
                            <td><span class='badge badge-danger' style='font-size:14px;'>❤️ {$row['tong_luot_thich']}</span></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;'>Chưa có sản phẩm nào được thả tim/yêu thích</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'footer.php'; ?>
