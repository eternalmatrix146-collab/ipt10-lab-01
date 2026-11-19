<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\View\View;
use Tests\TestCase;

class ViewTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/phantom_views_' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/layouts', 0777, true);
        file_put_contents($this->dir . '/layouts/base.php', '<html><title><?= $this->e($title) ?></title><?= $this->section("content") ?></html>');
        file_put_contents($this->dir . '/layouts/inner.php', '<?php $this->layout("layouts.base") ?><main><?= $this->section("content") ?></main>');
        file_put_contents($this->dir . '/page.php', '<?php $this->layout("layouts.base", ["title" => "Shop"]) ?><p><?= $this->e($name) ?></p>');
        file_put_contents($this->dir . '/nested.php', '<?php $this->layout("layouts.inner", ["title" => "Nest"]) ?><b>hi</b>');
        file_put_contents($this->dir . '/partial.php', 'Hello <?= $this->insert("name") ?>');
        file_put_contents($this->dir . '/name.php', '<?= $this->e($who) ?>');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        foreach (['layouts/base.php', 'layouts/inner.php', 'page.php', 'nested.php', 'partial.php', 'name.php'] as $f) {
            @unlink($this->dir . '/' . $f);
        }
        @rmdir($this->dir . '/layouts');
        @rmdir($this->dir);
    }

    public function testLayoutWrapsContentAndEscapes(): void
    {
        $html = (new View($this->dir))->render('page', ['name' => '<script>alert(1)</script>']);
        $this->assertSame('<html><title>Shop</title><p>&lt;script&gt;alert(1)&lt;/script&gt;</p></html>', $html);
    }

    public function testNestedLayouts(): void
    {
        $html = (new View($this->dir))->render('nested');
        $this->assertSame('<html><title>Nest</title><main><b>hi</b></main></html>', $html);
    }

    public function testPartialsInheritData(): void
    {
        $this->assertSame('Hello Ana', (new View($this->dir))->render('partial', ['who' => 'Ana']));
    }
}
