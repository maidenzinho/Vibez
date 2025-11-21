# Vibez
 Rede Social em **desenvolvimento**, criada por estudantes da PUCPR, não recomendado para produtividade por enquanto, estamos trabalhando para deixar melhor e segura, logo mais teremos muitas features novas, aceitamos ajuda da comunidade! (Analisaremos o código antes de aceitar).

*Login de Admin:*
User: admin
Email: admin@admin.com
Senha: admin1234

Este repositório contém o código de uma rede social chamada **Vibez**, desenvolvida em **PHP + MySQL/MariaDB**, com:

- Sistema de cadastro/login com confirmação por e-mail (token de verificação);
- Perfis de usuário com foto, banner, tema claro/escuro;
- Posts com imagens;
- Notificações;
- Chat com envio de **texto, imagem, áudio e vídeo**.

Este `README.md` explica como:

1. Preparar o ambiente (PHP, servidor web, banco de dados);
2. Criar e configurar o banco de dados;
3. Criar o arquivo `.env` com credenciais do banco e do SMTP;
4. Ajustar permissões de pastas (se necessário);
5. Testar o sistema e o chat;
6. Resolver erros comuns.

---

## 1. Requisitos do Ambiente

Você pode usar:

- **XAMPP** (Windows / Linux / macOS);
- Qualquer outro stack PHP + MySQL/MariaDB (Laragon, Wamp, LAMP, etc.).

Mínimo recomendado:

- **PHP**: 8.0+
- **Banco**: MySQL ou MariaDB
- Extensões PHP:
  - `pdo_mysql`
  - `openssl`
  - `mbstring`
  - `curl`

> Em ambientes como XAMPP ou Laragon, quase tudo isso já vem habilitado por padrão.

---

## 2. Estrutura Básica do Projeto

Supondo que o projeto foi colocado em:

- **Windows (XAMPP)**: `C:\xampp\htdocs\Vibez`
- **Linux (LAMP)**: `/var/www/html/Vibez`

Estrutura relevante (simplificada):

```text
Vibez/
├─ chat/
│  ├─ index.php
│  ├─ send_message.php
│  └─ get_messages.php
├─ api/
│  └─ search_users.php
├─ assets/
│  ├─ css/
│  │  ├─ main.css
│  │  ├─ dark-theme.css
│  │  ├─ light-theme.css
│  │  └─ chat.css
│  └─ js/
│     └─ chat.js
├─ includes/
│  ├─ config.php
│  ├─ functions.php
│  ├─ sidebar.php
│  ├─ menumobile.php
│  └─ load_env.php
├─ uploads/
│  └─ (fotos de perfil, banners, posts etc.)
├─ uploads/chat/
│  └─ (arquivos de mídia do chat)
├─ vibez_vibeeez.sql
├─ register.php
├─ login.php
├─ profile.php
├─ notifications.php
├─ settings.php
└─ .env
```

---

## 3. Banco de Dados

### 3.1. Criar o banco

No MySQL/MariaDB (via phpMyAdmin ou linha de comando), crie o banco com o nome:

```sql
CREATE DATABASE vibez_vibeeez
  CHARACTER SET utf8mb3
  COLLATE utf8mb3_general_ci;
```

### 3.2. Importar o arquivo `vibez_vibeeez.sql`

1. Abra o **phpMyAdmin** (ou seu cliente de banco favorito);
2. Selecione o banco `vibez_vibeeez`;
3. Vá em **Importar**;
4. Selecione o arquivo `vibez_vibeeez.sql` que está na pasta do projeto (**Vibez/**);
5. Clique em **Ir / Importar**.

> Esse arquivo já contém **toda a estrutura e dados necessários** (tabelas, colunas e relações).  
> Não é necessário rodar manualmente `ALTER TABLE` ou criar tabelas na mão – apenas **crie o banco** e **importe o `.sql`**.

Se você atualizar o projeto no futuro e receber um novo `vibez_vibeeez.sql`, basta:

- Fazer backup do banco antigo (se quiser preservar dados);
- Dropar e recriar o banco `vibez_vibeeez` (ou usar outro nome);
- Importar o novo `.sql`.

---

## 4. Arquivo `.env`

O projeto usa um carregador de `.env` simples em `includes/load_env.php` e lê as variáveis via `$_ENV`.

Crie um arquivo chamado **`.env`** na raiz do projeto (`Vibez/.env`) com o conteúdo abaixo, ajustando os valores:

```dotenv
# =========================
# BANCO DE DADOS (MySQL)
# =========================
DB_HOST=localhost
DB_USER=root
DB_PASS=SUASENHA_AQUI
DB_NAME=vibez_vibeeez

# URL base do site (ajuste conforme seu ambiente)
# Exemplo XAMPP/localhost:
SITE_URL=http://localhost/Vibez

# =========================
# SMTP / PHPMailer
# =========================
# Exemplo com Gmail (recomendado usar senha de app)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu_email@gmail.com
MAIL_PASSWORD=sua_senha_de_app_aqui
MAIL_FROM=seu_email@gmail.com
MAIL_FROM_NAME="Vibez"
MAIL_SECURE=tls
```

### 4.1. Observações sobre SMTP (Gmail)

- Ative 2FA na conta Gmail;
- Crie uma **senha de app** e use em `MAIL_PASSWORD`;
- `MAIL_SECURE` normalmente é `tls` na porta `587`.

Se usar outro provedor (Outlook, SMTP corporativo), altere `MAIL_HOST`, `MAIL_PORT` e `MAIL_SECURE` conforme a documentação do provedor.

---

## 5. Configurando o Servidor Web

Como o foco é XAMPP ou um servidor PHP já configurado, você basicamente precisa:

### 5.1. XAMPP (Windows)

1. Copie a pasta do projeto para:
   ```text
   C:\xampp\htdocs\Vibez
   ```
2. Certifique-se de que o Apache e o MySQL estão iniciados no XAMPP;
3. Acesse:
   ```text
   http://localhost/Vibez
   ```
4. Ajuste o `SITE_URL` no `.env` para `http://localhost/Vibez`.

### 5.2. Servidor PHP genérico (Linux / outro)

1. Coloque o projeto na pasta pública do seu servidor (ex.: `/var/www/html/Vibez`);
2. Configure o VirtualHost/Host de acordo com o seu provedor ou painel de hospedagem;
3. Aponte o domínio/subdomínio para a pasta `Vibez`;
4. Ajuste `SITE_URL` no `.env` para a URL real (ex.: `https://meusite.com`).

Não é necessário nenhum script especial além do que seu servidor de hospedagem já oferece normalmente.

---

## 6. Pastas de Upload e Permissões

Certifique-se de que as pastas de upload existem e são graváveis pelo servidor web.

### 6.1. Estrutura esperada

```text
Vibez/
├─ uploads/
│  ├─ (fotos de perfil, banners, imagens de post)
│  └─ chat/
│     └─ (imagens, áudios, vídeos enviados no chat)
```

Se a pasta `uploads/chat` não existir, crie manualmente.

### 6.2. Permissões (Linux)

Se você estiver em um servidor Linux e tiver problema de upload, ajuste as permissões:

```bash
cd /var/www/html/Vibez

mkdir -p uploads/chat

sudo chown -R www-data:www-data uploads
sudo chmod -R 775 uploads
```

> Ajuste `www-data` para o usuário do seu servidor (ex.: `apache` no CentOS).  
> Em XAMPP no Windows, normalmente não é necessário mexer em permissões.

---

## 7. Fluxo Básico de Uso

### 7.1. Cadastro e verificação de e-mail

1. Acesse `SITE_URL/register.php` (por ex. `http://localhost/Vibez/register.php`);
2. Preencha os dados e envie;
3. O sistema irá:
   - Inserir o usuário na tabela `users` com token de verificação;
   - Enviar um e-mail com link de verificação (usando os dados do `.env`);
4. Clique no link recebido por e-mail;
5. O sistema libera o login para a conta verificada.

Se aparecer erro de coluna (por exemplo `Unknown column 'verification_token'`), significa que o banco importado não é o `vibez_vibeeez.sql` correto. Verifique se você importou o **arquivo certo**.

### 7.2. Login

1. Acesse `SITE_URL/login.php`;
2. Informe e-mail/usuário e senha;
3. Em caso de 2FA ativado (para aquela conta), o fluxo pode exigir código TOTP (Google Authenticator).

### 7.3. Perfil e fotos

- A foto de perfil usa a coluna `profile_pic` da tabela `users` e arquivos em `/uploads`;
- O banner de perfil usa a coluna `banner` e arquivos em `/uploads`;
- Se o arquivo não existir ou a coluna estiver vazia, o sistema usa imagens padrão.

### 7.4. Chat (texto, imagem, áudio, vídeo)

- Página principal do chat: `SITE_URL/chat/index.php`.

Funcionalidades:

- Lista de conversas à esquerda (usuários com quem já houve mensagens);
- Busca de usuários via `/api/search_users.php`;
- Área principal mostra mensagens com:
  - **Nome + avatar** do usuário;
  - Bolhas diferenciadas para mensagens enviadas/recebidas;
  - Mídia incorporada (imagem, áudio, vídeo);
- Envio pelo formulário inferior:
  - Campo de texto;
  - Botão 📎 (abre seletor de arquivo);
  - Botão **Enviar**.

Endpoints usados:

- `chat/send_message.php` – recebe `receiver_id`, `message` e (opcional) `attachment` via `FormData`;
- `chat/get_messages.php?user_id=ID` – retorna JSON com histórico da conversa;
- `api/search_users.php?q=texto` – retorna usuários para a busca.

> Todas as colunas necessárias para o chat (como `message_type` e `file_path`) já estão definidas no `vibez_vibeeez.sql`.

---

## 8. Erros Comuns e Soluções

### 8.1. HTTP 500 sem mensagem clara

Ative o display de erros (em ambiente de desenvolvimento) no topo de um arquivo PHP onde o erro ocorre, por exemplo em `index.php`:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

Recarregue a página e veja a mensagem exata.

### 8.2. `SQLSTATE[42S22]: Column not found: 1054 Unknown column ...`

Causa típica:

- O banco foi criado manualmente ou está desatualizado;
- O `vibez_vibeeez.sql` não foi importado corretamente.

Solução:

1. Garantir que o banco se chama `vibez_vibeeez` (ou que `DB_NAME` no `.env` aponta pro banco que você importou);
2. Apagar o banco atual (se não tiver dados importantes);
3. Criar de novo `vibez_vibeeez`;
4. Importar **novamente** o `vibez_vibeeez.sql` do projeto Vibez.

### 8.3. Erro de chave estrangeira ao inserir `posts` (`#1452 - Cannot add or update a child row...`)

Exemplo:

```text
#1452 - Cannot add or update a child row:
a foreign key constraint fails (`vibez_vibeeez`.`posts`, CONSTRAINT `posts_ibfk_1`
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE)
```

Causa: está tentando criar um `post` com `user_id` que não existe em `users` (ou você importou só parte do `.sql`).

Solução:

- Verificar se o usuário existe no banco:
  ```sql
  SELECT * FROM users WHERE id = 1;
  ```
- Se estiver apenas testando, crie um usuário manualmente ou cadastre via interface e logue com ele.

### 8.4. E-mail de verificação não chega

- Testar conexão SMTP (host, porta, usuário e senha);
- Conferir se `MAIL_USERNAME` e `MAIL_PASSWORD` estão corretos;
- No Gmail, usar **senha de app**, não a senha normal;
- Verificar pasta de SPAM.

### 8.5. Uploads de imagem/vídeo não funcionam

- Verifique se as pastas `uploads/` e `uploads/chat/` existem;
- Verifique permissões (especialmente em Linux);
- Veja se o PHP não está barrando por tamanho (`upload_max_filesize` e `post_max_size` no `php.ini`).

---

## 9. Checklist Rápido

1. [ ] PHP 8+ instalado e funcionando;
2. [ ] MySQL/MariaDB instalado;
3. [ ] Banco `vibez_vibeeez` criado;
4. [ ] `vibez_vibeeez.sql` importado sem erros;
5. [ ] `.env` criado com `DB_*`, `SITE_URL` e `MAIL_*` corretos;
6. [ ] Pastas `uploads/` e `uploads/chat/` criadas (e graváveis se necessário);
7. [ ] Acesso à home/login funciona;
8. [ ] Cadastro cria usuário sem erro de coluna;
9. [ ] Verificação de e-mail funcionando;
10. [ ] Perfil carrega sem erro de coluna;
11. [ ] Chat envia mensagens de texto e mídia sem erro.

Se todos os itens acima estiverem ok, o sistema **Vibez** deve estar rodando de forma completa e estável.

## 10. Novos Updates

1. [ ] Criptografia de Ponta a Ponta
2. [ ] Correção dos Bugs já existentes

Aceitamos sugestões de novos updates, é só mandar um email para ```imaidenxx@tutamail.com```
