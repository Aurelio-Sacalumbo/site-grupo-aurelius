<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Captura as variáveis de ambiente configuradas no Render
$db_host = getenv('DB_HOST') ?: "localhost";
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";

// 2. Inicializa o driver MySQLi de forma isolada
$conexao_link = mysqli_init();

if (!$conexao_link) {
    die("Erro na inicialização do driver MySQLi.");
}

// 3. Verifica se está a rodar online (no Render) para aplicar as regras estritas de SSL
if (getenv('DB_HOST')) {
    // Configura a flag de SSL obrigatória exigida pela nuvem da Aiven
    mysqli_ssl_set($conexao_link, NULL, NULL, NULL, NULL, NULL);
    
    // Estabece a conexão usando a porta correta (22002) e a flag MYSQLI_CLIENT_SSL
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        $db_host, 
        $db_user, 
        $db_pass, 
        $db_name, 
        (int)$db_port, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );
} else {
    // Ambiente de Contingência Local (XAMPP de desenvolvimento no computador)
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        "127.0.0.1", 
        "root", 
        "", 
        "aurelius_salao", 
        3306
    );
}

// 4. Validação e travamento de segurança caso falte o motor de dados
if (!$status_conexao) {
    die("🚨 Falha na ligação à Base de Dados: " . mysqli_connect_error());
}

// Sincroniza o charset para aceitar emojis e caracteres especiais
mysqli_set_charset($conexao_link, "utf8mb4");
mysqli_query($conexao_link, "SET SESSION sql_mode=''");
?><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Captura as variáveis de ambiente configuradas no Render
$db_host = getenv('DB_HOST') ?: "localhost";
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";

// 2. Inicializa o driver MySQLi de forma isolada
$conexao_link = mysqli_init();

if (!$conexao_link) {
    die("Erro na inicialização do driver MySQLi.");
}

// 3. Verifica se está a rodar online (no Render) para aplicar as regras estritas de SSL
if (getenv('DB_HOST')) {
    // Configura a flag de SSL obrigatória exigida pela nuvem da Aiven
    mysqli_ssl_set($conexao_link, NULL, NULL, NULL, NULL, NULL);
    
    // Estabece a conexão usando a porta correta (22002) e a flag MYSQLI_CLIENT_SSL
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        $db_host, 
        $db_user, 
        $db_pass, 
        $db_name, 
        (int)$db_port, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );
} else {
    // Ambiente de Contingência Local (XAMPP de desenvolvimento no computador)
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        "127.0.0.1", 
        "root", 
        "", 
        "aurelius_salao", 
        3306
    );
}

// 4. Validação e travamento de segurança caso falte o motor de dados
if (!$status_conexao) {
    die("🚨 Falha na ligação à Base de Dados: " . mysqli_connect_error());
}

// Sincroniza o charset para aceitar emojis e caracteres especiais
mysqli_set_charset($conexao_link, "utf8mb4");
mysqli_query($conexao_link, "SET SESSION sql_mode=''");
?><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Captura as variáveis de ambiente configuradas no Render
$db_host = getenv('DB_HOST') ?: "localhost";
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";

// 2. Inicializa o driver MySQLi de forma isolada
$conexao_link = mysqli_init();

if (!$conexao_link) {
    die("Erro na inicialização do driver MySQLi.");
}

// 3. Verifica se está a rodar online (no Render) para aplicar as regras estritas de SSL
if (getenv('DB_HOST')) {
    // Configura a flag de SSL obrigatória exigida pela nuvem da Aiven
    mysqli_ssl_set($conexao_link, NULL, NULL, NULL, NULL, NULL);
    
    // Estabece a conexão usando a porta correta (22002) e a flag MYSQLI_CLIENT_SSL
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        $db_host, 
        $db_user, 
        $db_pass, 
        $db_name, 
        (int)$db_port, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );
} else {
    // Ambiente de Contingência Local (XAMPP de desenvolvimento no computador)
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        "127.0.0.1", 
        "root", 
        "", 
        "aurelius_salao", 
        3306
    );
}

// 4. Validação e travamento de segurança caso falte o motor de dados
if (!$status_conexao) {
    die("🚨 Falha na ligação à Base de Dados: " . mysqli_connect_error());
}

// Sincroniza o charset para aceitar emojis e caracteres especiais
mysqli_set_charset($conexao_link, "utf8mb4");
mysqli_query($conexao_link, "SET SESSION sql_mode=''");
?><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Captura as variáveis de ambiente configuradas no Render
$db_host = getenv('DB_HOST') ?: "localhost";
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";

// 2. Inicializa o driver MySQLi de forma isolada
$conexao_link = mysqli_init();

if (!$conexao_link) {
    die("Erro na inicialização do driver MySQLi.");
}

// 3. Verifica se está a rodar online (no Render) para aplicar as regras estritas de SSL
if (getenv('DB_HOST')) {
    // Configura a flag de SSL obrigatória exigida pela nuvem da Aiven
    mysqli_ssl_set($conexao_link, NULL, NULL, NULL, NULL, NULL);
    
    // Estabece a conexão usando a porta correta (22002) e a flag MYSQLI_CLIENT_SSL
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        $db_host, 
        $db_user, 
        $db_pass, 
        $db_name, 
        (int)$db_port, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );
} else {
    // Ambiente de Contingência Local (XAMPP de desenvolvimento no computador)
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        "127.0.0.1", 
        "root", 
        "", 
        "aurelius_salao", 
        3306
    );
}

// 4. Validação e travamento de segurança caso falte o motor de dados
if (!$status_conexao) {
    die("🚨 Falha na ligação à Base de Dados: " . mysqli_connect_error());
}

// Sincroniza o charset para aceitar emojis e caracteres especiais
mysqli_set_charset($conexao_link, "utf8mb4");
mysqli_query($conexao_link, "SET SESSION sql_mode=''");
?><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Captura as variáveis de ambiente configuradas no Render
$db_host = getenv('DB_HOST') ?: "localhost";
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";

// 2. Inicializa o driver MySQLi de forma isolada
$conexao_link = mysqli_init();

if (!$conexao_link) {
    die("Erro na inicialização do driver MySQLi.");
}

// 3. Verifica se está a rodar online (no Render) para aplicar as regras estritas de SSL
if (getenv('DB_HOST')) {
    // Configura a flag de SSL obrigatória exigida pela nuvem da Aiven
    mysqli_ssl_set($conexao_link, NULL, NULL, NULL, NULL, NULL);
    
    // Estabece a conexão usando a porta correta (22002) e a flag MYSQLI_CLIENT_SSL
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        $db_host, 
        $db_user, 
        $db_pass, 
        $db_name, 
        (int)$db_port, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );
} else {
    // Ambiente de Contingência Local (XAMPP de desenvolvimento no computador)
    $status_conexao = @mysqli_real_connect(
        $conexao_link, 
        "127.0.0.1", 
        "root", 
        "", 
        "aurelius_salao", 
        3306
    );
}

// 4. Validação e travamento de segurança caso falte o motor de dados
if (!$status_conexao) {
    die("🚨 Falha na ligação à Base de Dados: " . mysqli_connect_error());
}

// Sincroniza o charset para aceitar emojis e caracteres especiais
mysqli_set_charset($conexao_link, "utf8mb4");
mysqli_query($conexao_link, "SET SESSION sql_mode=''");
?>
