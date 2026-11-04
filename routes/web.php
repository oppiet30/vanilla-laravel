<?php

declare(strict_types=1);

$router->get('/', static fn (): string =>
    '<h1>Welcome to Vanilla Laravel</h1><p>Our router is working!</p>'
);

$router->get('/about', static fn (): string =>
    '<h1>About</h1><p>This application uses vanilla PHP routing.</p>'
);

$router->get('/users', static fn (): string =>
    '<h1>Users</h1><p>User management will come later.</p>'
);
