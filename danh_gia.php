<!-- ================= KHUNG ĐÁNH GIÁ VÀ BÌNH LUẬN KIỂU FAHASA ================= -->
<div style="background: #fff; border-radius: 8px; border: 1px solid #eee; padding: 25px; margin-top: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
    <h3 style="font-size: 16px; color: #333; border-bottom: 2px solid #C92127; padding-bottom: 8px; margin: 0 0 20px 0; text-transform: uppercase;">Đánh giá sản phẩm</h3>

    <!-- Khu vực hiển thị thông báo -->
    <div id="reviewAlert"></div>

    <?php
    // Xác định ID của người dùng hiện tại nếu đã đăng nhập
    $current_user_id = 0;
    $so_lan_mua = 0;
    $so_lan_danhgia = 0;
    $duoc_phep_danhgia = false;

    if (isset($_SESSION['taikhoan'])) {
        $us_logged = $_SESSION['taikhoan'];
        $u_q = $conn->query("SELECT nguoidung_id FROM nguoi_dung WHERE taikhoan = '$us_logged' LIMIT 1");
        if ($u_q && $u_q->num_rows > 0) {
            $u_row_data = $u_q->fetch_assoc();
            $current_user_id = intval($u_row_data['nguoidung_id']);

            // 1. Đếm số hóa đơn thành công (hoàn thành) mà người dùng này đã mua cuốn sách này
            $q_mua = $conn->query("SELECT COUNT(DISTINCT d.donhang_id) as total_mua 
                                   FROM don_hang d 
                                   JOIN chi_tiet_don_hang c ON d.donhang_id = c.donhang_id 
                                   WHERE d.nguoidung_id = $current_user_id 
                                     AND c.sach_id = $sach_id 
                                     AND d.trangthai = 'hoanthanh'");
            if ($q_mua && $row_m = $q_mua->fetch_assoc()) {
                $so_lan_mua = intval($row_m['total_mua']);
            }

            // 2. Đếm số lần người dùng này đã viết đánh giá cho cuốn sách này
            $q_dg = $conn->query("SELECT COUNT(*) as total_dg 
                                  FROM danh_gia 
                                  WHERE sach_id = $sach_id AND nguoidung_id = $current_user_id");
            if ($q_dg && $row_d = $q_dg->fetch_assoc()) {
                $so_lan_danhgia = intval($row_d['total_dg']);
            }

            // Nếu số lần mua > số lần đã đánh giá thì được phép đánh giá tiếp
            if ($so_lan_mua > 0 && $so_lan_danhgia < $so_lan_mua) {
                $duoc_phep_danhgia = true;
            }
        }
    }

    // Thống kê điểm số đánh giá
    $stats_query = $conn->query("SELECT COUNT(*) as total, AVG(sosao) as avg_sao FROM danh_gia WHERE sach_id = $sach_id");
    $stats = $stats_query->fetch_assoc();
    $total_dg = intval($stats['total']);
    $avg_sao = $stats['avg_sao'] ? round($stats['avg_sao'], 1) : 0;

    $sao_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $count_by_star = $conn->query("SELECT sosao, COUNT(*) as c FROM danh_gia WHERE sach_id = $sach_id GROUP BY sosao");
    while($row_s = $count_by_star->fetch_assoc()) {
        $sao_counts[intval($row_s['sosao'])] = intval($row_s['c']);
    }
    ?>

    <!-- BỐ CỤC TỔNG QUAN ĐÁNH GIÁ -->
    <div style="display: flex; gap: 40px; align-items: center; background: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #f0f0f0; margin-bottom: 25px;">
        <div style="text-align: center; width: 220px; flex-shrink: 0; border-right: 1px solid #ddd; padding-right: 20px;">
            <div style="font-size: 36px; font-weight: bold; color: #333; line-height: 1;"><?= $avg_sao ?><span style="font-size: 20px; color: #888;">/5</span></div>
            <div style="color: #ffc107; font-size: 16px; margin: 8px 0;">
                <?php 
                $sao_rounded = round($avg_sao);
                echo str_repeat('★', $sao_rounded) . str_repeat('☆', 5 - $sao_rounded);
                ?>
            </div>
            <div style="font-size: 13px; color: #666;">(<?= $total_dg ?> đánh giá)</div>
        </div>

        <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
            <?php for($i = 5; $i >= 1; $i--): 
                $percent = $total_dg > 0 ? ($sao_counts[$i] / $total_dg) * 100 : 0;
            ?>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #555;">
                    <span style="width: 45px; text-align: right;"><?= $i ?> sao</span>
                    <div style="flex: 1; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="width: <?= $percent ?>%; height: 100%; background: #ffc107; border-radius: 3px;"></div>
                    </div>
                    <span style="width: 35px; color: #888; font-size: 12px;"><?= round($percent) ?>%</span>
                </div>
            <?php endfor; ?>
        </div>

        <div style="width: 300px; text-align: center; font-size: 13px; color: #555;">
            <?php if (!isset($_SESSION['taikhoan'])): ?>
                <span>Chỉ có thành viên mới có thể viết nhận xét. Vui lòng <a href="dangnhap.php" style="color: #C92127; text-decoration: none; font-weight: bold;">đăng nhập</a> hoặc <a href="dangky.php" style="color: #C92127; text-decoration: none; font-weight: bold;">đăng ký</a>.</span>
            <?php elseif ($so_lan_mua == 0): ?>
                <span style="color: #dc3545; font-weight: bold; line-height: 1.4; display: block;">
                    <i class="fas fa-lock"></i> Chỉ khách hàng đã mua sản phẩm này mới được viết nhận xét.
                </span>
            <?php elseif ($so_lan_danhgia >= $so_lan_mua): ?>
                <span style="color: #e67e22; font-weight: bold; line-height: 1.4; display: block;">
                    <i class="fas fa-check-circle"></i> Bạn đã đánh giá sản phẩm này cho tất cả hóa đơn đã mua (<?= $so_lan_danhgia ?>/<?= $so_lan_mua ?> hóa đơn). Nhu cầu bình luận thêm vui lòng mua thêm sản phẩm trong hóa đơn mới!
                </span>
            <?php elseif ($duoc_phep_danhgia): ?>
                <span style="color: #28a745; font-weight: bold; line-height: 1.4; display: block;">
                    ✔ Bạn có thể viết nhận xét bên dưới (Bạn còn <b><?= ($so_lan_mua - $so_lan_danhgia) ?></b> lượt đánh giá tương ứng với hóa đơn đã mua).
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; gap: 20px; border-bottom: 2px solid #eee; margin-bottom: 20px; font-size: 14px;">
        <span style="padding-bottom: 8px; border-bottom: 2px solid #C92127; color: #C92127; font-weight: bold;">Mới nhất</span>
    </div>

    <!-- FORM VIẾT ĐÁNH GIÁ (Chỉ hiện khi được phép) -->
    <?php if ($duoc_phep_danhgia): ?>
        <div style="background: #f8f9fa; padding: 15px 20px; border-radius: 6px; margin-bottom: 25px;">
            <h4 style="font-size: 14px; color: #333; margin: 0 0 10px 0;">Viết đánh giá cho hóa đơn đã mua</h4>
            <form id="reviewForm">
                <input type="hidden" name="sach_id" value="<?= $sach_id ?>">
                <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <label style="font-size: 13px; color: #555;">Đánh giá sao:</label>
                    <select name="sosao" style="padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                        <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
                        <option value="4">⭐⭐⭐⭐ (4/5)</option>
                        <option value="3">⭐⭐⭐ (3/5)</option>
                        <option value="2">⭐⭐ (2/5)</option>
                        <option value="1">⭐ (1/5)</option>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <textarea name="binhluan" id="binhluanInput" rows="3" placeholder="Chia sẻ cảm nhận của bạn về cuốn sách..." required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; outline: none;"></textarea>
                </div>
                <button type="submit" name="gui_danhgia" style="background: #C92127; color: white; border: none; padding: 8px 20px; border-radius: 4px; font-size: 14px; font-weight: bold; cursor: pointer;">Gửi Đánh Giá</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- DANH SÁCH BÌNH LUẬN -->
    <div id="reviewList" style="display: flex; flex-direction: column; gap: 20px;">
        <?php
        $sql_dg = "SELECT dg.*, nd.taikhoan FROM danh_gia dg LEFT JOIN nguoi_dung nd ON dg.nguoidung_id = nd.nguoidung_id WHERE dg.sach_id = $sach_id ORDER BY dg.danhgia_id DESC";
        $result_dg = $conn->query($sql_dg);

        if (!isset($_SESSION['liked_reviews'])) {
            $_SESSION['liked_reviews'] = [];
        }

        if ($result_dg && $result_dg->num_rows > 0) {
            while ($dg = $result_dg->fetch_assoc()) {
                $sao_hienthi = str_repeat('★', intval($dg['sosao'])) . str_repeat('☆', 5 - intval($dg['sosao']));
                $dg_id = $dg['danhgia_id'];
                $luotthich = intval($dg['luotthich']);
                $dg_nguoidung_id = intval($dg['nguoidung_id']);
                
                // Kiểm tra xem có phải bình luận của chính user đang đăng nhập không
                $is_my_review = ($current_user_id > 0 && $dg_nguoidung_id === $current_user_id);

                // Nếu là bản thân thì hiển thị chữ "Bạn", ngược lại hiển thị tên tài khoản
                $ten_hienthi = $is_my_review ? 'Bạn' : (!empty($dg['taikhoan']) ? htmlspecialchars($dg['taikhoan']) : 'Khách hàng');

                $is_liked_current = in_array($dg_id, $_SESSION['liked_reviews']);
                $btn_color = $is_liked_current ? '#C92127' : '#007bff';
                $text_like = $is_liked_current ? 'Đã thích' : 'Thích';
                
                echo '
                <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 15px; display: flex; gap: 20px;">
                    <div style="width: 120px; flex-shrink: 0;">
                        <div style="font-weight: bold; font-size: 14px; color: '.($is_my_review ? '#C92127' : '#333').';">'.$ten_hienthi.'</div>
                        <div style="font-size: 12px; color: #888; margin-top: 3px;">'.date('d/m/Y', strtotime($dg['ngaytao'])).'</div>
                    </div>
                    <div style="flex: 1;">
                        <div style="color: #ffc107; font-size: 14px; margin-bottom: 5px;">'.$sao_hienthi.'</div>
                        <p style="font-size: 14px; color: #444; margin: 0 0 8px 0; line-height: 1.5;">'.nl2br(htmlspecialchars($dg['binhluan'])).'</p>
                        <div style="display: flex; gap: 15px; font-size: 12px; color: #777;">';
                
                // Nếu là bình luận của chính mình thì không cho like/báo cáo
                if ($is_my_review) {
                    echo '
                            <span style="color: #aaa; cursor: not-allowed;"><i class="far fa-thumbs-up"></i> Thích ('.$luotthich.')</span>
                            <span style="color: #aaa; cursor: not-allowed;"><i class="far fa-flag"></i> Báo cáo</span>';
                } else {
                    echo '
                            <span onclick="thichBinhLuan('.$dg_id.')" style="cursor: pointer; color: '.$btn_color.'; font-weight: bold;" id="like-btn-'.$dg_id.'">
                                <i class="far fa-thumbs-up"></i> <span id="like-text-'.$dg_id.'">'.$text_like.'</span> (<span id="like-count-'.$dg_id.'">'.$luotthich.'</span>)
                            </span>
                            <span onclick="baocaoBinhLuan('.$dg_id.')" style="cursor: pointer; color: #C92127;" id="report-text-'.$dg_id.'">
                                <i class="far fa-flag"></i> Báo cáo
                            </span>';
                }

                echo '
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo '<p style="color: #777; font-style: italic; font-size: 14px; text-align: center; margin: 20px 0;">Chưa có đánh giá nào cho cuốn sách này. Hãy là người đầu tiên đánh giá!</p>';
        }
        ?>
    </div>
</div>

<!-- JavaScript xử lý Ajax -->
<script>
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            formData.append('gui_danhgia_ajax', 1);

            fetch('ajax/xu_ly_danhgia_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let alertBox = document.getElementById('reviewAlert');
                if (data.status === 'success') {
                    if (typeof showToast === 'function') showToast('success', 'Gửi đánh giá thành công! Cảm ơn bạn đã chia sẻ cảm nhận.');
                    alertBox.innerHTML = '<div style="background: #e6f9ec; color: #28a745; padding: 10px 15px; border-radius: 6px; font-size: 14px; margin-bottom: 15px; font-weight: bold;">Gửi đánh giá thành công! Cảm ơn bạn đã chia sẻ cảm nhận.</div>';
                    document.getElementById('binhluanInput').value = '';
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else if (data.status === 'not_login') {
                    if (typeof showToast === 'function') showToast('warning', data.message); else alert(data.message);
                    setTimeout(() => { window.location.href = 'dangnhap.php'; }, 1000);
                } else {
                    if (typeof showToast === 'function') showToast('error', data.message);
                    alertBox.innerHTML = '<div style="background: #ffe6e6; color: #C92127; padding: 10px 15px; border-radius: 6px; font-size: 14px; margin-bottom: 15px;">'+data.message+'</div>';
                }
            });
        });
    }

    function thichBinhLuan(id) {
        let formData = new FormData();
        formData.append('danhgia_id', id);
        formData.append('action_type', 'like');

        fetch('ajax/xu_ly_danhgia_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'not_login') {
                if (typeof showToast === 'function') showToast('warning', data.message); else alert(data.message);
                setTimeout(() => { window.location.href = 'dangnhap.php'; }, 1000);
                return;
            }
            if(data.status === 'error') {
                if (typeof showToast === 'function') showToast('error', data.message); else alert(data.message);
                return;
            }
            if(data.status === 'success') {
                document.getElementById('like-count-' + id).innerText = data.count;
                let btn = document.getElementById('like-btn-' + id);
                let textSpan = document.getElementById('like-text-' + id);
                
                if(data.is_liked) {
                    btn.style.color = '#C92127';
                    textSpan.innerText = 'Đã thích';
                    if (typeof showToast === 'function') showToast('success', 'Đã thích đánh giá!');
                } else {
                    btn.style.color = '#007bff';
                    textSpan.innerText = 'Thích';
                    if (typeof showToast === 'function') showToast('info', 'Đã bỏ thích đánh giá!');
                }
            }
        });
    }

    function baocaoBinhLuan(id) {
        let formData = new FormData();
        formData.append('danhgia_id', id);
        formData.append('action_type', 'report');

        fetch('ajax/xu_ly_danhgia_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'not_login') {
                if (typeof showToast === 'function') showToast('warning', data.message); else alert(data.message);
                setTimeout(() => { window.location.href = 'dangnhap.php'; }, 1000);
                return;
            }
            if(data.status === 'error') {
                if (typeof showToast === 'function') showToast('error', data.message); else alert(data.message);
                return;
            }
            if(data.status === 'success') {
                if (typeof showToast === 'function') showToast('info', 'Cảm ơn bạn! Báo cáo của bạn đã được gửi tới quản trị viên.');
            }
        });
    }
                    let reportBtn = document.getElementById('report-text-' + id);
                    reportBtn.innerText = "Đã báo cáo";
                    reportBtn.style.color = "#888";
                    reportBtn.style.pointerEvents = "none";
                }
            }
        });
    }
</script>