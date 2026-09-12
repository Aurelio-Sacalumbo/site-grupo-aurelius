<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    // Ambiente Local (XAMPP)
    $db_host = "127.0.0.1"; 
    $db_port = "3306"; 
    $db_user = "root"; 
    $db_pass = ""; 
    $db_name = "aurelius_salao";
    } else {
    // Ambiente Online (Forçando o seu host exclusivo do Aiven de forma estrita)
    $db_host = "://aivencloud.com";
    $db_port = 22002;
    $db_user = "avnadmin";
    $db_pass = "AVNS_6AyaHMtSplThuvy6uGm";
    $db_name = "defaultdb";
}

// Remove qualquer prefixo de protocolo para evitar rejeição do driver
$db_host = str_replace(['https://', 'http://', '://'], '', trim($db_host));

// 🟢 Conexão MySQLi Central
$conexao_aurelius = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
$mysqli = $conexao_aurelius;

if (!$conexao_aurelius) { 
    die("🚨 Erro de ligação MySQLi do Ecossistema: " . mysqli_connect_error()); 
}
mysqli_set_charset($conexao_aurelius, "utf8mb4");

// 🟢 Motor PDO Unificado
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("🚨 Erro de ligação PDO do Ecossistema: " . $e->getMessage()); 
}

// Pontes Universais de Compatibilidade
$conexao_link = $conexao_aurelius; 
$conexao = $conexao_aurelius; 
$link = $conexao_aurelius;
