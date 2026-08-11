<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Feature;
use App\Models\FeatureValue;
use App\Models\Product;
use App\Models\ShoppingOrder;            
use App\Services\DeliveryCalculatorService; 
use Illuminate\Http\JsonResponse;
use App\Models\ProductVariant;
use App\Models\ShoppingBasket;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProductController extends Controller
{
    //region Storefront Catalog Methods

    public function index(Request $request): Response
    {
        try {
            $categories = Category::defaultOrder()->get()->toTree()->toArray();
            $features = Feature::with('values')->get();

            $query = Product::with([
                'categories',
                'featureValues.feature',
                'variants.featureValues.feature',
            ]);

            if ($request->filled('category')) {
                $slug = strtolower($request->category);
                $selectedCategories = Category::whereRaw('LOWER(slug) = ?', [$slug])->get();

                if ($selectedCategories->isNotEmpty()) {
                    $categoryIds = collect();
                    foreach ($selectedCategories as $cat) {
                        $categoryIds = $categoryIds->merge(
                            Category::descendantsAndSelf($cat->id)->pluck('id')
                        );
                    }
                    $query->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('categories.id', $categoryIds->unique());
                    });
                }
            }

            if ($request->filled('features')) {
                $featureValueIds = (array) $request->features;
                $query->whereHas('featureValues', function ($q) use ($featureValueIds) {
                    $q->whereIn('feature_values.id', $featureValueIds);
                });
            }

            if ($request->filled('search')) {
                $query->where('name', 'like', '%'.$request->search.'%');
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            $products = $query->latest()->paginate(12)->withQueryString();

            $userFavoriteProductIds = [];
            if ($request->user()) {
                $userFavoriteProductIds = Favorite::where('user_id', $request->user()->id)
                    ->pluck('product_id')
                    ->toArray();
            }

            return Inertia::render('Shop/Index', [
                'categories' => $categories,
                'features' => $features,
                'products' => $products,
                'filters' => $request->only(['category', 'features', 'search', 'min_price', 'max_price']),
                'user_favorites' => $userFavoriteProductIds,
            ]);
        } catch (Throwable $e) {
            Log::error('ProductController@index failed: '.$e->getMessage());

            return Inertia::render('Shop/Index', [
                'categories' => [],
                'features' => [],
                'products' => ['data' => [], 'links' => [], 'total' => 0],
                'filters' => [],
                'user_favorites' => [],
            ]);
        }
    }

    public function productDetails(string $productIdentifier): Response|RedirectResponse
    {
        try {
            $product = Product::with([
                'categories',
                'featureValues.feature',
                'variants.featureValues.feature',
            ])
                ->where('slug', $productIdentifier)
                ->orWhere('id', $productIdentifier)
                ->firstOrFail();

            return Inertia::render('Shop/Details', [
                'product' => $product,
                'categories' => $product->categories,
                'featureValues' => $product->featureValues,
                'variants' => $product->variants,
            ]);
        } catch (Exception $e) {
            return redirect()->route('home')->withErrors([
                'error' => 'Aradığınız ürün bulunamadı.',
            ]);
        } catch (Throwable $e) {
            Log::error('ProductController@productDetails failed: '.$e->getMessage());

            return redirect()->route('home')->withErrors([
                'error' => 'Ürün detayları yüklenirken bir sorun oluştu.',
            ]);
        }
    }

    //endregion

    //region Product CRUD

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

            return redirect()->route('products.index', [], 303)
                ->with('status', 'Ürün başarıyla oluşturuldu.');
        } catch (Throwable $e) {
            Log::error('ProductController@store failed: '.$e->getMessage());

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

            return redirect()->route('products.index', [], 303)
                ->with('status', 'Ürün başarıyla güncellendi.');
        } catch (Throwable $e) {
            Log::error('ProductController@update failed: '.$e->getMessage());

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

            return redirect()->route('products.index', [], 303)
                ->with('status', 'Ürün silindi.');
        } catch (Throwable $e) {
            Log::error('ProductController@destroy failed: '.$e->getMessage());

            return redirect()->route('products.index', [], 303)->withErrors([
                'error' => 'Ürün silinemedi.',
            ]);
        }
    }

    //endregion

    //region Variant CRUD

    public function storeVariant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:100|unique:product_variants,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:50',
            'image_url' => 'nullable|url',
            'attributes' => 'nullable|array',
            'feature_value_ids' => 'nullable|array',
            'feature_value_ids.*' => 'exists:feature_values,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $variant = ProductVariant::create([
                    'product_id' => $validated['product_id'],
                    'sku' => $validated['sku'],
                    'price' => $validated['price'],
                    'stock' => $validated['stock'] ?? 0,
                    'color' => $validated['color'] ?? null,
                    'size' => $validated['size'] ?? null,
                    'image_url' => $validated['image_url'] ?? null,
                    'attributes' => $validated['attributes'] ?? null,
                ]);

                if (! empty($validated['feature_value_ids'])) {
                    $variant->featureValues()->sync($validated['feature_value_ids']);
                }
            });

            return redirect()->route('products.index', [], 303)
                ->with('status', 'Varyant başarıyla oluşturuldu.');
        } catch (Exception $e) {
            Log::error('ProductController@storeVariant failed: '.$e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Varyant oluşturulamadı.');
        }
    }

    public function updateVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:100|unique:product_variants,sku,'.$variant->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:50',
            'image_url' => 'nullable|url',
            'attributes' => 'nullable|array',
            'feature_value_ids' => 'nullable|array',
            'feature_value_ids.*' => 'exists:feature_values,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $variant) {
                $variant->update([
                    'sku' => $validated['sku'],
                    'price' => $validated['price'],
                    'stock' => $validated['stock'] ?? $variant->stock,
                    'color' => $validated['color'] ?? null,
                    'size' => $validated['size'] ?? null,
                    'image_url' => $validated['image_url'] ?? null,
                    'attributes' => $validated['attributes'] ?? null,
                ]);

                $variant->featureValues()->sync($validated['feature_value_ids'] ?? []);
            });

            return redirect()->route('products.index', [], 303)
                ->with('status', 'Varyant başarıyla güncellendi.');
        } catch (Exception $e) {
            Log::error('ProductController@updateVariant failed: '.$e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Varyant güncellenemedi.');
        }
    }

    public function destroyVariant(ProductVariant $variant): RedirectResponse
    {
        try {
            DB::transaction(function () use ($variant) {
                $variant->featureValues()->detach();
                $variant->delete();
            });

            return redirect()->back()->with('status', 'Varyant silindi.');
        } catch (Exception $e) {
            Log::error('ProductController@destroyVariant failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Varyant silinemedi.']);
        }
    }

    //endregion

    //region Feature CRUD

    public function indexFeatures(): Response
    {
        return Inertia::render('Features/Index', [
            'features' => Feature::with('values')->latest()->get(),
        ]);
    }

    public function storeFeature(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:features,slug',
            'values' => 'required|array|min:1',
            'values.*.value' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $slug = ! empty($validated['slug'])
                    ? Str::slug($validated['slug'])
                    : Str::slug($validated['name']);

                $feature = Feature::create([
                    'name' => $validated['name'],
                    'slug' => $slug,
                ]);

                $valuesToInsert = collect($validated['values'])
                    ->map(fn ($v) => trim($v['value'] ?? ''))
                    ->filter()
                    ->unique()
                    ->map(fn ($val) => ['value' => $val])
                    ->values()
                    ->all();

                if (! empty($valuesToInsert)) {
                    $feature->values()->createMany($valuesToInsert);
                }
            });

            return redirect()->back()->with('status', 'Özellik ve değerleri başarıyla oluşturuldu.');
        } catch (Throwable $e) {
            Log::error('ProductController@storeFeature failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Özellik eklenirken bir hata oluştu.',
            ]);
        }
    }

    public function updateFeature(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:features,slug,'.$feature->id,
        ]);

        try {
            if (! empty($validated['slug']) && $feature->slug !== $validated['slug']) {
                $feature->slug = Str::slug($validated['slug']);
            }

            $feature->name = $validated['name'];
            $feature->save();

            return redirect()->back()->with('status', 'Özellik adı güncellendi.');
        } catch (Throwable $e) {
            Log::error('ProductController@updateFeature failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Özellik güncellenirken hata oluştu.',
            ]);
        }
    }

    public function destroyFeature(Feature $feature): RedirectResponse
    {
        try {
            DB::transaction(function () use ($feature) {
                $feature->values()->delete();
                $feature->delete();
            });

            return redirect()->back()->with('status', 'Özellik ve bağlı değerler silindi.');
        } catch (Throwable $e) {
            Log::error('ProductController@destroyFeature failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Özellik silinemedi.',
            ]);
        }
    }

    public function storeFeatureValue(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
        ]);

        try {
            $feature->values()->create([
                'value' => trim($validated['value']),
            ]);

            return redirect()->back()->with('status', 'Özellik değeri eklendi.');
        } catch (Throwable $e) {
            Log::error('ProductController@storeFeatureValue failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Değer eklenirken hata oluştu.',
            ]);
        }
    }

    public function destroyFeatureValue(FeatureValue $featureValue): RedirectResponse
    {
        try {
            $featureValue->delete();

            return redirect()->back()->with('status', 'Özellik değeri silindi.');
        } catch (Throwable $e) {
            Log::error('ProductController@destroyFeatureValue failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Değer silinemedi.',
            ]);
        }
    }

    //endregion
//region basket

    public function updateCartItem(Request $request, ShoppingBasket $shoppingBasket): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            if ($shoppingBasket->user_id !== $request->user()->id) {
                abort(403);
            }

            $maxStock = 99;
            if ($shoppingBasket->variant_id) {
                $variant = ProductVariant::where('product_id', $shoppingBasket->product_id)
                    ->findOrFail($shoppingBasket->variant_id);
                $maxStock = $variant->stock;
            }

            if ($validated['quantity'] > $maxStock) {
                return redirect()->back()->withErrors([
                    'error' => "Sepetinizdeki miktar ürün stok sınırına ({$maxStock}) ulaştı.",
                ]);
            }

            $shoppingBasket->quantity = $validated['quantity'];
            $shoppingBasket->save();

            return redirect()->back()->with('status', 'Sepet güncellendi.');
        } catch (Throwable $e) {
            Log::error('ProductController@updateCartItem failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Sepet güncellenirken bir hata oluştu.',
            ]);
        }
    }

    public function addToBasket(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
            'value' => 'nullable|string|max:255',
        ]);

        try {
            $userId = $request->user()->id;
            $productId = $validated['product_id'];
            $variantId = $validated['variant_id'] ?? null;
            $quantityToAdd = $validated['quantity'] ?? 1;

            $product = Product::with('variants')->findOrFail($productId);

            if ($product->variants->isNotEmpty() && ! $variantId) {
                $firstWithStock = $product->variants->first(fn ($v) => $v->stock > 0);
                $selectedVariant = $firstWithStock ?: $product->variants->first();
                if ($selectedVariant) {
                    $variantId = $selectedVariant->id;
                }
            }

            if ($variantId) {
                $variant = ProductVariant::where('product_id', $productId)->findOrFail($variantId);
                $maxStock = $variant->stock;
            } else {
                $maxStock = 99;
            }

            $basketItem = ShoppingBasket::where('user_id', $userId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->first();

            $currentQty = $basketItem ? $basketItem->quantity : 0;
            $newQty = $currentQty + $quantityToAdd;

            if ($newQty > $maxStock) {
                if ($maxStock <= 0) {
                    return redirect()->back()->withErrors([
                        'error' => 'Bu ürünün stoğu tükenmiştir.',
                    ]);
                }

                if ($currentQty >= $maxStock) {
                    return redirect()->back()->withErrors([
                        'error' => "Sepetinizdeki miktar ürün stok sınırına ({$maxStock}) ulaştı.",
                    ]);
                }

                $newQty = $maxStock;
                $message = "Sadece {$maxStock} adet stok bulunduğu için sepetiniz güncellendi.";
            } else {
                $message = 'Ürün başarıyla alışveriş sepetine eklendi.';
            }

            ShoppingBasket::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                ],
                [
                    'quantity' => $newQty,
                    'value' => $validated['value'] ?? null,
                ]
            );

            return redirect()->back()->with('status', $message);
        } catch (Throwable $e) {
            Log::error('ProductController@addToBasket failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Ürün sepete eklenirken bir hata oluştu.',
            ]);
        }
    }

    public function removeFromBasket(Request $request, ShoppingBasket $shoppingBasket): RedirectResponse
    {
        try {
            if ($shoppingBasket->user_id !== $request->user()->id) {
                abort(403);
            }
            DB::transaction(function () use ($shoppingBasket) {
                $shoppingBasket->delete();
            });

            return redirect()->back()->with('status', 'Ürün sepetten çıkarıldı.');
        } catch (Throwable $e) {
            Log::error('ProductController@removeFromBasket failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Ürün çıkarılırken bir hata oluştu.',
            ]);
        }
    }

    //endregion

//region order
    public function storeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'origin_city' => 'required|string',
            'destination_city' => 'required|string',
        ]);

        try {
            $user = $request->user();

            $basketItems = ShoppingBasket::with(['product', 'variant'])
                ->where('user_id', $user->id)
                ->get();

            if ($basketItems->isEmpty()) {
                return redirect()->back()->withErrors([
                    'error' => 'Sepetiniz boş olduğu için sipariş verilemez.',
                ]);
            }

            $orderedAt = now();
            $estimatedArrival = DeliveryCalculatorService::calculateEstimatedArrival(
                $validated['origin_city'],
                $validated['destination_city'],
                $orderedAt
            );

           
            DB::transaction(function () use ($validated, $orderedAt, $estimatedArrival, $user, $basketItems) {
                
                foreach ($basketItems as $item) {
                    if ($item->variant_id) {
                        $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();
                        
                        if (!$variant || $variant->stock < $item->quantity) {
                            throw new Exception("{$item->product->name} ürünü için yeterli stok yok (Kalan stok: " . ($variant->stock ?? 0) . ").");
                        }
                        $variant->decrement('stock', $item->quantity);
                    } else {
                        if (Schema::hasColumn('products', 'stock')) {
                            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                            
                            if (!$product || $product->stock < $item->quantity) {
                                throw new Exception("{$item->product->name} ürünü için yeterli stok yok.");
                            }
                            $product->decrement('stock', $item->quantity);
                        }
                    }
                }

                ShoppingOrder::create([
                    'user_id' => $user->id,
                    'origin_city' => $validated['origin_city'],
                    'destination_city' => $validated['destination_city'],
                    'ordered_at' => $orderedAt,
                    'estimated_arrival_at' => $estimatedArrival,
                ]);

                ShoppingBasket::where('user_id', $user->id)->delete();
            });

            return redirect()->back()->with('status', 'Siparişiniz başarıyla oluşturuldu!');

        } catch (Throwable $e) {
        
            Log::error('ProductController@storeOrder failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Sipariş oluşturulamadı: ' . $e->getMessage(),
            ]);
        }
    }
    //endregion
  
  
  
  
    //region Favorites

    public function toggleFavorite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'value' => 'nullable|string|max:255',
        ]);

        try {
            $userId = $request->user()->id;

            $existing = Favorite::where('user_id', $userId)
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($existing) {
                $existing->delete();
                $message = 'Ürün favorilerden çıkarıldı.';
            } else {
                Favorite::create([
                    'user_id' => $userId,
                    'product_id' => $validated['product_id'],
                    'value' => $validated['value'] ?? null,
                ]);
                $message = 'Ürün favorilere eklendi.';
            }

            return redirect()->back()->with('status', $message);
        } catch (Throwable $e) {
            Log::error('ProductController@toggleFavorite failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Favori işlemi sırasında hata oluştu.',
            ]);
        }
    }

    //endregion

    //region Helpers

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

    //endregion
}
