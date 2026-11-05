<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
</head>
<body>
    <header>
        <p><strong><?= htmlspecialchars((string) $appName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></p>
        <nav aria-label="Main navigation">
            <a href="/">Home</a> |
            <a href="/about">About</a> |
            <a href="/users">Users</a> |
            <a href="/contact">Contact</a> |
            <a href="/database">Database demo</a>
        </nav>
    </header>
    <main>
        <?= $content ?>
    </main>
</body>
</html>
