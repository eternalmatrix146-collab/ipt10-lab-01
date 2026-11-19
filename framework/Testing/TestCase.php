<?php

declare(strict_types=1);

namespace Phantom\Testing;

use Throwable;

/**
 * Base class for unit tests run by `php phantom test`.
 * Every public method whose name starts with "test" is a test.
 */
abstract class TestCase
{
    private int $assertions = 0;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function assertionCount(): int
    {
        return $this->assertions;
    }

    protected function assertTrue(mixed $condition, string $message = 'Expected value to be true'): void
    {
        $this->assertions++;
        if ($condition !== true) {
            throw new AssertionFailed($message);
        }
    }

    protected function assertFalse(mixed $condition, string $message = 'Expected value to be false'): void
    {
        $this->assertions++;
        if ($condition !== false) {
            throw new AssertionFailed($message);
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new AssertionFailed($message ?: 'Expected ' . $this->export($expected) . ' but got ' . $this->export($actual));
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected != $actual) {
            throw new AssertionFailed($message ?: 'Expected ' . $this->export($expected) . ' but got ' . $this->export($actual));
        }
    }

    protected function assertNull(mixed $value, string $message = 'Expected null'): void
    {
        $this->assertSame(null, $value, $message);
    }

    protected function assertNotNull(mixed $value, string $message = 'Expected a non-null value'): void
    {
        $this->assertions++;
        if ($value === null) {
            throw new AssertionFailed($message);
        }
    }

    /** @param array<mixed>|\Countable $haystack */
    protected function assertCount(int $expected, array|\Countable $haystack, string $message = ''): void
    {
        $this->assertSame($expected, count($haystack), $message ?: "Expected count {$expected}, got " . count($haystack));
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailed($message ?: "Failed asserting that the string contains \"{$needle}\".\nActual: " . mb_substr($haystack, 0, 300));
        }
    }

    protected function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new AssertionFailed($message ?: "Failed asserting that the string does not contain \"{$needle}\".");
        }
    }

    /** @param class-string<Throwable> $class */
    protected function assertThrows(string $class, callable $callback, string $message = ''): Throwable
    {
        $this->assertions++;
        try {
            $callback();
        } catch (Throwable $e) {
            if ($e instanceof $class) {
                return $e;
            }
            throw new AssertionFailed($message ?: "Expected {$class}, got " . $e::class . ': ' . $e->getMessage());
        }
        throw new AssertionFailed($message ?: "Expected {$class} to be thrown, nothing was thrown.");
    }

    private function export(mixed $value): string
    {
        $out = var_export($value, true);
        return strlen($out) > 300 ? substr($out, 0, 300) . '…' : $out;
    }
}
