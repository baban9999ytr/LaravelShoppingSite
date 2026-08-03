<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CategoryController extends Controller
{
    public function index(): Response
    {
        try {
            $categories = Category::defaultOrder()->get()->toTree();

            return Inertia::render('Categories/Index', [
                'categories' => $categories,
            ]);
        } catch (Throwable $e) {
            Log::error('CategoryController@index failed: '.$e->getMessage());

            return Inertia::render('Categories/Index', [
                'categories' => [],
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'nullable|exists:categories,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $rawNames = preg_split('/[,\n]+/', (string) $validated['name']);
                $names = array_filter(array_map('trim', (array) ($rawNames ?: [])));
                $parentIds = $request->input('parent_ids', []);

                if (! is_array($parentIds)) {
                    $parentIds = [];
                }

                if (empty($parentIds) || (count($parentIds) === 1 && is_null($parentIds[0]))) {
                    foreach ($names as $name) {
                        Category::create([
                            'name' => $name,
                            'slug' => $this->generateUniqueSlug($name),
                            'parent_id' => null,
                        ]);
                    }
                } else {
                    foreach ($parentIds as $parentId) {
                        if ($parentId !== null) {
                            $parentCategory = Category::find($parentId);

                            foreach ($names as $name) {
                                $data = [
                                    'name' => $name,
                                    'slug' => $this->generateUniqueSlug($name),
                                    'parent_id' => $parentId,
                                ];

                                if ($parentCategory && method_exists(Category::class, 'appendToNode')) {
                                    $category = new Category($data);
                                    $category->appendToNode($parentCategory)->save();
                                } else {
                                    Category::create($data);
                                }
                            }
                        }
                    }
                }
            });

            return redirect()->route('categories.index', [], 303)
                ->with('status', 'Category(ies) created successfully.');
        } catch (Throwable $e) {
            Log::error('CategoryController@store failed: '.$e->getMessage());

            return redirect()->route('categories.index', [], 303)->withErrors([
                'name' => 'Kategori(ler) oluşturulurken bir hata oluştu: '.$e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
        ]);
        try {
            DB::transaction(function () use ($validated, $category) {
                if ($category->name !== $validated['name']) {
                    $category->slug = $this->generateUniqueSlug($validated['name'], $category->id);
                }
                $category->name = $validated['name'];
                $newParentId = $validated['parent_id'] ?? null;
                if ($category->parent_id !== $newParentId) {
                    if ($newParentId) {
                        $newParent = Category::findOrFail($newParentId);

                        if ($newParent->isDescendantOf($category)) {
                            throw new \Exception('Bir kategori kendi alt kategorisinin altına taşınamaz.');
                        }

                        $category->appendToNode($newParent);
                    } else {
                        $category->makeRoot();
                    }
                }
                $category->save();
            });

            return redirect()->route('categories.index', [], 303)
                ->with('status', 'Category updated successfully.');
        } catch (Throwable $e) {
            Log::error('CategoryController@update failed: '.$e->getMessage());

            return redirect()->route('categories.index', [], 303)->withErrors([
                'name' => $e->getMessage() ?: 'Güncelleme sırasında bir hata oluştu.',
            ]);
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            DB::transaction(function () use ($category) {
                $category->delete();
            });

            return redirect()->route('categories.index', [], 303)
                ->with('status', 'Category deleted successfully.');
        } catch (Throwable $e) {
            Log::error('CategoryController@destroy failed: '.$e->getMessage());

            return redirect()->route('categories.index', [], 303)->withErrors([
                'error' => 'Kategori silinemedi.',
            ]);
        }
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $count = 1;
        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}
