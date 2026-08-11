<script setup lang="ts">
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Product {
    id: number;
    name: string;
    slug?: string;
    price?: number | string;
    image_url?: string;
    categories?: { id: number; name: string; slug?: string }[];
    feature_values?: {
        id: number;
        value: string;
        feature?: { name: string };
    }[];
    variants?: {
        id: number;
        stock?: number;
        price?: number | string;
        color?: string;
        size?: string;
    }[];
}

interface FavoriteItem {
    id?: number;
    user_id: number;
    product_id: number;
    product: Product;
    value?: string;
}

interface PageProps {
    auth?: { user?: any };
    user_favorites?: number[];
    user_basket?: any[];
    favorites?: FavoriteItem[];
    [key: string]: any;
}

const page = usePage<PageProps>();

const auth = computed(() => {
    return page.props.auth;
});

const favorites = computed(() => {
    return page.props.favorites || [];
});

const userFavorites = computed<number[]>(() => {
    return page.props.user_favorites || [];
});

const userBasket = computed<any[]>(() => {
    const basket = page.props.user_basket;

    if (Array.isArray(basket)) {
        return basket;
    }

    return [];
});

const cartItemCount = computed<number>(() => {
    return userBasket.value.reduce((sum: number, item: any) => {
        return sum + (item?.quantity || 1);
    }, 0);
});

const loadingId = ref<number | null>(null);
const isUserMenuOpen = ref(false);

const removeFavorite = (productId: number) => {
    loadingId.value = productId;
    router.delete(`/favorites/${productId}`, {
        preserveScroll: true,
        onFinish: () => {
            loadingId.value = null;
        },
    });
};

const addToCart = (product: Product) => {
    loadingId.value = product.id;
    const firstVariant = product.variants?.find((v) => (v.stock ?? 0) > 0);
    router.post(
        '/basket/add',
        {
            product_id: product.id,
            variant_id: firstVariant?.id || null,
            quantity: 1,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                loadingId.value = null;
            },
        },
    );
};

const logout = () => {
    router.post('/logout');
};
</script>
<template>
    <Head title="My Favorites - LCW" />

    <div class="min-h-screen bg-gray-50 font-sans text-gray-800">
        <div
            class="bg-gradient-to-r from-red-500 to-pink-500 py-2 text-center text-xs font-medium text-white"
        >Your Favorite Products — All in One Place
        </div>

        <header class="sticky top-0 z-50 border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center space-x-2">
                        <span
                            class="text-2xl font-black tracking-tight text-blue-700"
                            >LCW</span
                        >
                        <span
                            class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800"
                            >VUE</span
                        >
                    </Link>

                    <div class="flex items-center space-x-6 text-sm">
                        <Link
                            href="/"
                            class="text-xs font-semibold text-gray-600 hover:text-blue-600"
                        >
                            ← Back to Shop
                        </Link>

                        <div class="relative">
                            <template v-if="auth?.user">
                                <button
                                    @click="isUserMenuOpen = !isUserMenuOpen"
                                    class="flex flex-col items-center text-gray-700 hover:text-blue-600 focus:outline-none"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    <span
                                        class="mt-0.5 max-w-[80px] truncate text-[11px] font-medium"
                                    >
                                        {{ auth.user.name.split(' ')[0] }}
                                    </span>
                                </button>

                                <div
                                    v-if="isUserMenuOpen"
                                    @click="isUserMenuOpen = false"
                                    class="absolute right-0 z-50 mt-2 w-48 rounded-md border border-gray-200 bg-white py-1 text-xs shadow-lg"
                                >
                                    <div
                                        class="border-b border-gray-100 px-4 py-2"
                                    >
                                        <p
                                            class="truncate font-bold text-gray-900"
                                        >
                                            {{ auth.user.name }}
                                        </p>
                                        <p class="truncate text-gray-500">
                                            {{ auth.user.email }}
                                        </p>
                                    </div>

                                    <Link
                                        href="/dashboard-redirect"
                                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                        >Dashboard</Link
                                    >

                                    <button
                                        @click="logout"
                                        class="block w-full border-t border-gray-100 px-4 py-2 text-left text-red-600 hover:bg-red-50"
                                    >
                                        Log Out
                                    </button>
                                </div>
                            </template>
                        </div>

                        <Link
                            href="/favorites"
                            class="relative flex flex-col items-center text-red-500"
                        >
                            <svg
                                class="h-6 w-6 fill-current"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                    fill="currentColor"
                                />
                            </svg>
                            <span class="mt-0.5 text-[11px] font-semibold"
                                >Favorites</span
                            >
                            <span
                                v-if="userFavorites.length"
                                class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                            >
                                {{ userFavorites.length }}
                            </span>
                        </Link>

                        <Link
                            href="/basket"
                            class="relative flex flex-col items-center text-gray-700 hover:text-blue-600"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                />
                            </svg>
                            <span class="mt-0.5 text-[11px]">Cart</span>
                            <span
                                v-if="cartItemCount > 0"
                                class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white"
                            >
                                {{ cartItemCount }}
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    My Favorites
                    <span
                        v-if="favorites.length"
                        class="ml-2 text-base font-normal text-gray-500"
                        >({{ favorites.length }}
                        {{ favorites.length === 1 ? 'item' : 'items' }})</span
                    >
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Products you've saved for later
                </p>
            </div>

            <!-- Favorites Grid -->
            <div
                v-if="favorites.length"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="fav in favorites"
                    :key="fav.product_id"
                    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                >
                    <!-- Remove button -->
                    <button
                        @click="removeFavorite(fav.product.id)"
                        :disabled="loadingId === fav.product.id"
                        class="absolute top-3 right-3 z-10 rounded-full bg-white/90 p-2 text-red-500 shadow-md backdrop-blur transition-all hover:scale-110 hover:bg-red-50"
                    >
                        <svg
                            class="h-4 w-4 fill-current"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                fill="currentColor"
                            />
                        </svg>
                    </button>

                    <!-- Image -->
                    <Link
                        :href="`/products/${fav.product.slug || fav.product.id}`"
                    >
                        <div
                            class="relative aspect-3/4 overflow-hidden bg-gray-100"
                        >
                            <img
                                :src="
                                    fav.product.image_url ||
                                    'https://via.placeholder.com/300x400?text=No+Image'
                                "
                                :alt="fav.product.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        </div>
                    </Link>

                    <!-- Product Info -->
                    <div class="p-4">
                        <div
                            v-if="
                                fav.product.categories &&
                                fav.product.categories.length
                            "
                            class="mb-1 text-[10px] font-semibold tracking-wider text-gray-400 uppercase"
                        >
                            {{
                                fav.product.categories
                                    .map((c: any) => c.name)
                                    .join(', ')
                            }}
                        </div>

                        <Link
                            :href="`/products/${fav.product.slug || fav.product.id}`"
                        >
                            <h3
                                class="line-clamp-2 text-sm font-semibold text-gray-800 transition-colors group-hover:text-blue-600"
                            >
                                {{ fav.product.name }}
                            </h3>
                        </Link>

                        <div
                            v-if="
                                fav.product.feature_values &&
                                fav.product.feature_values.length
                            "
                            class="mt-2 flex flex-wrap gap-1"
                        >
                            <span
                                v-for="fv in fav.product.feature_values.slice(
                                    0,
                                    3,
                                )"
                                :key="fv.id"
                                class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[9px] text-slate-600"
                            >
                                {{ fv.feature?.name }}: {{ fv.value }}
                            </span>
                        </div>

                        <div
                            class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3"
                        >
                            <span class="text-lg font-bold text-gray-900"
                                >₺{{
                                    Number(fav.product.price || 0).toFixed(2)
                                }}</span
                            >

                            <div class="flex items-center gap-2">
                                <Link
                                    :href="`/products/${fav.product.slug || fav.product.id}`"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-[11px] font-semibold text-gray-600 transition-colors hover:border-blue-500 hover:text-blue-600"
                                >
                                    Details
                                </Link>
                                <button
                                    @click="addToCart(fav.product)"
                                    :disabled="loadingId === fav.product.id"
                                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                                >
                                    <span v-if="loadingId === fav.product.id"
                                        >...</span
                                    >
                                    <span v-else>Add to Cart</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="mx-auto flex max-w-md flex-col items-center rounded-2xl border border-gray-200 bg-white py-16 text-center shadow-sm"
            >
                <div
                    class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-50"
                >
                    <svg
                        class="h-10 w-10 text-red-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                        />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">
                    No favorites yet
                </h2>
                <p class="mt-2 px-6 text-sm text-gray-500">
                    Start exploring products and tap the heart icon to save your
                    favorites here.
                </p>
                <Link
                    href="/"
                    class="mt-6 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-colors hover:bg-blue-700"
                >
                    Explore Products
                </Link>
            </div>
        </main>
    </div>
</template>
