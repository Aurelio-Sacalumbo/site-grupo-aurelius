<?php
// =========================================================================
// 🔮 ECOSSISTEMA MESTRE - MOTOR CENTRAL DE CONEXÃO MYSQL (RAIZ COMPATÍVEL)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    // 💻 AMBIENTE LOCAL (XAMPP)
    $db_host = "127.0.0.1";
    $db_port = "3306";
    $db_user = "root";
    $db_pass = "";
    $db_name = "aurelius_salao";
} else {
    // ☁️ AMBIENTE ONLINE (Lê as variáveis configuradas no Render)
    $db_host = getenv('DB_HOST') ?: "mysql-1a34c184-aureliosacalumbo42-bf60.a.aivencloud.com";
    $db_port = getenv('DB_PORT') ?: 22002;
    $db_user = getenv('DB_USER') ?: "avnadmin";
    $db_pass = getenv('DB_PASSWORD') ?: "AVNS_6AyaHMtSplThuvy6uGm";
    $db_name = getenv('DB_NAME') ?: "defaultdb";
}

// Remove prefixos inválidos de segurança do host
$db_host = str_replace(['https://', 'http://', '://'], '', trim($db_host));

// 🟢 1. PONTE MYSQLI TRADICIONAL
$conexao_aurelius = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
$mysqli = $conexao_aurelius;

if (!$conexao_aurelius) {
    die("🚨 Erro de ligação MySQLi do Ecossistema: " . mysqli_connect_error());
}
mysqli_set_charset($conexao_aurelius, "utf8mb4");

// 🟢 2. MOTOR PDO UNIFICADO
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("🚨 Erro de ligação PDO do Ecossistema: " . $e->getMessage());
}

// Mapa de Compatibilidade SaaS
$conexao_link = $conexao_aurelius;
$conexao = $conexao_aurelius;
$link = $conexao_aurelius;
