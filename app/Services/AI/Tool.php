<?php

declare(strict_types=1);

namespace App\Services\AI;

use Closure;

/**
 * One capability the agent can invoke. The JSON Schema tells the model what
 * arguments to send; the handler runs the real PHP code.
 */
final class Tool
{
    /**
     * @param array<string, mixed> $inputSchema JSON Schema for the arguments
     * @param Closure(ToolInput): ToolResult $handler
     * @param string $label short past-tense label for the UI, e.g. "Searched the catalog"
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema,
        public Closure $handler,
        public string $label = ''
    ) {
    }

    /** @return array<string, mixed> tool definition in Chat Completions ("function") format */
    public function definition(): array
    {
        $schema = $this->inputSchema + ['type' => 'object'];
        if (!isset($schema['properties']) || $schema['properties'] === []) {
            $schema['properties'] = new \stdClass(); // must encode as {} not []
        }
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => $schema,
            ],
        ];
    }
}
