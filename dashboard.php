<?php
// 1. INÍCIO ABSOLUTO: Sem espaços ou linhas em branco antes da tag PHP
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica login imediatamente
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

$alerta_erro = null;
if (isset($_SESSION['alerta_erro']) && !empty($_SESSION['alerta_erro'])) {
    $alerta_erro = $_SESSION['alerta_erro'];
    unset($_SESSION['alerta_erro']); 
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexao.php'; 

// Força UTF8 para evitar caracteres estranhos
$pdo->exec("SET NAMES 'UTF8'");

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário'; 

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
    $sql_total_geral = "SELECT COUNT(id) FROM controle";
    $total_registros_bd = $pdo->query($sql_total_geral)->fetchColumn();
    $total_paginas = ceil($total_registros_bd / $limite_por_pagina);

    $sql_consulta = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle" . $where_clause . " ORDER BY id LIMIT :limite OFFSET :offset";
    $stmt_consulta = $pdo->prepare($sql_consulta);
    $stmt_consulta->bindValue(':limite', (int)$limite_por_pagina, PDO::PARAM_INT);
    $stmt_consulta->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    if (!empty($termo_busca)) { $stmt_consulta->bindValue(':termo', $termo_sql); }

    $stmt_consulta->execute();
    $lista_incidentes = $stmt_consulta->fetchAll();
    
    // ✅ DEFINIÇÃO DA VARIÁVEL (Para evitar o erro de Undefined Variable)
    $total_nesta_pagina = count($lista_incidentes);

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
        /* 1. AMPULHETA FIXED PARA MOBILE */
        #loader-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(255, 255, 255, 0.9); z-index: 999999; backdrop-filter: blur(8px);
            flex-direction: column; justify-content: center; align-items: center;
        }
        .ampulheta { font-size: 80px; animation: girar 2s linear infinite; }
        @keyframes girar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .texto-loader { margin-top: 20px; font-weight: bold; color: #e02810; font-size: 1.2em; text-align: center; }

        /* 2. ESTILOS GLOBAIS E FUNDO */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #fff; min-height: 100vh; overflow-x: hidden; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.15; z-index: -3; }
        body::after { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%); filter: blur(80px); animation: moveColors 25s ease-in-out infinite alternate; }
        @keyframes moveColors { 0% { transform: translate(0, 0); } 100% { transform: translate(2%, -2%); } }

        /* 3. TÍTULOS COM HOVER E CENTRALIZAÇÃO */
        #titulo-incidentes, .admin-header h3 {
            display: block; text-align: center; margin: 30px auto; font-size: 1.8em;
            color: #e02810ff; text-decoration: underline; transition: all 0.3s ease;
            cursor: pointer; width: fit-content; padding: 5px 15px;
        }
        #titulo-incidentes:hover, .admin-header h3:hover {
            color: #007bff; transform: scale(1.05); text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* 4. TABELAS, HOVER E ZEBRADO */
        table, .user-table { background-color: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(10px); border-collapse: collapse; margin: 20px auto; width: 95%; max-width: 1100px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        th { background-color: #007bff; color: white; padding: 12px 15px; text-align: left; font-weight: bold; }
        td { border: 1px solid #ddd; padding: 10px 15px; background-color: rgba(255, 255, 255, 0.85); transition: background-color 0.2s; }
        tr:nth-child(even) td { background-color: rgba(247, 247, 247, 0.9); }
        tbody tr:hover td { background-color: rgba(233, 247, 255, 0.95) !important; cursor: pointer; }

        /* 5. PAGINAÇÃO ORGANIZADA (FLEXBOX) */
        .pagination { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 5px; margin: 30px auto; padding: 10px; max-width: 95%; }
        .btn-page { display: inline-flex; justify-content: center; align-items: center; min-width: 35px; height: 35px; padding: 0 10px; text-decoration: none; color: #007bff; border: 1px solid #007bff; border-radius: 4px; background-color: transparent; transition: all 0.2s; font-size: 14px; }
        .btn-page.active { background-color: #007bff; color: white; font-weight: bold; }
        .btn-page.disabled { color: #ccc; border-color: #ccc; cursor: default; background-color: #f9f9f9; }

        /* 6. ESTATÍSTICAS */
        .destaque-pagina { margin: 15px 0; font-size: 1.1em; color: #333; font-weight: bold; }
        footer { width: 100%; border-radius: 5px; border: 1px solid #131212ff; padding: 20px 0; background-color: #b2cae2ff; max-width: 800px; margin: 40px auto 20px auto; color: #239406ff; border-top: 5px solid #3498db; }
        .estatisticas { display: flex; flex-direction: column; align-items: center; padding: 0 20px; }
        .estatisticas h3 { font-size: 1.5em; color: #e20e0eff; margin-bottom: 20px; border-bottom: 2px solid #db4d34ff; text-decoration: none; }
        .estatisticas p { font-size: 1.1em; padding: 15px 30px; border-radius: 8px; background-color: #34495e; color: #ecf0f1; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); transition: all 0.3s; margin: 10px 20px; width: fit-content; }
        .estatisticas strong { color: #e67e22; font-size: 1.5em; margin-left: 10px; font-weight: bold; }
        /* Container do Cabeçalho */
        .header {
            width: 100%;
            padding: 40px 0;
            text-align: center;
            background: rgba(255, 255, 255, 0.4); /* Fundo vidro um pouco mais visível */
            backdrop-filter: blur(10px);
            border-bottom: 3px solid #e02810; /* Linha vermelha mais grossa e marcante */
            margin-bottom: 30px;
        }
        
        /* Estilo do Texto H2 principal (Negrito e Moderno) */
        .header h2 {
            margin: 0;
            font-size: 2.5em; /* Aumentado levemente */
            color: #1a1a1a; /* Preto mais profundo para contraste */
            font-weight: 800; /* Negrito extra para ar moderno */
            letter-spacing: -1px; /* Letras mais próximas (estilo Apple/Moderno) */
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }
        
        /* Destaque para o nome do usuário */
        .header h2 span.user-name {
            color: #e02810; /* Vermelho Claro */
            font-weight: 900; /* Peso máximo */
            text-transform: uppercase; /* Nome em CAIXA ALTA para autoridade */
            position: relative;
            display: inline-block;
            padding: 0 10px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        /* Efeito ao passar o mouse */
        .header h2 span.user-name:hover {
            transform: scale(1.1); /* Aumenta levemente */
            color: #007bff; /* Troca para azul no hover para indicar interatividade */
            text-shadow: 3px 6px 10px rgba(0, 0, 0, 0.2);
        }
        /* Container que fixa o botão no topo direito */
.logout-container {
    position: absolute;
    top: 25px;
    right: 30px;
    z-index: 1000;
}

/* Estilização do Botão Azul */
.btn-logout {
    display: inline-block;
    padding: 10px 22px;
    background-color: #007bff; /* Azul padrão */
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    font-size: 14px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Efeito Hover (ao passar o mouse) */
.btn-logout:hover {
    background-color: #0056b3; /* Azul mais escuro */
    border-color: #004085;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Ajuste para Mobile (Celular) */
@media (max-width: 600px) {
    .logout-container {
        top: 15px;
        right: 15px;
    }
    .btn-logout {
        padding: 8px 15px;
        font-size: 12px;
    }
}
        /* Container da linha de cadastro */
.cadastro-container {
    text-align: center;
    margin: 20px 0 40px 0;
}

/* Botão Moderno e Negrito */
.btn-cadastrar {
    display: inline-block;
    background: linear-gradient(135deg, #1167c2 0%, #004a99 100%);
    color: white;
    padding: 12px 30px;
    border-radius: 50px; /* Estilo pílula moderno */
    text-decoration: none;
    font-weight: 800; /* Extra Negrito */
    font-size: 1.1em;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(17, 103, 194, 0.4);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

/* Efeito Hover */
.btn-cadastrar:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 25px rgba(17, 103, 194, 0.6);
    background: linear-gradient(135deg, #e02810 0%, #b31d0a 100%); /* Muda para vermelho no hover */
    color: white;
}

/* Efeito ao clicar */
.btn-cadastrar:active {
    transform: translateY(0);
}
                
    </style>
</head>
<body>

    <div id="loader-overlay">
        <div class="ampulheta">⏳</div>
        <div class="texto-loader">Buscando informações no sistema...</div>
    </div>

    <div class="header">
        <h2>Bem-vindo <?php echo htmlspecialchars($nome_do_usuario); ?>!</h2>
    </div>

    <div class="logout-container"><a href="logout.php" class="btn-logout">Sair</a></div>

   <div class="cadastro-container">
    <a href="cadastro.php" class="btn-cadastrar">
        <span>+</span> Cadastrar Novo Incidente
    </a>
    </div>

    <div class="container-titulo">
        <form id="form-busca" method="GET" action="dashboard.php" style="text-align: center; margin-bottom: 30px;">
            <label style="font-weight: bold; margin-right: 10px;">Buscar:</label>
            <input type="text" name="termo_busca" style="width: 250px; padding: 8px; border-radius: 4px; border: 1px solid #ccc;" value="<?php echo htmlspecialchars($termo_busca); ?>">
            <button type="submit" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Pesquisar</button>
        </form>
        
        <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>

        <div style="text-align: center; margin-bottom: 30px; padding: 15px; background-color: rgba(255, 255, 255, 0.5); border-radius: 10px; max-width: 600px; margin: 20px auto;">
            <h4>Estatísticas Rápidas</h4>
            <p>Total Geral: <strong><?php echo $total_incidentes; ?></strong><br>Último: <strong><?php echo $ultimo_cadastro ?: 'Nenhum'; ?></strong></p>
            
            <div class="destaque-pagina">
                Incidentes exibidos nesta página: <span style="font-size: 1.5em; color:#007bff;"><?php echo $total_nesta_pagina; ?></span>
            </div>
            
            <div id="chart_div" style="width: 400px; height: 120px; margin: 0 auto;"></div>
        </div>
    </div>

    <div style="overflow-x: auto;">
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
        document.getElementById('form-busca').addEventListener('submit', () => loader.style.display = 'flex');
        document.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function() {
                if(!this.classList.contains('active') && !this.classList.contains('disabled')) loader.style.display = 'flex';
            });
        });
    </script>
</body>
</html>



