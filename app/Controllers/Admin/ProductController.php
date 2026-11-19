<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Services\AI\AgentService;
use Phantom\Database\DB;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $page = Product::search([
            'q' => (string) $request->query('q', ''),
            'category' => (string) $request->query('category', ''),
            'sort' => 'newest',
        ])->paginate(15, max(1, (int) $request->query('page', 1)));

        return $this->view('admin.products.index', [
            'page' => $page,
            'categories' => Category::ordered(),
            'q' => (string) $request->query('q', ''),
            'category' => (string) $request->query('category', ''),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.products.form', ['product' => null, 'categories' => Category::ordered()]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $product = Product::create($data);
        $this->flash('success', "Product \"{$product->name}\" created.");
        return $this->redirectRoute('admin.products');
    }

    public function edit(int $id): Response
    {
        return $this->view('admin.products.form', ['product' => Product::findOrFail($id), 'categories' => Category::ordered()]);
    }

    public function update(int $id, Request $request): Response
    {
        $product = Product::findOrFail($id);
        $data = $this->validated($request);
        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $id);
        }
        $product->update($data);
        $this->flash('success', "Product \"{$product->name}\" updated.");
        return $this->redirectRoute('admin.products');
    }

    public function destroy(int $id): Response
    {
        $product = Product::findOrFail($id);
        $product->delete(); // order_items keep their name/price snapshot (FK is SET NULL)
        $this->flash('success', "Product \"{$product->name}\" deleted.");
        return $this->redirectRoute('admin.products');
    }

    /** AI copywriter: POST name/category/tagline, returns a generated description as JSON. */
    public function describe(Request $request, AgentService $agent): Response
    {
        $data = $this->validate($request, [
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|integer',
            'tagline' => 'nullable|string|max:160',
        ]);
        $category = isset($data['category_id']) ? Category::find((int) $data['category_id']) : null;
        $result = $agent->describeProduct($data['name'], (string) ($category?->name ?? 'general'), (string) ($data['tagline'] ?? ''));
        return $this->json($result, $result['ok'] ? 200 : 503);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'name' => 'required|string|min:2|max:150',
            'category_id' => 'required|integer|exists:categories,id',
            'tagline' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:1|max:1000000',
            'stock' => 'required|integer|min:0|max:100000',
        ]);
        $data['category_id'] = (int) $data['category_id'];
        $data['price'] = round((float) $data['price'], 2);
        $data['stock'] = (int) $data['stock'];
        $data['is_featured'] = $request->input('is_featured') ? 1 : 0;
        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = str_slug($name);
        $slug = $base;
        $n = 2;
        while (true) {
            $query = DB::table('products')->where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
            if (!$query->exists()) {
                return $slug;
            }
            $slug = $base . '-' . $n++;
        }
    }
}
