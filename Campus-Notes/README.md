# Campus Notes: security review project (repaired)

This started as intentionally vulnerable educational material. The repaired
version fixes the eleven issues from the lab handout, but it is still a lab
app: run it only on an isolated machine you control, with synthetic data and a
disposable local database. Never expose it to the internet or connect it to
real accounts.

## What changed in the layout

```
Campus-Notes/
├── public/              <- the ONLY folder the web server serves
│   ├── index.php        controller for the web app (routing + checks only)
│   ├── api.php          JSON API (auth, ownership, validation, same-origin)
│   └── style.css        CSS moved out of the page so CSP can block inline code
├── src/                 not reachable from the browser
│   ├── bootstrap.php    error settings, generic exception handler, includes
│   ├── database.php     PDO connection from env vars only, real prepared statements
│   ├── session.php      hardened session cookie, login regenerate, logout destroy
│   ├── csrf.php         CSRF token + Origin / Sec-Fetch-Site checks
│   ├── users.php        register / authenticate with password_hash()
│   ├── notes.php        note queries, always scoped to the signed-in user
│   └── view.php         e() escaping, security headers, flash, redirect, render
├── templates/
│   ├── page.php         main HTML (every value escaped with e())
│   └── error.php        small error page
├── tools/reset_passwords.php   re-hash the two synthetic accounts (CLI only)
├── docs/REPORT.md       the security report (same text as the submitted Google Doc)
├── docs/screenshots/    evidence screenshots
├── tests/run_tests.php         automated security checks (CLI)
├── schema.sql           bcrypt hashes instead of MD5, role CHECK, index
└── .gitignore
```

No Composer packages are used; everything is plain PHP 8 (procedural, split by
responsibility).

## Local setup

1. Install PHP 8.1+ with `pdo_mysql` and `mbstring`, and a local MySQL/MariaDB
   server. Confirm with `php -m`.
2. As a local MySQL administrator, import `schema.sql` into a fresh, disposable
   server/database (it creates `notes_lab`). From PowerShell (no `<` redirection)
   use: `mysql -u root -e "source C:/path/to/schema.sql"` as its own command. If you already imported the old
   starter schema, either drop `notes_lab` and re-import, or run
   `php tools/reset_passwords.php` after step 3 to replace the MD5 hashes.
3. Create a dedicated local account with only SELECT, INSERT, UPDATE, DELETE on
   `notes_lab`:
   ```sql
   CREATE USER 'notes_app'@'localhost' IDENTIFIED BY 'choose-a-local-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON notes_lab.* TO 'notes_app'@'localhost';
   ```
   Set the environment variables in the same shell that starts the server.
   PowerShell:
   ```powershell
   $env:LAB_DB_USER = 'notes_app'
   $env:LAB_DB_PASSWORD = 'choose-a-local-password'
   ```
   bash: `export LAB_DB_USER=notes_app LAB_DB_PASSWORD=...`
   Both are required; the app no longer falls back to `root` with an empty
   password. Optional: `LAB_DB_HOST` (default `localhost`), `LAB_DB_NAME`
   (default `notes_lab`), `LAB_APP_ORIGIN` (default: scheme + Host header,
   e.g. `http://127.0.0.1:8000`). Do not commit credentials.
4. From this folder run:
   ```
   php -S 127.0.0.1:8000 -t public
   ```
   and visit http://127.0.0.1:8000. Note the `-t public`: only `public/` is
   served. Stop with Ctrl+C.
5. Synthetic accounts (public throwaway passwords, stored with bcrypt now):
   - `alex@example.test` / `LabOnly-Alex-123`
   - `sam@example.test` / `LabOnly-Sam-456`

   Use separate browser profiles for separate sessions.

## API

All API calls need a signed-in session cookie from the same origin.

- `GET /api.php` lists the signed-in user's notes. `?user_id=N` is accepted only
  when N is your own ID (otherwise 403).
- `POST /api.php` updates one of your own notes. Requirements:
  `Content-Type: application/json`, header `X-CSRF-Token` with the value of
  `<meta name="csrf-token">` from the main page, and a JSON object with exactly
  `id` (integer), `title` (1-120 chars), `body` (1-10000 chars).
  Responses: 200 `{"updated":1}`, 400 malformed JSON, 401 not signed in,
  403 bad token / cross-origin, 404 not found or not yours, 413 too large,
  415 wrong content type, 422 validation errors.

Example from the browser console on http://127.0.0.1:8000 while signed in:

```js
fetch('/api.php', {method: 'POST', headers: {'Content-Type': 'application/json',
  'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content},
  body: JSON.stringify({id: 1, title: 'New title', body: 'New body'})}).then(r => r.json())
```

## Tests

Re-import `schema.sql` first (the tests change data), start the server, then:

```
php tests/run_tests.php http://127.0.0.1:8000
```

To also test error handling, start a second server with a wrong DB password
and pass its URL as a second argument, e.g. in another PowerShell window:

```powershell
$env:LAB_DB_USER = 'notes_app'; $env:LAB_DB_PASSWORD = 'wrong'
php -S 127.0.0.1:8001 -t public
```
```
php tests/run_tests.php http://127.0.0.1:8000 http://127.0.0.1:8001
```

The script prints a Markdown table and saves it to `tests/results.md`. It never
prints passwords, session IDs or tokens. `tests/baseline-starter-results.md`
is the same suite run against the original starter code.

## Production notes (not covered by this local lab)

HTTPS with HSTS (the session cookie automatically becomes `Secure` and
`__Host-` prefixed when served over HTTPS), a real web server instead of
`php -S`, secrets from a secret store, login rate limiting / lockout, central
log monitoring, and dependency/PHP patching.
