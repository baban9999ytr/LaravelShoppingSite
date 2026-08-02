<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Category {
    id: number;
    name: string;
    children?: Category[];
    [key: string]: any;
}

interface Variant {
    id: number;
    product_id: number;
    sku: string;
    price: number | string;
    stock: number;
    color?: string;
    size?: string;
    image_url?: string;
    [key: string]: any;
}

interface Product {
    id: number;
    name: string;
    price: number | string;
    description?: string;
    image_url?: string;
    categories?: Category[];
    variants?: Variant[];
    [key: string]: any;
}

interface Props {
    products?: Product[] | { data: Product[]; [key: string]: any };
    categories?: Category[];
    features?: any[];
    variant?: Variant[];
}

const props = withDefaults(defineProps<Props>(), {
    products: () => ({ data: [] }),
    categories: () => [],
    features: () => [],
    variant: () => [],
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isVariantModalOpen = ref(false);

const selectedProduct = ref<Product | null>(null);
const selectedVariant = ref<Variant | null>(null);
const imageInputType = ref('file');
const expandedProducts = ref<Set<number>>(new Set());

const productList = computed<Product[]>(() => {
    if (!props.products) {
        return [];
    }

    return Array.isArray(props.products)
        ? props.products
        : props.products.data || [];
});

const flattenCategories = (
    nodes: Category[] = [],
    prefix = '',
): { id: number; name: string }[] => {
    let list: { id: number; name: string }[] = [];
    nodes.forEach((node) => {
        list.push({ id: node.id, name: prefix + node.name });

        if (node.children && node.children.length) {
            list = list.concat(
                flattenCategories(node.children, prefix + '-- '),
            );
        }
    });

    return list;
};

const toggleExpand = (productId: number) => {
    if (expandedProducts.value.has(productId)) {
        expandedProducts.value.delete(productId);
    } else {
        expandedProducts.value.add(productId);
    }
};

const getProductVariants = (productId: number): Variant[] => {
    const product = productList.value.find((p) => p.id === productId);

    if (product && product.variants) {
        return product.variants;
    }

    if (props.variant && props.variant.length) {
        return props.variant.filter((v) => v.product_id === productId);
    }

    return [];
};

const createForm = useForm({
    name: '',
    price: '',
    description: '',
    image_file: null as File | null,
    image_url: '',
    category_ids: [] as number[],
});

const editForm = useForm({
    name: '',
    price: '',
    description: '',
    image_file: null as File | null,
    image_url: '',
    category_ids: [] as number[],
    _method: 'PUT',
});

const variantForm = useForm({
    product_id: null as number | null,
    sku: '',
    price: '',
    stock: 0,
    color: '',
    size: '',
    image_url: '',
});

const handleFileChange = (e: Event, formInstance: any) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        formInstance.image_file = target.files[0];
    }
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/products', {
        forceFormData: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (product: Product) => {
    selectedProduct.value = product;
    editForm.clearErrors();
    editForm.name = product.name;
    editForm.price = String(product.price ?? '');
    editForm.description = product.description || '';
    editForm.image_url = product.image_url || '';
    editForm.image_file = null;
    editForm.category_ids = product.categories
        ? product.categories.map((c) => c.id)
        : [];
    isEditModalOpen.value = true;
};

const submitUpdate = () => {
    if (!selectedProduct.value) {
        return;
    }

    editForm.post(`/products/${selectedProduct.value.id}`, {
        forceFormData: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            selectedProduct.value = null;
        },
    });
};

const confirmDelete = (product: Product) => {
    if (
        confirm(
            `"${product.name}" isimli ürünü silmek istediğinize emin misiniz?`,
        )
    ) {
        router.delete(`/products/${product.id}`);
    }
};

const openCreateVariantModal = (product: Product) => {
    selectedVariant.value = null;
    variantForm.reset();
    variantForm.clearErrors();
    variantForm.product_id = product.id;
    variantForm.price = String(product.price ?? '');
    isVariantModalOpen.value = true;
};

const openEditVariantModal = (variantItem: Variant) => {
    selectedVariant.value = variantItem;
    variantForm.clearErrors();
    variantForm.product_id = variantItem.product_id;
    variantForm.sku = variantItem.sku;
    variantForm.price = String(variantItem.price ?? '');
    variantForm.stock = variantItem.stock;
    variantForm.color = variantItem.color || '';
    variantForm.size = variantItem.size || '';
    variantForm.image_url = variantItem.image_url || '';
    isVariantModalOpen.value = true;
};

const submitVariant = () => {
    if (selectedVariant.value) {
        variantForm.put(`/products/variants/${selectedVariant.value.id}`, {
            onSuccess: () => {
                isVariantModalOpen.value = false;
                selectedVariant.value = null;
            },
        });
    } else {
        variantForm.post('/products/variants', {
            onSuccess: () => {
                isVariantModalOpen.value = false;
                variantForm.reset();
            },
        });
    }
};

const deleteVariant = (variantItem: Variant) => {
    if (
        confirm(
            `"${variantItem.sku}" SKU'lu varyantı silmek istediğinize emin misiniz?`,
        )
    ) {
        router.delete(`/products/variants/${variantItem.id}`);
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 p-6 text-slate-800">
        <div class="mx-auto max-w-6xl space-y-6">
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Ürün Yönetimi
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Ürünleri, kategorileri ve varyantları yönetin.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700"
                >
                    Yeni Ürün Ekle
                </button>
            </div>

            <div
                v-if="($page.props as any).flash?.status"
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
            >
                {{ ($page.props as any).flash.status }}
            </div>
            <div
                v-if="($page.props as any).flash?.error"
                class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"
            >
                {{ ($page.props as any).flash.error }}
            </div>

            <div
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold tracking-wider text-slate-500 uppercase"
                            >
                                <th class="px-4 py-3">Görsel</th>
                                <th class="px-4 py-3">Ürün Adı</th>
                                <th class="px-4 py-3">Fiyat</th>
                                <th class="px-4 py-3">Kategoriler</th>
                                <th class="px-4 py-3 text-right">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <template
                                v-for="product in productList"
                                :key="product?.id || Math.random()"
                            >
                                <tr
                                    v-if="product"
                                    class="transition-colors hover:bg-slate-50/50"
                                >
                                    <td class="px-4 py-3">
                                        <img
                                            v-if="product.image_url"
                                            :src="product.image_url"
                                            :alt="product.name"
                                            class="h-10 w-10 rounded-md border border-slate-200 object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-100 text-xs text-slate-400"
                                        >
                                            Yok
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 font-medium text-slate-900"
                                    >
                                        {{ product.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-mono font-medium text-slate-700"
                                    >
                                        ₺{{ Number(product.price).toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="cat in product.categories"
                                                :key="cat.id"
                                                class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                                            >
                                                {{ cat.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <button
                                                @click="
                                                    toggleExpand(product.id)
                                                "
                                                class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800"
                                            >
                                                Varyantlar ({{
                                                    getProductVariants(
                                                        product.id,
                                                    ).length
                                                }})
                                            </button>
                                            <button
                                                @click="
                                                    openCreateVariantModal(
                                                        product,
                                                    )
                                                "
                                                class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50 hover:text-emerald-800"
                                            >
                                                + Varyant
                                            </button>
                                            <button
                                                @click="openEditModal(product)"
                                                class="rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                                            >
                                                Düzenle
                                            </button>
                                            <button
                                                @click="confirmDelete(product)"
                                                class="rounded px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-800"
                                            >
                                                Sil
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        product &&
                                        expandedProducts.has(product.id)
                                    "
                                    :key="`variants-${product.id}`"
                                    class="bg-slate-50/70"
                                >
                                    <td
                                        colspan="5"
                                        class="border-t border-b border-slate-200 p-4"
                                    >
                                        <div
                                            class="rounded-lg border border-slate-200 bg-white p-3"
                                        >
                                            <div
                                                class="mb-2 flex items-center justify-between"
                                            >
                                                <span
                                                    class="text-xs font-bold tracking-wider text-slate-500 uppercase"
                                                    >Ürün Varyantları</span
                                                >
                                            </div>
                                            <div
                                                v-if="
                                                    getProductVariants(
                                                        product.id,
                                                    ).length === 0
                                                "
                                                class="py-2 text-xs text-slate-400"
                                            >
                                                Henüz hiç varyant eklenmemiş.
                                            </div>
                                            <table
                                                v-else
                                                class="w-full text-left text-xs"
                                            >
                                                <thead>
                                                    <tr
                                                        class="border-b font-semibold text-slate-400 uppercase"
                                                    >
                                                        <th class="px-2 py-1">
                                                            SKU
                                                        </th>
                                                        <th class="px-2 py-1">
                                                            Renk
                                                        </th>
                                                        <th class="px-2 py-1">
                                                            Beden
                                                        </th>
                                                        <th class="px-2 py-1">
                                                            Stok
                                                        </th>
                                                        <th class="px-2 py-1">
                                                            Fiyat
                                                        </th>
                                                        <th
                                                            class="px-2 py-1 text-right"
                                                        >
                                                            Eylem
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y">
                                                    <tr
                                                        v-for="v in getProductVariants(
                                                            product.id,
                                                        )"
                                                        :key="v.id"
                                                    >
                                                        <td
                                                            class="px-2 py-1.5 font-mono font-medium"
                                                        >
                                                            {{ v.sku }}
                                                        </td>
                                                        <td class="px-2 py-1.5">
                                                            {{ v.color || '-' }}
                                                        </td>
                                                        <td class="px-2 py-1.5">
                                                            {{ v.size || '-' }}
                                                        </td>
                                                        <td class="px-2 py-1.5">
                                                            {{ v.stock }}
                                                        </td>
                                                        <td class="px-2 py-1.5">
                                                            ₺{{
                                                                Number(
                                                                    v.price,
                                                                ).toFixed(2)
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-2 py-1.5 text-right"
                                                        >
                                                            <button
                                                                @click="
                                                                    openEditVariantModal(
                                                                        v,
                                                                    )
                                                                "
                                                                class="mr-2 text-indigo-600 hover:underline"
                                                            >
                                                                Düzenle
                                                            </button>
                                                            <button
                                                                @click="
                                                                    deleteVariant(
                                                                        v,
                                                                    )
                                                                "
                                                                class="text-rose-600 hover:underline"
                                                            >
                                                                Sil
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-xl space-y-4 rounded-xl border bg-white p-6 shadow-xl"
            >
                <h2 class="text-lg font-bold text-slate-900">Yeni Ürün Ekle</h2>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold uppercase"
                            >Ürün Adı</label
                        >
                        <input
                            v-model="createForm.name"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm"
                            required
                        />
                        <span
                            v-if="createForm.errors.name"
                            class="text-xs text-rose-500"
                            >{{ createForm.errors.name }}</span
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Fiyat (₺)</label
                            >
                            <input
                                v-model="createForm.price"
                                type="number"
                                step="0.01"
                                class="w-full rounded-lg border-slate-300 text-sm"
                                required
                            />
                            <span
                                v-if="createForm.errors.price"
                                class="text-xs text-rose-500"
                                >{{ createForm.errors.price }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Görsel Yükleme Tipi</label
                            >
                            <div class="mb-2 flex gap-2">
                                <button
                                    type="button"
                                    @click="imageInputType = 'file'"
                                    :class="
                                        imageInputType === 'file'
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-slate-100'
                                    "
                                    class="rounded px-2 py-1 text-xs"
                                >
                                    Dosya
                                </button>
                                <button
                                    type="button"
                                    @click="imageInputType = 'url'"
                                    :class="
                                        imageInputType === 'url'
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-slate-100'
                                    "
                                    class="rounded px-2 py-1 text-xs"
                                >
                                    URL Bağlantısı
                                </button>
                            </div>

                            <input
                                v-if="imageInputType === 'file'"
                                type="file"
                                @change="
                                    (e) => handleFileChange(e, createForm)
                                "
                                accept="image/*"
                                class="w-full text-xs"
                            />
                            <input
                                v-else
                                v-model="createForm.image_url"
                                type="url"
                                placeholder="https://..."
                                class="w-full rounded-lg border-slate-300 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold uppercase"
                            >Kategoriler</label
                        >
                        <select
                            v-model="createForm.category_ids"
                            multiple
                            class="h-28 w-full rounded-lg border-slate-300 text-sm"
                        >
                            <option
                                v-for="cat in flattenCategories(
                                    props.categories,
                                )"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 border-t pt-4">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white"
                        >
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-xl space-y-4 rounded-xl border bg-white p-6 shadow-xl"
            >
                <h2 class="text-lg font-bold text-slate-900">Ürünü Düzenle</h2>

                <form @submit.prevent="submitUpdate" class="space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold uppercase"
                            >Ürün Adı</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm"
                        />
                        <span
                            v-if="editForm.errors.name"
                            class="text-xs text-rose-500"
                            >{{ editForm.errors.name }}</span
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Fiyat (₺)</label
                            >
                            <input
                                v-model="editForm.price"
                                type="number"
                                step="0.01"
                                class="w-full rounded-lg border-slate-300 text-sm"
                            />
                            <span
                                v-if="editForm.errors.price"
                                class="text-xs text-rose-500"
                                >{{ editForm.errors.price }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Yeni Görsel Yükle / URL</label
                            >
                            <input
                                type="file"
                                @change="(e) => handleFileChange(e, editForm)"
                                accept="image/*"
                                class="mb-1 w-full text-xs"
                            />
                            <input
                                v-model="editForm.image_url"
                                type="url"
                                placeholder="veya resim URL girin"
                                class="w-full rounded-lg border-slate-300 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold uppercase"
                            >Kategoriler</label
                        >
                        <select
                            v-model="editForm.category_ids"
                            multiple
                            class="h-28 w-full rounded-lg border-slate-300 text-sm"
                        >
                            <option
                                v-for="cat in flattenCategories(
                                    props.categories,
                                )"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 border-t pt-4">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white"
                        >
                            Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Variant Modal -->
        <div
            v-if="isVariantModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg space-y-4 rounded-xl border bg-white p-6 shadow-xl"
            >
                <h2 class="text-lg font-bold text-slate-900">
                    {{
                        selectedVariant
                            ? 'Varyantı Düzenle'
                            : 'Yeni Varyant Ekle'
                    }}
                </h2>

                <form @submit.prevent="submitVariant" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >SKU</label
                            >
                            <input
                                v-model="variantForm.sku"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-sm"
                                required
                            />
                            <span
                                v-if="variantForm.errors.sku"
                                class="text-xs text-rose-500"
                                >{{ variantForm.errors.sku }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Fiyat (₺)</label
                            >
                            <input
                                v-model="variantForm.price"
                                type="number"
                                step="0.01"
                                class="w-full rounded-lg border-slate-300 text-sm"
                                required
                            />
                            <span
                                v-if="variantForm.errors.price"
                                class="text-xs text-rose-500"
                                >{{ variantForm.errors.price }}</span
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Stok</label
                            >
                            <input
                                v-model="variantForm.stock"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border-slate-300 text-sm"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Renk</label
                            >
                            <input
                                v-model="variantForm.color"
                                type="text"
                                placeholder="Örn: Kırmızı"
                                class="w-full rounded-lg border-slate-300 text-sm"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold uppercase"
                                >Beden</label
                            >
                            <input
                                v-model="variantForm.size"
                                type="text"
                                placeholder="Örn: XL"
                                class="w-full rounded-lg border-slate-300 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold uppercase"
                            >Görsel URL (İsteğe Bağlı)</label
                        >
                        <input
                            v-model="variantForm.image_url"
                            type="url"
                            class="w-full rounded-lg border-slate-300 text-sm"
                        />
                    </div>

                    <div class="flex justify-end gap-3 border-t pt-4">
                        <button
                            type="button"
                            @click="isVariantModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="variantForm.processing"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white"
                        >
                            {{ selectedVariant ? 'Güncelle' : 'Kaydet' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>