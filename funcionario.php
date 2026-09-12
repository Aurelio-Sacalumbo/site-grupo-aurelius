<?php
// 1. CONEXÃO COM O BANCO DE DADOS
require_once "config/Banco.php";

// Captura o nome do funcionário vindo do clique ou define um padrão seguro
$nomeFuncionario = $_GET['nome'] ?? 'Aurélio';

try {
    // Busca os dados exatos do funcionário ativo na tabela do MySQL
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE nome = :nome LIMIT 1");
    $stmt->execute([':nome' => $nomeFuncionario]);
    $profissional = $stmt->fetch();

    // Se o funcionário não existir no banco, cria um array temporário para não quebrar a tela
    if (!$profissional) {
        $profissional = [
            'nome' => $nomeFuncionario,
            'especialidade' => 'Especialista Geral',
            'ativo' => 1
        ];
    }
} catch (PDOException $e) {
    die("Erro ao carregar perfil do profissional: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Perfil - <?php echo htmlspecialchars($profissional['nome']); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
        body { background-color: #0b1a30; color: #ffffff; padding: 20px; }
        .perfil-container { max-width: 600px; margin: 40px auto; background-color: #21409a; border: 2px dashed #0088cc; border-radius: 15px; padding: 30px; text-align: center; }
        .avatar-placeholder { width: 120px; height: 120px; background-color: #14424b; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 48px; border: 3px solid #ff9900; }
        h2 { font-size: 26px; margin-bottom: 5px; }
        .badge-especialidade { display: inline-block; background-color: #ff9900; color: white; padding: 4px 12px; font-size: 13px; font-weight: bold; border-radius: 20px; margin-bottom: 20px; }
        .status-atual { font-size: 15px; margin-bottom: 25px; padding: 10px; border-radius: 8px; background-color: rgba(0,0,0,0.2); }
        .btn-voltar { display: inline-block; background-color: #0088cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; transition: 0.2s; border: 1px solid #006699; }
        .btn-voltar:hover { background-color: #006699; }
    </style>
</head>
<body>

    <div class="perfil-container">
        <!-- Ícone representativo do profissional -->
        <div class="avatar-placeholder">💈</div>
        
        <h2><?php echo htmlspecialchars($profissional['nome']); ?></h2>
        <span class="badge-especialidade"><?php echo htmlspecialchars($profissional['especialidade']); ?></span>
        
        <div class="status-atual">
            Situação Operacional: 
            <strong id="status-perfil" style="color: #22c55e;">Carregando estado...</strong>
        </div>

        <p style="font-size: 14px; color: #cbd5e1; margin-bottom: 30px; line-height: 1.5;">
            Profissional qualificado atuando na unidade de Huambo, Angola. Comprometido com a excelência no atendimento e satisfação dos clientes do Salão Aurelius.
        </p>

        <a href="dashboard.php" class="btn-voltar">← Voltar ao Painel</a>
    </div>

    <script>
        // Sincroniza dinamicamente o status do funcionário com o que o Administrador definiu na outra tela
        window.addEventListener('DOMContentLoaded', () => {
            const nomeSlug = "<?php echo strtolower(preg_replace('/[^a-zA-SU-Z0-9]/', '', $profissional['nome'])); ?>";
            const statusGuardado = localStorage.getItem('status-' + nomeSlug) || 'Disponível';
            
            const elemento = document.getElementById('status-perfil');
            if (elemento) {
                elemento.innerText = statusGuardado;
                if (statusGuardado.includes('Ausente') || statusGuardado.includes('Folga')) {
                    elemento.style.color = '#ef4444';
                } else if (statusGuardado.includes('Atendimento')) {
                    elemento.style.color = '#ffaa00';
                } else {
                    elemento.style.color = '#22c55e';
                }
            }
        });
    </script>
</body>
</html>