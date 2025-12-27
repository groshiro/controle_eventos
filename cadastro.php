<?php
// Arquivo: cadastro.php
require_once 'conexao.php'; 

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recebe os dados do formulário
    $incidente = $_POST['incidente'] ?? '';
    $evento  = $_POST['evento'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $area = $_POST['area'] ?? '';
    $regiao  = $_POST['regiao'] ?? '';
    $site  = $_POST['site'] ?? '';
    $otdr  = $_POST['otdr'] ?? '';

    // Define a data e hora atual
    $data_cadastro = date('Y-m-d H:i:s'); 
        
    if (!$pdo) {
        $mensagem = "❌ Erro: Falha na conexão com o BD.";
    } elseif (empty($incidente) || empty($evento) || empty($endereco)|| empty($area)|| empty($regiao)|| empty($site)|| empty($otdr)) {
        $mensagem = "⚠️ Preencha todos os campos.";
    } else {
        try {
            // 2. INSERÇÃO SEGURA na tabela 'controle'
            $sql_insert = "INSERT INTO controle 
                           (incidente, evento, endereco, area, regiao, site, otdr, data_cadastro) 
                           VALUES 
                           (:incidente_ph, :evento_ph, :endereco_ph, :area_ph, :regiao_ph, :site_ph, :otdr_ph, :data_cadastro_ph)";
                           
            $stmt = $pdo->prepare($sql_insert);

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
            
            // Redireciona com status de sucesso
            header("Location: dashboard.php?status=cadastro_ok"); 
            exit;

        } catch (PDOException $e) {
            $mensagem = "❌ Erro grave ao cadastrar incidente: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Incidente | Sistema</title>
    <style>
        /* 1. RESET E FUNDO ANIMADO (Padronizado) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden; /* Permite scroll vertical se o form for grande */
            background-color: #ffffff;
            position: relative;
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

        /* 2. CARD COM EFEITO DE VIDRO (GLASSMORPHISM) */
        .card-cadastro {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            padding: 30px;
            margin: 20px 0;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            text-align: center;
            font-size: 1.6rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 700;
        }

        h1::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: #007bff;
            margin: 10px auto;
            border-radius: 10px;
        }

        /* 3. ESTILOS DE FORMULÁRIO */
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        
        input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 15px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            transition: 0.3s;
            outline: none;
        }
        
        input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
            transform: scale(1.01);
        }
        
        button {
            width: 100%; 
            background-color: #28a745;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
            margin-top: 10px;
        }

        button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }

        .voltar-link {
            text-align: center;
            margin-top: 20px;
        }

        .voltar-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .voltar-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-animation"></div>

    <div class="card-cadastro">
        <h1>Novo Incidente</h1>
        
        <?php if (!empty($mensagem)): ?>
            <p style="color: #d9534f; font-weight: bold; text-align: center; margin-bottom: 15px;">
                <?php echo $mensagem; ?>
            </p>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <label for="incidente">Incidente:</label>
            <input type="text" name="incidente" placeholder="Número do incidente" required>
            
            <label for="evento">Evento:</label>
            <input type="text" name="evento" placeholder="Descrição do evento" required>
            
            <label for="endereco">Endereço:</label>
            <input type="text" name="endereco" placeholder="Rua, Avenida, Alameda...." required>

            <label for="area">Área:</label>
            <input type="text" name="area" placeholder="Cluster" required>

            <label for="regiao">Região:</label>
            <input type="text" name="regiao" placeholder="Cidade, Bairro" required>

            <label for="site">Site:</label>
            <input type="text" name="site" placeholder="Nome do site/estação" required>

            <label for="otdr">OTDR:</label>
            <input type="text" name="otdr" placeholder="Medição OTDR" required>
                 
            <button type="submit">Registrar Incidente</button>
        </form>
        
        <div class="voltar-link">
            <a href="dashboard.php">← Voltar a tela inicial</a>
        </div>
    </div>
</body>
</html>