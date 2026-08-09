<?php

namespace App\Http\Middleware;

use App\Models\Favorite;
use App\Models\ShoppingBasket;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'user_favorites' => $request->user()
                ? Favorite::where('user_id', $request->user()->id)->pluck('product_id')->toArray()
                : [],
            'user_basket' => $request->user()
                ? ShoppingBasket::where('user_id', $request->user()->id)
                    ->with(['product.categories', 'variant.featureValues.feature'])
                    ->get()
                : [],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),
        ]);
    }
}
