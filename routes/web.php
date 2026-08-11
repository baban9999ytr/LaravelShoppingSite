<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuiController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [GuiController::class, 'index'])->name('shop.index');
Route::get('/home', fn () => redirect()->route('shop.index'))->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('/favorites', [GuiController::class, 'favorites'])->name('favorites.index');
    Route::post('/favorites/toggle', [ProductController::class, 'toggleFavorite'])->name('favorites.toggle');
    Route::delete('/favorites/{product_id}', [ProductController::class, 'removeFavorite'])->name('favorites.remove');

    Route::get('/basket', [GuiController::class, 'cart'])->name('basket.index');
    Route::post('/basket/add', [ProductController::class, 'AddToBasket'])->name('basket.add');
    Route::post('/basket/update/{shoppingBasket}', [ProductController::class, 'updateCartItem'])->name('basket.update');
    Route::delete('/basket/remove/{shoppingBasket}', [ProductController::class, 'removeFromBasket'])->name('basket.remove');
    Route::post('/order', [ProductController::class, 'storeOrder'])->name('order.store');
    Route::get('/products/{productIdentifier}', [GuiController::class, 'productDetails'])->name('products.show');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::resource('products', ProductController::class)->except(['create', 'edit', 'show']);

    Route::post('/products/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
    Route::put('/products/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
    Route::delete('/products/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');

    Route::get('/features', [ProductController::class, 'indexFeatures'])->name('features.index');
    Route::post('/features', [ProductController::class, 'storeFeature'])->name('features.store');
    Route::put('/features/{feature}', [ProductController::class, 'updateFeature'])->name('features.update');
    Route::delete('/features/{feature}', [ProductController::class, 'destroyFeature'])->name('features.destroy');

    Route::post('/features/{feature}/values', [ProductController::class, 'storeFeatureValue'])->name('features.values.store');
    Route::delete('/feature-values/{featureValue}', [ProductController::class, 'destroyFeatureValue'])->name('features.values.destroy');
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

Route::middleware(['auth'])->get('/dashboard-redirect', function (Request $request) {
    $team = $request->user()->currentTeam;

    if (! $team) {
        return redirect()->route('shop.index');
    }

    return redirect()->route('dashboard', ['current_team' => $team->slug ?? $team->id]);
});
