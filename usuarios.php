<?php
require_once 'conexao.php';
session_start();

// Proteção: Apenas ADMIN acessa esta página
if (!isset($_SESSION['usuario_logado']) || $_SESSION['nivel_permissao'] !== 'ADMIN') {
    header("Location: dashboard.php");
    exit();
}

try {
    // 1. Consulta organizada por ID DESC (Mais recentes primeiro)
    $stmt = $pdo->query("SELECT id, nome, login, email, nivel_permissao FROM usuario ORDER BY id DESC");
    $lista_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Contagem total
    $total_usuarios = count($lista_usuarios);

    // 3. Dados para o Gráfico
    $contagem = ['ADMIN' => 0, 'USER' => 0, 'VIEW' => 0];
    foreach ($lista_usuarios as $u) {
        $nv = strtoupper($u['nivel_permissao']);
        if (isset($contagem[$nv])) $contagem[$nv]++;
    }
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários | Sistema</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Nível', 'Qtd'],
          ['ADMIN', <?php echo $contagem['ADMIN']; ?>],
          ['USER',  <?php echo $contagem['USER']; ?>],
          ['VIEW',  <?php echo $contagem['VIEW']; ?>]
        ]);
        var options = {
          pieHole: 0.4,
          colors: ['#e02810', '#007bff', '#6c757d'],
          legend: {position: 'bottom'},
          chartArea: {width: '90%', height: '80%'}
        };
        var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; min-height: 100vh; position: relative; }
        /* IMAGEM DE FUNDO PADRÃO CLARO */
        body::before { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: url('claro-operadora.jpg'); background-size: cover; 
            opacity: 0.1; z-index: -1; 
        }
        .container { 
            max-width: 1250px; margin: 20px auto; background: rgba(255,255,255,0.9); 
            padding: 30px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            backdrop-filter: blur(5px);
        }
        .header-box { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #e02810; padding-bottom: 15px; margin-bottom: 25px; }
        h2 { color: #e02810; margin: 0; text-transform: uppercase; font-weight: 800; }
        .badge-count { background: #34495e; color: white; padding: 6px 15px; border-radius: 20px; font-weight: bold; }
        
        .main-content { display: flex; gap: 25px; flex-wrap: wrap; }
        .table-area { flex: 2; min-width: 650px; }
        .chart-area { flex: 1; min-width: 320px; background: white; border-radius: 10px; padding: 15px; border: 1px solid #eee; height: fit-content; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; font-size: 0.75em; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 0.85em; color: #333; }
        tr:hover { background-color: rgba(0, 123, 255, 0.05); }

        .btn-edit { color: #007bff; text-decoration: none; font-weight: bold; }
        .btn-delete { color: #e02810; font-weight: bold; cursor: pointer; margin-left: 12px; }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; }
        .modal-card { background: white; padding: 30px; border-radius: 12px; width: 380px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-btns { margin-top: 25px; display: flex; justify-content: space-around; }
        .btn-m-cancel { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .btn-m-confirm { background: #e02810; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>Gestão de Usuários</h2>
        <div style="display: flex; align-items: center; gap: 15px;">
            <span class="badge-count">Cadastrados: <?php echo $total_usuarios; ?></span>
            <a href="dashboard.php" style="text-decoration:none; color:#6c757d; font-weight:bold;">← Voltar</a>
        </div>
    </div>

    <div class="main-content">
        <div class="table-area">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Email</th>
                        <th>Nível</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_usuarios as $user): ?>
                    <tr>
                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($user['nome']); ?></td>
                        <td><?php echo htmlspecialchars($user['login']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span style="color:#555;"><?php echo $user['nivel_permissao']; ?></span></td>
                        <td>
                            <a href="alterar_usuario.php?id=<?php echo $user['id']; ?>" class="btn-edit">Editar</a>
                            <span class="btn-delete" onclick="confirmarExclusao('<?php echo $user['id']; ?>', '<?php echo addslashes($user['nome']); ?>')">Excluir</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="chart-area">
            <h4 style="text-align:center; color:#444; margin-top:0;">Níveis de Acesso</h4>
            <div id="chart_div" style="width: 100%; height: 280px;"></div>
        </div>
    </div>
</div>

<div id="modalExcluir" class="modal-overlay">
    <div class="modal-card">
        <h3 style="color:#e02810;">Excluir Usuário?</h3>
        <p>Deseja realmente remover o usuário:<br><strong id="nomeModal"></strong>?</p>
        <div class="modal-btns">
            <button onclick="fecharModal()" class="btn-m-cancel">Cancelar</button>
            <a id="linkExcluir" href="#" class="btn-m-confirm">Confirmar Exclusão</a>
        </div>
    </div>
</div>

<script>
    function confirmarExclusao(id, nome) {
        document.getElementById('nomeModal').innerText = nome;
        document.getElementById('linkExcluir').href = "processar_crud.php?acao=excluir_usuario&id=" + id;
        document.getElementById('modalExcluir').style.display = 'flex';
    }
    function fecharModal() {
        document.getElementById('modalExcluir').style.display = 'none';
    }
</script>

</body>

</html>
