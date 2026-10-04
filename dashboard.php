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

if (!isset($pdo) || $pdo === null) {
    die("❌ Erro: Falha na conexão com o banco de dados.");
}

// Força UTF8 para evitar caracteres estranhos
$pdo->exec("SET NAMES 'UTF8'");

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário';

// Recupera a permissão do usuário logado na sessão (padrão 'user' caso não esteja definida)
$nivel_permissao_logado = strtolower($_SESSION['nivel_permissao'] ?? $_SESSION['permissao'] ?? 'user');

// Mensagens das ações de aprovação
$msg_admin = null;
$msg_admin_tipo = 'sucesso';

// PROCESSAMENTO DE APROVAÇÃO / REJEIÇÃO DE USUÁRIOS (APENAS ADMIN)
if ($nivel_permissao_logado === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_aprovacao'])) {
    $id_usuario_alvo = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $acao = $_POST['acao_aprovacao'];
    $novo_nivel = ($_POST['nivel_permissao'] ?? 'VIEW') === 'ADMIN' ? 'ADMIN' : 'VIEW';

    if ($id_usuario_alvo) {
        try {
            if ($acao === 'aprovar') {
                $stmt_aprov = $pdo->prepare("UPDATE usuario SET status = 'ATIVO', nivel_permissao = :nivel WHERE id = :id");
                $stmt_aprov->execute(['nivel' => $novo_nivel, 'id' => $id_usuario_alvo]);
                $msg_admin = "✅ Usuário aprovado com sucesso!";
                $msg_admin_tipo = 'sucesso';
            } elseif ($acao === 'rejeitar') {
                $stmt_recusa = $pdo->prepare("DELETE FROM usuario WHERE id = :id AND status = 'PENDENTE'");
                $stmt_recusa->execute(['id' => $id_usuario_alvo]);
                $msg_admin = "⚠️ Solicitação rejeitada e removida com sucesso!";
                $msg_admin_tipo = 'alerta';
            }
        } catch (PDOException $e) {
            $msg_admin = "❌ Erro ao atualizar: " . $e->getMessage();
            $msg_admin_tipo = 'erro';
        }
    }
}

// Configurações da Paginação
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

    $total_nesta_pagina = count($lista_incidentes);
    $total_incidentes = $total_registros_bd;
    $ultimo_cadastro = $pdo->query("SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1")->fetchColumn();
    $total_usuarios = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $lista_usuarios = $pdo->query("SELECT id, nome, login, nivel_permissao FROM usuario ORDER BY id ASC")->fetchAll();

    // Consulta SQL para agrupar Incidentes x Área
    $sql_grafico_area = "SELECT COALESCE(NULLIF(area, ''), 'Não Informado') AS area, COUNT(id) AS total FROM controle GROUP BY area ORDER BY total DESC LIMIT 10";
    $dados_grafico_area = $pdo->query($sql_grafico_area)->fetchAll(PDO::FETCH_ASSOC);

    // Consulta de Usuários Pendentes para Aprovação (apenas se for admin)
    $lista_pendentes = [];
    $total_pendentes = 0;
    if ($nivel_permissao_logado === 'admin') {
        $stmt_pendentes = $pdo->query("SELECT id, nome, login, email, nivel_permissao FROM usuario WHERE status = 'PENDENTE' ORDER BY id ASC");
        $lista_pendentes = $stmt_pendentes->fetchAll(PDO::FETCH_ASSOC);
        $total_pendentes = count($lista_pendentes);
    }

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
        google.charts.load('current', {
            'packages': ['gauge', 'corechart']
        });
        
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // 1. Gráfico Gauge (Velocímetro)
            var dataGauge = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['Incidentes', <?php echo (int)$total_incidentes; ?>]
            ]);
            var optionsGauge = {
                width: 400,
                height: 120,
                redFrom: 0,
                redTo: 5000,
                yellowFrom: 5001,
                yellowTo: 15000,
                greenFrom: 15001,
                greenTo: 40000,
                max: 40000
            };
            new google.visualization.Gauge(document.getElementById('chart_div')).draw(dataGauge, optionsGauge);

            // 2. Gráfico de Barras com Efeito Tridimensional
            var dataArea = google.visualization.arrayToDataTable([
                ['Área', 'Quantidade', { role: 'style' }, { role: 'annotation' }],
                <?php 
                $cores = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6610f2', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'];
                $i = 0;
                foreach ($dados_grafico_area as $linha) {
                    $cor = $cores[$i % count($cores)];
                    echo "['" . addslashes(htmlspecialchars($linha['area'])) . "', " . (int)$linha['total'] . ", '" . $cor . "', " . (int)$linha['total'] . "],";
                    $i++;
                }
                ?>
            ]);

            var optionsArea = {
                title: 'Top Áreas com Maior Número de Incidentes',
                height: 420,
                bar: { groupWidth: "55%" },
                legend: { position: "none" },
                hAxis: { title: 'Total de Incidentes', gridlines: { color: '#f0f0f0' } },
                vAxis: { title: 'Área / Setor' },
                backgroundColor: 'transparent',
                annotations: {
                    alwaysOutside: true,
                    textStyle: { fontSize: 12, bold: true, color: '#333' }
                }
            };

            var chartArea = new google.visualization.BarChart(document.getElementById('chart_area_div'));
            chartArea.draw(dataArea, optionsArea);
        }

        // Alterna abas e redesenha gráficos
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";

            if (tabName === 'tab-graficos') {
                drawCharts();
            }
        }
    </script>

    <style>
        /* AMPULHETA FIXED */
        #loader-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(255, 255, 255, 0.9);
            z-index: 999999;
            backdrop-filter: blur(8px);
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .ampulheta {
            font-size: 80px;
            animation: girar 2s linear infinite;
        }

        @keyframes girar {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .texto-loader {
            margin-top: 20px;
            font-weight: bold;
            color: #e02810;
            font-size: 1.2em;
            text-align: center;
        }

        /* ESTILOS DE NAVEGAÇÃO E ABAS */
        nav.menu-superior {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .tabs-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px auto;
            max-width: 1100px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 25px;
            font-weight: 800;
            font-size: 1em;
            border: none;
            background-color: rgba(255, 255, 255, 0.7);
            color: #333;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button.active {
            background-color: #007bff;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0,123,255,0.3);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* BADGE INDICADOR DE PENDÊNCIAS */
        .badge-pendente {
            background-color: #e02810;
            color: white;
            font-size: 0.75em;
            padding: 2px 7px;
            border-radius: 12px;
            font-weight: 900;
        }

        /* DESTAQUE DE INCIDENTES DA PÁGINA ATUAL */
        .card-info-pagina {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-left: 5px solid #007bff;
            border-radius: 8px;
            padding: 12px 20px;
            max-width: 400px;
            margin: 0 auto 20px auto;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            font-weight: 700;
            color: #333;
        }

        /* CONTAINER ISOLANDO O SCROLL VERTICAL E HORIZONTAL */
        .tabela-container-scroll {
            overflow-y: auto;
            overflow-x: auto;
            max-height: 65vh;
            position: relative;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            width: 98%;
            max-width: 98vw;
        }

        /* FIXAÇÃO SÓLIDA DO CABEÇALHO (THEAD) */
        .tabela-container-scroll table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #007bff !important;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
        }

        .tabela-container-scroll table thead tr {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* BARRAS DE ROLAGEM ESTILIZADAS */
        .tabela-container-scroll::-webkit-scrollbar {
            height: 12px;
            width: 8px;
        }

        .tabela-container-scroll::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 10px;
        }

        /* SIMULAÇÃO 3D NAS BARRAS SVG */
        #chart_area_div svg rect {
            rx: 4px;
            ry: 4px;
            filter: drop-shadow(3px 3px 3px rgba(0, 0, 0, 0.25));
            transition: all 0.3s ease;
        }

        #chart_area_div svg rect:hover {
            filter: drop-shadow(5px 5px 6px rgba(0, 0, 0, 0.35));
            cursor: pointer;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url('claro-operadora.jpg');
            background-size: cover;
            opacity: 0.15;
            z-index: -3;
        }

        body::after {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%);
            filter: blur(80px);
            animation: moveColors 25s ease-in-out infinite alternate;
        }

        @keyframes moveColors {
            0% { transform: translate(0, 0); }
            100% { transform: translate(2%, -2%); }
        }

        #titulo-incidentes, .admin-header h3 {
            display: block;
            text-align: center;
            margin: 20px auto;
            font-size: 1.8em;
            color: #e02810ff;
            text-decoration: underline;
            transition: all 0.3s ease;
            cursor: pointer;
            width: fit-content;
            padding: 5px 15px;
        }

        #titulo-incidentes:hover, .admin-header h3:hover {
            color: #007bff;
            transform: scale(1.05);
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
        }

        table, .user-table {
            border-collapse: collapse;
            margin: 0;
            width: 100%;
            background-color: transparent !important;
        }

        th {
            background-color: #007bff;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
        }

        td {
            border: 1px solid #ddd;
            padding: 10px 15px;
            background-color: rgba(255, 255, 255, 0.85);
            transition: background-color 0.2s;
        }

        tr:nth-child(even) td { background-color: rgba(247, 247, 247, 0.9); }
        tbody tr:hover td { background-color: rgba(233, 247, 255, 0.95) !important; cursor: pointer; }

        .pagination {
            display: flex; flex-wrap: wrap; justify-content: center; align-items: center;
            gap: 8px; margin: 30px auto; padding: 10px; max-width: 95%;
        }

        .btn-page {
            display: inline-flex; justify-content: center; align-items: center;
            min-width: 40px; height: 40px; padding: 0 15px;
            text-decoration: none; color: #007bff; border: 2px solid #007bff;
            border-radius: 8px; background-color: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px; font-weight: 700;
        }

        .btn-page:not(.active):not(.disabled):hover {
            background-color: #007bff; color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
            border-color: #0056b3;
        }

        .btn-page.active {
            background-color: #007bff; color: white; font-weight: 800;
            border-color: #0056b3; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-page.disabled {
            color: #ccc; border-color: #ddd; cursor: not-allowed;
            background-color: #f9f9f9; opacity: 0.6;
        }

        .user-table { width: 90%; max-width: 800px; }

        table a, .user-table a {
            color: #17a2b8; text-decoration: none; font-weight: 600; transition: color 0.2s;
        }

        table a:hover, .user-table a:hover { color: #0056b3; text-decoration: underline; }

        .admin-header { margin-top: 50px; text-align: center; }

        .header {
            width: 100%; padding: 40px 0; text-align: center;
            background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px);
            border-bottom: 3px solid #e02810; margin-bottom: 20px;
        }

        .header h2 {
            margin: 0; font-size: 2.5em; color: #1a1a1a; font-weight: 800;
            letter-spacing: -1px; text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        }

        .header h2 span.user-name {
            color: #e02810; font-weight: 900; text-transform: uppercase;
            position: relative; display: inline-block; padding: 0 10px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .header h2 span.user-name:hover {
            transform: scale(1.1); color: #007bff; text-shadow: 3px 6px 10px rgba(0, 0, 0, 0.2);
        }

        .logout-container { position: absolute; top: 25px; right: 30px; z-index: 1000; }

        .btn-logout {
            display: inline-block; padding: 10px 22px; background-color: #007bff; color: white;
            text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;
            border: 2px solid transparent; transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-transform: uppercase; letter-spacing: 0.5px;
        }

        .btn-logout:hover {
            background-color: #0056b3; border-color: #004085; transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 600px) {
            .logout-container { top: 15px; right: 15px; }
            .btn-logout { padding: 8px 15px; font-size: 12px; }
        }

        @media print {
            nav, .tabs-container, .logout-container, .cadastro-container, .container-titulo, #form-busca, button, .btn-pesquisar, th:last-child, td:last-child, #chart_div, .header {
                display: none !important;
            }
            body { background: white !important; padding: 0; }
            body::before, body::after { display: none; }
            table { width: 100%; border: 1px solid #000; font-size: 10pt; color: black; }
            th { background-color: #eee !important; color: black !important; border: 1px solid #000; }
            td { border: 1px solid #000; }
            #titulo-incidentes { color: black; text-decoration: none; margin-top: 0; }
        }

        /* CONTAINER FLEX PARA ALINHAR OS BOTÕES LADO A LADO */
        .botoes-topo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 25px auto 35px auto;
            flex-wrap: wrap;
        }

        /* BOTÃO CADASTRAR */
        .btn-cadastrar {
            display: inline-block;
            background: linear-gradient(135deg, #1167c2 0%, #004a99 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(17, 103, 194, 0.4);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn-cadastrar:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(17, 103, 194, 0.6);
            background: linear-gradient(135deg, #e02810 0%, #b31d0a 100%);
            color: white;
        }

        /* BOTÃO CONSULTAR DOCUMENTOS */
        .btn-consultar-documentos {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn-consultar-documentos:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.6);
            background: linear-gradient(135deg, #e02810 0%, #b31d0a 100%);
            color: white;
        }
        #form-busca { display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 25px; }
        #form-busca label { font-weight: 800; color: #333; text-transform: uppercase; font-size: 0.95em; letter-spacing: 0.5px; }
        #form-busca input[type="text"] {
            width: 280px; padding: 12px 18px; border: 2px solid #ddd; border-radius: 10px;
            font-weight: 600; font-size: 1em; transition: all 0.3s ease; outline: none; background: rgba(255, 255, 255, 0.9);
        }
        #form-busca input[type="text"]:focus { border-color: #007bff; box-shadow: 0 0 12px rgba(0, 123, 255, 0.2); background: #fff; }

        .btn-pesquisar {
            padding: 12px 28px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1.2px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        .btn-pesquisar:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 8px 20px rgba(0, 123, 255, 0.5); background: linear-gradient(135deg, #0056b3 0%, #004085 100%); }

        /* ESTILOS DE BOTÕES DE APROVAÇÃO */
        .btn-aprovar {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white; border: none; padding: 7px 15px; border-radius: 6px;
            font-weight: bold; cursor: pointer; transition: 0.2s;
        }
        .btn-aprovar:hover { transform: scale(1.05); box-shadow: 0 3px 8px rgba(40,167,69,0.3); }

        .btn-rejeitar {
            background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
            color: white; border: none; padding: 7px 15px; border-radius: 6px;
            font-weight: bold; cursor: pointer; transition: 0.2s;
        }
        .btn-rejeitar:hover { transform: scale(1.05); box-shadow: 0 3px 8px rgba(220,53,69,0.3); }

        .select-permissao {
            padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc;
            font-weight: 600; background: #fff;
        }

        .alerta-feedback {
            max-width: 650px; margin: 15px auto; padding: 12px 20px;
            border-radius: 8px; text-align: center; font-weight: bold;
        }
        .alerta-feedback.sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alerta-feedback.alerta { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alerta-feedback.erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .modal-erro-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); overflow: auto; }
        .modal-erro-content { background-color: #fff; margin: 10% auto; padding: 20px; border: 3px solid #dc3545; border-radius: 8px; width: 80%; max-width: 450px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); text-align: center; }
        .modal-erro-titulo { color: #dc3545; font-size: 1.5em; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        #modal-erro-texto { font-size: 1.1em; color: #333; margin-bottom: 20px; }
        .modal-erro-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .modal-erro-close:hover, .modal-erro-close:focus { color: #000; text-decoration: none; cursor: pointer; }
        .btn-fechar-modal { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-fechar-modal:hover { background-color: #0056b3; }
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

    <!-- BARRAS DE NAVEGAÇÃO NO TOPO (EXIBIDA APENAS PARA ADMIN) -->
    <?php if ($nivel_permissao_logado === 'admin'): ?>
        <nav class="menu-superior">
            <?php $arquivo_no_servidor = basename($_SERVER['PHP_SELF']); ?>

            <a href="usuarios.php" class="btn-page <?php echo ($arquivo_no_servidor == 'usuarios.php' || $arquivo_no_servidor == 'gerenciar_usuarios.php') ? 'active' : ''; ?>">
                Gestão de Usuários
            </a>

            <a href="auditoria.php" class="btn-page <?php echo ($arquivo_no_servidor == 'auditoria.php') ? 'active' : ''; ?>">
                🔍 Auditoria
            </a>
        </nav>
    <?php endif; ?>

    <div class="botoes-topo-container">
    <a href="cadastro.php" class="btn-cadastrar">
        Cadastrar Novo Incidente
    </a>

    <!-- BOTÃO COM ACESSO RESTRITO À PASTA ESPECÍFICA -->
    <a href="https://drive.google.com/drive/folders/1RXuFS0i8Ek06Bht2gUEjnvBoKUvm0Gzu?usp=sharing"
        target="_blank" 
        rel="noopener noreferrer" 
        class="btn-consultar-documentos">
        📁 Consultar Documentos
    </a>
    </div>

    <!-- NAVEGAÇÃO DE ABAS INTERNAS -->
    <div class="tabs-container">
        <button class="tab-button active" onclick="openTab(event, 'tab-tabela')">📋 Lista de Incidentes</button>
        <button class="tab-button" onclick="openTab(event, 'tab-graficos')">📊 Gráficos & Métricas</button>
        
        <?php if ($nivel_permissao_logado === 'admin'): ?>
            <button class="tab-button" onclick="openTab(event, 'tab-aprovacoes')">
                👥 Aprovações de Usuários
                <?php if ($total_pendentes > 0): ?>
                    <span class="badge-pendente"><?php echo $total_pendentes; ?></span>
                <?php endif; ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- ABA 1: TABELA E INCIDENTES -->
    <div id="tab-tabela" class="tab-content" style="display: block;">
        <div class="container-titulo">
            <form id="form-busca" method="GET" action="dashboard.php">
                <label>Buscar:</label>
                <input type="text" name="termo_busca" placeholder="Digite sua busca..." value="<?php echo htmlspecialchars($termo_busca); ?>">
                <button type="submit" class="btn-pesquisar">Pesquisar</button>
            </form>
        </div>

        <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>

        <!-- CARD DE PÁGINA ATUAL -->
        <div class="card-info-pagina">
            Incidentes exibidos nesta página: 
            <span style="font-size: 1.4em; color: #007bff; font-weight: 900; margin-left: 5px;">
                <?php echo $total_nesta_pagina; ?>
            </span>
        </div>

        <!-- TABELA COM O WRAPPER DE ROLAGEM E CABEÇALHO FIXO -->
        <div class="tabela-container-scroll">
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
                        <th>Ações</th>
                    </tr>
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
    </div>

    <!-- ABA 2: GRÁFICOS E ESTATÍSTICAS -->
    <div id="tab-graficos" class="tab-content">
        <div class="card-stats" style="text-align: center; margin-bottom: 30px; padding: 20px; background-color: rgba(255, 255, 255, 0.8); border-radius: 12px; max-width: 900px; margin: 20px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">

            <h4>PAINEL DE ESTATÍSTICAS E GRÁFICOS</h4>

            <!-- TOTAL DE USUÁRIOS EXIBIDO EXCLUSIVAMENTE AQUI -->
            <div style="display: flex; justify-content: space-around; flex-wrap: wrap; margin-bottom: 20px; gap: 15px;">
                <p style="font-size: 1.1em; background-color: #34495e; color: #ecf0f1; padding: 12px 20px; border-radius: 8px; margin: 0;">
                    Total Geral de Incidentes: <strong style="color: #e67e22; font-size: 1.3em;"><?php echo $total_incidentes; ?></strong>
                </p>
                <p style="font-size: 1.1em; background-color: #34495e; color: #ecf0f1; padding: 12px 20px; border-radius: 8px; margin: 0;">
                    Usuários Cadastrados: <strong style="color: #e67e22; font-size: 1.3em;"><?php echo $total_usuarios; ?></strong>
                </p>
                <p style="font-size: 1.1em; background-color: #34495e; color: #ecf0f1; padding: 12px 20px; border-radius: 8px; margin: 0;">
                    Último Cadastro: <strong style="color: #e67e22; font-size: 1.1em;"><?php echo $ultimo_cadastro ?: 'Nenhum'; ?></strong>
                </p>
            </div>

            <!-- BOTÕES IMPRIMIR E EXTRAIR EXCEL -->
            <div style="text-align: center; margin-bottom: 25px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <button onclick="window.print()" class="btn-pesquisar" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    🖨️ Imprimir Relatório
                </button>

                <a href="exportar_incidentes.php" class="btn-pesquisar" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); text-decoration: none;">
                    📥 Extrair Excel (CSV)
                </a>
            </div>

            <!-- Gráfico 1: Gauge -->
            <div style="margin-bottom: 30px;">
                <h5 style="color: #333; font-size: 1.1em;">Volume Total de Incidentes</h5>
                <div id="chart_div" style="width: 400px; height: 120px; margin: 10px auto;"></div>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

            <!-- Gráfico 2: Incidentes x Área em Barras 3D -->
            <div>
                <h5 style="color: #333; font-size: 1.2em; margin-bottom: 10px;">Volume de Incidentes por Área</h5>
                <div id="chart_area_div" style="width: 100%; height: 420px; margin: 0 auto;"></div>
            </div>
        </div>
    </div>

    <!-- ABA 3: APROVAÇÕES PENDENTES (VISÍVEL APENAS PARA ADMIN) -->
    <?php if ($nivel_permissao_logado === 'admin'): ?>
        <div id="tab-aprovacoes" class="tab-content">
            <h3 id="titulo-incidentes">Solicitações de Cadastro Pendentes</h3>

            <?php if (!empty($msg_admin)): ?>
                <div class="alerta-feedback <?php echo $msg_admin_tipo; ?>">
                    <?php echo htmlspecialchars($msg_admin); ?>
                </div>
            <?php endif; ?>

            <div class="card-info-pagina" style="border-left-color: #ffc107;">
                Usuários aguardando aprovação: 
                <span style="font-size: 1.4em; color: #e02810; font-weight: 900; margin-left: 5px;">
                    <?php echo $total_pendentes; ?>
                </span>
            </div>

            <?php if ($total_pendentes === 0): ?>
                <div style="text-align: center; padding: 40px; background: rgba(255,255,255,0.8); border-radius: 12px; max-width: 600px; margin: 20px auto; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    <span style="font-size: 3em;">🎉</span>
                    <p style="font-size: 1.1em; color: #555; font-weight: bold; margin-top: 10px;">Não há nenhuma solicitação de cadastro pendente no momento!</p>
                </div>
            <?php else: ?>
                <div class="tabela-container-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Completo</th>
                                <th>Login</th>
                                <th>E-mail</th>
                                <th>Nível de Acesso</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_pendentes as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['login']); ?></td>
                                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                                    <form method="POST" action="dashboard.php">
                                        <input type="hidden" name="id_usuario" value="<?php echo (int)$p['id']; ?>">
                                        <td>
                                            <select name="nivel_permissao" class="select-permissao">
                                                <option value="VIEW" <?php echo ($p['nivel_permissao'] === 'VIEW') ? 'selected' : ''; ?>>VIEW</option>
                                                <option value="ADMIN" <?php echo ($p['nivel_permissao'] === 'ADMIN') ? 'selected' : ''; ?>>ADMIN</option>
                                            </select>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <button type="submit" name="acao_aprovacao" value="aprovar" class="btn-aprovar">
                                                ✓ Aprovar
                                            </button>
                                            <button type="submit" name="acao_aprovacao" value="rejeitar" class="btn-rejeitar" onclick="return confirm('Deseja realmente recusar este cadastro?');">
                                                ✕ Rejeitar
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Modal de Erro -->
    <div id="modal-erro" class="modal-erro-overlay">
        <div class="modal-erro-content">
            <span class="modal-erro-close" onclick="fecharModal()">×</span>
            <h4 class="modal-erro-titulo">⚠️ Erro de Permissão</h4>
            <p id="modal-erro-texto"></p>
            <button onclick="fecharModal()" class="btn-fechar-modal">Entendi</button>
        </div>
        <script>
            const mensagemErro = <?php echo json_encode($alerta_erro ?? ''); ?>;

            function fecharModal() {
                document.getElementById('modal-erro').style.display = 'none';
            }

            if (mensagemErro) {
                const modal = document.getElementById('modal-erro');
                const texto = document.getElementById('modal-erro-texto');
                texto.innerText = mensagemErro;
                modal.style.display = 'block';
            }
        </script>
    </div>

    <script>
        const loader = document.getElementById('loader-overlay');
        document.getElementById('form-busca').addEventListener('submit', () => loader.style.display = 'flex');
        document.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('active') && !this.classList.contains('disabled')) loader.style.display = 'flex';
            });
        });
    </script>
</body>

</html>
