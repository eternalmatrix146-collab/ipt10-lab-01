<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#e60012">
<title><?= e(($title ?? null) ? $title . ' · ' . config('app.name') : config('app.name')) ?></title>
<link rel="icon" href="<?= asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600;700&family=Bebas+Neue&family=Courier+Prime:wght@700&family=Permanent+Marker&family=Playfair+Display:wght@900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
