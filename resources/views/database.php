<h1>Database demo</h1>
<?php if (!empty($notice)): ?>
    <p role="status"><?= htmlspecialchars((string) $notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php endif; ?>
<p>Sample users from MariaDB, 20 per page, newest first.</p>
<p><a href="/database/users/create">New user</a></p>
<?php if (!empty($error)): ?>
    <p role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <p><a href="/database">Back to the first page</a></p>
<?php elseif ($unavailable): ?>
    <p>Database unavailable. Check your local configuration, PDO mysql driver, and imported schema using the README.</p>
<?php elseif ($users === []): ?>
    <p>No sample users found.</p>
<?php else: ?>
    <ul>
        <?php foreach ($users as $user): ?>
            <li><a href="/database/users/<?= (int) $user['id'] ?>"><?= htmlspecialchars((string) $user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (isset($pagination)): ?>
    <p><?= (int) $pagination['total'] ?> sample users.</p>
    <nav aria-label="User list pages">
        <?php if ($pagination['page'] > 1): ?>
            <a rel="prev" href="/database?page=<?= (int) $pagination['page'] - 1 ?>">Previous</a>
        <?php endif; ?>
        <span aria-current="page">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['lastPage'] ?></span>
        <?php if ($pagination['page'] < $pagination['lastPage']): ?>
            <a rel="next" href="/database?page=<?= (int) $pagination['page'] + 1 ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
