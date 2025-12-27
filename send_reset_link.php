<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// Ativar log de erros
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 1. CARREGAMENTO MANUAL (Compatível com Render)
$base_path = __DIR__ . '/PHPMailer/src/';

// Se falhar aqui, o erro aparecerá nos Logs do Render
if (!file_exists($base_path . 'Exception.php')) {
    error_log("❌ ERRO: Arquivos do PHPMailer não encontrados em: " . $base_path);
    die("Erro interno de configuração.");
}

require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';

if (!$pdo) {
    header("Location: forgot_password.php?status=erro_bd");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 2. Busca o usuário
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 3. Salvar no Banco
        $sql_update = "UPDATE usuario 
                       SET reset_token = :token_hash, 
                           token_expires_at = :expires_at 
                       WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'token_hash' => $token_hash,
            'expires_at' => $expires_at,
            'id' => $usuario['id']
        ]);
        
        // 4. CONSTRUÇÃO DA URL
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $url_base = $protocol . $_SERVER['HTTP_HOST'] . "/"; 
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        // --- CONFIGURAÇÃO SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; 
        $mail->Password   = getenv('SMTP_PASS') ?: '2735lubi'; 
        
        // STARTTLS na 587 é a configuração padrão recomendada
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 
        $mail->CharSet    = 'UTF-8';

        // --- REMETENTE E DESTINATÁRIO ---
        $mail->setFrom('gr.oshiro@uol.com.br', 'Sistema de Controle');
        $mail->addAddress($email, $usuario['nome']); 
        
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - Sistema de Controle';
        $mail->Body    = "
            <html>
            <body style='font-family: sans-serif;'>
                <h2>Olá, {$usuario['nome']}!</h2>
                <p>Você solicitou a redefinição de senha. O link abaixo expirará em 1 hora.</p>
                <div style='margin: 30px 0;'>
                    <a href='{$link_reset}' style='background-color:#f70b0b; color:white; padding:12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>
                        Criar Nova Senha
                    </a>
                </div>
                <p style='font-size: 12px; color: #666;'>Se você não solicitou, ignore este e-mail.</p>
            </body>
            </html>";
            
        $mail->send();
    }

    // Sucesso (mesmo se o e-mail não existir no BD por segurança)
    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (PDOException $e) {
    error_log("❌ Erro BD: " . $e->getMessage());
    header("Location: forgot_password.php?status=erro_bd");
    exit;
} catch (Exception $e) {
    // Se o PHPMailer falhar, ele grava o motivo nos Logs do Render
    error_log("❌ Erro PHPMailer: " . $e->getMessage());
    header("Location: forgot_password.php?status=erro_email");
    exit;
}








