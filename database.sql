
CREATE DATABASE IF NOT EXISTS `customer_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `customer_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `care_notes`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `customer_groups`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customer_groups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(20) NOT NULL DEFAULT '#0d6efd'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT UNSIGNED NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `address` VARCHAR(255) NULL,
    `gender` ENUM('Nam', 'Nữ', 'Khác') NOT NULL DEFAULT 'Khác',
    `birthday` DATE NULL,
    `avatar` VARCHAR(255) NULL,
    `note` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,

    CONSTRAINT `fk_customers_group`
        FOREIGN KEY (`group_id`) REFERENCES `customer_groups`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_customers_user`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_customers_deleted_at` (`deleted_at`),
    INDEX `idx_customers_group_id` (`group_id`),
    INDEX `idx_customers_phone` (`phone`),
    INDEX `idx_customers_search` (`full_name`, `email`, `phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `care_notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_notes_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notes_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `idx_notes_customer_time` (`customer_id`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `target_table` VARCHAR(50) NOT NULL,
    `target_id` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_logs_latest` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$A57LxUJ20NtbNISbGrb64u2o8mhC/nqHaN0uAzUdsKW9t9zOIvEXa', 'Quản Trị Viên (Admin)', 'admin', '2024-01-01 08:00:00'),
(2, 'staff_nam', '$2y$10$OmujSyEJovRaTE59CoA79uugraQJQwr8SvOOkVffJcAkJ3PYQpfo.', 'Nguyễn Văn Nam (Staff)', 'staff', '2024-01-05 09:30:00'),
(3, 'staff_hoa', '$2y$10$OmujSyEJovRaTE59CoA79uugraQJQwr8SvOOkVffJcAkJ3PYQpfo.', 'Trần Thị Hoa (Staff)', 'staff', '2024-01-10 10:15:00');

INSERT INTO `customer_groups` (`id`, `name`, `color`) VALUES
(1, 'Khách hàng VIP', '#dc3545'),
(2, 'Khách hàng Tiềm năng', '#0d6efd'),
(3, 'Khách hàng Thân thiết', '#198754');

INSERT INTO `customers` (`id`, `group_id`, `full_name`, `email`, `phone`, `address`, `gender`, `birthday`, `avatar`, `note`, `created_by`, `created_at`, `deleted_at`) VALUES
(1, 1, 'Nguyễn An Khang', 'ankhang@gmail.com', '0912345671', '124 Phố Huế, Q. Hai Bà Trưng, Hà Nội', 'Nam', '1988-04-12', NULL, 'Khách hàng đầu tư lớn, ưu tiên hỗ trợ 24/7', 1, '2024-01-15 08:30:00', NULL),
(2, 1, 'Trần Bảo Ngọc', 'baongoc@gmail.com', '0988223344', '45 Lê Duẩn, P. Bến Nghé, Quận 1, TP.HCM', 'Nữ', '1992-09-20', NULL, 'Quan tâm gói dịch vụ cao cấp hàng năm', 2, '2024-01-16 09:00:00', NULL),
(3, 2, 'Lê Hoàng Minh', 'minh.le@outlook.com', '0903112233', '88 Trần Phú, Q. Hải Châu, Đà Nẵng', 'Nam', '1995-11-05', NULL, 'Cần tư vấn thêm về chính sách chiết khấu', 2, '2024-01-18 14:20:00', NULL),
(4, 3, 'Phạm Quỳnh Anh', 'quynhanh.pham@gmail.com', '0934556677', '12 Nguyễn Trãi, Q. Thanh Xuân, Hà Nội', 'Nữ', '1990-02-14', NULL, 'Đã mua hàng 5 lần trong năm 2023', 3, '2024-01-20 10:10:00', NULL),
(5, 1, 'Vũ Đình Trọng', 'trong.vu@fpt.com.vn', '0977889900', '350 Điện Biên Phủ, P. 11, Quận 10, TP.HCM', 'Nam', '1985-07-28', NULL, 'Giám đốc doanh nghiệp vừa và nhỏ', 1, '2024-01-22 16:45:00', NULL),
(6, 2, 'Hoàng Thùy Linh', 'thuylinh.h@gmail.com', '0945667788', '73 Nguyễn Văn Cừ, Q. Ninh Kiều, Cần Thơ', 'Nữ', '1994-12-03', NULL, 'Nhận thông tin qua chiến dịch Facebook Ads', 3, '2024-02-01 08:45:00', NULL),
(7, 3, 'Đặng Quốc Huy', 'huy.dang@yahoo.com', '0922334455', '56 Lạch Tray, Q. Ngô Quyền, Hải Phòng', 'Nam', '1989-05-17', NULL, 'Khách hàng giới thiệu thêm 2 đối tác mới', 2, '2024-02-03 11:20:00', NULL),
(8, 2, 'Bùi Tuyết Mai', 'tuyetmai.bui@gmail.com', '0966778899', '210 Cầu Giấy, Q. Cầu Giấy, Hà Nội', 'Nữ', '1997-08-22', NULL, 'Sinh viên mới tốt nghiệp, chuộng gói tiết kiệm', 3, '2024-02-05 15:30:00', NULL),
(9, 1, 'Đỗ Mạnh Cường', 'cuong.do@gmail.com', '0919887766', '18 Hai Bà Trưng, TP. Nha Trang, Khánh Hòa', 'Nam', '1983-03-30', NULL, 'Khách hàng VIP ngành khách sạn', 1, '2024-02-08 09:15:00', NULL),
(10, 3, 'Ngô Thanh Vân', 'thanhvan.ngo@gmail.com', '0983445566', '99 Nguyễn Thị Minh Khai, Quận 3, TP.HCM', 'Nữ', '1987-10-10', NULL, 'Yêu cầu hóa đơn VAT đầy đủ sau mỗi đơn', 2, '2024-02-10 13:40:00', NULL),
(11, 2, 'Dương Gia Hưng', 'giahung.duong@gmail.com', '0908776655', '42 Quang Trung, TP. Vinh, Nghệ An', 'Nam', '1993-06-18', NULL, 'Hẹn gọi lại vào đầu tuần sau để chốt hợp đồng', 3, '2024-02-12 10:00:00', NULL),
(12, 1, 'Lý Mỹ Dung', 'mydung.ly@gmail.com', '0938112244', '15 Lê Lợi, TP. Huế, Thừa Thiên Huế', 'Nữ', '1991-01-25', NULL, 'Thường xuyên thanh toán trước hạn', 1, '2024-02-15 14:10:00', NULL),
(13, 3, 'Hồ Văn Long', 'vanlong.ho@gmail.com', '0971223344', '80 Hùng Vương, TP. Buôn Ma Thuột, Đắk Lắk', 'Nam', '1986-11-12', NULL, 'Khách hàng thân thiết từ năm 2021', 2, '2024-02-18 16:00:00', NULL),
(14, 2, 'Cao Bích Phương', 'bichphuong.cao@gmail.com', '0943998877', '29 Bà Triệu, TP. Vũng Tàu, BR-VT', 'Nữ', '1996-09-09', NULL, 'Cần gửi thêm tài liệu brochure bản cứng', 3, '2024-02-20 09:20:00', NULL),
(15, 1, 'Phan Hữu Tài', 'huutai.phan@gmail.com', '0918665544', '105 Hoàng Hoa Thám, TP. Đà Lạt, Lâm Đồng', 'Nam', '1982-12-25', NULL, 'Chủ trang trại nông sản công nghệ cao', 1, '2024-02-22 15:50:00', NULL),
(16, 3, 'Tạ Kiều Trang', 'kieutrang.ta@gmail.com', '0985332211', '67 Lý Thường Kiệt, TP. Thái Nguyên', 'Nữ', '1993-04-04', NULL, 'Giao tiếp nhẹ nhàng, cần CSKH định kỳ', 2, '2024-02-25 10:30:00', NULL),
(17, 2, 'Lương Thế Vinh', 'thevinh.luong@gmail.com', '0909554433', '152 Cách Mạng Tháng 8, P. 10, Quận 3, TP.HCM', 'Nam', '1998-03-15', NULL, 'Đang quan tâm bản dùng thử (Trial)', 3, '2024-02-27 11:15:00', NULL),
(18, 1, 'Đoàn Kim Oanh', 'kimoanh.doan@gmail.com', '0937443322', '38 Trần Hưng Đạo, Q. Hoàn Kiếm, Hà Nội', 'Nữ', '1984-08-08', NULL, 'Thích nhận ưu đãi qua SMS và Email', 1, '2024-03-01 08:30:00', NULL),
(19, 3, 'Trịnh Đức Thịnh', 'ducthinh.trinh@gmail.com', '0979114477', '14 Nguyễn Văn Cừ, TP. Hạ Long, Quảng Ninh', 'Nam', '1990-07-21', NULL, 'Phản hồi dịch vụ rất tích cực', 2, '2024-03-03 14:00:00', NULL),
(20, 2, 'Mai Ngọc Lan', 'ngoclan.mai@gmail.com', '0948225588', '51 Lê Hồng Phong, TP. Quy Nhơn, Bình Định', 'Nữ', '1995-10-30', NULL, 'Đang so sánh tính năng với đối thủ cạnh tranh', 3, '2024-03-05 09:40:00', NULL),
(21, 1, 'Nguyễn Tiến Dũng', 'tiendung.nguyen@gmail.com', '0917336699', '204 Võ Thị Sáu, Quận 3, TP.HCM', 'Nam', '1987-05-19', NULL, 'Ký hợp đồng 3 năm liên tiếp', 1, '2024-03-08 16:30:00', NULL),
(22, 3, 'Vũ Hải Yến', 'haiyen.vu@gmail.com', '0986774411', '89 Kim Mã, Q. Ba Đình, Hà Nội', 'Nữ', '1992-06-06', NULL, 'Khách hàng thân thiện, sinh nhật tháng 6', 2, '2024-03-10 10:45:00', NULL),
(23, 2, 'Chu Tuấn Kiệt', 'tuankiet.chu@gmail.com', '0901889922', '12 Nguyễn Đình Chiểu, TP. Phan Thiết, Bình Thuận', 'Nam', '1999-01-11', NULL, 'Khách hàng tự tìm đến qua website công ty', 3, '2024-03-12 13:20:00', NULL),
(24, 1, 'Phùng Thanh Nga', 'thanhnga.phung@gmail.com', '0932447700', '77 Đại lộ Bình Dương, TP. Thủ Dầu Một, Bình Dương', 'Nữ', '1986-09-14', NULL, 'Đối tác chiến lược tại khu vực Đông Nam Bộ', 1, '2024-03-15 15:10:00', NULL),
(25, 3, 'Lâm Hoài Bắc', 'hoaibac.lam@gmail.com', '0975663322', '33 Tôn Đức Thắng, Q. Liên Chiểu, Đà Nẵng', 'Nam', '1991-03-23', NULL, 'Hài lòng với tốc độ giải quyết khiếu nại', 2, '2024-03-18 09:10:00', NULL),
(26, 2, 'Đinh Thị Thu', 'thuthu.dinh@gmail.com', '0941558833', '90 Trần Phú, TP. Thanh Hóa', 'Nữ', '1994-11-28', NULL, 'Yêu cầu tư vấn viên gọi điện trước 11h sáng', 3, '2024-03-20 14:40:00', NULL),
(27, 3, 'Võ Quốc Toàn', 'quoctoan.vo@gmail.com', '0928441155', '64 Lê Duẩn, TP. Pleiku, Gia Lai', 'Nam', '1989-12-16', NULL, 'Khách hàng kỳ cựu', 1, '2024-03-22 11:00:00', NULL),

(28, 2, 'Trương Văn Bách', 'vanbach.truong@test.com', '0905123456', 'Số 5 Đinh Tiên Hoàng, Q. 1, TP.HCM', 'Nam', '1993-02-02', NULL, 'Khách tạm ngưng hoạt động do chuyển văn phòng', 2, '2024-01-10 09:00:00', '2024-03-25 10:15:00'),
(29, 3, 'Nguyễn Lệ Hằng', 'lehang.nguyen@test.com', '0981654321', '88 Lê Trọng Tấn, Q. Tân Phú, TP.HCM', 'Nữ', '1996-07-17', NULL, 'Trùng số điện thoại khi nhập liệu (nhập nhầm)', 3, '2024-02-12 11:30:00', '2024-03-26 14:20:00'),
(30, 1, 'Phạm Anh Khoa', 'anhkhoa.pham@test.com', '0938789012', '12 Hàng Bạc, Q. Hoàn Kiếm, Hà Nội', 'Nam', '1985-05-05', NULL, 'Đã chuyển đổi sang mô hình công ty liên kết', 1, '2024-02-28 16:00:00', '2024-03-27 09:50:00');

INSERT INTO `care_notes` (`customer_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 'Đã gặp mặt trực tiếp tại văn phòng. Khách hàng đồng ý gia hạn gói dịch vụ năm 2024.', '2024-01-20 14:00:00'),
(1, 2, 'Gọi điện chúc mừng sinh nhật khách hàng và gửi voucher tri ân giảm 15%.', '2024-04-12 09:30:00'),
(2, 2, 'Tư vấn chi tiết về tính năng xuất báo cáo nâng cao, khách phản hồi rất hứng thú.', '2024-01-25 10:15:00'),
(3, 2, 'Gửi email báo giá qua minh.le@outlook.com. Đang đợi sếp của khách duyệt ngân sách.', '2024-01-22 15:00:00'),
(4, 3, 'Hỗ trợ khách hàng đổi địa chỉ nhận tài liệu và cập nhật thông tin thành viên.', '2024-02-02 11:20:00'),
(5, 1, 'Thương thảo hợp đồng đại lý cấp 1. Hẹn cuộc họp chuyên sâu với ban giám đốc tuần tới.', '2024-02-10 16:30:00'),
(7, 2, 'Khách hàng gọi điện khen ngợi bộ phận hỗ trợ kỹ thuật nhiệt tình, nhanh chóng.', '2024-02-15 08:45:00'),
(9, 1, 'Tặng quà tết tri ân khách hàng VIP đặc biệt.', '2024-02-08 10:00:00'),
(10, 2, 'Đã xuất và gửi bản mềm hóa đơn điện tử VAT cho kế toán của khách qua email.', '2024-02-11 09:00:00'),
(11, 3, 'Gọi điện nhắc lịch hẹn demo phần mềm. Khách xác nhận thứ Ba lúc 9h sáng.', '2024-02-14 14:10:00'),
(15, 1, 'Khách hàng quan tâm mô hình tưới tiêu tự động liên kết phần mềm quản lý.', '2024-02-25 15:45:00'),
(18, 1, 'Gửi tin nhắn SMS mã ưu đãi ngày 8/3, khách đã phản hồi cảm ơn.', '2024-03-08 08:30:00');

INSERT INTO `activity_logs` (`user_id`, `action`, `target_table`, `target_id`, `created_at`) VALUES
(1, 'LOGIN', 'auth', 1, '2024-03-25 08:00:00'),
(1, 'CREATE', 'customer_groups', 1, '2024-03-25 08:15:00'),
(1, 'CREATE', 'customer_groups', 2, '2024-03-25 08:16:00'),
(1, 'CREATE', 'customer_groups', 3, '2024-03-25 08:17:00'),
(2, 'LOGIN', 'auth', 2, '2024-03-25 08:30:00'),
(2, 'CREATE', 'customers', 1, '2024-03-25 08:40:00'),
(3, 'LOGIN', 'auth', 3, '2024-03-25 09:00:00'),
(3, 'CREATE', 'customers', 4, '2024-03-25 09:15:00'),
(2, 'CREATE', 'care_notes', 1, '2024-03-25 09:30:00'),
(1, 'UPDATE', 'customers', 1, '2024-03-25 10:00:00'),
(1, 'DELETE', 'customers', 28, '2024-03-25 10:15:00'),
(1, 'DELETE', 'customers', 29, '2024-03-26 14:20:00'),
(1, 'DELETE', 'customers', 30, '2024-03-27 09:50:00'),
(2, 'CREATE', 'care_notes', 4, '2024-03-27 10:30:00'),
(1, 'LOGIN', 'auth', 1, '2024-03-27 14:00:00');
