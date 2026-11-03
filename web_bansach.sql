-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 31, 2026 lúc 04:47 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `web_bansach`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banner_slide`
--

CREATE TABLE `banner_slide` (
  `banner_id` int(11) NOT NULL,
  `tieude` varchar(255) DEFAULT NULL,
  `hinhanh` varchar(255) NOT NULL,
  `lienket` varchar(255) DEFAULT NULL,
  `thutu` int(11) DEFAULT 0,
  `trangthai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `chitiet_id` int(11) NOT NULL,
  `donhang_id` int(11) DEFAULT NULL,
  `sach_id` int(11) DEFAULT NULL,
  `soluong` int(11) NOT NULL,
  `gia` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_gia`
--

CREATE TABLE `danh_gia` (
  `danhgia_id` int(11) NOT NULL,
  `nguoidung_id` int(11) DEFAULT NULL,
  `sach_id` int(11) DEFAULT NULL,
  `sosao` int(11) DEFAULT NULL,
  `binhluan` text DEFAULT NULL,
  `luotthich` int(11) NOT NULL DEFAULT 0,
  `baocao` int(11) NOT NULL DEFAULT 0,
  `trangthai` varchar(20) NOT NULL DEFAULT 'Hiển thị',
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `doanh_thu`
--

CREATE TABLE `doanh_thu` (
  `doanhthu_id` int(11) NOT NULL,
  `kho_id` int(11) NOT NULL,
  `sach_id` int(11) NOT NULL,
  `tensach` varchar(255) DEFAULT NULL,
  `soluongban` int(11) DEFAULT NULL,
  `gianhap` int(11) NOT NULL,
  `giaban` int(11) DEFAULT NULL,
  `ngayban` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

CREATE TABLE `don_hang` (
  `donhang_id` int(11) NOT NULL,
  `nguoidung_id` int(11) DEFAULT NULL,
  `ten_nguoinhan` varchar(100) DEFAULT NULL,
  `sdt_nguoinhan` varchar(20) DEFAULT NULL,
  `diachi_giaohang` text DEFAULT NULL,
  `phuongthuc_thanhtoan` varchar(50) DEFAULT 'COD',
  `ghichu` text DEFAULT NULL,
  `ngaydat` timestamp NOT NULL DEFAULT current_timestamp(),
  `tongtien` decimal(12,2) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `sotien_giam` decimal(10,2) DEFAULT 0.00,
  `trangthai` varchar(30) DEFAULT 'choduyet'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `gio_hang`
--

CREATE TABLE `gio_hang` (
  `giohang_id` int(11) NOT NULL,
  `nguoidung_id` int(11) DEFAULT NULL,
  `sach_id` int(11) DEFAULT NULL,
  `soluong` int(11) DEFAULT 1,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kho`
--

CREATE TABLE `kho` (
  `kho_id` int(11) NOT NULL,
  `nhacungcap_id` int(11) DEFAULT NULL,
  `tensach` varchar(255) DEFAULT NULL,
  `gianhap` int(11) DEFAULT NULL,
  `soluong` int(11) DEFAULT NULL,
  `ngaytao` datetime DEFAULT current_timestamp(),
  `ngaycapnhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lien_he`
--

CREATE TABLE `lien_he` (
  `lienhe_id` int(11) NOT NULL,
  `hoten` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `sodienthoai` varchar(20) DEFAULT NULL,
  `noidung` text NOT NULL,
  `ngaygui` timestamp NOT NULL DEFAULT current_timestamp(),
  `trangthai` varchar(20) DEFAULT 'chuxuly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `nguoidung_id` int(11) NOT NULL,
  `taikhoan` varchar(50) NOT NULL,
  `hoten` varchar(100) DEFAULT NULL,
  `matkhau` varchar(255) NOT NULL,
  `sodienthoai_email` varchar(150) DEFAULT NULL,
  `diachi` varchar(255) DEFAULT NULL,
  `vaitro` varchar(20) DEFAULT 'khachhang',
  `trangthai` tinyint(1) DEFAULT 1,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nha_cung_cap`
--

CREATE TABLE `nha_cung_cap` (
  `nhacungcap_id` int(11) NOT NULL,
  `ten_ncc` varchar(255) NOT NULL,
  `sdt` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `diachi` varchar(255) DEFAULT NULL,
  `ghichu` text DEFAULT NULL,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sach`
--

CREATE TABLE `sach` (
  `sach_id` int(11) NOT NULL,
  `kho_id` int(11) NOT NULL,
  `tensach` varchar(255) NOT NULL,
  `tacgia` varchar(150) DEFAULT NULL,
  `nhaxuatban` varchar(150) DEFAULT NULL,
  `namxuatban` int(4) DEFAULT NULL,
  `so_trang` int(11) DEFAULT NULL,
  `gia` decimal(10,2) NOT NULL,
  `phantram_giamgia` int(11) NOT NULL DEFAULT 0,
  `daban` int(11) NOT NULL DEFAULT 0,
  `luotxem` int(11) NOT NULL DEFAULT 0,
  `mota` text DEFAULT NULL,
  `hinhanh` varchar(255) DEFAULT NULL,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sach_theloai`
--

CREATE TABLE `sach_theloai` (
  `sachtheloai_id` int(11) NOT NULL,
  `sach_id` int(11) NOT NULL,
  `theloai_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `the_loai`
--

CREATE TABLE `the_loai` (
  `theloai_id` int(11) NOT NULL,
  `tentheloai` varchar(255) NOT NULL,
  `mota` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher`
--

CREATE TABLE `voucher` (
  `voucher_id` int(11) NOT NULL,
  `ma` varchar(50) NOT NULL,
  `loai_giam` enum('phantram','tienmat') NOT NULL DEFAULT 'phantram',
  `giatri` decimal(10,2) NOT NULL,
  `dieukien_toithieu` decimal(10,2) DEFAULT 0.00,
  `soluong` int(11) NOT NULL DEFAULT 0,
  `ngay_batdau` date NOT NULL,
  `ngay_ketthuc` date NOT NULL,
  `trangthai` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeu_thich`
--

CREATE TABLE `yeu_thich` (
  `yeuthich_id` int(11) NOT NULL,
  `nguoidung_id` int(11) NOT NULL,
  `sach_id` int(11) NOT NULL,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `banner_slide`
--
ALTER TABLE `banner_slide`
  ADD PRIMARY KEY (`banner_id`);

--
-- Chỉ mục cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`chitiet_id`),
  ADD KEY `donhang_id` (`donhang_id`),
  ADD KEY `sach_id` (`sach_id`);

--
-- Chỉ mục cho bảng `danh_gia`
--
ALTER TABLE `danh_gia`
  ADD PRIMARY KEY (`danhgia_id`),
  ADD KEY `nguoidung_id` (`nguoidung_id`),
  ADD KEY `sach_id` (`sach_id`);

--
-- Chỉ mục cho bảng `doanh_thu`
--
ALTER TABLE `doanh_thu`
  ADD PRIMARY KEY (`doanhthu_id`),
  ADD KEY `kho_id` (`kho_id`),
  ADD KEY `sach_id` (`sach_id`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`donhang_id`),
  ADD KEY `nguoidung_id` (`nguoidung_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Chỉ mục cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD PRIMARY KEY (`giohang_id`),
  ADD KEY `nguoidung_id` (`nguoidung_id`),
  ADD KEY `sach_id` (`sach_id`);

--
-- Chỉ mục cho bảng `kho`
--
ALTER TABLE `kho`
  ADD PRIMARY KEY (`kho_id`),
  ADD KEY `nhacungcap_id` (`nhacungcap_id`);

--
-- Chỉ mục cho bảng `lien_he`
--
ALTER TABLE `lien_he`
  ADD PRIMARY KEY (`lienhe_id`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`nguoidung_id`),
  ADD UNIQUE KEY `taikhoan` (`taikhoan`);

--
-- Chỉ mục cho bảng `nha_cung_cap`
--
ALTER TABLE `nha_cung_cap`
  ADD PRIMARY KEY (`nhacungcap_id`);

--
-- Chỉ mục cho bảng `sach`
--
ALTER TABLE `sach`
  ADD PRIMARY KEY (`sach_id`),
  ADD KEY `kho_id` (`kho_id`);

--
-- Chỉ mục cho bảng `sach_theloai`
--
ALTER TABLE `sach_theloai`
  ADD PRIMARY KEY (`sachtheloai_id`),
  ADD KEY `sach_id` (`sach_id`),
  ADD KEY `theloai_id` (`theloai_id`);

--
-- Chỉ mục cho bảng `the_loai`
--
ALTER TABLE `the_loai`
  ADD PRIMARY KEY (`theloai_id`);

--
-- Chỉ mục cho bảng `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`voucher_id`),
  ADD UNIQUE KEY `ma` (`ma`);

--
-- Chỉ mục cho bảng `yeu_thich`
--
ALTER TABLE `yeu_thich`
  ADD PRIMARY KEY (`yeuthich_id`),
  ADD KEY `nguoidung_id` (`nguoidung_id`),
  ADD KEY `sach_id` (`sach_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `banner_slide`
--
ALTER TABLE `banner_slide`
  MODIFY `banner_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `chitiet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `danh_gia`
--
ALTER TABLE `danh_gia`
  MODIFY `danhgia_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `doanh_thu`
--
ALTER TABLE `doanh_thu`
  MODIFY `doanhthu_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `donhang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  MODIFY `giohang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `kho`
--
ALTER TABLE `kho`
  MODIFY `kho_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lien_he`
--
ALTER TABLE `lien_he`
  MODIFY `lienhe_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `nguoidung_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nha_cung_cap`
--
ALTER TABLE `nha_cung_cap`
  MODIFY `nhacungcap_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sach`
--
ALTER TABLE `sach`
  MODIFY `sach_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sach_theloai`
--
ALTER TABLE `sach_theloai`
  MODIFY `sachtheloai_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `the_loai`
--
ALTER TABLE `the_loai`
  MODIFY `theloai_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `voucher`
--
ALTER TABLE `voucher`
  MODIFY `voucher_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `yeu_thich`
--
ALTER TABLE `yeu_thich`
  MODIFY `yeuthich_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
