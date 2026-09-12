<?php
// empresa.php - Página Pública Dinâmica para Qualquer Parceiro do Grupo Aurélius
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
$mysqli->set_charset("utf8");

// 🔮 CAPTURA O LINK ÚNICO (SLUG) ENVIADO PELA URL
$slug_recebido = isset($_GET['p']) ? $mysqli->escape_string($_GET['p']) : '';

// Procura a empresa correspondente no banco de dados
$query_perfil = $mysqli->query("SELECT * FROM `usuario` WHERE `slug` = '$slug_recebido' AND `transacao_status` = 'Confirmado'");

if ($query_perfil && $query_perfil->num_rows > 0) {
    $empresa = $query_perfil->fetch_assoc();
} else {
    // Se a empresa não existir ou não estiver validada, redireciona de volta
    header("Location: Principal.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($empresa['nome']); ?> - Grupo Aurélius</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: white; text-align: center; padding: 50px; }
        .container-perfil { background: #1e293b; padding: 40px; border-radius: 12px; max-width: 600px; margin: 0 auto; border: 1px solid #334155; }
        .logo-perfil { max-width: 150px; border-radius: 50%; border: 3px solid #38bdf8; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="container-perfil">
        <!-- Mostra dinamicamente o logótipo comercial de quem foi clicado -->
        <img class="logo-perfil" src="uploads/<?php echo htmlspecialchars($empresa['logo_empresa'] ?? 'OIP (6).webp'); ?>" alt="Logo">
        
        <h1>🎌 <?php echo htmlspecialchars($empresa['nome']); ?></h1>
        <p style="color: #38bdf8; font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($empresa['tipos_de_servico']); ?></p>
        <hr style="border-color: #334155; margin: 20px 0;">
        
        <p><strong>📍 Endereço Local:</strong> <?php echo htmlspecialchars($empresa['endereco']); ?></p>
        <p><strong>📞 Contacto WhatsApp:</strong> <?php echo htmlspecialchars($empresa['telefone']); ?></p>
        <p><strong>🕒 Horário de Expediente:</strong> Segunda a Sábado — Das 8h00 às 22h00</p>
        
        <br>
        <a href="https://whatsapp.com<?php echo preg_replace('/\D/', '', $empresa['telefone']); ?>" target="_blank" style="background: #25D366; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">💬 AGENDAR VIA WHATSAPP</a>
    </div>

</body>
</html>