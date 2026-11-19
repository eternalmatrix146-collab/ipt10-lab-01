<?php

declare(strict_types=1);

namespace App\Services\AI;

use Throwable;

/**
 * The set of tools an agent may use, plus safe execution: unknown tools,
 * bad arguments and exceptions all become error results that the model can
 * read and recover from, instead of crashing the request.
 */
final class ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    /** @var (callable(string, array<string, mixed>, ToolResult): void)|null */
    private $listener = null;

    public function register(Tool $tool): self
    {
        $this->tools[$tool->name] = $tool;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    /** @return string[] */
    public function names(): array
    {
        return array_keys($this->tools);
    }

    /** Called after every execution (used for the agent_actions audit log). */
    public function onExecute(callable $listener): void
    {
        $this->listener = $listener;
    }

    /** @return array<int, array<string, mixed>> */
    public function definitions(): array
    {
        return array_values(array_map(static fn (Tool $t) => $t->definition(), $this->tools));
    }

    /** @param array<string, mixed> $input */
    public function execute(string $name, array $input): ToolResult
    {
        $tool = $this->tools[$name] ?? null;
        if ($tool === null) {
            $result = ToolResult::error("Unknown tool \"{$name}\". Available tools: " . implode(', ', $this->names()));
        } else {
            try {
                $result = ($tool->handler)(new ToolInput($input));
            } catch (ToolError $e) {
                $result = ToolResult::error($e->getMessage());
            } catch (\App\Services\CartException $e) {
                $result = ToolResult::error($e->getMessage());
            } catch (Throwable $e) {
                app(\Phantom\Support\Logger::class)->error('Agent tool failed', ['tool' => $name, 'error' => $e->getMessage()]);
                $result = ToolResult::error('The tool failed unexpectedly. Try a different approach.');
            }
        }

        if ($this->listener !== null) {
            ($this->listener)($name, $input, $result);
        }
        return $result;
    }
}
