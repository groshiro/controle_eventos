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

// --- CONFIGURAÇÃO DA PAGINAÇÃO ---
$itens_por_pagina = 50; 
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

try {
    // 2. CONTAGEM TOTAL PARA PAGINAÇÃO
    $sql_count = "SELECT COUNT(*) FROM controle 
                  WHERE data_cadastro >= CURRENT_DATE - INTERVAL '30 days'
                     OR (data_alteracao IS NOT NULL)";
    $total_registros = $pdo->query($sql_count)->fetchColumn();
    $total_paginas = ceil($total_registros / $itens_por_pagina);

    // 3. CONSULTA SQL OTIMIZADA
    $sql = "SELECT id, incidente, evento, endereco, area, regiao, site, otdr, criado_por, alterado_por, data_cadastro, data_alteracao 
            FROM controle 
            WHERE data_cadastro >= CURRENT_DATE - INTERVAL '30 days'
               OR (data_alteracao IS NOT NULL)
            ORDER BY COALESCE(data_alteracao, data_cadastro) DESC 
            LIMIT $itens_por_pagina OFFSET $offset";
            
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria | Controle Claro</title>
    <style>
        :root {
            --claro-vermelho: #e02810;
            --claro-escuro: #1a1a1a;
            --claro-azul: #007bff;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0; overflow-x: hidden; }
        
        body::before { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: url('claro-operadora.jpg'); background-size: cover; 
            opacity: 0.05; z-index: -1; 
        }

        .container { 
            max-width: 98%; margin: 20px auto; background: rgba(255,255,255,0.92); 
            padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            position: relative; backdrop-filter: blur(8px);
            animation: fadeIn 0.6s ease-out;
        }

        h2 { 
            color: var(--claro-vermelho); text-transform: uppercase; text-align: center; 
            border-bottom: 3px solid var(--claro-vermelho); padding-bottom: 15px; 
            font-weight: 800; letter-spacing: 1px;
        }

        .voltar-container { position: absolute; top: 25px; right: 30px; }
        .btn-voltar { 
            display: inline-block; padding: 10px 20px; background-color: #6c757d; 
            color: white; text-decoration: none; border-radius: 8px; 
            font-weight: bold; transition: 0.3s; font-size: 13px;
        }
        .btn-voltar:hover { background-color: #495057; transform: translateX(-5px); }

        .tabela-responsiva { 
            width: 100%; overflow: auto; max-height: 65vh; margin-top: 25px; 
            border: 1px solid #ddd; border-radius: 10px; background: white;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; min-width: 1500px; }
        
        th { 
            position: sticky; top: 0; background: var(--claro-azul); color: white; 
            padding: 15px; text-align: left; text-transform: uppercase; 
            font-size: 11px; z-index: 10; 
        }

        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 13px; color: #444; }
        tr:hover { background-color: #f9f9f9; }

        .badge-user { background: #34495e; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; }
        .badge-update { background: #e67e22; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; }

        /* PAGINAÇÃO */
        .paginacao { display: flex; justify-content: center; gap: 5px; margin-top: 25px; }
        .paginacao a, .paginacao span { 
            padding: 8px 14px; border-radius: 5px; text-decoration: none; 
            border: 1px solid #ddd; font-weight: bold; font-size: 13px; color: #555;
        }
        .paginacao a:hover { background: var(--claro-azul); color: white; border-color: var(--claro-azul); }
        .paginacao .atual { background: var(--claro-vermelho); color: white; border-color: var(--claro-vermelho); }
        .paginacao .desativado { color: #ccc; cursor: not-allowed; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .tabela-responsiva::-webkit-scrollbar { height: 10px; width: 8px; }
        .tabela-responsiva::-webkit-scrollbar-thumb { background: var(--claro-azul); border-radius: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="voltar-container">
        <a href="dashboard.php" class="btn-voltar">← Voltar Dashboard</a>
    </div>

    <h2>Auditoria de Incidentes (ADMIN)</h2>

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
                    <th>Criação</th>
                    <th>Última Alteração</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><strong>#<?= $log['id'] ?></strong></td>
                        <td><?= htmlspecialchars($log['incidente'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['evento'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['endereco'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['area'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['regiao'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['site'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($log['otdr'] ?? '-') ?></td>
                        
                        <td>
                            <?php $criador = explode(' ', trim($log['criado_por'] ?: 'Sistema'))[0]; ?>
                            <span class="badge-user"><?= htmlspecialchars($criador) ?></span>
                            <br><small><?= date('d/m/Y H:i', strtotime($log['data_cadastro'])) ?></small>
                        </td>
                        
                        <td>
                            <?php 
                            $alt = trim($log['alterado_por'] ?? '');
                            if (!empty($alt)): 
                                $nome_alt = explode(' ', $alt)[0];
                            ?>
                                <span class="badge-update"><?= htmlspecialchars($nome_alt) ?></span>
                                <br>
                                <small>
                                    <?= !empty($log['data_alteracao']) ? date('d/m/Y H:i', strtotime($log['data_alteracao'])) : '--' ?>
                                </small>
                            <?php else: ?>
                                <span style="color: #bbb; font-style: italic;">Inalterado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" style="text-align:center; padding: 50px;">Nenhum registro encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <div class="paginacao">
        <?php if ($pagina_atual > 1): ?>
            <a href="?pagina=1">«</a>
            <a href="?pagina=<?= $pagina_atual - 1 ?>">Anterior</a>
        <?php endif; ?>

        <?php 
        $max_links = 5;
        $start = max(1, $pagina_atual - 2);
        $end = min($total_paginas, $start + $max_links - 1);
        
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?pagina=<?= $i ?>" class="<?= ($i == $pagina_atual) ? 'atual' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="?pagina=<?= $pagina_atual + 1 ?>">Próximo</a>
            <a href="?pagina=<?= $total_paginas ?>">»</a>
        <?php endif; ?>
    </div>
    <p style="text-align:center; font-size: 11px; color: #888;">Total: <?= $total_registros ?> logs</p>
    <?php endif; ?>
</div>

</body>
</html>
