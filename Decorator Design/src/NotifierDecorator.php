<?php

declare(strict_types=1);

require_once __DIR__ . '/Notifier.php';

abstract class NotifierDecorator implements Notifier
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    public function send(string $message): void
    {
        $this->notifier->send($message);
    }
}
