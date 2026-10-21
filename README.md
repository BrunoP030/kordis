# Kordis — Sistema Operacional Hospitalar

Monorepo: **Laravel 11 (API/webservice)** + **PostgreSQL** + **Vue 3 + Syncfusion EJ2**, com tempo real via **Laravel Reverb / Echo** e padrão **MVP**.

```
kordis/
├── backend/     → Laravel: TODA a lógica de negócio (webservice puro, só JSON)
├── frontend/    → Vue 3 + EJ2: só interface, consome o backend via /api
└── README.md
```

## Módulos

| Módulo | Rota | Permissão | O que faz |
|---|---|---|---|
| Dashboard | `/dashboard` | `dashboard.visualizar` | Kanban de tarefas de enfermagem (A fazer / Em andamento / Concluído), com categorias e prioridade. Atualiza em tempo real. |
| Centro Cirúrgico | `/centro-cirurgico` | `centro-cirurgico.visualizar` | Agenda e painel das salas cirúrgicas: agendar, editar, iniciar, finalizar, liberar sala e cancelar cirurgias. Tempo real. |
| Leitos | `/leitos` | `leitos.visualizar` | Painel de leitos (ocupado / limpeza / disponível) e troca de status. |
| Prontuários | `/prontuarios` | `prontuarios.visualizar` | Busca de pacientes e histórico clínico em linha do tempo, com filtro por tipo. O botão **Adicionar Prontuário** (permissão `prontuarios.criar`) registra evolução, prescrição, exame, procedimento ou alta. |

> O módulo de Chat Interno foi removido do escopo e não será implementado por enquanto.

### Tempo real

Eventos transmitidos por broadcasting (Reverb) e consumidos no frontend por `services/echo.js`:

- `NursingTaskUpdated` → canais `tarefas` e `tarefas.higienizacao`
- `SurgicalBookingUpdated` → canal `centro-cirurgico`

A autorização dos canais está em `backend/routes/channels.php`. O perfil `limpeza` só recebe os cards de higienização.

### Perfis e permissões

Definidos em `backend/database/seeders/RolesAndPermissionsSeeder.php` (Spatie Permission):

| Perfil | Acesso |
|---|---|
| Administrador | Todas as permissões |
| medico | Dashboard, prontuários (ver/criar/editar), centro cirúrgico completo |
| enfermeiro | Dashboard, criar tarefas, leitos completo, centro cirúrgico (leitura), prontuários (leitura) |
| limpeza | Dashboard (só higienização) e leitos (leitura) |

O frontend esconde itens de menu e bloqueia rotas conforme as permissões (`stores/auth.js`, `meta.permissao` no router).

## API (resumo)

Autenticação via Laravel Sanctum. Todas as rotas abaixo exigem `auth:sanctum` e a permissão indicada em `backend/routes/api.php`.

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/login`, `/api/logout` · GET `/api/me` | Sessão e usuário logado |
| GET/POST/PUT/DELETE | `/api/patients[/{id}]` | CRUD de pacientes |
| GET | `/api/clinical-records/patients` | Pacientes para o módulo de prontuários (com busca) |
| GET/POST | `/api/patients/{id}/clinical-records` | Histórico e criação de registros clínicos |
| GET/POST/PUT | `/api/surgical-bookings[/{id}]` | Agenda cirúrgica |
| POST | `/api/surgical-bookings/{id}/iniciar \| finalizar \| liberar-sala \| cancelar` | Ciclo da cirurgia |
| GET/POST/PUT | `/api/surgical-rooms[/{id}]` | Salas cirúrgicas |
| GET · PUT | `/api/beds` · `/api/beds/{id}/status` | Leitos |
| GET/POST · PUT | `/api/nursing-tasks` · `/api/nursing-tasks/{id}/status` | Tarefas de enfermagem |

## Arquitetura (MVP)

**Backend** — `Request` (validação) → `Controller` (fino) → `Service` (regra de negócio) → `Model` → `Presenter` (formata o JSON de saída). Eventos em `app/Events`.

**Frontend** — `View` (`.vue`, só renderização) → `Presenter` (composable `use*Presenter.js`, estado e ações da tela) → `Service` (`services/*.js`, chamadas HTTP via axios).

```
backend/app/{Http/Controllers/Api, Http/Requests, Services, Presenters, Models, Events}
frontend/src/{views, presenters, services, stores, components/layout, config, router}
```

## Dados de exemplo (seeders)

`php artisan migrate --seed` cria:

- Usuário administrador `admin@kordis.com` / `senha-forte-123`
- Perfis e permissões
- Tarefas de enfermagem (`NursingTaskSeeder`)
- Leitos (`BedSeeder`)
- 5 pacientes fictícios, cada um com um registro clínico de exemplo (`PatientSeeder`)

Os seeders são idempotentes (`updateOrCreate` / `firstOrCreate`) e podem ser rodados individualmente, ex.: `php artisan db:seed --class=PatientSeeder`.

## Setup local

### Pré-requisitos
PHP 8.2+, Composer, Node 18+, PostgreSQL.

### Banco
```sql
CREATE DATABASE kordis_db;
```

### Backend
```bash
cd backend
composer install
cp .env.example .env     # ajuste as credenciais do banco e as chaves REVERB_*
php artisan key:generate
php artisan migrate --seed
php artisan serve        # http://localhost:8000
```

Em terminais separados, para o tempo real e as filas (`QUEUE_CONNECTION=database`):
```bash
php artisan reverb:start   # WebSocket em localhost:8080
php artisan queue:work
```

### Frontend
```bash
cd frontend
npm install
cp .env.example .env
# preencha VITE_SYNCFUSION_LICENSE_KEY (licença community ou comercial)
# e VITE_REVERB_APP_KEY (mesmo valor de REVERB_APP_KEY do backend)
npm run dev              # http://localhost:5173
```

## Marca e interface

| Token CSS | Cor | Uso |
|---|---|---|
| `--kordis-navy-900` | `#14245B` | títulos principais, tinta da logo |
| `--kordis-navy-800` | `#1E3A8A` | topbar, botões primários |
| `--kordis-teal-500` | `#2BA3B9` | destaque (símbolo K), avatar |
| `--kordis-bg-sidebar` | `#F1F5F9` | fundo do menu lateral |
| `--kordis-danger` / `--kordis-danger-text` | `#F6627B` / `#D93A54` | alertas |

Definidos em `frontend/src/style.css`. **Sempre use as variáveis, nunca hex direto** em componentes novos.

- Assets da marca em `frontend/src/assets/brand/` (`kordis-logo.png`, `kordis-symbol.png`).
- Ícones: [Material Icons](https://fonts.google.com/icons?icon.set=Material+Icons), carregados via `<link>` no `index.html`. Uso: `<span class="material-icons">dashboard</span>`.
- Layout: `components/layout/AppSidebar.vue` (menu retrátil, estado em `stores/ui.js`), `AppTopbar.vue` e `AppShell.vue`.

## ⭐ Como adicionar um novo módulo/menu

1. **View:** `frontend/src/views/<modulo>/<Nome>Painel.vue`
2. **Rota:** em `frontend/src/router/index.js`, com `meta: { permissao: '<modulo>.visualizar' }`
3. **Permissão:** em `RolesAndPermissionsSeeder.php`, inclua no array `$permissoes` e nos perfis que devem acessar; rode o seeder.
4. **Menu:** em `frontend/src/config/navigation.js` (`label`, `icon`, `routeName`, `permission`). A sidebar mostra o item só para quem tem a permissão.

Se houver backend: `Migration` → `Model` → `Request` → `Service` → `Presenter` → `Controller` → rota em `routes/api.php` com `->middleware('permission:...')`. No frontend: `Service` + `Presenter` (composable) + View.

## Próximos passos

- Edição de registros clínicos (permissão `prontuarios.editar` já existe, sem endpoint).
- Cadastro de pacientes pela interface (a API `/api/patients` já existe).
- Vincular leitos a pacientes cadastrados (hoje o leito guarda só o nome).
- Testes automatizados de backend e frontend.
- Chat Interno: fora do escopo atual.
