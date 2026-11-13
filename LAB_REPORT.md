# Laboratory Report: Visualizing Nested Forum Replies (PHP, MySQL and PDO)

---

## 1. Student & Activity Information
- **Student Name:** [Your Name Here]
- **Course & Section:** BSIT 3A
- **Activity Title:** Laboratory Activity: Visualizing Nested Forum Replies (PHP, MySQL and PDO)
- **Submission Date:** November 13, 2026
- **Repository URL:** `https://github.com/[your-username]/nested-forum-lab` (or designated course Git repository)

---

## 2. Architecture & Request Lifecycle

### Request Flow Diagram
```
Browser / HTTP Client
         │
         ▼  (1) HTTP Request (e.g., GET /discussions/1)
┌────────────────────────────────────────────────────────┐
│ Front Controller & Router (public/index.php)           │
│ - Starts secure session (HttpOnly, SameSite=Lax)      │
│ - Bramus Router matches URI pattern to Controller      │
└────────────────────────┬───────────────────────────────┘
                         │
                         ▼  (2) Dispatches action
┌────────────────────────────────────────────────────────┐
│ Controllers (app/Controllers/)                         │
│ - Validates input parameters (ID, CSRF, lengths)       │
│ - Authenticates session state                          │
│ - Calls Model methods                                  │
└──────────────┬──────────────────────────┬──────────────┘
               │ (3) Data Query           │ (5) View Data
               ▼                          ▼
┌──────────────────────────────┐  ┌──────────────────────┐
│ Models (app/Models/)         │  │ Views (app/Views/)   │
│ - Comment, Discussion, User  │  │ - Mustache templates │
│ - Parameterized PDO queries  │  │ - Recursive partials │
│ - Tree traversal & depth     │  │ - HTML entity escape │
└──────────────┬───────────────┘  └──────────┬───────────┘
               │                             │
               ▼ (4) SQL Query               │ (6) Rendered HTML
┌──────────────────────────────┐             │
│ MySQL / MariaDB Database     │             │
│ - Composite Foreign Keys     │             │
│ - ACID transactions          │             ▼
└──────────────────────────────┘  Browser receives HTML
```

### Libraries Selected and Justification
1. **Bramus Router (`bramus/router`)**: Lightweight, robust routing engine supporting clean HTTP method separation (`GET`, `POST`), URL parameters, and custom 404 handlers. Replaces manual query string parsing (`?page=...`) with modern RESTful endpoints (`/discussions/1`, `/replies`, `/login`).
2. **Mustache (`mustache/mustache`)**: Logic-less templating engine strictly enforcing separation of presentation and business logic. Automatically performs context-aware HTML escaping (`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`), preventing XSS vulnerabilities. Supports recursive partials (`{{> comment}}`) for clean tree rendering.
3. **Native PDO (`PDO` & `pdo_mysql`)**: Used directly instead of heavyweight ORMs. Emulated prepared statements are disabled (`PDO::ATTR_EMULATE_PREPARES => false`) and exceptions are enabled, ensuring maximum security against SQL injection while maintaining high performance.

---

## 3. Data Model & Parent-Child Discussion Invariant

### Schema Definition
```sql
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
    CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    author_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_comments_id_discussion (id, discussion_id),
    CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent_discussion FOREIGN KEY (parent_id, discussion_id) 
        REFERENCES comments(id, discussion_id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Relationship Between `discussion_id` and `parent_id`
- **`discussion_id`** establishes forum topic membership. Every reply belongs to exactly one discussion thread.
- **`parent_id`** defines the hierarchical tree structure. When `parent_id` is `NULL`, the comment is a top-level reply (depth 0). When `parent_id` references another comment ID, it is a nested reply.
- **Composite Foreign Key Invariant:** The composite constraint `FOREIGN KEY (parent_id, discussion_id) REFERENCES comments(id, discussion_id)` guarantees at the database storage engine level that a child reply can never reference a parent comment from a different discussion thread.

---

## 4. Key Verification & Evidence Commentary

### 4.1 Project Structure & Clean Separation
- **Evidence:** The codebase follows strict MVC directory separation (`public/`, `app/Controllers/`, `app/Models/`, `app/Views/`, `config/`, `database/`, `tests/`).
- **Explanation:** SQL is completely isolated inside Model classes (`app/Models/`). Controllers never execute queries directly and never render raw HTML. Templates contain zero database or routing code, utilizing Mustache tags for rendering.

### 4.2 Seed Data & Tree Hierarchy
- **Evidence:** The initial discussion *"How do I learn PHP?"* renders 7 seeded comments matching the activity scenario:
  - Depth 0: Start with the PHP manual. [Alice]
    - Depth 1: Try the examples as you read. [Ben]
      - Depth 2: Build a small project too. [Cara]
        - Depth 3: Start with a forum. [Ben]
          - Depth 4: Add replies next. [Eli]
    - Depth 1: Which manual section first? [Dee]
  - Depth 0: Practice SQL as well. [Eli]
- **Explanation:** The tree builder groups comments by parent in $O(N)$ time using an adjacency map and executes a depth-first traversal to calculate depth dynamically without storing redundant depth counters in the database.

### 4.3 Registration & Authentication Security
- **Evidence:** Registration validates username uniqueness, valid email format, minimum 6-character passwords, and matching confirmation. Passwords are encrypted with `password_hash($password, PASSWORD_DEFAULT)`.
- **Explanation:** During login, `password_verify()` authenticates users. A null or empty password hash is explicitly guarded against and will never authenticate. Upon authentication, `session_regenerate_id(true)` invalidates the old session ID to eliminate session fixation risks.

### 4.4 Authenticated Replies: Depth-5 Reply & Second Top-Level Comment
- **Evidence:**
  - Posted reply under Comment #7 (Eli at depth 4) by user `grace` with message: *"Awesome tips, applying them now!"*. Dynamically rendered at **Depth 5**.
  - Posted second top-level reply by user `grace`: *"Also make sure to learn PDO and prepared statements."*. Dynamically rendered at **Depth 0**.
- **Explanation:** The write handler verifies the parent comment exists, belongs to the same discussion, and calculates depth dynamically. Indentation is capped at depth 8 while depth badges reflect accurate numerical depth.

### 4.5 Server-Side Authorization: Rejected Guest Write
- **Evidence:** Directly sending a POST request to `/replies` without an authenticated session fails and produces zero database rows.
- **Explanation:** `ReplyController::store()` inspects `$_SESSION['user_id']` on the server before processing input. Guests cannot bypass authorization through forged POST requests.

### 4.6 Security Checks: CSRF, Malformed IDs, and XSS Escaping
- **CSRF Token Validation:** Forms generate a cryptographically secure 256-bit token (`csrf_token()`). Tampered tokens result in HTTP 400 rejection and no record creation.
- **Malformed Discussion ID:** Requesting `/discussions/abc` or nonexistent IDs like `/discussions/9999` safely returns HTTP 404 with an accessible error template.
- **HTML/XSS Escaping:** Submitting `<script>alert('XSS Attack');</script> & <b>bold text</b>` is safely converted by Mustache to `&lt;script&gt;alert(&#039;XSS Attack&#039;);&lt;/script&gt;`, preventing script execution.

---

## 5. Power-User Challenge: Accessible Collapse & Expand Replies

### Implementation Details
- **Keyboard & Screen-Reader Accessible:** Rendered using standard `<button type="button" class="collapse-btn">` elements. Fully operable with Tab, Space, and Enter keys.
- **WAI-ARIA Attributes:** Each child container has a unique DOM ID (`id="children-{{id}}"`). The button connects to the container using `aria-controls="children-{{id}}"` and toggles `aria-expanded="true"` / `aria-expanded="false"`.
- **Visible Dynamic Labels:** The button text toggles between `"Hide replies (N)"` and `"Show replies (N)"` where N is the child count.
- **Progressive Enhancement:** If JavaScript is disabled or blocked, all comments remain expanded and legible, ensuring zero degradation for non-JS clients. Comments without children omit the collapse button.

---

## 6. Development Reflection

### How the Solution Was Built
1. **Schema & Model First:** We started by structuring the MySQL schema with composite foreign keys to lock down discussion boundaries at the database engine level. We built the `Comment` model to support both pre-order flattened arrays and recursive tree structures.
2. **MVC Separation:** We wired up the Bramus Router to route requests to dedicated controllers (`DiscussionController`, `AuthController`, `ReplyController`), keeping SQL inside models and presentation in Mustache templates.
3. **Security Hardening:** We integrated session-backed CSRF tokens, `password_hash()`, `session_regenerate_id()`, and strict input validations.
4. **Accessible Challenge:** We implemented dynamic collapsible trees with WAI-ARIA controls and tested keyboard navigation and progressive enhancement.
5. **Automated Verification:** We developed an automated test suite of 40 assertions to verify all edge cases.

### Problems Encountered & Resolutions
1. **Composite Foreign Key on Self-Referencing Table:** MySQL InnoDB requires that foreign keys reference a unique or primary key. By adding `UNIQUE KEY uq_comments_id_discussion (id, discussion_id)`, InnoDB permitted the composite self-referential constraint `FOREIGN KEY (parent_id, discussion_id) REFERENCES comments(id, discussion_id)`.
2. **Bramus Router Route Syntax:** Bramus Router expects string format `'Controller@method'` for instantiated controller invocation rather than array callables. Updating route definitions resolved method dispatching smoothly.
3. **Header Modification Warnings in CLI Tests:** In automated CLI test scripts, output started prior to redirect headers. We guarded `redirect()` with `headers_sent()` checks to ensure clean CLI execution while preserving strict 302 redirects in web environments.

### Limitations & Future Improvements
- **In-Memory Traversal for Large Threads:** Loading all comments into memory and building the tree in PHP is efficient for lab datasets and medium threads (< 5,000 comments). For enterprise-scale threads (100,000+ comments), pagination or lazy-loading via CTEs (`WITH RECURSIVE`) or closure tables would be recommended.
- **Image Avatars:** Currently, users receive a default SVG avatar with their username's initial and unique color hash. Future iterations could integrate local file uploads with MIME-type verification or opt-in Gravatars.
