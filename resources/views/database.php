<h1>Database demo</h1>
<p>Step 8 reads up to 20 sample users from MariaDB. This page does not write data.</p>
<?php if ($unavailable): ?>
    <p>Database unavailable. Check your local configuration, PDO mysql driver, and imported schema using the README.</p>
<?php elseif ($users === []): ?>
    <p>No sample users found.</p>
<?php else: ?>
    <ul>
        <?php foreach ($users as $user): ?>
            <li><a href="/database/user?id=<?= (int) $user['id'] ?>"><?= htmlspecialchars((string) $user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
