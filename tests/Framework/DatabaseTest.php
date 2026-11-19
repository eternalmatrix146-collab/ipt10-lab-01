<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Models\Product;
use InvalidArgumentException;
use Phantom\Database\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function setUp(): void
    {
        $this->useSqlite();
        $this->seedCatalog();
    }

    public function testBuilderSelectWhereOrder(): void
    {
        $rows = DB::table('products')->where('price', '<', 1000)->orderBy('price', 'DESC')->get();
        $this->assertSame(['Crimson Mask', 'Midnight Roast'], array_column($rows, 'name'));
    }

    public function testBuilderBindsValuesInsteadOfConcatenating(): void
    {
        $query = DB::table('products')->where('name', "x' OR '1'='1");
        $this->assertSame('SELECT * FROM products WHERE name = ?', $query->toSql());
        $this->assertSame(0, $query->count());
    }

    public function testBuilderRejectsInjectedIdentifiers(): void
    {
        $this->assertThrows(InvalidArgumentException::class, fn () => DB::table('products')->orderBy('price; DROP TABLE products'));
        $this->assertThrows(InvalidArgumentException::class, fn () => DB::table('products')->where('name', 'OR 1=1', 'x'));
    }

    public function testAggregatesAndPagination(): void
    {
        $this->assertSame(3, DB::table('products')->count());
        $this->assertEquals(60.0, DB::table('products')->sum('stock'));
        $page = DB::table('products')->orderBy('id')->paginate(2, 2);
        $this->assertSame(3, $page['total']);
        $this->assertSame(2, $page['pages']);
        $this->assertCount(1, $page['data']);
    }

    public function testUpdateIncrementDelete(): void
    {
        DB::table('products')->where('slug', 'crimson-mask')->decrement('stock', 3);
        $this->assertSame(7, (int) DB::table('products')->where('slug', 'crimson-mask')->value('stock'));
        DB::table('products')->where('slug', 'crimson-mask')->update(['price' => 799]);
        $this->assertEquals(799, DB::table('products')->where('slug', 'crimson-mask')->value('price'));
        $this->assertSame(1, DB::table('products')->where('stock', 0)->delete());
    }

    public function testModelCreateFindUpdateAndMassAssignmentProtection(): void
    {
        $product = Product::create(['category_id' => 1, 'name' => 'Gloves', 'slug' => 'gloves', 'price' => 1290, 'stock' => 4, 'id' => 999]);
        $this->assertTrue($product->exists);
        $this->assertFalse((int) $product->id === 999, 'id must not be mass-assignable');

        $found = Product::find((int) $product->id);
        $this->assertSame('Gloves', $found->name);
        $found->update(['stock' => 9]);
        $this->assertSame(9, (int) Product::find((int) $product->id)->stock);
    }

    public function testCatalogSearchJoinsCategoriesAndFilters(): void
    {
        $results = Product::search(['q' => 'mask', 'in_stock' => true])->get();
        $this->assertCount(1, $results);
        $this->assertSame('Masks', $results[0]->category_name);

        $coffee = Product::search(['category' => 'coffee', 'max_price' => 700])->get();
        $this->assertSame(['Midnight Roast'], array_map(fn ($p) => $p->name, $coffee));

        // LIKE wildcards in user input are escaped
        $this->assertCount(0, Product::search(['q' => '%'])->get());
    }

    public function testTransactionRollsBackOnFailure(): void
    {
        try {
            DB::transaction(function () {
                DB::table('products')->where('id', 1)->update(['stock' => 0]);
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(10, (int) DB::table('products')->where('id', 1)->value('stock'));
    }
}
