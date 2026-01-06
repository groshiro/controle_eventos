<?php
// 1. SEGURANÇA E SESSÃO (DEVE SER O PRIMEIRO BLOCO)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

// Tratamento de alertas de erro para o Modal
$alerta_erro = $_SESSION['alerta_erro'] ?? null;
if ($alerta_erro) unset($_SESSION['alerta_erro']); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexao.php'; 
$pdo->exec("SET NAMES 'UTF8'");

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário'; 

// 2. LÓGICA DE DADOS E PAGINAÇÃO
$limite_por_pagina = 300; 
$pagina_atual = $_GET['pagina'] ?? 1; 
$offset = ($pagina_atual - 1) * $limite_por_pagina;

$termo_busca = $_GET['termo_busca'] ?? '';
$where_clause = '';
$params = [];

if (!empty($termo_busca)) {
    $termo_sql = "%" . $termo_busca . "%";
    $where_clause = " WHERE incidente ILIKE :termo OR evento ILIKE :termo OR endereco ILIKE :termo OR area ILIKE :termo OR regiao ILIKE :termo OR site ILIKE :termo OR otdr ILIKE :termo OR CAST(id AS TEXT) ILIKE :termo";
    $params['termo'] = $termo_sql;
}

try {
    $total_registros_bd = $pdo->query("SELECT COUNT(id) FROM controle")->fetchColumn();
    $total_paginas = ceil($total_registros_bd / $limite_por_pagina);

    $sql_consulta = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle" . $where_clause . " ORDER BY id LIMIT :limite OFFSET :offset";
    $stmt_consulta = $pdo->prepare($sql_consulta);
    $stmt_consulta->bindValue(':limite', (int)$limite_por_pagina, PDO::PARAM_INT);
    $stmt_consulta->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    if (!empty($termo_busca)) $stmt_consulta->bindValue(':termo', $termo_sql);

    $stmt_consulta->execute();
    $lista_incidentes = $stmt_consulta->fetchAll();
    $total_nesta_pagina = count($lista_incidentes);

    $total_incidentes = $total_registros_bd;
    $ultimo_cadastro = $pdo->query("SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1")->fetchColumn() ?: 'Nenhum'; 
    $total_usuarios = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $lista_usuarios = $pdo->query("SELECT id, nome, login, nivel_permissao FROM usuario ORDER BY id ASC")->fetchAll();

} catch (PDOException $e) {
    die("Erro ao consultar: " . $e->getMessage());
}
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
            var options = { width: 400, height: 120, redFrom: 0, redTo: 3000, yellowFrom: 3001, yellowTo: 10000, greenFrom: 10001, greenTo: 25000, max: 25000 };
            new google.visualization.Gauge(document.getElementById('chart_div')).draw(data, options);
        });
    </script>
    <style>
        /* 1. ESTRUTURA E FUNDO */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background-color: #fff; min-height: 100vh; overflow-x: hidden; position: relative; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.15; z-index: -3; }
        body::after { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%); filter: blur(80px); animation: moveColors 25s ease-in-out infinite alternate; }
        @keyframes moveColors { 0% { transform: translate(0, 0); } 100% { transform: translate(2%, -2%); } }

        /* 2. AMPULHETA E MODAL */
        #loader-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(255, 255, 255, 0.9); z-index: 999999; backdrop-filter: blur(8px); flex-direction: column; justify-content: center; align-items: center; }
        .ampulheta { font-size: 80px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .texto-loader { margin-top: 20px; font-weight: 800; color: #e02810; text-transform: uppercase; }

        .modal-erro-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); }
        .modal-erro-content { background-color: #fff; margin: 10% auto; padding: 25px; border: 3px solid #dc3545; border-radius: 12px; width: 80%; max-width: 450px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

        /* 3. CABEÇALHO E BOTÕES */
        .header { width: 100%; padding: 40px 0; text-align: center; background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px); border-bottom: 3px solid #e02810; margin-bottom: 30px; }
        .header h2 { margin: 0; font-size: 2.5em; color: #1a1a1a; font-weight: 800; letter-spacing: -1px; }
        .header h2 span.user-name { color: #e02810; font-weight: 900; text-transform: uppercase; transition: 0.3s; display: inline-block; }
        .header h2 span.user-name:hover { transform: scale(1.1); color: #007bff; }

        .logout-container { position: absolute; top: 25px; right: 30px; z-index: 1000; }
        .btn-logout { display: inline-block; padding: 10px 22px; background-color: #007bff; color: white; text-decoration: none; border-radius: 6px; font-weight: 800; text-transform: uppercase; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-logout:hover { background-color: #0056b3; transform: translateY(-2px); }

        .btn-cadastrar { display: inline-block; background: linear-gradient(135deg, #1167c2 0%, #004a99 100%); color: white; padding: 12px 35px; border-radius: 50px; text-decoration: none; font-weight: 800; text-transform: uppercase; box-shadow: 0 4px 15px rgba(17,103,194,0.4); transition: 0.3s; }
        .btn-cadastrar:hover { transform: translateY(-3px); background: linear-gradient(135deg, #e02810 0%, #b31d0a 100%); }

        /* 4. BUSCA E TÍTULOS */
        .btn-pesquisar { padding: 12px 28px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800; text-transform: uppercase; transition: 0.3s; box-shadow: 0 4px 12px rgba(0,123,255,0.3); }
        .btn-pesquisar:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 8px 20px rgba(0,123,255,0.5); }

        #titulo-incidentes, .admin-header h3 { display: block; text-align: center; margin: 30px auto; font-size: 1.8em; color: #e02810; text-decoration: underline; font-weight: 800; transition: 0.3s; cursor: pointer; width: fit-content; }
        #titulo-incidentes:hover, .admin-header h3:hover { color: #007bff; transform: scale(1.05); }

        /* 5. TABELA COM ROLAGEM FIXA (SOLUÇÃO POSITION STICKY) */
        .container-tabela-pai { width: 95%; margin: 0 auto; }
        .tabela-responsiva-fixa { 
            overflow-x: auto; 
            max-height: 70vh; /* Permite visualizar a barra horizontal sempre na tela */
            position: relative;
            border-radius: 8px;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
        }
        table { border-collapse: collapse; width: 100%; min-width: 1000px; }
        th { 
            background-color: #007bff; color: white; padding: 15px; text-align: left; 
            position: sticky; top: 0; z-index: 10; /* Trava o cabeçalho no topo */
        }
        td { border: 1px solid #ddd; padding: 12px; background-color: rgba(255,255,255,0.85); transition: 0.2s; }
        tr:nth-child(even) td { background-color: rgba(247, 247, 247, 0.9); }
        tbody tr:hover td { background-color: rgba(233, 247, 255, 0.95) !important; cursor: pointer; }

        /* 6. PAGINAÇÃO E ESTATÍSTICAS */
        .pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin: 30px 0; }
        .btn-page { display: inline-flex; justify-content: center; align-items: center; min-width: 40px; height: 40px; text-decoration: none; color: #007bff; border: 2px solid #007bff; border-radius: 8px; font-weight: 700; transition: 0.3s; }
        .btn-page:hover:not(.active) { background: #007bff; color: white; transform: translateY(-3px); }
        .btn-page.active { background: #007bff; color: white; font-weight: 800; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }

        .card-stats h4, .estatisticas h3 { font-size: 1.5em; color: #e20e0eff; margin-bottom: 20px; padding-bottom: 5px; border-bottom: 2px solid #db4d34ff; letter-spacing: 1px; font-weight: 800; text-transform: uppercase; }
        .estatisticas p { font-size: 1.1em; padding: 15px 30px; border-radius: 8px; background-color: #34495e; color: #ecf0f1; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: 0.3s; margin: 10px; font-weight: bold; }
        .estatisticas p:hover { transform: translateY(-3px); box-shadow: 0 8px 12px rgba(0,0,0,0.5); }
        .estatisticas strong { color: #e67e22; font-size: 1.5em; margin-left: 10px; }

        footer { width: 100%; max-width: 800px; margin: 40px auto 20px auto; background: #b2cae2; border-radius: 5px; border-top: 5px solid #3498db; padding: 20px 0; }
    </style>
</head>
<body>

    <div id="loader-overlay">
        <div class="ampulheta">⏳</div>
        <div class="texto-loader">Buscando informações no sistema...</div>
    </div>

    <div class="logout-container"><a href="logout.php" class="btn-logout">Sair</a></div>

    <div class="header">
        <h2>BEM-VINDO, <span class="user-name"><?php echo htmlspecialchars($nome_do_usuario); ?></span>!</h2>
    </div>

    <div class="cadastro-container">
        <a href="cadastro.php" class="btn-cadastrar">+ CADASTRAR NOVO INCIDENTE</a>
    </div>

    <div class="container-titulo">
        <form id="form-busca" method="GET" action="dashboard.php">
            <label>BUSCAR:</label>
            <input type="text" name="termo_busca" placeholder="Digite sua busca..." value="<?php echo htmlspecialchars($termo_busca); ?>">
            <button type="submit" class="btn-pesquisar">Pesquisar</button>
        </form>
        
        <div class="card-stats" style="text-align: center; margin-bottom: 30px; padding: 20px; background-color: rgba(255, 255, 255, 0.6); border-radius: 12px; max-width: 600px; margin: 20px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h4>ESTATÍSTICAS RÁPIDAS</h4>
            <p style="font-size: 1.1em;">Total Geral: <strong><?php echo $total_incidentes; ?></strong></p>
            <p style="font-size: 1.1em;">Último Cadastro: <strong><?php echo $ultimo_cadastro; ?></strong></p>
            <div style="border-top: 1px solid #ddd; margin-top: 15px; padding-top: 15px;">
                INCIDENTES NESTA PÁGINA: <span style="font-size: 1.5em; color:#007bff; font-weight: 900;"><?php echo $total_nesta_pagina; ?></span>
            </div>
            <div id="chart_div" style="width: 100%; height: 120px; display: flex; justify-content: center; margin-top: 10px;"></div>
        </div>

        <h3 id="titulo-incidentes">INCIDENTES CADASTRADOS</h3>
    </div>

    <div class="container-tabela-pai">
        <div class="tabela-responsiva-fixa">
            <table>
                <thead>
                    <tr><th>ID</th><th>INCIDENTE</th><th>EVENTO</th><th>ENDEREÇO</th><th>ÁREA</th><th>REGIÃO</th><th>SITE</th><th>OTDR</th><th>AÇÕES</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_incidentes as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['incidente'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['evento'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['endereco'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['area'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['regiao'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['site'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['otdr'] ?: '-'); ?></td>
                        <td style="white-space: nowrap;">
                            <a href="alterar.php?id=<?php echo $c['id']; ?>" style="color:blue; font-weight:800;">EDITAR</a> | 
                            <a href="processar_crud.php?acao=excluir&id=<?php echo $c['id']; ?>" onclick="return confirm('EXCLUIR?')" style="color:red; font-weight:800;">EXCLUIR</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">
        <?php if ($total_paginas > 1): ?>
            <?php $base_url = "dashboard.php?termo_busca=" . urlencode($termo_busca) . "&"; ?>
            <?php if ($pagina_atual > 1): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual - 1); ?>" class="btn-page">Anterior</a>
            <?php endif; ?>
            <?php 
            for ($i = 1; $i <= $total_paginas; $i++): 
                if ($i == 1 || $i == $total_paginas || ($i >= $pagina_atual - 2 && $i <= $pagina_atual + 2)): ?>
                <a href="<?php echo $base_url . 'pagina=' . $i; ?>" class="btn-page <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php elseif ($i == $pagina_atual - 3 || $i == $pagina_atual + 3): echo "<span class='btn-page disabled'>...</span>"; endif; endfor; ?>
            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual + 1); ?>" class="btn-page">Próximo</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="admin-header"><h3>ADMINISTRAÇÃO DE USUÁRIOS</h3></div>
    <table class="user-table" style="margin-bottom: 50px;">
        <thead><tr><th>ID</th><th>NOME</th><th>LOGIN</th><th>PERMISSÃO</th><th>AÇÕES</th></tr></thead>
        <tbody>
            <?php foreach ($lista_usuarios as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                <td><?php echo htmlspecialchars($u['login']); ?></td>
                <td><span style="background:#eee; padding:5px 10px; border-radius:4px; font-weight:800;"><?php echo $u['nivel_permissao']; ?></span></td>
                <td><a href="alterar_usuario.php?id=<?php echo $u['id']; ?>" style="color:blue; font-weight:800;">EDITAR</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <footer>
        <div class="estatisticas">
            <h3>ESTATÍSTICAS RÁPIDAS</h3>
            <p>USUÁRIOS CADASTRADOS: <strong><?php echo $total_usuarios; ?></strong></p>
        </div>
    </footer>

    <div id="modal-erro" class="modal-erro-overlay">
        <div class="modal-erro-content">
            <h4 class="modal-erro-titulo">⚠️ Erro de Permissão</h4>
            <p id="modal-erro-texto"></p>
            <button onclick="fecharModal()" class="btn-pesquisar">Entendi</button>
        </div>
    </div>

    

    <script>
        // Modal de Erro
        const mensagemErro = <?php echo json_encode($alerta_erro ?? ''); ?>;
        const modal = document.getElementById('modal-erro');
        if (mensagemErro) {
            document.getElementById('modal-erro-texto').innerText = mensagemErro;
            modal.style.display = 'block';
        }
        function fecharModal() { modal.style.display = 'none'; }

        // Loader
        const loader = document.getElementById('loader-overlay');
        document.getElementById('form-busca').addEventListener('submit', () => loader.style.display = 'flex');
        document.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function() {
                if(!this.classList.contains('active')) loader.style.display = 'flex';
            });
        });
    </script>
</body>
</html>
