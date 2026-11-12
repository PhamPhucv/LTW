<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['user_role']
    ];
}

function require_login_page(): void {
    if (!is_logged_in()) {
        $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : 'login.php';
        header('Location: ' . $loginUrl);
        exit;
    }
}

function require_login_api(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function require_admin_api(): void {
    require_login_api();
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền thực hiện hành động này (Chỉ dành cho Admin)!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function validate_csrf_api(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi bảo mật CSRF: Token không hợp lệ hoặc đã hết hạn!'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
