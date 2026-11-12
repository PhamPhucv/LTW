# CRM PRO - HỆ THỐNG QUẢN LÝ KHÁCH HÀNG MINI
> Đồ án môn: Lập trình Web  
> Công nghệ: PHP thuần (PDO), Bootstrap 5, MySQL (utf8mb4), jQuery AJAX  
> Kiến trúc: Portable (Chạy ngay trên mọi máy tính cài XAMPP mà không cần sửa cấu hình)

---

## 1. TÀI KHOẢN ĐĂNG NHẬP MẪU (SEED DATA)

| Tài khoản | Tên đăng nhập | Mật khẩu | Vai trò (Role) | Quyền hạn thực tế |
|:---|:---|:---|:---:|:---|
| **Quản trị viên (Admin)** | `admin` | `admin123` | `admin` | **Toàn quyền:** Thêm, Sửa, Xóa mềm, Khôi phục thùng rác, Quản lý nhóm, Xuất CSV, Xem logs. |
| **Nhân viên 1 (Staff)** | `staff_nam` | `staff123` | `staff` | **Hạn chế:** Xem danh sách, Thêm, Sửa, Chăm sóc KH, Xuất CSV. **KHÔNG** được xóa khách, **KHÔNG** xem thùng rác, **KHÔNG** sửa nhóm. |
| **Nhân viên 2 (Staff)** | `staff_hoa` | `staff123` | `staff` | Tương tự nhân viên 1. |

---

## 2. HƯỚNG DẪN CÀI ĐẶT & CHẠY TRÊN XAMPP (PORTABLE)

Hệ thống được thiết kế **100% Portable**, tự động nhận diện thư mục cài đặt qua hằng số `BASE_URL` và sử dụng đường dẫn tương đối. Bạn có thể copy thư mục vào bất kỳ đâu trong `htdocs` với bất kỳ tên gọi nào.

### Bước 1: Khởi động XAMPP
- Mở **XAMPP Control Panel**.
- Bấm **Start** ở cả 2 dịch vụ: **Apache** và **MySQL** (cả 2 chuyển sang màu xanh lá).

### Bước 2: Copy thư mục dự án vào `htdocs`
- Copy toàn bộ thư mục dự án này vào thư mục `htdocs` của XAMPP, ví dụ:
  `C:\xampp\htdocs\crm_web`
  *(Tên thư mục `crm_web` có thể đổi tùy ý, code sẽ tự động bắt đường dẫn).*

### Bước 3: Import Cơ sở dữ liệu duy nhất (`database.sql`)
- Mở trình duyệt, truy cập phpMyAdmin: `http://localhost/phpmyadmin`
- Bấm vào tab **Import** (hoặc **Nhập**).
- Bấm **Choose File** (Chọn tệp) và trỏ tới file: `database.sql` nằm ở thư mục gốc của dự án.
- Bấm nút **Import** ở cuối trang.
  *(File này đã chứa sẵn lệnh tạo CSDL `customer_db`, bảng mã `utf8mb4`, cấu trúc 5 bảng chuẩn, khóa ngoại, index và 30 khách hàng mẫu kèm 3 khách trong thùng rác).*

### Bước 4: Mở ứng dụng trên trình duyệt
- Truy cập vào đường dẫn:
  `http://localhost/crm_web/login.php`
- Đăng nhập với tài khoản:
  - Username: `admin`
  - Password: `admin123`

---

## 3. CẤU HÌNH HỆ THỐNG & TÍNH DI ĐỘNG (PORTABILITY)

- **Cấu hình CSDL duy nhất**: Toàn bộ thông số kết nối nằm tập trung tại file `config/database.php`:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'customer_db');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_PORT', '3306');
  ```
- **Hằng số `BASE_URL` tự động**: File `config/database.php` tự động tính toán đường dẫn tương đối dựa trên `$_SERVER['SCRIPT_NAME']`.
- **Không dùng đường dẫn tuyệt đối**: Mọi liên kết CSS, JS, API và chuyển hướng đều dùng `BASE_URL` và đường dẫn tương đối.
- **Chuẩn Tiếng Việt utf8mb4**:
  - CSDL thiết lập `utf8mb4_unicode_ci`.
  - Kết nối PDO khởi tạo lệnh `SET NAMES utf8mb4`.
  - Mọi phản hồi JSON gửi header `Content-Type: application/json; charset=utf-8` và cờ `JSON_UNESCAPED_UNICODE`.
  - File xuất CSV gửi chuỗi **UTF-8 BOM** (`\xEF\xBB\xBF`) để Excel hiển thị tiếng Việt hoàn hảo không bị vỡ font.

---

## 4. TỔNG QUAN TÍNH NĂNG ĐẠT ĐIỂM TỐI ĐA

1. **CRUD Khách hàng Modal + AJAX**: Toàn bộ thao tác thêm, xem chi tiết, sửa, xóa mềm và khôi phục đều dùng Bootstrap 5 Modal và jQuery AJAX, không bao giờ phải reload trang.
2. **Tìm kiếm Live Search (Debounce 300ms)**: Không gây nghẽn server khi người dùng gõ phím.
3. **Bộ lọc đa tiêu chí**: Lọc theo nhóm khách hàng, theo giới tính, và sắp xếp linh hoạt.
4. **Phân quyền người dùng (RBAC)**: Admin toàn quyền; Staff bị ẩn nút xóa và bị chặn cứng tại Backend (trả về mã HTTP 403 Forbidden).
5. **Xóa mềm (Soft Delete) & Thùng rác**: Cập nhật `deleted_at = NOW()`, có modal thùng rác riêng để khôi phục (`deleted_at = NULL`).
6. **Lịch sử chăm sóc (`care_notes`)**: Xem timeline tương tác theo thời gian thực và thêm ghi chú mới tức thì.
7. **Nhật ký thao tác (`activity_logs`)**: Tự động lưu vết `CREATE`, `UPDATE`, `DELETE`, `RESTORE`, `LOGIN` và hiển thị 10 log gần nhất trên Dashboard.
8. **Dashboard & Biểu đồ Chart.js**: Thống kê 4 thẻ KPI và trực quan hóa phân bổ khách hàng theo từng nhóm.
9. **Xuất file CSV cho Excel**: Chuẩn UTF-8 BOM, hỗ trợ xuất toàn bộ hoặc theo bộ lọc tìm kiếm hiện tại.
10. **Bảo mật**: PDO Prepared Statements chống SQL Injection, băm mật khẩu BCRYPT với `password_hash()`, chống tấn công XSS bằng `htmlspecialchars()`, chống CSRF Token cho mọi request POST.
