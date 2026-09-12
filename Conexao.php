<?php
// =========================================================================
// 🔮 ECOSSISTEMA MESTRE - MOTOR CENTRAL DE CONEXÃO MYSQL (INJEÇÃO DIRETA)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    // 💻 AMBIENTE LOCAL (XAMPP)
    $db_host = "127.0.0.1";
    $db_port = 3306;
    $db_user = "root";
    $db_pass = "";
    $db_name = "aurelius_salao";
} else {
    // ☁️ AMBIENTE ONLINE (Injeção absoluta do Aiven sem intermediários)
    $db_host = "://aivencloud.com";
    $db_port = 22002;
    $db_user = "avnadmin";
    $db_pass = "AVNS_6AyaHMtSplThuvy6uGm";
    $db_name = "defaultdb";
}

// Limpeza de segurança redundante na string do anfitrião
$db_host = str_replace(['https://', 'http://', '://'], '', trim($db_host));

// 🟢 1. PONTE MYSQLI TRADICIONAL NATIVA (Principal.php)
$conexao_aurelius = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
$mysqli = $conexao_aurelius;

if (!$conexao_aurelius) {
    die("🚨 Erro crítico de ligação ao motor MySQLi: " . mysqli_connect_error());
}
mysqli_set_charset($conexao_aurelius, "utf8mb4");

// 🟢 2. MOTOR PDO UNIFICADO CENTRAL
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("🚨 Falha na retaguarda PDO do ecossistema: " . $e->getMessage());
}

// Mapeamento global para compatibilidade com sub-módulos
$conexao_link = $conexao_aurelius;
$conexao = $conexao_aurelius;
$link = $conexao_aurelius;
