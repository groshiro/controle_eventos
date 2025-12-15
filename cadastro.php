<?php
// Arquivo: cadastro.php
require_once 'conexao.php'; 

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incidente = $_POST['incidente'] ?? '';
    $evento  = $_POST['evento'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $area = $_POST['area'] ?? '';
    $regiao  = $_POST['regiao'] ?? '';
    $site  = $_POST['site'] ?? '';
    $otdr  = $_POST['otdr'] ?? '';

    // Adicionamos a data de cadastro para consistência (PostgreSQL usa NOW() ou a variável)
    $data_cadastro = date('Y-m-d H:i:s'); // Define a data e hora atual
        
    if (!$pdo) {
        $mensagem = "❌ Erro: Falha na conexão com o BD.";
    } elseif (empty($incidente) || empty($evento) || empty($endereco)|| empty($area)|| empty($regiao)|| empty($site)|| empty($otdr)) {
        $mensagem = "⚠️ Preencha todos os campos.";
    } else {
        // 1. CRIPTOGRAFA A SENHA
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT); 
        
        // 2. INSERÇÃO SEGURA (Assumindo que a tabela é 'usuario' e as colunas são 'login', 'email', 'senha_hash')
        try {
            $sql_insert = "INSERT INTO controle 
                           (incidente, evento, endereco, area, regiao, site, otdr, data_cadastro) 
                           VALUES 
                           (:incidente_ph, :evento_ph, :endereco_ph, :area_ph, :regiao_ph, :site_ph, :otdr_ph, :data_cadastro_ph)";
                           
            $stmt = $pdo->prepare($sql_insert);

            // 3. Executa a instrução, usando as variáveis de INCIDENTE
            $stmt->execute([
                'incidente_ph' => $incidente,
                'evento_ph' => $evento,
                'endereco_ph' => $endereco,
                'area_ph' => $area,
                'regiao_ph' => $regiao,
                'site_ph' => $site,
                'otdr_ph' => $otdr,
                'data_cadastro_ph' => $data_cadastro
            ]);
            
            $mensagem = "✅ Incidente cadastrado com sucesso!";
            // Opcional: Redirecionar após o sucesso
            header("Location: dashboard.php?status=cadastro_ok"); 
            exit;

        } catch (PDOException $e) {
            $mensagem = "❌ Erro grave ao cadastrar incidente: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html LANG="en">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
<style>
        /* CORREÇÃO: Aplicar estilos estruturais e de fundo ao body */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            display: flex; /* Usado para centralizar a div */
            justify-content: center; /* Centraliza horizontalmente */
            align-items: center; /* Centraliza verticalmente */
            min-height: 100vh; /* Garante que a altura da viewport seja coberta */
        }
        
        /* CORREÇÃO: Use uma classe para o contêiner de formulário */
        .card-cadastro {
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
            padding: 20px;
            border: 1px solid #ccc;
        }

        /* Estilos de input e botão permanecem os mesmos */
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Garante que o padding não estoure a largura de 100% */
        }
        h1 {
            /* CENTRALIZAÇÃO PADRÃO */
            text-align: center;

            /* TIPOGRAFIA MODERNA */
            font-size: 1.8em;
            /* Levemente menor que o tamanho padrão do h1 */
            font-weight: 600;
            /* Semi-negrito, mais suave que bold (700) */
            color: #333;
            /* Cinza escuro suave */
            letter-spacing: 0.5px;
            /* Espaçamento sutil */
            margin-bottom: 0;
            /* Remove a margem inferior padrão */
            padding-bottom: 10px;
            /* Adiciona espaço para o sublinhado */
            position: relative;
            /* Contexto para o ::after */
        }

        /* Efeito Sublinhado Decorativo (Linha Moderna) */
        h1::after {
            content: '';
            display: block;
            width: 350px;
            /* Largura da linha */
            height: 3px;
            background-color: #007bff;
            /* Cor primária vibrante */
            margin: 8px auto 0;
            /* Centraliza a linha e a afasta do texto */
            border-radius: 2px;
        }
        button {
            width: 100%; /* Ajuste para o botão preencher a largura */
            background-color: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="card-cadastro">
        
        <h1>Cadastrar Novo Incidente</h1>
        
        <?php if (!empty($mensagem)): ?>
            <p style="color: green; font-weight: bold;"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <label for="incidente">Incidente:</label>
            <input type="text" name="incidente" required><br><br>
            
            <label for="evento">Evento:</label>
            <input type="text" name="evento" required><br><br>
            
            <label for="endereco">Endereço:</label>
            <input type="text" name="endereco" required><br><br>

            <label for="area">Área:</label>
            <input type="text" name="area" required><br><br>

            <label for="regiao">Região:</label>
            <input type="text" name="regiao" required><br><br>

            <label for="site">Site:</label>
            <input type="text" name="site" required><br><br>

            <label for="otdr">OTDR:</label>
            <input type="text" name="otdr" required><br><br>
                 
            <button type="submit">Registrar</button>
        </form>
        <p style="text-align: center; margin-top: 20px;"><a href="index.php">Voltar ao Login</a></p>
    </div>
</body>
</html>