# 📡 Sistema de Controle de Incidentes — Claro

Solução robusta para gestão, monitoramento e análise de incidentes de rede, focada em centralizar eventos, áreas afetadas, sites críticos e rastreabilidade operacional.

---

### 🌟 Diferenciais
* **Interface Moderna (Glassmorphism):** Design responsivo baseado em efeitos de transparência e desfoque (`backdrop-filter`), proporcionando uma experiência de usuário (UX) fluida e intuitiva.
* **Análise Visual Avançada:** Painel analítico interativo desenvolvido com **Plotly.js** (velocímetro de volume, distribuição por região, mapa hierárquico *Sunburst*, ranking de áreas/sites e evolução temporal).

---

### 🚀 Funcionalidades
* 📊 **Dashboard Analítico (Plotly.js):** Indicadores em tempo real e gráficos interativos com zoom, filtros dinâmicos e detalhamento ao passar o mouse (*hover*).
* ⚡ **Gestão Completa (CRUD):** Cadastro, busca global, paginação otimizada, edição e exclusão de incidentes, além de consulta de divisão por áreas e coordenadores.
* 👥 **Gestão de Acessos:** Fluxo completo de aprovação de novos cadastros (`ADMIN`/`VIEW`) e desbloqueio administrativo de contas bloqueadas por excesso de tentativas de login.
* 🔍 **Auditoria:** Rastreamento detalhado de cada ação realizada pelos usuários no sistema.
* 🔐 **Segurança:** Controle de permissões por perfil, sessões protegidas, proteção contra bloqueio por força bruta e consultas parametrizadas via PDO.
* 🖨️ **Relatórios e Exportação:** Impressão inteligente (`@media print`) e extração direta de dados em formato CSV/Excel.

---

### 🛠️ Stack Tecnológica
* **Frontend:** HTML5, CSS3 (Glassmorphism / Grid Responsivo), JavaScript ES6, **Plotly.js**.
* **Backend:** PHP 8.x (PDO).
* **Banco de Dados:** PostgreSQL.
* **Cloud:** Deploy otimizado para Render.com (SSL Native).

---

### ☁️ Cloud & Deploy
Projetado para operar em ambiente **Cloud-Native**, o sistema conta com:
* **Auto-Detection:** Alterna automaticamente entre ambiente `localhost` e `production`.
* **Segurança:** Conexões obrigatórias via SSL no servidor de produção.
* **Roteamento:** Configurações de `.htaccess` para servidores Apache.

---

### 📋 Pré-requisitos
* Servidor Apache ou Nginx.
* PHP 8.0 ou superior (com extensão `pdo_pgsql` habilitada).
* PostgreSQL 12+.

---

### 🔧 Instalação

1. **Clonar o repositório:**
   ```bash
   git clone [https://github.com/seu-usuario/controle-claro.git](https://github.com/seu-usuario/controle-claro.git)
Configurar o Banco de Dados:
Importe o esquema SQL no PostgreSQL e ajuste as credenciais no arquivo conexao.php:

PHP
define('HOST', 'localhost');
define('DBNAME', 'controle_claro_new');
Executar:
Mova os arquivos para a pasta raiz do servidor web e acesse http://localhost no navegador.

Desenvolvido com 💻 e ☕ para gestão eficiente de redes.
