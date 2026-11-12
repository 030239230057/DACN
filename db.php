<?php
$host = '127.0.0.1';
$db   = 'backoffice_db';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// Đọc tham số rủi ro từ file config (Rule Engine - BR5)
function getSysConfig() {
    $configFile = __DIR__ . '/config.json';
    if (!file_exists($configFile)) {
        $defaultConfig = ["AGE_MIN"=>18, "AGE_MAX"=>60, "DOWN_PAYMENT_MIN_PCT"=>20, "DTI_MAX_PCT"=>43, "INTEREST_RATE_MONTHLY"=>0.025];
        file_put_contents($configFile, json_encode($defaultConfig));
        return $defaultConfig;
    }
    return json_decode(file_get_contents($configFile), true);
}
?>