<?php
// Arquivo: validar_login.php
ob_start();
session_start();

require_once 'conexao.php'; 

$login_usuario = trim($_POST['login'] ?? ''); 
$senha_usuario = $_POST['pswd'] ?? ''; 

if (!isset($pdo) || $pdo === null) {
    ob_end_clean();
    header("Location: index.php?erro=3"); 
    exit;
}

if (empty($login_usuario) || empty($senha_usuario)) {
    ob_end_clean();
    header("Location: index.php?erro=2");
    exit;
}

try {
    $instrucaoSQL = 'SELECT id, senha_hash, nome, nivel_permissao, status, COALESCE(tentativas_login, 0) AS tentativas_login FROM usuario WHERE login = :login'; 
    $stmt = $pdo->prepare($instrucaoSQL);
    $stmt->execute(['login' => $login_usuario]);
    $controle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($controle) {
        $status_conta = strtoupper(trim($controle['status'] ?? 'PENDENTE'));

        if ($status_conta === 'PENDENTE') {
            ob_end_clean();
            header("Location: index.php?erro=4");
            exit;
        }

        if ($status_conta !== 'ATIVO' && $status_conta !== 'APROVADO') {
            ob_end_clean();
            header("Location: index.php?erro=5");
            exit;
        }

        if (password_verify($senha_usuario, $controle['senha_hash'])) {
            $stmt_reset = $pdo->prepare("UPDATE usuario SET tentativas_login = 0 WHERE id = :id");
            $stmt_reset->execute(['id' => $controle['id']]);

            session_regenerate_id(true); 
            
            $_SESSION['usuario_id'] = $controle['id']; 
            $_SESSION['usuario_logado'] = $login_usuario;
            $_SESSION['nome_completo'] = $controle['nome']; 
            $_SESSION['nivel_permissao'] = $controle['nivel_permissao'];
            
            ob_end_clean();
            header("Location: dashboard.php");
            exit;
        } else {
            $tentativas = (int)$controle['tentativas_login'] + 1;

            if ($tentativas >= 3) {
                $stmt_bloq = $pdo->prepare("UPDATE usuario SET status = 'BLOQUEADO', tentativas_login = :t WHERE id = :id");
                $stmt_bloq->execute(['t' => $tentativas, 'id' => $controle['id']]);

                ob_end_clean();
                header("Location: index.php?erro=7");
                exit;
            } else {
                $stmt_upd = $pdo->prepare("UPDATE usuario SET tentativas_login = :t WHERE id = :id");
                $stmt_upd->execute(['t' => $tentativas, 'id' => $controle['id']]);

                ob_end_clean();
                if ($tentativas === 2) {
                    header("Location: index.php?erro=6");
                } else {
                    header("Location: index.php?erro=1");
                }
                exit;
            }
        }
    } else {
        ob_end_clean();
        header("Location: index.php?erro=1");
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro no Render: " . $e->getMessage());
    ob_end_clean();
    header("Location: index.php?erro=3");
    exit;
}
