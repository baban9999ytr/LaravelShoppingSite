<script setup>
import { ref, computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  featureValues: {
    type: Array,
    default: () => [],
  },
  variants: {
    type: Array,
    default: () => [],
  },
});

const selectedVariant = ref(props.variants && props.variants.length ? props.variants[0] : null);
const quantity = ref(1);

const activeImage = ref(
  selectedVariant.value?.image_url || props.product.image_url || 'https://via.placeholder.com/600x800?text=No+Image'
);

const images = computed(() => {
  const list = [];
  if (props.product.image_url) list.push(props.product.image_url);
  props.variants.forEach((v) => {
    if (v.image_url && !list.includes(v.image_url)) {
      list.push(v.image_url);
    }
  });
  return list.length ? list : ['https://via.placeholder.com/600x800?text=No+Image'];
});

const currentPrice = computed(() => {
  if (selectedVariant.value && selectedVariant.value.price) {
    return Number(selectedVariant.value.price);
  }
  return Number(props.product.price || 0);
});

const currentSku = computed(() => {
  if (selectedVariant.value && selectedVariant.value.sku) {
    return selectedVariant.value.sku;
  }
  return props.product.slug || `PRD-${props.product.id}`;
});

const currentStock = computed(() => {
  if (selectedVariant.value) {
    return selectedVariant.value.stock ?? 0;
  }
  return 99; 
});

const selectVariant = (variant) => {
  selectedVariant.value = variant;
  if (variant.image_url) {
    activeImage.value = variant.image_url;
  }
  if (quantity.value > currentStock.value) {
    quantity.value = Math.max(1, currentStock.value);
  }
};

// Quantity controls
const incrementQty = () => {
  if (quantity.value < currentStock.value) {
    quantity.value++;
  }
};
const decrementQty = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

const addToCart = () => {
  alert(`Added ${quantity.value} x "${props.product.name}" (${selectedVariant.value ? 'SKU: ' + currentSku.value : 'Standard'}) to cart!`);
};
</script>

<template>
  <Head :title="`${props.product.name} - LCW`" />

  <div class="min-h-screen bg-white text-gray-800 font-sans">
    <div class="bg-blue-600 text-white text-xs py-2 text-center font-medium">
      LCW STYLE CONCEPTS — Free Express Shipping on orders over ₺500
    </div>

    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <Link href="/" class="flex items-center space-x-2">
            <span class="text-2xl font-black tracking-tight text-blue-700">LCW</span>
            <span class="text-xs bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded">STORE</span>
          </Link>

          <div class="flex items-center space-x-6 text-sm">
            <Link href="/" class="text-gray-600 hover:text-blue-600 text-xs font-semibold">
              ← Back to Shop
            </Link>
            <button class="flex flex-col items-center text-gray-700 hover:text-blue-600 relative">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
              <span class="text-[11px] mt-0.5">Cart</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <nav class="flex text-xs text-gray-500 space-x-2 mb-6">
        <Link href="/" class="hover:text-blue-600">Home</Link>
        <span>/</span>
        <span v-if="categories && categories.length">
          <Link :href="`/?category=${categories[0].slug}`" class="hover:text-blue-600">
            {{ categories[0].name }}
          </Link>
          <span>/</span>
        </span>
        <span class="text-gray-900 font-medium truncate">{{ props.product.name }}</span>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <div class="lg:col-span-7 flex flex-col-reverse sm:flex-row gap-4">
          <div v-if="images.length > 1" class="flex sm:flex-col gap-3 overflow-x-auto sm:overflow-y-auto max-h-[550px] shrink-0">
            <button
              v-for="(img, idx) in images"
              :key="idx"
              @click="activeImage = img"
              :class="[
                'w-16 h-20 rounded border overflow-hidden shrink-0 transition-all',
                activeImage === img ? 'border-2 border-blue-600 ring-2 ring-blue-100' : 'border-gray-200 opacity-70 hover:opacity-100'
              ]"
            >
              <img :src="img" :alt="`Thumbnail ${idx + 1}`" class="w-full h-full object-cover" />
            </button>
          </div>

          <div class="flex-1 bg-gray-50 rounded-xl border border-gray-200 overflow-hidden relative aspect-3/4 max-h-[600px]">
            <img :src="activeImage" :alt="props.product.name" class="w-full h-full object-cover" />
            
            <button class="absolute top-4 right-4 p-2 bg-white/80 backdrop-blur rounded-full shadow hover:text-red-500 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="lg:col-span-5 space-y-6">
          
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="cat in categories"
              :key="cat.id"
              class="text-[10px] font-bold tracking-wider uppercase bg-blue-50 text-blue-700 px-2.5 py-1 rounded"
            >
              {{ cat.name }}
            </span>
          </div>

          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
              {{ props.product.name }}
            </h1>
            <p class="text-xs text-gray-400 mt-1 font-mono">SKU: {{ currentSku }}</p>
          </div>

          <div class="flex items-baseline space-x-3 border-b border-gray-100 pb-4">
            <span class="text-3xl font-extrabold text-gray-900">₺{{ currentPrice.toFixed(2) }}</span>
            <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded">KDV Dahil</span>
          </div>

          <div v-if="props.variants && props.variants.length" class="space-y-3">
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
              Varyant Seçiniz:
            </label>

            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="v in props.variants"
                :key="v.id"
                @click="selectVariant(v)"
                :class="[
                  'p-3 text-left border rounded-lg transition-all flex flex-col justify-between',
                  selectedVariant?.id === v.id
                    ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                ]"
              >
                <div class="flex justify-between items-center w-full">
                  <span class="text-xs font-semibold text-gray-900">
                    {{ v.color || '' }} {{ v.size ? `(${v.size})` : '' }}
                    <span v-if="!v.color && !v.size">SKU: {{ v.sku }}</span>
                  </span>
                  <span class="text-[11px] font-mono font-bold text-gray-700">₺{{ Number(v.price).toFixed(2) }}</span>
                </div>

                <div v-if="v.featureValues && v.featureValues.length" class="mt-1.5 flex flex-wrap gap-1">
                  <span
                    v-for="fv in v.featureValues"
                    :key="fv.id"
                    class="text-[9px] bg-white text-gray-600 px-1 py-0.5 rounded border border-gray-200"
                  >
                    {{ fv.feature?.name }}: {{ fv.value }}
                  </span>
                </div>
              </button>
            </div>
          </div>

          <div class="space-y-3 pt-2">
            <div class="flex justify-between items-center text-xs">
              <span class="font-bold uppercase tracking-wider text-gray-700">Adet:</span>
              <span :class="currentStock > 0 ? 'text-emerald-600 font-medium' : 'text-rose-600 font-medium'">
                {{ currentStock > 0 ? `Stokta Var (${currentStock} adet)` : 'Stokta Yok' }}
              </span>
            </div>

            <div class="flex items-center gap-4">
              <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
                <button
                  @click="decrementQty"
                  :disabled="quantity <= 1"
                  class="px-3 py-2 text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                >
                  -
                </button>
                <span class="px-4 py-2 text-sm font-bold text-gray-900 w-12 text-center">{{ quantity }}</span>
                <button
                  @click="incrementQty"
                  :disabled="quantity >= currentStock"
                  class="px-3 py-2 text-gray-600 hover:bg-gray-200 disabled:opacity-40"
                >
                  +
                </button>
              </div>

              <button
                @click="addToCart"
                :disabled="currentStock <= 0"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-md disabled:bg-gray-300 disabled:cursor-not-allowed text-sm uppercase tracking-wider"
              >
                Sepete Ekle
              </button>
            </div>
          </div>

          <div v-if="props.featureValues && props.featureValues.length" class="pt-4 border-t border-gray-100 space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Ürün Özellikleri</h3>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="fv in props.featureValues"
                :key="fv.id"
                class="text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 font-medium"
              >
                <strong class="text-slate-900">{{ fv.feature?.name || 'Özellik' }}:</strong> {{ fv.value }}
              </span>
            </div>
          </div>

          <div v-if="props.product.description" class="pt-4 border-t border-gray-100 space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Açıklama</h3>
            <p class="text-xs text-gray-600 leading-relaxed whitespace-pre-line">
              {{ props.product.description }}
            </p>
          </div>

        </div>
      </div>
    </main>
  </div>
</template>