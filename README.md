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

## 2. CÁCH CHẠY DỰ ÁN (CHỈ 1 CÚ NHẤP CHUỘT)

### Cách 1: Chạy tự động bằng file `run.bat` (Khuyên dùng - Nhanh nhất)
1. Bật **MySQL** trong XAMPP (hoặc Laragon).
2. Nhấp đúp chuột vào file **`run.bat`** trong thư mục dự án.
3. Script sẽ tự động:
   - Phát hiện PHP trên máy.
   - Tự động kiểm tra và import CSDL `database.sql` nếu chưa có (không cần vào phpMyAdmin thao tác thủ công).
   - Khởi động máy chủ và tự động mở trình duyệt vào trang đăng nhập: `http://localhost:8000/login.php`.

---

### Cách 2: Chạy truyền thống qua thư mục `htdocs` của XAMPP

Hệ thống được thiết kế **100% Portable**, tự động nhận diện thư mục cài đặt qua hằng số `BASE_URL` và sử dụng đường dẫn tương đối.

#### Bước 1: Khởi động XAMPP
- Mở **XAMPP Control Panel**, bấm **Start** cả **Apache** và **MySQL**.

#### Bước 2: Copy dự án vào `htdocs`
- Copy thư mục vào `C:\xampp\htdocs\crm_web`.

#### Bước 3: Import CSDL
- Truy cập `http://localhost/phpmyadmin` -> bấm tab **Import** -> chọn file `database.sql` -> bấm **Import**.

#### Bước 4: Mở trình duyệt
- Truy cập `http://localhost/crm_web/login.php`.


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
11. **Chatbot AI & Trợ lý ảo CRM (Hybrid)**: Khung chat nổi hiện đại ở góc dưới màn hình, tích hợp AJAX tra cứu dữ liệu CSDL thời gian thực (thống kê KPI, danh sách VIP, tra cứu khách hàng theo tên/SĐT, hướng dẫn nghiệp vụ) chạy 100% offline không cần tiến trình nền; hỗ trợ tích hợp Google Gemini API qua file `config/chatbot.php`.

