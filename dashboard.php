<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

$alerta_erro = null;
if (isset($_SESSION['alerta_erro']) && !empty($_SESSION['alerta_erro'])) {
    $alerta_erro = $_SESSION['alerta_erro'];
    unset($_SESSION['alerta_erro']); 
}

require_once 'conexao.php'; 
$pdo->exec("SET NAMES 'UTF8'");

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário'; 

// Configurações da Paginação
$limite_por_pagina = 300; 
$pagina_atual = $_GET['pagina'] ?? 1; 
$offset = ($pagina_atual - 1) * $limite_por_pagina;

$termo_busca = $_GET['termo_busca'] ?? '';
$where_clause = '';

if (!empty($termo_busca)) {
    $termo_sql = "%" . $termo_busca . "%";
    $where_clause = " WHERE incidente ILIKE :termo OR evento ILIKE :termo OR endereco ILIKE :termo OR area ILIKE :termo OR regiao ILIKE :termo OR site ILIKE :termo OR otdr ILIKE :termo OR CAST(id AS TEXT) ILIKE :termo";
}

try {
    $total_registros_bd = $pdo->query("SELECT COUNT(id) FROM controle")->fetchColumn();
    $total_paginas = ceil($total_registros_bd / $limite_por_pagina);

    $sql_consulta = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle" . $where_clause . " ORDER BY id LIMIT :limite OFFSET :offset";
    $stmt_consulta = $pdo->prepare($sql_consulta);
    $stmt_consulta->bindValue(':limite', (int)$limite_por_pagina, PDO::PARAM_INT);
    $stmt_consulta->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    if (!empty($termo_busca)) { $stmt_consulta->bindValue(':termo', $termo_sql); }

    $stmt_consulta->execute();
    $lista_incidentes = $stmt_consulta->fetchAll();
    $total_nesta_pagina = count($lista_incidentes);
    $total_incidentes = $total_registros_bd;
    $ultimo_cadastro = $pdo->query("SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1")->fetchColumn(); 

} catch (PDOException $e) { die("Erro ao consultar: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistema de Controle</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['gauge']});
        google.charts.setOnLoadCallback(() => {
            var data = google.visualization.arrayToDataTable([['Label', 'Value'],['Incidentes', <?php echo $total_incidentes; ?>]]);
            var options = { width: 400, height: 120, redFrom: 0, redTo: 3000, yellowFrom: 3001, yellowTo: 10000, greenFrom: 10001, greenTo: 35000, max: 35000 };
            new google.visualization.Gauge(document.getElementById('chart_div')).draw(data, options);
        });
    </script>
    <style>
        #loader-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(255, 255, 255, 0.9); z-index: 999999; backdrop-filter: blur(8px); flex-direction: column; justify-content: center; align-items: center; }
        .ampulheta { font-size: 80px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .texto-loader { margin-top: 20px; font-weight: bold; color: #e02810; font-size: 1.2em; text-align: center; }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #fff; min-height: 100vh; overflow-x: hidden; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.15; z-index: -3; }
        body::after { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%); filter: blur(80px); animation: moveColors 25s ease-in-out infinite alternate; }
        @keyframes moveColors { 0% { transform: translate(0, 0); } 100% { transform: translate(2%, -2%); } }
        #titulo-incidentes, .admin-header h3 { display: block; text-align: center; margin: 30px auto; font-size: 1.8em; color: #e02810ff; text-decoration: underline; transition: all 0.3s ease; cursor: pointer; width: fit-content; padding: 5px 15px; }
        #titulo-incidentes:hover, .admin-header h3:hover { color: #007bff; transform: scale(1.05); text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); }
        table, .user-table { background-color: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px); border-collapse: collapse; margin: 20px auto; width: 95%; max-width: 1100px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        th { background-color: #007bff; color: white; padding: 12px 15px; text-align: left; font-weight: bold; }
        td { border: 1px solid #ddd; padding: 10px 15px; background-color: rgba(255, 255, 255, 0.85); transition: background-color 0.2s; }
        tr:nth-child(even) td { background-color: rgba(247, 247, 247, 0.9); }
        tbody tr:hover td { background-color: rgba(233, 247, 255, 0.95) !important; cursor: pointer; }
        .pagination { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px; margin: 30px auto; padding: 10px; max-width: 95%; }
        .btn-page { display: inline-flex; justify-content: center; align-items: center; min-width: 40px; height: 40px; padding: 0 15px; text-decoration: none; color: #007bff; border: 2px solid #007bff; border-radius: 8px; background-color: transparent; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 14px; font-weight: 700; }
        .btn-page:not(.active):not(.disabled):hover { background-color: #007bff; color: white; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4); border-color: #0056b3; }
        .btn-page.active { background-color: #007bff; color: white; font-weight: 800; border-color: #0056b3; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); }
        .btn-page.disabled { color: #ccc; border-color: #ddd; cursor: not-allowed; background-color: #f9f9f9; opacity: 0.6; }
        .user-table { width: 90%; max-width: 800px; }
        table a, .user-table a { color: #17a2b8; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        table a:hover, .user-table a:hover { color: #0056b3; text-decoration: underline; }
        .admin-header { margin-top: 50px; text-align: center; }
        footer { width: 100%; border-radius: 5px; border: 1px solid #131212ff; padding: 20px 0; background-color: #b2cae2ff; max-width: 800px; margin: 20px auto; color: #239406ff; border-top: 5px solid #3498db; }
        .estatisticas { display: flex; flex-direction: column; align-items: center; padding: 0 20px; }
        .estatisticas h3, .card-stats h4 { font-size: 1.5em; color: #e20e0eff; margin-bottom: 20px; padding-bottom: 5px; border-bottom: 2px solid #db4d34ff; letter-spacing: 1px; }
        .estatisticas p { font-size: 1.1em; padding: 15px 30px; border: none; border-radius: 8px; background-color: #34495e; color: #ecf0f1; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); transition: all 0.3s ease-in-out; margin: 10px 20px; }
        .estatisticas p:hover { box-shadow: 0 8px 12px rgba(0, 0, 0, 0.5); transform: translateY(-3px); border: 1px solid #3498db; cursor: pointer; }
        .estatisticas strong { color: #e67e22; font-size: 1.5em; margin-left: 10px; font-weight: bold; }
        .header { width: 100%; padding: 40px 0; text-align: center; background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px); border-bottom: 3px solid #e02810; margin-bottom: 30px; }
        /* Animação Específica para o Nome do Usuário */
        .header h2 span.user-name { 
            color: #e02810; 
            font-weight: 900; 
            text-transform: uppercase; 
            position: relative; 
            display: inline-block; 
            padding: 0 10px; 
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            
            /* Revelação com atraso para o nome aparecer depois do texto */
            opacity: 0;
            animation: revealName 0.5s ease-out 0.6s forwards;
        }
        
        .header h2 span.user-name:hover { 
            transform: scale(1.1); 
            color: #007bff; 
            text-shadow: 3px 6px 10px rgba(0, 0, 0, 0.2); 
        }
        
        /* Definição dos Movimentos */
        @keyframes surgeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes revealName {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .logout-container { position: absolute; top: 25px; right: 30px; z-index: 1000; }
        .btn-logout { display: inline-block; padding: 10px 22px; background-color: #007bff; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; border: 2px solid transparent; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-logout:hover { background-color: #0056b3; border-color: #004085; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15); }
        @media (max-width: 600px) { .logout-container { top: 15px; right: 15px; } .btn-logout { padding: 8px 15px; font-size: 12px; } }
        .cadastro-container { text-align: center; margin: 20px 0 40px 0; }
        .btn-cadastrar { display: inline-block; background: linear-gradient(135deg, #1167c2 0%, #004a99 100%); color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 1.1em; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(17, 103, 194, 0.4); transition: all 0.3s ease; border: 2px solid transparent; }
        .btn-cadastrar:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 25px rgba(17, 103, 194, 0.6); background: linear-gradient(135deg, #e02810 0%, #b31d0a 100%); color: white; }
        .btn-cadastrar:active { transform: translateY(0); }
        #form-busca { display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 40px; }
        #form-busca label { font-weight: 800; color: #333; text-transform: uppercase; font-size: 0.95em; letter-spacing: 0.5px; }
        #form-busca input[type="text"] { width: 280px; padding: 12px 18px; border: 2px solid #ddd; border-radius: 10px; font-weight: 600; font-size: 1em; transition: all 0.3s ease; outline: none; background: rgba(255, 255, 255, 0.9); }
        #form-busca input[type="text"]:focus { border-color: #007bff; box-shadow: 0 0 12px rgba(0, 123, 255, 0.2); background: #fff; }
        .btn-pesquisar { padding: 12px 28px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); }
        .btn-pesquisar:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 8px 20px rgba(0, 123, 255, 0.5); background: linear-gradient(135deg, #0056b3 0%, #004085 100%); }
        .btn-pesquisar:active { transform: scale(0.98); }
        .modal-erro-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); overflow: auto; }
        .modal-erro-content { background-color: #fff; margin: 10% auto; padding: 20px; border: 3px solid #dc3545; border-radius: 8px; width: 80%; max-width: 450px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); text-align: center; }
        .modal-erro-titulo { color: #dc3545; font-size: 1.5em; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        #modal-erro-texto { font-size: 1.1em; color: #333; margin-bottom: 20px; }
        .modal-erro-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .modal-erro-close:hover, .modal-erro-close:focus { color: #000; text-decoration: none; cursor: pointer; }
        .btn-fechar-modal { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-fechar-modal:hover { background-color: #0056b3; }
        .tabela-container-scroll { overflow-x: auto; max-height: 75vh; position: relative; border-radius: 8px; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
        .tabela-container-scroll table thead th { position: sticky; top: 0; z-index: 10; background-color: #007bff; }
        .tabela-container-scroll::-webkit-scrollbar { height: 12px; }
        .tabela-container-scroll::-webkit-scrollbar-thumb { background: #007bff; border-radius: 10px; }
    </style>
</head>
<body>
    <div id="loader-overlay">
        <div class="ampulheta">⏳</div>
        <div class="texto-loader">Buscando informações no sistema...</div>
    </div>

    <div class="header">
        <h2>Bem-vindo <span class="user-name"><?php echo htmlspecialchars($nome_do_usuario); ?></span>!</h2>
    </div>

    <div class="logout-container"><a href="logout.php" class="btn-logout">Sair</a></div>

    <nav style="text-align: center; margin-bottom: 20px;">
        <a href="dashboard.php" class="btn-page active">Incidentes</a>
        <a href="usuarios.php" class="btn-page">Gestão de Usuários</a>
        <a href="auditoria.php" class="btn-page <?php echo (basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'active' : ''; ?>">🔍 Auditoria</a>
    </nav>

    <div class="cadastro-container">
        <a href="cadastro.php" class="btn-cadastrar">Cadastrar Novo Incidente</a>
    </div>

    <div class="container-titulo">
        <form id="form-busca" method="GET" action="dashboard.php">
            <label>Buscar:</label>
            <input type="text" name="termo_busca" placeholder="Digite sua busca..." value="<?php echo htmlspecialchars($termo_busca); ?>">
            <button type="submit" class="btn-pesquisar">Pesquisar</button>
        </form>
    </div>
        
    <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>

    <div class="card-stats" style="text-align: center; margin-bottom: 30px; padding: 20px; background-color: rgba(255, 255, 255, 0.6); border-radius: 12px; max-width: 600px; margin: 20px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h4>ESTATÍSTICAS RÁPIDAS</h4>
        <p style="font-size: 1.1em;">Total Geral: <strong><?php echo $total_incidentes; ?></strong></p>
        <p style="font-size: 1.1em;">Último Cadastro: <strong><?php echo $ultimo_cadastro ?: 'Nenhum'; ?></strong></p>
        <div class="destaque-pagina" style="border-top: 1px solid #ddd; margin-top: 15px; padding-top: 15px;">
            Incidentes exibidos nesta página: <span style="font-size: 1.5em; color:#007bff; font-weight: 900;"><?php echo $total_nesta_pagina; ?></span>
        </div>
        <div id="chart_div" style="width: 400px; height: 120px; margin: 10px auto;"></div>
    </div>

    <div class="tabela-container-scroll">
    <table>
        <thead>
            <tr><th>ID</th><th>Incidente</th><th>Evento</th><th>Endereço</th><th>Área</th><th>Região</th><th>Site</th><th>OTDR</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lista_incidentes as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo !empty($c['incidente']) ? htmlspecialchars($c['incidente']) : '-'; ?></td>
                <td><?php echo !empty($c['evento']) ? htmlspecialchars($c['evento']) : '-'; ?></td>
                <td><?php echo !empty($c['endereco']) ? htmlspecialchars($c['endereco']) : '-'; ?></td>
                <td><?php echo !empty($c['area']) ? htmlspecialchars($c['area']) : '-'; ?></td>
                <td><?php echo !empty($c['regiao']) ? htmlspecialchars($c['regiao']) : '-'; ?></td>
                <td><?php echo !empty($c['site']) ? htmlspecialchars($c['site']) : '-'; ?></td>
                <td><?php echo !empty($c['otdr']) ? htmlspecialchars($c['otdr']) : '-'; ?></td>
                <td>
                    <a href="alterar.php?id=<?php echo $c['id']; ?>" style="color:blue; font-weight:bold;">Editar</a> | 
                    <a href="processar_crud.php?acao=excluir&id=<?php echo $c['id']; ?>" onclick="return confirm('Excluir?')" style="color:red; font-weight:bold;">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="pagination">
        <?php if ($total_paginas > 1): ?>
            <?php $base_url = "dashboard.php?termo_busca=" . urlencode($termo_busca) . "&"; ?>
            <?php if ($pagina_atual > 1): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual - 1); ?>" class="btn-page">Anterior</a>
            <?php endif; ?>
            <?php 
            $gap = 2;
            for ($i = 1; $i <= $total_paginas; $i++): 
                if ($i == 1 || $i == $total_paginas || ($i >= $pagina_atual - $gap && $i <= $pagina_atual + $gap)):
            ?>
                <a href="<?php echo $base_url . 'pagina=' . $i; ?>" class="btn-page <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php 
                elseif ($i == $pagina_atual - $gap - 1 || $i == $pagina_atual + $gap + 1):
                    echo "<span class='btn-page disabled'>...</span>";
                endif;
            endfor; 
            ?>
            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual + 1); ?>" class="btn-page">Próximo</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="modal-erro" class="modal-erro-overlay">
        <div class="modal-erro-content">
            <span class="modal-erro-close" onclick="fecharModal()">×</span>
            <h4 class="modal-erro-titulo">⚠️ Erro de Permissão</h4>
            <p id="modal-erro-texto"></p>
            <button onclick="fecharModal()" class="btn-fechar-modal">Entendi</button>
        </div>
    </div>

    <script>
        const loader = document.getElementById('loader-overlay');
        const mensagemErro = <?php echo json_encode($alerta_erro ?? ''); ?>;
        function fecharModal() { document.getElementById('modal-erro').style.display = 'none'; }
        if (mensagemErro) {
            document.getElementById('modal-erro-texto').innerText = mensagemErro;
            document.getElementById('modal-erro').style.display = 'block';
        }
        document.getElementById('form-busca').addEventListener('submit', () => loader.style.display = 'flex');
        document.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function() {
                if(!this.classList.contains('active') && !this.classList.contains('disabled')) loader.style.display = 'flex';
            });
        });
    </script>
</body>
</html>




