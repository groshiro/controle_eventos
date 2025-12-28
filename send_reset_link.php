<?php
require_once 'conexao.php';

// 1. Configurações Mailjet
$apiKey = 'e62bc1c69902deb493c7da7a8fda7bbb';
$apiSecret = '9f31b3449518906b7ec44ed5664d6e4a';

$email_destino = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email_destino)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuario WHERE email = :email");
    $stmt->execute(['email' => $email_destino]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE usuario SET reset_token = ?, token_expires_at = ? WHERE id = ?");
        $stmt->execute([$token_hash, $expires_at, $usuario['id']]);
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $link_reset = $protocol . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token . "&email=" . urlencode($email_destino);

        // 2. PREPARAÇÃO DO ENVIO (API MAILJET)
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "gr.oshiro@uol.com.br", // O e-mail que você validou no Mailjet
                        'Name' => "Sistema de Controle"
                    ],
                    'To' => [
                        [
                            'Email' => $email_destino,
                            'Name' => $usuario['nome']
                        ]
                    ],
                    'Subject' => "Redefinição de Senha",
                    'HTMLPart' => "<h3>Olá {$usuario['nome']}!</h3><p>Clique no link para resetar sua senha:</p><a href='{$link_reset}'>{$link_reset}</a>"
                ]
            ]
        ];

        $ch = curl_init('https://api.mailjet.com/v3.1/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_USERPWD, "{$apiKey}:{$apiSecret}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);
    }

    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (Exception $e) {
    error_log("Erro Mailjet: " . $e->getMessage());
    header("Location: forgot_password.php?status=erro");
}

