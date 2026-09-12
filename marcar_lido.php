php<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/config/Banco.php";

// Atualiza todas as entradas para "vistas" no banco local
$pdo->query("UPDATE `pagamentos` SET `visto_admin` = 1 WHERE `visto_admin` = 0");

echo "<script>window.location.href = 'Admini.php';</script>";
exit();
?>