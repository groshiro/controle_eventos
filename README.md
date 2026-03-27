📡 Sistema de Controle de Incidentes - Claro
Este projeto é uma ferramenta robusta de gestão e monitoramento de incidentes de rede, desenvolvida para centralizar informações sobre eventos, áreas afetadas e auditoria de ações, com foco em usabilidade e performance.

🚀 Funcionalidades Principais
Dashboard Interativo: Visualização de indicadores rápidos e gráficos de medidor (Google Charts).

Gestão de Incidentes: CRUD completo (Cadastro, Leitura, Atualização e Exclusão).

Auditoria Dinâmica: Rastreamento detalhado de todas as ações realizadas no sistema.

Controle de Acesso: Sistema de login seguro com diferentes níveis de permissão (ADMIN/VIEW).

NOVO: Relatórios e Impressão: * Impressão Otimizada: Layout inteligente via CSS @media print que remove elementos de navegação para relatórios limpos.

Exportação CSV: Extração de dados direta para Excel/Planilhas para análise externa.

🛠️ Tecnologias Utilizadas
Frontend: HTML5, CSS3 (Design moderno com Glassmorphism e Backdrop Filter), JavaScript.

Backend: PHP 8.x com suporte a sessões seguras.

Banco de Dados: PostgreSQL (Configurado para rodar localmente ou em nuvem).

Integrações: Google Charts API para estatísticas em tempo real.

☁️ Deploy (Render.com)
O sistema está configurado para rodar em ambientes de nuvem como o Render, com suporte automático para:

Conexões seguras via SSL (Require) para o PostgreSQL.

Detecção automática de ambiente (Local vs. Produção) no arquivo de conexão.

Configurações de roteamento via .htaccess para servidores Apache.

📋 Pré-requisitos
Para rodar o projeto localmente, você precisará de:

Servidor Web (Apache/Nginx).

PHP >= 7.4 instalado.

Banco de dados PostgreSQL configurado.

🔧 Instalação
Clone o repositório:

Bash
git clone https://github.com/seu-usuario/controle-claro.git
Configure o Banco de Dados:
Importe a estrutura das tabelas no seu PostgreSQL e ajuste as credenciais no arquivo conexao.php.

Suba o Servidor:
Coloque os arquivos na pasta pública do seu servidor (ex: htdocs ou www) e acesse via navegador.
