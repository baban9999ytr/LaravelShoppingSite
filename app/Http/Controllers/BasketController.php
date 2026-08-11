<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingBasket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BasketController extends Controller
{
    public function store(Request $request): RedirectResponse
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
            Log::error('BasketController@store failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Ürün sepete eklenirken bir hata oluştu.',
            ]);
        }
    }

    public function update(Request $request, ShoppingBasket $shoppingBasket): RedirectResponse
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
            Log::error('BasketController@update failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Sepet güncellenirken bir hata oluştu.',
            ]);
        }
    }

    public function destroy(Request $request, ShoppingBasket $shoppingBasket): RedirectResponse
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
            Log::error('BasketController@destroy failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Ürün çıkarılırken bir hata oluştu.',
            ]);
        }
    }
}