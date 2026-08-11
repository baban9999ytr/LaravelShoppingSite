<?php

use App\Http\Controllers\Admin\FeatureController as AdminFeatureController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\VariantController as AdminVariantController;
use App\Http\Controllers\BasketController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GuiController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


Route::get('/', [CatalogController::class, 'index'])->name('shop.index');
Route::get('/home', fn () => redirect()->route('shop.index'))->name('home');
Route::get('/products/{productIdentifier}', [CatalogController::class, 'show'])->name('products.show');



Route::middleware(['auth'])->get('/dashboard', function (Request $request) {
    return redirect()->route('shop.index');
});

Route::prefix('{current_team}')
    ->middleware(['auth', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('/favorites', [GuiController::class, 'favorites'])->name('favorites.index');
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/basket', [GuiController::class, 'cart'])->name('basket.index');
    Route::post('/basket/add', [BasketController::class, 'store'])->name('basket.add');
    Route::post('/basket/update/{shoppingBasket}', [BasketController::class, 'update'])->name('basket.update');
    Route::delete('/basket/remove/{shoppingBasket}', [BasketController::class, 'destroy'])->name('basket.remove');

    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/refund', [OrderController::class, 'requestRefund'])->name('orders.refund');
    
    Route::post('/orders/{order}/simulate-time', [OrderController::class, 'simulateTime'])->name('orders.simulate-time');
});



Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);

    Route::resource('products', AdminProductController::class)->except(['create', 'edit', 'show']);

    Route::post('/products/variants', [AdminVariantController::class, 'store'])->name('products.variants.store');
    Route::put('/products/variants/{variant}', [AdminVariantController::class, 'update'])->name('products.variants.update');
    Route::delete('/products/variants/{variant}', [AdminVariantController::class, 'destroy'])->name('products.variants.destroy');

    Route::get('/features', [AdminFeatureController::class, 'index'])->name('features.index');
    Route::post('/features', [AdminFeatureController::class, 'store'])->name('features.store');
    Route::put('/features/{feature}', [AdminFeatureController::class, 'update'])->name('features.update');
    Route::delete('/features/{feature}', [AdminFeatureController::class, 'destroy'])->name('features.destroy');

    Route::post('/features/{feature}/values', [AdminFeatureController::class, 'storeValue'])->name('features.values.store');
    Route::delete('/feature-values/{featureValue}', [AdminFeatureController::class, 'destroyValue'])->name('features.values.destroy');
});


require __DIR__.'/settings.php';

Route::get('/media/music/{filename}', function ($filename) {
    $disk = Storage::disk('public');
    $relativePath = 'musics/'.$filename;

    if (! $disk->exists($relativePath)) {
        abort(404);
    }

    return response()->file($disk->path($relativePath));
})->name('music.stream');

Route::fallback(function () {
    return redirect()->route('shop.index');
});