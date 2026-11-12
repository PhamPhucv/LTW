<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_api();
$pdo = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {

    case 'list':
        $customerId = (int)($_GET['customer_id'] ?? 0);
        if ($customerId <= 0) {
            json_response(false, 'Mã khách hàng không hợp lệ!', [], 400);
        }

        $stmt = $pdo->prepare("
            SELECT
                n.id, n.customer_id, n.user_id, n.content, n.created_at,
                u.full_name AS staff_name, u.role AS staff_role
            FROM care_notes n
            JOIN users u ON n.user_id = u.id
            WHERE n.customer_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$customerId]);
        $notes = $stmt->fetchAll();

        json_response(true, 'Thành công', $notes);
        break;

    case 'create':
        validate_csrf_api();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $content    = clean_input($_POST['content'] ?? '');

        if ($customerId <= 0) {
            json_response(false, 'Mã khách hàng không hợp lệ!', [], 400);
        }
        if (empty($content)) {
            json_response(false, 'Nội dung ghi chú chăm sóc không được để trống!', [], 422);
        }

        $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? LIMIT 1");
        $checkStmt->execute([$customerId]);
        if (!$checkStmt->fetch()) {
            json_response(false, 'Khách hàng không tồn tại!', [], 404);
        }

        $stmt = $pdo->prepare("
            INSERT INTO care_notes (customer_id, user_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$customerId, $currentUserId, $content]);
        $newNoteId = (int)$pdo->lastInsertId();

        log_activity($pdo, $currentUserId, 'CREATE', 'care_notes', $customerId);

        $getStmt = $pdo->prepare("
            SELECT
                n.id, n.customer_id, n.user_id, n.content, n.created_at,
                u.full_name AS staff_name, u.role AS staff_role
            FROM care_notes n
            JOIN users u ON n.user_id = u.id
            WHERE n.id = ?
            LIMIT 1
        ");
        $getStmt->execute([$newNoteId]);
        $createdNote = $getStmt->fetch();

        json_response(true, 'Thêm ghi chú chăm sóc thành công!', $createdNote, 201);
        break;

    default:
        json_response(false, 'Hành động không hợp lệ!', [], 400);
        break;
}
