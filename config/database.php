<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'customer_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli' || empty($_SERVER['SCRIPT_NAME'])) {
        define('BASE_URL', '/');
    } else {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $base = preg_replace('#/(api|config|includes|views|assets).*$#i', '', $scriptDir);
        $base = rtrim($base, '/') . '/';
        define('BASE_URL', $base);
    }
}

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $ports = array_unique([DB_PORT, '3306', '3307']);
        $lastException = null;

        foreach ($ports as $port) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                return $pdo;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . ($lastException ? $lastException->getMessage() : 'Không thể kết nối MySQL')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $pdo;
}
