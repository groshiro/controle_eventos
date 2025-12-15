<?php
// Arquivo: alterar.php
require_once 'conexao.php';
session_start();

$id_editar = $_GET['id'] ?? null;

// ------------------------------------------------------------------
// 1. REMOVIDO: Array com as opções de permissão (Não aplicável a Incidentes)
// $niveis_disponiveis = [...]
// ------------------------------------------------------------------

if (!$pdo || !$id_editar) {
    header("Location: dashboard.php");
    exit;
}

try {
    // 2. BUSCA DADOS DO INCIDENTE (Mudar para a tabela controle)
    $sql_fetch = "SELECT id, data_cadastro, incidente, evento, endereco, area, regiao, site, otdr FROM controle WHERE id = :id"; 
    $stmt = $pdo->prepare($sql_fetch);
    $stmt->execute(['id' => $id_editar]);
    $incidente_atual = $stmt->fetch(); // Variável correta: $incidente_atual

    // CORREÇÃO CRÍTICA: Se não encontrar o incidente
    if (!$incidente_atual) {
        die("Incidente não encontrado.");
    }
    
    // REMOVIDO: Linhas relacionadas à permissão do usuário
    
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alterar Incidente <?php echo $id_editar; ?></title>
   <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        display: flex;
        flex-direction: column; /* Organiza o título e o formulário verticalmente */
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    h1 {
        color: #333;
        margin-bottom: 20px;
    }
    form {
        background: #fff;
        padding: 30px; /* Aumentado o padding */
        border: 1px solid #ccc;
        border-radius: 8px; /* Arredondamento mais suave */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 350px; /* Define largura fixa para o formulário */
    }
    label {
        display: block;
        margin-top: 15px; /* Espaço entre os grupos de input */
        margin-bottom: 5px;
        font-weight: bold;
    }
    input[type="text"], 
    input[type="email"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    /* Estilo Base para os Botões/Links */
    .btn {
        display: inline-block;
        text-align: center;
        text-decoration: none;
        padding: 10px 15px;
        border-radius: 4px;
        font-weight: bold;
        transition: background-color 0.2s, box-shadow 0.2s;
        margin-top: 20px;
    }
    
    /* Botão Primário (Salvar) */
    .btn-primary {
        background-color: #007bff;
        color: white;
        border: none;
        margin-right: 10px; /* Espaço para o botão Cancelar */
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    
    /* Botão Secundário (Cancelar/Voltar) */
    .btn-secondary {
        background-color: #6c757d; /* Cinza suave */
        color: white;
        border: none;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
</style>
</head>
<body>
    <h1>Alterar Dados do Incidente ID: <?php echo $id_editar; ?></h1>
    
    <form action="processar_crud.php" method="POST">
        
        <input type="hidden" name="id" value="<?php echo $id_editar; ?>">
        <input type="hidden" name="acao" value="alterar">
        
        <label for="incidente">Incidente:</label>
        <input type="text" name="incidente" value="<?php echo htmlspecialchars($incidente_atual['incidente']); ?>" required>
        
        <label for="evento">Evento:</label>
        <input type="text" name="evento" value="<?php echo htmlspecialchars($incidente_atual['evento']); ?>" required>

        <label for="endereco">Endereço:</label>
        <input type="text" name="endereco" value="<?php echo htmlspecialchars($incidente_atual['endereco']); ?>" required>

        <label for="area">Área:</label>
        <input type="text" name="area" value="<?php echo htmlspecialchars($incidente_atual['area']); ?>" required>

        <label for="regiao">Região:</label>
        <input type="text" name="regiao" value="<?php echo htmlspecialchars($incidente_atual['regiao']); ?>" required>

        <label for="site">Site:</label>
        <input type="text" name="site" value="<?php echo htmlspecialchars($incidente_atual['site']); ?>" required>

        <label for="otdr">OTDR:</label>
        <input type="text" name="otdr" value="<?php echo htmlspecialchars($incidente_atual['otdr']); ?>" required>
        
        <br><br>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        
        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
    </form>
</body>
</html>