<script setup lang="ts">
defineProps({
  category: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['select-parent'])
</script>

<template>
  <li class="my-1">
    <div class="flex items-center justify-between p-2 rounded bg-gray-50 hover:bg-gray-100 border border-gray-200">
      <span class="font-medium text-gray-700">
         {{ category.name }}
      </span>
      
      <button 
        @click="emit('select-parent', category)"
        type="button"
        class="text-xs px-2 py-1 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100"
      >
        + Alt Kategori Ekle
      </button>
    </div>

    <ul v-if="category.children && category.children.length > 0" class="pl-6 border-l-2 border-indigo-200 mt-1 space-y-1">
      <CategoryItem 
        v-for="child in category.children" 
        :key="child.id" 
        :category="child"
        @select-parent="(cat) => emit('select-parent', cat)"
      />
    </ul>
  </li>
</template>