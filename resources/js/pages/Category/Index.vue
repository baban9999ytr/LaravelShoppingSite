<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';
import CategoryItem from './CategoryItem.vue';
interface Category {
    id: number;
    name: string;
    parent_id?: number | null;
    children?: Category[];
}

interface Props {
    categories: Category[];
}

defineProps<Props>();

const selectedParentName = ref('Ana Kategori (Kök Düğüm)');

const form = useForm({
    name: '',
    parent_id: null as number | null,
});

function setParent(category?: Category | null) {
    if (category) {
        form.parent_id = category.id;
        selectedParentName.value = category.name;
    } else {
        form.parent_id = null;
        selectedParentName.value = 'Ana Kategori (Kök Düğüm)';
    }
}

function submitForm() {
    form.post(route('categories.store'), {
        onSuccess: () => {
            form.reset('name');
        },
    });
}
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-8 p-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Kategori Ağacı Yönetimi
        </h1>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div
                class="h-fit rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-4 text-lg font-semibold text-gray-700">
                    Yeni Kategori Ekle
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div
                        class="flex items-center justify-between rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-900"
                    >
                        <div>
                            <span
                                class="block text-xs font-semibold text-indigo-500"
                                >Ekleneceği Yer:</span
                            >
                            <span>{{ selectedParentName }}</span>
                        </div>
                        <button
                            v-if="form.parent_id"
                            @click="setParent(null)"
                            type="button"
                            class="text-xs text-red-500 hover:underline"
                        >
                            Temizle
                        </button>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Kategori Adı</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Örn: Mont, Tudors, Kışlık..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            required
                        />
                        <span
                            v-if="form.errors.name"
                            class="mt-1 block text-xs text-red-500"
                            >{{ form.errors.name }}</span
                        >
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-indigo-600 py-2 font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Ekleniyor...'
                                : 'Kategoriyi Kaydet'
                        }}
                    </button>
                </form>
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:col-span-2"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-700">
                        Mevcut Kategori Ağacı
                    </h2>
                    <button
                        @click="setParent(null)"
                        type="button"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-200"
                    >
                        Ana Kategori Seç
                    </button>
                </div>

                <div
                    v-if="categories.length === 0"
                    class="text-sm text-gray-400 italic"
                >
                    Henüz kategori eklenmemiş.
                </div>

                <ul v-else class="space-y-2">
                    <CategoryItem
                        v-for="category in categories"
                        :key="category.id"
                        :category="category"
                        @select-parent="setParent"
                    />
                </ul>
            </div>
        </div>
    </div>
</template>
