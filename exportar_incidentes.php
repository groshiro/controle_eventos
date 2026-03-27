<?php
// Arquivo: exportar_incidentes.php
ob_start(); 
require_once 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_logado'])) {
    die("Acesso negado. Por favor, faça login novamente."); 
}

try {
    // Busca todos os incidentes (ou você pode passar o filtro de busca aqui via GET)
    $sql = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle ORDER BY id";
    $stmt = $pdo->query($sql);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "relatorio_incidentes_" . date('d-m-Y_H-i') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Adiciona o BOM para o Excel abrir com acentos corretos
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalho do CSV
    fputcsv($output, ['ID', 'Data', 'Incidente', 'Evento', 'Endereço', 'Área', 'Região', 'Site', 'OTDR'], ';');

    foreach ($dados as $linha) {
        fputcsv($output, $linha, ';');
    }

    fclose($output);
    exit();
} catch (PDOException $e) {
    die("Erro ao exportar: " . $e->getMessage());
}
