</main> <!-- Kết thúc thẻ main từ header -->

<footer class="user-footer">
    <div class="container footer-container">
        <div class="footer-col">
            <h3>BookStore</h3>
            <p>Lan tỏa tri thức, kết nối đam mê đọc sách đến mọi người.</p>
        </div>
        <div class="footer-col">
            <h3>Liên Kết Nhanh</h3>
            <ul>
                <li><a href="index.php">Trang Chủ</a></li>
                <li><a href="sach.php">Sản Phẩm</a></li>
                <li><a href="chinhsach.php">Chính Sách Mua Hàng</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Liên Hệ</h3>
            <p><i class="fas fa-map-marker-alt"></i> Cần Thơ, Việt Nam</p>
            <p><i class="fas fa-phone"></i> 0123 456 789</p>
            <p><i class="fas fa-envelope"></i> hotro@bookstore.com</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Cửa Hàng Sách. Đã đăng ký bản quyền.</p>
    </div>
</footer>
<script>
// Hàm xử lý việc Ẩn/Hiện mật khẩu
function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    
    if (input.type === 'password') {
        input.type = 'text'; // Chuyển thành text để hiện mật khẩu
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash'); // Đổi icon thành con mắt bị gạch chéo
    } else {
        input.type = 'password'; // Chuyển lại thành password để ẩn đi
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye'); // Đổi lại thành icon con mắt bình thường
    }
}
</script>
</body>
</html>