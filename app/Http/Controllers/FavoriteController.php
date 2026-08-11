<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class FavoriteController extends Controller
{
    public function toggle(Request $request): RedirectResponse
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
            Log::error('FavoriteController@toggle failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Favori işlemi sırasında hata oluştu.',
            ]);
        }
    }
}