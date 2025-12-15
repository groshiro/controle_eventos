<?php
// Arquivo: alterar_usuario.php
require_once 'conexao.php';
session_start();

$id_editar = $_GET['id'] ?? null;

// 1. Definição dos Níveis de Permissão
$niveis_disponiveis = [
    'ADMIN' => 'Administrador',
    'EDIT'  => 'Editor de Conteúdo',
    'VIEW'  => 'Somente Visualização'
];

if (!$pdo || !$id_editar) {
    header("Location: dashboard.php");
    exit;
}

try {
    // 2. BUSCAR DADOS DO USUÁRIO NA TABELA 'usuario'
    $sql_fetch = "SELECT id, login, email, nome, nivel_permissao FROM usuario WHERE id = :id";
    $stmt = $pdo->prepare($sql_fetch);
    $stmt->execute(['id' => $id_editar]);
    $usuario_atual = $stmt->fetch();

    if (!$usuario_atual) {
        die("Usuário não encontrado.");
    }
    
    // 3. Define o nível atual (para selecionar no <select>)
    $nivel_atual = $usuario_atual['nivel_permissao'] ?? 'VIEW'; 

} catch (PDOException $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alterar Usuário ID: <?php echo $id_editar; ?></title>
    <style>
        /* ------------------------------------------- */
        /* ESTILOS GERAIS E CENTRALIZAÇÃO */
        /* ------------------------------------------- */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            display: flex;
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
            text-align: center;
        }

        /* ------------------------------------------- */
        /* FORMULÁRIO E CONTROLES */
        /* ------------------------------------------- */
        form {
            background: #fff;
            padding: 30px; 
            border: 1px solid #ccc;
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        label {
            display: block;
            margin-top: 15px; 
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], 
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            /* Garante que o select tenha a mesma altura dos inputs */
            height: 40px; 
        }

        /* ------------------------------------------- */
        /* BOTÕES */
        /* ------------------------------------------- */

        .btn-group {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
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
            width: 48%; /* Divide o espaço entre os dois botões */
            box-sizing: border-box;
        }
        
        /* Botão Primário (Salvar) */
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        /* Botão Secundário (Cancelar/Voltar) */
        .btn-secondary {
            background-color: #6c757d; 
            color: white;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <h1>Alterar Dados do Usuário: <?php echo htmlspecialchars($usuario_atual['nome']); ?></h1>
    
    <form action="processar_crud.php" method="POST">
        
        <input type="hidden" name="id" value="<?php echo $id_editar; ?>">
        <input type="hidden" name="acao" value="alterar_usuario"> 
        
        <label for="nome">Nome:</label>
        <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario_atual['nome']); ?>" required>
        
        <label for="login">Login:</label>
        <input type="text" name="login" value="<?php echo htmlspecialchars($usuario_atual['login']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario_atual['email']); ?>" required>
        
        <label for="nivel_permissao">Nível de Permissão:</label>
        <select name="nivel_permissao" id="nivel_permissao">
            <?php foreach ($niveis_disponiveis as $valor_chave => $texto_display): ?>
            <option value="<?php echo htmlspecialchars($valor_chave); ?>"
                <?php if ($valor_chave === $nivel_atual) { echo 'selected'; } ?>
            >
                <?php echo htmlspecialchars($texto_display); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</body>
</html>