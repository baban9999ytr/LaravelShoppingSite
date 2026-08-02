<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue' 

const props = defineProps({
  products: {
    type: [Array, Object],
    default: () => ({ data: [] }),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  features: {
    type: Array,
    default: () => [],
  },
  variant: {
    type: Array,
    default: () => [],
  },
})

const isCreateModalOpen = ref(false)
const isEditModalOpen = ref(false)
const isVariantModalOpen = ref(false)

const selectedProduct = ref(null)
const selectedVariant = ref(null)
const imageInputType = ref('file')
const expandedProducts = ref(new Set())

const productList = computed(() => {
  if (!props.products) {
return []
}

  return Array.isArray(props.products)
    ? props.products
    : (props.products.data || [])
})

const flattenCategories = (nodes = [], prefix = '') => {
  let list = []
  nodes.forEach(node => {
    list.push({ id: node.id, name: prefix + node.name })

    if (node.children && node.children.length) {
      list = list.concat(flattenCategories(node.children, prefix + '-- '))
    }
  })

  return list
}

const toggleExpand = (productId) => {
  if (expandedProducts.value.has(productId)) {
    expandedProducts.value.delete(productId)
  } else {
    expandedProducts.value.add(productId)
  }
}

const getProductVariants = (productId) => {
  const product = productList.value.find(p => p.id === productId)

  if (product && product.variants) {
    return product.variants
  }
  
  if (props.variant && props.variant.length) {
    return props.variant.filter(v => v.product_id === productId)
  }
  
  return []
}

// Forms
const createForm = useForm({
  name: '',
  price: '',
  description: '',
  image_file: null,
  image_url: '',
  category_ids: []
})

const editForm = useForm({
  name: '',
  price: '',
  description: '',
  image_file: null,
  image_url: '',
  category_ids: [],
  _method: 'PUT'
})

const variantForm = useForm({
  product_id: null,
  sku: '',
  price: '',
  stock: 0,
  color: '',
  size: '',
  image_url: ''
})

const handleFileChange = (e, form) => {
  if (e.target.files && e.target.files[0]) {
    form.image_file = e.target.files[0]
  }
}

const openCreateModal = () => {
  createForm.reset()
  createForm.clearErrors()
  isCreateModalOpen.value = true
}

const submitCreate = () => {
  createForm.post('/products', {
    forceFormData: true,
    onSuccess: () => {
      isCreateModalOpen.value = false
      createForm.reset()
    }
  })
}

const openEditModal = (product) => {
  selectedProduct.value = product
  editForm.clearErrors()
  editForm.name = product.name
  editForm.price = product.price
  editForm.description = product.description || ''
  editForm.image_url = product.image_url || ''
  editForm.image_file = null
  editForm.category_ids = product.categories ? product.categories.map(c => c.id) : []
  isEditModalOpen.value = true
}

const submitUpdate = () => {
  if (!selectedProduct.value) {
return
}

  editForm.post(`/products/${selectedProduct.value.id}`, {
    forceFormData: true,
    onSuccess: () => {
      isEditModalOpen.value = false
      selectedProduct.value = null
    }
  })
}

const confirmDelete = (product) => {
  if (confirm(`"${product.name}" isimli ürünü silmek istediğinize emin misiniz?`)) {
    router.delete(`/products/${product.id}`)
  }
}

const openCreateVariantModal = (product) => {
  selectedVariant.value = null
  variantForm.reset()
  variantForm.clearErrors()
  variantForm.product_id = product.id
  variantForm.price = product.price
  isVariantModalOpen.value = true
}

const openEditVariantModal = (variantItem) => {
  selectedVariant.value = variantItem
  variantForm.clearErrors()
  variantForm.product_id = variantItem.product_id
  variantForm.sku = variantItem.sku
  variantForm.price = variantItem.price
  variantForm.stock = variantItem.stock
  variantForm.color = variantItem.color || ''
  variantForm.size = variantItem.size || ''
  variantForm.image_url = variantItem.image_url || ''
  isVariantModalOpen.value = true
}

const submitVariant = () => {
  if (selectedVariant.value) {
    variantForm.put(`/products/variants/${selectedVariant.value.id}`, {
      onSuccess: () => {
        isVariantModalOpen.value = false
        selectedVariant.value = null
      }
    })
  } else {
    variantForm.post('/products/variants', {
      onSuccess: () => {
        isVariantModalOpen.value = false
        variantForm.reset()
      }
    })
  }
}

const deleteVariant = (variantItem) => {
  if (confirm(`"${variantItem.sku}" SKU'lu varyantı silmek istediğinize emin misiniz?`)) {
    router.delete(`/products/variants/${variantItem.id}`)
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-6 text-slate-800">
    <div class="max-w-6xl mx-auto space-y-6">

      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Ürün Yönetimi</h1>
          <p class="text-sm text-slate-500 mt-1">Ürünleri, kategorileri ve varyantları yönetin.</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-lg transition-colors shadow-sm text-sm"
        >
          Yeni Ürün Ekle
        </button>
      </div>

      <div v-if="$page.props.flash?.status" class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm">
        {{ $page.props.flash.status }}
      </div>
      <div v-if="$page.props.flash?.error" class="p-4 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-sm">
        {{ $page.props.flash.error }}
      </div>

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-500 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-200">
                <th class="py-3 px-4">Görsel</th>
                <th class="py-3 px-4">Ürün Adı</th>
                <th class="py-3 px-4">Fiyat</th>
                <th class="py-3 px-4">Kategoriler</th>
                <th class="py-3 px-4 text-right">İşlemler</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <template v-for="product in productList" :key="product?.id || Math.random()">
                <tr v-if="product" class="hover:bg-slate-50/50 transition-colors">
                  <td class="py-3 px-4">
                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="w-10 h-10 object-cover rounded-md border border-slate-200" />
                    <div v-else class="w-10 h-10 rounded-md bg-slate-100 flex items-center justify-center text-xs text-slate-400">Yok</div>
                  </td>
                  <td class="py-3 px-4 font-medium text-slate-900">
                    {{ product.name }}
                  </td>
                  <td class="py-3 px-4 font-mono font-medium text-slate-700">
                    ₺{{ Number(product.price).toFixed(2) }}
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex flex-wrap gap-1">
                      <span v-for="cat in product.categories" :key="cat.id" class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs">
                        {{ cat.name }}
                      </span>
                    </div>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <button @click="toggleExpand(product.id)" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium px-2 py-1 rounded hover:bg-indigo-50">
                        Varyantlar ({{ getProductVariants(product.id).length }})
                      </button>
                      <button @click="openCreateVariantModal(product)" class="text-xs text-emerald-600 hover:text-emerald-800 font-medium px-2 py-1 rounded hover:bg-emerald-50">
                        + Varyant
                      </button>
                      <button @click="openEditModal(product)" class="text-xs text-slate-600 hover:text-slate-900 font-medium px-2 py-1 rounded hover:bg-slate-100">
                        Düzenle
                      </button>
                      <button @click="confirmDelete(product)" class="text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded hover:bg-rose-50">
                        Sil
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="product && expandedProducts.has(product.id)" :key="`variants-${product.id}`" class="bg-slate-50/70">
                  <td colspan="5" class="p-4 border-t border-b border-slate-200">
                    <div class="bg-white rounded-lg border border-slate-200 p-3">
                      <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Ürün Varyantları</span>
                      </div>
                      <div v-if="getProductVariants(product.id).length === 0" class="text-xs text-slate-400 py-2">
                        Henüz hiç varyant eklenmemiş.
                      </div>
                      <table v-else class="w-full text-xs text-left">
                        <thead>
                          <tr class="border-b text-slate-400 uppercase font-semibold">
                            <th class="py-1 px-2">SKU</th>
                            <th class="py-1 px-2">Renk</th>
                            <th class="py-1 px-2">Beden</th>
                            <th class="py-1 px-2">Stok</th>
                            <th class="py-1 px-2">Fiyat</th>
                            <th class="py-1 px-2 text-right">Eylem</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y">
                          <tr v-for="v in getProductVariants(product.id)" :key="v.id">
                            <td class="py-1.5 px-2 font-mono font-medium">{{ v.sku }}</td>
                            <td class="py-1.5 px-2">{{ v.color || '-' }}</td>
                            <td class="py-1.5 px-2">{{ v.size || '-' }}</td>
                            <td class="py-1.5 px-2">{{ v.stock }}</td>
                            <td class="py-1.5 px-2">₺{{ Number(v.price).toFixed(2) }}</td>
                            <td class="py-1.5 px-2 text-right">
                              <button @click="openEditVariantModal(v)" class="text-indigo-600 hover:underline mr-2">Düzenle</button>
                              <button @click="deleteVariant(v)" class="text-rose-600 hover:underline">Sil</button>
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

    <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-xl border shadow-xl max-w-xl w-full p-6 space-y-4">
        <h2 class="text-lg font-bold text-slate-900">Yeni Ürün Ekle</h2>

        <form @submit.prevent="submitCreate" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold uppercase mb-1">Ürün Adı</label>
            <input v-model="createForm.name" type="text" class="w-full text-sm rounded-lg border-slate-300" required />
            <span v-if="createForm.errors.name" class="text-xs text-rose-500">{{ createForm.errors.name }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Fiyat (₺)</label>
              <input v-model="createForm.price" type="number" step="0.01" class="w-full text-sm rounded-lg border-slate-300" required />
              <span v-if="createForm.errors.price" class="text-xs text-rose-500">{{ createForm.errors.price }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Görsel Yükleme Tipi</label>
              <div class="flex gap-2 mb-2">
                <button type="button" @click="imageInputType = 'file'" :class="imageInputType === 'file' ? 'bg-indigo-600 text-white' : 'bg-slate-100'" class="px-2 py-1 text-xs rounded">Dosya</button>
                <button type="button" @click="imageInputType = 'url'" :class="imageInputType === 'url' ? 'bg-indigo-600 text-white' : 'bg-slate-100'" class="px-2 py-1 text-xs rounded">URL Bağlantısı</button>
              </div>

              <input v-if="imageInputType === 'file'" type="file" @change="e => handleFileChange(e, createForm)" accept="image/*" class="w-full text-xs" />
              <input v-else v-model="createForm.image_url" type="url" placeholder="https://..." class="w-full text-sm rounded-lg border-slate-300" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase mb-1">Kategoriler</label>
            <select v-model="createForm.category_ids" multiple class="w-full text-sm rounded-lg border-slate-300 h-28">
              <option v-for="cat in flattenCategories(props.categories)" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 text-sm text-slate-600">İptal</button>
            <button type="submit" :disabled="createForm.processing" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg">Kaydet</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-xl border shadow-xl max-w-xl w-full p-6 space-y-4">
        <h2 class="text-lg font-bold text-slate-900">Ürünü Düzenle</h2>

        <form @submit.prevent="submitUpdate" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold uppercase mb-1">Ürün Adı</label>
            <input v-model="editForm.name" type="text" class="w-full text-sm rounded-lg border-slate-300" />
            <span v-if="editForm.errors.name" class="text-xs text-rose-500">{{ editForm.errors.name }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Fiyat (₺)</label>
              <input v-model="editForm.price" type="number" step="0.01" class="w-full text-sm rounded-lg border-slate-300" />
              <span v-if="editForm.errors.price" class="text-xs text-rose-500">{{ editForm.errors.price }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Yeni Görsel Yükle / URL</label>
              <input type="file" @change="e => handleFileChange(e, editForm)" accept="image/*" class="w-full text-xs mb-1" />
              <input v-model="editForm.image_url" type="url" placeholder="veya resim URL girin" class="w-full text-sm rounded-lg border-slate-300" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase mb-1">Kategoriler</label>
            <select v-model="editForm.category_ids" multiple class="w-full text-sm rounded-lg border-slate-300 h-28">
              <option v-for="cat in flattenCategories(props.categories)" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 text-sm text-slate-600">İptal</button>
            <button type="submit" :disabled="editForm.processing" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg">Güncelle</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isVariantModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-xl border shadow-xl max-w-lg w-full p-6 space-y-4">
        <h2 class="text-lg font-bold text-slate-900">
          {{ selectedVariant ? 'Varyantı Düzenle' : 'Yeni Varyant Ekle' }}
        </h2>

        <form @submit.prevent="submitVariant" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">SKU</label>
              <input v-model="variantForm.sku" type="text" class="w-full text-sm rounded-lg border-slate-300" required />
              <span v-if="variantForm.errors.sku" class="text-xs text-rose-500">{{ variantForm.errors.sku }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Fiyat (₺)</label>
              <input v-model="variantForm.price" type="number" step="0.01" class="w-full text-sm rounded-lg border-slate-300" required />
              <span v-if="variantForm.errors.price" class="text-xs text-rose-500">{{ variantForm.errors.price }}</span>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Stok</label>
              <input v-model="variantForm.stock" type="number" min="0" class="w-full text-sm rounded-lg border-slate-300" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Renk</label>
              <input v-model="variantForm.color" type="text" placeholder="Örn: Kırmızı" class="w-full text-sm rounded-lg border-slate-300" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Beden</label>
              <input v-model="variantForm.size" type="text" placeholder="Örn: XL" class="w-full text-sm rounded-lg border-slate-300" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase mb-1">Görsel URL (İsteğe Bağlı)</label>
            <input v-model="variantForm.image_url" type="url" class="w-full text-sm rounded-lg border-slate-300" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="isVariantModalOpen = false" class="px-4 py-2 text-sm text-slate-600">İptal</button>
            <button type="submit" :disabled="variantForm.processing" class="px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg">
              {{ selectedVariant ? 'Güncelle' : 'Kaydet' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>