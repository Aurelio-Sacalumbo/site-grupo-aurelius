<?php
// forcar_login.php - Restabelece a sessão do Administrador Master
session_start();
$_SESSION['codigo_usuario'] = 237;
$_SESSION['nome_usuario']   = 'Barbearia Branca';
$_SESSION['funcionario_nome'] = 'Aurelio';
$_SESSION['tenant_empresa_id'] = 237;

header("Location: Principal.php");
exit;
?>