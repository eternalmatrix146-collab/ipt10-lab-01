<?php

declare(strict_types=1);

require_once __DIR__ . '/NotifierDecorator.php';

final class SmsDecorator extends NotifierDecorator
{
    public function __construct(Notifier $notifier, private readonly string $phoneNumber)
    {
        parent::__construct($notifier);
    }

    public function send(string $message): void
    {
        printf('SMS sent to %s: %s%s', $this->phoneNumber, $message, PHP_EOL);
        parent::send($message);
    }
}
