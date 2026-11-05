<?php $nameErrors = $errors['name'] ?? []; ?>
<h1>New sample user</h1>
<p>Add a sample name to the database. This does not create a login account.</p>
<?php if (!empty($error)): ?>
    <p role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php endif; ?>
<form action="/database/users" method="post">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <p>
        <label for="name">Name</label>
        <input id="name" name="name" maxlength="100" required<?php if ($nameErrors !== []): ?> aria-invalid="true" aria-describedby="name-error"<?php endif; ?> value="<?= htmlspecialchars((string) ($name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </p>
    <?php if ($nameErrors !== []): ?>
        <p id="name-error"><strong>Error:</strong> <?= htmlspecialchars(implode(' ', $nameErrors), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
    <button type="submit">Create user</button>
</form>
<p><a href="/database">Back to sample users</a></p>
