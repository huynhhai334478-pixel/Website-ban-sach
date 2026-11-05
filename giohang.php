<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Đánh dấu đã xem giỏ hàng (ẩn badge số lượng trên header)
$_SESSION['giohang_unread'] = false;

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['giohang'])) {
    $_SESSION['giohang'] = [];
}

// Xử lý các thao tác cập nhật số lượng hoặc xóa sản phẩm trong giỏ hàng
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($action == 'delete' && $id > 0) {
        unset($_SESSION['giohang'][$id]);
        $_SESSION['toast'] = [
            'type' => 'info',
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng thành công!'
        ];
        header("Location: giohang.php");
        exit();
    } elseif ($action == 'update' && $id > 0) {
        $soluong = isset($_GET['soluong']) ? intval($_GET['soluong']) : 1;
        if ($soluong > 0) {
            $_SESSION['giohang'][$id] = $soluong;
        } else {
            unset($_SESSION['giohang'][$id]);
            $_SESSION['toast'] = [
                'type' => 'info',
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng thành công!'
            ];
        }
        header("Location: giohang.php");
        exit();
    } elseif ($action == 'clear') {
        $_SESSION['giohang'] = [];
        $_SESSION['toast'] = [
            'type' => 'info',
            'message' => 'Đã xóa tất cả sản phẩm trong giỏ hàng!'
        ];
        header("Location: giohang.php");
        exit();
    } elseif ($action == 'delete_selected' && isset($_GET['ids'])) {
        $ids = array_map('intval', explode(',', $_GET['ids']));
        $count_del = 0;
        foreach ($ids as $del_id) {
            if ($del_id > 0 && isset($_SESSION['giohang'][$del_id])) {
                unset($_SESSION['giohang'][$del_id]);
                $count_del++;
            }
        }
        $_SESSION['toast'] = [
            'type' => 'info',
            'message' => "Đã xóa thành công $count_del sản phẩm đã chọn khỏi giỏ hàng!"
        ];
        header("Location: giohang.php");
        exit();
    }
}

// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';
?>

<!-- BỐ CỤC TRANG GIỎ HÀNG -->
<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px; font-family: Arial, sans-serif;">
    <h2 style="font-size: 20px; color: #333; text-transform: uppercase; border-bottom: 2px solid #C92127; padding-bottom: 8px; margin-bottom: 20px;">
        Giỏ Hàng Của Bạn (<span style="color: #C92127;"><?= array_sum($_SESSION['giohang']) ?></span> sản phẩm)
    </h2>

    <?php if (empty($_SESSION['giohang'])): ?>
        <!-- Trường hợp giỏ hàng trống -->
        <div style="background: #fff; padding: 50px; text-align: center; border-radius: 8px; border: 1px solid #eee;">
            <i class="fas fa-shopping-cart" style="font-size: 60px; color: #ddd; margin-bottom: 15px;"></i>
            <p style="font-size: 16px; color: #666; margin-bottom: 20px;">Không có sản phẩm nào trong giỏ hàng của bạn.</p>
            <a href="sach_tatca.php" style="background: #C92127; color: white; padding: 10px 25px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px;">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <!-- Trường hợp có sản phẩm trong giỏ -->
        <form action="thanhtoan.php" method="POST" id="cartForm">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                
                <!-- Cột trái: Bảng danh sách sản phẩm -->
                <div style="flex: 1; background: #fff; border-radius: 8px; border: 1px solid #eee; padding: 20px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f1f1; text-align: left; font-size: 13.5px; color: #666;">
                                <th style="padding-bottom: 12px; width: 6%; text-align: center;">
                                    <input type="checkbox" id="selectAllCheckbox" checked style="width: 17px; height: 17px; cursor: pointer; accent-color: #C92127;" title="Chọn tất cả">
                                </th>
                                <th style="padding-bottom: 12px; width: 42%;">Sản phẩm</th>
                                <th style="padding-bottom: 12px; width: 14%;">Đơn giá</th>
                                <th style="padding-bottom: 12px; width: 14%; text-align: center;">Số lượng</th>
                                <th style="padding-bottom: 12px; width: 14%;">Thành tiền</th>
                                <th style="padding-bottom: 12px; width: 10%; text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tong_tien = 0;
                            foreach ($_SESSION['giohang'] as $sach_id => $soluong) {
                                $q = $conn->query("SELECT * FROM sach WHERE sach_id = $sach_id LIMIT 1");
                                if ($q && $q->num_rows > 0) {
                                    $book = $q->fetch_assoc();
                                    $gia_goc = $book['gia'];
                                    $phantram_giam = intval($book['phantram_giamgia']);
                                    $gia_ban = $phantram_giam > 0 ? $gia_goc * (1 - $phantram_giam / 100) : $gia_goc;
                                    $thanhtien = $gia_ban * $soluong;
                                    $tong_tien += $thanhtien;
                                    
                                    $hinhanh = "ADMIN/" . $book['hinhanh'];
                            ?>
                                    <tr style="border-bottom: 1px solid #f9f9f9; font-size: 14px;">
                                        <!-- Ô Checkbox chọn từng sản phẩm -->
                                        <td style="text-align: center; vertical-align: middle;">
                                            <input type="checkbox" class="cart-item-checkbox" name="selected_items[]" value="<?= $sach_id ?>" data-thanhtien="<?= $thanhtien ?>" checked style="width: 17px; height: 17px; cursor: pointer; accent-color: #C92127;">
                                        </td>
                                        <!-- Sản phẩm -->
                                        <td style="padding: 15px 0; display: flex; gap: 15px; align-items: center;">
                                            <img src="<?= $hinhanh ?>" style="width: 60px; height: 75px; object-fit: contain; border: 1px solid #eee; border-radius: 4px; padding: 2px;">
                                            <div>
                                                <a href="chitiet.php?id=<?= $book['sach_id'] ?>" style="text-decoration: none; color: #333; font-weight: 500; line-height: 1.4; display: block; margin-bottom: 5px;"><?= htmlspecialchars($book['tensach']) ?></a>
                                                <span style="font-size: 12px; color: #888;"><?= htmlspecialchars($book['tacgia']) ?></span>
                                            </div>
                                        </td>
                                        <!-- Đơn giá -->
                                        <td style="color: #C92127; font-weight: bold; font-size: 14px;">
                                            <?= number_format($gia_ban, 0, ',', '.') ?> đ
                                        </td>
                                        <!-- Số lượng -->
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; align-items: center; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                                <a href="giohang.php?action=update&id=<?= $sach_id ?>&soluong=<?= $soluong - 1 ?>" style="width: 25px; height: 30px; background: #f1f1f1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #333; font-weight: bold;">-</a>
                                                <span style="width: 35px; text-align: center; font-size: 13px;"><?= $soluong ?></span>
                                                <a href="giohang.php?action=update&id=<?= $sach_id ?>&soluong=<?= $soluong + 1 ?>" style="width: 25px; height: 30px; background: #f1f1f1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #333; font-weight: bold;">+</a>
                                            </div>
                                        </td>
                                        <!-- Thành tiền -->
                                        <td style="color: #C92127; font-weight: bold; font-size: 14px;">
                                            <?= number_format($thanhtien, 0, ',', '.') ?> đ
                                        </td>
                                        <!-- Xóa -->
                                        <td style="text-align: center;">
                                            <a href="giohang.php?action=delete&id=<?= $sach_id ?>" onclick="return confirmAction(event, 'Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?', 'Xác nhận xóa sản phẩm', 'Xác nhận xóa');" style="color: #999; text-decoration: none; font-size: 16px;" title="Xóa">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                            <?php 
                                }
                            } 
                            ?>
                        </tbody>
                    </table>
                    
                    <!-- Thanh công cụ dưới bảng -->
                    <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; background: #fafafa; padding: 12px 15px; border-radius: 6px; border: 1px solid #eee;">
                        <a href="sach_tatca.php" style="color: #C92127; text-decoration: none; font-size: 14px; font-weight: bold;">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <button type="button" id="btnDeleteSelected" style="background: none; border: none; color: #dc3545; font-size: 13.5px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: bold;">
                                <i class="fas fa-trash-alt"></i> Xóa sản phẩm đã chọn (<span id="countSelectedDelete">0</span>)
                            </button>
                            <span style="color: #ddd;">|</span>
                            <a href="giohang.php?action=clear" onclick="return confirmAction(event, 'Bạn có chắc muốn xóa toàn bộ giỏ hàng?', 'Xác nhận xóa tất cả', 'Xác nhận xóa');" style="color: #888; text-decoration: none; font-size: 13px;">
                                Xóa tất cả giỏ hàng
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Tổng tiền & Nút thanh toán -->
                <div style="width: 320px; flex-shrink: 0; background: #fff; border-radius: 8px; border: 1px solid #eee; padding: 20px;">
                    <h3 style="font-size: 15px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin: 0 0 15px 0; text-transform: uppercase;">Cộng giỏ hàng</h3>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #666; margin-bottom: 12px;">
                        <span>Tạm tính (<span id="selectedCountText">0</span>SP):</span>
                        <span style="font-weight: bold; color: #333;" id="displaySubtotal"><?= number_format($tong_tien, 0, ',', '.') ?> đ</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #666; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <span>Phí vận chuyển:</span>
                        <span style="color: #28a745; font-weight: bold;">Miễn phí</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 16px; color: #333; margin-bottom: 20px;">
                        <span style="font-weight: bold;">Tổng tiền:</span>
                        <span style="font-weight: bold; color: #C92127; font-size: 20px;" id="displayFinalTotal"><?= number_format($tong_tien, 0, ',', '.') ?> đ</span>
                    </div>

                    <button type="submit" id="btnCheckout" style="display: block; width: 100%; background: #C92127; color: white; text-align: center; padding: 12px 0; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; font-size: 15px; box-sizing: border-box; transition: 0.2s;">
                        Tiến Hành Thanh Toán (<span id="btnCheckoutCount">0</span>)
                    </button>
                </div>

            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const itemCheckboxes = document.querySelectorAll('.cart-item-checkbox');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayFinalTotal = document.getElementById('displayFinalTotal');
    const selectedCountText = document.getElementById('selectedCountText');
    const btnCheckoutCount = document.getElementById('btnCheckoutCount');
    const countSelectedDelete = document.getElementById('countSelectedDelete');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const cartForm = document.getElementById('cartForm');

    function updateCartSummary() {
        let totalMoney = 0;
        let selectedCount = 0;
        let totalItems = itemCheckboxes.length;

        itemCheckboxes.forEach(cb => {
            if (cb.checked) {
                totalMoney += parseFloat(cb.getAttribute('data-thanhtien')) || 0;
                selectedCount++;
            }
        });

        let formattedMoney = Number(totalMoney).toLocaleString('vi-VN') + ' đ';
        if (displaySubtotal) displaySubtotal.innerText = formattedMoney;
        if (displayFinalTotal) displayFinalTotal.innerText = formattedMoney;

        if (selectedCountText) selectedCountText.innerText = selectedCount;
        if (btnCheckoutCount) btnCheckoutCount.innerText = selectedCount;
        if (countSelectedDelete) countSelectedDelete.innerText = selectedCount;

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (selectedCount > 0 && selectedCount === totalItems);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
            updateCartSummary();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateCartSummary);
    });

    if (btnDeleteSelected) {
        btnDeleteSelected.addEventListener('click', function() {
            let selectedIds = [];
            itemCheckboxes.forEach(cb => {
                if (cb.checked) selectedIds.push(cb.value);
            });

            if (selectedIds.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('warning', 'Vui lòng chọn ít nhất 1 sản phẩm để xóa!');
                }
                return;
            }

            showConfirm('Bạn có chắc chắn muốn xóa ' + selectedIds.length + ' sản phẩm đã chọn khỏi giỏ hàng?', 'Xóa sản phẩm đã chọn', 'Xác nhận xóa').then(confirmed => {
                if (confirmed) {
                    window.location.href = 'giohang.php?action=delete_selected&ids=' + selectedIds.join(',');
                }
            });
        });
    }

    if (cartForm) {
        cartForm.addEventListener('submit', function(e) {
            let selectedIds = [];
            itemCheckboxes.forEach(cb => {
                if (cb.checked) selectedIds.push(cb.value);
            });

            if (selectedIds.length === 0) {
                e.preventDefault();
                if (typeof showToast === 'function') {
                    showToast('warning', 'Vui lòng chọn ít nhất 1 sản phẩm để tiến hành thanh toán!');
                }
            }
        });
    }

    updateCartSummary();
});
</script>

<?php
$conn->close();
include 'footer.php';
?>