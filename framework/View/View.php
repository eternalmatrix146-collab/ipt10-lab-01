<?php

declare(strict_types=1);

namespace Phantom\View;

use RuntimeException;

/**
 * Plain-PHP templates with layout inheritance and named sections.
 *
 * In a page template:
 *   <?php $this->layout('layouts.main', ['title' => 'Shop']) ?>
 *   <?php $this->start('content') ?> ...html... <?php $this->stop() ?>
 *
 * In the layout:
 *   <?= $this->section('content') ?>
 *
 * Templates live in resources/views and are referenced with dot notation.
 */
class View
{
    /** @var array<string, mixed> data available to every template */
    private array $shared = [];

    public function __construct(private string $path)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        return (new Template($this, $this->resolve($template), array_merge($this->shared, $data)))->render();
    }

    public function exists(string $template): bool
    {
        return is_file($this->filePath($template));
    }

    public function resolve(string $template): string
    {
        $file = $this->filePath($template);
        if (!is_file($file)) {
            throw new RuntimeException("View [{$template}] not found at {$file}");
        }
        return $file;
    }

    private function filePath(string $template): string
    {
        return rtrim($this->path, '/\\') . '/' . str_replace('.', '/', $template) . '.php';
    }
}
