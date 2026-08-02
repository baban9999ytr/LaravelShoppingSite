<script setup lang="ts">
defineOptions({
    name: 'CategoryItem',
});

defineProps<{
    category: any;
}>();

const emit = defineEmits<{
    (e: 'select-parent', category: any): void;
    (e: 'edit-category', category: any): void;
    (e: 'delete-category', category: any): void;
}>();

const onSelectParent = (cat: any) => emit('select-parent', cat);
const onEditCategory = (cat: any) => emit('edit-category', cat);
const onDeleteCategory = (cat: any) => emit('delete-category', cat);
</script>

<template>
    <li class="my-1">
        <div
            class="group flex items-center justify-between rounded border border-gray-200 bg-gray-50 p-2 hover:bg-gray-100"
        >
            <span class="font-medium text-gray-700">
                {{ category.name }}
            </span>

            <div class="flex items-center space-x-2">
                <button
                    type="button"
                    @click.prevent="onSelectParent(category)"
                    class="rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-600 transition hover:bg-indigo-100"
                >
                    + Alt Ekle
                </button>

                <button
                    type="button"
                    @click.prevent="onEditCategory(category)"
                    class="rounded bg-amber-50 px-2 py-1 text-xs text-amber-600 transition hover:bg-amber-100"
                >
                    Düzenle
                </button>

                <button
                    type="button"
                    @click.prevent="onDeleteCategory(category)"
                    class="rounded bg-red-50 px-2 py-1 text-xs text-red-600 transition hover:bg-red-100"
                >
                    Sil
                </button>
            </div>
        </div>

        <ul
            v-if="category.children && category.children.length > 0"
            class="mt-1 space-y-1 border-l-2 border-indigo-200 pl-6"
        >
            <CategoryItem
                v-for="child in category.children"
                :key="child.id"
                :category="child"
                @select-parent="$emit('select-parent', $event)"
                @edit-category="$emit('edit-category', $event)"
                @delete-category="$emit('delete-category', $event)"
            />
        </ul>
    </li>
</template>
