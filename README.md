# Midnight Market — built on the Phantom PHP framework

IPT10 Final Project: a custom PHP MVC framework (**Phantom**) written from scratch, and an
e-commerce application (**Midnight Market**) built on top of it, with an agentic AI shopping
assistant (**Rook**, powered by NVIDIA Nemotron 3 Ultra through the NVIDIA NIM API) and live currency conversion (**Frankfurter API**).
The UI uses a bold red/black/white "phantom thief" style: slanted panels, ransom-note
headings, halftone textures and page-wipe transitions.

No Composer, no third-party PHP packages: every framework class lives in `framework/`.

---

## Requirements

- PHP 8.0+ (8.3 recommended — 8.0 is end-of-life) with `pdo_mysql`, `curl`, `mbstring`, `openssl`
- MySQL or MariaDB (XAMPP's MariaDB works)
- Optional: one to three NVIDIA API keys from <https://build.nvidia.com> for the full AI agent

`start.bat` uses the first PHP that has `pdo_mysql`: the `php` on your PATH, then
`C:\xampp\php`, then `D:\Program Files\Xampp\php`. For a standalone PHP from windows.php.net,
enable `extension=pdo_mysql` and `extension=pdo_sqlite` in `php.ini` and set `curl.cainfo` to a
[cacert.pem](https://curl.se/docs/caextract.html) so HTTPS calls to the APIs work. If XAMPP is not in
`C:\xampp`, set `XAMPP_PATH=` in `.env` so `phantom start` can find `mysqld.exe`.

`.env` ships with `APP_ENV=production` and `APP_DEBUG=false` (generic error pages). Set
`APP_DEBUG=true` while developing to see the debug page with stack traces.

## Quick start — one command (XAMPP on Windows)

Double-click `start.bat`, or in a terminal opened in the project folder (e.g. VS Code, Ctrl+`):

```bash
.\start
```

It starts XAMPP's MySQL if needed, creates and migrates the database (loading demo data on the
first run only), picks a free port from 8000, opens your browser and runs the server.
Press **Ctrl+C** to stop the server; MySQL keeps running in the background.

## Manual start (step by step)

1. Start **MySQL** from the XAMPP Control Panel.
2. Open a terminal in the project folder and run:

```bash
copy .env.example .env
```

```bash
C:\xampp\php\php.exe phantom db:create
```

```bash
C:\xampp\php\php.exe phantom migrate:fresh --seed
```

```bash
C:\xampp\php\php.exe phantom serve
```

3. Open <http://localhost:8000>.

### Enabling the AI agent (NVIDIA NIM)

1. Sign in at <https://build.nvidia.com>, open
   [Nemotron 3 Ultra](https://build.nvidia.com/nvidia/nemotron-3-ultra-550b-a55b) and click
   **Get API Key**. Repeat with up to three accounts/keys.
2. Open `.env` and paste the keys:

```
NVIDIA_API_KEY_1=nvapi-...
NVIDIA_API_KEY_2=nvapi-...
NVIDIA_API_KEY_3=nvapi-...
NVIDIA_MODEL=nvidia/nemotron-3-ultra-550b-a55b
NVIDIA_REASONING_EFFORT=none
```

Every request starts on a random key. If that key is rate limited (HTTP 429), invalid or out of
credits, the next key is tried immediately, so three keys give Rook roughly three times the
request budget. One or two keys also work, and the names `NVIDIA_API_KEY`, `NVIDIA_API_KEY2`,
`NVIDIA_API_KEY3` are accepted too. `NVIDIA_REASONING_EFFORT` can be `none` (default and fastest: ~3–5 s per Rook reply),
`medium` or `high` (slowest, most thorough).

3. Check every key and confirm tool calling works, then measure response latency:

```bash
php phantom agent:ping
php phantom agent:bench            # 3 runs per case with the configured model
php phantom agent:bench 3 nvidia/nemotron-3.5-lightning-30b-a3b   # compare another model
```

Without any key, Rook runs in **offline mode**: a rule-based planner that calls the same tools, so
the chat, cart actions and activity log can still be demonstrated.

### Running under Apache instead of `php -S`

Copy the folder into `C:\xampp\htdocs\midnight-market` and browse to
`http://localhost/midnight-market/`. The root `.htaccess` routes everything into `public/`, and
the framework detects the sub-folder base path automatically.

## Demo accounts (local test data from `database/seeders/DatabaseSeeder.php`)

| Role     | Email                 | Password        |
|----------|-----------------------|-----------------|
| Admin    | `admin@midnight.test` | `phantom-admin` |
| Customer | `ana@midnight.test`   | `shopper123`    |

## CLI

```
php phantom start [port]        Start MySQL, prepare the DB, open the browser, serve
php phantom serve [port]        Start the development server
php phantom db:create           Create the MySQL database
php phantom migrate             Run pending migrations
php phantom migrate:rollback    Undo the last batch
php phantom migrate:fresh --seed  Rebuild + demo data
php phantom migrate:status      Show migration status
php phantom routes              List routes
php phantom test [filter]       Run the unit tests (68 tests)
php phantom agent:ping          Test each NVIDIA API key + tool calling
php phantom agent:bench [runs] [model]  Measure AI latency (plain replies, tool call, full Rook turn)
```

## Project layout

```
framework/            Phantom framework (namespace Phantom\)
  Application.php     Kernel: lifecycle, dispatch, argument injection
  Container.php       DI container with autowiring
  Routing/            Router + Route (params, constraints, groups, names)
  Http/               Request, Response, Controller, Client/HttpClient
  Middleware/         Pipeline, CSRF, session, logging, throttle, security headers
  Database/           Connection (PDO), QueryBuilder, Model (Active Record), Migrator
  View/               Templates with layouts, sections, partials, escaping
  Validation/         Rule-based validator
  Session/, Auth/     Sessions with flash data, session authentication
  Support/            Env, Config, Cache, Logger
  Console/, Testing/  CLI dispatcher, test runner + assertions
app/                  Application (namespace App\)
  Controllers/        Storefront, auth, cart, checkout, orders, API, admin
  Models/             User, Product, Category, Order, ...
  Middleware/         auth, admin, guest, shared view data
  Services/           Cart, Order, Currency (Frankfurter), ProductArt (SVG)
  Services/AI/        NimClient (key rotation), NimAgent loop, tools, KnowledgeBase, offline agent
config/               app.php, database.php, services.php
database/             migrations/ + seeders/
resources/views/      PHP templates
public/               index.php front controller, CSS, JS
routes/web.php        Route definitions
tests/                Unit tests (framework + app)
```

## Features

- Catalog with search, category filters, price range, sorting, pagination
- Product pages with generated SVG artwork, reviews and ratings, related items
- Database-backed cart for guests and members (merged on login)
- Checkout in a single database transaction with row locks and stock checks
- Order history, status timeline, customer cancellation (restores stock)
- Admin: dashboard with a revenue chart, products CRUD, categories, orders and status updates, customers
- **Rook** (storefront agent): searches, compares, adds to the cart, tracks orders, converts currencies, and
  answers policy questions from the help-center knowledge base
- **Rook Intel** (admin agent): sales briefings, best-sellers, low stock, category performance; it can only
  *prepare* an order-status change or restock and link to the admin page where you apply it
- AI copywriter for product descriptions
- Agent activity log: every tool call, its input and output, stored in `agent_actions`
- Live currency switcher (PHP, USD, EUR, JPY, GBP, KRW, SGD) via the Frankfurter API with database caching
- Security: CSRF tokens, prepared statements, identifier whitelisting, output escaping, bcrypt passwords,
  session regeneration, IP-based rate limiting, ownership checks, security headers with a baseline CSP

## Rook's knowledge base

Rook answers questions from `resources/knowledge/faq.md` (27 entries, searched by the `search_knowledge_base` tool;
offline mode uses it too):

- **The shop:** what Midnight Market is, what it sells (live catalog), its phantom-thief style, the site's vocabulary
  (Hideout, My Heists, calling card, crew…), whether it is a real store, and who built it.
- **Rook itself:** who Rook is and which model it runs on, what it can and cannot do, how chats are stored and who
  can see them, and Rook Intel.
- **Policies and how-tos:** shipping, delivery, payment, currencies, cart and stock, checkout, order references and
  statuses, cancelling, returns, changing orders, accounts, passwords, privacy, reviews and searching.

Each entry is a `## Title`, an optional `keywords:` line and the answer. Placeholders are filled at runtime so answers
always match the live app: `{shipping_fee}`, `{free_shipping_over}`, `{max_qty}`, `{currencies}`, `{payment_methods}`,
`{statuses}` and `{model}` come from config; `{catalog_overview}`, `{product_count}`, `{price_min}` and `{price_max}`
come from the database. Only real facts go in; when a feature changes, update its entry.

## AI guardrails

- Every agent tool runs as the signed-in user and re-checks ownership (orders) or session scope (cart).
- Tool arguments from the model are validated (`ToolInput`); unknown tools and bad JSON become error results; a turn
  is capped at 8 steps; every call is logged in `agent_actions`.
- Rook Intel cannot change data. Tool results contain customer-written text (names), so a prompt-injected model must
  never be able to write: status changes and restocks are only prepared and applied by a human on the admin pages.
- Look-alike `<context>` tags typed by users are stripped; agent replies are rendered by an escape-first Markdown
  renderer that only allows same-origin links.
- Rate limits are per IP (login 10/min, chat 15/min) and cannot be reset by dropping the session cookie.
