<?php
// Arquivo: admin_usuarios.php
session_start();
require_once 'conexao.php';

// 1. Controle de Acesso: Apenas ADMIN logado pode acessar
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_permissao'] ?? '') !== 'ADMIN') {
    header("Location: index.php?erro=1");
    exit;
}

$mensagem = "";
$tipo_mensagem = "";

// 2. Processamento das Ações do Administrador (Aprovar / Recusar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $novo_nivel = $_POST['nivel_permissao'] ?? 'VIEW';

    if (!$id_usuario) {
        $mensagem = "❌ Identificador de usuário inválido.";
        $tipo_mensagem = "erro";
    } else {
        try {
            if ($acao === 'aprovar') {
                $nivel_final = ($novo_nivel === 'ADMIN') ? 'ADMIN' : 'VIEW';
                $stmt = $pdo->prepare("UPDATE usuario SET status = 'ATIVO', nivel_permissao = :nivel WHERE id = :id");
                $stmt->execute([
                    'nivel' => $nivel_final,
                    'id'    => $id_usuario
                ]);
                $mensagem = "✅ Usuário aprovado com sucesso!";
                $tipo_mensagem = "sucesso";

            } elseif ($acao === 'rejeitar') {
                // Opção 1: Deletar a solicitação diretamente
                $stmt = $pdo->prepare("DELETE FROM usuario WHERE id = :id");
                // Opção 2 (se preferir manter histórico de rejeitados):
                // $stmt = $pdo->prepare("UPDATE usuario SET status = 'BLOQUEADO' WHERE id = :id");

                $stmt->execute(['id' => $id_usuario]);
                $mensagem = "⚠️ Solicitação rejeitada com sucesso!";
                $tipo_mensagem = "sucesso";
            }
        } catch (PDOException $e) {
            $mensagem = "❌ Erro ao atualizar status: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// 3. Buscar todos os usuários com status 'PENDENTE'
try {
    $stmt = $pdo->query("SELECT id, nome, login, email, nivel_permissao FROM usuario WHERE status = 'PENDENTE' ORDER BY id ASC");
    $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendentes = [];
    $mensagem = "❌ Erro ao listar pendências: " . $e->getMessage();
    $tipo_mensagem = "erro";
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Autorização de Usuários | Painel Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 1. RESET E FUNDO ANIMADO (Idêntico ao Index e Cadastro) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #ffffff;
            position: relative;
            padding: 20px;
        }

        /* IMAGEM DE FUNDO FIXA */
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('claro.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.25;
            z-index: -3;
        }

        /* ANIMAÇÃO DE CORES FLUTUANTES */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: 
                radial-gradient(circle at 20% 30%, rgba(0, 123, 255, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(220, 53, 69, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(0, 123, 255, 0.05) 0%, transparent 60%);
            filter: blur(60px);
            animation: moveColors 20s ease-in-out infinite alternate;
        }

        @keyframes moveColors {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-5%, 5%) scale(1.1); }
            100% { transform: translate(5%, -5%) scale(1); }
        }

        /* 2. CARD GLASSMORPHISM LARGO */
        .admin-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 950px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            padding: 35px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-painel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 1.6rem;
            color: #333;
            font-weight: 700;
        }

        .contador {
            display: inline-block;
            background: #ffc107;
            color: #212529;
            font-size: 0.8rem;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .link-voltar {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            transition: 0.2s;
        }

        .link-voltar:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* 3. MENSAGENS DE STATUS */
        .mensagem {
            text-align: center;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.92rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .msg-sucesso {
            background-color: rgba(40, 167, 69, 0.12);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.25);
        }

        .msg-erro {
            background-color: rgba(220, 53, 69, 0.12);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.25);
        }

        /* 4. TABELA RESPONSIVA */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            padding: 12px 14px;
            font-size: 0.85rem;
            color: #666;
            font-weight: 700;
            border-bottom: 2px solid rgba(0, 0, 0, 0.06);
            text-transform: uppercase;
        }

        td {
            padding: 14px;
            font-size: 0.95rem;
            color: #333;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        /* 5. CAMPOS E BOTÕES DE AÇÃO */
        select {
            padding: 8px 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: #ffffff;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
        }

        select:focus {
            border-color: #007bff;
        }

        .acoes-container {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            align-items: center;
        }

        .btn {
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-aprovar {
            background-color: #28a745;
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        }

        .btn-aprovar:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .btn-rejeitar {
            background-color: #dc3545;
            color: white;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
        }

        .btn-rejeitar:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        /* ESTADO VAZIO */
        .sem-pendencias {
            text-align: center;
            padding: 40px 10px;
            color: #666;
        }

        .sem-pendencias .icone {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>

<body>
    <div class="bg-image"></div>
    <div class="bg-animation"></div>

    <main class="admin-container">
        <header class="header-painel">
            <h1>
                Aprovações Pendentes
                <span class="contador"><?= count($pendentes) ?></span>
            </h1>
            <a href="dashboard.php" class="link-voltar">← Voltar ao Sistema</a>
        </header>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?= $tipo_mensagem === 'sucesso' ? 'msg-sucesso' : 'msg-erro' ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (count($pendentes) === 0): ?>
            <div class="sem-pendencias">
                <span class="icone">🎉</span>
                <p>Nenhuma conta pendente de aprovação no momento!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Usuário (Login)</th>
                            <th>E-mail</th>
                            <th>Nível de Acesso</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendentes as $usuario): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($usuario['login'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                
                                <form method="POST" action="admin_usuarios.php">
                                    <input type="hidden" name="id_usuario" value="<?= (int)$usuario['id'] ?>">
                                    
                                    <td>
                                        <select name="nivel_permissao">
                                            <option value="VIEW" <?= $usuario['nivel_permissao'] === 'VIEW' ? 'selected' : '' ?>>VIEW</option>
                                            <option value="ADMIN" <?= $usuario['nivel_permissao'] === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                                        </select>
                                    </td>

                                    <td>
                                        <div class="acoes-container">
                                            <button type="submit" name="acao" value="aprovar" class="btn btn-aprovar">
                                                Aprovar
                                            </button>
                                            <button type="submit" name="acao" value="rejeitar" class="btn btn-rejeitar" onclick="return confirm('Tem certeza que deseja recusar este usuário?');">
                                                Recusar
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>