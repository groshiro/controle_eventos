<?php
require_once 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

try {
    // Busca dados incluindo as colunas de auditoria
    $sql = "SELECT id, incidente, site, criado_por, alterado_por, data_cadastro FROM controle ORDER BY id DESC LIMIT 100";
    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao extrair logs: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Auditoria de Alterações | Sistema</title>
    <style>
        /* Reutilizando seu CSS padrão para consistência */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.1; z-index: -1; }
        
        .container { max-width: 1000px; margin: auto; background: rgba(255,255,255,0.9); padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        h2 { color: #e02810; text-transform: uppercase; text-align: center; border-bottom: 2px solid #e02810; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 0.9em; }
        
        .badge-user { background: #34495e; color: #ecf0f1; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-family: monospace; }
        .badge-update { background: #e67e22; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Relatório de Auditoria (Últimas 100 ações)</h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Incidente / Site</th>
                <th>Criado por</th>
                <th>Última Alteração</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><strong>#<?php echo $log['id']; ?></strong></td>
                <td><?php echo htmlspecialchars($log['incidente'] . " - " . $log['site']); ?></td>
                <td>
                    <span class="badge-user"><?php echo $log['criado_por'] ?? 'Sistema'; ?></span>
                    <br><small><?php echo date('d/m/Y H:i', strtotime($log['data_cadastro'])); ?></small>
                </td>
                <td>
                    <?php if ($log['alterado_por']): ?>
                        <span class="badge-update"><?php echo $log['alterado_por']; ?></span>
                    <?php else: ?>
                        <span style="color: #999;">Sem alterações</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: center;">
        <a href="dashboard.php" style="text-decoration: none; color: #007bff; font-weight: bold;">← Voltar ao Dashboard</a>
    </div>
</div>

</body>
</html>