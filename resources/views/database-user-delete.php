<h1>Delete sample user</h1>
<p>Delete <?= htmlspecialchars((string) $user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (ID <?= (int) $user['id'] ?>)? This cannot be undone.</p>
<form action="/database/users/<?= (int) $user['id'] ?>/delete" method="post">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <button type="submit">Yes, delete user</button>
</form>
<p><a href="/database/users/<?= (int) $user['id'] ?>">Cancel</a></p>
