# Campus Notes security test results

Run: 2026-11-06 10:38 UTC against http://127.0.0.1:8000 and http://127.0.0.1:8001
PHP 8.3.6, DB 10.11.14-MariaDB-0ubuntu0.24.04.1

| ID | Area | Check | Expected | Actual | Result |
|---|---|---|---|---|---|
| L1 | Login | Valid login (Alex) | 303 redirect, page shows "Signed in as alex" | HTTP 303; signed-in text shown | PASS |
| L2 | Session | Session ID changes at login (fixation) | Different session ID before/after login | ID changed (values not printed) | PASS |
| L3 | Login | Wrong password | Not signed in; generic error | Not signed in; generic message shown | PASS |
| L4 | SQL injection | Login with ' OR '1'='1' -- and alex@example.test' --  | Not signed in | Not signed in | PASS |
| L5 | Passwords | Stored hashes use password_hash() | No MD5; all bcrypt/argon2 | 2 hashes checked; all password_hash() format | PASS |
| L6 | Role | Register while posting role=admin | Account created with role student | role=student, hash=$2y$... | PASS |
| L7 | Passwords | Register with 5-char password | Rejected, no account | account not created | PASS |
| L8 | Session | Session cookie flags | HttpOnly; SameSite=Strict | HttpOnly SameSite=Strict | PASS |
| A1 | Authorization | Alex's note list | Only Alex's notes, none of Sam's | Only Alex's notes listed | PASS |
| A2 | Authorization | Sam's note list (second session) | Only Sam's notes | Only Sam's notes listed | PASS |
| A3 | Authorization | Alex opens ?edit=3 (Sam's note) | 404, Sam's text not shown | HTTP 404; Sam's text not shown | PASS |
| A4 | Authorization | Alex POSTs update for note 3 (Sam's) with valid token | 404, note unchanged | HTTP 404; note 3 unchanged | PASS |
| A5 | Authorization | Alex POSTs delete for note 3 (Sam's) with valid token | 404, note still exists | HTTP 404; note 3 exists | PASS |
| A6 | Owner | Alex creates note while posting user_id=2 | Note owned by Alex (1) | owner user_id=1 | PASS |
| A7 | CRUD | Alex updates own note 1 | Update succeeds | HTTP 303; body updated | PASS |
| A8 | CRUD | Alex deletes own note 2 | Delete succeeds | HTTP 303; note 2 deleted | PASS |
| X1 | XSS | Note with <script> title and <img onerror> body | Rendered as escaped text | escaped (&lt;script&gt;...) | PASS |
| X2 | XSS | Edit form with "> and </textarea> breakout payloads | Attribute/textarea stay escaped | escaped | PASS |
| X3 | XSS | GET /?message=<script>… | Not reflected | not reflected | PASS |
| X4 | XSS | Failed login with HTML in e-mail | E-mail not echoed | not echoed | PASS |
| X5 | XSS | Content-Security-Policy header | Present with script-src 'self' | present | PASS |
| C1 | CSRF | Save without CSRF token | 403, nothing saved | HTTP 403; notes unchanged | PASS |
| C2 | CSRF | Save with wrong CSRF token | 403, nothing saved | HTTP 403; notes unchanged | PASS |
| C3 | CSRF | Valid token but Origin: http://evil.example | 403, nothing saved | HTTP 403; notes unchanged | PASS |
| C4 | CSRF | Old GET link /?delete=1 | Ignored, note 1 still exists | note 1 exists | PASS |
| C5 | CSRF | Old GET link /?logout=1 | Ignored, still signed in | still signed in | PASS |
| C6 | CSRF | Logout POST without token | 403, still signed in | HTTP 403 | PASS |
| P1 | API auth | GET /api.php?user_id=1 with no session | 401, no notes | HTTP 401; no notes | PASS |
| P2 | API auth | POST update with no session | 401, note unchanged | HTTP 401 | PASS |
| P3 | API authz | Alex GET /api.php | 200, only Alex's notes | HTTP 200; owners=1 | PASS |
| P4 | API authz | Alex GET ?user_id=2 (Sam's ID) | 403, no Sam notes | HTTP 403 | PASS |
| P5 | API authz | Alex POST update for note 3 (Sam's) | 404, note unchanged | HTTP 404; note 3 unchanged | PASS |
| P6 | API validation | Malformed JSON body | 400 Malformed JSON, no PHP warning | HTTP 400 {"error":"Malformed JSON"} | PASS |
| P7 | API validation | JSON array instead of object | 400 | HTTP 400 | PASS |
| P8 | API validation | Wrong types / missing body | 422 with details | HTTP 422 | PASS |
| P9 | API validation | Title 121 characters | 422 | HTTP 422 | PASS |
| P10 | API validation | Extra field user_id:2 | 422 (owner cannot be set) | HTTP 422 | PASS |
| P11 | API validation | Content-Type: text/plain (simple-request CSRF shape) | 415, unchanged | HTTP 415 | PASS |
| P12 | API CSRF | POST without X-CSRF-Token | 403, unchanged | HTTP 403 | PASS |
| P13 | API CORS | GET from Origin: http://evil.example | 403; no Access-Control-Allow-Origin | HTTP 403; ACAO=none | PASS |
| P14 | API CORS | CORS preflight from evil origin | Rejected; no ACAO / Allow-Credentials | HTTP 403; ACAO=none | PASS |
| P15 | API CORS | Same-site, different port (Origin: http://127.0.0.1:3000) | 403, unchanged | HTTP 403 | PASS |
| P16 | API CORS | Sec-Fetch-Site: cross-site, no Origin | 403 | HTTP 403 | PASS |
| P17 | API CRUD | Alex POST valid update of own note 1 | 200 {"updated":1}, DB changed | HTTP 200 {"updated":1} | PASS |
| P18 | API | PUT /api.php | 405 JSON error | HTTP 405 | PASS |
| O1 | Logout | POST logout with token | Signed out, sign-in form shown | signed out | PASS |
| O2 | Logout | Replay old session cookie after logout | Not signed in; API 401 | page signed out; API HTTP 401 | PASS |
| E1 | Config | Request /src/database.php and /schema.sql (outside public/) | 404 for both, no file content | HTTP 404 and 404; no file content | PASS |
| E2 | Errors | Server started with wrong DB password: GET / | 500 generic message, no internals | HTTP 500; generic message only | PASS |
| E3 | Errors | Same, GET /api.php (no session) | Generic JSON error, no internals | HTTP 401 {"error":"Authentication required"} | PASS |

**50 passed, 0 failed, 0 not run.**
