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
    die("❌ Erro: Falha na conexão com o banco de dados.");
}

// Configurações da Paginação Original
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
    $params['limite'] = $limite_por_pagina;
    $params['offset'] = $offset;
    $stmt_consulta->execute($params);
    $lista_incidentes = $stmt_consulta->fetchAll();
    $total_encontrado = count($lista_incidentes);

    $total_incidentes = $pdo->query("SELECT COUNT(id) FROM controle")->fetchColumn();
    $ultimo_cadastro = $pdo->query("SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1")->fetchColumn(); 

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
            var options = { width: 400, height: 120, redFrom: 0, redTo: 3000, yellowFrom: 3001, yellowTo: 10000, greenFrom: 10001, greenTo: 25000, minorTicks: 5, max: 25000 };
            new google.visualization.Gauge(document.getElementById('chart_div')).draw(data, options);
        });
    </script>
    <style>
        /* AMPULHETA CORRIGIDA (FIXED) PARA MOBILE */
        #loader-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(255, 255, 255, 0.9); z-index: 999999; backdrop-filter: blur(8px);
            flex-direction: column; justify-content: center; align-items: center;
        }
        .ampulheta { font-size: 80px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .texto-loader { margin-top: 20px; font-weight: bold; color: #e02810; font-size: 1.2em; text-align: center; }

        /* ESTILOS GLOBAIS E FUNDO */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #ffffff; position: relative; min-height: 100vh; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.15; filter: grayscale(50%); z-index: -3; }
        body::after { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%); filter: blur(80px); animation: moveColors 25s ease-in-out infinite alternate; }
        @keyframes moveColors { 0% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-2%, 2%) scale(1.05); } 100% { transform: translate(2%, -2%) scale(1); } }
        
        /* TABELAS E HOVER ORIGINAL */
        table, .user-table { background-color: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px); border-collapse: collapse; margin: 20px auto; width: 100%; max-width: 1000px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); border-radius: 8px; overflow: hidden; }
        table th, .user-table th { background-color: #007bff; color: white; padding: 12px 15px; text-align: left; }
        table td, .user-table td { border: 1px solid #ddd; padding: 10px 15px; background-color: rgba(255, 255, 255, 0.85); transition: background-color 0.2s; }
        table tr:nth-child(even) td, .user-table tbody tr:nth-child(even) td { background-color: rgba(247, 247, 247, 0.9); }
        table tbody tr:hover td, .user-table tbody tr:hover td { background-color: rgba(233, 247, 255, 0.95) !important; cursor: pointer; }

        .header { color: black; padding: 10px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 1.5em; text-decoration: underline; }
        .container-titulo { text-align: center; }
        h3 { display: inline-block; color: #235303ff; text-decoration: underline; margin-top: 0; padding: 10px; }
        p { font-size: 18px; text-align: center; font-weight: bold; }

        .btn-pesquisar, .btn-page { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-page { display: inline-block; text-decoration: none; color: #007bff; border: 1px solid #007bff; background-color: transparent; margin: 0 5px; }
        .btn-page.active { background-color: #007bff; color: white; font-weight: bold; }
        .btn-page.disabled { color: #ccc; border-color: #ccc; cursor: default; background-color: #f9f9f9; }
        
        .logout-container { position: absolute; top: 20px; right: 20px; z-index: 1000; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: #0b34ebff; color: white; text-decoration: none; border-radius: 8px; border: black 2px solid; font-weight: bold; }
        
        .admin-header { margin-top: 50px; text-align: center; width: 100%; }

        /* FOOTER E ESTATÍSTICAS DE USUÁRIOS (RESTALRADO) */
        footer { width: 100%; border-radius: 5px; border: 1px solid #131212ff; padding: 20px 0; background-color: #b2cae2ff; max-width: 800px; margin: 40px auto 20px auto; color: #239406ff; border-top: 5px solid #3498db; }
        .estatisticas { display: flex; flex-direction: column; align-items: center; padding: 0 20px; }
        .estatisticas h3 { font-size: 1.5em; color: #e20e0eff; margin-bottom: 20px; border-bottom: 2px solid #db4d34ff; }
        .estatisticas p { font-size: 1.1em; padding: 15px 30px; border-radius: 8px; background-color: #34495e; color: #ecf0f1; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); transition: all 0.3s; margin: 10px 20px; width: fit-content; }
        .estatisticas p:hover { transform: translateY(-3px); box-shadow: 0 8px 12px rgba(0,0,0,0.5); }
        .estatisticas strong { color: #e67e22; font-size: 1.5em; margin-left: 10px; font-weight: bold; }
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

    <p style="text-align: center;">Novo incidente: <a href="cadastro.php" style="background:#1167c2; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold;">Cadastrar Incidente</a></p>

    <div class="logout-container"><a href="logout.php" class="btn-logout">Sair</a></div>

    <div class="container-titulo">
        <form id="form-busca" method="GET" action="dashboard.php" style="text-align: center; margin-bottom: 30px;">
            <label style="font-weight: bold; margin-right: 10px;">Buscar Dados:</label>
            <input type="text" name="termo_busca" style="width: 250px; padding: 8px; border-radius: 4px; border: 1px solid #ccc;" value="<?php echo htmlspecialchars($termo_busca); ?>">
            <button type="submit" class="btn-pesquisar">Pesquisar</button>
        </form>
        
        <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>

        <div style="text-align: center; margin-bottom: 30px; padding: 15px; background-color: rgba(255, 255, 255, 0.5); border-radius: 10px; max-width: 600px; margin: 20px auto; z-index: 5;">
            <h4>Estatísticas Rápidas</h4>
            <p>Total de Incidentes: <strong><?php echo $total_incidentes; ?></strong><br>Último Cadastro: <strong><?php echo $ultimo_cadastro ?: 'Nenhum'; ?></strong></p>
            <div id="chart_div" style="width: 400px; height: 120px; margin: 0 auto;"></div>
        </div>

        <div style="margin-top: 15px; font-weight: bold;">
            <?php echo !empty($termo_busca) ? $total_encontrado . " Resultados para: \"" . htmlspecialchars($termo_busca) . "\"" : "Total por Página: " . $total_encontrado; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>ID</th><th>Incidente</th><th>Evento</th><th>Endereço</th><th>Área</th><th>Região</th><th>Site</th><th>OTDR</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lista_incidentes as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td><td><?php echo htmlspecialchars($c['incidente']); ?></td><td><?php echo htmlspecialchars($c['evento']); ?></td><td><?php echo htmlspecialchars($c['endereco']); ?></td><td><?php echo htmlspecialchars($c['area']); ?></td><td><?php echo htmlspecialchars($c['regiao']); ?></td><td><?php echo htmlspecialchars($c['site']); ?></td><td><?php echo htmlspecialchars($c['otdr']); ?></td>
                <td><a href="alterar.php?id=<?php echo $c['id']; ?>" style="color:blue; font-weight:bold;">Editar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination" style="text-align: center; margin: 30px 0;">
        <?php if ($total_paginas > 1): ?>
            <?php $base_url = "dashboard.php?termo_busca=" . urlencode($termo_busca) . "&"; ?>
            <?php if ($pagina_atual > 1): ?>
                <a href="<?php echo $base_url . 'pagina=' . ($pagina_atual - 1); ?>" class="btn-page">Anterior</a>
            <?php else: ?>
                <span class="btn-page disabled">Anterior</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="<?php echo $base_url . 'pagina=' . $i; ?>" class="btn-page <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
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
    <table class="user-table">
        <thead><tr><th>ID</th><th>Nome</th><th>Login</th><th>Permissão</th></tr></thead>
        <tbody>
            <?php foreach ($lista_usuarios as $u): ?>
            <tr><td><?php echo $u['id']; ?></td><td><?php echo htmlspecialchars($u['nome']); ?></td><td><?php echo htmlspecialchars($u['login']); ?></td><td><?php echo htmlspecialchars($u['nivel_permissao']); ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <footer>
        <div class="estatisticas">
            <h3>Estatísticas Rápidas</h3>
            <p>Total de Usuários Cadastrados: <strong><?php echo $total_usuarios; ?></strong></p>
        </div>
    </footer>

    <script>
        const loader = document.getElementById('loader-overlay');
        document.getElementById('form-busca').addEventListener('submit', () => { loader.style.display = 'flex'; });
        document.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function() {
                if(!this.classList.contains('active') && !this.classList.contains('disabled')) loader.style.display = 'flex';
            });
        });
    </script>
</body>
</html>
