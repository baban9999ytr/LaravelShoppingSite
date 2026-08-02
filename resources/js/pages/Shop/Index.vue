<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface Category {
    id: number;
    name: string;
    slug?: string;
    children?: Category[];
}

interface FeatureValue {
    id: number;
    value: string;
}

interface Feature {
    id: number;
    name: string;
    values?: FeatureValue[];
}

interface BasketItemObject {
    id?: number;
    product_id?: number;
    quantity?: number;
    [key: string]: any;
}

type BasketType = number[] | BasketItemObject[] | { data?: BasketItemObject[]; total?: number };

interface Props {
    auth?: {
        user?: User | null;
    };
    categories?: Category[];
    products?: {
        data: any[];
        links: any[];
        total: number;
    };
    features?: Feature[];
    filters?: {
        search?: string;
        min_price?: string | number;
        max_price?: string | number;
        features?: number | number[];
        [key: string]: any;
    };
    user_favorites?: number[];
    user_basket?: BasketType;
}

const props = withDefaults(defineProps<Props>(), {
    auth: () => ({ user: null }),
    categories: () => [],
    products: () => ({ data: [], links: [], total: 0 }),
    features: () => [],
    filters: () => ({}),
    user_favorites: () => [],
    user_basket: () => [],
});

const isUserMenuOpen = ref(false);
const search = ref(props.filters?.search || '');
const openMenuId = ref<number | null>(null);

const priceFilter = reactive({
    min_price: props.filters?.min_price || '',
    max_price: props.filters?.max_price || '',
});

const selectedFeatures = ref<number[]>(
    Array.isArray(props.filters?.features)
        ? props.filters.features.map(Number)
        : props.filters?.features
          ? [Number(props.filters.features)]
          : [],
);

const cartItemCount = computed<number>(() => {
    if (!props.user_basket) {
        return 0;
    }

    if (Array.isArray(props.user_basket)) {
        return props.user_basket.reduce((sum: number, item: any) => {
            if (typeof item === 'number') {
                return sum + 1;
            }

            if (item && typeof item.quantity === 'number') {
                return sum + item.quantity;
            }

            return sum + 1;
        }, 0);
    }

    if (
        typeof props.user_basket === 'object' &&
        'total' in props.user_basket &&
        typeof props.user_basket.total === 'number'
    ) {
        return props.user_basket.total;
    }

    return 0;
});

const handleSearch = () => {
    router.get(
        '/',
        { ...props.filters, search: search.value },
        { preserveState: true, preserveScroll: true },
    );
};

const applyPriceFilter = () => {
    router.get(
        '/',
        {
            ...props.filters,
            min_price: priceFilter.min_price,
            max_price: priceFilter.max_price,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const toggleFeatureFilter = (featureValueId: number) => {
    const index = selectedFeatures.value.indexOf(featureValueId);

    if (index > -1) {
        selectedFeatures.value.splice(index, 1);
    } else {
        selectedFeatures.value.push(featureValueId);
    }

    router.get(
        '/',
        {
            ...props.filters,
            features: selectedFeatures.value.length
                ? selectedFeatures.value
                : undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    search.value = '';
    priceFilter.min_price = '';
    priceFilter.max_price = '';
    selectedFeatures.value = [];
    router.get('/', {}, { preserveState: true, preserveScroll: true });
};

const logout = () => {
    router.post('/logout');
};

const toggleFavorite = (productId: number) => {
    if (!props.auth?.user) {
        router.get('/login');
        return;
    }

    router.post(
        '/favorites/toggle',
        { product_id: productId },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const addToCart = (productId: number) => {
    if (!props.auth?.user) {
        router.get('/login');
        return;
    }

    router.post(
        '/basket/add',
        { product_id: productId },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const isFavorite = (productId: number): boolean => {
    return props.user_favorites ? props.user_favorites.includes(productId) : false;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 font-sans text-gray-800">
        <div
            class="bg-blue-600 py-2 text-center text-xs font-medium text-white"
        >
            LCW STYLE CONCEPTS — Free Express Shipping on orders over $50
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

                    <form
                        @submit.prevent="handleSearch"
                        class="mx-8 max-w-lg flex-1"
                    >
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search products, categories..."
                                class="w-full rounded-full border border-gray-300 bg-gray-50 py-2 pr-10 pl-4 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            <button
                                type="submit"
                                class="absolute top-2.5 right-3 text-gray-400 hover:text-blue-600"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <div class="flex items-center space-x-6 text-sm">
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
                                        href="/dashboard"
                                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                        >Dashboard</Link
                                    >
                                    <Link
                                        href="/profile"
                                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                                        >My Account</Link
                                    >

                                    <button
                                        @click="logout"
                                        class="block w-full border-t border-gray-100 px-4 py-2 text-left text-red-600 hover:bg-red-50"
                                    >
                                        Log Out
                                    </button>
                                </div>
                            </template>

                            <template v-else>
                                <div
                                    class="flex items-center space-x-2 text-xs"
                                >
                                    <Link
                                        href="/login"
                                        class="font-semibold text-gray-700 hover:text-blue-600"
                                        >Log In</Link
                                    >
                                    <span class="text-gray-300">|</span>
                                    <Link
                                        href="/register"
                                        class="font-semibold text-blue-600 hover:underline"
                                        >Register</Link
                                    >
                                </div>
                            </template>
                        </div>

                        <Link
                            href="/favorites"
                            class="relative flex flex-col items-center text-gray-700 hover:text-red-600"
                        >
                            <svg
                                class="h-6 w-6"
                                :class="
                                    auth?.user && user_favorites?.length
                                        ? 'fill-current text-red-500'
                                        : ''
                                "
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                    :fill="
                                        auth?.user && user_favorites?.length
                                            ? 'currentColor'
                                            : 'none'
                                    "
                                />
                            </svg>
                            <span class="mt-0.5 text-[11px]">Favorites</span>
                            <span
                                v-if="auth?.user && user_favorites?.length"
                                class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                            >
                                {{ user_favorites.length }}
                            </span>
                        </Link>

                        <Link
                            href="/basket"
                            class="relative flex flex-col items-center text-gray-700 hover:text-blue-600"
                        >
                            <svg
                                class="h-6 w-6"
                                :class="
                                    cartItemCount > 0
                                        ? 'fill-current text-blue-600'
                                        : ''
                                "
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                    :fill="
                                        cartItemCount > 0
                                            ? 'currentColor'
                                            : 'none'
                                    "
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

                <nav
                    class="relative flex min-h-10.5 items-center space-x-8 border-t border-gray-100 py-2 text-sm font-semibold text-gray-700"
                >
                    <Link
                        href="/"
                        :class="[
                            'whitespace-nowrap hover:text-blue-600',
                            !filters?.category
                                ? 'border-b-2 border-blue-600 pb-1 text-blue-600'
                                : '',
                        ]"
                    >
                        All Products
                    </Link>

                    <Link
                        href="/?category=kadin"
                        :class="[
                            'whitespace-nowrap hover:text-blue-600',
                            filters?.category === 'kadin'
                                ? 'font-bold text-blue-600'
                                : '',
                        ]"
                    >
                        Kadın
                    </Link>

                    <Link
                        href="/?category=erkek"
                        :class="[
                            'whitespace-nowrap hover:text-blue-600',
                            filters?.category === 'erkek'
                                ? 'font-bold text-blue-600'
                                : '',
                        ]"
                    >
                        Erkek
                    </Link>

                    <Link
                        href="/?category=kislik"
                        :class="[
                            'whitespace-nowrap hover:text-blue-600',
                            filters?.category === 'kislik'
                                ? 'font-bold text-blue-600'
                                : '',
                        ]"
                    >
                        Kışlık
                    </Link>

                    <template v-if="categories && categories.length">
                        <div
                            v-for="cat in categories"
                            :key="cat.id"
                            class="group relative py-1"
                            @mouseenter="openMenuId = cat.id"
                            @mouseleave="openMenuId = null"
                        >
                            <Link
                                :href="`/?category=${cat.slug}`"
                                :class="[
                                    'inline-flex items-center whitespace-nowrap hover:text-blue-600',
                                    filters?.category === cat.slug
                                        ? 'font-bold text-blue-600'
                                        : '',
                                ]"
                            >
                                {{ cat.name }}
                                <svg
                                    v-if="cat.children && cat.children.length"
                                    class="ml-1 h-3.5 w-3.5 text-gray-400 group-hover:text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </Link>

                            <div
                                v-if="
                                    cat.children &&
                                    cat.children.length &&
                                    openMenuId === cat.id
                                "
                                class="absolute top-full left-0 z-50 mt-1 grid min-w-120 grid-cols-2 gap-4 rounded-b-md border border-gray-200 bg-white p-4 shadow-xl sm:grid-cols-3"
                            >
                                <div
                                    v-for="subCat in cat.children"
                                    :key="subCat.id"
                                    class="space-y-1"
                                >
                                    <Link
                                        :href="`/?category=${subCat.slug}`"
                                        class="block border-b border-gray-100 pb-1 text-xs font-bold text-gray-900 hover:text-blue-600"
                                    >
                                        {{ subCat.name }}
                                    </Link>

                                    <div
                                        v-if="
                                            subCat.children &&
                                            subCat.children.length
                                        "
                                        class="space-y-0.5 pt-1"
                                    >
                                        <Link
                                            v-for="leafCat in subCat.children"
                                            :key="leafCat.id"
                                            :href="`/?category=${leafCat.slug}`"
                                            class="block text-[11px] text-gray-600 hover:text-blue-600 hover:underline"
                                        >
                                            {{ leafCat.name }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-8 lg:flex-row">
                <aside class="w-full shrink-0 lg:w-64">
                    <div
                        class="space-y-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b pb-3"
                        >
                            <h3 class="font-bold text-gray-900">Filters</h3>
                            <button
                                @click="clearFilters"
                                class="text-xs text-blue-600 hover:underline"
                            >
                                Clear all
                            </button>
                        </div>

                        <div
                            v-if="categories && categories.length"
                            class="space-y-2"
                        >
                            <label
                                class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >Categories</label
                            >
                            <ul class="space-y-1 text-xs">
                                <li v-for="cat in categories" :key="cat.id">
                                    <Link
                                        :href="`/?category=${cat.slug}`"
                                        :class="[
                                            'block py-1 hover:text-blue-600',
                                            filters?.category === cat.slug
                                                ? 'font-bold text-blue-600'
                                                : 'text-gray-600',
                                        ]"
                                    >
                                        {{ cat.name }}
                                    </Link>
                                    <ul
                                        v-if="
                                            cat.children && cat.children.length
                                        "
                                        class="mt-1 space-y-1 border-l border-gray-100 pl-3"
                                    >
                                        <li
                                            v-for="child in cat.children"
                                            :key="child.id"
                                        >
                                            <Link
                                                :href="`/?category=${child.slug}`"
                                                :class="[
                                                    'block py-0.5 hover:text-blue-600',
                                                    filters?.category ===
                                                    child.slug
                                                        ? 'font-bold text-blue-600'
                                                        : 'text-gray-500',
                                                ]"
                                            >
                                                {{ child.name }}
                                            </Link>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="features && features.length"
                            class="space-y-4 border-t border-gray-100 pt-4"
                        >
                            <div
                                v-for="feature in features"
                                :key="feature.id"
                                class="space-y-2"
                            >
                                <label
                                    class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    {{ feature.name }}
                                </label>
                                <div
                                    v-if="
                                        feature.values && feature.values.length
                                    "
                                    class="max-h-40 space-y-1.5 overflow-y-auto pr-1"
                                >
                                    <label
                                        v-for="val in feature.values"
                                        :key="val.id"
                                        class="flex cursor-pointer items-center space-x-2 text-xs text-gray-700 hover:text-blue-600"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="val.id"
                                            :checked="
                                                selectedFeatures.includes(
                                                    val.id,
                                                )
                                            "
                                            @change="
                                                toggleFeatureFilter(val.id)
                                            "
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span>{{ val.value }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-gray-100 pt-4">
                            <label
                                class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >Price Range</label
                            >
                            <div class="flex items-center space-x-2">
                                <input
                                    v-model="priceFilter.min_price"
                                    type="number"
                                    placeholder="Min"
                                    class="w-full rounded border px-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                />
                                <span class="text-gray-400">-</span>
                                <input
                                    v-model="priceFilter.max_price"
                                    type="number"
                                    placeholder="Max"
                                    class="w-full rounded border px-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                />
                            </div>
                            <button
                                @click="applyPriceFilter"
                                class="mt-2 w-full rounded bg-gray-900 py-2 text-xs font-medium text-white transition-colors hover:bg-blue-600"
                            >
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </aside>

                <section class="flex-1">
                    <div class="mb-6 flex items-center justify-between">
                        <h1 class="text-xl font-bold text-gray-900">
                            {{
                                filters?.category
                                    ? filters.category.toUpperCase()
                                    : 'All Products'
                            }}
                            <span class="text-sm font-normal text-gray-500"
                                >({{ products?.total || 0 }} items)</span
                            >
                        </h1>
                    </div>

                    <div
                        v-if="products?.data && products.data.length"
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4"
                    >
                        <Link
                            v-for="product in products.data"
                            :key="product.id"
                            :href="`/products/${product.slug || product.id}`"
                            class="group block flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div
                                class="relative aspect-3/4 overflow-hidden bg-gray-100"
                            >
                                <img
                                    :src="
                                        product.image_url ||
                                        'https://via.placeholder.com/300x400?text=No+Image'
                                    "
                                    :alt="product.name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />

                                <button
                                    @click.prevent="toggleFavorite(product.id)"
                                    class="absolute top-2 right-2 rounded-full bg-white p-1.5 shadow-md transition-colors"
                                    :class="
                                        isFavorite(product.id)
                                            ? 'fill-red-500 text-red-500'
                                            : 'text-gray-400 hover:text-red-500'
                                    "
                                >
                                    <svg
                                        class="h-4 w-4"
                                        :fill="
                                            isFavorite(product.id)
                                                ? 'currentColor'
                                                : 'none'
                                        "
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <div
                                class="flex flex-1 flex-col justify-between p-3"
                            >
                                <div>
                                    <div
                                        class="mb-1 text-[10px] font-semibold tracking-wider text-gray-400 uppercase"
                                    >
                                        {{
                                            product.categories
                                                ?.map((c: any) => c.name)
                                                .join(', ')
                                        }}
                                    </div>
                                    <h2
                                        class="line-clamp-2 text-xs font-medium text-gray-800 transition-colors group-hover:text-blue-600"
                                    >
                                        {{ product.name }}
                                    </h2>

                                    <div
                                        v-if="
                                            product.feature_values &&
                                            product.feature_values.length
                                        "
                                        class="mt-2 flex flex-wrap gap-1"
                                    >
                                        <span
                                            v-for="fv in product.feature_values"
                                            :key="fv.id"
                                            class="rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[9px] text-slate-600"
                                        >
                                            {{ fv.feature?.name }}:
                                            {{ fv.value }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-baseline justify-between"
                                >
                                    <span
                                        class="text-sm font-bold text-gray-900"
                                        >₺{{
                                            Number(product.price).toFixed(2)
                                        }}</span
                                    >

                                    <button
                                        @click.prevent="addToCart(product.id)"
                                        class="text-xs font-semibold text-blue-600 hover:underline"
                                    >
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="rounded-lg border border-gray-200 bg-white py-12 text-center"
                    >
                        <p class="text-sm text-gray-500">
                            No products found matching your filter options.
                        </p>
                    </div>

                    <div
                        v-if="products?.links && products.links.length"
                        class="mt-8 flex justify-center space-x-1"
                    >
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'rounded border px-3 py-1.5 text-xs',
                                link.active
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
                                !link.url
                                    ? 'pointer-events-none opacity-50'
                                    : '',
                            ]"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>
