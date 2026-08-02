<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
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
    if (confirm(`"${category.name}" kategorisini silmek istediğinize emin misiniz?`)) {
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
            if (item.id === id) return item;
            if (item.children?.length) {
                const found = findCategory(item.children, id);
                if (found) return found;
            }
        }
        return null;
    };

    const targetCategory = findCategory(props.categories, draggedId);
    if (!targetCategory) return;

    useForm({
        name: targetCategory.name,
        parent_id: newParentId,
    }).put(`/categories/${draggedId}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kategoriler" />

    <AppLayout>
        <div class="min-h-full bg-slate-100 text-slate-900 p-6 space-y-8 dark:bg-slate-100 dark:text-slate-900">
            <div class="max-w-6xl mx-auto space-y-8">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-900">
                    Kategori Ağacı Yönetimi
                </h1>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Form Section -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 h-fit text-slate-900 dark:bg-white dark:text-slate-900 dark:border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-800">
                                {{ isEditing ? 'Kategoriyi Düzenle' : 'Yeni Kategori Ekle' }}
                            </h2>
                            <button
                                v-if="isEditing"
                                @click="cancelEdit"
                                type="button"
                                class="text-xs text-slate-500 hover:text-slate-700 underline dark:text-slate-500"
                            >
                                İptal Et
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-700">
                                    Üst Kategoriler
                                    <span v-if="!isEditing" class="text-xs font-normal text-slate-500 block">
                                        (Birden fazla seçmek için <kbd class="px-1 bg-slate-200 rounded">Ctrl</kbd> veya <kbd class="px-1 bg-slate-200 rounded">Cmd</kbd> basın)
                                    </span>
                                </label>
                                
                                <select
                                    v-model="form.parent_ids"
                                    :multiple="!isEditing"
                                    :size="!isEditing ? 6 : 1"
                                    class="w-full px-3 py-2 bg-white text-slate-900 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-sm dark:bg-white dark:text-slate-900 dark:border-slate-300"
                                >
                                    <option :value="null">Ana Kategori (Kök Düğüm)</option>
                                    <option
                                        v-for="cat in flatCategoryOptions"
                                        :key="cat.id"
                                        :value="cat.id"
                                        :disabled="isEditing && cat.id === editingCategoryId"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-700">
                                    Kategori Adı / İsimleri
                                    <span v-if="!isEditing" class="text-xs font-normal text-slate-500 block">
                                        (Birden fazla eklemek için virgül veya yeni satır ile ayırın)
                                    </span>
                                </label>
                                <textarea
                                    v-if="!isEditing"
                                    v-model="form.name"
                                    rows="3"
                                    placeholder="Örn: Üst Giyim, Alt Giyim, Dış Giyim"
                                    class="w-full px-3 py-2 bg-white text-slate-900 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-sm placeholder:text-slate-400 dark:bg-white dark:text-slate-900 dark:border-slate-300"
                                    required
                                ></textarea>
                                <input
                                    v-else
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Örn: Üst Giyim"
                                    class="w-full px-3 py-2 bg-white text-slate-900 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-sm placeholder:text-slate-400 dark:bg-white dark:text-slate-900 dark:border-slate-300"
                                    required
                                />
                                <span v-if="form.errors.name" class="text-xs text-red-600 mt-1 block">
                                    {{ form.errors.name }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 rounded-lg transition disabled:opacity-50 text-sm"
                                >
                                    {{ form.processing ? 'Kaydediliyor...' : isEditing ? 'Güncelle' : 'Kategoriyi Kaydet' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tree View Section -->
                    <div class="md:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200 text-slate-900 dark:bg-white dark:text-slate-900 dark:border-slate-200">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-800">Mevcut Kategori Ağacı</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-500">Üst kategoriyi değiştirmek için kartları sürükleyip bırakabilirsiniz.</p>
                            </div>
                            <button
                                @click="setParent(null)"
                                type="button"
                                class="text-xs bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg hover:bg-slate-200 transition dark:bg-slate-100 dark:text-slate-700"
                            >
                                Ana Kategori Seç
                            </button>
                        </div>

                        <!-- Root Drop Zone -->
                        <div
                            @dragover.prevent
                            @drop="
                                const draggedId = Number($event.dataTransfer?.getData('text/plain'));
                                if (draggedId) handleCategoryDrop(draggedId, null);
                            "
                            class="mb-4 p-3 border-2 border-dashed border-slate-300 rounded-lg text-center text-xs font-medium text-slate-500 hover:border-indigo-400 hover:bg-indigo-50/50 transition cursor-pointer dark:text-slate-500 dark:border-slate-300"
                        >
                            Ana Kategori Yapmak İçin Buraya Sürükleyin (Root Level)
                        </div>

                        <div v-if="categories.length === 0" class="text-slate-400 text-sm italic">
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