<script setup lang="ts">
defineProps({
    category: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['select-parent']);
</script>

<template>
    <li class="my-1">
        <div
            class="flex items-center justify-between rounded border border-gray-200 bg-gray-50 p-2 hover:bg-gray-100"
        >
            <span class="font-medium text-gray-700">
                {{ category.name }}
            </span>

            <button
                @click="emit('select-parent', category)"
                type="button"
                class="rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-600 hover:bg-indigo-100"
            >
                + Alt Kategori Ekle
            </button>
        </div>

        <ul
            v-if="category.children && category.children.length > 0"
            class="mt-1 space-y-1 border-l-2 border-indigo-200 pl-6"
        >
            <CategoryItem
                v-for="child in category.children"
                :key="child.id"
                :category="child"
                @select-parent="(cat) => emit('select-parent', cat)"
            />
        </ul>
    </li>
</template>
