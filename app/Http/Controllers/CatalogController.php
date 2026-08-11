<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Feature;
use App\Models\Product;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CatalogController extends Controller
{
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
            Log::error('CatalogController@index failed: '.$e->getMessage());

            return Inertia::render('Shop/Index', [
                'categories' => [],
                'features' => [],
                'products' => ['data' => [], 'links' => [], 'total' => 0],
                'filters' => [],
                'user_favorites' => [],
            ]);
        }
    }

    public function show(string $productIdentifier): Response|RedirectResponse
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
            Log::error('CatalogController@show failed: '.$e->getMessage());

            return redirect()->route('home')->withErrors([
                'error' => 'Ürün detayları yüklenirken bir sorun oluştu.',
            ]);
        }
    }
}