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
    echo "\n[!] KHONG THE KET NOI DEN MYSQL!\n";
    echo "    Vui long mo XAMPP Control Panel va nhan [Start] o muc MySQL truoc.\n";
    exit(1);
}

echo "[OK] Ket noi MySQL thanh cong tren cong {$connectedPort}.\n";

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
        echo "[i] Dang tu dong import co so du lieu database.sql...\n";
        $sqlFile = __DIR__ . '/database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            echo "[OK] Import co so du lieu database.sql thanh cong!\n";
        } else {
            echo "[!] Khong tim thay file database.sql de import.\n";
        }
    } else {
        echo "[OK] Co so du lieu customer_db da san sang su dung.\n";
    }
} catch (Exception $e) {
    echo "[!] Canh bao CSDL: " . $e->getMessage() . "\n";
}

$candidates = [8000, 8080, 8888, 8008, 8081];
$chosenPort = 8000;
foreach ($candidates as $candidate) {
    $fp = @fsockopen('127.0.0.1', $candidate, $errno, $errstr, 0.5);
    if (!$fp) {
        $chosenPort = $candidate;
        break;
    }
    fclose($fp);
}

file_put_contents(__DIR__ . '/.port', $chosenPort);
if ($chosenPort !== 8000) {
    echo "[i] Cong 8000 da co ung dung khac su dung. He thong tu dong chuyen sang cong {$chosenPort}.\n";
}

exit(0);
