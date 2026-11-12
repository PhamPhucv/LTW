<?php

$ports = ['3306', '3307'];
$pdo = null;
$connectedPort = null;

foreach ($ports as $port) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port={$port};charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $connectedPort = $port;
        break;
    } catch (PDOException $e) {
    }
}

if (!$pdo) {
    echo "[!] Khong the ket noi den MySQL tren cong 3306/3307.\n";
    echo "    Vui long bat XAMPP Control Panel va nhan Start o muc MySQL!\n";
    exit(1);
}

echo "[OK] Ket noi thanh cong MySQL tren cong {$connectedPort}.\n";

try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'customer_db'");
    $dbExists = $stmt->fetchColumn();

    $needImport = false;
    if (!$dbExists) {
        $needImport = true;
    } else {
        $pdo->exec("USE `customer_db`");
        $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
        if (!$stmt->fetchColumn()) {
            $needImport = true;
        }
    }

    if ($needImport) {
        echo "[i] Dang tu dong import co so du lieu database.sql vao MySQL...\n";
        $sqlFile = __DIR__ . '/database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            echo "[OK] Import co so du lieu thanh cong!\n";
        } else {
            echo "[!] Khong tim thay file database.sql.\n";
        }
    } else {
        echo "[OK] Co so du lieu customer_db da ton tai va san sang su dung.\n";
    }
} catch (Exception $e) {
    echo "[!] Loi thiet lap CSDL: " . $e->getMessage() . "\n";
}
exit(0);
