<?php
// Arquivo: processar_crud.php
session_start();
require_once 'conexao.php';

if (!$pdo) {
    die("Falha na conexão.");
}

// ----------------------------------------------------
// 🛑 PASSO 1: VERIFICAÇÃO DE AUTENTICAÇÃO E USUÁRIO
// ----------------------------------------------------
if (!isset($_SESSION['usuario_logado']) || empty($_SESSION['nivel_permissao'])) {
    header("Location: index.php?erro=sessao_expirada"); 
    exit; 
}

// Captura o nome de quem está operando o sistema para a Auditoria
$usuario_ativo = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Sistema';

$acao = $_GET['acao'] ?? $_POST['acao'] ?? ''; 
$id = $_GET['id'] ?? $_POST['id'] ?? null; 

$nivel_permissao = trim($_SESSION['nivel_permissao']); 
$PERMISSAO_ADMIN = 'ADMIN'; 

// ----------------------------------------------------
// 🛑 PASSO 2: VERIFICAÇÃO DE AUTORIZAÇÃO
// ----------------------------------------------------
$acoes_admin = ['excluir', 'alterar_usuario']; 
$acoes_edicao_restrita = ['alterar'];          

if (in_array($acao, $acoes_admin)) {
    if ($nivel_permissao != $PERMISSAO_ADMIN) {
        $_SESSION['alerta_erro'] = "Acesso Negado: Requer permissão 'ADMIN'.";
        header("Location: dashboard.php");
        exit;
    }
} elseif (in_array($acao, $acoes_edicao_restrita)) {
    if ($nivel_permissao == 'VIEW') {
        $_SESSION['alerta_erro'] = "Acesso Negado: Usuários 'VIEW' não podem editar dados.";
        header("Location: dashboard.php");
        exit;
    }
}

if (!$id || empty($acao)) {
    header("Location: dashboard.php");
    exit;
}

try {
    // ----------------------------------------------------
    // 🗑️ AÇÃO: EXCLUIR INCIDENTE
    // ----------------------------------------------------
    if ($acao == 'excluir') {
        $sql_delete = "DELETE FROM controle WHERE id = :id"; 
        $stmt = $pdo->prepare($sql_delete);
        $stmt->execute(['id' => $id]);

        header("Location: dashboard.php?status=excluido"); 
        exit;

    // ----------------------------------------------------
    // 👤 AÇÃO: ALTERAR USUÁRIO (SISTEMA ADMIN)
    // ----------------------------------------------------
    } elseif ($acao == 'alterar_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
        $nome = $_POST['nome'] ?? ''; 
        $login = $_POST['login'] ?? '';
        $email = $_POST['email'] ?? '';
        $nivel_permissao_novo = $_POST['nivel_permissao'] ?? ''; 

        $sql_update = "UPDATE usuario SET nome = :nome, login = :login, email = :email, nivel_permissao = :nivel WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'nome' => $nome, 
            'login' => $login, 
            'email' => $email, 
            'nivel' => $nivel_permissao_novo, 
            'id' => $id
        ]);

        header("Location: dashboard.php?status=usuario_alterado");
        exit;
        
    // ----------------------------------------------------
    // 📝 AÇÃO: ALTERAR INCIDENTE (COM AUDITORIA)
    // ----------------------------------------------------
    } elseif ($acao == 'alterar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $incidente = $_POST['incidente'] ?? '';
        $evento    = $_POST['evento'] ?? '';
        $endereco  = $_POST['endereco'] ?? '';
        $area      = $_POST['area'] ?? '';
        $regiao    = $_POST['regiao'] ?? '';
        $site      = $_POST['site'] ?? '';
        $otdr      = $_POST['otdr'] ?? '';

        // IMPORTANTE: Incluímos 'alterado_por' para registrar quem salvou
        $sql_update = "UPDATE controle SET 
                        incidente = :incidente, 
                        evento = :evento, 
                        endereco = :endereco, 
                        area = :area, 
                        regiao = :regiao, 
                        site = :site, 
                        otdr = :otdr,
                        alterado_por = :usuario_ativo 
                       WHERE id = :id";

        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'incidente' => $incidente,
            'evento'    => $evento,
            'endereco'  => $endereco,
            'area'      => $area,
            'regiao'    => $regiao,
            'site'      => $site,
            'otdr'      => $otdr,
            'usuario_ativo' => $usuario_ativo, // Nome vindo da $_SESSION
            'id'        => $id
        ]);

        header("Location: dashboard.php?status=alterado");
        exit;
    }
    
} catch (PDOException $e) {
    header("Location: dashboard.php?status=erro&msg=" . urlencode($e->getMessage()));
    exit;
}

header("Location: dashboard.php");
exit;