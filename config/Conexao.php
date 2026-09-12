<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Luanda');

// 🟢 1. DETEÇÃO AUTOMÁTICA DE AMBIENTE (LOCALHOST VS AIVEN CLOUD)
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    
    // 💻 AMBIENTE LOCAL (XAMPP)
    $db_host = "127.0.0.1";
    $db_port = "3306";
    $db_user = "root";
    $db_pass = "";
    $db_name = "aurelius_salao";

} else {
    
    // ☁️ AMBIENTE ONLINE (Forçando o host e credenciais exclusivas do seu Aiven)
    $db_host = "://aivencloud.com";
    $db_port = 22002;
    $db_user = "avnadmin";
    $db_pass = "AVNS_6AyaHMtSplThuvy6uGm";
    $db_name = "defaultdb";
}

// Limpeza de segurança na string do host
$db_host = str_replace(['https://', 'http://', '://'], '', trim($db_host));

// 🟢 2. PONTE DE CONEXÃO MYSQLI TRADICIONAL
$conexao_aurelius = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
$mysqli = $conexao_aurelius;

if (!$conexao_aurelius) {
    die("🚨 Grupo Aurélius - Falha técnica na ligação ao motor MySQLi: " . mysqli_connect_error());
}
mysqli_set_charset($conexao_aurelius, "utf8mb4");
mysqli_query($conexao_aurelius, "SET SESSION sql_mode=''");

// 🟢 3. MOTOR PDO UNIFICADO
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("🚨 Falha na infraestrutura PDO Aurélius Central: " . $e->getMessage());
}

// Mapeamento global de compatibilidade
$conexao_link = $conexao_aurelius;
$conexao = $conexao_aurelius;
$link = $conexao_aurelius;
