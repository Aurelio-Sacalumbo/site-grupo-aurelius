<?php
// atualizar_tabela_vip.php
include_once("Conexao.php");
date_default_timezone_set('Africa/Luanda');

try {
    $query_vip = $pdo->query("SELECT * FROM assinaturas ORDER BY data_fim DESC");
    $clientes_vip = $query_vip->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $clientes_vip = []; }

if(empty($clientes_vip)): ?>
    <tr>
        <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8; font-family: sans-serif; background: #131e35; border-radius: 8px;">
            <strong style="color: #ca8a04; font-size: 14px;">Nenhuma Subscrição Ativa</strong>
        </td>
    </tr>
<?php else: 
    foreach($clientes_vip as $vip): 
        $nome_fidelizado = $vip['cliente'] ?? 'Cliente Premium';
        $hoje = new DateTime();
        $data_fim_plano = new DateTime($vip['data_fim']);
        $intervalo = $hoje->diff($data_fim_plano);
        $dias_restantes = $data_fim_plano > $hoje ? $intervalo->days : 0;
        $data_exibicao = date('d/m/Y', strtotime($vip['data_fim']));

        $fone_limpo = preg_replace('/\D/', '', $vip['telefone_express']);
        if (strlen($fone_limpo) === 9 && (strpos($fone_limpo, '9') === 0)) {
            $fone_limpo = '244' . $fone_limpo;
        }
    ?>
        <tr style="border-bottom: 1px solid #1e293b; color: #fff; font-family: sans-serif;">
            <td style="padding: 12px; font-weight: bold; color: #ffffff;"><?php echo htmlspecialchars($nome_fidelizado); ?></td>
            <td style="padding: 12px; text-transform: uppercase;">
                <span style="background:#1e293b; padding:2px 6px; border-radius:4px; font-size:11px; color: #ca8a04; border: 1px solid #ca8a04;">
                    <?php echo htmlspecialchars($vip['plano']); ?>
                </span>
            </td>
            <td style="padding: 12px; color: #3b82f6; font-weight: bold;"><?php echo htmlspecialchars($vip['telefone_express']); ?></td>
            <td style="padding: 12px;">
                <?php if ($dias_restantes <= 3 && $dias_restantes > 0): ?>
                    <span style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; color: #facc15; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">⚠️ Faltam <?php echo $dias_restantes; ?> d</span>
                <?php elseif ($dias_restantes > 0): ?>
                    <span style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #22c55e; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">✓ <?php echo $dias_restantes; ?> dias</span>
                <?php else: ?>
                    <span style="background: rgba(220, 38, 38, 0.15); border: 1px solid #dc2626; color: #f87171; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">✕ EXPIRADO</span>
                <?php endif; ?>
            </td>
            <td style="padding: 12px; text-align: center;">
                <!-- 🌐 LINK RETIFICADO PARA EVITAR CONFLITO DE DNS -->
                <a href="https://whatsapp.com<?php echo $fone_limpo; ?>&text=Olá%20<?php echo urlencode($nome_fidelizado); ?>!%20Passando%20para%20agradecer%20a%20sua%20fidelidade%20no%20Grupo%20Aurélius." 
                   target="_blank" 
                   style="background: #22c55e; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block;">
                    💬 Enviar Incentivo
                </a>
            </td>
        </tr>
    <?php endforeach; 
endif; ?><?php
// atualizar_tabela_vip.php
include_once("Conexao.php");
date_default_timezone_set('Africa/Luanda');

try {
    $query_vip = $pdo->query("SELECT * FROM assinaturas ORDER BY data_fim DESC");
    $clientes_vip = $query_vip->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $clientes_vip = []; }

if(empty($clientes_vip)): ?>
    <tr>
        <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8; font-family: sans-serif; background: #131e35; border-radius: 8px;">
            <strong style="color: #ca8a04; font-size: 14px;">Nenhuma Subscrição Ativa</strong>
        </td>
    </tr>
<?php else: 
    foreach($clientes_vip as $vip): 
        $nome_fidelizado = $vip['cliente'] ?? 'Cliente Premium';
        $hoje = new DateTime();
        $data_fim_plano = new DateTime($vip['data_fim']);
        $intervalo = $hoje->diff($data_fim_plano);
        $dias_restantes = $data_fim_plano > $hoje ? $intervalo->days : 0;
        $data_exibicao = date('d/m/Y', strtotime($vip['data_fim']));

        $fone_limpo = preg_replace('/\D/', '', $vip['telefone_express']);
        if (strlen($fone_limpo) === 9 && (strpos($fone_limpo, '9') === 0)) {
            $fone_limpo = '244' . $fone_limpo;
        }
    ?>
        <tr style="border-bottom: 1px solid #1e293b; color: #fff; font-family: sans-serif;">
            <td style="padding: 12px; font-weight: bold; color: #ffffff;"><?php echo htmlspecialchars($nome_fidelizado); ?></td>
            <td style="padding: 12px; text-transform: uppercase;">
                <span style="background:#1e293b; padding:2px 6px; border-radius:4px; font-size:11px; color: #ca8a04; border: 1px solid #ca8a04;">
                    <?php echo htmlspecialchars($vip['plano']); ?>
                </span>
            </td>
            <td style="padding: 12px; color: #3b82f6; font-weight: bold;"><?php echo htmlspecialchars($vip['telefone_express']); ?></td>
            <td style="padding: 12px;">
                <?php if ($dias_restantes <= 3 && $dias_restantes > 0): ?>
                    <span style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; color: #facc15; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">⚠️ Faltam <?php echo $dias_restantes; ?> d</span>
                <?php elseif ($dias_restantes > 0): ?>
                    <span style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #22c55e; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">✓ <?php echo $dias_restantes; ?> dias</span>
                <?php else: ?>
                    <span style="background: rgba(220, 38, 38, 0.15); border: 1px solid #dc2626; color: #f87171; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">✕ EXPIRADO</span>
                <?php endif; ?>
            </td>
            <td style="padding: 12px; text-align: center;">
                <!-- 🌐 LINK RETIFICADO PARA EVITAR CONFLITO DE DNS -->
                <a href="https://whatsapp.com<?php echo $fone_limpo; ?>&text=Olá%20<?php echo urlencode($nome_fidelizado); ?>!%20Passando%20para%20agradecer%20a%20sua%20fidelidade%20no%20Grupo%20Aurélius." 
                   target="_blank" 
                   style="background: #22c55e; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block;">
                    💬 Enviar Incentivo
                </a>
            </td>
        </tr>
    <?php endforeach; 
endif; ?>