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
        <div class="flex items-center justify-between p-2 rounded bg-gray-50 hover:bg-gray-100 border border-gray-200 group">
            <span class="font-medium text-gray-700">
                {{ category.name }}
            </span>

            <div class="flex items-center space-x-2">
                <button
                    type="button"
                    @click.prevent="onSelectParent(category)"
                    class="text-xs px-2 py-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition"
                >
                    + Alt Ekle
                </button>

                <button
                    type="button"
                    @click.prevent="onEditCategory(category)"
                    class="text-xs px-2 py-1 bg-amber-50 text-amber-600 rounded hover:bg-amber-100 transition"
                >
                    Düzenle
                </button>

                <button
                    type="button"
                    @click.prevent="onDeleteCategory(category)"
                    class="text-xs px-2 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100 transition"
                >
                    Sil
                </button>
            </div>
        </div>

        <ul v-if="category.children && category.children.length > 0" class="pl-6 border-l-2 border-indigo-200 mt-1 space-y-1">
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