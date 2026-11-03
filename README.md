# Vanilla Laravel

An educational framework built from vanilla PHP to learn Laravel-style architecture. This is an independent learning project, with no Laravel or Symfony dependency.

## Requirements and setup

- PHP 8.5.12 (the milestone's tested version), including the CLI.
- No Composer packages or database are needed yet.

From a terminal:

```sh
git clone https://github.com/oppiet30/vanilla-laravel.git
cd vanilla-laravel
php -v
php -S localhost:8000 -t public public/index.php
```

Open <http://localhost:8000/>. Stop the server with Ctrl+C. Run commands from the repository root. The explicit router script ensures paths such as `/hello/Ada` reach the front controller. This development command routes every request through PHP; static asset serving is left for a later milestone. Use PHP's built-in server for local development only.

## Milestone 1: front controller and routing

Implemented:

- `public/index.php` loads the router, controller and route definitions, then outputs the dispatched response.
- `App\Router` registers GET and POST routes with closures or `[Controller::class, 'method']` handlers. Controller instances are created when dispatched.
- `{name}` parameters match one nonempty path segment and are passed to handlers in path order. Parameter names use letters, digits and underscores, beginning with a letter or underscore.
- Query strings do not affect routing. Parameter values are URL-decoded once after matching; `+` stays a plus sign.
- Routes match exactly, in registration order, including case and trailing slashes. Unmatched paths and methods return HTTP 404 with `404 - Page Not Found`.
- Automated tests cover controller dispatch, GET/POST, parameters, escaping, literal route punctuation and 404 behavior.

Try the sample routes:

```sh
curl http://localhost:8000/
curl http://localhost:8000/hello
curl 'http://localhost:8000/hello/Ada%20Lovelace?example=1'
curl -X POST http://localhost:8000/hello/Ada
curl -i http://localhost:8000/missing
```

The POST example only returns a greeting; it does not save data. Views, middleware, sessions, CSRF protection and database access are future milestones.

## Project structure

```text
app/
  Controllers/HomeController.php
  Middleware/                  # Future middleware
  Models/                      # Future models
  Services/                    # Future services
  Router.php
bootstrap/                     # Future application bootstrap
config/                        # Future configuration
database/migrations/           # Future migrations
public/index.php               # Web entry point; document root is public/
resources/views/
  layouts/
  users/
routes/web.php                 # Route registrations
storage/                       # Future runtime storage
tests/routing.php
```

Empty directories use `.gitkeep` files so Git tracks them. Explicit PHP includes keep this milestone dependency-free. Milestone 2 will introduce Composer and PSR-4 autoloading.

## Tests

```sh
php -d error_reporting=E_ALL -d display_errors=1 tests/routing.php
```

The runner exits nonzero on failure and prints the passing test count on success. It needs no testing package or running web server.

Milestone 1 is proposed on a feature branch for review. No release tag has been created; a `v0.1.0` tag can follow approval and merge.

## License

MIT License, copyright (c) 2026 Robert Hinson. See [LICENSE](LICENSE); the original license is preserved unchanged.
