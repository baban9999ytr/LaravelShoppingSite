<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Category {
    id: number;
    name: string;
    slug?: string;
    [key: string]: any;
}

interface FeatureValue {
    id: number;
    value: string;
    feature?: {
        id?: number;
        name?: string;
    };
    [key: string]: any;
}

interface Variant {
    id: number;
    sku?: string;
    price?: number | string;
    stock?: number;
    color?: string;
    size?: string;
    image_url?: string;
    featureValues?: FeatureValue[];
    [key: string]: any;
}

interface Product {
    id: number;
    name: string;
    price?: number | string;
    slug?: string;
    description?: string;
    image_url?: string;
    [key: string]: any;
}

interface Props {
    product: Product;
    categories?: Category[];
    featureValues?: FeatureValue[];
    variants?: Variant[];
}

const props = withDefaults(defineProps<Props>(), {
    categories: () => [],
    featureValues: () => [],
    variants: () => [],
});

const selectedVariant = ref<Variant | null>(
    props.variants && props.variants.length ? props.variants[0] : null,
);
const quantity = ref(1);

const activeImage = ref<string>(
    selectedVariant.value?.image_url ||
        props.product.image_url ||
        'https://via.placeholder.com/600x800?text=No+Image',
);

const images = computed<string[]>(() => {
    const list: string[] = [];

    if (props.product.image_url) {
        list.push(props.product.image_url);
    }

    props.variants.forEach((v: Variant) => {
        if (v.image_url && !list.includes(v.image_url)) {
            list.push(v.image_url);
        }
    });

    return list.length
        ? list
        : ['https://via.placeholder.com/600x800?text=No+Image'];
});

const currentPrice = computed<number>(() => {
    if (selectedVariant.value && selectedVariant.value.price) {
        return Number(selectedVariant.value.price);
    }

    return Number(props.product.price || 0);
});

const currentSku = computed<string>(() => {
    if (selectedVariant.value && selectedVariant.value.sku) {
        return selectedVariant.value.sku;
    }

    return props.product.slug || `PRD-${props.product.id}`;
});

const currentStock = computed<number>(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.stock ?? 0;
    }

    return 99;
});

const selectVariant = (variant: Variant) => {
    selectedVariant.value = variant;

    if (variant.image_url) {
        activeImage.value = variant.image_url;
    }

    if (quantity.value > currentStock.value) {
        quantity.value = Math.max(1, currentStock.value);
    }
};

// Quantity controls
const incrementQty = () => {
    if (quantity.value < currentStock.value) {
        quantity.value++;
    }
};
const decrementQty = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const addToCart = () => {
    alert(
        `Added ${quantity.value} x "${props.product.name}" (${selectedVariant.value ? 'SKU: ' + currentSku.value : 'Standard'}) to cart!`,
    );
};
</script>

<template>
    <Head :title="`${props.product.name} - LCW`" />

    <div class="min-h-screen bg-white font-sans text-gray-800">
        <div
            class="bg-blue-600 py-2 text-center text-xs font-medium text-white"
        >
            LCW STYLE CONCEPTS — Free Express Shipping on orders over ₺500
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
                            >STORE</span
                        >
                    </Link>

                    <div class="flex items-center space-x-6 text-sm">
                        <Link
                            href="/"
                            class="text-xs font-semibold text-gray-600 hover:text-blue-600"
                        >
                            ← Back to Shop
                        </Link>
                        <button
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
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="mb-6 flex space-x-2 text-xs text-gray-500">
                <Link href="/" class="hover:text-blue-600">Home</Link>
                <span>/</span>
                <span v-if="categories && categories.length">
                    <Link
                        :href="`/?category=${categories[0].slug}`"
                        class="hover:text-blue-600"
                    >
                        {{ categories[0].name }}
                    </Link>
                    <span>/</span>
                </span>
                <span class="truncate font-medium text-gray-900">{{
                    props.product.name
                }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <div
                    class="flex flex-col-reverse gap-4 sm:flex-row lg:col-span-7"
                >
                    <div
                        v-if="images.length > 1"
                        class="flex max-h-[550px] shrink-0 gap-3 overflow-x-auto sm:flex-col sm:overflow-y-auto"
                    >
                        <button
                            v-for="(img, idx) in images"
                            :key="idx"
                            @click="activeImage = img"
                            :class="[
                                'h-20 w-16 shrink-0 overflow-hidden rounded border transition-all',
                                activeImage === img
                                    ? 'border-2 border-blue-600 ring-2 ring-blue-100'
                                    : 'border-gray-200 opacity-70 hover:opacity-100',
                            ]"
                        >
                            <img
                                :src="img"
                                :alt="`Thumbnail ${idx + 1}`"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>

                    <div
                        class="relative aspect-3/4 max-h-[600px] flex-1 overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
                    >
                        <img
                            :src="activeImage"
                            :alt="props.product.name"
                            class="h-full w-full object-cover"
                        />

                        <button
                            class="absolute top-4 right-4 rounded-full bg-white/80 p-2 shadow backdrop-blur transition-colors hover:text-red-500"
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
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-5">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="cat in categories"
                            :key="cat.id"
                            class="rounded bg-blue-50 px-2.5 py-1 text-[10px] font-bold tracking-wider text-blue-700 uppercase"
                        >
                            {{ cat.name }}
                        </span>
                    </div>

                    <div>
                        <h1
                            class="text-2xl leading-tight font-bold text-gray-900 sm:text-3xl"
                        >
                            {{ props.product.name }}
                        </h1>
                        <p class="mt-1 font-mono text-xs text-gray-400">
                            SKU: {{ currentSku }}
                        </p>
                    </div>

                    <div
                        class="flex items-baseline space-x-3 border-b border-gray-100 pb-4"
                    >
                        <span class="text-3xl font-extrabold text-gray-900"
                            >₺{{ currentPrice.toFixed(2) }}</span
                        >
                        <span
                            class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-600"
                            >KDV Dahil</span
                        >
                    </div>

                    <div
                        v-if="props.variants && props.variants.length"
                        class="space-y-3"
                    >
                        <label
                            class="block text-xs font-bold tracking-wider text-gray-700 uppercase"
                        >
                            Varyant Seçiniz:
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="v in props.variants"
                                :key="v.id"
                                @click="selectVariant(v)"
                                :class="[
                                    'flex flex-col justify-between rounded-lg border p-3 text-left transition-all',
                                    selectedVariant?.id === v.id
                                        ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600'
                                        : 'border-gray-200 bg-white hover:border-gray-300',
                                ]"
                            >
                                <div
                                    class="flex w-full items-center justify-between"
                                >
                                    <span
                                        class="text-xs font-semibold text-gray-900"
                                    >
                                        {{ v.color || '' }}
                                        {{ v.size ? `(${v.size})` : '' }}
                                        <span v-if="!v.color && !v.size"
                                            >SKU: {{ v.sku }}</span
                                        >
                                    </span>
                                    <span
                                        class="font-mono text-[11px] font-bold text-gray-700"
                                        >₺{{ Number(v.price).toFixed(2) }}</span
                                    >
                                </div>

                                <div
                                    v-if="
                                        v.featureValues &&
                                        v.featureValues.length
                                    "
                                    class="mt-1.5 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="fv in v.featureValues"
                                        :key="fv.id"
                                        class="rounded border border-gray-200 bg-white px-1 py-0.5 text-[9px] text-gray-600"
                                    >
                                        {{ fv.feature?.name }}: {{ fv.value }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between text-xs">
                            <span
                                class="font-bold tracking-wider text-gray-700 uppercase"
                                >Adet:</span
                            >
                            <span
                                :class="
                                    currentStock > 0
                                        ? 'font-medium text-emerald-600'
                                        : 'font-medium text-rose-600'
                                "
                            >
                                {{
                                    currentStock > 0
                                        ? `Stokta Var (${currentStock} adet)`
                                        : 'Stokta Yok'
                                }}
                            </span>
                        </div>

                        <div class="flex items-center gap-4">
                            <div
                                class="flex items-center overflow-hidden rounded-lg border border-gray-300 bg-gray-50"
                            >
                                <button
                                    @click="decrementQty"
                                    :disabled="quantity <= 1"
                                    class="px-3 py-2 text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                                >
                                    -
                                </button>
                                <span
                                    class="w-12 px-4 py-2 text-center text-sm font-bold text-gray-900"
                                    >{{ quantity }}</span
                                >
                                <button
                                    @click="incrementQty"
                                    :disabled="quantity >= currentStock"
                                    class="px-3 py-2 text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                                >
                                    +
                                </button>
                            </div>

                            <button
                                @click="addToCart"
                                :disabled="currentStock <= 0"
                                class="flex-1 rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold tracking-wider text-white uppercase shadow-md transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                            >
                                Sepete Ekle
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="props.featureValues && props.featureValues.length"
                        class="space-y-2 border-t border-gray-100 pt-4"
                    >
                        <h3
                            class="text-xs font-bold tracking-wider text-gray-500 uppercase"
                        >
                            Ürün Özellikleri
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="fv in props.featureValues"
                                :key="fv.id"
                                class="rounded-md border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                            >
                                <strong class="text-slate-900"
                                    >{{
                                        fv.feature?.name || 'Özellik'
                                    }}:</strong
                                >
                                {{ fv.value }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="props.product.description"
                        class="space-y-2 border-t border-gray-100 pt-4"
                    >
                        <h3
                            class="text-xs font-bold tracking-wider text-gray-500 uppercase"
                        >
                            Açıklama
                        </h3>
                        <p
                            class="text-xs leading-relaxed whitespace-pre-line text-gray-600"
                        >
                            {{ props.product.description }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
