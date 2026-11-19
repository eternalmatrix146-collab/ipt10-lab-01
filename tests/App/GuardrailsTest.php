<?php

declare(strict_types=1);

namespace Tests\App;

use App\Models\User;
use App\Services\AI\AdminToolkit;
use Phantom\Application;
use Phantom\Auth\Auth;
use Phantom\Exceptions\HttpException;
use Phantom\Http\Request;
use Phantom\Http\Response;
use Phantom\Middleware\ThrottleRequests;
use Phantom\Session\Session;
use Tests\TestCase;

/**
 * Regression tests for the security audit fixes (2026-11-19).
 */
class GuardrailsTest extends TestCase
{
    /** F1: Rook Intel can only prepare changes; customer text in its context can never write data. */
    public function testIntelWriteToolsNeverChangeData(): void
    {
        $db = $this->useSqlite();
        $this->seedCatalog();
        $db->unprepared("CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, reference TEXT, status TEXT, total REAL, created_at TEXT, updated_at TEXT)");
        $db->table('orders')->insert(['user_id' => 1, 'reference' => 'MM-261119-TEST1', 'status' => 'pending', 'total' => 830]);

        $tools = (new AdminToolkit())->registry();
        $status = $tools->execute('update_order_status', ['reference' => 'MM-261119-TEST1', 'status' => 'cancelled']);
        $restock = $tools->execute('restock_product', ['product_id' => 1, 'add_quantity' => 500]);

        $this->assertFalse($status->isError);
        $this->assertFalse($status->data['applied']);
        $this->assertStringContains('/admin/orders/MM-261119-TEST1', $status->data['apply_url']);
        $this->assertSame('pending', $db->table('orders')->where('reference', 'MM-261119-TEST1')->value('status'));

        $this->assertFalse($restock->data['applied']);
        $this->assertStringContains('/admin/products/1/edit', $restock->data['apply_url']);
        $this->assertSame(10, (int) $db->table('products')->where('id', 1)->value('stock'));
    }

    /** F2: dropping the session cookie must not reset the rate limit. */
    public function testThrottleIsNotResetByANewSession(): void
    {
        $throttle = new ThrottleRequests(3, 60);
        $path = '/throttle-test-' . bin2hex(random_bytes(4));
        $next = static fn () => new Response('ok');
        $hit = static fn () => $throttle->handle(new Request('POST', $path, [], [], [], [], ['REMOTE_ADDR' => '203.0.113.7']), $next);

        $app = Application::getInstance();
        for ($i = 0; $i < 3; $i++) {
            $app->instance(Session::class, new Session('throttle-' . $i, false)); // a fresh session for every request
            $hit();
        }
        $app->instance(Session::class, new Session('throttle-x', false));
        $e = $this->assertThrows(HttpException::class, $hit);
        $this->assertSame(429, $e->getStatus());
    }

    /** F3: a referer like http://host//evil.example must not become a protocol-relative redirect. */
    public function testBackUrlStaysOnThisSite(): void
    {
        $app = Application::getInstance();
        $_SERVER['HTTP_HOST'] = 'localhost:8000';
        foreach (['http://localhost:8000//evil.example/phish', 'http://localhost:8000/\\evil.example/phish'] as $referer) {
            $app->instance(Request::class, new Request('POST', '/currency', [], [], ['referer' => $referer]));
            $this->assertSame('/evil.example/phish', back_url());
        }
    }

    /** F4: unknown emails never crash login (bcrypt rejects NUL bytes from random dummy passwords). */
    public function testUnknownEmailLoginNeverThrows(): void
    {
        $this->useSqlite();
        $auth = new Auth(new Session('test', false), User::class);
        for ($i = 0; $i < 25; $i++) {
            $this->assertFalse($auth->attempt("nobody{$i}@example.test", 'wrong-password'));
        }
    }
}
