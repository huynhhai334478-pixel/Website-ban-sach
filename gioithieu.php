<?php
// Gọi header
include 'header.php';

// Kết nối CSDL
require_once 'ADMIN/config/connect.php';
?>

<div style="background: #f8f9fa; padding-bottom: 50px;">

    <!-- 1. HERO BANNER GIỚI THIỆU -->
    <div style="background: linear-gradient(135deg, #C92127 0%, #990B10 100%); color: white; padding: 60px 20px; text-align: center; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(201, 33, 39, 0.2);">
        <div style="max-width: 900px; margin: 0 auto;">
            <i class="fas fa-book-reader" style="font-size: 55px; margin-bottom: 15px; opacity: 0.95;"></i>
            <h1 style="font-size: 32px; font-weight: 800; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 1px;">
                Giới Thiệu Về BookStore
            </h1>
            <p style="font-size: 16px; opacity: 0.9; max-width: 700px; margin: 0 auto; line-height: 1.6;">
                Hành trình truyền cảm hứng đọc sách, lan tỏa tri thức và mang đến những cuốn sách chất lượng hàng đầu cho hàng triệu độc giả Việt Nam.
            </p>
        </div>
    </div>

    <div style="max-width: 1100px; margin: 0 auto; padding: 0 15px;">

        <!-- 2. CÂU CHUYỆN THƯƠNG HIỆU -->
        <div style="background: #fff; padding: 35px; border-radius: 12px; border: 1px solid #e5e5e5; margin-bottom: 35px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 35px; align-items: center;">
                <div>
                    <h2 style="font-size: 22px; color: #333; margin: 0 0 15px 0; border-left: 4px solid #C92127; padding-left: 12px;">
                        Hành Trình Kết Nối Tri Thức
                    </h2>
                    <p style="font-size: 14.5px; color: #555; line-height: 1.7; margin-bottom: 15px;">
                        Được thành lập với tình yêu mãnh liệt dành cho những trang sách, <strong>BookStore</strong> tự hào là điểm đến tin cậy của hàng ngàn độc giả yêu sách trên khắp mọi miền đất nước.
                    </p>
                    <p style="font-size: 14.5px; color: #555; line-height: 1.7; margin: 0;">
                        Chúng tôi tin rằng mỗi cuốn sách là một thế giới mới rộng mở, mang lại nguồn cảm hứng bất tận, mở rộng vốn sống và thắp sáng những ước mơ. Dù bạn tìm kiếm tiểu thuyết văn học sâu lắng, tri thức kinh tế sắc bén hay những cuốn sách kỹ năng sống bổ ích, BookStore luôn sẵn sàng đồng hành cùng bạn.
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="background: #FFF5F5; border: 2px dashed #FFDCDC; border-radius: 12px; padding: 30px 20px;">
                        <i class="fas fa-quote-left" style="font-size: 35px; color: #C92127; opacity: 0.3; margin-bottom: 10px;"></i>
                        <p style="font-size: 16px; font-style: italic; color: #C92127; font-weight: 600; line-height: 1.6; margin-bottom: 10px;">
                            "Việc đọc tất cả những cuốn sách hay cũng giống như trò chuyện với những bộ óc tuyệt vời nhất của những thế kỷ đã qua."
                        </p>
                        <span style="font-size: 13px; color: #777; font-weight: bold;">— René Descartes —</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. SỨ MỆNH - TẦM NHÌN - GIÁ TRỊ CỐT LÕI -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 35px;">
            
            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #eee; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: 0.3s;">
                <div style="width: 60px; height: 60px; background: #FFF0F5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                    <i class="fas fa-bullseye" style="font-size: 26px; color: #C92127;"></i>
                </div>
                <h3 style="font-size: 17px; color: #333; margin: 0 0 10px 0;">Sứ Mệnh</h3>
                <p style="font-size: 13.5px; color: #666; line-height: 1.6; margin: 0;">
                    Lan tỏa văn hóa đọc rộng rãi, mang tri thức đến với mọi nhà bằng những sản phẩm sách chuẩn mực, chính hãng và dễ dàng tiếp cận.
                </p>
            </div>

            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #eee; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: 0.3s;">
                <div style="width: 60px; height: 60px; background: #EBF3FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                    <i class="fas fa-eye" style="font-size: 26px; color: #0F62FE;"></i>
                </div>
                <h3 style="font-size: 17px; color: #333; margin: 0 0 10px 0;">Tầm Nhìn</h3>
                <p style="font-size: 13.5px; color: #666; line-height: 1.6; margin: 0;">
                    Trở thành hệ thống nhà sách trực tuyến hàng đầu Việt Nam, được độc giả yêu mến nhờ sự đa dạng danh mục và chất lượng dịch vụ xuất sắc.
                </p>
            </div>

            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #eee; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: 0.3s;">
                <div style="width: 60px; height: 60px; background: #E8F8F0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                    <i class="fas fa-heart" style="font-size: 26px; color: #28a745;"></i>
                </div>
                <h3 style="font-size: 17px; color: #333; margin: 0 0 10px 0;">Giá Trị Cốt Lõi</h3>
                <p style="font-size: 13.5px; color: #666; line-height: 1.6; margin: 0;">
                    Tận tâm phục vụ, cam kết sách thật 100%, không ngừng đổi mới trải nghiệm mua sắm nhằm mang lại sự hài lòng tuyệt đối cho độc giả.
                </p>
            </div>

        </div>

        <!-- 4. VÌ SAO CHỌN BOOKSTORE? -->
        <div style="background: #fff; padding: 35px; border-radius: 12px; border: 1px solid #e5e5e5; margin-bottom: 35px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <h2 style="font-size: 20px; color: #333; margin: 0 0 25px 0; text-align: center; text-transform: uppercase;">
                Vì Sao Bạn Nên Chọn BookStore?
            </h2>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                
                <div style="text-align: center; padding: 15px;">
                    <i class="fas fa-shield-alt" style="font-size: 32px; color: #C92127; margin-bottom: 12px;"></i>
                    <h4 style="font-size: 15px; color: #333; margin: 0 0 8px 0;">Sách Chính Hãng 100%</h4>
                    <p style="font-size: 13px; color: #666; margin: 0; line-height: 1.5;">Liên kết trực tiếp với các NXB uy tín: Kim Đồng, Trẻ, Nhã Nam...</p>
                </div>

                <div style="text-align: center; padding: 15px;">
                    <i class="fas fa-truck-fast" style="font-size: 32px; color: #0F62FE; margin-bottom: 12px;"></i>
                    <h4 style="font-size: 15px; color: #333; margin: 0 0 8px 0;">Giao Hàng Siêu Tốc</h4>
                    <p style="font-size: 13px; color: #666; margin: 0; line-height: 1.5;">Đóng gói màng bọc xốp chống sốc cẩn thận, giao hàng toàn quốc nhanh chóng.</p>
                </div>

                <div style="text-align: center; padding: 15px;">
                    <i class="fas fa-tags" style="font-size: 32px; color: #e67e22; margin-bottom: 12px;"></i>
                    <h4 style="font-size: 15px; color: #333; margin: 0 0 8px 0;">Giá Cả Ưu Đãi</h4>
                    <p style="font-size: 13px; color: #666; margin: 0; line-height: 1.5;">Luôn có chương trình giảm giá, Voucher ưu đãi hàng tuần cho khách hàng.</p>
                </div>

                <div style="text-align: center; padding: 15px;">
                    <i class="fas fa-headset" style="font-size: 32px; color: #28a745; margin-bottom: 12px;"></i>
                    <h4 style="font-size: 15px; color: #333; margin: 0 0 8px 0;">Hỗ Trợ Nhanh Chóng</h4>
                    <p style="font-size: 13px; color: #666; margin: 0; line-height: 1.5;">Đội ngũ tư vấn tận tâm, phản hồi thắc mắc & hỗ trợ đổi trả dễ dàng.</p>
                </div>

            </div>
        </div>

        <!-- 5. NHỮNG CON SỐ ẤN TƯỢNG -->
        <div style="background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%); color: white; padding: 35px; border-radius: 12px; margin-bottom: 35px; text-align: center;">
            <h2 style="font-size: 20px; margin: 0 0 25px 0; text-transform: uppercase; letter-spacing: 1px;">
                Con Số Ấn Tượng
            </h2>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div>
                    <div style="font-size: 32px; font-weight: bold; color: #f1c40f;">10.000+</div>
                    <div style="font-size: 13.5px; opacity: 0.85; margin-top: 5px;">Đầu Sách Đa Dạng</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold; color: #2ecc71;">50.000+</div>
                    <div style="font-size: 13.5px; opacity: 0.85; margin-top: 5px;">Khách Hàng Tin Tưởng</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold; color: #e74c3c;">99%</div>
                    <div style="font-size: 13.5px; opacity: 0.85; margin-top: 5px;">Đánh Giá Hài Lòng</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold; color: #3498db;">63</div>
                    <div style="font-size: 13.5px; opacity: 0.85; margin-top: 5px;">Tỉnh Thành Phục Vụ</div>
                </div>
            </div>
        </div>

        <!-- 6. LỜI KẾT & CTA -->
        <div style="background: #fff; padding: 35px; border-radius: 12px; border: 1px solid #e5e5e5; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <h3 style="font-size: 20px; color: #333; margin: 0 0 10px 0;">Sẵn Sàng Khám Phá Thế Giới Sách?</h3>
            <p style="font-size: 14px; color: #666; max-width: 600px; margin: 0 auto 20px auto;">
                Hãy để BookStore đồng hành cùng bạn trên con đường chinh phục tri thức và tìm kiếm những phút giây thư giãn tuyệt vời bên từng trang sách.
            </p>

            <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="sach_tatca.php" style="background: #C92127; color: white; padding: 12px 28px; border-radius: 25px; font-weight: bold; text-decoration: none; font-size: 14.5px; box-shadow: 0 4px 12px rgba(201, 33, 39, 0.25);">
                    <i class="fas fa-book-open"></i> Khám Phá Kho Sách Ngay
                </a>
                <a href="lienhe.php" style="background: #0F62FE; color: white; padding: 12px 28px; border-radius: 25px; font-weight: bold; text-decoration: none; font-size: 14.5px; box-shadow: 0 4px 12px rgba(15, 98, 254, 0.25);">
                    <i class="fas fa-paper-plane"></i> Liên Hệ Với Chúng Tôi
                </a>
            </div>
        </div>

    </div>

</div>

<?php
$conn->close();
include 'footer.php';
?>
