# Complete Project Documentation
## Laboratory Activity: Visualizing Nested Forum Replies (PHP, MySQL and PDO)

---

## 1. Document Control & Project Metadata

| Field | Details |
|---|---|
| **Course & Section** | BSIT 3A |
| **Course Subject** | Web Systems and Technologies / Advanced Web Development |
| **Activity Title** | Laboratory Activity: Visualizing Nested Forum Replies (PHP, MySQL and PDO) |
| **Academic Date** | November 13, 2026 |
| **Author / Student** | [Insert Your Name Here] |
| **Student ID** | [Insert Your Student ID] |
| **Repository URL** | [Insert Your GitHub / Git Repository URL] |
| **Local Working Directory** | `c:\BSIT 3A\forum-refactor` |
| **Local Web URL** | `http://127.0.0.1:8000/discussions/1` |

---

## 2. Executive Summary & Problem Formulation

### 2.1 Background & Scenario
Modern online communities (such as Reddit, Hacker News, and technical discussion boards) organize conversations hierarchically. Instead of a flat chronological feed, users can reply directly to any comment, generating a **reply tree** (a rooted tree data structure).

In this laboratory activity, we designed, refactored, and deployed a complete, standalone web application implementing:
1. **Mathematical Tree Visualization:** Top-level comments have depth 0, and each reply increments depth by 1. Comments can share parents (siblings).
2. **Visual Indentation Capping:** Indentation is visually capped at depth 8 to prevent thread cramping on narrow viewports, while accurate numerical depth badges are preserved.
3. **Strict MVC Architecture:** Clean separation between routes, controllers, data-access models, and logic-less templates.
4. **Server-Side Security & Authentication:** Public reading access, but authenticated-only posting. Implementation of registration, login with Bcrypt password hashing, session fixation defense, CSRF token validation, and SQL/XSS mitigation.
5. **Database Invariants:** Enforcement of self-referencing composite foreign keys ensuring replies strictly remain within the same discussion thread.
6. **Accessible Power-User Challenge:** Interactive thread collapse/expand controls conforming to WAI-ARIA standards and progressive enhancement.

---

## 3. Mathematical Theory: Rooted Trees & Depth

### 3.1 Formal Definition
The forum conversation is modeled as a finite directed rooted tree:
$$T = (V, E, r), \quad E \subseteq V \times V, \quad r \in V$$
- $V$: The set of vertices (the discussion root $D$ and all comments $C_1, C_2, \dots$).
- $E$: The set of directed edges $(p, v)$ pointing from parent $p$ to child $v$.
- $r$: The discussion root node $D$.

### 3.2 Depth & Height
- **Graph Depth $d(v)$:** Number of edges along the unique directed path from the root $r$ to node $v$.
- **Display Depth:** Top-level comments (direct children of $D$) have displayed depth 0 ($d_{\text{display}}(v) = d(v) - 1$).
- **Tree Height $h(T)$:** The length of the longest path from root to a leaf node:
  $$h(T) = \max_{v \in V} d(v)$$

### 3.3 Target Scenario Trace
In the initial seeded discussion (*"How do I learn PHP?"*), the reply hierarchy is:
```
Discussion 1 (Root D) — How do I learn PHP?
├── Comment 1: Alice — [Depth 0, Indent 0]
│   ├── Comment 2: Ben — [Depth 1, Indent 1]
│   │   └── Comment 3: Cara — [Depth 2, Indent 2]
│   │       └── Comment 6: Ben — [Depth 3, Indent 3]
│   │           └── Comment 7: Eli — [Depth 4, Indent 4]  <-- Initial Max Depth
│   └── Comment 4: Dee — [Depth 1, Indent 1] (Sibling of Ben #2)
└── Comment 5: Eli — [Depth 0, Indent 0] (Top-Level)
```

---

## 4. System Architecture & Component Design

The application follows the **Model-View-Controller (MVC)** architectural pattern:

```
                            HTTP Request (e.g., GET /discussions/1)
                                      │
                                      ▼
                      ┌───────────────────────────────┐
                      │      public/index.php         │
                      │  (Front Controller & Router)  │
                      └───────────────┬───────────────┘
                                      │ Dispatch
                                      ▼
                      ┌───────────────────────────────┐
                      │       app/Controllers/        │
                      │ - DiscussionController        │
                      │ - AuthController              │
                      │ - ReplyController             │
                      └───────┬───────────────┬───────┘
          Load / Save Data    │               │  Prepare View Data
                              ▼               ▼
        ┌───────────────────────────┐   ┌───────────────────────────┐
        │        app/Models/        │   │        app/Views/         │
        │ - Database (PDO)          │   │ - Mustache Templates      │
        │ - Discussion              │   │ - Recursive Partials      │
        │ - Comment (Tree Builder)  │   │ - Auto HTML Escaping      │
        │ - User (Bcrypt & Avatar)  │   └─────────────┬─────────────┘
        └─────────────┬─────────────┘                 │
       SQL via PDO    │                               │ Rendered HTML
                      ▼                               ▼
        ┌───────────────────────────┐           Client Browser
        │   MySQL / MariaDB Engine  │
        └───────────────────────────┘
```

### 4.1 Component Breakdown

#### 1. Router & Front Controller (`public/index.php`)
- Acts as the single public entry point for all HTTP requests.
- Initializes session security parameters:
  - `lifetime = 0` (session lasts until browser closes).
  - `httponly = true` (blocks JavaScript `document.cookie` access).
  - `samesite = Lax` (defends against Cross-Site Request Forgery).
  - `secure = true` when served over HTTPS / port 443.
- Utilizes **Bramus Router** to map RESTful endpoints to controllers.
- Configures custom 404 handlers via `$router->set404(...)`.

#### 2. Controllers (`app/Controllers/`)
- **`DiscussionController.php`**:
  - Validates discussion IDs using regex (`/^[1-9]\d*$/`). Non-numeric or negative IDs trigger a clean 404 error page.
  - Queries `Discussion` and `Comment` models to retrieve thread data.
  - Passes data to Mustache engine with session context and flash messages.
- **`AuthController.php`**:
  - `showRegister()` and `register()`: Validates username (3-50 chars), email address (`FILTER_VALIDATE_EMAIL`), and password (min 6 chars). Hashes passwords using `password_hash($password, PASSWORD_DEFAULT)`.
  - `showLogin()` and `login()`: Authenticates users using `password_verify()`. Calls `session_regenerate_id(true)` upon login to prevent session fixation.
  - `logout()`: Requires POST method and valid CSRF token; flushes `$_SESSION`, destroys the session, and expires the session cookie.
- **`ReplyController.php`**:
  - Enforces server-side authorization: guests submitting replies are redirected to `/login` with an error flash.
  - Validates session-backed CSRF tokens via `csrf_check()`.
  - Derives `user_id` strictly from `$_SESSION['user_id']`, completely ignoring any client-tampered user fields.
  - Validates comment body length (1 to 2,000 characters).
  - Validates parent invariants and cycle prevention via `Comment::validateParent()`.

#### 3. Models & Data Access Layer (`app/Models/`)
- **`Database.php`**:
  - Implements a centralized PDO singleton.
  - Configures `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` and `PDO::ATTR_EMULATE_PREPARES => false`.
  - Connects using `utf8mb4` character encoding.
- **`User.php`**:
  - Queries and creates user records.
  - Implements `verifyPassword(array $user, string $password)`: explicitly guarantees that a `NULL` or empty password hash will **never** authenticate.
  - Implements `resolveAvatar()`: dynamically generates an inline SVG avatar with the user's initial and consistent hue hash when no custom avatar is present.
- **`Discussion.php`**:
  - Retrieves discussion title, body, author, and UTC timestamp using parameterized SQL queries.
- **`Comment.php`**:
  - **`threadedForDiscussion(int $discussionId)`**: Builds an in-memory adjacency list in $O(N)$ time and outputs a flattened depth-first pre-order array for linear processing.
  - **`treeForDiscussion(int $discussionId)`**: Assembles a multi-branch recursive tree structure where each comment contains a nested `replies` array.
  - **`validateParent(int $discussionId, int $parentId)`**: Traverses up the parent chain to detect loops (cycle prevention), verify discussion ID parity, and ensure total depth does not exceed `MAX_TRAVERSAL_DEPTH = 50`.
  - Enforces `MAX_DISPLAY_DEPTH = 8` (`indent = min(depth, 8)`).

#### 4. Views & Logic-less Templating (`app/Views/`)
- **`discussion.mustache`**: Renders forum thread header, comment tree, and top-level reply form.
- **`comment.mustache`**: Recursive Mustache partial (`{{> comment}}`) rendering individual comment cards, depth badges, avatars, UTC timestamps, accessible collapse/expand controls, and inline reply forms.
- **`login.mustache`** & **`register.mustache`**: Authentication views with validation hints and CSRF hidden tokens.
- **`error.mustache`**: Accessible error template for 404/400 HTTP errors.

#### 5. Helper Functions (`app/helpers.php`)
- `csrf_token()`: Generates and stores a cryptographically secure 256-bit random hex token in `$_SESSION`.
- `csrf_check()`: Verifies incoming POST token against `$_SESSION['csrf_token']` using constant-time `hash_equals()`.
- `redirect(string $url)`: Safe HTTP 302 redirection with `headers_sent()` guards for automated testing compatibility.
- `flash(string $key, ?string $value)`: Flash messaging system persisting messages across one redirect.

---

## 5. Database Schema & Relational Integrity

### 5.1 Relational Schema (`database/schema.sql`)
```sql
CREATE DATABASE IF NOT EXISTS nested_forum_lab 
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum_lab;

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS discussions;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE discussions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    author_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_comments_id_discussion (id, discussion_id),
    CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) 
        REFERENCES discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_author FOREIGN KEY (author_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) 
        REFERENCES comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent_discussion 
        FOREIGN KEY (parent_id, discussion_id) 
        REFERENCES comments(id, discussion_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_comments_discussion_parent ON comments(discussion_id, parent_id);
CREATE INDEX idx_comments_parent ON comments(parent_id);
```

### 5.2 Composite Foreign Key Mechanism
In standard database designs, a simple foreign key `FOREIGN KEY (parent_id) REFERENCES comments(id)` ensures that `parent_id` points to a real comment, but does **not** ensure the parent belongs to the *same* discussion. A corrupted or malicious write could set `discussion_id = 2` while `parent_id = 1` (which belongs to discussion 1), breaking data integrity.

By adding `UNIQUE KEY (id, discussion_id)` to `comments`, InnoDB allows the composite constraint:
```sql
CONSTRAINT fk_comments_parent_discussion 
FOREIGN KEY (parent_id, discussion_id) 
REFERENCES comments(id, discussion_id) 
ON DELETE CASCADE
```
When `parent_id` is `NULL`, MySQL allows the insert (standard SQL nullable foreign key behavior). When `parent_id` is not null, InnoDB validates that a row exists where both `id = parent_id` **and** `discussion_id = child.discussion_id`. Any cross-discussion parent reference is rejected by MySQL with a foreign key violation error.

---

## 6. Comprehensive Security Engineering

| Threat / Vulnerability | Vector | Mitigation Implemented |
|---|---|---|
| **SQL Injection** | Malicious input in forms or URLs | Parameterized PDO statements (`PDO::prepare`) across all queries. `ATTR_EMULATE_PREPARES` set to false. No SQL string concatenation. |
| **Cross-Site Scripting (XSS)** | Injection of `<script>` or HTML into comment bodies or usernames | Mustache auto-escapes all variables via `htmlspecialchars(..., ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')`. Triple mustache `{{{...}}}` is strictly forbidden for user input. |
| **Cross-Site Request Forgery (CSRF)** | Unauthorized state changes from external origins | Cryptographic 32-byte session tokens generated on all GET views; validated on all POST writes (`register`, `login`, `logout`, `replies`) with timing-safe `hash_equals()`. |
| **Session Fixation** | Attacker presets session ID before victim logs in | Immediate call to `session_regenerate_id(true)` upon successful user authentication. |
| **Credential Theft / Leakage** | Database compromise or plaintext logging | Passwords hashed using industry-standard Bcrypt (`PASSWORD_DEFAULT`). Passwords and session tokens are never logged. Null password hashes explicitly rejected. |
| **Privilege Bypass** | Direct HTTP POST by unauthenticated guests | Server-side authentication verification (`$_SESSION['user_id']`) before processing any reply creation. |
| **Infinite Loops / DoS** | Malicious creation of parent cycles ($A \to B \to A$) | `Comment::validateParent` algorithm traverses ancestor chain, recording visited nodes; throws exception if loop or depth > 50 detected. |

---

## 7. Power-User Challenge: Accessible Collapse & Expand Replies

Section 9 requirements were fully implemented in [`comment.mustache`](file:///c:/BSIT%203A/forum-refactor/app/Views/comment.mustache) and [`public/js/forum.js`](file:///c:/BSIT%203A/forum-refactor/public/js/forum.js):

### 7.1 WAI-ARIA Accessibility Standards
1. **Semantic HTML Elements:** Built using native `<button type="button" class="collapse-btn">` tags rather than non-semantic `<div>` or `<a>` elements. This provides native keyboard navigation (`Tab` focus, `Enter`/`Space` activation).
2. **Accessible Relationships:** Each child container possesses a unique ID (`id="children-{{id}}"`). The button links directly to this container via `aria-controls="children-{{id}}"`.
3. **State Representation:** The button reflects real-time status via `aria-expanded="true"` (expanded) or `aria-expanded="false"` (collapsed), allowing assistive technologies (screen readers) to announce branch state.
4. **Accessible Hiding:** Collapsing hides the child container using the native HTML `hidden` attribute (`targetContainer.hidden = true`).
5. **No Collapse for Leaves:** Comments without children omit the collapse button entirely.

### 7.2 Progressive Enhancement
If a user blocks or disables JavaScript:
- The `hidden` attribute is never applied.
- All replies remain fully visible, readable, and properly nested.
- No broken or unresponsive script controls are displayed.

---

## 8. Verification & Test Evidence (Screenshot Commentary Guide)

An automated verification test suite ([`tests/run_tests.php`](file:///c:/BSIT%203A/forum-refactor/tests/run_tests.php)) was developed and executed, validating **40 out of 40 assertions**. Below is the detailed commentary corresponding to each required submission screenshot:

### Screenshot 1: Project Structure & Routing Separation
- **Demonstration:** Shows the clean folder hierarchy (`app/Controllers`, `app/Models`, `app/Views`, `public`, `config`, `database`, `tests`) and route definitions in `public/index.php`.
- **Commentary:** Demonstrates strict separation of concerns. The front controller routes clean URLs to controller classes (`DiscussionController@show`, `AuthController@register`, `ReplyController@store`). No presentation logic exists in models, and no database queries exist in controllers or templates.

### Screenshot 2: Database Schema & Relational Integrity
- **Demonstration:** Shows the MySQL command line executing `DESCRIBE comments;` and `SHOW CREATE TABLE comments;`.
- **Commentary:** Highlights the composite foreign key constraint `fk_comments_parent_discussion` on `(parent_id, discussion_id) REFERENCES comments(id, discussion_id)`. This provides verifiable proof that the same-discussion invariant is enforced by MySQL at the storage level.

### Screenshot 3: User Registration & Authentication
- **Demonstration:** Shows user registration of `grace` (`grace@example.com`) and subsequent login, followed by inspection of `users` table in MySQL.
- **Commentary:** Demonstrates secure registration with email validation and Bcrypt password hashing (`$2y$10$...`). Verifies that passwords are never stored in plaintext and that `session_regenerate_id(true)` successfully creates a new session ID upon login.

### Screenshot 4: Nested Reply Tree with Depth-5 Reply and Second Top-Level Comment
- **Demonstration:** Displays `http://127.0.0.1:8000/discussions/1` after user `grace` submits:
  1. A reply under Comment #7 (Eli at depth 4) $\to$ rendered at **Depth 5**.
  2. A new top-level comment $\to$ rendered at **Depth 0**.
- **Commentary:** Confirms accurate recursive tree rendering. Comment #7 now shows a collapse button with 1 child. Grace's depth-5 reply appears indented directly under Eli. The new top-level comment appears at the root level at depth 0. Both display author names, UTC timestamps, and generated SVG avatars.

### Screenshot 5: Server-Side Authorization: Rejected Guest POST
- **Demonstration:** Sending an unauthenticated `POST` request to `/replies` via cURL or automated test script without a logged-in session.
- **Commentary:** Demonstrates that the server enforces authorization independently of the UI. The request is rejected, redirecting to `/login` with an error flash. Database count verifies that **zero rows** were inserted.

### Screenshot 6: Security Defense: Invalid CSRF Rejection & Malformed ID 404
- **Demonstration:**
  1. Submitting a POST request with an invalid/tampered CSRF token $\to$ returns HTTP 400 with "Invalid or expired CSRF token".
  2. Requesting `/discussions/abc` $\to$ returns HTTP 404 with "Invalid discussion ID".
  3. Requesting `/discussions/9999` $\to$ returns HTTP 404 with "Discussion not found".
- **Commentary:** Proves robust validation against forged requests and malformed parameters before reaching the database layer.

### Screenshot 7: XSS Mitigation: HTML/Script Tag Escaping
- **Demonstration:** A comment posted with `<script>alert('XSS Attack');</script> & <b>bold text</b> "quoted"` rendered on the discussion page, inspected via Browser Developer Tools.
- **Commentary:** Demonstrates that Mustache's escaping converts special characters into safe HTML entities (`&lt;script&gt;`, `&amp;`, `&#039;`). The script tag renders as readable plaintext without executing in the client browser.

### Screenshot 8: Power-User Challenge: Accessible Collapse & Expand in Action
- **Demonstration:** Collapsing Comment #1 (Alice) using mouse click or keyboard `Space`/`Enter`.
- **Commentary:** Shows that collapsing Alice hides all of Alice's descendants (Ben, Cara, Ben, Eli, Dee) while Eli's top-level Comment #5 remains completely visible. The button updates dynamically to `"Show replies (2)"` and `aria-expanded="false"`. Clicking again restores the entire branch with its exact nesting and depth labels intact.

---

## 9. Development Reflection

### 9.1 Technical Challenges & Resolutions
1. **InnoDB Composite Self-Referencing Foreign Key:**
   - *Problem:* Creating a foreign key from `(parent_id, discussion_id)` to `(id, discussion_id)` failed initially because InnoDB requires that the referenced composite columns be explicitly covered by a UNIQUE index.
   - *Resolution:* Added `UNIQUE KEY uq_comments_id_discussion (id, discussion_id)` before defining the foreign key constraint. This satisfied InnoDB's relational requirements and enforced cross-discussion protection at the engine level.
2. **Bramus Router Route Handler Format in PHP 8.2:**
   - *Problem:* Using PHP callable array syntax `[DiscussionController::class, 'show']` caused a `TypeError: stripos(): Argument #1 ($haystack) must be of type string` inside Bramus Router on PHP 8.2 because non-static methods are not callable statically.
   - *Resolution:* Reconfigured route registrations to Bramus's native `'ControllerClass@method'` format (`'App\Controllers\DiscussionController@show'`), enabling automatic controller instantiation and clean parameter injection.
3. **Automated Testing CLI Redirects:**
   - *Problem:* In PHP CLI test scripts, executing controller methods that invoke `header('Location: ...')` triggered `Cannot modify header information - headers already sent` warnings because test progress was already printing to `STDOUT`.
   - *Resolution:* Guarded the `redirect()` helper with `headers_sent()` and a `TESTING_MODE` flag, allowing automated tests to verify controller redirection logic without halting CLI test execution.

### 9.2 Limitations & Future Roadmap
- **In-Memory Traversal for Massive Threads:** For classroom and standard forum discussions (< 5,000 comments), loading rows into memory and building adjacency lists is extremely fast ($O(N)$). For enterprise systems (e.g. Reddit with 100,000+ comments), pagination with SQL Recursive Common Table Expressions (`WITH RECURSIVE`) or a Nested Set / Closure Table model would be ideal.
- **Profile Image Extensions:** The project currently implements dynamic SVG avatars based on user initials and color hashes. Future iterations can integrate local file uploads with MIME-type verification, image resizing, and opt-in Gravatars as outlined in Section 10 of the lab guide.

---

## 10. Local Setup & Execution Guide

### Step 1: Run the Automated Test Runner
```bash
php "c:\BSIT 3A\forum-refactor\tests\run_tests.php"
```
*Expected Output: `Test Summary: 40 Passed, 0 Failed`.*

### Step 2: Run End-to-End HTTP Route Tests
```bash
php "c:\BSIT 3A\forum-refactor\tests\test_http.php"
```
*Expected Output: `All HTTP tests passed successfully!`.*

### Step 3: Access the Web Application
Start local development server:
```bash
php -S 127.0.0.1:8000 -t "c:\BSIT 3A\forum-refactor\public"
```
Open [http://127.0.0.1:8000/discussions/1](http://127.0.0.1:8000/discussions/1) in your web browser.

### Step 4: Test Credentials
- **Username:** `alice` | **Password:** `password123`
- **Username:** `ben`   | **Password:** `password123`
- **Username:** `cara`  | **Password:** `password123`
- **Username:** `dee`   | **Password:** `password123`
- **Username:** `eli`   | **Password:** `password123`
