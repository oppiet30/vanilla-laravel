<h1>Message received</h1>
<p>Thanks, <?= htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
<p>Your message (not emailed or added to the database):</p>
<blockquote><?= nl2br(htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></blockquote>
<p><a href="/contact">Back to contact form</a></p>
