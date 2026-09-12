<?php
// logout.php - Encerramento de Sessão Segura (Grupo Aurélius)
if (!isset($_SESSION)) {
    session_start();
}

// 🧼 LIMPA TODAS AS VARIÁVEIS DE ISOLAMENTO DA MEMÓRIA DO APACHE
$_SESSION = array();

// Destrói fisicamente o cookie de sessão no navegador do utilizador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finaliza a sessão ativa no servidor local do XAMPP
session_destroy();

// 🚀 REDIRECIONA IMEDIATAMENTE PARA A TELA DE LOGIN ATUALIZADA
echo "<script>alert('Sessão encerrada com segurança no ecossistema Aurélius!'); window.location.href='Login.php';</script>";
exit();
?>