<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductController extends Controller
{
    
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'price' => 'required|numeric|min:0',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'feature_value_ids' => 'nullable|array',
            'feature_value_ids.*' => 'exists:feature_values,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $finalImageUrl = $this->handleImageToWebp($request);

                $slug = ! empty($validated['slug'])
                    ? Str::slug($validated['slug'])
                    : $this->generateUniqueSlug($validated['name']);

                $product = Product::create([
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'meta_title' => $validated['meta_title'] ?? null,
                    'meta_description' => $validated['meta_description'] ?? null,
                    'price' => $validated['price'],
                    'image_url' => $finalImageUrl,
                    'description' => $validated['description'] ?? null,
                ]);

                if (! empty($validated['category_ids'])) {
                    $product->categories()->sync($validated['category_ids']);
                }

                if (! empty($validated['feature_value_ids'])) {
                    $product->featureValues()->sync($validated['feature_value_ids']);
                }
            });

            return redirect()->route('admin.products.index', [], 303)
                ->with('status', 'Ürün başarıyla oluşturuldu.');
        } catch (Throwable $e) {
            Log::error('Admin\ProductController@store failed: '.$e->getMessage());

            return redirect()->back()->withInput()->withErrors([
                'error' => 'Hata oluştu: '.$e->getMessage(),
            ]);
        }
    }

    
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,'.$product->id,
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'price' => 'required|numeric|min:0',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'feature_value_ids' => 'nullable|array',
            'feature_value_ids.*' => 'exists:feature_values,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $product) {
                if (! empty($validated['slug']) && $product->slug !== $validated['slug']) {
                    $product->slug = Str::slug($validated['slug']);
                }

                if ($request->hasFile('image_file') || ! empty($validated['image_url'])) {
                    $newImageUrl = $this->handleImageToWebp($request);
                    if ($newImageUrl) {
                        $product->image_url = $newImageUrl;
                    }
                }

                $product->name = $validated['name'];
                $product->meta_title = $validated['meta_title'] ?? $product->meta_title;
                $product->meta_description = $validated['meta_description'] ?? $product->meta_description;
                $product->price = $validated['price'];
                $product->description = $validated['description'] ?? null;
                $product->save();

                $product->categories()->sync($validated['category_ids'] ?? []);
                $product->featureValues()->sync($validated['feature_value_ids'] ?? []);
            });

            return redirect()->route('admin.products.index', [], 303)
                ->with('status', 'Ürün başarıyla güncellendi.');
        } catch (Throwable $e) {
            Log::error('Admin\ProductController@update failed: '.$e->getMessage());

            return redirect()->back()->withInput()->withErrors([
                'error' => $e->getMessage() ?: 'Güncelleme sırasında bir hata oluştu.',
            ]);
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            DB::transaction(function () use ($product) {
                $product->categories()->detach();
                $product->featureValues()->detach();
                $product->delete();
            });

            return redirect()->route('admin.products.index', [], 303)
                ->with('status', 'Ürün silindi.');
        } catch (Throwable $e) {
            Log::error('Admin\ProductController@destroy failed: '.$e->getMessage());

            return redirect()->route('admin.products.index', [], 303)->withErrors([
                'error' => 'Ürün silinemedi.',
            ]);
        }
    }

  
    private function handleImageToWebp(Request $request): ?string
    {
        $imageStringData = null;

        if ($request->hasFile('image_file')) {
            $imageStringData = file_get_contents($request->file('image_file')->getRealPath());
        } elseif ($request->filled('image_url')) {
            $url = $request->input('image_url');

            try {
                $response = Http::timeout(5)->get($url);
                if ($response->successful()) {
                    $imageStringData = $response->body();
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch image from URL: '.$url);

                return $url;
            }
        }

        if (! $imageStringData) {
            return $request->input('image_url');
        }

        try {
            $image = imagecreatefromstring($imageStringData);
            if (! $image) {
                return $request->input('image_url');
            }

            ob_start();
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);

            imagewebp($image, null, 80);
            $webpData = ob_get_clean();
            imagedestroy($image);

            $baseName = Str::slug($request->input('name', 'product'));
            $filename = 'products/'.$baseName.'-'.Str::random(6).'.webp';

            Storage::disk('public')->put($filename, $webpData);

            return Storage::url($filename);
        } catch (Exception $e) {
            Log::error('Image conversion to WebP failed: '.$e->getMessage());

            return $request->input('image_url');
        }
    }

   
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $slug = $baseSlug;

        if (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$baseSlug}-".strtolower(Str::random(4));
        }

        return $slug;
    }
}