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

require_once 'conexao.php'; 

if ($pdo === null) { 
    die("❌ Erro: Falha na conexão com o banco de dados.");
}

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário'; 

// --- CONFIGURAÇÃO DE PAGINAÇÃO ---
$limite_por_pagina = 1500; 
$pagina_atual = $_GET['pagina'] ?? 1; 
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// --- BUSCA ---
$termo_busca = $_GET['termo_busca'] ?? '';
$where_clause = '';
$params = [];

if (!empty($termo_busca)) {
    $termo_sql = "%" . $termo_busca . "%";
    $where_clause = " WHERE incidente ILIKE :termo OR evento ILIKE :termo OR endereco ILIKE :termo OR area ILIKE :termo OR regiao ILIKE :termo OR site ILIKE :termo OR otdr ILIKE :termo OR CAST(id AS TEXT) ILIKE :termo";
    $params['termo'] = $termo_sql;
}

try {
    // 1. Contagem Total para Paginação
    $total_registros_bd = $pdo->query("SELECT COUNT(id) FROM controle")->fetchColumn();
    $total_paginas = ceil($total_registros_bd / $limite_por_pagina);

    // 2. Consulta de Incidentes (Tabela Controle)
    $sql_consulta = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle" . $where_clause . " ORDER BY id LIMIT :limite OFFSET :offset";
    $stmt_consulta = $pdo->prepare($sql_consulta);
    $stmt_consulta->bindValue(':limite', (int)$limite_por_pagina, PDO::PARAM_INT);
    $stmt_consulta->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    if (!empty($termo_busca)) { $stmt_consulta->bindValue(':termo', $termo_sql); }
    $stmt_consulta->execute();
    $lista_incidentes = $stmt_consulta->fetchAll();
    $total_encontrado = count($lista_incidentes);

    // 3. Estatísticas para o Gráfico
    $total_incidentes = $total_registros_bd;
    $ultimo_cadastro = $pdo->query("SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1")->fetchColumn(); 

    // 4. Listagem de Usuários (Tabela Usuario)
    $lista_usuarios = $pdo->query("SELECT id, nome, login, nivel_permissao FROM usuario ORDER BY id ASC")->fetchAll();
    $total_usuarios = count($lista_usuarios);

} catch (PDOException $e) {
    die("Erro ao consultar: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Controle Claro</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        /* CSS DO LOADER */
        #loader-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.8); z-index: 10000; backdrop-filter: blur(5px);
            flex-direction: column; justify-content: center; align-items: center;
        }
        .ampulheta { font-size: 60px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* ESTILOS GERAIS */
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f7f6; }
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.1; z-index: -1;
        }
        .container { width: 95%; margin: auto; padding: 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats-panel { display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; margin: 20px 0; background: rgba(255,255,255,0.9); padding: 20px; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 30px; border-radius: 8px; overflow: hidden; }
        th { background: #007bff; color: #fff; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .btn-logout { background: #d9534f; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .btn-pesquisar { background: #007bff; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; }
        .status-msg { padding: 15px; margin: 10px 0; border-radius: 5px; text-align: center; font-weight: bold; }
        .sucesso { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div id="loader-overlay">
    <div class="ampulheta">⏳</div>
    <div class="texto-loader">Processando dados...</div>
</div>

<div class="container">
    <div class="header-top">
        <h2>Olá, <?php echo htmlspecialchars($nome_do_usuario); ?>!</h2>
        <a href="logout.php" class="btn-logout">Sair do Sistema</a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'alterado'): ?>
        <div class="status-msg sucesso">✅ Alteração realizada com sucesso!</div>
    <?php endif; ?>

    <div class="stats-panel">
        <div style="text-align: center;">
            <h4 style="margin: 0; color: #333;">Estatísticas de Incidentes</h4>
            <p>Total Geral: <strong><?php echo $total_incidentes; ?></strong></p>
            <p>Último: <strong><?php echo $ultimo_cadastro; ?></strong></p>
        </div>
        <div id="chart_div"></div> <div>
            <a href="cadastro.php" style="background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">+ Novo Incidente</a>
        </div>
    </div>

    <form id="form-busca" method="GET" style="text-align: center; margin: 20px 0;">
        <input type="text" name="termo_busca" value="<?php echo htmlspecialchars($termo_busca); ?>" placeholder="Pesquisar incidente..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
        <button type="submit" class="btn-pesquisar">Buscar</button>
    </form>

    <h3>📋 Lista de Incidentes</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Incidente</th><th>Evento</th><th>Área</th><th>Site</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lista_incidentes as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['incidente']); ?></td>
                <td><?php echo htmlspecialchars($row['evento']); ?></td>
                <td><?php echo htmlspecialchars($row['area']); ?></td>
                <td><?php echo htmlspecialchars($row['site']); ?></td>
                <td><a href="alterar.php?id=<?php echo $row['id']; ?>">✏️ Editar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align: center; margin-bottom: 50px;">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="dashboard.php?termo_busca=<?php echo urlencode($termo_busca); ?>&pagina=<?php echo $i; ?>" 
               style="padding: 10px; border: 1px solid #007bff; text-decoration: none; margin: 2px; <?php echo ($i == $pagina_atual) ? 'background: #007bff; color: #fff;' : ''; ?>">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <hr>

    <h3 style="margin-top: 40px;">👥 Gerenciamento de Usuários</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nome</th><th>Login</th><th>Permissão</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lista_usuarios as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                <td><?php echo htmlspecialchars($user['login']); ?></td>
                <td><strong><?php echo htmlspecialchars($user['nivel_permissao']); ?></strong></td>
                <td><a href="alterar_usuario.php?id=<?php echo $user['id']; ?>">Modificar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    // SCRIPT DO GRÁFICO
    google.charts.load('current', {'packages':['gauge']});
    google.charts.setOnLoadCallback(() => {
        var data = google.visualization.arrayToDataTable([
            ['Label', 'Value'],
            ['Incidentes', <?php echo $total_incidentes; ?>]
        ]);
        var options = {
            width: 400, height: 120,
            redFrom: 0, redTo: 3000, yellowFrom: 3001, yellowTo: 10000,
            greenFrom: 10001, greenTo: 25000, max: 25000
        };
        var chart = new google.visualization.Gauge(document.getElementById('chart_div'));
        chart.draw(data, options);
    });

    // SCRIPT DO LOADER
    document.getElementById('form-busca').addEventListener('submit', () => {
        document.getElementById('loader-overlay').style.display = 'flex';
    });
</script>

</body>
</html>
