<?php
/**
 * Main page template. Every dynamic value goes through e() (issue 4).
 * Variables: $messages, $userId, $email, $role, $notes, $editing, $csrfToken
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e($csrfToken) ?>">
<title>Campus Notes</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="topbar">
  <h1 class="brand"><a href="/">Campus Notes</a></h1>
  <?php if ($userId !== null): ?>
  <div class="account">
    <span>Signed in as <?= e($email) ?> (<?= e($role) ?>)</span>
    <form method="post" action="/">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
      <button class="btn btn-quiet" name="action" value="logout">Log out</button>
    </form>
  </div>
  <?php endif; ?>
</header>

<?php if ($messages): ?>
<div class="flashes" role="status">
  <?php foreach ($messages as $m): ?>
    <p class="message"><?= e($m) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($userId === null): ?>
<main class="auth">
  <section class="sheet auth-sheet">
    <h2>Sign in or create an account</h2>
    <form method="post" action="/" class="stack">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
      <label>Email
        <input name="email" type="email" maxlength="254" required autocomplete="username">
      </label>
      <label>Password
        <input name="password" type="password" minlength="12" maxlength="72" required autocomplete="current-password">
      </label>
      <p class="hint">New accounts need a password of at least 12 characters.</p>
      <div class="actions">
        <button class="btn btn-primary" name="action" value="login">Sign in</button>
        <button class="btn btn-secondary" name="action" value="register">Register</button>
      </div>
    </form>
  </section>
</main>
<?php else: ?>
<main class="workspace">
  <aside class="composer">
    <?php if ($editing): ?>
    <section class="sheet editing">
      <h2>Edit note</h2>
      <form method="post" action="/" class="stack">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <label>Title <input name="title" value="<?= e($editing['title']) ?>" maxlength="120" required></label>
        <label>Body <textarea name="body" rows="6" required><?= e($editing['body']) ?></textarea></label>
        <div class="actions">
          <button class="btn btn-primary" name="action" value="update">Update</button>
          <a class="btn btn-secondary" href="/">Cancel</a>
        </div>
      </form>
    </section>
    <?php endif; ?>

    <section class="sheet">
      <h2>Create a note</h2>
      <form method="post" action="/" class="stack">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <label>Title <input name="title" maxlength="120" required></label>
        <label>Body <textarea name="body" rows="6" required></textarea></label>
        <div class="actions">
          <button class="btn btn-primary" name="action" value="save">Save</button>
        </div>
      </form>
    </section>
  </aside>

  <section class="notes" aria-labelledby="notes-heading">
    <h2 id="notes-heading">My notes <span class="count"><?= e(count($notes)) ?></span></h2>
    <?php if (!$notes): ?>
      <p class="empty">No notes yet. Your first note will show up here.</p>
    <?php endif; ?>
    <?php foreach ($notes as $note): ?>
    <article class="note<?= ($editing && (int) $editing['id'] === (int) $note['id']) ? ' is-editing' : '' ?>">
      <h3><?= e($note['title']) ?></h3>
      <p class="body"><?= e($note['body']) ?></p>
      <div class="note-actions">
        <a class="btn btn-quiet" href="/?edit=<?= e(rawurlencode((string) $note['id'])) ?>">Edit</a>
        <form method="post" action="/">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="id" value="<?= e($note['id']) ?>">
          <button class="btn btn-danger" name="action" value="delete">Delete</button>
        </form>
      </div>
    </article>
    <?php endforeach; ?>
  </section>
</main>
<?php endif; ?>
</body>
</html>
