<?php

declare(strict_types=1);

namespace Phantom\View;

/**
 * One rendering pass of a template file. `$this` inside the template refers
 * to this object, which provides layout(), start()/stop(), section(), insert()
 * and e() for escaping.
 */
class Template
{
    private ?string $layout = null;

    private bool $isLayout = false;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /** @var array<string, string> */
    private array $sections = [];

    /** @var string[] */
    private array $sectionStack = [];

    /** @param array<string, mixed> $data */
    public function __construct(private View $view, private string $file, private array $data)
    {
    }

    public function render(): string
    {
        $content = $this->capture($this->file, $this->data);

        if ($this->layout === null) {
            return $content;
        }

        // A page may define its 'content' section explicitly; a layout that itself
        // extends another layout always passes its whole output up as 'content'.
        if ($this->isLayout) {
            $this->sections['content'] = $content;
        } else {
            $this->sections['content'] ??= $content;
        }
        $layout = new self($this->view, $this->view->resolve($this->layout), array_merge($this->data, $this->layoutData));
        $layout->sections = $this->sections;
        $layout->isLayout = true;
        return $layout->render();
    }

    /** @param array<string, mixed> $data */
    private function capture(string $__file, array $__data): string
    {
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $data */
    public function layout(string $name, array $data = []): void
    {
        $this->layout = $name;
        $this->layoutData = $data;
    }

    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            throw new \LogicException('stop() called without a matching start().');
        }
        $this->sections[$name] = (string) ob_get_clean();
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]) && trim($this->sections[$name]) !== '';
    }

    /**
     * Renders a partial with the parent's data plus $data.
     *
     * @param array<string, mixed> $data
     */
    public function insert(string $template, array $data = []): string
    {
        return (new self($this->view, $this->view->resolve($template), array_merge($this->data, $data)))->render();
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
