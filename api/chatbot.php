<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/chatbot.php';
require_once __DIR__ . '/../includes/functions.php';

require_login_api();
$pdo = getDBConnection();
$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Phương thức không được hỗ trợ', [], 405);
}

$rawInput = file_get_contents('php://input');
$postData = json_decode($rawInput, true);
if (!is_array($postData)) {
    $postData = $_POST;
}

$message = trim($postData['message'] ?? '');
if ($message === '') {
    json_response(false, 'Nội dung tin nhắn không được để trống');
}

function normalize_text($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    ];
    foreach ($unicode as $nonAccent => $accent) {
        $str = preg_replace("/($accent)/i", $nonAccent, $str);
    }
    return $str;
}

$normalized = normalize_text($message);
$reply = '';
$quickReplies = [];
$matched = false;

if (preg_match('/\b(chao|xin chao|hello|hi|helo|alo|ban la ai|gioi thieu)\b/', $normalized)) {
    $userName = e($currentUser['full_name'] ?? 'bạn');
    $reply = "Xin chào <strong>{$userName}</strong>! 👋 Tôi là <strong>" . BOT_NAME . "</strong>, trợ lý ảo thông minh của CRM Pro.<br><br>"
           . "Tôi có thể giúp bạn:<br>"
           . "• 📊 <strong>Báo cáo thống kê:</strong> Đếm số lượng khách hàng, số khách theo nhóm, thùng rác.<br>"
           . "• 🔍 <strong>Tra cứu nhanh:</strong> Tìm khách theo tên hoặc số điện thoại (ví dụ: <em>'Tìm khách Nam'</em>).<br>"
           . "• ⭐ <strong>Khách hàng VIP:</strong> Xem nhanh danh sách khách hàng VIP.<br>"
           . "• 📝 <strong>Nhật ký chăm sóc:</strong> Xem các ghi chú chăm sóc khách hàng mới nhất.<br>"
           . "• ❓ <strong>Hướng dẫn sử dụng:</strong> Hướng dẫn xuất Excel, phân quyền, xóa/khôi phục.";
    $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP', '🔍 Tìm khách hàng', '❓ Hướng dẫn sử dụng'];
    $matched = true;
}

elseif (preg_match('/\b(thong ke|bao cao|tong quan|kpi|bao nhieu khach|so luong|tong so)\b/', $normalized)) {

    $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL");
    $totalActive = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NOT NULL");
    $totalTrash = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM customer_groups");
    $totalGroups = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT g.name, g.color, COUNT(c.id) as total
        FROM customer_groups g
        LEFT JOIN customers c ON c.group_id = g.id AND c.deleted_at IS NULL
        GROUP BY g.id, g.name, g.color
    ");
    $groupStats = $stmt->fetchAll();

    $groupListHtml = '<ul class="mb-0 ps-3">';
    foreach ($groupStats as $g) {
        $color = e($g['color']);
        $gName = e($g['name']);
        $gTotal = (int)$g['total'];
        $groupListHtml .= "<li><span class='badge' style='background-color: {$color}'>{$gName}</span>: <strong>{$gTotal}</strong> khách hàng</li>";
    }
    $groupListHtml .= '</ul>';

    $reply = "📊 <strong>Báo cáo tổng quan hệ thống CRM Pro:</strong><br><br>"
           . "• Tổng khách hàng đang hoạt động: <strong class='text-primary'>{$totalActive}</strong><br>"
           . "• Khách hàng trong thùng rác: <strong class='text-danger'>{$totalTrash}</strong><br>"
           . "• Số nhóm khách hàng: <strong>{$totalGroups}</strong> nhóm<br><br>"
           . "<strong>Phân bổ theo nhóm:</strong><br>" . $groupListHtml;

    $quickReplies = ['⭐ Khách VIP', '🗑️ Thùng rác', '📝 Ghi chú gần nhất'];
    $matched = true;
}

elseif (preg_match('/\b(vip|khach vip|khach hang vip)\b/', $normalized)) {
    $stmt = $pdo->query("
        SELECT c.id, c.full_name, c.phone, c.email, g.name as group_name, g.color as group_color
        FROM customers c
        JOIN customer_groups g ON c.group_id = g.id
        WHERE g.name LIKE '%VIP%' AND c.deleted_at IS NULL
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $vips = $stmt->fetchAll();

    if (empty($vips)) {
        $reply = "Hiện tại chưa có khách hàng nào thuộc nhóm <strong>VIP</strong>.";
    } else {
        $reply = "⭐ <strong>Danh sách khách hàng VIP (" . count($vips) . " khách gần nhất):</strong><br><br>";
        foreach ($vips as $v) {
            $name = e($v['full_name']);
            $phone = e($v['phone']);
            $email = e($v['email']);
            $color = e($v['group_color']);
            $reply .= "<div class='border rounded p-2 mb-2 bg-white'>"
                    . "<strong>👤 {$name}</strong> <span class='badge' style='background-color:{$color}'>VIP</span><br>"
                    . "<small class='text-muted'>📞 {$phone} | ✉️ {$email}</small>"
                    . "</div>";
        }
    }
    $quickReplies = ['📊 Thống kê nhanh', '🔍 Tìm khách hàng khác'];
    $matched = true;
}

elseif (preg_match('/\b(tim|tim kiem|tra cuu|so dien thoai|sdt|khach hang|khach)\b/', $normalized) && !preg_match('/\b(cach|huong dan)\b/', $normalized)) {

    $keyword = preg_replace('/^(tim|tim kiem|tra cuu|thong tin|khach hang|khach|sdt|so dien thoai)\s+/i', '', trim($message));
    $keyword = trim(preg_replace('/^(la|ve|ten|so)\s+/i', '', $keyword));

    if (mb_strlen($keyword, 'UTF-8') < 2) {
        $reply = "Bạn vui lòng nhập tên hoặc số điện thoại cụ thể để tôi tìm giúp nhé! <br>Ví dụ: <em>'Tìm khách Nam'</em> hoặc <em>'Tìm 0912345678'</em>.";
        $quickReplies = ['Tìm khách Nam', 'Tìm khách VIP'];
        $matched = true;
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, g.name as group_name, g.color as group_color
            FROM customers c
            LEFT JOIN customer_groups g ON c.group_id = g.id
            WHERE c.deleted_at IS NULL
              AND (c.full_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)
            ORDER BY c.id DESC
            LIMIT 4
        ");
        $term = "%{$keyword}%";
        $stmt->execute([$term, $term, $term]);
        $results = $stmt->fetchAll();

        if (empty($results)) {
            $reply = "🔍 Không tìm thấy khách hàng nào khớp với từ khóa <strong>'" . e($keyword) . "'</strong> trong hệ thống.";
            $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP'];
        } else {
            $count = count($results);
            $reply = "🔍 Tìm thấy <strong>{$count}</strong> khách hàng phù hợp với <em>'" . e($keyword) . "'</em>:<br><br>";
            foreach ($results as $c) {
                $cName = e($c['full_name']);
                $cPhone = e($c['phone']);
                $cEmail = e($c['email']);
                $cGroup = e($c['group_name'] ?? 'Chưa phân nhóm');
                $cColor = e($c['group_color'] ?? '#6c757d');
                $cGender = e($c['gender']);
                $cAddress = e($c['address'] ?? 'Chưa có địa chỉ');

                $reply .= "<div class='border rounded p-2 mb-2 bg-white'>"
                        . "<strong>👤 {$cName}</strong> ({$cGender}) "
                        . "<span class='badge' style='background-color:{$cColor}'>{$cGroup}</span><br>"
                        . "<small class='text-muted d-block'>📞 SĐT: <strong>{$cPhone}</strong></small>"
                        . "<small class='text-muted d-block'>✉️ Email: {$cEmail}</small>"
                        . "<small class='text-muted d-block'>🏠 {$cAddress}</small>"
                        . "</div>";
            }
            $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP'];
        }
        $matched = true;
    }
}

elseif (preg_match('/\b(cham soc|ghi chu|care note|care notes|tuong tac|lich su cham soc)\b/', $normalized)) {
    $stmt = $pdo->query("
        SELECT n.content, n.created_at, c.full_name as customer_name, u.full_name as staff_name
        FROM care_notes n
        JOIN customers c ON n.customer_id = c.id
        JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 4
    ");
    $notes = $stmt->fetchAll();

    if (empty($notes)) {
        $reply = "Hiện chưa có lịch sử chăm sóc khách hàng nào được ghi lại.";
    } else {
        $reply = "📝 <strong>Các ghi chú chăm sóc khách hàng gần nhất:</strong><br><br>";
        foreach ($notes as $n) {
            $cName = e($n['customer_name']);
            $sName = e($n['staff_name']);
            $content = e($n['content']);
            $time = date('d/m/Y H:i', strtotime($n['created_at']));

            $reply .= "<div class='border-start border-3 border-primary ps-2 mb-2 small'>"
                    . "<strong>Khách: {$cName}</strong> <span class='text-muted'>({$time})</span><br>"
                    . "<span>{$content}</span><br>"
                    . "<span class='text-secondary'>— Bởi: {$sName}</span>"
                    . "</div>";
        }
    }
    $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP'];
    $matched = true;
}

elseif (preg_match('/\b(thung rac|xoa mem|khoi phuc|restore|xoa khach|deleted)\b/', $normalized)) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NOT NULL");
    $trashCount = (int)$stmt->fetchColumn();

    $reply = "🗑️ <strong>Cơ chế Thùng rác & Xóa mềm (Soft Delete):</strong><br><br>"
           . "• Hiện có <strong>{$trashCount}</strong> khách hàng trong thùng rác.<br>"
           . "• Khi xóa khách hàng, hệ thống <em>không xóa vĩnh viễn</em> mà cập nhật trường <code>deleted_at = NOW()</code>.<br>"
           . "• <strong>Khôi phục:</strong> Admin có thể nhấn nút <strong>'Thùng rác'</strong> trên thanh công cụ trang Khách hàng $\\rightarrow$ bấm <strong>'Khôi phục'</strong> để đưa khách quay lại danh sách.<br>"
           . "• <em>Lưu ý:</em> Nhân viên (Staff) không có quyền truy cập thùng rác hoặc xóa khách hàng.";
    $quickReplies = ['📊 Thống kê nhanh', '❓ Phân quyền tài khoản'];
    $matched = true;
}

elseif (preg_match('/\b(xuat excel|xuat csv|export|tai excel|file excel)\b/', $normalized)) {
    $reply = "📥 <strong>Tính năng Xuất file CSV / Excel:</strong><br><br>"
           . "1. Truy cập trang <strong>Khách hàng</strong>.<br>"
           . "2. Nhấn nút <strong>'Xuất CSV'</strong> màu xanh lá ở góc phải.<br>"
           . "3. Hệ thống sẽ tự động xuất danh sách theo đúng bộ lọc tìm kiếm và nhóm hiện tại.<br>"
           . "• <em>Điểm đặc biệt:</em> File CSV được tích hợp mã <strong>UTF-8 BOM</strong>, đảm bảo mở trực tiếp bằng Microsoft Excel không bao giờ bị lỗi font tiếng Việt!";
    $quickReplies = ['📊 Thống kê nhanh', '❓ Hướng dẫn sử dụng'];
    $matched = true;
}

elseif (preg_match('/\b(phan quyen|role|admin|staff|tai khoan|mat khau|quyen)\b/', $normalized)) {
    $reply = "🔐 <strong>Hệ thống Phân quyền người dùng (RBAC):</strong><br><br>"
           . "• <strong>Admin (Quản trị viên):</strong> Toàn quyền thêm, sửa, xóa mềm, xem thùng rác, khôi phục, quản lý nhóm và xem nhật ký thao tác.<br>"
           . "• <strong>Staff (Nhân viên):</strong> Xem danh sách, thêm mới, sửa thông tin, thêm ghi chú chăm sóc và xuất CSV. <em>Bị chặn xóa khách và không thấy thùng rác.</em><br><br>"
           . "<strong>Tài khoản mẫu:</strong><br>"
           . "1. <code>admin</code> / <code>admin123</code> (Admin)<br>"
           . "2. <code>staff_nam</code> / <code>staff123</code> (Staff)<br>"
           . "3. <code>staff_hoa</code> / <code>staff123</code> (Staff)";
    $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP'];
    $matched = true;
}

elseif (preg_match('/\b(huong dan|giup do|help|chuc nang|tinh nang)\b/', $normalized)) {
    $reply = "💡 <strong>Các tính năng chính trong CRM Pro:</strong><br><br>"
           . "1. <strong>Dashboard:</strong> 4 thẻ KPI thống kê, biểu đồ phân bổ nhóm khách hàng (Chart.js), lịch sử thao tác.<br>"
           . "2. <strong>Quản lý Khách hàng:</strong> Tìm kiếm tức thì (Live search debounce 300ms), lọc theo nhóm/giới tính, phân trang AJAX.<br>"
           . "3. <strong>Chăm sóc khách hàng:</strong> Modal xem chi tiết và timeline ghi chú tương tác thời gian thực.<br>"
           . "4. <strong>Thùng rác & Xóa mềm:</strong> Bảo vệ dữ liệu, khôi phục tức thì.<br>"
           . "5. <strong>Xuất dữ liệu:</strong> Xuất file CSV tiếng Việt chuẩn UTF-8 BOM.";
    $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP', '🔍 Tìm khách hàng'];
    $matched = true;
}

if (!$matched && defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
    $apiKey = GEMINI_API_KEY;
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-1.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $systemInstruction = "Bạn là CRM AI Assistant, trợ lý ảo thông minh của hệ thống quản lý khách hàng CRM Pro. "
                       . "Hãy trả lời người dùng một cách thân thiện, ngắn gọn, lịch sự bằng Tiếng Việt. "
                       . "Bạn có kiến thức về quản lý khách hàng, chăm sóc khách hàng, nghiệp vụ CRM và hỗ trợ kỹ thuật web.";

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemInstruction . "\n\nNgười dùng hỏi: " . $message]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 500
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($aiText !== '') {
            $reply = nl2br(e($aiText));
            $matched = true;
        }
    }
}

if (!$matched) {
    $reply = "Xin lỗi, tôi chưa hiểu rõ câu hỏi <em>'" . e($message) . "'</em> của bạn. 🤔<br><br>"
           . "Bạn có thể thử các câu hỏi mẫu sau đây:";
    $quickReplies = ['📊 Thống kê nhanh', '⭐ Khách VIP', '🔍 Tìm khách Nam', '❓ Hướng dẫn sử dụng'];
}

json_response(true, 'Thành công', [
    'reply' => $reply,
    'quick_replies' => $quickReplies
]);
