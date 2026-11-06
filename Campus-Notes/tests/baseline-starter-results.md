# Campus Notes security test results

Run: 2026-11-06 09:58 UTC against http://127.0.0.1:8002
PHP 8.3.6, DB 10.11.14-MariaDB-0ubuntu0.24.04.1

| ID | Area | Check | Expected | Actual | Result |
|---|---|---|---|---|---|
| L1 | Login | Valid login (Alex) | 303 redirect, page shows "Signed in as alex" | HTTP 302; signed-in text missing | FAIL |
| L2 | Session | Session ID changes at login (fixation) | Different session ID before/after login | ID unchanged | FAIL |
| L3 | Login | Wrong password | Not signed in; generic error | Not signed in; generic message shown | FAIL |
| L4 | SQL injection | Login with ' OR '1'='1' -- and alex@example.test' --  | Not signed in | Not signed in | PASS |
| L5 | Passwords | Stored hashes use password_hash() | No MD5; all bcrypt/argon2 | 2 hashes checked; MD5 or unknown found | FAIL |
| L6 | Role | Register while posting role=admin | Account created with role student | role=admin, hash=d599... | FAIL |
| L7 | Passwords | Register with 5-char password | Rejected, no account | account not created | PASS |
| L8 | Session | Session cookie flags | HttpOnly; SameSite=Strict | no HttpOnly no SameSite=Strict | FAIL |
| A1 | Authorization | Alex's note list | Only Alex's notes, none of Sam's | Sam's notes visible | FAIL |
| A2 | Authorization | Sam's note list (second session) | Only Sam's notes | Alex's notes visible | FAIL |
| A3 | Authorization | Alex opens ?edit=3 (Sam's note) | 404, Sam's text not shown | HTTP 200; Sam's text SHOWN | FAIL |
| A4 | Authorization | Alex POSTs update for note 3 (Sam's) with valid token | 404, note unchanged | HTTP 302; note 3 CHANGED | FAIL |
| A5 | Authorization | Alex POSTs delete for note 3 (Sam's) with valid token | 404, note still exists | HTTP 200; note 3 exists | FAIL |
| A6 | Owner | Alex creates note while posting user_id=2 | Note owned by Alex (1) | owner user_id=2 | FAIL |
| A7 | CRUD | Alex updates own note 1 | Update succeeds | HTTP 302; body updated | FAIL |
| A8 | CRUD | Alex deletes own note 2 | Delete succeeds | HTTP 200; note 2 still exists | FAIL |
| X1 | XSS | Note with <script> title and <img onerror> body | Rendered as escaped text | escaped (&lt;script&gt;...) | FAIL |
| X2 | XSS | Edit form with "> and </textarea> breakout payloads | Attribute/textarea stay escaped | BREAKOUT | FAIL |
| X3 | XSS | GET /?message=<script>… | Not reflected | REFLECTED | FAIL |
| X4 | XSS | Failed login with HTML in e-mail | E-mail not echoed | not echoed | PASS |
| X5 | XSS | Content-Security-Policy header | Present with script-src 'self' | missing | FAIL |
| C1 | CSRF | Save without CSRF token | 403, nothing saved | HTTP 200; notes unchanged | FAIL |
| C2 | CSRF | Save with wrong CSRF token | 403, nothing saved | HTTP 200; notes unchanged | FAIL |
| C3 | CSRF | Valid token but Origin: http://evil.example | 403, nothing saved | HTTP 200; notes unchanged | FAIL |
| C4 | CSRF | Old GET link /?delete=1 | Ignored, note 1 still exists | DELETED | FAIL |
| C5 | CSRF | Old GET link /?logout=1 | Ignored, still signed in | signed out | FAIL |
| C6 | CSRF | Logout POST without token | 403, still signed in | HTTP 200 | FAIL |
| P1 | API auth | GET /api.php?user_id=1 with no session | 401, no notes | HTTP 200; no notes | FAIL |
| P2 | API auth | POST update with no session | 401, note unchanged | HTTP 200 | FAIL |
| P3 | API authz | Alex GET /api.php | 200, only Alex's notes | HTTP 200; owners= | FAIL |
| P4 | API authz | Alex GET ?user_id=2 (Sam's ID) | 403, no Sam notes | HTTP 200 | FAIL |
| P5 | API authz | Alex POST update for note 3 (Sam's) | 404, note unchanged | HTTP 200; note 3 CHANGED | FAIL |
| P6 | API validation | Malformed JSON body | 400 Malformed JSON, no PHP warning | HTTP 200 <br /> <b>Warning</b>:  Trying to access array offset on null in <b>/home/claude/src/Insecure-PHP-Notes-Lab/api.php</b> on line <b>27</b><br /> <br /> <b>Warning</b>:  Trying to access array offset on null in <b>/home/claude/src/Insecure-PHP-Notes-Lab/api.php</b> on line <b>27</b><br /> <br /> <b>Warning</b>:  Trying to access array offset on null in <b>/home/claude/src/Insecure-PHP-Notes-Lab/api.php</b> on line <b>27</b><br /> {"updated":0} | FAIL |
| P7 | API validation | JSON array instead of object | 400 | HTTP 200 | FAIL |
| P8 | API validation | Wrong types / missing body | 422 with details | HTTP 200 | FAIL |
| P9 | API validation | Title 121 characters | 422 | HTTP 200 | FAIL |
| P10 | API validation | Extra field user_id:2 | 422 (owner cannot be set) | HTTP 200 | FAIL |
| P11 | API validation | Content-Type: text/plain (simple-request CSRF shape) | 415, unchanged | HTTP 200 | FAIL |
| P12 | API CSRF | POST without X-CSRF-Token | 403, unchanged | HTTP 200 | FAIL |
| P13 | API CORS | GET from Origin: http://evil.example | 403; no Access-Control-Allow-Origin | HTTP 200; ACAO=http://evil.example | FAIL |
| P14 | API CORS | CORS preflight from evil origin | Rejected; no ACAO / Allow-Credentials | HTTP 405; ACAO=http://evil.example | FAIL |
| P15 | API CORS | Same-site, different port (Origin: http://127.0.0.1:3000) | 403, unchanged | HTTP 200 | FAIL |
| P16 | API CORS | Sec-Fetch-Site: cross-site, no Origin | 403 | HTTP 200 | FAIL |
| P17 | API CRUD | Alex POST valid update of own note 1 | 200 {"updated":1}, DB changed | HTTP 200 {"updated":0} | FAIL |
| P18 | API | PUT /api.php | 405 JSON error | HTTP 405 | PASS |
| O1 | Logout | POST logout with token | Signed out, sign-in form shown | signed out | PASS |
| O2 | Logout | Replay old session cookie after logout | Not signed in; API 401 | page signed out; API HTTP 200 | FAIL |
| E1 | Config | Request /src/database.php | Not served (404) | HTTP 404 | PASS |
| E2 | Errors | Server with wrong DB password: GET / | 500 generic message, no internals | Second server URL not given | NOT RUN |

**6 passed, 42 failed, 1 not run.**
