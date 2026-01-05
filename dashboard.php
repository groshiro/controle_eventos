<?php
// Arquivo: dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$alerta_erro = null;

// Verifica se há um erro de permissão armazenado na sessão
if (isset($_SESSION['alerta_erro']) && !empty($_SESSION['alerta_erro'])) {
    $alerta_erro = $_SESSION['alerta_erro'];
    unset($_SESSION['alerta_erro']); 
}

// Força a exibição de erros (para depuração)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexao.php'; 

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado']; 

if ($pdo === null) { 
    die("❌ Erro: Falha na conexão com o banco de dados. Status: " . $status_conexao);
}

if (!isset($_SESSION['usuario_logado'])) {
    // header("Location: index.php"); exit; 
    echo "PONTO DE ALERTA: Usuário não logado.<br>";
}

// Configurações da Paginação
$limite_por_pagina = 1500; 
$pagina_atual = $_GET['pagina'] ?? 1; 
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// 1. Capturar o Termo de Busca
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

    // Consulta principal
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
    <style>
        /* === NOVO: ESTILO DA AMPULHETA (LOADER) === */
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

        /* === SEUS ESTILOS EXISTENTES === */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('claro-operadora.jpg'); background-size: cover;
            opacity: 0.15; z-index: -3;
        }
        /* ... (Mantenha seus estilos de tabela e botões aqui) ... */
        .header { text-align: center; padding: 20px; }
        table { width: 95%; margin: 20px auto; border-collapse: collapse; background: rgba(255,255,255,0.9); }
        th { background: #007bff; color: white; padding: 12px; }
        td { border: 1px solid #ddd; padding: 8px; }
        .btn-page.active { background: #007bff; color: white; }
        .btn-logout { background: #0b34eb; color: white; padding: 10px; border-radius: 5px; text-decoration: none; position: absolute; top: 20px; right: 20px; }
        
        /* Estilo para Mensagens de Status */
        .status-msg {
            max-width: 600px; margin: 10px auto; padding: 15px; border-radius: 5px; text-align: center; font-weight: bold;
        }
        .status-sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div id="loader-overlay">
        <div class="ampulheta">⏳</div>
        <div class="texto-loader">Buscando informações...</div>
    </div>

    <div class="header">
        <h2 id="titulo-saudacao">Bem-vindo <?php echo htmlspecialchars($nome_do_usuario); ?>!</h2>
    </div>

    <a href="logout.php" class="btn-logout">Sair</a>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'alterado'): ?>
            <div class="status-msg status-sucesso">✅ Incidente atualizado com sucesso!</div>
        <?php elseif ($_GET['status'] == 'excluido'): ?>
            <div class="status-msg status-sucesso">🗑️ Registro removido com sucesso!</div>
        <?php elseif ($_GET['status'] == 'erro'): ?>
            <div class="status-msg status-erro">❌ Erro na operação: <?php echo htmlspecialchars($_GET['msg'] ?? ''); ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="container-titulo">
        <p>Novo incidente: <a href="cadastro.php" class="link-cadastro" style="background:#1167c2; color:white; padding:8px; border-radius:5px; text-decoration:none;">Cadastrar Incidente</a></p>

        <form id="form-busca" method="GET" action="dashboard.php" style="text-align: center; margin-bottom: 30px;">
            <label style="font-weight: bold;">Buscar Dados:</label>
            <input type="text" name="termo_busca" placeholder="Busca por conteúdo..." 
                   style="width: 250px; padding: 8px;" value="<?php echo htmlspecialchars($termo_busca); ?>">
            <button type="submit" class="btn-pesquisar" style="padding:8px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">Pesquisar</button>
        </form>
        
        <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Incidente</th><th>Evento</th><th>Endereço</th><th>Área</th><th>Região</th><th>Site</th><th>OTDR</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lista_incidentes as $controle): ?>
            <tr>
                <td><?php echo $controle['id']; ?></td>
                <td><?php echo htmlspecialchars($controle['incidente']); ?></td>
                <td><?php echo htmlspecialchars($controle['evento']); ?></td>
                <td><?php echo htmlspecialchars($controle['endereco']); ?></td>
                <td><?php echo htmlspecialchars($controle['area']); ?></td>
                <td><?php echo htmlspecialchars($controle['regiao']); ?></td>
                <td><?php echo htmlspecialchars($controle['site']); ?></td>
                <td><?php echo htmlspecialchars($controle['otdr']); ?></td>
                <td>
                    <a href="alterar.php?id=<?php echo $controle['id']; ?>">Editar</a> | 
                    <a href="processar_crud.php?acao=excluir&id=<?php echo $controle['id']; ?>" onclick="return confirm('Excluir?')" style="color:red;">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination" style="text-align:center; padding: 20px;">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="dashboard.php?termo_busca=<?php echo urlencode($termo_busca); ?>&pagina=<?php echo $i; ?>" 
               class="btn-page <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>" style="padding:8px; border:1px solid #007bff; text-decoration:none; margin:2px;">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <script>
        // Lógica da Ampulheta
        document.getElementById('form-busca').addEventListener('submit', function() {
            document.getElementById('loader-overlay').style.display = 'flex';
        });

        document.querySelectorAll('.btn-page').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('loader-overlay').style.display = 'flex';
            });
        });

        // Google Charts (Gauge)
        google.charts.load('current', {'packages':['gauge']});
        google.charts.setOnLoadCallback(() => {
            var data = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['Incidentes', <?php echo $total_incidentes; ?>]
            ]);
            var chart = new google.visualization.Gauge(document.getElementById('chart_div'));
            chart.draw(data, { width: 400, height: 120, max: 25000, redFrom: 0, redTo: 3000, yellowFrom: 3001, yellowTo: 10000, greenFrom: 10001, greenTo: 25000 });
        });

        // Modal de Erro de Permissão
        const msgPermissao = <?php echo json_encode($alerta_erro); ?>;
        if (msgPermissao) {
            alert(msgPermissao); // Ou use seu modal estilizado
        }
    </script>

    <div id="chart_div" style="width: 400px; height: 120px; margin: 0 auto;"></div>

</body>
</html>



