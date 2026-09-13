<?php
header("Location: ./Principal.php");
exit();
if (basename($_SERVER['PHP_SELF']) == 'index.php') {
    include 'Principal.php';
    exit();
}
?>