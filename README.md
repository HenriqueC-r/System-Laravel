<div align="center">

# 🔐 Login System API — Laravel + JWT

**API REST de autenticação desenvolvida em Laravel para o front-end Quasar/Vue.js criado por [Mirian](https://github.com/mirianrl).**

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![JWT](https://img.shields.io/badge/Auth-JWT-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)](https://jwt.io/)
[![Tests](https://img.shields.io/badge/Tests-PHPUnit-3C9CD7?style=for-the-badge&logo=php&logoColor=white)](https://phpunit.de/)

[Front-end Quasar](https://github.com/mirianrl/Quasar-Login-Vue.js) · [Back-end em PHP puro](https://github.com/HenriqueC-r/Login-System-PHP) · [Endpoints](#-endpoints-da-api) · [Instalação](#-como-executar)

</div>

---

## 📖 Sobre o projeto

Este repositório contém o **back-end em Laravel** do projeto de login desenvolvido para consumir o front-end **[Quasar-Login-Vue.js](https://github.com/mirianrl/Quasar-Login-Vue.js)**, criado pela **Mirian**.

A API mantém o formato de rotas e respostas esperado pelo front-end e oferece cadastro, login, consulta de perfil e listagem de usuários. A autenticação das rotas privadas é feita com **JSON Web Token (JWT)**.

O mesmo back-end também está disponível em uma implementação feita com **PHP puro, sem Laravel**:

> 🐘 **[Login-System-PHP — versão em PHP puro](https://github.com/HenriqueC-r/Login-System-PHP)**

### Repositórios do ecossistema

| Projeto | Tecnologia | Repositório |
| --- | --- | --- |
| Front-end | Quasar + Vue.js | [mirianrl/Quasar-Login-Vue.js](https://github.com/mirianrl/Quasar-Login-Vue.js) |
| Back-end (este projeto) | Laravel + JWT | [HenriqueC-r/System-Laravel](https://github.com/HenriqueC-r/System-Laravel) |
| Back-end alternativo | PHP puro | [HenriqueC-r/Login-System-PHP](https://github.com/HenriqueC-r/Login-System-PHP) |

## ✨ Funcionalidades

- Cadastro de usuários com validação dos dados
- Login com e-mail e senha
- Senhas armazenadas com hash seguro
- Emissão e validação de tokens JWT
- Consulta dos dados do usuário autenticado
- Listagem de usuários cadastrados
- Respostas JSON e mensagens de erro em português
- Testes automatizados do fluxo de autenticação
- Compatibilidade com SQLite e MySQL

## 🛠️ Tecnologias

- [PHP 8.3+](https://www.php.net/)
- [Laravel 13](https://laravel.com/)
- [Firebase PHP-JWT](https://github.com/firebase/php-jwt)
- SQLite ou MySQL
- PHPUnit
- Laravel Sail / Docker (opcional)

## 🚀 Como executar

### Pré-requisitos

- PHP 8.3 ou superior
- Composer
- Extensões PHP exigidas pelo Laravel
- SQLite (configuração padrão) ou MySQL

### Instalação local

```bash
git clone https://github.com/HenriqueC-r/System-Laravel.git
cd System-Laravel

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

A API estará disponível em:

```text
http://localhost:8000/api
```

> A configuração padrão usa SQLite. Para usar MySQL, altere as variáveis `DB_*` no arquivo `.env` antes de executar as migrations.

### Configuração do JWT

Por padrão, a aplicação usa a `APP_KEY` para assinar os tokens. Em produção, defina um segredo exclusivo, longo e aleatório no `.env`:

```dotenv
JWT_SECRET=coloque-aqui-um-segredo-longo-e-aleatorio
JWT_TTL=60
```

`JWT_TTL` representa o tempo de validade do token em **minutos**.

<details>
<summary><strong>Executar com Laravel Sail / Docker</strong></summary>

Depois de instalar as dependências e configurar o `.env` para MySQL:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

Para encerrar os containers:

```bash
./vendor/bin/sail down
```

</details>

## 🔌 Endpoints da API

| Método | Endpoint | Descrição | Autenticação |
| :---: | --- | --- | :---: |
| `POST` | `/api/cadastro.php` | Cadastra um usuário | Não |
| `POST` | `/api/login.php` | Autentica e retorna um JWT | Não |
| `GET` | `/api/perfil.php` | Retorna o perfil autenticado | Bearer JWT |
| `GET` | `/api/usuarios.php` | Lista os usuários | Bearer JWT |

### Cadastro

```http
POST /api/cadastro.php
Content-Type: application/json
```

```json
{
  "nome": "Maria Silva",
  "email": "maria@example.com",
  "senha": "segredo123"
}
```

Exemplo de resposta (`201 Created`):

```json
{
  "status": true,
  "mensagem": "Cadastro realizado com sucesso!",
  "usuario": {
    "id": 1,
    "nome": "Maria Silva",
    "email": "maria@example.com"
  }
}
```

### Login

```http
POST /api/login.php
Content-Type: application/json
```

```json
{
  "email": "maria@example.com",
  "senha": "segredo123"
}
```

O login retorna o token, seu tipo, o tempo de expiração em segundos e os dados do usuário:

```json
{
  "status": true,
  "mensagem": "Login realizado com sucesso!",
  "token": "SEU_TOKEN_JWT",
  "tipo_token": "Bearer",
  "expira_em": 3600,
  "usuario": {
    "id": 1,
    "nome": "Maria Silva",
    "email": "maria@example.com"
  }
}
```

### Rotas protegidas

Envie o token recebido no login no cabeçalho `Authorization`:

```http
Authorization: Bearer SEU_TOKEN_JWT
```

Exemplo com cURL:

```bash
curl http://localhost:8000/api/perfil.php \
  -H "Authorization: Bearer SEU_TOKEN_JWT" \
  -H "Accept: application/json"
```

## 🖥️ Integração com o front-end

Clone o projeto da Mirian e configure a URL base usada pelo Axios:

```bash
git clone https://github.com/mirianrl/Quasar-Login-Vue.js.git
```

No arquivo `src/services/api.js` do front-end, utilize:

```js
baseURL: 'http://localhost:8000/api'
```

Caso a página de cadastro ainda utilize uma URL fixa, em `src/pages/Cadastro.vue` a requisição pode apontar diretamente para:

```js
await axios.post('http://localhost:8000/api/cadastro.php', dados)
```

## 🧪 Testes

Execute a suíte automatizada com:

```bash
php artisan test
```

Os testes cobrem cadastro, login, acesso autenticado ao perfil e à listagem de usuários, além da rejeição de tokens ausentes ou inválidos.

## 📁 Estrutura principal

```text
app/
├── Http/
│   ├── Controllers/AuthController.php
│   └── Middleware/AuthenticateJwt.php
├── Models/User.php
└── Services/JwtService.php
routes/
└── api.php
tests/
└── Feature/JwtAuthenticationTest.php
```

## 🤝 Créditos

- **Front-end:** [Mirian](https://github.com/mirianrl) — [Quasar-Login-Vue.js](https://github.com/mirianrl/Quasar-Login-Vue.js)
- **Back-end Laravel e versão em PHP puro:** [HenriqueC-r](https://github.com/HenriqueC-r)

---

<div align="center">

Feito com PHP, Laravel e muita vontade de aprender. 🚀

</div>
