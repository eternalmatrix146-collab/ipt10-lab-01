<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use Phantom\Database\DB;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = DB::table('categories')
            ->select('categories.*')
            ->selectRaw('(SELECT COUNT(*) FROM products WHERE products.category_id = categories.id) AS product_count')
            ->orderBy('name')
            ->get();
        return $this->view('admin.categories', ['categories' => $categories, 'motifs' => Category::MOTIFS]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        $data['slug'] = str_slug($data['name']);
        if (DB::table('categories')->where('slug', $data['slug'])->exists()) {
            $this->flash('error', 'A category with that name already exists.');
            return $this->back();
        }
        Category::create($data);
        $this->flash('success', "Category \"{$data['name']}\" created.");
        return $this->redirectRoute('admin.categories');
    }

    public function update(int $id, Request $request): Response
    {
        $category = Category::findOrFail($id);
        $data = $this->validated($request);
        $category->update($data);
        $this->flash('success', "Category \"{$category->name}\" updated.");
        return $this->redirectRoute('admin.categories');
    }

    public function destroy(int $id): Response
    {
        $category = Category::findOrFail($id);
        if ($category->productCount() > 0) {
            $this->flash('error', 'Move or delete this category\'s products first.');
            return $this->redirectRoute('admin.categories');
        }
        $category->delete();
        $this->flash('success', "Category \"{$category->name}\" deleted.");
        return $this->redirectRoute('admin.categories');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $this->validate($request, [
            'name' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:255',
            'motif' => 'required|in:' . implode(',', Category::MOTIFS),
        ]);
    }
}
