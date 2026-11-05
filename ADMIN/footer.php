</main>
        <footer>
            <p>&copy; 2026 Quản Lý Web Bán Sách | Admin Panel</p>
        </footer>
    </div>
    <script>
        // Xóa trạng thái lưu cũ nếu có
        localStorage.removeItem('admin_sidebar_collapsed');

        // Highlight trang hiện tại trong Sidebar Admin
        const currentPage = location.pathname.split("/").pop();
        document.querySelectorAll('#sidebar li a').forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'index.php')) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>