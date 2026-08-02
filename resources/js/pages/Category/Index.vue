<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import CategoryItem from './CategoryItem.vue'

// const props = defineProps({
//   categories: {
//     type: Array,
//     required: true
//   }
// })

defineProps<Props>();

const selectedParentName = ref('Ana Kategori (Kök Düğüm)')

const form = useForm({
  name: '',
  parent_id: null
})

const setParent = (category) => {
  if (category) {
    form.parent_id = category.id
    selectedParentName.value = category.name
  } else {
    form.parent_id = null
    selectedParentName.value = 'Ana Kategori (Kök Düğüm)'
  }
}

const submitForm = () => {
  form.post(route('categories.store'), {
    onSuccess: () => {
      form.reset('name')
    }
  })
}
</script>

<template>
  <div class="max-w-6xl mx-auto p-6 space-y-8">
    <h1 class="text-2xl font-bold text-gray-800">Kategori Ağacı Yönetimi</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-fit">
        <h2 class="text-lg font-semibold mb-4 text-gray-700">Yeni Kategori Ekle</h2>
        
        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-sm text-indigo-900 flex justify-between items-center">
            <div>
              <span class="block text-xs text-indigo-500 font-semibold">Ekleneceği Yer:</span>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Adı</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="Örn: Mont, Tudors, Kışlık..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
              required
            />
            <span v-if="form.errors.name" class="text-xs text-red-500 mt-1 block">{{ form.errors.name }}</span>
          </div>

          <button 
            type="submit" 
            :disabled="form.processing"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 rounded-lg transition disabled:opacity-50"
          >
            {{ form.processing ? 'Ekleniyor...' : 'Kategoriyi Kaydet' }}
          </button>
        </form>
      </div>

      <div class="md:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-700">Mevcut Kategori Ağacı</h2>
          <button 
            @click="setParent(null)"
            type="button"
            class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-200"
          >
            Ana Kategori Seç
          </button>
        </div>

        <div v-if="categories.length === 0" class="text-gray-400 text-sm italic">
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