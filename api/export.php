<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_page();
$pdo = getDBConnection();

$search  = clean_input($_GET['search'] ?? '');
$groupId = !empty($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$gender  = clean_input($_GET['gender'] ?? '');

$where = ["c.deleted_at IS NULL"];
$params = [];

if (!empty($search)) {
    $where[] = "(c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($groupId)) {
    $where[] = "c.group_id = ?";
    $params[] = $groupId;
}

if (!empty($gender) && in_array($gender, ['Nam', 'Nữ', 'Khác'])) {
    $where[] = "c.gender = ?";
    $params[] = $gender;
}

$whereClause = "WHERE " . implode(" AND ", $where);

$sql = "
    SELECT
        c.id, c.full_name, c.email, c.phone,
        COALESCE(g.name, 'Chưa phân nhóm') AS group_name,
        c.gender, c.birthday, c.address, c.note, c.created_at
    FROM customers c
    LEFT JOIN customer_groups g ON c.group_id = g.id
    $whereClause
    ORDER BY c.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$filename = "danh_sach_khach_hang_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

fputs($output, "\xEF\xBB\xBF");

$headers = [
    'Mã KH',
    'Họ và tên',
    'Email',
    'Số điện thoại',
    'Nhóm khách hàng',
    'Giới tính',
    'Ngày sinh',
    'Địa chỉ',
    'Ghi chú',
    'Ngày tạo'
];
fputcsv($output, $headers);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['group_name'],
        $row['gender'],
        $row['birthday'] ? date('d/m/Y', strtotime($row['birthday'])) : '',
        $row['address'],
        $row['note'],
        date('d/m/Y H:i', strtotime($row['created_at']))
    ]);
}

fclose($output);
exit;
