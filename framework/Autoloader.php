<?php

declare(strict_types=1);

namespace Phantom;

/**
 * Minimal PSR-4 autoloader so the framework runs without Composer.
 *
 * Usage:
 *   $loader = new Autoloader();
 *   $loader->addNamespace('App\\', __DIR__ . '/app');
 *   $loader->register();
 */
final class Autoloader
{
    /** @var array<string, string[]> namespace prefix => base directories */
    private array $prefixes = [];

    public function addNamespace(string $prefix, string $baseDir): self
    {
        $prefix = trim($prefix, '\\') . '\\';
        $this->prefixes[$prefix][] = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        return $this;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $class): bool
    {
        foreach ($this->prefixes as $prefix => $dirs) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }
            $relative = substr($class, strlen($prefix));
            foreach ($dirs as $dir) {
                $file = $dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return true;
                }
            }
        }
        return false;
    }
}
