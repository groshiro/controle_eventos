<?php
require_once 'conexao.php';
session_start();

// --- EVITA CACHE DO NAVEGADOR ---
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

// 1. Verificação de Login e Permissão
if (!isset($_SESSION['usuario_logado']) || $_SESSION['nivel_permissao'] !== 'ADMIN') {
    header("Location: dashboard.php");
    exit();
}

try {
    // 2. CONSULTA SQL HÍBRIDA
    // Pega inserções dos últimos 5 dias OU qualquer registro que tenha sido alterado
    $sql = "SELECT id, incidente, evento, endereco, area, regiao, site, otdr, criado_por, alterado_por, data_cadastro, data_alteracao 
            FROM controle 
            WHERE data_cadastro >= CURRENT_DATE - INTERVAL '5 days'
               OR (alterado_por IS NOT NULL AND TRIM(alterado_por) <> '')
            ORDER BY id DESC LIMIT 300";
            
    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao extrair logs: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Auditoria | Sistema</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0; }
        
        /* IMAGEM DE FUNDO PADRÃO CLARO */
        body::before { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: url('claro-operadora.jpg'); background-size: cover; 
            opacity: 0.1; z-index: -1; 
        }

        .container { 
            max-width: 98%; margin: 20px auto; background: rgba(255,255,255,0.95); 
            padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            position: relative; backdrop-filter: blur(5px);
        }

        h2 { color: #e02810; text-transform: uppercase; text-align: center; border-bottom: 3px solid #e02810; padding-bottom: 15px; font-weight: 800; }
        
        .voltar-container { position: absolute; top: 25px; right: 30px; z-index: 1000; }
        .btn-voltar { display: inline-block; padding: 10px 22px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; text-transform: uppercase; font-size: 13px; }

        .tabela-responsiva { width: 100%; overflow: auto; max-height: 70vh; margin-top: 25px; border: 1px solid #ddd; border-radius: 8px; background: white; }
        table { width: 100%; border-collapse: collapse; min-width: 1600px; }
        
        th { position: sticky; top: 0; background: #007bff; color: white; padding: 12px; text-align: left; text-transform: uppercase; font-size: 0.75em; white-space: nowrap; z-index: 5; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 0.85em; color: #333; }
        
        .badge-user { background: #34495e; color: #ecf0f1; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; }
        .badge-update { background: #e67e22; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; }
        
        .tabela-responsiva::-webkit-scrollbar { height: 12px; }
        .tabela-responsiva::-webkit-scrollbar-thumb { background: #007bff; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="voltar-container">
        <a href="dashboard.php" class="btn-voltar">← Dashboard</a>
    </div>

    <h2>Relatório de Auditoria Recente (ADMIN)</h2>

    <div class="tabela-responsiva">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Incidente</th>
                    <th>Evento</th>
                    <th>Endereço</th>
                    <th>Área</th>
                    <th>Região</th>
                    <th>Site</th>
                    <th>OTDR</th>
                    <th>Criado por / Data</th>
                    <th>Última Alteração</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><strong>#<?php echo $log['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($log['incidente'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['evento'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['endereco'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['area'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['regiao'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['site'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['otdr'] ?? '-'); ?></td>
                        
                        <td>
                            <?php $criador = explode(' ', trim($log['criado_por'] ?: 'Sistema'))[0]; ?>
                            <span class="badge-user"><?php echo htmlspecialchars($criador); ?></span>
                            <br><small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($log['data_cadastro'])); ?></small>
                        </td>
                        
                        <td>
                            <?php 
                            $alt = trim($log['alterado_por'] ?? '');
                            $data_alt = $log['data_alteracao'] ?? null;

                            if (!empty($alt)): 
                                $nome_alt = explode(' ', $alt)[0];
                            ?>
                                <span class="badge-update"><?php echo htmlspecialchars($nome_alt); ?></span>
                                <br>
                                <small style="color: #666;">
                                    <?php 
                                        // EXIBE SÓ SE TIVER DATA, SENÃO FICA VAZIO
                                        if (!empty($data_alt)) {
                                            echo date('d/m/Y H:i', strtotime($data_alt)); 
                                        }
                                    ?>
                                </small>
                            <?php else: ?>
                                <span style="color: #bbb; font-style: italic;">Sem alterações</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" style="text-align:center; padding: 50px;">Nenhum registro nos últimos 5 dias.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>