<?php
$nameErrors = $errors['name'] ?? [];
$messageErrors = $errors['message'] ?? [];
?>
<h1>Contact</h1>
<p>Messages are shown once for confirmation; they are not emailed or added to the database.</p>
<?php if (!empty($error)): ?>
    <p role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php endif; ?>
<form action="/contact" method="post">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <p>
        <label for="name">Name</label>
        <input id="name" name="name" maxlength="100" required<?php if ($nameErrors !== []): ?> aria-invalid="true" aria-describedby="name-error"<?php endif; ?> value="<?= htmlspecialchars((string) ($name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </p>
    <?php if ($nameErrors !== []): ?>
        <p id="name-error"><strong>Error:</strong> <?= htmlspecialchars(implode(' ', $nameErrors), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
    <p>
        <label for="message">Message</label>
        <textarea id="message" name="message" maxlength="2000" required<?php if ($messageErrors !== []): ?> aria-invalid="true" aria-describedby="message-error"<?php endif; ?>><?= htmlspecialchars((string) ($message ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
    </p>
    <?php if ($messageErrors !== []): ?>
        <p id="message-error"><strong>Error:</strong> <?= htmlspecialchars(implode(' ', $messageErrors), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
    <button type="submit">Submit</button>
</form>
