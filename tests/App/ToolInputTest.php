<?php

declare(strict_types=1);

namespace Tests\App;

use App\Services\AI\ToolError;
use App\Services\AI\ToolInput;
use Tests\TestCase;

/**
 * Tool arguments come from the model and are treated as untrusted input.
 */
class ToolInputTest extends TestCase
{
    public function testClampsAndCoercesValues(): void
    {
        $in = new ToolInput(['limit' => '500', 'quantity' => -3, 'query' => str_repeat('a', 500), 'max' => '1200.5', 'flag' => 'true']);
        $this->assertSame(8, $in->int('limit', 5, 1, 8));
        $this->assertSame(1, $in->int('quantity', 1, 1, 10));
        $this->assertSame(80, mb_strlen($in->string('query', '', 80)));
        $this->assertEquals(1200.5, $in->float('max'));
        $this->assertTrue($in->bool('flag'));
    }

    public function testRejectsWrongTypes(): void
    {
        $in = new ToolInput(['query' => ['nested' => 'array'], 'limit' => 'lots']);
        $this->assertSame('', $in->string('query'));
        $this->assertSame(5, $in->int('limit', 5, 1, 8));
        $this->assertThrows(ToolError::class, fn () => $in->required('reference'));
    }
}
