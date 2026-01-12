<?php
require_once 'conexao.php';
session_start();

// 1. Verificação de Login
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

// 2. Trava de Segurança: Somente Administradores
if ($_SESSION['nivel_permissao'] !== 'ADMIN') {
    $_SESSION['alerta_erro'] = "Acesso Negado: Você não tem permissão para visualizar a auditoria.";
    header("Location: dashboard.php");
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
        /* Estilos idênticos ao seu padrão */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0; }
        body::before { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.1; z-index: -1; 
        }
        
        .container { 
            max-width: 1000px; margin: 40px auto; background: rgba(255,255,255,0.9); 
            padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            backdrop-filter: blur(5px);
        }
        
        h2 { 
            color: #e02810; text-transform: uppercase; text-align: center; 
            border-bottom: 3px solid #e02810; padding-bottom: 15px; font-weight: 800; 
        }
        
        table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        th { background: #007bff; color: white; padding: 15px; text-align: left; text-transform: uppercase; font-size: 0.85em; }
        td { padding: 15px; border-bottom: 1px solid #ddd; font-size: 0.95em; color: #333; }
        
        tr:hover td { background-color: rgba(0, 123, 255, 0.05); }

        .badge-user { 
            background: #34495e; color: #ecf0f1; padding: 5px 10px; 
            border-radius: 4px; font-weight: bold; font-family: monospace; font-size: 0.9em;
        }
        .badge-update { 
            background: #e67e22; color: white; padding: 5px 10px; 
            border-radius: 4px; font-weight: bold; font-size: 0.9em;
        }
        
        .btn-voltar {
            display: inline-block; margin-top: 25px; text-decoration: none; 
            color: white; background: #6c757d; padding: 10px 20px; 
            border-radius: 6px; font-weight: bold; transition: 0.3s;
        }
        .btn-voltar:hover { background: #5a6268; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container">
    <h2>Relatório de Auditoria (ADMIN)</h2>
    
    

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
                <td><?php echo htmlspecialchars(($log['incidente'] ?? '-') . " - " . ($log['site'] ?? '-')); ?></td>
                <td>
                    <span class="badge-user"><?php echo htmlspecialchars($log['criado_por'] ?? 'Sistema'); ?></span>
                    <br><small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($log['data_cadastro'])); ?></small>
                </td>
                <td>
                    <?php if (!empty($log['alterado_por'])): ?>
                        <span class="badge-update"><?php echo htmlspecialchars($log['alterado_por']); ?></span>
                    <?php else: ?>
                        <span style="color: #bbb; font-style: italic;">Sem alterações</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="text-align: center;">
        <a href="dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
    </div>
</div>

</body>
</html>
