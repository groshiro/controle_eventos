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

// Configurações da Paginação
$limite_por_pagina = 1500; 
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
    if (!empty($termo_busca)) { $stmt_consulta->bindValue(':termo', $termo_sql); }

    $stmt_consulta->execute();
    $lista_incidentes = $stmt_consulta->fetchAll();
    $total_encontrado = count($lista_incidentes);

    $total_incidentes = $total_registros_bd;
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
            var options = { width: 400, height: 120, redFrom: 0, redTo: 3000, yellowFrom: 3001, yellowTo: 10000, greenFrom: 10001, greenTo: 25000, max: 25000 };
            new google.visualization.Gauge(document.getElementById('chart_div')).draw(data, options);
        });
    </script>
    <style>
        /* === AMPULHETA CORRIGIDA PARA MOBILE === */
        #loader-overlay {
            display: none;
            position: fixed; /* Fixa na tela visual, ignora o scroll */
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(255, 255, 255, 0.9);
            z-index: 999999; /* Fica acima de tudo */
            backdrop-filter: blur(8px);
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .ampulheta { font-size: 80px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .texto-loader { margin-top: 15px; font-weight: bold; color: #e02810; font-size: 1.2em; text-align: center; }

        /* ESTILOS GLOBAIS */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background-color: #ffffff; min-height: 100vh; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.15; z-index: -3; }
        
        /* TABELAS, HOVER E ZEBRADO */
        table, .user-table { background-color: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px); border-collapse: collapse; margin: 20px auto; width: 95%; max-width: 1100px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        th { background-color: #007bff; color: white; padding: 12px; text-align: left; }
        td { border: 1px solid #ddd; padding: 10px; background-color: rgba(255, 255, 255, 0.85); transition: background 0.2s; }
        
        /* Zebrado */
        tr:nth-child(even) td { background-color: rgba(247, 247, 247, 0.9); }
        
        /* Hover corrigido */
        tbody tr:hover td { background-color: rgba(233, 247, 255, 0.95) !important; cursor: pointer; }

        .header { text-align: center; padding: 20px; text-decoration: underline; font-weight: bold; }
        .admin-header { text-align: center; margin-top: 50px; width: 100%; }
        .btn-logout { background: #0b34eb; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; position: absolute; top: 20px; right: 20px; font-weight: bold; border: 2px solid black; }
        .btn-pesquisar, .btn-page { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-page { background: transparent; color: #007bff; border: 1px solid #007bff; margin: 2px; }
        .btn-page.active { background: #007bff; color: white; font-weight: bold; }
        .status-msg { max-width: 600px; margin: 10px auto; padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; text-align: center; font-weight: bold; }
        
        footer { width: 100%; max-width: 800px; margin: 20px auto; padding: 20px 0; background: #b2cae2; border-radius: 8px; border-top: 5px solid #3498db; text-align: center; }
    </style>
</head>
<body>

    <div id="loader-overlay">
        <div class="ampulheta">⏳</div>
        <div class="texto-loader">Buscando no banco de dados...</div>
    </div>

    <div class="header">
        <h2>Bem-vindo <?php echo htmlspecialchars($nome_do_usuario); ?>!</h2>
    </div>

    <a href="logout.php" class="btn-logout">Sair</a>

    <p style="text-align: center;">Novo incidente: <a href="cadastro.php" style="background:#1167c2; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold;">Cadastrar Incidente</a></p>

    <form id="form-busca" method="GET" style="text-align: center; margin-bottom: 30px;">
        <label style="font-weight: bold;">Buscar:</label>
        <input type="text" name="termo_busca" style="width: 250px; padding: 8px;" value="<?php echo htmlspecialchars($termo_busca); ?>">
        <button type="submit" class="btn-pesquisar">Pesquisar</button>
    </form>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'alterado'): ?>
        <div class="status-msg">✅ Incidente atualizado com sucesso!</div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 30px;">
        <div id="chart_div" style="width: 100%; height: 120px; margin: 0 auto;"></div>
        <p>Total: <strong><?php echo $total_incidentes; ?></strong> | Último: <?php echo $ultimo_cadastro; ?></p>
    </div>

    <table>
        <thead>
            <tr><th>ID</th><th>Incidente</th><th>Evento</th><th>Área</th><th>Site</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lista_incidentes as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['incidente']); ?></td>
                <td><?php echo htmlspecialchars($c['evento']); ?></td>
                <td><?php echo htmlspecialchars($c['area']); ?></td>
                <td><?php echo htmlspecialchars($c['site']); ?></td>
                <td><a href="alterar.php?id=<?php echo $c['id']; ?>" style="color:blue;">Editar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination" style="text-align: center; padding: 20px;">
        <?php if ($total_paginas > 1): ?>
            <?php $base_url = "dashboard.php?termo_busca=" . urlencode($termo_busca) . "&"; ?>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="<?php echo $base_url . 'pagina=' . $i; ?>" class="btn-page <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <div class="admin-header"><h3>Administração de Usuários</h3></div>
    <table class="user-table">
        <thead><tr><th>ID</th><th>Nome</th><th>Login</th><th>Permissão</th></tr></thead>
        <tbody>
            <?php foreach ($lista_usuarios as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                <td><?php echo htmlspecialchars($u['login']); ?></td>
                <td><?php echo htmlspecialchars($u['nivel_permissao']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <footer>
        <h3>Estatísticas Rápidas</h3>
        <p>Total de Usuários: <strong><?php echo $total_usuarios; ?></strong></p>
    </footer>

    <script>
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
