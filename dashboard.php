<?php
// Arquivo: dashboard.php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// require_once 'conexao.php'; // Se for necessário

$alerta_erro = null;

// Verifica se há um erro de permissão armazenado na sessão
if (isset($_SESSION['alerta_erro']) && !empty($_SESSION['alerta_erro'])) {
    $alerta_erro = $_SESSION['alerta_erro'];
    
    // ✅ OBRIGATÓRIO: Limpa a variável de sessão imediatamente após ler
    unset($_SESSION['alerta_erro']); 
}

// Força a exibição de erros (para depuração)
error_reporting(E_ALL);
ini_set('display_errors', 1);


// 2. INCLUI A CONEXÃO COM O BANCO DE DADOS (DEFINE $pdo e $status_conexao)
require_once 'conexao.php'; 

// Armazena o nome completo (se existir)
$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado']; 

// ----------------------------------------------------
// PASSO 3: VERIFICAÇÃO DE CONEXÃO
// ----------------------------------------------------
if ($pdo === null) { 
    die("❌ Erro: Falha na conexão com o banco de dados. Status: " . $status_conexao);
}

// ----------------------------------------------------
// PASSO 4: VERIFICAÇÃO DE LOGIN E INICIALIZAÇÃO
// ----------------------------------------------------
if (!isset($_SESSION['usuario_logado'])) {
    // Se estivesse em produção, o header/exit estaria aqui.
    //header("Location: index.php"); exit; 
    
    // Deixando em modo de teste para ver o conteúdo
    echo "PONTO DE ALERTA: Usuário não logado (Redirecionamento evitado).<br>";
} else {
    $login_usuario = $_SESSION['usuario_logado'];
}
$lista_incidentes = [];

// Configurações da Paginação
$limite_por_pagina = 1500; // Define o número de registros por página
$pagina_atual = $_GET['pagina'] ?? 1; // Pega a página da URL, padrão é 1

// Calcula o OFFSET (onde começar a consulta):
// Exemplo: Página 3 (3-1) * 15 = 30. O OFFSET começa no registro 30.
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// Inicializa a variável para o total GERAL de registros (para calcular o número de páginas)
$total_registros_bd = 0;
// --------------------------------------------------------------------------
// ----------------------------------------------------
// 5. PREPARAÇÃO E EXECUÇÃO DA CONSULTA SQL
// ----------------------------------------------------

// 1. Capturar o Termo de Busca (se existir)
$termo_busca = $_GET['termo_busca'] ?? '';
$where_clause = '';
$params = [];
$total_encontrado = 0; 

if (!empty($termo_busca)) {
    $termo_sql = "%" . $termo_busca . "%";
    
    // ✅ CORREÇÃO CRÍTICA: Usar as colunas da tabela 'controle' (incidentes)
    $where_clause = " WHERE incidente ILIKE :termo OR evento ILIKE :termo OR endereco ILIKE :termo OR area ILIKE :termo OR regiao ILIKE :termo OR site ILIKE :termo OR otdr ILIKE :termo OR CAST(id AS TEXT) ILIKE :termo";
    
    $params['termo'] = $termo_sql;
}

try {
    // --- CONSULTA PARA O TOTAL GERAL DE REGISTROS (ANTES DA BUSCA) ---
// Usamos esta contagem para calcular o número total de páginas
$sql_total_geral = "SELECT COUNT(id) FROM controle";
$total_registros_bd = $pdo->query($sql_total_geral)->fetchColumn();

// Calcula o número total de páginas
$total_paginas = ceil($total_registros_bd / $limite_por_pagina);


// A consulta principal (agora com LIMIT e OFFSET)
$sql_consulta = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle" . $where_clause . " ORDER BY id LIMIT :limite OFFSET :offset";

$stmt_consulta = $pdo->prepare($sql_consulta);

// Adiciona os parâmetros de paginação
$params['limite'] = $limite_por_pagina;
$params['offset'] = $offset;

// 2. EXECUÇÃO: Passa os parâmetros de busca E paginação
$stmt_consulta->execute($params);
    $lista_incidentes = $stmt_consulta->fetchAll();
    
    // Contagem de resultados (para exibição)
    $total_encontrado = count($lista_incidentes);
    
    // --- CONSULTAS DE ESTATÍSTICAS ---
    $sql_contagem = "SELECT COUNT(id) FROM controle";
    $total_incidentes = $pdo->query($sql_contagem)->fetchColumn();

    $sql_ultimo = "SELECT data_cadastro FROM controle ORDER BY data_cadastro DESC LIMIT 1";
    $ultimo_cadastro = $pdo->query($sql_ultimo)->fetchColumn(); 

} catch (PDOException $e) {
    die("Erro ao consultar incidentes: " . $e->getMessage());
}
// --- CONSULTA PARA LISTAR USUÁRIOS (Novo Bloco) ---
// Inicialização de variáveis (fora do try/catch)
    $lista_usuarios = [];
    $total_usuarios = 0; // Inicializa a contagem para evitar erros de variável indefinida
try {
    // 1. CONSULTA DE CONTAGEM (MELHOR ABORDAGEM: MAIS RÁPIDA)
    $sql_contagem = "SELECT COUNT(id) FROM usuario";
    // fetchColumn() é a forma mais eficiente de obter um único valor (a contagem)
    $total_usuarios = $pdo->query($sql_contagem)->fetchColumn();

    // 2. CONSULTA PRINCIPAL PARA LISTAGEM
    $sql_usuarios = "SELECT id, nome, login, nivel_permissao FROM usuario ORDER BY id ASC";
    $stmt_usuarios = $pdo->query($sql_usuarios);
    $lista_usuarios = $stmt_usuarios->fetchAll();

    // ALTERNATIVA: Se a contagem já foi feita, não precisa recalcular aqui.

} catch (PDOException $e) {
    // Registra o erro em um arquivo de log, útil em produção
    error_log("Erro ao consultar usuários: " . $e->getMessage());
    $lista_usuarios = [];
    $total_usuarios = 0; // Garante que a contagem seja zero em caso de erro
}

// --------------------------------------------------------------------------
// 6. ESTRUTURA E EXIBIÇÃO HTML
// --------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Lista de Usuários</title>
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        // ----------------------------------------------------
        // FUNÇÃO DE DESENHO DO GRÁFICO (JavaScript)
        // ----------------------------------------------------
        google.charts.load('current', {'packages':['gauge']});
        google.charts.setOnLoadCallback(drawChart);     

        function drawChart() {
            // Pega o total de incidentes do PHP (Passo C)
            var total = <?php echo $total_incidentes; ?>;       

            var data = google.visualization.arrayToDataTable([  
                ['Label', 'Value'],
                // O valor central é o total de incidentes  
                ['Incidentes', total] 
            ]);

            var options = {
                width: 400, height: 120,
                redFrom: 0, redTo: 3000, // Zona vermelha (poucos incidentes)
                yellowFrom: 3001, yellowTo: 10000, // Zona amarela
                greenFrom: 10001, greenTo: 25000, // Zona verde (meta de 25000)
                minorTicks: 5,
                max: 25000 // Escala máxima do seu medidor
            };

            var chart = new google.visualization.Gauge(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>
   <style>
/* ======================================================= */
/* 1. ESTILOS GLOBAIS E FUNDO ANIMADO */
/* ======================================================= */

body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #ffffff;
    position: relative;
    min-height: 100vh;
}

/* Camada 1: Marca d'água Fixa */
body::before {
    content: "";
    position: fixed; /* Mantém a imagem fixa ao rolar */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('claro-operadora.jpg'); /* Verifique se o nome é este ou claro.png */
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0.15; /* Sutil para não atrapalhar a tabela */
    filter: grayscale(50%);
    z-index: -3;
}

/* Camada 2: Gradiente de Malha Animado (Cores Suaves) */
body::after {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -2;
    background: 
        radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%),
        radial-gradient(circle at 50% 50%, rgba(0, 123, 255, 0.02) 0%, transparent 60%);
    filter: blur(80px);
    animation: moveColors 25s ease-in-out infinite alternate;
}

@keyframes moveColors {
    0% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-2%, 2%) scale(1.05); }
    100% { transform: translate(2%, -2%) scale(1); }
}

/* Ajuste de Transparência das Tabelas para o efeito Glassmorphism */
table, .user-table {
    background-color: rgba(255, 255, 255, 0.8) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

    .header {
        color: black; 
        padding: 10px; 
        margin-bottom: 20px; 
        text-align: center; 
        font-weight: bold;
        font-size: 1.5em;
        text-decoration: underline;
    }

    .container-titulo {
        text-align: center;
    }

    h3 { 
        display: inline-block;
        color: #235303ff; /* Cor de destaque */
        text-decoration: underline;
        margin-top: 0;
        padding: 10px;
    }

    p {
        font-size: 18px;
        text-align: center;
        font-weight: bold;
    }

    /* ======================================================= */
    /* 2. ESTILOS DE BOTÕES E LINKS (REDUNDÂNCIA) */
    /* ======================================================= */

    /* Estilos de Botões Padrão (Pesquisar, Paginação) */
    .btn-pesquisar, .btn-page {
        padding: 8px 15px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease; 
    }

    /* Estilo para links de botões (como paginação) */
    .btn-page {
        display: inline-block;
        padding: 8px 12px;
        margin: 0 5px;
        text-decoration: none;
        color: #007bff;
        border: 1px solid #007bff;
        background-color: transparent; /* Transparente para ver o fundo */
    }
    .btn-pesquisar:hover, .btn-page:hover {
        background-color: #0056b3; 
        color: white; /* Garante que o texto fique branco no hover */
    }
    .btn-page.active {
        background-color: #007bff;
        color: white;
        font-weight: bold;
    }
    .btn-page.disabled {
        color: #ccc;
        border-color: #ccc;
        cursor: default;
        background-color: #f9f9f9;
    }

    /* Link Cadastro */
    .link-cadastro {
        display: inline-block; 
        color: #edf5f5ff; 
        text-decoration: none; 
        font-weight: bold;
        padding: 8px 15px; 
        border: 1px solid #021120ff; 
        border-radius: 5px; 
        background-color: #1167c2ee;
        transition: background-color 0.3s, color 0.3s, border-color 0.3s;
    }
    .link-cadastro:hover {
        background-color: #329b08ff; 
        color: white; 
        border-color: #0056b3; 
        text-decoration: none;
    }

    /* Botão Logout */
    .logout-container {
        position: absolute; 
        top: 20px; 
        right: 20px; 
        z-index: 1000;
        margin-bottom: 0;
    }
    .btn-logout {
        display: inline-block; 
        padding: 8px 16px;
        background-color: #0b34ebff;
        color: white;
        text-decoration: none; 
        border-radius: 8px;
        border: black 2px solid;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        font-size: 16px;
        font-weight: bold;
        transition: background-color 0.3s;
    }
    .btn-logout:hover {
        background-color: #c82333;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    /* ======================================================= */
    /* 3. ESTILOS DE TABELAS E CÉLULAS (AGRUPAMENTO E TRANSPARÊNCIA) */
    /* ======================================================= */

    /* Estilos Comuns para Ambas as Tabelas */
    table, .user-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 20px auto; 
        max-width: 1000px; /* Define uma largura máxima para centralização */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); 
        border-radius: 8px; 
        overflow: hidden;
        /* Fundo Transparente para Ver a Marca D'Água */
        background-color: transparent;
    }

    /* Estilos Comuns para Cabeçalhos (th) */
    table th, .user-table th { 
        background-color: #007bff; 
        color: white; 
        padding: 12px 15px; 
        text-align: left; 
        font-weight: bold; 
    }

    /* Estilos Comuns para Células (td) - Aplica Transparência */
    table td, .user-table td { 
        border: 1px solid #ddd; 
        padding: 10px 15px; 
        text-align: left; 
        /* Semi-transparente para ver o body::before */
        background-color: rgba(255, 255, 255, 0.85); 
    }

    /* Fundo Zebrado (Linhas alternadas) - Aplica Transparência */
    table tr:nth-child(even) td, .user-table tbody tr:nth-child(even) td { 
        /* Linha par um pouco mais opaca que a ímpar */
        background-color: rgba(247, 247, 247, 0.9); 
    }

    /* Efeito Hover nas linhas */
    table tbody tr:hover td, .user-table tbody tr:hover td { 
        background-color: rgba(233, 247, 255, 0.95); 
    }

    /* Estilo para a Tabela de Usuários (Apenas ajustes específicos) */
    .user-table {
        width: 90%; 
        max-width: 800px; /* Mantém a largura específica para a tabela de admin */
    }

    /* Estilo do Link de Edição/Ações */
    table a, .user-table a {
        color: #17a2b8; 
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    table a:hover, .user-table a:hover {
        color: #0056b3;
        text-decoration: underline;
    }

    /* ======================================================= */
    /* 4. ESTILOS DE ADMIN/FOOTER */
    /* ======================================================= */

    .admin-header {
        margin-top: 50px;
        text-align: center;
    }

    /* Estilos para o Rodapé (Footer) */
    footer {
        width: 100%;
        border-radius: 5px;
        border: 1px solid #131212ff;
        padding: 20px 0; 
        background-color: #b2cae2ff; 
        max-width: 800px;
        margin: 20px auto; /* Mantendo a margem do footer centralizada */
        color: #239406ff; 
        border-top: 5px solid #3498db; 
    }

    /* Container Principal das Estatísticas */
    .estatisticas {
        display: flex;
        flex-direction: column; 
        align-items: center; 
        padding: 0 20px;
    }

    /* Título (Estatísticas Rápidas) */
    .estatisticas h3 {
        font-size: 1.5em;
        color: #e20e0eff; 
        margin-bottom: 20px;
        padding-bottom: 5px;
        border-bottom: 2px solid #db4d34ff;
        letter-spacing: 1px;
    }

    /* Estilo para a Linha de Texto da Estatística */
    .estatisticas p {
        font-size: 1.1em;
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        background-color: #34495e; 
        color: #ecf0f1;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease-in-out; 
        margin: 10px 20px; 
    }

    /* EFEITO DE HOVER (Animação) */
    .estatisticas p:hover {
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.5);
        transform: translateY(-3px); 
        border: 1px solid #3498db; 
        cursor: pointer;
    }

    /* Estilo para o Número em Destaque */
    .estatisticas strong {
        color: #e67e22; 
        font-size: 1.5em;
        margin-left: 10px;
        font-weight: bold;
    }

    /* Estilo para a mensagem de erro/vazio */
    .no-user-message {
        text-align: center;
        font-weight: bold;
        color: #dc3545; 
        margin-top: 20px;
    }
    #titulo-incidentes {
        text-align: center;
        font-size: 1.6em;
        color: #e02810ff;
     }
    #titulo-incidentes:hover {
        color: #007bff;
        cursor: pointer;
    } 
    #titulo-saudacao {
        font-size: 2em;
        color: #2c3e50;
        text-shadow: 1px 1px 2px #bdc3c7;  
    }  
    #titulo-saudacao:hover {
        color: #27ae60;
        cursor: pointer;
    }   
    .modal-erro-overlay {
    /* Esconde o modal por padrão */
    display: none; 
    position: fixed; /* Fixa na tela */
    z-index: 9999; /* Garante que fique acima de tudo */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6); /* Fundo escuro semi-transparente */
    overflow: auto; 
}

.modal-erro-content {
    background-color: #fff;
    margin: 10% auto; /* Centraliza verticalmente e horizontalmente (10% do topo) */
    padding: 20px;
    border: 3px solid #dc3545; /* Borda vermelha de erro */
    border-radius: 8px;
    width: 80%;
    max-width: 450px; /* Limita a largura para melhor visualização */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    text-align: center;
}

.modal-erro-titulo {
    color: #dc3545;
    font-size: 1.5em;
    margin-bottom: 15px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

#modal-erro-texto {
    font-size: 1.1em;
    color: #333;
    margin-bottom: 20px;
}

.modal-erro-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.modal-erro-close:hover,
.modal-erro-close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

.btn-fechar-modal {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-fechar-modal:hover {
    background-color: #0056b3;
}
    
    </style>
</head>
<body>

    <div class="header">
        <h2 id="titulo-saudacao">Bem-vindo <?php echo htmlspecialchars($nome_do_usuario); ?>!</h2>
    </div>
    <p style="text-align: center; margin-top: 15px;">
                Novo incidente:
                <a href="cadastro.php" class="link-cadastro">Cadastrar Incidente</a>
    </p>
    <div class="logout-container">
        <a href="logout.php" class="btn-logout">Sair</a>
    </div>
    <div class= "container-titulo">
    <div style="max-width: 600px; margin: 0 auto; padding: 10px;">
    
    <form method="GET" action="dashboard.php" style="text-align: center; margin-bottom: 30px;">
        
        <label for="termo" style="font-weight: bold; margin-right: 10px;">Buscar Dados:</label>
        
        <input 
            type="text" 
            id="termo" 
            name="termo_busca" 
            placeholder="Busca por conteúdo..." 
            style="width: 250px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
            value="<?php echo htmlspecialchars($_GET['termo_busca'] ?? ''); ?>"
        >
        
        <button type="submit" class="btn-pesquisar">
            Pesquisar
        </button>
        
    </form>
    <h3 id="titulo-incidentes">Incidentes Cadastrados</h3>
    </div>
    <div style="text-align: center; margin-bottom: 30px; padding: 15px; background-color: rgba(255, 255, 255, 0.5); border-radius: 10px; max-width: 600px; margin: 20px auto; z-index: 5;">
        
        <h4>Estatísticas Rápidas</h4>
        
        <p>
            Total de Incidentes Cadastrados: <strong><?php echo $total_incidentes; ?></strong><br>
            Último Cadastro em: <strong><?php echo $ultimo_cadastro ? htmlspecialchars($ultimo_cadastro) : 'Nenhum'; ?></strong>
        </p>
        
        <div id="chart_div" style="width: 400px; height: 120px; margin: 0 auto;"></div>
        <div id="chart_div"></div>
    </div>
    <div id="status-busca" style="margin-top: 15px; font-weight: bold; text-align: center;">
    <?php 
        $termo_exibido = $_GET['termo_busca'] ?? '';

        if (!empty($termo_exibido)) {
            // Exemplo de saída: "3 resultados encontrados para 'Alice'"
            echo $total_encontrado . " Resultados encontrados para: \"" . htmlspecialchars($termo_exibido) . "\"";
        } else {
            // Exemplo de saída: "Exibindo 15 Incidentes Cadastrados"
            echo "Total de Incidentes Cadastrados por Página: " . $total_encontrado;
        }
    ?>
    </div>
    <?php if (count($lista_incidentes) > 0): ?>
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
                <th>Data de Cadastro</th>
                <th>Ações</th>
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
                    onclick="return confirm('Tem certeza que deseja excluir este incidente?')" 
                    style="color: red;">Excluir</a>
            </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Nenhum incidente encontrado no banco de dados.</p>
    <?php endif; ?>

    <div class="pagination" style="text-align: center; margin-top: 30px; margin-bottom: 30px;">
    <?php if ($total_paginas > 1): ?>
        
        <?php 
            // Constrói a base da URL para os links, mantendo o termo de busca
            $base_url = "dashboard.php?termo_busca=" . urlencode($termo_busca) . "&"; 
        ?>

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
    
    <p style="font-size: 0.9em; margin-top: 10px;">
        Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?>
    </p>
</div>

<div class="admin-header">
    <h3>Administração de Usuários</h3>
</div>

<?php if (count($lista_usuarios) > 0): ?>
<table class="user-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Login</th>
            <th>Permissão Atual</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($lista_usuarios as $usuario): ?>
        <tr>
            <td><?php echo htmlspecialchars($usuario['id'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($usuario['login'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($usuario['nivel_permissao'] ?? ''); ?></td>
            <td>
                <a href="alterar_usuario.php?id=<?php echo $usuario['id']; ?>">Editar Usuário</a>
            </td>
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
    <p>
        Total de Usuários Cadastrados: 
        <strong><?php echo $total_usuarios; ?></strong>
    </p>
    </div>
</footer>
   <!-- Modal de Erro -->
        <div id="modal-erro" class="modal-erro-overlay">
        <div class="modal-erro-content">
        <span class="modal-erro-close" onclick="fecharModal()">×</span>
        <h4 class="modal-erro-titulo">⚠️ Erro de Permissão</h4>
        <p id="modal-erro-texto"></p>
        <button onclick="fecharModal()" class="btn-fechar-modal">Entendi</button>
    </div>
    <script>
    // Variável JS para a mensagem de erro (escapa caracteres especiais para segurança)
    const mensagemErro = <?php echo json_encode($alerta_erro ?? ''); ?>;
    
    // Função para fechar o modal (chamada pelos botões)
    function fecharModal() {
        document.getElementById('modal-erro').style.display = 'none';
    }

    if (mensagemErro) {
        // 1. Encontra os elementos
        const modal = document.getElementById('modal-erro');
        const texto = document.getElementById('modal-erro-texto');
        
        // 2. Insere o texto da mensagem
        texto.innerText = mensagemErro;
        
        // 3. Exibe o modal (ativa o CSS)
        modal.style.display = 'block';
    }
    </script>
 </div>
</body>

</html>


