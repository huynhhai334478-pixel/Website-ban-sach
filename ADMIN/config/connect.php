<?php
/**
 * Kết nối cơ sở dữ liệu Web Bán Sách
 * Database Connection for Web_BanSach
 */

// Thông tin kết nối
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "web_bansach";

try {
    // Tạo kết nối MySQLi
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Kiểm tra kết nối trước
    if ($conn->connect_error) {
        throw new Exception("Kết nối CSDL thất bại: " . $conn->connect_error);
    }

    // Thiết lập charset UTF-8
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Lỗi thiết lập charset: " . $conn->error);
    }
    
} catch (Exception $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
