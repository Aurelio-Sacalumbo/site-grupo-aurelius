<?php
// Desativa o relatório estrito de exceções para permitir testes dinâmicos de colunas
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuração oficial do fuso horário de Angola
date_default_timezone_set('Africa/Luanda');

// 🟢 1. DETEÇÃO AUTOMÁTICA DE AMBIENTE (LOCALHOST VS HOSPEDAGEM SEGURA)
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1');

if ($is_local) {
    // 💻 AMBIENTE LOCAL (Configuração para o seu XAMPP/MySQL)
    $db_host = "127.0.0.1";
    $db_port = "3306";
    $db_user = "root";
    $db_pass = "";
    $db_name = "aurelius_salao";
} else {
    // ☁️ AMBIENTE DE HOSPEDAGEM REAL (Puxa os dados injetados pelo painel do Render para o Aiven)
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASSWORD');
    $db_name = getenv('DB_NAME');
}

// 🟢 2. PONTE DE CONEXÃO MYSQLI TRADICIONAL COM SUPORTE A SSL
$mysqli = mysqli_init();
if (!$mysqli) {
    die("🚨 Grupo Aurélius - Falha ao inicializar o motor MySQLi.");
}

// Se estiver online no Render/Aiven, força o uso de SSL antes de conectar
if (!$is_local) {
    mysqli_ssl_set($mysqli, NULL, NULL, NULL, NULL, NULL);
    $status_mysqli = @mysqli_real_connect($mysqli, $db_host, $db_user, $db_pass, $db_name, (int)$db_port, NULL, MYSQLI_CLIENT_SSL);
} else {
    $status_mysqli = @mysqli_real_connect($mysqli, $db_host, $db_user, $db_pass, $db_name, (int)$db_port);
}

if ($status_mysqli) {
    mysqli_set_charset($mysqli, "utf8mb4");
} else {
    die("🚨 Grupo Aurélius - Falha técnica na ligação ao motor MySQLi: " . mysqli_connect_error());
}

// 🟢 3. MOTOR PDO UNIFICADO COM SUPORTE A SSL
try {
    $pdo_options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Se estiver online na nuvem, injeta a flag de SSL do PDO para o MySQL
    if (!$is_local) {
        $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = true; 
        // Define explicitamente para não verificar o certificado localmente se não tiver o ficheiro .pem
        $pdo_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    die("🚨 Falha na infraestrutura PDO Aurélius Central: " . $e->getMessage());
}

// 🔓 COLA AS DUAS LINHAS EXATAMENTE AQUI:
mysqli_query($mysqli, "SET SESSION sql_mode=''");
$pdo->exec("SET SESSION sql_mode=''");

// 🟢 4. MAPA GLOBAL DE COMPATIBILIDADE
$conexao_link     = $mysqli;
$conexao_aurelius = $mysqli;
$conexao          = $mysqli;
$link             = $mysqli;
?>