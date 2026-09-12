<?php
// =========================================================================
// ⚙️ CONFIGURAÇÕES GLOBAIS E METADADOS - ECOSSISTEMA REATIVO AURÉLIUS
// =========================================================================

// Garante que o ficheiro de ligação à base de dados (Banco.php) está incluído primeiro
// O dirname(__DIR__) garante o caminho correto na árvore do Linux no Render
require_once dirname(__DIR__) . '/config/Banco.php';

// 🟢 CONFIGURAÇÃO DINÂMICA DA URL BASE (Evita erros de redirecionamento amigável)
if (getenv('DB_HOST')) {
    // URL oficial de Produção no Render
    define('BASE_URL', 'https://onrender.com');
} else {
    // URL de desenvolvimento no seu XAMPP Local
    define('BASE_URL', 'http://localhost/grupo-aurelius-pwa/');
}

// Nome fantasia da plataforma SaaS
define('SITENAME', 'Grupo Aurelius');

// Configuração oficial do fuso horário para bater com Huambo/Angola
date_default_timezone_set('Africa/Luanda'); 

// 🟢 GESTÃO PROFISSIONAL DE LOGS E DEPENDÊNCIAS DE ERRO
if (getenv('DB_HOST')) {
    // Ambiente Online: Esconde erros do cliente e grava em arquivos internos de log
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
} else {
    // Ambiente Local (XAMPP): Mostra tudo detalhadamente para ajudar a depurar
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
?>
