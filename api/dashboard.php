<?php
/**
 * api/dashboard.php
 * Cung cấp dữ liệu thống kê KPI, biểu đồ phân bổ nhóm (Chart.js) và 10 nhật ký gần nhất
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_api();
$pdo = getDBConnection();

// 1. Thống kê 4 thẻ KPI
$stats = [
    'total_active' => (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL")->fetchColumn(),
    'new_this_month' => (int)$pdo->query("
        SELECT COUNT(*) FROM customers
        WHERE deleted_at IS NULL
          AND MONTH(created_at) = MONTH(CURRENT_DATE())
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ")->fetchColumn(),
    'total_groups' => (int)$pdo->query("SELECT COUNT(*) FROM customer_groups")->fetchColumn(),
    'total_trash' => (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NOT NULL")->fetchColumn(),
];

// 2. Dữ liệu phân bổ khách hàng theo nhóm cho Chart.js
$groupQuery = "
    SELECT
        g.name, g.color,
        COUNT(c.id) AS total
    FROM customer_groups g
    LEFT JOIN customers c ON g.id = c.group_id AND c.deleted_at IS NULL
    GROUP BY g.id, g.name, g.color
";
$groupsData = $pdo->query($groupQuery)->fetchAll();

// Đếm số lượng khách chưa phân nhóm
$noGroupCount = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE group_id IS NULL AND deleted_at IS NULL")->fetchColumn();
if ($noGroupCount > 0) {
    $groupsData[] = [
        'name'  => 'Chưa phân nhóm',
        'color' => '#6c757d',
        'total' => $noGroupCount
    ];
}

$chartData = [
    'labels'   => array_column($groupsData, 'name'),
    'datasets' => [
        [
            'data'            => array_map('intval', array_column($groupsData, 'total')),
            'backgroundColor' => array_column($groupsData, 'color'),
            'borderWidth'     => 1
        ]
    ]
];

// 3. Lấy 10 hoạt động gần nhất (Activity Logs)
$logQuery = "
    SELECT
        a.id, a.action, a.target_table, a.target_id, a.created_at,
        u.full_name AS user_name, u.role AS user_role
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 10
";
$recentLogs = $pdo->query($logQuery)->fetchAll();

json_response(true, 'Thành công', [
    'stats'       => $stats,
    'chart_data'  => $chartData,
    'recent_logs' => $recentLogs
]);
