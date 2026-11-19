<?php

declare(strict_types=1);

namespace Phantom\Testing;

use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Discovers tests/**\/*Test.php, runs each test method in a fresh instance,
 * and prints a coloured report. Returns a non-zero exit code on failure.
 */
class TestRunner
{
    private int $passed = 0;

    private int $assertions = 0;

    /** @var array<int, array{test: string, error: string}> */
    private array $failures = [];

    public function __construct(private string $testsDir, private string $namespace = 'Tests')
    {
    }

    public function run(?string $filter = null): int
    {
        $start = microtime(true);
        fwrite(STDOUT, "\033[1;31mPHANTOM\033[0m test runner\n\n");

        foreach ($this->discover() as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(TestCase::class)) {
                continue;
            }
            $short = $reflection->getShortName();
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (!str_starts_with($method->getName(), 'test')) {
                    continue;
                }
                $name = "{$short}::{$method->getName()}";
                if ($filter !== null && stripos($name, $filter) === false) {
                    continue;
                }
                $this->runTest($class, $method->getName(), $name);
            }
        }

        $total = $this->passed + count($this->failures);
        $time = round(microtime(true) - $start, 2);
        fwrite(STDOUT, "\n");

        foreach ($this->failures as $i => $failure) {
            fwrite(STDOUT, "\033[31m" . ($i + 1) . ") {$failure['test']}\033[0m\n   {$failure['error']}\n\n");
        }

        $summary = "Tests: {$total}, Passed: {$this->passed}, Failed: " . count($this->failures)
            . ", Assertions: {$this->assertions}, Time: {$time}s";
        fwrite(STDOUT, ($this->failures ? "\033[41;37m FAIL \033[0m " : "\033[42;30m PASS \033[0m ") . $summary . "\n");

        return $this->failures ? 1 : 0;
    }

    /** @param class-string<TestCase> $class */
    private function runTest(string $class, string $method, string $name): void
    {
        /** @var TestCase $test */
        $test = new $class();
        try {
            $test->setUp();
            $test->{$method}();
            $this->passed++;
            fwrite(STDOUT, "  \033[32m✔\033[0m {$name}\n");
        } catch (Throwable $e) {
            $location = $e instanceof AssertionFailed ? $this->testLocation($e) : $e->getFile() . ':' . $e->getLine();
            $kind = $e instanceof AssertionFailed ? '' : $e::class . ': ';
            $this->failures[] = ['test' => $name, 'error' => $kind . $e->getMessage() . "\n   at {$location}"];
            fwrite(STDOUT, "  \033[31m✘\033[0m {$name}\n");
        } finally {
            try {
                $test->tearDown();
            } catch (Throwable) {
            }
            $this->assertions += $test->assertionCount();
        }
    }

    private function testLocation(Throwable $e): string
    {
        foreach ($e->getTrace() as $frame) {
            if (isset($frame['file']) && str_contains(str_replace('\\', '/', $frame['file']), '/tests/')) {
                return $frame['file'] . ':' . ($frame['line'] ?? '?');
            }
        }
        return $e->getFile() . ':' . $e->getLine();
    }

    /** @return string[] fully-qualified test class names */
    private function discover(): array
    {
        $classes = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->testsDir, \FilesystemIterator::SKIP_DOTS));
        $files = [];
        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), 'Test.php')) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        foreach ($files as $path) {
            $relative = substr($path, strlen(rtrim($this->testsDir, '/\\')) + 1, -4);
            $classes[] = $this->namespace . '\\' . str_replace(['/', '\\'], '\\', $relative);
        }
        return $classes;
    }
}
