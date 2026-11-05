# Vanilla Laravel — Step 8 (v0.8.0)

A Laravel-inspired framework learned in small steps using PHP 8.2+ and Composer's PSR-4 autoloader. Step 8 adds PDO/MariaDB reads without third-party packages.

## What changed

`App\Database::connection()` lazily opens and reuses one PDO connection per request. It uses exceptions, associative rows, native prepared statements, and utf8mb4. For future queries with user input, always use `prepare()` and bound parameters.

GET `/database` selects sample names from `demo_users`, escapes HTML, and returns 503 with a generic message when configuration or the database is unavailable. The page performs no writes. Existing `/`, `/about`, `/users`, and GET/POST `/contact` retain their behavior; contact submissions still are not saved or emailed. No database connection is opened for those routes.

## Fedora setup

Install PHP 8.2+, Composer, MariaDB and the PDO mysql extension (Fedora: `sudo dnf install php-cli php-pdo php-mysqlnd composer mariadb-server`). Start MariaDB with `sudo systemctl enable --now mariadb`.

```bash
git pull origin main
composer install
cp .env.example .env
php -r 'var_export(PDO::getAvailableDrivers());'
```

The drivers list must include `mysql`. As a database administrator, create a dedicated database, then import the example:

```sql
CREATE DATABASE vanilla_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
mariadb -u root -p vanilla_laravel < database/schema.sql
```

Use your system's administrator login (Fedora may use `sudo mariadb` with socket authentication). In an administrator session, create a local reader; replace the password placeholder locally:

```sql
CREATE USER 'vanilla_reader'@'127.0.0.1' IDENTIFIED BY 'choose-a-local-password';
GRANT SELECT ON vanilla_laravel.demo_users TO 'vanilla_reader'@'127.0.0.1';
```

Edit `.env` to match. Keep DB_HOST as 127.0.0.1 for this TCP account. The application needs only SELECT; schema import uses a separate administrator account.

The optional root `.env` uses PHP INI syntax: one DB_KEY=value per line, with double quotes around values containing spaces or #. Values are literal; no variable interpolation or dotenv escape processing. For unusual passwords, process environment variables are an alternative. Process variables override file values, even empty strings. Only DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD are read. A missing file is allowed when these are supplied through the environment. Keep the web document root at `public/`; never serve the repository root. `.env` and local variants are ignored; only the credential-free example is tracked.

```bash
php -S localhost:8000 -t public public/index.php
```

## Verify the milestone

In another terminal:

```bash
find app public routes resources/views -name '*.php' -exec php -l {} \;
curl -i http://localhost:8000/database
# Expect 200 and Ada Lovelace / Grace Hopper after importing the schema.
for path in / /about /users /contact; do
  curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8000$path"
done
# Four 200 responses.
curl -i -d "name=Oppie&message=Hello" http://localhost:8000/contact
# 200
curl -i -d "name=&message=" http://localhost:8000/contact
# 422
curl -i -X PUT http://localhost:8000/contact
# 405
curl -i -X POST http://localhost:8000/database
# 405, Allow: GET
curl -i http://localhost:8000/missing
# 404
```

Also check an empty demo_users table in a disposable database: expect 200 and "No sample users found." Temporarily use an incorrect DB_PORT and restart the server: only /database should return 503, without raw error details; prior routes should still work. Restore configuration afterward. To check escaping, put a name such as `<b>Example</b>` in the disposable table as administrator and confirm it displays as text.

## Tag after Fedora testing

Step 8 builds on Step 7 commit `1e2091c`. After verifying this milestone on Fedora and confirming main points to the intended Step 8 commit:

```bash
git pull --ff-only origin main
git log -1 --oneline
git tag -a v0.8.0 -m "Step 8: PDO and MariaDB integration"
git push origin v0.8.0
```

The tag is intentionally left for the verified milestone. LICENSE and previous milestone code are preserved.

## Step 9 — Prepared statements (v0.9.0)

Builds on Step 8 without schema or dependency changes. Visit `/database`, click a sample name, or use `/database/user?id=1`. The GET form looks up one sample user. `Request::query()` reads query parameters separately from POST `input()`, so contact behavior stays the same.

The controller validates a canonical positive unsigned INT ID, prepares SQL containing `:id`, binds the value, then executes it. Binding keeps data separate from SQL; validation also gives useful errors. The decimal ID is bound as a string to support the full MariaDB unsigned INT range without a platform integer overflow. This milestone remains read-only and uses the existing SELECT account.

### Verify Step 9 on Fedora

Use the same setup/server commands above. Expect:

```bash
curl -i 'http://localhost:8000/database/user?id=1'       # 200, Ada Lovelace
curl -i 'http://localhost:8000/database/user?id=999999'  # 404 if absent
curl -i 'http://localhost:8000/database/user'            # 422, missing ID
curl -i 'http://localhost:8000/database/user?id=0'       # 422
curl -i 'http://localhost:8000/database/user?id[]=1' --globoff # 422
curl -i 'http://localhost:8000/database/user?id=4294967296' # 422
curl -i 'http://localhost:8000/database/user?id=1%20OR%201=1' # 422
curl -i -X POST 'http://localhost:8000/database/user?id=1' # 405
```

Repeat the Step 8 route checks. With an unavailable database, a valid lookup returns a generic 503; malformed IDs still return 422 before connecting. No schema import or data changes are needed.

After verifying the Step 9 commit on main:

```bash
git pull --ff-only origin main
git log -1 --oneline
git tag -a v0.9.0 -m "Step 9: Prepared statements and sample-user lookup"
git push origin v0.9.0
```

## Step 10 — A small model (v0.10.0)

`App\Models\DemoUser` now owns the two read-only queries. `all()` returns up to 20 rows ordered by ID; `find($id)` uses the existing prepared statement and returns one row or null when absent. Database errors still throw, allowing the controller to return the same generic 503.

The request supplies input, the controller validates it and chooses the HTTP response, the model reads database rows, and the view renders escaped HTML. This is a plain PHP model, with no ORM or new dependencies. Existing URLs, schema, SELECT-only account, and contact behavior stay the same.

### Verify Step 10 on Fedora

```bash
git pull --ff-only origin main
composer dump-autoload
find app public routes resources/views -name '*.php' -exec php -l {} \;
php -S 10.1.10.138:8181 -t public public/index.php
```

Open http://10.1.10.138:8181/database and click a sample name. Repeat Step 9's lookup checks and Step 8's prior-route checks using port 8181: expect 200 for a known user, 404 for an absent ID, 422 for invalid IDs, and 405 for POST to the lookup. An unavailable database should still produce a generic 503 while the home page works. No schema or credential changes are needed.

After verification, confirm main is at the intended Step 10 commit before tagging:

```bash
git log -1 --oneline
git tag -a v0.10.0 -m "Step 10: Extract read-only sample-user model"
git push origin v0.10.0
```

## Step 11 — Dynamic routes (v0.11.0)

Sample names now link to `/database/users/1`. Register a route using a named segment:

```php
$router->get('/database/users/{id}', [DatabaseController::class, 'show']);
```

The router calls the handler with the Request followed by an associative parameter array, such as `['id' => '1']`. The controller uses the path ID in preference to a query ID and keeps the same validation and prepared model lookup. The old `/database/user?id=1` route and its GET form remain supported.

Literal routes take precedence for the requested method. Dynamic routes are checked in registration order; each placeholder must occupy a whole segment, have a unique name, and match one nonempty segment. Captured values are URL-decoded once. Trailing slashes and extra segments do not match. Unsupported methods on matching dynamic routes return 405 with Allow. No optional segments, wildcards, or custom constraints are introduced.

### Verify Step 11

```bash
git pull --ff-only origin main
composer dump-autoload
php -S 10.1.10.138:8181 -t public public/index.php
curl -i http://10.1.10.138:8181/database/users/1          # 200
curl -i http://10.1.10.138:8181/database/users/999999     # 404 if absent
curl -i http://10.1.10.138:8181/database/users/invalid    # 422
curl -i http://10.1.10.138:8181/database/users/1/extra    # 404
curl -i -X POST http://10.1.10.138:8181/database/users/1 # 405, Allow: GET
curl -i 'http://10.1.10.138:8181/database/user?id=1'      # 200, old URL
```

Repeat the prior-route checks. Once verified on main:

```bash
git tag -a v0.11.0 -m "Step 11: Add named dynamic routes"
git push origin v0.11.0
```

## Step 12 — Middleware (v0.12.0)

Middleware wraps request handling. Implement `App\Http\Middleware::handle(Request $request, callable $next): Response`, then register an instance with `$router->middleware(...)` before dispatch. Call `$next($request)` to continue and receive the downstream Response; return a Response directly to stop the chain.

Middleware runs in registration order before the handler and reverse order afterward. The pipeline is rebuilt for each dispatch and also wraps 404/405 responses. This milestone provides global middleware only.

`ContentTypeOptions`, registered in `public/index.php`, adds `X-Content-Type-Options: nosniff` to returned responses while preserving their body, status, and existing headers. It demonstrates modifying the response after the handler. Existing URLs, validation, database reads, and contact behavior stay the same. No dependencies or schema changes are needed.

### Verify Step 12

```bash
git pull --ff-only origin main
composer dump-autoload
php tests/middleware.php
php -S 10.1.10.138:8181 -t public public/index.php
curl -i http://10.1.10.138:8181/database/users/1
curl -i http://10.1.10.138:8181/missing
curl -i -X POST http://10.1.10.138:8181/database/users/1
```

Expect 200, 404, and 405 respectively, each with the new header; the 405 must still include `Allow: GET`. Repeat the previous milestone's route checks. The middleware tests run without a database and cover ordering, repeated dispatch, stopping the pipeline, forwarding requests, and response/header preservation.

After verifying the intended Step 12 commit on main:

```bash
git log -1 --oneline
git tag -a v0.12.0 -m "Step 12: Add request-response middleware"
git push origin v0.12.0
```

## Step 13 — Sessions and flash messages (v0.13.0)

Successful contact submissions now follow POST → 303 redirect → GET /contact/success. The name and message are stored temporarily in a server-side PHP session and pulled once for confirmation. Refreshing the confirmation redirects to /contact instead of submitting the POST again. Visiting /contact/success without a pending message also redirects to the form. Invalid submissions still return 422 with escaped form values.

This supersedes earlier instructions expecting a direct 200 on a valid contact POST: expect 303 with Location: /contact/success, then 200 when following the redirect with the same session cookie. Contact messages are not emailed or written to MariaDB, but are held temporarily in PHP's session storage until consumed or cleaned up by PHP. Pending flash values are consumed when read, rather than aged on every unrelated request.

StartSession middleware starts PHP's built-in session, then saves/closes it after the handler to release the session lock. Session::flash() writes a one-time value and Session::pullFlash() reads/removes it. Response::redirect() creates a 303 response for an application-controlled destination.

The session cookie is HttpOnly, SameSite=Lax, and Secure when PHP receives HTTPS directly. On this tutorial's HTTP development server, Secure is off so the cookie works. PHP's session storage directory must be writable. No database changes or new dependencies are needed.

### Verify Step 13

```bash
git pull --ff-only origin main
composer dump-autoload
php tests/middleware.php
php tests/session.php
php -S 10.1.10.138:8181 -t public public/index.php
```

Open /contact, submit a valid message, and confirm the address becomes /contact/success. Refresh: you should return to /contact. Submit empty or oversized values: expect 422 without redirecting. Open /contact/success in a separate private browser session: expect a redirect to /contact, without another session's message. Repeat the database and dynamic-route checks.

For curl, keep cookies across the POST and GET:

```bash
cookies=$(mktemp)
curl -i -c "$cookies" -b "$cookies" -d "name=Oppie&message=Hello" http://10.1.10.138:8181/contact
# 303, Location: /contact/success
curl -i -c "$cookies" -b "$cookies" http://10.1.10.138:8181/contact/success
# 200, confirmation
curl -i -b "$cookies" http://10.1.10.138:8181/contact/success
# 303, Location: /contact
rm -f "$cookies"
```

After verifying the intended Step 13 commit on main:

```bash
git log -1 --oneline
git tag -a v0.13.0 -m "Step 13: Add sessions and one-time flash messages"
git push origin v0.13.0
```

## Step 14 — CSRF protection (v0.14.0)

The contact form now includes a hidden _token field. Csrf::token() creates a random 32-byte token (64 hexadecimal characters) stored in the session. Csrf::validate() checks the submitted value with hash_equals() before any contact validation or flash writes. Missing, malformed, incorrect, or other-session tokens return HTTP 403 with a link to load a fresh form. This check protects POST /contact; other routes keep their existing behavior.

Tokens remain stable for the session so multiple open form tabs work. They are separate from flash data and are not consumed by confirmation. Session expiry requires loading a fresh form. This follows the [OWASP synchronizer-token pattern](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#synchronizer-token-pattern); comparison uses [PHP hash_equals](https://www.php.net/hash_equals). Tokens belong in the POST body, not the URL.

A valid token plus valid fields still produces the Step 13 303 redirect and one-time confirmation. A valid token plus invalid fields returns 422, retains escaped input, and includes the same token for retry. Earlier bare curl contact POST examples now return 403 unless they include a token fetched with the same session cookie. No dependencies, credentials, or database changes are introduced.

### Verify Step 14

```bash
git pull --ff-only origin main
composer dump-autoload
php tests/middleware.php
php tests/session.php
php tests/csrf.php
php -S 10.1.10.138:8181 -t public public/index.php
```

Reload /contact to get a fresh form. Submit normally: expect the confirmation redirect. In browser developer tools, remove or change the hidden _token field, then submit: expect 403 and no new confirmation. Reopen the form and submit empty or oversized fields with its valid token: expect 422. Open the form in a private browser session: its token must not work with the original session cookie. Database pages, dynamic routes, and 404/405 responses should still work.

For curl, fetch a form using a cookie jar, then extract and submit the hidden token:

```bash
cookies=$(mktemp)
form=$(curl -s -c "$cookies" -b "$cookies" http://10.1.10.138:8181/contact)
token=$(printf '%s' "$form" | sed -n 's/.*name="_token" value="\([a-f0-9]*\)".*/\1/p')
curl -i -c "$cookies" -b "$cookies" --data-urlencode "_token=$token" --data-urlencode "name=Oppie" --data-urlencode "message=Hello" http://10.1.10.138:8181/contact
# 303, Location: /contact/success
curl -i -b "$cookies" http://10.1.10.138:8181/contact/success
# 200, one-time confirmation
rm -f "$cookies"
```

After verifying the intended Step 14 commit on main:

```bash
git log -1 --oneline
git tag -a v0.14.0 -m "Step 14: Add session-backed CSRF form protection"
git push origin v0.14.0
```

## Step 15 — Reusable validation (v0.15.0)

Validator::validate($data, $rules) now handles text-field rules independently of HTTP, sessions, and views. It returns an empty array on success, or errors keyed by field and rule. Supported rules are required and max (a nonnegative character limit); non-text values receive a string error. Required rejects missing, null, empty, or whitespace-only values, while the text "0" is valid.

```php
$errors = Validator::validate(
    ['title' => 'My book'],
    ['title' => ['required' => true, 'max' => 100]],
);
// [] on success; otherwise, for example:
// ['title' => ['required' => 'This field is required.']]
```

Callers normalize input (the contact controller still trims strings). Validation does not alter input, escape HTML, write sessions, or choose HTTP responses. Lengths use mb_strlen(), as before; PHP's mbstring extension is required. Unknown fields are ignored unless a rule is provided.

The contact controller now declares its two fields' rules and chooses its existing summary message from the results. Required errors still take priority over length errors. CSRF is checked first (403), invalid fields still return 422 with escaped input and a retry token, and valid submissions still redirect with 303 to the one-time confirmation. No routes, schemas, credentials, or dependencies change.

### Verify Step 15

```bash
git pull --ff-only origin main
composer dump-autoload
php tests/validator.php
php tests/middleware.php
php tests/session.php
php tests/csrf.php
php -S 10.1.10.138:8181 -t public public/index.php
```

Reload /contact before testing. Submit valid fields, missing fields, and values at/over the 100-character name and 2000-character message limits. Limits count Unicode characters rather than bytes. With a valid token, missing/oversized fields should produce the same 422 messages as before; missing/incorrect tokens must still produce 403. Repeat the database and dynamic-route checks.

After verifying the intended Step 15 commit on main:

```bash
git log -1 --oneline
git tag -a v0.15.0 -m "Step 15: Extract reusable text-field validation"
git push origin v0.15.0
```
