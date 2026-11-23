<?php
// Arquivo: dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

$pdo->exec("SET NAMES 'UTF8'");

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário';
$nivel_permissao_logado = strtolower($_SESSION['nivel_permissao'] ?? $_SESSION['permissao'] ?? 'user');

$msg_admin = null;
$msg_admin_tipo = 'sucesso';

// PROCESSAMENTO DE AÇÕES DE ADMINISTRAÇÃO (APROVAR, DESBLOQUEAR OU REJEITAR)
if ($nivel_permissao_logado === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_aprovacao'])) {
    $id_usuario_alvo = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $acao = $_POST['acao_aprovacao'];
    $novo_nivel = ($_POST['nivel_permissao'] ?? 'VIEW') === 'ADMIN' ? 'ADMIN' : 'VIEW';

    if ($id_usuario_alvo) {
        try {
            if ($acao === 'aprovar') {
                $stmt_aprov = $pdo->prepare("UPDATE usuario SET status = 'ATIVO', nivel_permissao = :nivel, tentativas_login = 0 WHERE id = :id");
                $stmt_aprov->execute(['nivel' => $novo_nivel, 'id' => $id_usuario_alvo]);
                $msg_admin = "✅ Usuário aprovado com sucesso!";
                $msg_admin_tipo = 'sucesso';
            } elseif ($acao === 'desbloquear') {
                $stmt_desbloq = $pdo->prepare("UPDATE usuario SET status = 'ATIVO', tentativas_login = 0 WHERE id = :id");
                $stmt_desbloq->execute(['id' => $id_usuario_alvo]);
                $msg_admin = "🔓 Usuário desbloqueado com sucesso!";
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
$pagina_atual = max(1, (int)($_GET['pagina'] ?? 1));
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

    // =========================================================================
    // CONSULTAS PARA OS GRÁFICOS MODERNOS (PLOTLY.JS)
    // =========================================================================

    // 1. Top 12 Áreas com maior número de incidentes
    $sql_grafico_area = "SELECT COALESCE(NULLIF(UPPER(TRIM(area)), ''), 'NÃO INFORMADO') AS area, COUNT(id) AS total 
                         FROM controle 
                         GROUP BY UPPER(TRIM(area)) 
                         ORDER BY total DESC 
                         LIMIT 12";
    $dados_grafico_area = $pdo->query($sql_grafico_area)->fetchAll(PDO::FETCH_ASSOC);

    // 2. Distribuição por Região (Top 10 Regiões)
    $sql_grafico_regiao = "SELECT COALESCE(NULLIF(UPPER(TRIM(regiao)), ''), 'NÃO INFORMADO') AS regiao, COUNT(id) AS total 
                           FROM controle 
                           GROUP BY UPPER(TRIM(regiao)) 
                           ORDER BY total DESC 
                           LIMIT 10";
    $dados_grafico_regiao = $pdo->query($sql_grafico_regiao)->fetchAll(PDO::FETCH_ASSOC);

    // 3. Top 10 Sites com mais incidentes (desconsiderando vazios ou '-')
    $sql_grafico_site = "SELECT UPPER(TRIM(site)) AS site, COUNT(id) AS total 
                         FROM controle 
                         WHERE site IS NOT NULL AND TRIM(site) <> '' AND TRIM(site) <> '-'
                         GROUP BY UPPER(TRIM(site)) 
                         ORDER BY total DESC 
                         LIMIT 10";
    $dados_grafico_site = $pdo->query($sql_grafico_site)->fetchAll(PDO::FETCH_ASSOC);

    // 4. Hierarquia Área -> Região (TODAS as regiões pertencentes apenas às Top 12 Áreas)
    $sql_hierarquia = "SELECT COALESCE(NULLIF(UPPER(TRIM(area)), ''), 'NÃO INFORMADO') AS area,
                              COALESCE(NULLIF(UPPER(TRIM(regiao)), ''), 'NÃO INFORMADO') AS regiao,
                              COUNT(id) AS total
                       FROM controle
                       WHERE COALESCE(NULLIF(UPPER(TRIM(area)), ''), 'NÃO INFORMADO') IN (
                           SELECT COALESCE(NULLIF(UPPER(TRIM(area)), ''), 'NÃO INFORMADO')
                           FROM controle
                           GROUP BY UPPER(TRIM(area))
                           ORDER BY COUNT(id) DESC
                           LIMIT 12
                       )
                       GROUP BY UPPER(TRIM(area)), UPPER(TRIM(regiao))
                       ORDER BY total DESC";
    $dados_hierarquia = $pdo->query($sql_hierarquia)->fetchAll(PDO::FETCH_ASSOC);

    // 5. Evolução Diária de Cadastros (últimos 30 dias com registros)
    $sql_evolucao = "SELECT dia, total FROM (
                        SELECT TO_CHAR(data_cadastro::timestamp, 'YYYY-MM-DD') AS dia, COUNT(id) AS total 
                        FROM controle 
                        WHERE data_cadastro IS NOT NULL AND TRIM(CAST(data_cadastro AS TEXT)) <> ''
                        GROUP BY dia 
                        ORDER BY dia DESC 
                        LIMIT 30
                     ) sub ORDER BY dia ASC";
    $dados_evolucao = $pdo->query($sql_evolucao)->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================================

    // Consulta de Usuários Pendentes e Bloqueados (para o Administrador)
    $lista_pendentes = [];
    $total_pendentes = 0;
    $lista_bloqueados = [];
    $total_bloqueados = 0;

    if ($nivel_permissao_logado === 'admin') {
        $stmt_pendentes = $pdo->query("SELECT id, nome, login, email, nivel_permissao FROM usuario WHERE status = 'PENDENTE' ORDER BY id ASC");
        $lista_pendentes = $stmt_pendentes->fetchAll(PDO::FETCH_ASSOC);
        $total_pendentes = count($lista_pendentes);

        $stmt_bloqueados = $pdo->query("SELECT id, nome, login, email, tentativas_login FROM usuario WHERE status = 'BLOQUEADO' ORDER BY id ASC");
        $lista_bloqueados = $stmt_bloqueados->fetchAll(PDO::FETCH_ASSOC);
        $total_bloqueados = count($lista_bloqueados);
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
    
    <!-- Plotly.js CDN -->
    <script src="https://cdn.plot.ly/plotly-2.35.2.min.js" charset="utf-8"></script>

    <script type="text/javascript">
        function drawCharts() {
            const totalIncidentes = <?php echo (int)$total_incidentes; ?>;
            const dadosArea = <?php echo json_encode($dados_grafico_area, JSON_UNESCAPED_UNICODE); ?>;
            const dadosRegiao = <?php echo json_encode($dados_grafico_regiao, JSON_UNESCAPED_UNICODE); ?>;
            const dadosSite = <?php echo json_encode($dados_grafico_site, JSON_UNESCAPED_UNICODE); ?>;
            const dadosHierarquia = <?php echo json_encode($dados_hierarquia, JSON_UNESCAPED_UNICODE); ?>;
            const dadosEvolucao = <?php echo json_encode($dados_evolucao, JSON_UNESCAPED_UNICODE); ?>;

            const paletaCores = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6610f2', '#fd7e14', '#20c997', '#e83e8c', '#6c757d', '#0d6efd', '#198754'];

            const configPlotly = {
                responsive: true,
                displaylogo: false,
                modeBarButtonsToRemove: ['lasso2d', 'select2d']
            };

            // -----------------------------------------------------------------
            // 1. GAUGE (VELOCÍMETRO MODERNO)
            // -----------------------------------------------------------------
            const traceGauge = [{
                type: "indicator",
                mode: "gauge+number",
                value: totalIncidentes,
                title: { text: "Volume Total de Incidentes", font: { size: 16, color: "#333", family: "Segoe UI" } },
                number: { font: { size: 34, color: "#007bff", family: "Segoe UI" }, valueformat: ",d" },
                gauge: {
                    axis: { range: [0, 40000], tickwidth: 1, tickcolor: "#666" },
                    bar: { color: "#007bff", thickness: 0.3 },
                    bgcolor: "white",
                    borderwidth: 1,
                    bordercolor: "#ddd",
                    steps: [
                        { range: [0, 5000], color: "rgba(220, 53, 69, 0.25)" },
                        { range: [5000, 15000], color: "rgba(255, 193, 7, 0.3)" },
                        { range: [15000, 40000], color: "rgba(40, 167, 69, 0.25)" }
                    ],
                    threshold: {
                        line: { color: "#e02810", width: 4 },
                        thickness: 0.8,
                        value: totalIncidentes
                    }
                }
            }];
            Plotly.newPlot('chart_div', traceGauge, {
                height: 250,
                margin: { t: 50, b: 15, l: 30, r: 30 },
                paper_bgcolor: "transparent"
            }, configPlotly);

            // -----------------------------------------------------------------
            // 2. ROSCA (DISTRIBUIÇÃO POR REGIÃO)
            // -----------------------------------------------------------------
            const traceRegiao = [{
                type: 'pie',
                hole: 0.48,
                labels: dadosRegiao.map(d => d.regiao),
                values: dadosRegiao.map(d => parseInt(d.total)),
                textinfo: 'label+percent',
                textposition: 'inside',
                insidetextorientation: 'radial',
                hovertemplate: '<b>Região: %{label}</b><br>Incidentes: %{value:,}<br>Participação: %{percent}<extra></extra>',
                marker: { colors: paletaCores }
            }];
            Plotly.newPlot('chart_regiao_div', traceRegiao, {
                title: { text: 'Distribuição por Região (Top 10)', font: { size: 16, color: '#333' } },
                height: 360,
                margin: { t: 50, b: 20, l: 20, r: 20 },
                paper_bgcolor: 'transparent',
                showlegend: true,
                legend: { orientation: 'v', x: 1.0, y: 0.5 }
            }, configPlotly);

            // -----------------------------------------------------------------
            // 3. BARRAS HORIZONTAIS (TOP 12 ÁREAS)
            // -----------------------------------------------------------------
            const areasInvertidas = [...dadosArea].reverse();
            const traceArea = [{
                type: 'bar',
                orientation: 'h',
                x: areasInvertidas.map(d => parseInt(d.total)),
                y: areasInvertidas.map(d => d.area),
                text: areasInvertidas.map(d => parseInt(d.total).toLocaleString('pt-BR')),
                textposition: 'outside',
                cliponaxis: false,
                hovertemplate: '<b>Área: %{y}</b><br>Total: %{x:,} incidentes<extra></extra>',
                marker: {
                    color: areasInvertidas.map((_, idx) => paletaCores[(areasInvertidas.length - 1 - idx) % paletaCores.length]),
                    line: { color: 'rgba(0,0,0,0.1)', width: 1 }
                }
            }];
            Plotly.newPlot('chart_area_div', traceArea, {
                title: { text: 'Top 12 Áreas com Maior Número de Incidentes', font: { size: 16, color: '#333' } },
                height: 440,
                margin: { t: 50, b: 40, l: 140, r: 65 },
                xaxis: { title: 'Total de Incidentes', gridcolor: '#eee' },
                yaxis: { automargin: true },
                paper_bgcolor: 'transparent',
                plot_bgcolor: 'transparent'
            }, configPlotly);

            // -----------------------------------------------------------------
            // 4. SUNBURST INTERATIVO (HIERARQUIA TOP 12 ÁREAS -> TODAS REGIÕES)
            // -----------------------------------------------------------------
            let ids = ['Total'];
            let labels = ['Todas Áreas'];
            let parents = [''];
            let values = [0];

            const mapaAreas = {};
            dadosHierarquia.forEach(item => {
                const areaNome = String(item.area).trim();
                const qtd = parseInt(item.total, 10) || 0;
                mapaAreas[areaNome] = (mapaAreas[areaNome] || 0) + qtd;
            });

            let somaTotalSunburst = 0;
            Object.keys(mapaAreas).forEach(area => {
                ids.push('AREA_' + area);
                labels.push(area);
                parents.push('Total');
                values.push(mapaAreas[area]);
                somaTotalSunburst += mapaAreas[area];
            });
            values[0] = somaTotalSunburst;

            dadosHierarquia.forEach(item => {
                const areaNome = String(item.area).trim();
                const regiaoNome = String(item.regiao).trim();
                const qtd = parseInt(item.total, 10) || 0;

                ids.push('REG_' + areaNome + '_' + regiaoNome);
                labels.push(regiaoNome);
                parents.push('AREA_' + areaNome);
                values.push(qtd);
            });

            const traceSunburst = [{
                type: "sunburst",
                ids: ids,
                labels: labels,
                parents: parents,
                values: values,
                branchvalues: "total",
                maxdepth: 2,
                hovertemplate: '<b>%{label}</b><br>Incidentes: %{value:,}<extra></extra>',
                marker: { line: { width: 1.5, color: '#fff' } }
            }];
            Plotly.newPlot('chart_sunburst_div', traceSunburst, {
                title: { text: 'Mapa Hierárquico: Área ➔ Região (Clique para expandir)', font: { size: 16, color: '#333' } },
                height: 440,
                margin: { t: 50, b: 15, l: 15, r: 15 },
                paper_bgcolor: 'transparent'
            }, configPlotly);

            // -----------------------------------------------------------------
            // 5. BARRAS HORIZONTAIS (TOP 10 SITES CRÍTICOS)
            // -----------------------------------------------------------------
            const sitesInvertidos = [...dadosSite].reverse();
            const traceSite = [{
                type: 'bar',
                orientation: 'h',
                x: sitesInvertidos.map(d => parseInt(d.total)),
                y: sitesInvertidos.map(d => d.site),
                text: sitesInvertidos.map(d => parseInt(d.total).toLocaleString('pt-BR')),
                textposition: 'outside',
                cliponaxis: false,
                hovertemplate: '<b>Site: %{y}</b><br>Incidentes: %{x:,}<extra></extra>',
                marker: { color: '#17a2b8' }
            }];
            Plotly.newPlot('chart_site_div', traceSite, {
                title: { text: 'Top 10 Pontos de Testes Mais Acessados', font: { size: 16, color: '#333' } },
                height: 380,
                margin: { t: 50, b: 40, l: 160, r: 60 },
                xaxis: { title: 'Ocorrências', gridcolor: '#eee' },
                yaxis: { automargin: true },
                paper_bgcolor: 'transparent',
                plot_bgcolor: 'transparent'
            }, configPlotly);

            // -----------------------------------------------------------------
            // 6. LINHA TEMPORAL (EVOLUÇÃO DIÁRIA DE CADASTROS)
            // -----------------------------------------------------------------
            const traceEvolucao = [{
                type: 'scatter',
                mode: 'lines+markers',
                x: dadosEvolucao.map(d => d.dia),
                y: dadosEvolucao.map(d => parseInt(d.total)),
                line: { color: '#e02810', width: 3, shape: 'spline' },
                marker: { size: 7, color: '#007bff' },
                fill: 'tozeroy',
                fillcolor: 'rgba(224, 40, 16, 0.1)',
                hovertemplate: '<b>Data: %{x}</b><br>Cadastros: %{y:,}<extra></extra>'
            }];
            Plotly.newPlot('chart_evolucao_div', traceEvolucao, {
                title: { text: 'Evolução Diária de Incidentes Cadastrados (Últimos 30 dias ativos)', font: { size: 16, color: '#333' } },
                height: 350,
                margin: { t: 50, b: 55, l: 55, r: 30 },
                xaxis: { title: 'Data do Cadastro', gridcolor: '#eee' },
                yaxis: { title: 'Quantidade', gridcolor: '#eee' },
                paper_bgcolor: 'transparent',
                plot_bgcolor: 'transparent'
            }, configPlotly);
        }

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
                setTimeout(() => {
                    drawCharts();
                    window.dispatchEvent(new Event('resize'));
                }, 50);
            }
        }
    </script>

    <style>
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

        /* GRID DE GRÁFICOS PLOTLY */
        .grid-graficos-plotly {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(460px, 1fr));
            gap: 20px;
            margin-top: 20px;
            text-align: left;
        }

        .card-grafico-plotly {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #e9ecef;
        }

        .card-grafico-full {
            grid-column: 1 / -1;
        }

        @media (max-width: 600px) {
            .grid-graficos-plotly {
                grid-template-columns: 1fr;
            }
        }

        .badge-pendente {
            background-color: #e02810;
            color: white;
            font-size: 0.75em;
            padding: 2px 7px;
            border-radius: 12px;
            font-weight: 900;
        }

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

        .tabela-container-scroll::-webkit-scrollbar {
            height: 12px;
            width: 8px;
        }

        .tabela-container-scroll::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 10px;
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
            nav, .tabs-container, .logout-container, .container-titulo, #form-busca, button, .btn-pesquisar, th:last-child, td:last-child, .header, .botoes-topo-container {
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

        .btn-aprovar {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white; border: none; padding: 7px 15px; border-radius: 6px;
            font-weight: bold; cursor: pointer; transition: 0.2s;
        }
        .btn-aprovar:hover { transform: scale(1.05); box-shadow: 0 3px 8px rgba(40,167,69,0.3); }

        .btn-desbloquear {
            background: linear-gradient(135deg, #fd7e14 0%, #d96203 100%);
            color: white; border: none; padding: 7px 15px; border-radius: 6px;
            font-weight: bold; cursor: pointer; transition: 0.2s;
        }
        .btn-desbloquear:hover { transform: scale(1.05); box-shadow: 0 3px 8px rgba(253,126,20,0.3); }

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

        .btn-atalho-areas {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.35);
            transition: all 0.3s ease;
        }
        
        .btn-atalho-areas:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 18px rgba(23, 162, 184, 0.55);
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        .card-stats h4 {
            display: inline-block;
            font-size: 1.4em;
            color: #2c3e50;
            letter-spacing: 1px;
            padding: 10px 24px;
            margin-bottom: 25px;
            border-radius: 8px;
            border-bottom: 3px solid #e67e22;
            background-color: transparent;
            transition: all 0.3s ease;
            cursor: default;
        }
    
        .card-stats h4:hover {
            background-color: #34495e;
            color: #ecf0f1;
            border-bottom-color: #e67e22;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 73, 94, 0.25);
        }
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

        <a href="https://corpclarobr-my.sharepoint.com/:f:/g/personal/gilson_oshiro_claro_com_br/IgAYbg7sCZMVR4mJiaioZn9hAfua4fyCa_VPfQ-pM9CHiX0?e=c5bxWB&action=embedview&IsDlg=1"
            target="_blank" 
            rel="noopener noreferrer" 
            class="btn-consultar-documentos">
            📁 Consultar Documentos
        </a>
    </div>

    <div class="tabs-container">
        <button class="tab-button active" onclick="openTab(event, 'tab-tabela')">📋 Lista de Incidentes</button>
        <button class="tab-button" onclick="openTab(event, 'tab-graficos')">📊 Relatórios e Estatísticas</button>
        
        <?php if ($nivel_permissao_logado === 'admin'): ?>
            <button class="tab-button" onclick="openTab(event, 'tab-aprovacoes')">
                👥 Gestão de Acessos
                <?php if (($total_pendentes + $total_bloqueados) > 0): ?>
                    <span class="badge-pendente"><?php echo ($total_pendentes + $total_bloqueados); ?></span>
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

        <div class="card-info-pagina">
            Incidentes exibidos nesta página: 
            <span style="font-size: 1.4em; color: #007bff; font-weight: 900; margin-left: 5px;">
                <?php echo $total_nesta_pagina; ?>
            </span>
        </div>

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

        <div style="text-align: center; margin: 15px auto 40px auto;">
            <a href="div_areas.php" class="btn-atalho-areas">
                🗺️ Consultar Divisão por Áreas e Coordenadores
            </a>
        </div>
    </div>
    
    <!-- ABA 2: GRÁFICOS E ESTATÍSTICAS (PLOTLY.JS) -->
    <div id="tab-graficos" class="tab-content">
        <div class="card-stats" style="text-align: center; padding: 25px; background-color: rgba(255, 255, 255, 0.85); border-radius: 12px; max-width: 1280px; margin: 20px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.06);">

            <h4>PAINEL DE ESTATÍSTICAS</h4>

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

            <div style="text-align: center; margin-bottom: 25px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <button onclick="window.print()" class="btn-pesquisar" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    🖨️ Imprimir Relatório
                </button>

                <a href="exportar_incidentes.php" class="btn-pesquisar" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); text-decoration: none;">
                    📥 Extrair Excel (CSV)
                </a>
            </div>

            <!-- GRADE RESPONSIVA DE GRÁFICOS INTERATIVOS PLOTLY -->
            <div class="grid-graficos-plotly">
                <!-- 1. Velocímetro / Gauge -->
                <div class="card-grafico-plotly">
                    <div id="chart_div" style="width: 100%;"></div>
                </div>

                <!-- 2. Rosca por Região -->
                <div class="card-grafico-plotly">
                    <div id="chart_regiao_div" style="width: 100%;"></div>
                </div>

                <!-- 3. Barras Horizontais por Área -->
                <div class="card-grafico-plotly">
                    <div id="chart_area_div" style="width: 100%;"></div>
                </div>

                <!-- 4. Mapa Hierárquico Sunburst (Área -> Região) -->
                <div class="card-grafico-plotly">
                    <div id="chart_sunburst_div" style="width: 100%;"></div>
                </div>

                <!-- 5. Top 10 Pontos de Testes Mais Acessados -->
                <div class="card-grafico-plotly">
                    <div id="chart_site_div" style="width: 100%;"></div>
                </div>

                <!-- 6. Evolução Diária (Largura Total) -->
                <div class="card-grafico-plotly card-grafico-full">
                    <div id="chart_evolucao_div" style="width: 100%;"></div>
                </div>
            </div>

        </div>
    </div>

    <!-- ABA 3: APROVAÇÕES E CONTAS BLOQUEADAS -->
    <?php if ($nivel_permissao_logado === 'admin'): ?>
        <div id="tab-aprovacoes" class="tab-content">
            <h3 id="titulo-incidentes">Aprovações e Desbloqueio de Usuários</h3>

            <?php if (!empty($msg_admin)): ?>
                <div class="alerta-feedback <?php echo $msg_admin_tipo; ?>">
                    <?php echo htmlspecialchars($msg_admin); ?>
                </div>
            <?php endif; ?>

            <!-- BLOCO 1: CADASTROS PENDENTES -->
            <div class="card-info-pagina" style="border-left-color: #ffc107; margin-bottom: 10px;">
                Aguardando aprovação inicial: 
                <span style="font-size: 1.4em; color: #e02810; font-weight: 900; margin-left: 5px;">
                    <?php echo $total_pendentes; ?>
                </span>
            </div>

            <?php if ($total_pendentes === 0): ?>
                <p style="text-align: center; color: #666; font-weight: 600; margin-bottom: 30px;">Sem solicitações de cadastro pendentes.</p>
            <?php else: ?>
                <div class="tabela-container-scroll" style="margin-bottom: 40px;">
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

            <!-- BLOCO 2: CONTAS BLOQUEADAS POR TENTATIVAS -->
            <div class="card-info-pagina" style="border-left-color: #dc3545; margin-bottom: 10px;">
                Contas bloqueadas por tentativas: 
                <span style="font-size: 1.4em; color: #dc3545; font-weight: 900; margin-left: 5px;">
                    <?php echo $total_bloqueados; ?>
                </span>
            </div>

            <?php if ($total_bloqueados === 0): ?>
                <p style="text-align: center; color: #666; font-weight: 600;">Nenhuma conta bloqueada por excesso de tentativas no momento.</p>
            <?php else: ?>
                <div class="tabela-container-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Completo</th>
                                <th>Login</th>
                                <th>E-mail</th>
                                <th>Erros Registrados</th>
                                <th style="text-align: center;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_bloqueados as $b): ?>
                                <tr>
                                    <td><?php echo $b['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($b['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($b['login']); ?></td>
                                    <td><?php echo htmlspecialchars($b['email']); ?></td>
                                    <td><span style="color: #dc3545; font-weight: bold;"><?php echo (int)$b['tentativas_login']; ?> tentativas incorretas</span></td>
                                    <form method="POST" action="dashboard.php">
                                        <input type="hidden" name="id_usuario" value="<?php echo (int)$b['id']; ?>">
                                        <td style="text-align: center; white-space: nowrap;">
                                            <button type="submit" name="acao_aprovacao" value="desbloquear" class="btn-desbloquear" onclick="return confirm('Deseja desbloquear esta conta e zerar as tentativas?');">
                                                🔓 Desbloquear Acesso
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

