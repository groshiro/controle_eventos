<?php
// Arquivo: div_areas.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}

$nome_do_usuario = $_SESSION['nome_completo'] ?? $_SESSION['usuario_logado'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divisão por Áreas e Coordenadores | Sistema de Controle</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #fff;
            min-height: 100vh;
            color: #333;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url('claro-operadora.jpg');
            background-size: cover;
            opacity: 0.12;
            z-index: -3;
        }

        body::after {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 10% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(220, 53, 69, 0.05) 0%, transparent 40%);
            filter: blur(80px);
        }

        .header {
            width: 100%; padding: 30px 0; text-align: center;
            background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(10px);
            border-bottom: 3px solid #e02810; margin-bottom: 25px;
            position: relative;
        }

        .header h2 {
            margin: 0; font-size: 2.2em; color: #1a1a1a; font-weight: 800;
        }

        .logout-container { position: absolute; top: 25px; right: 30px; }
        .btn-voltar {
            display: inline-block; padding: 10px 22px; background-color: #007bff; color: white;
            text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;
            transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-transform: uppercase;
        }
        .btn-voltar:hover { background-color: #0056b3; transform: translateY(-2px); }

        .container-principal {
            width: 98%;
            max-width: 1750px;
            margin: 0 auto 50px auto;
        }

        .card-tabela {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            padding: 20px;
            overflow-x: auto;
        }

        .titulo-pagina {
            text-align: center;
            color: #e02810;
            font-size: 1.8em;
            margin-top: 0;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        table.matriz-operacional {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: center;
            background-color: transparent;
        }

        table.matriz-operacional th, 
        table.matriz-operacional td {
            border: 1px solid #c2c9d1;
            padding: 8px 5px;
            vertical-align: top;
        }

        .th-periodo {
            background-color: #2c3e50;
            color: #fff;
            font-weight: 800;
            min-width: 90px;
            width: 100px;
        }

        .th-area {
            background-color: #007bff;
            color: white;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            min-width: 110px;
        }

        .coord-geral {
            background-color: #e9ecef;
            color: #212529;
            font-weight: 900;
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px 4px !important;
            border-bottom: 2px solid #007bff !important;
        }

        .coord-manha {
            background-color: #fff9e6;
            color: #b25e00;
            font-weight: 700;
        }

        .coord-tarde {
            background-color: #eef7ff;
            color: #0056b3;
            font-weight: 700;
        }

        .celula-regioes {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 12px 6px !important;
            line-height: 1.6;
        }

        .tag-regiao {
            display: block;
            margin: 2px 0;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 700;
            color: #333;
            transition: all 0.2s ease;
        }

        .tag-regiao:hover {
            background-color: #e02810;
            color: #fff;
            transform: scale(1.05);
        }

        /* Scrollbar customizada */
        .card-tabela::-webkit-scrollbar {
            height: 10px;
        }
        .card-tabela::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logout-container">
            <a href="dashboard.php" class="btn-voltar">⬅ Voltar ao Dashboard</a>
        </div>
        <h2>Sistema de Controle Operacional</h2>
    </div>

    <div class="container-principal">
        <div class="card-tabela">
            <h3 class="titulo-pagina">Divisão Operacional por Áreas e Coordenadores</h3>

            <table class="matriz-operacional">
                <thead>
                    <tr>
                        <th class="th-periodo">PERÍODO</th>
                        <th class="th-area">NORTE 1</th>
                        <th class="th-area">NORTE 2</th>
                        <th class="th-area">NORTE 3</th>
                        <th class="th-area">SUL 1</th>
                        <th class="th-area">SUL 2</th>
                        <th class="th-area">SUL 3</th>
                        <th class="th-area">LESTE 1</th>
                        <th class="th-area">LESTE 2</th>
                        <th class="th-area">LESTE 3</th>
                        <th class="th-area">METRO LESTE</th>
                        <th class="th-area">METRO OESTE</th>
                        <th class="th-area">ABCDM</th>
                    </tr>
                    <tr>
                        <th class="th-periodo" style="background:#34495e;">COORDENAÇÃO</th>
                        <th colspan="3" class="coord-geral">RICARDO LOURENÇO</th>
                        <th colspan="3" class="coord-geral">EDUARDO BORGES</th>
                        <th colspan="3" class="coord-geral">ALEXANDRO TÓIA</th>
                        <th colspan="2" class="coord-geral">DIEGO IANUZZI</th>
                        <th class="coord-geral">-</th>
                    </tr>
                    <tr>
                        <th class="th-periodo" style="background:#495057;">MANHÃ</th>
                        <td class="coord-manha">EDUARDO HENRIQUE</td>
                        <td class="coord-manha">SANDRO</td>
                        <td class="coord-manha">ROBERTO CRISTIANO</td>
                        <td class="coord-manha">RICARDO</td>
                        <td class="coord-manha">EDUARDO HENRIQUE</td>
                        <td class="coord-manha">EDUARDO HENRIQUE</td>
                        <td class="coord-manha">FLAVIO MARROCO</td>
                        <td class="coord-manha">ROBERTO CRISTIANO</td>
                        <td class="coord-manha">LUCAS</td>
                        <td class="coord-manha">RICARDO COSTA</td>
                        <td class="coord-manha">FERNANDO FONSECA</td>
                        <td class="coord-manha">MARCIO BERNARDES</td>
                    </tr>
                    <tr>
                        <th class="th-periodo" style="background:#6c757d;">TARDE</th>
                        <td class="coord-tarde">EDGAR</td>
                        <td class="coord-tarde">ALESSANDRO</td>
                        <td class="coord-tarde">ANTONIONE</td>
                        <td class="coord-tarde">BRUNO MONTEIRO</td>
                        <td class="coord-tarde">WILLIAM AZARA</td>
                        <td class="coord-tarde">BRUNO MONTEIRO</td>
                        <td class="coord-tarde">FERNANDO</td>
                        <td class="coord-tarde">CLEITON</td>
                        <td class="coord-tarde">FERNANDO</td>
                        <td class="coord-tarde">-</td>
                        <td class="coord-tarde">ROGÉRIO NORIO</td>
                        <td class="coord-tarde">-</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 800; background: #f8f9fa;">REGIÕES ATENDIDAS</td>
                        <!-- NORTE 1 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">API</span><span class="tag-regiao">BVI</span><span class="tag-regiao">BUT</span><span class="tag-regiao">CON</span><span class="tag-regiao">JAG</span><span class="tag-regiao">JRE</span><span class="tag-regiao">JDP</span><span class="tag-regiao">LAP</span><span class="tag-regiao">PRD</span><span class="tag-regiao">PIN</span><span class="tag-regiao">REP</span><span class="tag-regiao">VLE</span>
                        </td>
                        <!-- NORTE 2 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">BFU</span><span class="tag-regiao">BRE</span><span class="tag-regiao">BRL</span><span class="tag-regiao">CAC</span><span class="tag-regiao">FRE</span><span class="tag-regiao">JAR</span><span class="tag-regiao">LIM</span><span class="tag-regiao">PRS</span><span class="tag-regiao">PIR</span><span class="tag-regiao">SCE</span><span class="tag-regiao">SDO</span>
                        </td>
                        <!-- NORTE 3 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">CVE</span><span class="tag-regiao">JAC</span><span class="tag-regiao">MAN</span><span class="tag-regiao">SNT</span><span class="tag-regiao">TRE</span><span class="tag-regiao">TUC</span><span class="tag-regiao">VMR</span><span class="tag-regiao">VMD</span>
                        </td>
                        <!-- SUL 1 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">CMB</span><span class="tag-regiao">CUR</span><span class="tag-regiao">IPI</span><span class="tag-regiao">JDP</span><span class="tag-regiao">LIB</span><span class="tag-regiao">MOE</span><span class="tag-regiao">SAC</span><span class="tag-regiao">SAU</span><span class="tag-regiao">VMN</span>
                        </td>
                        <!-- SUL 2 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">CBE</span><span class="tag-regiao">CGR</span><span class="tag-regiao">CAD</span><span class="tag-regiao">CDU</span><span class="tag-regiao">GRA</span><span class="tag-regiao">IBI</span><span class="tag-regiao">JAB</span><span class="tag-regiao">PDR</span><span class="tag-regiao">SAM</span><span class="tag-regiao">SOC</span>
                        </td>
                        <!-- SUL 3 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">CLM</span><span class="tag-regiao">CRE</span><span class="tag-regiao">JDS</span><span class="tag-regiao">MOR</span><span class="tag-regiao">RTA</span><span class="tag-regiao">RPE</span><span class="tag-regiao">VAN</span><span class="tag-regiao">VSO</span>
                        </td>
                        <!-- LESTE 1 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">ARA</span><span class="tag-regiao">BEL</span><span class="tag-regiao">BRS</span><span class="tag-regiao">MOO</span><span class="tag-regiao">PRI</span><span class="tag-regiao">TAT</span><span class="tag-regiao">VFO</span><span class="tag-regiao">VGL</span><span class="tag-regiao">VPR</span>
                        </td>
                        <!-- LESTE 2 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">ARI</span><span class="tag-regiao">CAR</span><span class="tag-regiao">IGU</span><span class="tag-regiao">PQC</span><span class="tag-regiao">SLU</span><span class="tag-regiao">SMT</span><span class="tag-regiao">SRA</span><span class="tag-regiao">SAP</span><span class="tag-regiao">VMT</span><span class="tag-regiao">GUA</span>
                        </td>
                        <!-- LESTE 3 -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">AAL</span><span class="tag-regiao">CNG</span><span class="tag-regiao">CLD</span><span class="tag-regiao">ERM</span><span class="tag-regiao">ITQ</span><span class="tag-regiao">JBO</span><span class="tag-regiao">PEN</span><span class="tag-regiao">PRA</span><span class="tag-regiao">SMI</span><span class="tag-regiao">VCR</span><span class="tag-regiao">VIA</span>
                        </td>
                        <!-- METRO LESTE -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">AUJ</span><span class="tag-regiao">GRU</span><span class="tag-regiao">MCZ</span><span class="tag-regiao">SZN</span><span class="tag-regiao">GRM</span><span class="tag-regiao">BIRITIBA MIRIM</span><span class="tag-regiao">SBL</span><span class="tag-regiao">SALESÓPOLIS</span><span class="tag-regiao">POA</span><span class="tag-regiao">FAV</span><span class="tag-regiao">IAQ</span>
                        </td>
                        <!-- METRO OESTE -->
                        <td class="celula-regioes">
                            <span class="tag-regiao">BRI</span><span class="tag-regiao">CIV</span><span class="tag-regiao">COA</span><span class="tag-regiao">EMB</span><span class="tag-regiao">ICS</span><span class="tag-regiao">ITE</span><span class="tag-regiao">SRE</span><span class="tag-regiao">JAD</span><span class="tag-regiao">OCO</span><span class="tag-regiao">SPB</span><span class="tag-regiao">TBS</span><span class="tag-regiao">VPA</span><span class="tag-regiao">CER</span><span class="tag-regiao">CIR</span><span class="tag-regiao">EGU</span><span class="tag-regiao">Franco da Rocha</span><span class="tag-regiao">FRM</span><span class="tag-regiao">Juquitiba</span><span class="tag-regiao">São Lourenço Da Serra</span><span class="tag-regiao">SRE</span>
                        </td>
                        <!-- ABCDM -->
                         <td class="celula-regioes">
                            <span class="tag-regiao">SOB</span><span class="tag-regiao">DDA</span><span class="tag-regiao">SCS</span><span class="tag-regiao">SNE</span><span class="tag-regiao">MAU</span><span class="tag-regiao">RPS</span><span class="tag-regiao">RGS</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
