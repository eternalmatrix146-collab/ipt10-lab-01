<?php

declare(strict_types=1);

require_once __DIR__ . '/Notifier.php';

final class EmailNotifier implements Notifier
{
    public function __construct(private readonly string $recipient)
    {
    }

    public function send(string $message): void
    {
        printf('Email sent to %s: %s%s', $this->recipient, $message, PHP_EOL);
    }
}
