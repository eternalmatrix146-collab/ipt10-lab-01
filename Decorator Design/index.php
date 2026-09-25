<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Notifier.php';
require_once __DIR__ . '/src/EmailNotifier.php';
require_once __DIR__ . '/src/NotifierDecorator.php';
require_once __DIR__ . '/src/LoggingDecorator.php';
require_once __DIR__ . '/src/SmsDecorator.php';

$notifier = new EmailNotifier('student@example.edu');
$notifier = new LoggingDecorator($notifier);
$notifier = new SmsDecorator($notifier, '+63 912 345 6789');

$notifier->send('Your class starts at 8:00 AM.');
