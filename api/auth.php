<?php
/**
 * api/auth.php
 * Xử lý đăng nhập, đăng xuất và lấy thông tin người dùng hiện tại
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'login':
        // Đăng nhập người dùng
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(false, 'Phương thức không hợp lệ!', [], 405);
        }

        $username = clean_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            json_response(false, 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!', [], 422);
        }

        // Truy vấn tìm người dùng theo username
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Kiểm tra mật khẩu bằng hàm an toàn password_verify()
        if (!$user || !password_verify($password, $user['password'])) {
            json_response(false, 'Tên đăng nhập hoặc mật khẩu không chính xác!', [], 401);
        }

        // Đăng nhập thành công -> Lưu session
        session_regenerate_id(true); // Chống tấn công Session Fixation
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        // Ghi nhật ký đăng nhập
        log_activity($pdo, $user['id'], 'LOGIN', 'auth', $user['id']);

        json_response(true, 'Đăng nhập thành công! Đang chuyển hướng...', [
            'redirect' => (defined('BASE_URL') ? BASE_URL : '') . 'index.php'
        ]);
        break;

    case 'logout':
        // Đăng xuất và xóa sạch session
        if (is_logged_in()) {
            log_activity($pdo, $_SESSION['user_id'], 'LOGOUT', 'auth', $_SESSION['user_id']);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        json_response(true, 'Đăng xuất thành công!', [
            'redirect' => (defined('BASE_URL') ? BASE_URL : '') . 'login.php'
        ]);
        break;

    case 'current_user':
        // Lấy thông tin user hiện tại
        require_login_api();
        json_response(true, 'Thành công', current_user());
        break;

    default:
        json_response(false, 'Hành động không hợp lệ!', [], 400);
        break;
}
