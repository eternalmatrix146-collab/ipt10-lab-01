<?php
/** @var Throwable $exception */
$frames = array_slice($exception->getTrace(), 0, 25);
$file = $exception->getFile();
$line = $exception->getLine();
$snippet = [];
if (is_file($file)) {
    $lines = file($file);
    for ($i = max(1, $line - 6); $i <= min(count($lines), $line + 6); $i++) {
        $snippet[$i] = rtrim($lines[$i - 1], "\r\n");
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(class_basename($exception)) ?> · Phantom debug</title>
<style>
    :root { --red:#e60012; --black:#0b0b0b; --white:#f6f5f0; }
    * { box-sizing: border-box; }
    body { margin:0; font: 15px/1.5 Consolas, "Courier New", monospace; background: var(--black); color: var(--white); }
    header { background: var(--red); padding: 28px 32px; clip-path: polygon(0 0, 100% 0, 100% 82%, 0 100%); }
    header small { font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
    header h1 { margin: 6px 0; font: 900 30px/1.1 Impact, "Anton", sans-serif; letter-spacing: .02em; word-break: break-word; }
    header p { margin: 0; font-weight: 700; }
    main { padding: 16px 32px 48px; }
    h2 { font: 24px Impact, sans-serif; color: var(--red); letter-spacing: .05em; }
    .loc { color: #ffcf33; word-break: break-all; }
    pre { background:#151515; border-left: 6px solid var(--red); padding: 12px 0; overflow-x: auto; margin: 0; }
    .ln { display:block; padding: 0 16px; white-space: pre; }
    .ln b { display:inline-block; width: 48px; color:#666; font-weight: 400; }
    .ln.hit { background: rgba(230,0,18,.35); }
    ol { padding-left: 24px; } li { margin: 6px 0; word-break: break-all; } li span { color:#888; }
</style>
</head>
<body>
<header>
    <small>Phantom · uncaught <?= e($exception::class) ?></small>
    <h1><?= e($exception->getMessage() ?: '(no message)') ?></h1>
    <p class="loc"><?= e($file) ?>:<?= (int) $line ?></p>
</header>
<main>
    <?php if ($snippet): ?>
        <h2>Source</h2>
        <pre><?php foreach ($snippet as $n => $code): ?><span class="ln<?= $n === $line ? ' hit' : '' ?>"><b><?= $n ?></b><?= e($code) ?></span><?php endforeach; ?></pre>
    <?php endif; ?>
    <h2>Stack trace</h2>
    <ol>
        <?php foreach ($frames as $frame): ?>
            <li><?= e(($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '')) ?>()
                <span><?= e(($frame['file'] ?? '[internal]') . (isset($frame['line']) ? ':' . $frame['line'] : '')) ?></span></li>
        <?php endforeach; ?>
    </ol>
    <p style="color:#888">Debug output is shown because APP_DEBUG=true. Set it to false in production.</p>
</main>
</body>
</html>
