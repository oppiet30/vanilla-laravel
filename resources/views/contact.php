<h1>Contact</h1>
<p>Messages are shown once for confirmation; they are not emailed or added to the database.</p>
<?php if (!empty($error)): ?>
    <p role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php endif; ?>
<form action="/contact" method="post">
    <p>
        <label for="name">Name</label>
        <input id="name" name="name" maxlength="100" required value="<?= htmlspecialchars((string) ($name ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </p>
    <p>
        <label for="message">Message</label>
        <textarea id="message" name="message" maxlength="2000" required><?= htmlspecialchars((string) ($message ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
    </p>
    <button type="submit">Submit</button>
</form>
