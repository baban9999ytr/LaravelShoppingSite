<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingBasket;
use App\Models\ShoppingOrder;
use App\Services\DeliveryCalculatorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderController extends Controller
{
    
    public function index(Request $request): Response
    {
        $orders = ShoppingOrder::where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (ShoppingOrder $order) => $this->transformOrderForVue($order));

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

   
    public function store(Request $request): RedirectResponse
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
                $orderItems = [];

                foreach ($basketItems as $item) {
                    $unitPrice = 0;

                    if ($item->variant_id) {
                        $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();

                        if (! $variant || $variant->stock < $item->quantity) {
                            throw new Exception("{$item->product->name} ürünü için yeterli stok yok (Kalan stok: ".($variant->stock ?? 0).').');
                        }
                        $variant->decrement('stock', $item->quantity);
                        $unitPrice = $variant->price ?? $item->product->price ?? 0;
                    } else {
                        if (Schema::hasColumn('products', 'stock')) {
                            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                            if (! $product || $product->stock < $item->quantity) {
                                throw new Exception("{$item->product->name} ürünü için yeterli stok yok.");
                            }
                            $product->decrement('stock', $item->quantity);
                        }
                        $unitPrice = $item->product->price ?? 0;
                    }

                    $orderItems[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown',
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $item->quantity,
                    ];
                }

                ShoppingOrder::create([
                    'user_id' => $user->id,
                    'origin_city' => $validated['origin_city'],
                    'destination_city' => $validated['destination_city'],
                    'ordered_at' => $orderedAt,
                    'estimated_arrival_at' => $estimatedArrival,
                    'items' => $orderItems,
                ]);

                ShoppingBasket::where('user_id', $user->id)->delete();
            });

            return redirect()->back()->with('status', 'Siparişiniz başarıyla oluşturuldu!');
        } catch (Throwable $e) {
            Log::error('OrderController@store failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Sipariş oluşturulamadı: '.$e->getMessage(),
            ]);
        }
    }

   
    public function requestRefund(Request $request, int $orderId): RedirectResponse
    {
        $order = ShoppingOrder::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($redirect = $this->validateRefundEligibility($order)) {
            return $redirect;
        }

        try {
            DB::transaction(function () use ($order) {
                $this->restockOrderItems($order->items ?? []);

                $order->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);
            });

            return redirect()->back()->with('status', 'İade talebiniz başarıyla işleme alındı ve stoklar güncellendi.');
        } catch (Throwable $e) {
            Log::error("Order Refund Failed [ID: {$order->id}]: ".$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'İade işlemi sırasında teknik bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
            ]);
        }
    }

    public function simulateTime(Request $request, int $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'days_to_add' => 'required|integer',
        ]);

        $order = ShoppingOrder::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $simulatedArrival = Carbon::parse($order->estimated_arrival_at)->subDays($validated['days_to_add']);

        $order->update([
            'estimated_arrival_at' => $simulatedArrival,
        ]);

        return redirect()->back()->with('status', "Sipariş zamanı {$validated['days_to_add']} gün ileriye simüle edildi.");
    }

    
    private function transformOrderForVue(ShoppingOrder $order): array
    {
        return [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'origin_city' => $order->origin_city,
            'destination_city' => $order->destination_city,
            'status' => $order->status ?? 'processing',
            'ordered_at' => $order->ordered_at?->toIso8601String(),
            'estimated_arrival_at' => $order->estimated_arrival_at?->toIso8601String(),
            'items' => $order->items ?? [],
            'is_delivered' => $order->is_delivered,
            'is_refundable' => $order->is_refundable,
            'remaining_refund_time' => $order->remaining_refund_time,
            'refund_deadline' => $order->refund_deadline?->toIso8601String(),
        ];
    }

  
    private function validateRefundEligibility(ShoppingOrder $order): ?RedirectResponse
    {
        if ($order->status === 'refunded') {
            return redirect()->back()->withErrors(['error' => 'Bu sipariş daha önce zaten iade edilmiş.']);
        }

        if ($order->status === 'cancelled') {
            return redirect()->back()->withErrors(['error' => 'İptal edilmiş siparişler için iade yapılamaz.']);
        }

        if (! $order->is_delivered) {
            return redirect()->back()->withErrors(['error' => 'Siparişiniz henüz teslim edilmediği için iade talebi oluşturulamaz.']);
        }

        if (! $order->is_refundable) {
            return redirect()->back()->withErrors(['error' => 'Yasal 15 günlük iade süresi dolduğu için bu sipariş iade edilemez.']);
        }

        return null;
    }

  
    private function restockOrderItems(array $items): void
    {
        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);

            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::where('id', $item['variant_id'])->lockForUpdate()->first();
                $variant?->increment('stock', $quantity);
            } elseif (! empty($item['product_id'])) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                $product?->increment('stock', $quantity);
            }
        }
    }
}