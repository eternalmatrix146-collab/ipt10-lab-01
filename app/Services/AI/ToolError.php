<?php

declare(strict_types=1);

namespace App\Services\AI;

use RuntimeException;

/** Thrown by a tool handler for an expected failure; the message is sent back to the model as an error result. */
class ToolError extends RuntimeException
{
}
