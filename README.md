<<<<<<< HEAD
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
=======
<<<<<<< HEAD
# Nested Forum Lab — Visualizing Nested Forum Replies
**PHP, MySQL and PDO (MVC Architecture)**

A complete implementation of the **Laboratory Activity: Visualizing Nested Forum Replies** using modern PHP 8.2+, MySQL / MariaDB, PDO, Bramus Router, and Mustache templating.

---

## 1. Project Overview & Architecture

The application implements an MVC-style separation of concerns:
- **Router / Front Controller (`public/index.php`)**: Maps incoming HTTP requests to controller actions and handles 404 responses.
- **Controllers (`app/Controllers/`)**:
  - `DiscussionController`: Validates discussion IDs, fetches discussion and comment trees, and renders views.
  - `AuthController`: Handles user registration, authentication (login), and logout with CSRF validation.
  - `ReplyController`: Enforces server-side authorization, verifies CSRF tokens, validates reply depth and parent constraints, and creates comments.
- **Models / Data Access Layer (`app/Models/`)**:
  - `Database`: Encapsulates PDO connection with UTF-8 charset, exceptions enabled, and emulated prepares disabled.
  - `User`: Manages user records, registration, password hashing (`PASSWORD_DEFAULT`), and avatar generation.
  - `Discussion`: Manages discussion retrieval and formatting.
  - `Comment`: Builds hierarchical recursive trees (`treeForDiscussion`) and flattened pre-order traversals (`threadedForDiscussion`), validates parent invariants, detects cycles, and caps indentation at depth 8.
- **Views & Templates (`app/Views/`)**:
  - `discussion.mustache`: Main discussion view with comment tree and reply forms.
  - `comment.mustache`: Recursive Mustache partial rendering comments, depth badges, avatars, UTC timestamps, accessible collapse/expand controls, and inline reply forms.
  - `login.mustache`: Authentication form with CSRF protection.
  - `register.mustache`: User account creation form with validation rules.
  - `error.mustache`: Clean 404/400 error page.
- **Client Assets (`public/`)**:
  - `css/style.css`: Responsive, accessible styling with indentation margins, cards, badges, and focus rings.
  - `js/forum.js`: Client-side collapse/expand replies (accessible via keyboard and ARIA attributes) and inline reply toggling.

```
forum-refactor/
  public/
    index.php             # Front controller & routing
    css/style.css         # Forum styling & indentation
    js/forum.js           # Accessible collapse/expand & reply toggles
  app/
    Controllers/          # HTTP request handlers (Discussion, Auth, Reply)
    Models/               # PDO data-access classes (Database, User, Discussion, Comment)
    Views/                # Mustache templates and recursive partials
    helpers.php           # Session, CSRF, redirect, and flash helpers
  config/
    database.php          # Database connection credentials (env-overridable)
  database/
    schema.sql            # Table DDL, composite foreign keys, and indexes
    seed.sql              # Fictional seed data (7 comments, branch reaching depth 4)
  tests/
    run_tests.php         # Automated CLI test suite (40 assertions)
    test_http.php         # End-to-end HTTP integration tests
  composer.json           # Dependencies manifest
  composer.lock           # Locked dependency versions
  README.md               # Documentation & setup instructions
```

---

## 2. Requirements & Installation

### Prerequisites
- PHP 8.2 or newer with `pdo_mysql` and `curl` extensions enabled
- MySQL 8.0+ or MariaDB 10.4+
- Composer

### Installation Steps

1. **Clone / Navigate to Project Root**:
   ```bash
   cd forum-refactor
   ```

2. **Install Composer Packages**:
   ```bash
   composer install
   ```

3. **Database Configuration & Setup**:
   Default settings connect to `127.0.0.1:3306` with user `root` and empty password. You can customize them via environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
   
   Execute the schema and seed scripts:
   ```bash
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
   ```

4. **Run the Local Web Server**:
   Start the built-in PHP development server with document root set to `public/`:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   Open your browser and navigate to:
   [http://127.0.0.1:8000/discussions/1](http://127.0.0.1:8000/discussions/1)

5. **Production Web Server Rewrites (Reference)**:
   - **Apache (`public/.htaccess`)**:
     ```apache
     RewriteEngine On
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule ^ index.php [QSA,L]
     ```
   - **Nginx (`nginx.conf`)**:
     ```nginx
     location / {
         try_files $uri $uri/ /index.php?$query_string;
     }
     ```

---

## 3. Data Model & Relationships

### Relationship between `discussion_id` and `parent_id`
- **`discussion_id`**: Identifies which discussion thread a comment belongs to. Every comment must reference an existing discussion.
- **`parent_id`**: Self-referencing foreign key pointing to another comment within the **same** discussion:
  - When `parent_id IS NULL`, the comment is a **top-level comment** (depth 0), representing a direct edge from the discussion root.
  - When `parent_id` contains a comment ID, the comment is a **reply** to that parent, incrementing the displayed depth by 1.
- **Composite Foreign Key**:
  The schema defines a unique constraint on `(id, discussion_id)` and a composite foreign key on `(parent_id, discussion_id)`:
  ```sql
  UNIQUE KEY uq_comments_id_discussion (id, discussion_id),
  CONSTRAINT fk_comments_parent_discussion 
    FOREIGN KEY (parent_id, discussion_id) 
    REFERENCES comments(id, discussion_id) 
    ON DELETE CASCADE
  ```
  This guarantees at the database engine level that a reply can **never** reference a parent belonging to a different discussion.
- **Cycle Prevention & Max Depth**:
  `Comment::validateParent` inspects ancestor parent links before writing to ensure acyclicity (preventing $A \to B \to A$ cycles) and enforces a maximum traversal limit (`MAX_TRAVERSAL_DEPTH = 50`).

---

## 4. Security Implementation

1. **Password Hashing**:
   - Registered passwords are encrypted using `password_hash($password, PASSWORD_DEFAULT)` (Bcrypt).
   - Verified via `password_verify()`. Plain-text passwords are never stored or logged.
   - Null or empty password hashes are explicitly blocked (`verifyPassword` returns `false`).
2. **Session Security & Fixation Prevention**:
   - Cookies are configured with `HttpOnly = true`, `SameSite = Lax`, and `Secure` over HTTPS.
   - `session_regenerate_id(true)` is invoked immediately upon successful login and logout to defeat session fixation attacks.
3. **CSRF Protection**:
   - Every state-changing form (registration, login, logout, top-level replies, inline replies) generates a session-backed CSRF token via `csrf_token()`.
   - Incoming POST requests are validated with timing-safe `hash_equals()` via `csrf_check()`.
4. **Server-Side Authorization**:
   - `ReplyController` verifies authentication on the server (`$_SESSION['user_id']`), independent of view state.
   - The comment author is always bound from `$_SESSION['user_id']`, never from submitted form parameters.
5. **SQL Injection Defense (Parameter Binding)**:
   - All database queries use parameterized prepared statements with `PDO::prepare()` and `execute()`. Emulated prepares are disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
6. **XSS Defense (HTML Escaping)**:
   - Untrusted user input (`body`, `author`, `title`) is escaped by Mustache's default HTML entity encoding (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`). Raw unescaped tags (`{{{...}}}`) are forbidden for user content.

---

## 5. Application Routes

| Method | Path | Controller Action | Access Control | Description |
|---|---|---|---|---|
| `GET` | `/` | Closure | Public | Redirects to default discussion `/discussions/1` |
| `GET` | `/discussions/{id}` | `DiscussionController@show` | Public | Displays discussion thread with nested comments tree |
| `GET` | `/register` | `AuthController@showRegister` | Public | Renders registration form |
| `POST` | `/register` | `AuthController@register` | Public | Validates input, creates account, redirects to `/login` |
| `GET` | `/login` | `AuthController@showLogin` | Public | Renders login form |
| `POST` | `/login` | `AuthController@login` | Public | Authenticates user, regenerates session, redirects |
| `POST` | `/logout` | `AuthController@logout` | Authenticated | Clears session data, invalidates cookie, redirects |
| `POST` | `/replies` | `ReplyController@store` | Authenticated | Validates CSRF, parent invariant, inserts reply |
| `*` | Any undefined path | Custom 404 Handler | Public | Renders accessible 404 error page |

---

## 6. Seed Data & Test Accounts

The seed data establishes the discussion *"How do I learn PHP?"* with 5 fictional users and 7 nested comments:
- **Test Accounts** (password is `password123` for all fictional users):
  - `alice` (`alice@example.com`)
  - `ben` (`ben@example.com`)
  - `cara` (`cara@example.com`)
  - `dee` (`dee@example.com`)
  - `eli` (`eli@example.com`)
- **Initial Comment Tree**:
  - `Comment #1`: Alice (parent NULL) $\to$ **Depth 0**
    - `Comment #2`: Ben (parent 1) $\to$ **Depth 1**
      - `Comment #3`: Cara (parent 2) $\to$ **Depth 2**
        - `Comment #6`: Ben (parent 3) $\to$ **Depth 3**
          - `Comment #7`: Eli (parent 6) $\to$ **Depth 4**
    - `Comment #4`: Dee (parent 1) $\to$ **Depth 1** (sibling of Comment #2)
  - `Comment #5`: Eli (parent NULL) $\to$ **Depth 0** (top-level)

---

## 7. Power-User Challenge: Accessible Collapse & Expand Replies

Section 9 criteria are fully implemented:
1. **Semantic HTML**: Uses native `<button type="button" class="collapse-btn">` controls, fully operable via keyboard (Tab, Space, Enter).
2. **Accessible ARIA Attributes**:
   - Each child-replies container has a unique DOM ID: `id="children-{{id}}"`.
   - The button connects to the container using `aria-controls="children-{{id}}"`.
   - Dynamically updates `aria-expanded="true"` / `aria-expanded="false"`.
3. **Descriptive Labels & Progressive Enhancement**:
   - Visible labels toggle between `"Hide replies (N)"` and `"Show replies (N)"`.
   - Hides child trees using the native `hidden` attribute.
   - Comments without children do not display collapse buttons.
   - If JavaScript is disabled, all replies remain expanded and visible.

---

## 8. Verification & Test Suite

The project includes an automated test runner executing 40 verification assertions across schema integrity, traversal order, authentication, CSRF, guest rejection, cycle prevention, and XSS protection.

Run the test suite:
```bash
php tests/run_tests.php
```

Run HTTP route tests against the running server:
```bash
php tests/test_http.php
```
=======
# ipt10-lab-01
3rd year IT-IPT#10
>>>>>>> 3a468a07df116c4f45b58526e49fc9b668302d3d
>>>>>>> 4cebc346a79479eeaffbfda9cc62c1eab63ab568
