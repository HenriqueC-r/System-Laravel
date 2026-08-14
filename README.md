# API de autenticação Laravel com JWT

Back-end compatível com o front-end [Quasar-Login-Vue.js](https://github.com/mirianrl/Quasar-Login-Vue.js). A API permite cadastrar e autenticar usuários, consultar o perfil autenticado e listar usuários.

## Requisitos

- PHP 8.3+
- Composer
- MySQL 8+ (ou Laravel Sail/Docker)

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Configure a conexão com o banco no `.env`. Por padrão, o JWT usa `APP_KEY` para assinar os tokens. Em produção, é possível definir um segredo separado e forte:

```dotenv
JWT_SECRET=adicione-aqui-um-segredo-longo-e-aleatorio
JWT_TTL=60
```

`JWT_TTL` é o tempo de validade do token em minutos.

## Endpoints

| Método | Endpoint | Autenticação |
| --- | --- | --- |
| `POST` | `/api/cadastro.php` | Pública |
| `POST` | `/api/login.php` | Pública |
| `GET` | `/api/perfil.php` | Bearer JWT |
| `GET` | `/api/usuarios.php` | Bearer JWT |

Cadastro:

```json
{
  "nome": "Maria Silva",
  "email": "maria@example.com",
  "senha": "segredo123"
}
```

Login:

```json
{
  "email": "maria@example.com",
  "senha": "segredo123"
}
```

Nas rotas protegidas, envie o token retornado pelo login:

```http
Authorization: Bearer SEU_TOKEN_JWT
```

## Configuração do front-end

No arquivo `src/services/api.js` do projeto Quasar, use:

```js
baseURL: 'http://localhost:8000/api'
```

Em `src/pages/Cadastro.vue`, substitua a URL fixa do cadastro por uma chamada à mesma instância da API ou use diretamente:

```js
await axios.post('http://localhost:8000/api/cadastro.php', dados)
```

## Testes

```bash
php artisan test
```
