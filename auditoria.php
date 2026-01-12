<?php
require_once 'conexao.php';
session_start();

// --- EVITA CACHE DO NAVEGADOR ---
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

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
    // 3. CONSULTA SQL (Trazendo todos os campos para alinhar com a tabela)
    $sql = "SELECT id, incidente, evento, endereco, area, regiao, site, otdr, criado_por, alterado_por, data_cadastro 
            FROM controle 
            ORDER BY id DESC LIMIT 200";
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
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0; }
        body::before { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.1; z-index: -1; 
        }
        .container { 
            max-width: 98%; margin: 20px auto; background: rgba(255,255,255,0.95); 
            padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            position: relative;
        }
        h2 { color: #e02810; text-transform: uppercase; text-align: center; border-bottom: 3px solid #e02810; padding-bottom: 15px; font-weight: 800; }
        
        /* BOTÃO VOLTAR NO TOPO DIREITO */
        .voltar-container { position: absolute; top: 25px; right: 30px; z-index: 1000; }
        .btn-voltar { display: inline-block; padding: 10px 22px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; text-transform: uppercase; font-size: 13px; }

        /* TABELA RESPONSIVA COM ROLAGEM */
        .tabela-responsiva { width: 100%; overflow: auto; max-height: 70vh; margin-top: 25px; border: 1px solid #ddd; border-radius: 8px; background: white; }
        table { width: 100%; border-collapse: collapse; min-width: 1600px; }
        
        /* CABEÇALHO FIXO */
        th { position: sticky; top: 0; background: #007bff; color: white; padding: 12px; text-align: left; text-transform: uppercase; font-size: 0.75em; white-space: nowrap; z-index: 5; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 0.85em; color: #333; }
        
        .badge-user { background: #34495e; color: #ecf0f1; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; }
        .badge-update { background: #e67e22; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; }
        
        /* SCROLLBAR AZUL */
        .tabela-responsiva::-webkit-scrollbar { height: 12px; }
        .tabela-responsiva::-webkit-scrollbar-thumb { background: #007bff; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="voltar-container">
        <a href="dashboard.php" class="btn-voltar">← Dashboard</a>
    </div>

    <h2>Relatório de Auditoria (ADMIN)</h2>

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
                        <span class="badge-user"><?php echo htmlspecialchars($log['criado_por'] ?: 'Sistema'); ?></span>
                        <br><small><?php echo date('d/m/Y H:i', strtotime($log['data_cadastro'])); ?></small>
                    </td>
                    
                    <td>
                        <?php if (!empty(trim($log['alterado_por'] ?? ''))): ?>
                            <span class="badge-update"><?php echo htmlspecialchars($log['alterado_por']); ?></span>
                        <?php else: ?>
                            <span style="color: #bbb; font-style: italic;">Sem alterações</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>