<?php

declare(strict_types=1);

require_once __DIR__ . '/NotifierDecorator.php';

final class LoggingDecorator extends NotifierDecorator
{
    public function send(string $message): void
    {
        printf('[LOG] Notification requested: %s%s', $message, PHP_EOL);
        parent::send($message);
        printf('[LOG] Notification completed%s', PHP_EOL);
    }
}
