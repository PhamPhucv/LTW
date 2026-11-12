<?php
/**
 * includes/functions.php
 * Tập hợp các hàm tiện ích dùng chung trong toàn bộ hệ thống
 */

/**
 * Trả về phản hồi JSON chuẩn hóa và kết thúc thực thi
 */
function json_response(bool $success, string $message = '', array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Escape chuỗi để chống tấn công XSS (Cross-Site Scripting) khi in ra HTML
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Làm sạch dữ liệu đầu vào (cắt khoảng trắng thừa)
 */
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return trim($data ?? '');
}

/**
 * Tự động ghi nhật ký vào bảng `activity_logs`
 */
function log_activity(PDO $pdo, ?int $userId, string $action, string $targetTable, ?int $targetId = null): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, target_table, target_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$userId, $action, $targetTable, $targetId]);
    } catch (PDOException $e) {
        // Không ngắt luồng chính nếu ghi log thất bại
        error_log("Lỗi ghi log hoạt động: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate định dạng số điện thoại Việt Nam (10 chữ số, bắt đầu bằng 03, 05, 07, 08, 09)
 */
function is_valid_vn_phone(string $phone): bool {
    $pattern = '/^(0)(3[2-9]|5[2689]|7[06-9]|8[1-9]|9[0-9])[0-9]{7}$/';
    return preg_match($pattern, $phone) === 1;
}

/**
 * Validate định dạng email chuẩn RFC
 */
function is_valid_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Xử lý upload ảnh đại diện an toàn
 * - Kiểm tra lỗi upload
 * - Kiểm tra dung lượng (tối đa 2MB)
 * - Kiểm tra đuôi file và MIME type thực tế bằng finfo
 * - Đổi tên file ngẫu nhiên chống ghi đè và mã độc
 */
function handle_avatar_upload(array $file): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Không chọn file
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi trong quá trình tải ảnh lên (Mã lỗi: ' . $file['error'] . ')');
    }

    // Giới hạn 2MB
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Kích thước ảnh đại diện không được vượt quá 2MB!');
    }

    // Kiểm tra định dạng cho phép
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedMimes)) {
        throw new Exception('Định dạng ảnh không hợp lệ! Chỉ chấp nhận JPG, PNG, WEBP hoặc GIF.');
    }

    // Đường dẫn thư mục lưu trữ
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Đổi tên file ngẫu nhiên chống trùng lặp
    $extension = $allowedMimes[$mimeType];
    $newFileName = 'avatar_' . uniqid('', true) . '.' . $extension;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Không thể lưu ảnh đại diện vào thư mục uploads!');
    }

    return 'uploads/avatars/' . $newFileName;
}

/**
 * Xóa file ảnh đại diện cũ khi người dùng upload ảnh mới hoặc xóa khách hàng
 */
function delete_avatar_file(?string $relativePath): void {
    if (empty($relativePath)) return;
    $fullPath = __DIR__ . '/../' . ltrim($relativePath, '/');
    if (file_exists($fullPath) && is_file($fullPath)) {
        @unlink($fullPath);
    }
}

/**
 * Định dạng ngày giờ chuẩn hiển thị tiếng Việt
 */
function format_datetime(?string $dateStr, string $format = 'd/m/Y H:i'): string {
    if (empty($dateStr)) return '-';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : '-';
}

/**
 * Định dạng ngày chuẩn d/m/Y
 */
function format_date(?string $dateStr): string {
    return format_datetime($dateStr, 'd/m/Y');
}
