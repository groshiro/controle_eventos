<?php
// Arquivo: dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$alerta_erro = null;
if (isset($_SESSION['alerta_erro']) && !empty($_SESSION['alerta_erro'])) {
    $alerta_erro = $_SESSION['alerta_erro'];
    unset($_SESSION['alerta_erro']); 
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexao.php'; 

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado']; 

if ($pdo === null) { 
    die("❌ Erro: Falha na conexão com o banco de dados. Status: " . $status_conexao);
}

if (!isset($_SESSION['usuario_logado'])) {
    echo "PONTO DE ALERTA: Usuário não logado (Redirecionamento evitado).<br>";
} else {
    $login_usuario = $_SESSION['usuario_logado'];
}

$lista_incidentes = [];
$limite_por_pagina = 1500; 
$pagina_atual = $_GET['pagina'] ?? 1; 
$offset = ($pagina_atual - 1) * $limite_por_pagina;

$termo_busca = $_GET['termo_busca'] ?? '';
$where_clause = '';
$params = [];
$total_encontrado = 0; 

if (!empty($termo_busca)) {
    $termo_sql = "%" . $termo_busca . "%";
    $where_clause = " WHERE incidente ILIKE :termo OR evento ILIKE :termo OR endereco ILIKE :termo OR area ILIKE :termo OR regiao ILIKE :termo OR site ILIKE :termo OR otdr ILIKE :termo OR CAST(id AS TEXT) ILIKE :termo";
    $params['termo'] = $termo_sql;
}

try {
    $sql_total_geral = "SELECT COUNT(id) FROM controle";
    $total_registros_bd = $pdo->query($sql_total_geral)->fetchColumn();
    $total_paginas = ceil($total_registros_bd / $limite_por_pagina);

    $sql_consulta = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle" . $where_clause . " ORDER BY id LIMIT :limite OFFSET :offset";
    $stmt_consulta = $pdo->prepare($sql_consulta);

    $stmt_consulta->bindValue(':limite', (int)$limite_por_pagina, PDO::PARAM_INT);
    $stmt_consulta->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    if (!empty($termo_busca)) {
        $stmt_consulta->bindValue(':termo', $termo_sql);
    }

    $stmt_consulta->execute();
    $lista_incidentes = $stmt_consulta->fetchAll();
    $total_encontrado = count($lista_incidentes);

    $total_incidentes = $pdo->query("SELECT COUNT(id) FROM controle")->fetchColumn();
    $ultimo_cadastro = $pdo->query("SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1")->fetchColumn(); 

} catch (PDOException $e) {
    die("Erro ao consultar incidentes: " . $e->getMessage());
}

$lista_usuarios = [];
$total_usuarios = 0; 
try {
    $total_usuarios = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $sql_usuarios = "SELECT id, nome, login, nivel_permissao FROM usuario ORDER BY id ASC";
    $lista_usuarios = $pdo->query($sql_usuarios)->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao consultar usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Lista de Usuários</title>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['gauge']});
        google.charts.setOnLoadCallback(drawChart);     

        function drawChart() {
            var total = <?php echo $total_incidentes; ?>;       
            var data = google.visualization.arrayToDataTable([  
                ['Label', 'Value'],
                ['Incidentes', total] 
            ]);
            var options = {
                width: 400, height: 120,
                redFrom: 0, redTo: 3000,
                yellowFrom: 3001, yellowTo: 10000,
                greenFrom: 10001, greenTo: 25000,
                minorTicks: 5, max: 25000
            };
            var chart = new google.visualization.Gauge(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>
    <style>
        /* ESTILO DA AMPULHETA (LOADER) */
        #loader-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10000;
            backdrop-filter: blur(5px);
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .ampulheta { font-size: 60px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .texto-loader { margin-top: 15px; font-weight: bold; color: #e02810; }

        /* Mensagens de Confirmação */
        .status-msg { max-width: 600px; margin: 10px auto; padding: 15px; border-radius: 5px; text-align: center; font-weight: bold; }
        .status-sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        /* SEUS ESTILOS ORIGINAIS MANTIDOS */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #ffffff; position: relative; min-height: 100vh; }
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('claro-operadora.jpg'); background-size: cover;
            background-position: center; background-repeat: no-repeat; opacity: 0.15; filter: grayscale(50%); z-index: -3;
        }
        body::after {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 50% 50%, rgba(0, 123, 255, 0.02) 0%, transparent 60%);
            filter: blur(80px); animation: moveColors 25s ease-in-out infinite alternate;
        }
        @keyframes moveColors { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-2%, 2%) scale(1.05); } 100% { transform: translate(2%, -2%) scale(1); } }
        table, .user-table { background-color: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); width: 100%; border-collapse: collapse; margin: 20px auto; max-width: 1000px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); border-radius: 8px; overflow: hidden; }
        .admin-header { margin-top: 50px; text-align: center; /* ISSO CENTRALIZA O TEXTO */ width: 100%;}
        .header { color: black; padding: 10px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 1.5em; text-decoration: underline; }
        .container-titulo { text-align: center; }
        h3 { display: inline-block; color: #235303ff; text-decoration: underline; margin-top: 0; padding: 10px; }
        p { font-size: 18px; text-align: center; font-weight: bold; }
        .btn-pesquisar, .btn-page { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s ease; }
        .btn-page { display: inline-block; padding: 8px 12px; margin: 0 5px; text-decoration: none; color: #007bff; border: 1px solid #007bff; background-color: transparent; }
        .btn-page.active { background-color: #007bff; color: white; font-weight: bold; }
        .btn-page.disabled { color: #ccc; border-color: #ccc; cursor: default; background-color: #f9f9f9; }
        .link-cadastro { display: inline-block; color: #edf5f5ff; text-decoration: none; font-weight: bold; padding: 8px 15px; border: 1px solid #021120ff; border-radius: 5px; background-color: #1167c2ee; }
        .logout-container { position: absolute; top: 20px; right: 20px; z-index: 1000; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: #0b34ebff; color: white; text-decoration: none; border-radius: 8px; border: black 2px solid; font-weight: bold; }
        table th, .user-table th { background-color: #007bff; color: white; padding: 12px 15px; text-align: left; }
        table td, .user-table td { border: 1px solid #ddd; padding: 10px 15px; background-color: rgba(255, 255, 255, 0.85); }
        footer { width: 100%; border-radius: 5px; border: 1px solid #131212ff; padding: 20px 0; background-color: #b2cae2ff; max-width: 800px; margin: 20px auto; color: #239406ff; border-top: 5px solid #3498db; }
        .estatisticas { display: flex; flex-direction: column; align-items: center; padding: 0 20px; }
        .estatisticas h3 { font-size: 1.5em; color: #e20e0eff; margin-bottom: 20px; border-bottom: 2px solid #db4d34ff; }
        .estatisticas p { font-size: 1.1em; padding: 15px 30px; background-color: #34495e; color: #ecf0f1; border-radius: 8px; margin: 10px 20px; }
        .estatisticas strong { color: #e67e22; font-size: 1.5em; margin-left: 10px; }
        
        /* MODAL ERRO */
        .modal-erro-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); }
        .modal-erro-content { background-color: #fff; margin: 10% auto; padding: 20px; border: 3px solid #dc3545; border-radius: 8px; width: 80%; max-width: 450px; text-align: center; }
        .btn-fechar-modal { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

    <div id="loader-overlay">
        <div class="ampulheta">⏳</div>
        <div class="texto-loader">Buscando no banco de dados...</div>
    </div>

    <div class="header">
        <h2 id="titulo-saudacao">Bem-vindo <?php echo htmlspecialchars($nome_do_usuario); ?>!</h2>
    </div>

    <p style="text-align: center; margin-top: 15px;">
        Novo incidente: <a href="cadastro.php" class="link-cadastro">Cadastrar Incidente</a>
    </p>

    <div class="logout-container">
        <a href="logout.php" class="btn-logout">Sair</a>
    </div>

    <div class="container-titulo">
        <div style="max-width: 600px; margin: 0 auto; padding: 10px;">
            <form id="form-busca" method="GET" action="dashboard.php" style="text-align: center; margin-bottom: 30px;">
                <label for="termo" style="font-weight: bold; margin-right: 10px;">Buscar Dados:</label>
                <input type="text" id="termo" name="termo_busca" placeholder="Busca por conteúdo..." 
                       style="width: 250px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                       value="<?php echo htmlspecialchars($_GET['termo_busca'] ?? ''); ?>">
                <button type="submit" class="btn-pesquisar">Pesquisar</button>
            </form>
            <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'alterado'): ?>
                <div class="status-msg status-sucesso">✅ Incidente atualizado com sucesso!</div>
            <?php elseif ($_GET['status'] == 'excluido'): ?>
                <div class="status-msg status-sucesso">🗑️ Registro removido com sucesso!</div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 30px; padding: 15px; background-color: rgba(255, 255, 255, 0.5); border-radius: 10px; max-width: 600px; margin: 20px auto; z-index: 5;">
            <h4>Estatísticas Rápidas</h4>
            <p>
                Total de Incidentes Cadastrados: <strong><?php echo $total_incidentes; ?></strong><br>
                Último Cadastro em: <strong><?php echo $ultimo_cadastro ? htmlspecialchars($ultimo_cadastro) : 'Nenhum'; ?></strong>
            </p>
            <div id="chart_div" style="width: 400px; height: 120px; margin: 0 auto;"></div>
        </div>

        <div id="status-busca" style="margin-top: 15px; font-weight: bold; text-align: center;">
            <?php 
                $termo_exibido = $_GET['termo_busca'] ?? '';
                if (!empty($termo_exibido)) {
                    echo $total_encontrado . " Resultados encontrados para: \"" . htmlspecialchars($termo_exibido) . "\"";
                } else {
                    echo "Total de Incidentes Cadastrados por Página: " . $total_encontrado;
                }
            ?>
        </div>
    </div>

    <?php if (count($lista_incidentes) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Incidente</th><th>Evento</th><th>Endereço</th><th>Área</th><th>Região</th><th>Site</th><th>OTDR</th><th>Data de Cadastro</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lista_incidentes as $controle): ?>
            <tr>
                <td><?php echo htmlspecialchars($controle['id'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['incidente'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['evento'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['endereco'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['area'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['regiao'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['site'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['otdr'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($controle['data_cadastro'] ?? ''); ?></td>
                <td>
                    <a href="alterar.php?id=<?php echo $controle['id']; ?>" style="color: blue;">Editar</a> | 
                    <a href="processar_crud.php?acao=excluir&id=<?php echo $controle['id']; ?>" 
                       onclick="return confirm('Tem certeza que deseja excluir este incidente?')" style="color: red;">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align: center;">Nenhum incidente encontrado no banco de dados.</p>
    <?php endif; ?>

    <div class="pagination" style="text-align: center; margin-top: 30px; margin-bottom: 30px;">
        <?php if ($total_paginas > 1): ?>
            <?php $base_url = "dashboard.php?termo_busca=" . urlencode($termo_busca) . "&"; ?>
            
            <?php if ($pagina_atual > 1): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual - 1); ?>" class="btn-page">Anterior</a>
            <?php else: ?>
                <span class="btn-page disabled">Anterior</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="<?php echo $base_url . 'pagina=' . $i; ?>" 
                   class="btn-page <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual + 1); ?>" class="btn-page">Próximo</a>
            <?php else: ?>
                <span class="btn-page disabled">Próximo</span>
            <?php endif; ?>
        <?php endif; ?>
        <p style="font-size: 0.9em; margin-top: 10px;">Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?></p>
    </div>

    <div class="admin-header"><h3>Administração de Usuários</h3></div>

    <?php if (count($lista_usuarios) > 0): ?>
    <table class="user-table">
        <thead>
            <tr><th>ID</th><th>Nome</th><th>Login</th><th>Permissão Atual</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lista_usuarios as $usuario): ?>
            <tr>
                <td><?php echo htmlspecialchars($usuario['id'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($usuario['login'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($usuario['nivel_permissao'] ?? ''); ?></td>
                <td><a href="alterar_usuario.php?id=<?php echo $usuario['id']; ?>">Editar Usuário</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="no-user-message">Nenhum usuário encontrado na tabela 'usuario'</p>
    <?php endif; ?>

    <footer>
        <div class="estatisticas">
            <h3>Estatísticas Rápidas</h3>
            <p>Total de Usuários Cadastrados: <strong><?php echo $total_usuarios; ?></strong></p>
        </div>
    </footer>

    <div id="modal-erro" class="modal-erro-overlay">
        <div class="modal-erro-content">
            <span class="modal-erro-close" onclick="fecharModal()">×</span>
            <h4 class="modal-erro-titulo">⚠️ Erro de Permissão</h4>
            <p id="modal-erro-texto"></p>
            <button onclick="fecharModal()" class="btn-fechar-modal">Entendi</button>
        </div>
    </div>

    <script>
        // LÓGICA DO LOADER (AMPULHETA)
        document.getElementById('form-busca').addEventListener('submit', function() {
            document.getElementById('loader-overlay').style.display = 'flex';
        });
        document.querySelectorAll('.btn-page').forEach(link => {
            link.addEventListener('click', function() {
                if(!this.classList.contains('active') && !this.classList.contains('disabled')) {
                    document.getElementById('loader-overlay').style.display = 'flex';
                }
            });
        });

        // LÓGICA DO MODAL
        const mensagemErro = <?php echo json_encode($alerta_erro ?? ''); ?>;
        function fecharModal() { document.getElementById('modal-erro').style.display = 'none'; }
        if (mensagemErro) {
            document.getElementById('modal-erro-texto').innerText = mensagemErro;
            document.getElementById('modal-erro').style.display = 'block';
        }
    </script>
</body>
</html>

