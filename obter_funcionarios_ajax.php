<?php
// =========================================================================
// 🔄 MOTOR DE ALIMENTAÇÃO ASSÍNCRONA — LEITURA PURA DO PHPMYADMIN
// =========================================================================
header('Content-Type: application/json; charset=utf-8');

include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$funcionarios_ativos = [];

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");

    // Consulta direta focada na tabela confirmada 'funcionarios'
    $query = "SELECT `id_funcionario`, `nome`, `status`, `especialidade` FROM `funcionarios` WHERE `ativo` = 1 ORDER BY `nome` ASC";
    $resultado = mysqli_query($conexao_link, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            
            // Tratamento dinâmico para os campos nulos (NULL) do Jorge Calebe e Mestre Alex
            $cargo_final = !empty($row['especialidade']) ? trim($row['especialidade']) : 'Barbeiro';

            $funcionarios_ativos[] = [
                'id'    => (int)$row['id_funcionario'],
                'nome'  => trim($row['nome']),
                'cargo' => $cargo_final,
                'status'=> trim($row['status'] ?? 'Disponível')
            ];
        }
    }
}

echo json_encode($funcionarios_ativos);
exit();
?>