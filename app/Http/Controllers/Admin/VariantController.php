<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VariantController extends Controller
{
        
    public function store(Request $request): RedirectResponse
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

            return redirect()->route('admin.products.index', [], 303)
                ->with('status', 'Varyant başarıyla oluşturuldu.');
        } catch (Exception $e) {
            Log::error('Admin\VariantController@store failed: '.$e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Varyant oluşturulamadı.');
        }
    }

   
    public function update(Request $request, ProductVariant $variant): RedirectResponse
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

            return redirect()->route('admin.products.index', [], 303)
                ->with('status', 'Varyant başarıyla güncellendi.');
        } catch (Exception $e) {
            Log::error('Admin\VariantController@update failed: '.$e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Varyant güncellenemedi.');
        }
    }

  
    public function destroy(ProductVariant $variant): RedirectResponse
    {
        try {
            DB::transaction(function () use ($variant) {
                $variant->featureValues()->detach();
                $variant->delete();
            });

            return redirect()->back()->with('status', 'Varyant silindi.');
        } catch (Exception $e) {
            Log::error('Admin\VariantController@destroy failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Varyant silinemedi.']);
        }
    }
}