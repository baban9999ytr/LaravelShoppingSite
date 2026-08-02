<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CategoryItem from './CategoryItem.vue';

const props = defineProps<{
    categories: any[];
}>();

const isEditing = ref(false);
const editingCategoryId = ref<number | null>(null);

const form = useForm({
    name: '',
    parent_ids: [] as (number | null)[],
});

const setParent = (category: any) => {
    if (category) {
        form.parent_ids = [category.id];
    } else {
        form.parent_ids = [];
    }
};

const flattenCategories = (nodes: any[], depth = 0): any[] => {
    let result: any[] = [];

    for (const node of nodes) {
        result.push({
            id: node.id,
            name: `${'— '.repeat(depth)}${node.name}`,
        });

        if (node.children && node.children.length > 0) {
            result = result.concat(flattenCategories(node.children, depth + 1));
        }
    }

    return result;
};

const flatCategoryOptions = computed(() => flattenCategories(props.categories));

const startEdit = (category: any) => {
    isEditing.value = true;
    editingCategoryId.value = category.id;
    form.name = category.name;
    form.parent_ids = category.parent_id ? [category.parent_id] : [];
};

const cancelEdit = () => {
    isEditing.value = false;
    editingCategoryId.value = null;
    form.reset();
    form.parent_ids = [];
};

const submitForm = (e: Event) => {
    e.preventDefault();

    if (isEditing.value && editingCategoryId.value) {
        const updateData = {
            name: form.name,
            parent_id: form.parent_ids.length > 0 ? form.parent_ids[0] : null,
        };

        useForm(updateData).put(`/categories/${editingCategoryId.value}`, {
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
            onError: (errors) => console.error('Form update error:', errors),
        });
    } else {
        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.parent_ids = [];
            },
            onError: (errors) => console.error('Form submit error:', errors),
        });
    }
};

const deleteCategory = (category: any) => {
    if (
        confirm(
            `"${category.name}" kategorisini silmek istediğinize emin misiniz?`,
        )
    ) {
        useForm({}).delete(`/categories/${category.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (editingCategoryId.value === category.id) {
                    cancelEdit();
                }
            },
        });
    }
};

const handleCategoryDrop = (draggedId: number, newParentId: number | null) => {
    const findCategory = (list: any[], id: number): any => {
        for (const item of list) {
            if (item.id === id) {
                return item;
            }

            if (item.children?.length) {
                const found = findCategory(item.children, id);

                if (found) {
                    return found;
                }
            }
        }

        return null;
    };

    const targetCategory = findCategory(props.categories, draggedId);

    if (!targetCategory) {
        return;
    }

    useForm({
        name: targetCategory.name,
        parent_id: newParentId,
    }).put(`/categories/${draggedId}`, {
        preserveScroll: true,
    });
};

const handleRootDrop = (event: DragEvent) => {
    const draggedIdRaw = event.dataTransfer?.getData('text/plain');
    if (draggedIdRaw) {
        const draggedId = Number(draggedIdRaw);
        if (draggedId) {
            handleCategoryDrop(draggedId, null);
        }
    }
};
</script>

<template>
    <Head title="Kategoriler" />

    <AppLayout>
        <div
            class="min-h-full space-y-8 bg-slate-100 p-6 text-slate-900 dark:bg-slate-100 dark:text-slate-900"
        >
            <div class="mx-auto max-w-6xl space-y-8">
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-slate-900"
                >
                    Kategori Ağacı Yönetimi
                </h1>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        class="h-fit rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-sm dark:border-slate-200 dark:bg-white dark:text-slate-900"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h2
                                class="text-lg font-semibold text-slate-800 dark:text-slate-800"
                            >
                                {{
                                    isEditing
                                        ? 'Kategoriyi Düzenle'
                                        : 'Yeni Kategori Ekle'
                                }}
                            </h2>
                            <button
                                v-if="isEditing"
                                @click="cancelEdit"
                                type="button"
                                class="text-xs text-slate-500 underline hover:text-slate-700 dark:text-slate-500"
                            >
                                İptal Et
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-700"
                                >
                                    Üst Kategoriler
                                    <span
                                        v-if="!isEditing"
                                        class="block text-xs font-normal text-slate-500"
                                    >
                                        (Birden fazla seçmek için
                                        <kbd class="rounded bg-slate-200 px-1"
                                            >Ctrl</kbd
                                        >
                                        veya
                                        <kbd class="rounded bg-slate-200 px-1"
                                            >Cmd</kbd
                                        >
                                        basın)
                                    </span>
                                </label>

                                <select
                                    v-model="form.parent_ids"
                                    :multiple="!isEditing"
                                    :size="!isEditing ? 6 : 1"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-300 dark:bg-white dark:text-slate-900"
                                >
                                    <option :value="null">
                                        Ana Kategori (Kök Düğüm)
                                    </option>
                                    <option
                                        v-for="cat in flatCategoryOptions"
                                        :key="cat.id"
                                        :value="cat.id"
                                        :disabled="
                                            isEditing &&
                                            cat.id === editingCategoryId
                                        "
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-700"
                                >
                                    Kategori Adı / İsimleri
                                    <span
                                        v-if="!isEditing"
                                        class="block text-xs font-normal text-slate-500"
                                    >
                                        (Birden fazla eklemek için virgül veya
                                        yeni satır ile ayırın)
                                    </span>
                                </label>
                                <textarea
                                    v-if="!isEditing"
                                    v-model="form.name"
                                    rows="3"
                                    placeholder="Örn: Üst Giyim, Alt Giyim, Dış Giyim"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-300 dark:bg-white dark:text-slate-900"
                                    required
                                ></textarea>
                                <input
                                    v-else
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Örn: Üst Giyim"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-300 dark:bg-white dark:text-slate-900"
                                    required
                                />
                                <span
                                    v-if="form.errors.name"
                                    class="mt-1 block text-xs text-red-600"
                                >
                                    {{ form.errors.name }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {{
                                        form.processing
                                            ? 'Kaydediliyor...'
                                            : isEditing
                                              ? 'Güncelle'
                                              : 'Kategoriyi Kaydet'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-sm md:col-span-2 dark:border-slate-200 dark:bg-white dark:text-slate-900"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2
                                    class="text-lg font-semibold text-slate-800 dark:text-slate-800"
                                >
                                    Mevcut Kategori Ağacı
                                </h2>
                                <p
                                    class="text-xs text-slate-500 dark:text-slate-500"
                                >
                                    Üst kategoriyi değiştirmek için kartları
                                    sürükleyip bırakabilirsiniz.
                                </p>
                            </div>
                            <button
                                @click="setParent(null)"
                                type="button"
                                class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs text-slate-700 transition hover:bg-slate-200 dark:bg-slate-100 dark:text-slate-700"
                            >
                                Ana Kategori Seç
                            </button>
                        </div>

                        <div
                            @dragover.prevent
                            @drop="handleRootDrop"
                            class="mb-4 cursor-pointer rounded-lg border-2 border-dashed border-slate-300 p-3 text-center text-xs font-medium text-slate-500 transition hover:border-indigo-400 hover:bg-indigo-50/50 dark:border-slate-300 dark:text-slate-500"
                        >
                            Ana Kategori Yapmak İçin Buraya Sürükleyin (Root
                            Level)
                        </div>

                        <div
                            v-if="categories.length === 0"
                            class="text-sm text-slate-400 italic"
                        >
                            Henüz kategori eklenmemiş.
                        </div>

                        <ul v-else class="space-y-2">
                            <CategoryItem
                                v-for="category in categories"
                                :key="category.id"
                                :category="category"
                                @select-parent="setParent"
                                @edit-category="startEdit"
                                @delete-category="deleteCategory"
                                @drop-category="handleCategoryDrop"
                            />
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>