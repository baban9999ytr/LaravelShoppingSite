<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
// import { route } from 'ziggy-js'; 
const props = defineProps({
  features: {
    type: Array,
    default: () => [],
  },
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const editingFeature = ref(null);
const newValueInput = ref({});

const form = useForm({
  name: '',
  slug: '',
  values: [{ value: '' }],
});

const valueForm = useForm({
  value: '',
});

const filteredFeatures = computed(() => {
  if (!searchQuery.value) {
return props.features;
}

  const q = searchQuery.value.toLowerCase();

  return props.features.filter(
    (f) =>
      f.name.toLowerCase().includes(q) ||
      f.slug.toLowerCase().includes(q) ||
      f.values?.some((v) => v.value.toLowerCase().includes(q))
  );
});

const openCreateModal = () => {
  editingFeature.value = null;
  form.reset();
  form.clearErrors();
  form.values = [{ value: '' }];
  isModalOpen.value = true;
};

const openEditModal = (feature) => {
  editingFeature.value = feature;
  form.clearErrors();
  form.name = feature.name;
  form.slug = feature.slug;
  form.values = feature.values.map((v) => ({ value: v.value }));
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  form.reset();
};

const addValueRow = () => {
  form.values.push({ value: '' });
};

const removeValueRow = (index) => {
  if (form.values.length > 1) {
    form.values.splice(index, 1);
  }
};

const submitFeatureForm = () => {
  if (editingFeature.value) {
    form.put(`/features/${editingFeature.value.id}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/features', {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
};

const deleteFeature = (feature) => {
  if (confirm(`"${feature.name}" özelliğini silmek istediğinize emin misiniz?`)) {
    router.delete(`/features/${feature.id}`, {
      preserveScroll: true,
    });
  }
};

const submitQuickValue = (feature) => {
  const val = newValueInput.value[feature.id];

  if (!val || !val.trim()) {
return;
}

  valueForm.value = val;
  valueForm.post(`/features/${feature.id}/values`, {
    preserveScroll: true,
    onSuccess: () => {
      newValueInput.value[feature.id] = '';
      valueForm.reset();
    },
  });
};

const deleteValue = (valueId) => {
  if (confirm('Bu değeri silmek istediğinize emin misiniz?')) {
    router.delete(`/feature-values/${valueId}`, {
      preserveScroll: true,
    });
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-6">
    <div class="mx-auto max-w-7xl">
      
      <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Ürün Özellikleri</h1>
          <p class="text-sm text-slate-500">Ürünlerinize atanacak teknik özellik gruplarını ve seçenekleri yönetin.</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Yeni Özellik Ekle
        </button>
      </div>

      <div class="mb-6">
        <div class="relative max-w-md">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Özellik veya değer ara..."
            class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div v-if="filteredFeatures.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="feature in filteredFeatures"
          :key="feature.id"
          class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md"
        >
          <div class="border-b border-slate-100 p-5">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-base font-semibold text-slate-900">{{ feature.name }}</h3>
                <span class="inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-500">
                  {{ feature.slug }}
                </span>
              </div>
              <div class="flex items-center space-x-1">
                <button
                  @click="openEditModal(feature)"
                  class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                  title="Düzenle"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 210.3H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button
                  @click="deleteFeature(feature)"
                  class="rounded p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600"
                  title="Sil"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="flex-1 p-5">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Değerler</p>
            <div class="flex flex-wrap gap-2">
              <div
                v-for="val in feature.values"
                :key="val.id"
                class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 py-1 pl-2.5 pr-1 text-xs font-medium text-slate-700"
              >
                <span>{{ val.value }}</span>
                <button
                  @click="deleteValue(val.id)"
                  class="ml-1.5 rounded p-0.5 text-slate-400 hover:bg-slate-200 hover:text-red-600"
                >
                  <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <span v-if="!feature.values || feature.values.length === 0" class="text-xs text-slate-400 italic">
                Henüz değer eklenmemiş.
              </span>
            </div>
          </div>

          <div class="border-t border-slate-100 bg-slate-50/50 p-4 rounded-b-xl">
            <form @submit.prevent="submitQuickValue(feature)" class="flex gap-2">
              <input
                v-model="newValueInput[feature.id]"
                type="text"
                placeholder="Hızlı değer ekle..."
                class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none"
              />
              <button
                type="submit"
                :disabled="valueForm.processing"
                class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-50"
              >
                Ekle
              </button>
            </form>
          </div>
        </div>
      </div>

      <div v-else class="rounded-xl border border-dashed border-slate-300 p-12 text-center bg-white">
        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 class="mt-2 text-sm font-semibold text-slate-900">Özellik Bulunamadı</h3>
        <p class="mt-1 text-sm text-slate-500">Aramanıza uygun bir özellik yok veya henüz eklenmemiş.</p>
        <div class="mt-6">
          <button
            @click="openCreateModal"
            class="inline-flex items-center rounded-md bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
          >
            Özellik Oluştur
          </button>
        </div>
      </div>

      <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>

        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl transition-all">
            <h2 class="text-lg font-bold text-slate-900">
              {{ editingFeature ? 'Özelliği Düzenle' : 'Yeni Özellik Ekle' }}
            </h2>

            <form @submit.prevent="submitFeatureForm" class="mt-4 space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700">Özellik Adı</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="örn: Renk, Beden, Materyal"
                  class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none"
                  :class="{ 'border-red-500': form.errors.name }"
                />
                <span v-if="form.errors.name" class="text-xs text-red-500 mt-1 block">{{ form.errors.name }}</span>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700">
                  SEO Slug <span class="text-slate-400 font-normal">(İsteğe bağlı)</span>
                </label>
                <input
                  v-model="form.slug"
                  type="text"
                  placeholder="otomatik-olusturulur"
                  class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none"
                  :class="{ 'border-red-500': form.errors.slug }"
                />
                <span v-if="form.errors.slug" class="text-xs text-red-500 mt-1 block">{{ form.errors.slug }}</span>
              </div>

              <div v-if="!editingFeature" class="space-y-2">
                <label class="block text-xs font-semibold text-slate-700">Başlangıç Değerleri</label>
                <div v-for="(val, index) in form.values" :key="index" class="flex gap-2">
                  <input
                    v-model="val.value"
                    type="text"
                    placeholder="örn: Kırmızı"
                    class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none"
                  />
                  <button
                    type="button"
                    @click="removeValueRow(index)"
                    :disabled="form.values.length === 1"
                    class="rounded-lg border border-slate-200 px-2.5 text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-30"
                  >
                    ✕
                  </button>
                </div>
                <button
                  type="button"
                  @click="addValueRow"
                  class="mt-1 text-xs font-semibold text-blue-600 hover:text-blue-500"
                >
                  + Yeni Değer Satırı Ekle
                </button>
              </div>

              <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-slate-100">
                <button
                  type="button"
                  @click="closeModal"
                  class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                >
                  {{ editingFeature ? 'Güncelle' : 'Kaydet' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>