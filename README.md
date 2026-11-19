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
