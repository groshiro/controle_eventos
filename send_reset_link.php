<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// 1. Configurações de Log (O erro detalhado aparecerá na aba Logs do Render)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. Ajuste de Caminho (Garante que o Render ache a pasta no Linux)
$base_path = __DIR__ . '/PHPMailer/src/';

if (!file_exists($base_path . 'Exception.php')) {
    error_log("❌ Erro Crítico: Pasta PHPMailer não encontrada em: " . $base_path);
    die("Erro de configuração interna.");
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
    // 3. Busca o usuário
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 4. Salva o Token no Banco
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
        
        // 5. URL com HTTPS para o Render
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $url_base = $protocol . $_SERVER['HTTP_HOST'] . "/"; 
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        // 6. Configuração SMTP para UOL no Render
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; 
        
        // Importante: Pega a senha do painel Environment do Render
        $mail->Password   = getenv('SMTP_PASS') ?: '2735lubi'; 
        
        // Uso da Porta 465 com SMTPS (mais resiliente em servidores Cloud)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet    = 'UTF-8';

        // Opções extras para evitar erro de certificado SSL no Render
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Remetente e Destinatário
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

    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (PDOException $e) {
    error_log("❌ Erro BD: " . $e->getMessage());
    header("Location: forgot_password.php?status=erro_bd");
    exit;
} catch (Exception $e) {
    // Isso vai parar o redirecionamento e imprimir o erro real na tela
    echo "<h1>Erro Técnico Detalhado:</h1>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Erro do PHPMailer: " . $mail->ErrorInfo;
    exit; // Impede o redirecionamento para podermos ler
}







