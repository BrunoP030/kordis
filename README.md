# Kordis — Sistema Operacional Hospitalar

Monorepo: **Laravel 11 (API/webservice)** + **PostgreSQL** + **Vue 3 + Syncfusion EJ2** + padrão **MVP**.

```
kordis/
├── backend/     → Laravel: TODA a lógica de negócio (webservice puro, só JSON)
├── frontend/    → Vue 3 + EJ2: só interface, consome o backend via /api
└── README.md
```

## Marca

| Token CSS | Cor | Uso |
|---|---|---|
| `--kordis-navy-900` | `#14245B` | títulos principais, tinta da logo |
| `--kordis-navy-800` | `#1E3A8A` | topbar, botões primários |
| `--kordis-teal-500` | `#2BA3B9` | destaque (símbolo K), avatar |
| `--kordis-bg-sidebar` | `#F1F5F9` | fundo do menu lateral |
| `--kordis-danger` / `--kordis-danger-text` | `#F6627B` / `#D93A54` | alertas (ex: cartão "IA S.A.I.R.") |

Definidos em `frontend/src/style.css`. **Sempre use as variáveis, nunca hex direto** em componentes novos — se a marca mudar de tom, você troca em um lugar só.

Assets em `frontend/src/assets/brand/`:
- `kordis-logo.png` — logo completa (usada no login e na sidebar expandida)
- `kordis-symbol.png` — só o símbolo "K" (usado como favicon em `frontend/public/favicon.png`)

**Ícones**: [Material Icons do Google](https://fonts.google.com/icons?icon.set=Material+Icons), carregados via `<link>` no `index.html` (gratuito, sem instalação de pacote). Uso: `<span class="material-icons">dashboard</span>` — o nome do ícone é o mesmo que aparece na busca do site do Google Fonts.

## Layout (Sidebar + Topbar)

Réplica do Figma:
- `components/layout/AppSidebar.vue` — menu retrátil (recolhe pra ícone-only ao clicar no ☰). Estado guardado em `stores/ui.js` (persiste no `localStorage` do navegador do usuário).
- `components/layout/AppTopbar.vue` — barra navy com título do sistema + avatar/perfil do usuário logado.
- `components/layout/AppShell.vue` — junta os dois + área de conteúdo (`<router-view>`).

## ⭐ Como adicionar um novo item de menu

Este é o fluxo padrão — sempre nessa ordem, 4 passos:

**1. Crie a view (a "tela vazia")**
```
frontend/src/views/<nome-do-modulo>/<NomeDaTela>.vue
```
Exemplo, um módulo "Farmácia":
```
frontend/src/views/farmacia/FarmaciaPainel.vue
```

**2. Registre a rota**
Em `frontend/src/router/index.js`, adicione um objeto no array `routes`:
```js
{
  path: '/farmacia',
  name: 'farmacia.painel',
  component: () => import('../views/farmacia/FarmaciaPainel.vue'),
  meta: { permissao: 'farmacia.visualizar' },
},
```

**3. Adicione a permissão no backend**
Em `backend/database/seeders/RolePermissionSeeder.php`, inclua `'farmacia.visualizar'` no array `$permissoes` e atribua aos perfis que devem enxergar esse menu. Rode `php artisan migrate:fresh --seed` (ou crie um seeder incremental) pra aplicar.

**4. Adicione a entrada no menu**
Em `frontend/src/config/navigation.js`, adicione:
```js
{
  label: 'Farmácia',
  icon: 'medication',       // nome de um ícone em fonts.google.com/icons
  routeName: 'farmacia.painel',   // igual ao `name` da rota do passo 2
  permission: 'farmacia.visualizar', // igual à permissão do passo 3
},
```

Pronto — o item aparece na sidebar automaticamente **só para os perfis que têm a permissão**, sem tocar em `AppSidebar.vue`. Se o módulo tiver lógica de backend (endpoints próprios), siga o padrão do resto do sistema: `Model` → `Service` → `Presenter` → `Controller` → rota em `backend/routes/api.php` com `->middleware('permission:farmacia.visualizar')`.

## Setup local

### Banco
```sql
CREATE DATABASE kordis_db;
```

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```
Login padrão: `admin@kordis.com` / `senha-forte-123`

### Frontend
```bash
cd frontend
npm install
cp .env.example .env
# preencha VITE_SYNCFUSION_LICENSE_KEY (licença community ou comercial)
npm run dev   # http://localhost:5173
```

## Próximos passos
1. Rodar o setup acima e conferir a sidebar retraindo/expandindo com dados reais de perfil.
2. Implementar o conteúdo de cada tela placeholder (Centro Cirúrgico, Leitos, Chat Interno, Prontuários), seguindo o padrão MVP já usado no módulo de exemplo (`backend/app/Services/PatientService.php` + `Presenters/PatientPresenter.php`, que pode virar a base de "Prontuários").
3. Avaliar se "Prontuários" reaproveita o módulo de Pacientes já existente no backend ou se é uma entidade própria — a estrutura já suporta os dois casos.
