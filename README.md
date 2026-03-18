# API de Viagens Corporativas

Microserviço RESTful para gerenciamento de pedidos de viagem corporativa, construído com **Laravel 13** e autenticação **JWT**.

## Stack

- PHP 8.3
- Laravel 13.x
- MySQL 8.0
- JWT Auth (`php-open-source-saver/jwt-auth`)
- Docker (PHP-FPM + Nginx + MySQL + phpMyAdmin)
- PHPUnit

## Arquitetura

```
app/
├── Enums/              # TravelOrderStatus (requested, approved, cancelled)
├── Http/
│   ├── Controllers/    # AuthController, TravelOrderController
│   ├── Requests/       # StoreTravelOrderRequest, UpdateTravelOrderStatusRequest, ListTravelOrdersRequest
│   └── Resources/      # TravelOrderResource
├── Models/             # User, TravelOrder
├── Notifications/      # TravelOrderStatusChanged
├── Policies/           # TravelOrderPolicy
├── Repositories/
│   ├── Contracts/      # TravelOrderRepositoryInterface
│   └── Eloquent/       # TravelOrderRepository
├── Services/           # TravelOrderService
└── Providers/          # RepositoryServiceProvider
```

O projeto segue o padrão **Controller → Service → Repository**, com:

- **Controllers** finos que delegam lógica para a camada de serviço
- **Services** com regras de negócio e disparo de notificações
- **Repositories** abstraídos por interface para desacoplar o Eloquent
- **Policies** para controle de autorização
- **Form Requests** para validação de entrada

## Instalação com Docker

### Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) e [Docker Compose](https://docs.docker.com/compose/install/)

### Setup

```bash
# 1. Clonar o repositório
git clone <url-do-repositorio>
cd API-Viagens-Laravel

# 2. Copiar variáveis de ambiente
cp .env.example .env

# 3. Subir os containers
docker compose up -d

# 4. Instalar dependências (caso necessário)
docker compose exec app composer install

# 5. Gerar chave da aplicação
docker compose exec app php artisan key:generate

# 6. Gerar secret do JWT
docker compose exec app php artisan jwt:secret

# 7. Executar migrations
docker compose exec app php artisan migrate

# 8. (Opcional) Executar seeders
docker compose exec app php artisan db:seed
```

A API estará disponível em `http://localhost:8000`.

### Serviços Docker

| Serviço    | Container              | Porta  | Descrição            |
|------------|------------------------|--------|----------------------|
| app        | api-viagens-app        | 9000   | PHP-FPM              |
| nginx      | api-viagens-nginx      | 8000   | Servidor web         |
| mysql      | api-viagens-mysql      | 3306   | Banco de dados       |
| phpmyadmin | api-viagens-phpmyadmin | 8080   | Interface do MySQL   |

## Instalação Local (sem Docker)

```bash
# Pré-requisitos: PHP 8.3, Composer, MySQL 8.0

# 1. Instalar dependências
composer install

# 2. Copiar e configurar variáveis de ambiente
cp .env.example .env
# Editar .env com credenciais do MySQL local (DB_HOST=127.0.0.1)

# 3. Gerar chaves
php artisan key:generate
php artisan jwt:secret

# 4. Executar migrations
php artisan migrate

# 5. Iniciar servidor de desenvolvimento
php artisan serve
```

## Variáveis de Ambiente

| Variável         | Descrição                      | Padrão             |
|------------------|--------------------------------|---------------------|
| `APP_PORT`       | Porta do Nginx                 | `8000`              |
| `DB_CONNECTION`  | Driver de banco                | `mysql`             |
| `DB_HOST`        | Host do MySQL                  | `mysql` (Docker)    |
| `DB_PORT`        | Porta do MySQL                 | `3306`              |
| `DB_DATABASE`    | Nome do banco                  | `api_viagens`       |
| `DB_USERNAME`    | Usuário do banco               | `api_viagens_user`  |
| `DB_PASSWORD`    | Senha do banco                 | `password123`       |
| `JWT_SECRET`     | Chave secreta JWT              | (gerada automaticamente) |
| `MAIL_MAILER`    | Driver de envio de e-mail      | `log` (em dev, grava em `storage/logs`) |

## Endpoints da API

Todos os endpoints possuem prefixo `/api`. Endpoints protegidos requerem header `Authorization: Bearer {token}`.

### Autenticação

| Método | Rota              | Descrição                | Auth  |
|--------|--------------------|--------------------------|-------|
| POST   | `/api/auth/register` | Registrar novo usuário  | Não   |
| POST   | `/api/auth/login`    | Login (obter token JWT) | Não   |
| GET    | `/api/auth/me`       | Dados do usuário autenticado | Sim |
| POST   | `/api/auth/refresh`  | Renovar token JWT       | Sim   |
| POST   | `/api/auth/logout`   | Invalidar token (logout) | Sim  |

#### POST `/api/auth/register`

```json
{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha1234",
    "password_confirmation": "senha1234"
}
```

#### POST `/api/auth/login`

```json
{
    "email": "joao@example.com",
    "password": "senha1234"
}
```

**Resposta (200):**

```json
{
    "user": { "id": 1, "name": "João Silva", "email": "joao@example.com" },
    "authorization": { "token": "eyJ0eXAi...", "type": "bearer" }
}
```

### Pedidos de Viagem

| Método | Rota                                | Descrição                 | Auth |
|--------|--------------------------------------|---------------------------|------|
| GET    | `/api/travel-orders`                | Listar pedidos (paginado) | Sim  |
| POST   | `/api/travel-orders`                | Criar pedido              | Sim  |
| GET    | `/api/travel-orders/{id}`           | Consultar pedido por ID   | Sim  |
| PATCH  | `/api/travel-orders/{id}/status`    | Atualizar status          | Sim  |

#### POST `/api/travel-orders`

```json
{
    "destination": "São Paulo - SP",
    "departure_date": "2026-04-15",
    "return_date": "2026-04-20"
}
```

**Resposta (201):**

```json
{
    "data": {
        "id": 1,
        "user_id": 1,
        "destination": "São Paulo - SP",
        "departure_date": "2026-04-15",
        "return_date": "2026-04-20",
        "status": "requested",
        "created_at": "2026-03-18T10:00:00+00:00",
        "updated_at": "2026-03-18T10:00:00+00:00"
    }
}
```

#### GET `/api/travel-orders` — Filtros

| Query Param       | Tipo   | Descrição                          |
|--------------------|--------|------------------------------------|
| `status`          | string | `requested`, `approved`, `cancelled` |
| `destination`     | string | Busca parcial por destino          |
| `departure_from`  | date   | Data de ida a partir de            |
| `departure_until` | date   | Data de ida até                    |
| `return_from`     | date   | Data de retorno a partir de        |
| `return_until`    | date   | Data de retorno até                |
| `per_page`        | int    | Itens por página (1–100)           |

Exemplo: `GET /api/travel-orders?status=approved&departure_from=2026-04-01&per_page=10`

#### PATCH `/api/travel-orders/{id}/status`

```json
{
    "status": "approved"
}
```

Valores aceitos: `approved`, `cancelled`.

## Regras de Negócio

1. **Criação** — pedido é criado sempre com status `requested`, vinculado ao usuário autenticado
2. **Atualização de status** — apenas `approved` ou `cancelled`; o dono do pedido **não pode** alterar o próprio status (403)
3. **Cancelamento pós-aprovação** — permitido, porém não é possível cancelar se a data de ida já passou (409)
4. **Acesso** — cada usuário só visualiza seus próprios pedidos (Policy + filtro no Repository)
5. **Notificação** — disparo automático de notificação (e-mail) quando o status muda, enviada ao e-mail cadastrado do usuário dono do pedido

## Testes

```bash
# Com Docker
docker compose exec app php artisan test

# Local
php artisan test

# Apenas testes unitários
php artisan test --testsuite=Unit

# Apenas testes de feature
php artisan test --testsuite=Feature
```

### Estrutura de Testes

```
tests/
├── Unit/
│   ├── Services/
│   │   └── TravelOrderServiceTest.php    # Regras de negócio (com mocks)
│   └── Policies/
│       └── TravelOrderPolicyTest.php     # Autorização
└── Feature/
    ├── Auth/
    │   └── AuthTest.php                  # Endpoints de autenticação
    └── TravelOrder/
        └── TravelOrderEndpointTest.php   # Fluxo completo dos endpoints
```

## Collection do Postman

Uma collection pronta para testes está disponível em `docs/postman/API-Viagens.postman_collection.json`.

### Como importar

1. Abra o Postman
2. Clique em **Import** (ou `Ctrl+O`)
3. Selecione o arquivo `docs/postman/API-Viagens.postman_collection.json`
4. A collection será importada com a variável `base_url` pré-configurada para `http://localhost:8000/api`

### Uso

1. Execute o request **Register** ou **Login** — o token JWT é salvo automaticamente na variável `{{token}}`
2. Todos os demais requests autenticados utilizam o token salvo
3. Os filtros de listagem estão pré-configurados como query params desabilitados; habilite os que desejar
