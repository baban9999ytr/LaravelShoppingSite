<script setup lang="ts">
import { router, Link, Head, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Variant {
    id: number;
    sku?: string;
    price?: number | string;
    stock?: number;
    color?: string;
    size?: string;
    image_url?: string;
    feature_values?: {
        id: number;
        value: string;
        feature?: { name: string };
    }[];
    featureValues?: { id: number; value: string; feature?: { name: string } }[];
}

interface Product {
    id: number;
    name: string;
    slug?: string;
    price?: number | string;
    image_url?: string;
    categories?: { id: number; name: string }[];
    variants?: Variant[];
}

interface BasketItem {
    id: number;
    user_id: number;
    product_id: number;
    variant_id?: number | null;
    quantity: number;
    value?: string;
    product: Product;
    variant?: Variant | null;
}

interface Props {
    basket?: BasketItem[];
}

const props = withDefaults(defineProps<Props>(), {
    basket: () => [],
});
const page = usePage<any>();
const auth = computed(() => {
    return page.props.auth as { user?: any } | undefined;
});

const userFavorites = computed<number[]>(() => {
    return (page.props.user_favorites as number[]) || [];
});

const isUserMenuOpen = ref(false);
const loadingId = ref<number | null>(null);

const checkoutForm = useForm({
    origin_city: 'Bursa', 
    destination_city: '',
});
const flashSuccess = computed(() => page.props.flash?.status || page.props.status);
const flashError = computed(() => page.props.errors?.error);
const getItemPrice = (item: BasketItem): number => {
    if (item.variant && item.variant.price) {
        return Number(item.variant.price);
    }
    return Number(item.product.price || 0);
};

const getItemStock = (item: BasketItem): number => {
    if (item.variant) {
        return item.variant.stock ?? 0;
    }
    return 99;
};

const getItemImage = (item: BasketItem): string => {
    if (item.variant?.image_url) {
        return item.variant.image_url;
    }
    if (item.product.image_url) {
        return item.product.image_url;
    }
    return 'https://via.placeholder.com/100x130?text=No+Image';
};

const getVariantLabel = (item: BasketItem): string => {
    if (!item.variant) {
        return '';
    }
    const parts: string[] = [];
    if (item.variant.color) {
        parts.push(item.variant.color);
    }
    if (item.variant.size) {
        parts.push(item.variant.size);
    }
    if (!parts.length && item.variant.sku) {
        parts.push(`SKU: ${item.variant.sku}`);
    }
    return parts.join(' / ');
};

const getVariantFeatures = (item: BasketItem): any[] => {
    if (!item.variant) {
        return [];
    }
    return item.variant.feature_values || item.variant.featureValues || [];
};

const subtotal = computed<number>(() => {
    return props.basket.reduce((sum, item) => {
        return sum + getItemPrice(item) * item.quantity;
    }, 0);
});

const shippingCost = computed<number>(() => {
    return subtotal.value >= 500 ? 0 : 29.9;
});

const totalItemCount = computed<number>(() => {
    return props.basket.reduce((sum, item) => sum + item.quantity, 0);
});

const grandTotal = computed<number>(() => {
    return subtotal.value + shippingCost.value;
});

const updateQuantity = (item: BasketItem, newQty: number) => {
    const maxStock = getItemStock(item);
    if (newQty < 1) newQty = 1;
    if (newQty > maxStock) newQty = maxStock;
    if (newQty === item.quantity) return;

    loadingId.value = item.id;
    router.post(
        `/basket/update/${item.id}`,
        { quantity: newQty },
        {
            preserveScroll: true,
            onFinish: () => {
                loadingId.value = null;
            },
        },
    );
};

const removeItem = (item: BasketItem) => {
    loadingId.value = item.id;
    router.delete(`/basket/remove/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            loadingId.value = null;
        },
    });
};

const submitOrder = () => {
    // Assuming your web.php route is POST /order targeting ProductController@storeOrder
    checkoutForm.post('/order', {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: reset form or show success message if the backend redirects back
            checkoutForm.reset();
        }
    });
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Shopping Cart - LCW" />

    <div class="min-h-screen bg-gray-50 font-sans text-gray-800">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 py-2 text-center text-xs font-medium text-white">
            🛒 Your Shopping Cart — Review Your Items Before Checkout
        </div>

        <header class="sticky top-0 z-50 border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center space-x-2">
                        <span class="text-2xl font-black tracking-tight text-blue-700">LCW</span>
                        <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800">VUE</span>
                    </Link>

                    <div class="flex items-center space-x-6 text-sm">
                        <Link href="/" class="text-xs font-semibold text-gray-600 hover:text-blue-600">
                            ← Continue Shopping
                        </Link>

                        <div class="relative">
                            <template v-if="auth?.user">
                                <button
                                    @click="isUserMenuOpen = !isUserMenuOpen"
                                    class="flex flex-col items-center text-gray-700 hover:text-blue-600 focus:outline-none"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="mt-0.5 max-w-[80px] truncate text-[11px] font-medium">
                                        {{ auth.user.name.split(' ')[0] }}
                                    </span>
                                </button>

                                <div
                                    v-if="isUserMenuOpen"
                                    @click="isUserMenuOpen = false"
                                    class="absolute right-0 z-50 mt-2 w-48 rounded-md border border-gray-200 bg-white py-1 text-xs shadow-lg"
                                >
                                    <div class="border-b border-gray-100 px-4 py-2">
                                        <p class="truncate font-bold text-gray-900">{{ auth.user.name }}</p>
                                        <p class="truncate text-gray-500">{{ auth.user.email }}</p>
                                    </div>
                                    <Link href="/dashboard-redirect" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard</Link>
                                    <button @click="logout" class="block w-full border-t border-gray-100 px-4 py-2 text-left text-red-600 hover:bg-red-50">
                                        Log Out
                                    </button>
                                </div>
                            </template>
                        </div>

                        <Link href="/favorites" class="relative flex flex-col items-center text-gray-700 hover:text-red-600">
                            <svg
                                class="h-6 w-6"
                                :class="userFavorites.length ? 'fill-current text-red-500' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" :fill="userFavorites.length ? 'currentColor' : 'none'" />
                            </svg>
                            <span class="mt-0.5 text-[11px]">Favorites</span>
                            <span v-if="userFavorites.length" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                {{ userFavorites.length }}
                            </span>
                        </Link>

                        <div class="relative flex flex-col items-center text-blue-600">
                            <svg class="h-6 w-6 fill-current" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" fill="currentColor" />
                            </svg>
                            <span class="mt-0.5 text-[11px] font-semibold">Cart</span>
                            <span v-if="totalItemCount > 0" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                {{ totalItemCount }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Global Error / Success Alerts -->
            <div v-if="flashError" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                ⚠️ {{ flashError }}
            </div>

            <div v-if="flashSuccess" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
                ✅ {{ flashSuccess }}
            </div>

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    Shopping Cart
                    <span v-if="basket.length" class="ml-2 text-base font-normal text-gray-500">
                        ({{ totalItemCount }} {{ totalItemCount === 1 ? 'item' : 'items' }})
                    </span>
                </h1>
            </div>

            <div v-if="basket.length" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Cart Items -->
                <div class="space-y-4 lg:col-span-2">
                    <div
                        v-for="item in basket"
                        :key="item.id"
                        class="flex gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all hover:shadow-md"
                        :class="loadingId === item.id ? 'opacity-60' : ''"
                    >
                        <!-- Product Image -->
                        <Link :href="`/products/${item.product.slug || item.product.id}`" class="shrink-0">
                            <div class="h-32 w-24 overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                                <img :src="getItemImage(item)" :alt="item.product.name" class="h-full w-full object-cover" />
                            </div>
                        </Link>

                        <!-- Product Details -->
                        <div class="flex flex-1 flex-col justify-between">
                            <div>
                                <div v-if="item.product.categories && item.product.categories.length" class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">
                                    {{ item.product.categories.map((c: any) => c.name).join(', ') }}
                                </div>
                                <Link :href="`/products/${item.product.slug || item.product.id}`">
                                    <h3 class="text-sm font-semibold text-gray-900 hover:text-blue-600">
                                        {{ item.product.name }}
                                    </h3>
                                </Link>

                                <div v-if="getVariantLabel(item)" class="mt-1 text-xs text-gray-500">
                                    {{ getVariantLabel(item) }}
                                </div>

                                <div v-if="getVariantFeatures(item).length" class="mt-1 flex flex-wrap gap-1">
                                    <span
                                        v-for="fv in getVariantFeatures(item)"
                                        :key="fv.id"
                                        class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[9px] text-slate-600"
                                    >
                                        {{ fv.feature?.name }}: {{ fv.value }}
                                    </span>
                                </div>

                                <div class="mt-1.5 text-[10px]" :class="getItemStock(item) > 0 ? 'text-emerald-600' : 'text-rose-500'">
                                    {{ getItemStock(item) > 0 ? `${getItemStock(item)} in stock` : 'Out of stock' }}
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <!-- Quantity Controls -->
                                <div class="flex items-center overflow-hidden rounded-lg border border-gray-300 bg-gray-50">
                                    <button
                                        @click="updateQuantity(item, item.quantity - 1)"
                                        :disabled="item.quantity <= 1 || loadingId === item.id"
                                        class="px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                                    >
                                        −
                                    </button>
                                    <span class="w-8 py-1.5 text-center text-xs font-bold text-gray-900">{{ item.quantity }}</span>
                                    <button
                                        @click="updateQuantity(item, item.quantity + 1)"
                                        :disabled="item.quantity >= getItemStock(item) || loadingId === item.id"
                                        class="px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                                    >
                                        +
                                    </button>
                                </div>

                                <div class="flex items-center gap-4">
                                    <span class="text-sm font-bold text-gray-900">₺{{ (getItemPrice(item) * item.quantity).toFixed(2) }}</span>
                                    <button
                                        @click="removeItem(item)"
                                        :disabled="loadingId === item.id"
                                        class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                                        title="Remove from cart"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary & Form -->
                <div class="lg:col-span-1">
                    <form @submit.prevent="submitOrder" class="sticky top-24 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-gray-900">Order Summary</h2>

                        <div class="mt-4 space-y-3 border-b border-gray-100 pb-4 text-sm">
                            <div class="flex items-center justify-between text-gray-600">
                                <span>Subtotal ({{ totalItemCount }} {{ totalItemCount === 1 ? 'item' : 'items' }})</span>
                                <span class="font-semibold text-gray-900">₺{{ subtotal.toFixed(2) }}</span>
                            </div>

                            <div class="flex items-center justify-between text-gray-600">
                                <span>Shipping</span>
                                <span v-if="shippingCost === 0" class="font-semibold text-emerald-600">FREE</span>
                                <span v-else class="font-semibold text-gray-900">₺{{ shippingCost.toFixed(2) }}</span>
                            </div>

                            <div v-if="subtotal < 500 && subtotal > 0" class="rounded-lg bg-amber-50 px-3 py-2 text-[11px] text-amber-700">
                                Add ₺{{ (500 - subtotal).toFixed(2) }} more for <strong>FREE shipping!</strong>
                            </div>

                            <div v-if="shippingCost === 0" class="rounded-lg bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700">
                                🎉 You qualify for <strong>FREE shipping!</strong>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between text-base">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="text-xl font-extrabold text-gray-900">₺{{ grandTotal.toFixed(2) }}</span>
                        </div>

                        <!-- Checkout Details Fields -->
                        <div class="mt-6 border-t border-gray-100 pt-4">
                            <h3 class="mb-3 text-sm font-semibold text-gray-900">Delivery Information</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700">Origin City</label>
                                    <input 
                                        type="text" 
                                        v-model="checkoutForm.origin_city"
                                        placeholder="e.g. Istanbul"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="checkoutForm.errors.origin_city" class="mt-1 text-xs text-red-600">{{ checkoutForm.errors.origin_city }}</p>
                                </div>
                                
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-700">Destination City</label>
                                    <input 
                                        type="text" 
                                        v-model="checkoutForm.destination_city"
                                        placeholder="e.g. Ankara"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p v-if="checkoutForm.errors.destination_city" class="mt-1 text-xs text-red-600">{{ checkoutForm.errors.destination_city }}</p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="checkoutForm.processing"
                            class="mt-6 w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold tracking-wider text-white uppercase shadow-md transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-400"
                        >
                            <span v-if="checkoutForm.processing">Processing...</span>
                            <span v-else>Proceed to Checkout</span>
                        </button>

                        <Link href="/" class="mt-3 block w-full text-center text-xs font-semibold text-blue-600 hover:underline">
                            Continue Shopping
                        </Link>
                    </form>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="mx-auto flex max-w-md flex-col items-center rounded-2xl border border-gray-200 bg-white py-16 text-center shadow-sm">
                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-10 w-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Your cart is empty</h2>
                <p class="mt-2 px-6 text-sm text-gray-500">
                    Looks like you haven't added any items to your cart yet. Start exploring our products!
                </p>
                <Link href="/" class="mt-6 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-colors hover:bg-blue-700">
                    Start Shopping
                </Link>
            </div>
        </main>
    </div>
</template>