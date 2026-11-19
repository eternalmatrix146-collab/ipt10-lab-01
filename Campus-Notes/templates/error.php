<?php /* Variables: $message */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Campus Notes</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="topbar">
  <h1 class="brand"><a href="/">Campus Notes</a></h1>
</header>
<main class="auth">
  <section class="sheet auth-sheet">
    <p class="message"><?= e($message) ?></p>
    <div class="actions">
      <a class="btn btn-primary" href="/">Back to notes</a>
    </div>
  </section>
</main>
</body>
</html>
