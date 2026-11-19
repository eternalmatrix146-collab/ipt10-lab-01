<?php

declare(strict_types=1);

namespace Phantom\Console;

/**
 * Minimal command dispatcher for the `php phantom <command>` CLI.
 */
class Console
{
    /** @var array<string, array{description: string, handler: callable}> */
    private array $commands = [];

    /** @param callable(array<int, string>, Console): int $handler */
    public function command(string $name, string $description, callable $handler): void
    {
        $this->commands[$name] = ['description' => $description, 'handler' => $handler];
    }

    /** @param array<int, string> $argv */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'list';
        $args = array_slice($argv, 2);

        if ($name === 'list' || $name === 'help' || !isset($this->commands[$name])) {
            if (!in_array($name, ['list', 'help'], true)) {
                $this->error("Unknown command [{$name}].");
            }
            $this->printList();
            return $name === 'list' || $name === 'help' ? 0 : 1;
        }

        try {
            return (int) ($this->commands[$name]['handler'])($args, $this);
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            return 1;
        }
    }

    private function printList(): void
    {
        $this->line("\033[1;31mPHANTOM\033[0m framework CLI\n");
        $this->line('Usage: php phantom <command> [options]' . "\n");
        $width = max(array_map('strlen', array_keys($this->commands)) ?: [10]) + 2;
        foreach ($this->commands as $name => $command) {
            $this->line('  ' . "\033[33m" . str_pad($name, $width) . "\033[0m" . $command['description']);
        }
    }

    public function line(string $text = ''): void
    {
        fwrite(STDOUT, $text . PHP_EOL);
    }

    public function info(string $text): void
    {
        $this->line("\033[32m{$text}\033[0m");
    }

    public function error(string $text): void
    {
        fwrite(STDERR, "\033[31m{$text}\033[0m" . PHP_EOL);
    }
}
