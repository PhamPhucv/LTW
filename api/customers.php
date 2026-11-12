<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_api();
$pdo = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if (in_array($action, ['create', 'update', 'delete', 'restore'])) {
    validate_csrf_api();
}

switch ($action) {

    case 'list':
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = max(5, min(100, (int)($_GET['limit'] ?? 10)));
        $offset    = ($page - 1) * $limit;
        $search    = clean_input($_GET['search'] ?? '');
        $groupId   = !empty($_GET['group_id']) ? (int)$_GET['group_id'] : null;
        $gender    = clean_input($_GET['gender'] ?? '');
        $isTrash   = (int)($_GET['trash'] ?? 0) === 1;

        $where = [];
        $params = [];

        if ($isTrash) {
            $where[] = "c.deleted_at IS NOT NULL";
        } else {
            $where[] = "c.deleted_at IS NULL";
        }

        if (!empty($search)) {
            $where[] = "(c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($groupId)) {
            $where[] = "c.group_id = ?";
            $params[] = $groupId;
        }

        if (!empty($gender) && in_array($gender, ['Nam', 'Nữ', 'Khác'])) {
            $where[] = "c.gender = ?";
            $params[] = $gender;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $countSql = "SELECT COUNT(*) FROM customers c $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        $allowedSortCols = ['id', 'full_name', 'email', 'phone', 'created_at', 'birthday'];
        $sortBy = in_array($_GET['sort_by'] ?? '', $allowedSortCols) ? $_GET['sort_by'] : 'c.id';
        $sortDir = strtolower($_GET['sort_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $sql = "
            SELECT
                c.id, c.group_id, c.full_name, c.email, c.phone, c.address,
                c.gender, c.birthday, c.avatar, c.note, c.created_at, c.deleted_at,
                g.name AS group_name, g.color AS group_color,
                u.full_name AS created_by_name
            FROM customers c
            LEFT JOIN customer_groups g ON c.group_id = g.id
            LEFT JOIN users u ON c.created_by = u.id
            $whereClause
            ORDER BY {$sortBy} {$sortDir}
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();

        json_response(true, 'Lấy danh sách thành công', [
            'customers'    => $customers,
            'pagination'   => [
                'current_page' => $page,
                'total_pages'  => (int)$totalPages,
                'total_items'  => $totalItems,
                'limit'        => $limit
            ]
        ]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(false, 'ID khách hàng không hợp lệ!', [], 400);
        }

        $stmt = $pdo->prepare("
            SELECT
                c.*,
                g.name AS group_name, g.color AS group_color,
                u.full_name AS created_by_name
            FROM customers c
            LEFT JOIN customer_groups g ON c.group_id = g.id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            json_response(false, 'Không tìm thấy khách hàng yêu cầu!', [], 404);
        }

        json_response(true, 'Thành công', $customer);
        break;

    case 'create':
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email    = clean_input($_POST['email'] ?? '');
        $phone    = clean_input($_POST['phone'] ?? '');
        $groupId  = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $gender   = clean_input($_POST['gender'] ?? 'Khác');
        $birthday = !empty($_POST['birthday']) ? clean_input($_POST['birthday']) : null;
        $address  = clean_input($_POST['address'] ?? '');
        $note     = clean_input($_POST['note'] ?? '');

        if (empty($fullName)) {
            json_response(false, 'Họ và tên khách hàng không được để trống!', [], 422);
        }
        if (empty($email) || !is_valid_email($email)) {
            json_response(false, 'Email không đúng định dạng chuẩn!', [], 422);
        }
        if (empty($phone) || !is_valid_vn_phone($phone)) {
            json_response(false, 'Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại Việt Nam 10 chữ số (VD: 0912345678).', [], 422);
        }
        if (!in_array($gender, ['Nam', 'Nữ', 'Khác'])) {
            $gender = 'Khác';
        }

        $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            json_response(false, 'Email này đã tồn tại trong hệ thống! Vui lòng sử dụng email khác.', [], 409);
        }

        $avatarPath = null;
        try {
            if (isset($_FILES['avatar'])) {
                $avatarPath = handle_avatar_upload($_FILES['avatar']);
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }

        $insertSql = "
            INSERT INTO customers (group_id, full_name, email, phone, address, gender, birthday, avatar, note, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $pdo->prepare($insertSql);
        $success = $stmt->execute([
            $groupId, $fullName, $email, $phone, $address, $gender, $birthday, $avatarPath, $note, $currentUserId
        ]);

        if ($success) {
            $newCustomerId = (int)$pdo->lastInsertId();
            log_activity($pdo, $currentUserId, 'CREATE', 'customers', $newCustomerId);
            json_response(true, 'Thêm mới khách hàng thành công!', ['id' => $newCustomerId], 201);
        } else {
            json_response(false, 'Không thể thêm khách hàng vào hệ thống!', [], 500);
        }
        break;

    case 'update':
        $id       = (int)($_POST['id'] ?? 0);
        $fullName = clean_input($_POST['full_name'] ?? '');
        $email    = clean_input($_POST['email'] ?? '');
        $phone    = clean_input($_POST['phone'] ?? '');
        $groupId  = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $gender   = clean_input($_POST['gender'] ?? 'Khác');
        $birthday = !empty($_POST['birthday']) ? clean_input($_POST['birthday']) : null;
        $address  = clean_input($_POST['address'] ?? '');
        $note     = clean_input($_POST['note'] ?? '');

        if ($id <= 0) {
            json_response(false, 'Mã khách hàng không hợp lệ!', [], 400);
        }

        $stmtOld = $pdo->prepare("SELECT id, avatar FROM customers WHERE id = ? LIMIT 1");
        $stmtOld->execute([$id]);
        $oldCustomer = $stmtOld->fetch();
        if (!$oldCustomer) {
            json_response(false, 'Khách hàng không tồn tại!', [], 404);
        }

        if (empty($fullName)) {
            json_response(false, 'Họ và tên khách hàng không được để trống!', [], 422);
        }
        if (empty($email) || !is_valid_email($email)) {
            json_response(false, 'Email không đúng định dạng chuẩn!', [], 422);
        }
        if (empty($phone) || !is_valid_vn_phone($phone)) {
            json_response(false, 'Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại Việt Nam 10 chữ số.', [], 422);
        }

        $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ? LIMIT 1");
        $checkStmt->execute([$email, $id]);
        if ($checkStmt->fetch()) {
            json_response(false, 'Email này đã được dùng bởi một khách hàng khác!', [], 409);
        }

        $avatarPath = $oldCustomer['avatar'];
        try {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $newAvatar = handle_avatar_upload($_FILES['avatar']);
                if ($newAvatar) {

                    delete_avatar_file($oldCustomer['avatar']);
                    $avatarPath = $newAvatar;
                }
            }
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }

        $updateSql = "
            UPDATE customers
            SET group_id = ?, full_name = ?, email = ?, phone = ?, address = ?, gender = ?, birthday = ?, avatar = ?, note = ?
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($updateSql);
        $success = $stmt->execute([
            $groupId, $fullName, $email, $phone, $address, $gender, $birthday, $avatarPath, $note, $id
        ]);

        if ($success) {
            log_activity($pdo, $currentUserId, 'UPDATE', 'customers', $id);
            json_response(true, 'Cập nhật thông tin khách hàng thành công!');
        } else {
            json_response(false, 'Cập nhật thất bại!', [], 500);
        }
        break;

    case 'delete':
        require_admin_api();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            json_response(false, 'Mã khách hàng không hợp lệ!', [], 400);
        }

        $stmt = $pdo->prepare("UPDATE customers SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            log_activity($pdo, $currentUserId, 'DELETE', 'customers', $id);
            json_response(true, 'Đã chuyển khách hàng vào Thùng rác thành công!');
        } else {
            json_response(false, 'Khách hàng không tồn tại hoặc đã bị xóa trước đó!', [], 404);
        }
        break;

    case 'restore':
        require_admin_api();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            json_response(false, 'Mã khách hàng không hợp lệ!', [], 400);
        }

        $stmt = $pdo->prepare("UPDATE customers SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            log_activity($pdo, $currentUserId, 'RESTORE', 'customers', $id);
            json_response(true, 'Khôi phục khách hàng thành công!');
        } else {
            json_response(false, 'Không tìm thấy khách hàng trong thùng rác!', [], 404);
        }
        break;

    default:
        json_response(false, 'Hành động không hợp lệ!', [], 400);
        break;
}
