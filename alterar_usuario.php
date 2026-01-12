<?php
// Arquivo: alterar_usuario.php
require_once 'conexao.php';
session_start();

// Verifica login imediatamente
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

$id_editar = $_GET['id'] ?? null;

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
    $sql_fetch = "SELECT id, login, email, nome, nivel_permissao FROM usuario WHERE id = :id";
    $stmt = $pdo->prepare($sql_fetch);
    $stmt->execute(['id' => $id_editar]);
    $usuario_atual = $stmt->fetch();

    if (!$usuario_atual) {
        die("Usuário não encontrado.");
    }
    
    $nivel_atual = $usuario_atual['nivel_permissao'] ?? 'VIEW'; 

} catch (PDOException $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Usuário | ID: <?php echo $id_editar; ?></title>
    <style>
        /* 1. ESTILOS GLOBAIS E FUNDO (IGUAL AO DASHBOARD) */
        body { 
            margin: 0; 
            padding: 0; 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background-color: #fff; 
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
        }
        body::before { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: url('claro-operadora.jpg'); background-size: cover; opacity: 0.15; z-index: -3; 
        }
        body::after { 
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; 
            background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%), 
                        radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%); 
            filter: blur(80px); 
        }

        /* 2. TÍTULO PADRONIZADO */
        h1 {
            color: #e02810;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -1px;
            font-size: 1.8em;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }

        /* 3. FORMULÁRIO COM EFEITO VIDRO (GLASSMORPHISM) */
        form {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            padding: 35px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 8px;
            font-weight: 800;
            color: #333;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }

        input[type="text"], 
        input[type="email"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1em;
            font-weight: 600;
            color: #333;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
            background: #fff;
        }

        /* 4. BOTÕES PADRONIZADOS */
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border-radius: 10px;
            font-weight: 800;
            text-transform: uppercase;
            text-decoration: none;
            text-align: center;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            letter-spacing: 1px;
        }

        /* Botão Salvar (Azul do Dashboard) */
        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
        }

        /* Botão Cancelar (Cinza Moderno) */
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

    <h1>Editar Usuário</h1>
    
    <form action="processar_crud.php" method="POST">
        
        <input type="hidden" name="id" value="<?php echo $id_editar; ?>">
        <input type="hidden" name="acao" value="alterar_usuario"> 
        
        <label for="nome">Nome Completo</label>
        <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario_atual['nome']); ?>" required placeholder="Digite o nome completo">
        
        <label for="login">Login de Acesso</label>
        <input type="text" name="login" value="<?php echo htmlspecialchars($usuario_atual['login']); ?>" required placeholder="Usuário do sistema">

        <label for="email">E-mail Corporativo</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario_atual['email']); ?>" required placeholder="exemplo@claro.com.br">
        
        <label for="nivel_permissao">Nível de Permissão</label>
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
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

</body>
</html>