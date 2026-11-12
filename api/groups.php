<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_api();
$pdo = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if (in_array($action, ['create', 'update', 'delete'])) {
    validate_csrf_api();
    require_admin_api();
}

switch ($action) {

    case 'list':
        $sql = "
            SELECT
                g.id, g.name, g.color,
                COUNT(c.id) AS total_customers
            FROM customer_groups g
            LEFT JOIN customers c ON g.id = c.group_id AND c.deleted_at IS NULL
            GROUP BY g.id, g.name, g.color
            ORDER BY g.id ASC
        ";
        $stmt = $pdo->query($sql);
        $groups = $stmt->fetchAll();

        json_response(true, 'Thành công', $groups);
        break;

    case 'create':
        $name  = clean_input($_POST['name'] ?? '');
        $color = clean_input($_POST['color'] ?? '#0d6efd');

        if (empty($name)) {
            json_response(false, 'Tên nhóm không được để trống!', [], 422);
        }

        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            $color = '#0d6efd';
        }

        $stmt = $pdo->prepare("INSERT INTO customer_groups (name, color) VALUES (?, ?)");
        $stmt->execute([$name, $color]);
        $newId = (int)$pdo->lastInsertId();

        log_activity($pdo, $currentUserId, 'CREATE', 'customer_groups', $newId);
        json_response(true, 'Thêm nhóm khách hàng thành công!', ['id' => $newId], 201);
        break;

    case 'update':
        $id    = (int)($_POST['id'] ?? 0);
        $name  = clean_input($_POST['name'] ?? '');
        $color = clean_input($_POST['color'] ?? '#0d6efd');

        if ($id <= 0 || empty($name)) {
            json_response(false, 'Dữ liệu nhóm không hợp lệ!', [], 422);
        }

        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            $color = '#0d6efd';
        }

        $stmt = $pdo->prepare("UPDATE customer_groups SET name = ?, color = ? WHERE id = ?");
        $stmt->execute([$name, $color, $id]);

        log_activity($pdo, $currentUserId, 'UPDATE', 'customer_groups', $id);
        json_response(true, 'Cập nhật nhóm khách hàng thành công!');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(false, 'Mã nhóm không hợp lệ!', [], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM customer_groups WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            log_activity($pdo, $currentUserId, 'DELETE', 'customer_groups', $id);
            json_response(true, 'Đã xóa nhóm khách hàng thành công!');
        } else {
            json_response(false, 'Nhóm không tồn tại hoặc đã bị xóa!', [], 404);
        }
        break;

    default:
        json_response(false, 'Hành động không hợp lệ!', [], 400);
        break;
}
