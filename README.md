📡 Sistema de Controle de Incidentes — Claro

Solução robusta para gestão e monitoramento de incidentes de rede, focada em centralizar eventos, áreas afetadas e auditoria.

🌟 Diferenciais
Interface moderna baseada em Glassmorphism (efeito de transparência e desfoque), unindo estética de ponta a uma experiência de usuário (UX) fluida.

🚀 Funcionalidades
📊 Dashboard: Indicadores em tempo real e gráficos via Google Charts.

⚡ Gestão Completa: CRUD total de incidentes (Cadastro, Leitura, Edição e Exclusão).

🔍 Auditoria: Rastreamento detalhado de cada ação realizada no sistema.

🔐 Segurança: Níveis de acesso (ADMIN/VIEW) com sessões protegidas.

🖨️ Relatórios: Impressão inteligente (@media print) e exportação em CSV/Excel.

🛠️ Stack Tecnológica
Frontend: HTML5, CSS3 (Backdrop Filter), JavaScript ES6.

Backend: PHP 8.x (Arquitetura funcional).

Banco de Dados: PostgreSQL.

Cloud: Deploy otimizado para Render.com (SSL Native).

☁️ Cloud & Deploy
Projetado para ser Cloud-Native, o sistema conta com:

Auto-Detection: Alterna automaticamente entre ambiente localhost e production.

Segurança: Conexões obrigatórias via SSL no servidor de produção.

Roteamento: Configurações de .htaccess para servidores Apache.

📋 Pré-requisitos
Servidor Apache ou Nginx.

PHP 7.4 ou superior.

PostgreSQL 12+.

🔧 Instalação
Clonar repositório:

Bash
git clone https://github.com/seu-usuario/controle-claro.git
Configurar Banco:
Importe o esquema SQL e ajuste o conexao.php:

PHP
define('HOST', 'localhost');
define('DBNAME', 'controle_claro_new');
Executar:
Mova os arquivos para a pasta raiz do servidor e acesse http://localhost.

<p align="center">Desenvolvido com 💻 e ☕ para gestão eficiente de redes.</p>
