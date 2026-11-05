<h1>Sample user</h1>
<form action="/database/user" method="get">
    <label for="user-id">User ID</label>
    <input id="user-id" name="id" type="number" min="1" max="4294967295" required>
    <button type="submit">Find user</button>
</form>
<?php if ($message !== null): ?>
    <p><?= htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php elseif ($user !== null): ?>
    <p>ID: <?= (int) $user['id'] ?></p>
    <p>Name: <?= htmlspecialchars((string) $user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php endif; ?>
<p><a href="/database">Back to sample users</a></p>
