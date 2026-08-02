<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
  auth: {
    type: Object,
    default: () => ({ user: null }),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  products: {
    type: Object,
    default: () => ({ data: [], links: [], total: 0 }),
  },
  features: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  user_favorites: {
    type: Array,
    default: () => [],
  },
  user_basket: {
    type: Array,
    default: () => [],
  },
});

const isUserMenuOpen = ref(false);
const search = ref(props.filters?.search || '');
const openMenuId = ref(null);

const priceFilter = reactive({
  min_price: props.filters?.min_price || '',
  max_price: props.filters?.max_price || '',
});

const selectedFeatures = ref(
  Array.isArray(props.filters?.features)
    ? props.filters.features.map(Number)
    : props.filters?.features
      ? [Number(props.filters.features)]
      : []
);

const cartItemCount = computed(() => {
  if (!props.user_basket) {
return 0;
}

  if (Array.isArray(props.user_basket)) {
    return props.user_basket.reduce((sum, item) => {
      if (typeof item === 'number') {
return sum + 1;
}

      if (item && typeof item.quantity === 'number') {
return sum + item.quantity;
}

      return sum + 1;
    }, 0);
  }

  if (typeof props.user_basket === 'object' && typeof props.user_basket.total !== 'undefined') {
    return props.user_basket.total;
  }

  return 0;
});

const handleSearch = () => {
  router.get('/', { ...props.filters, search: search.value }, { preserveState: true, preserveScroll: true });
};

const applyPriceFilter = () => {
  router.get(
    '/',
    {
      ...props.filters,
      min_price: priceFilter.min_price,
      max_price: priceFilter.max_price,
    },
    { preserveState: true, preserveScroll: true }
  );
};

const toggleFeatureFilter = (featureValueId) => {
  const index = selectedFeatures.value.indexOf(featureValueId);

  if (index > -1) {
    selectedFeatures.value.splice(index, 1);
  } else {
    selectedFeatures.value.push(featureValueId);
  }

  router.get(
    '/',
    {
      ...props.filters,
      features: selectedFeatures.value.length ? selectedFeatures.value : undefined,
    },
    { preserveState: true, preserveScroll: true }
  );
};

const clearFilters = () => {
  search.value = '';
  priceFilter.min_price = '';
  priceFilter.max_price = '';
  selectedFeatures.value = [];
  router.get('/', {}, { preserveState: true, preserveScroll: true });
};

const logout = () => {
  router.post('/logout');
};

const toggleFavorite = (productId) => {
  if (!props.auth?.user) {
    router.get('/login');

    return;
  }

  router.post(
    '/favorites/toggle',
    { product_id: productId },
    {
      preserveScroll: true,
      preserveState: true,
    }
  );
};

const addToCart = (productId) => {
  if (!props.auth?.user) {
    router.get('/login');

    return;
  }

  router.post(
    '/basket/add',
    { product_id: productId },
    {
      preserveScroll: true,
      preserveState: true,
    }
  );
};

const isFavorite = (productId) => {
  return props.user_favorites.includes(productId);
};

// const isInCart = (productId) => {
//   if (!props.user_basket) {
// return false;
// }

//   if (Array.isArray(props.user_basket)) {
//     return props.user_basket.some((item) => {
//       if (typeof item === 'number') {
// return item === productId;
// }

//       return item?.product_id === productId || item?.id === productId;
//     });
//   }

//   if (typeof props.user_basket === 'object' && Array.isArray(props.user_basket.data)) {
//     return props.user_basket.data.some((item) => item?.product_id === productId || item?.id === productId);
//   }

//   return false;
// };
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-800 font-sans">
    <div class="bg-blue-600 text-white text-xs py-2 text-center font-medium">
      LCW STYLE CONCEPTS — Free Express Shipping on orders over $50
    </div>

    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <Link href="/" class="flex items-center space-x-2">
            <span class="text-2xl font-black tracking-tight text-blue-700">LCW</span>
            <span class="text-xs bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded">VUE</span>
          </Link>

          <form @submit.prevent="handleSearch" class="flex-1 max-w-lg mx-8">
            <div class="relative">
              <input
                v-model="search"
                type="text"
                placeholder="Search products, categories..."
                class="w-full pl-4 pr-10 py-2 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
              />
              <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </button>
            </div>
          </form>

          <div class="flex items-center space-x-6 text-sm">
            <div class="relative">
              <template v-if="auth?.user">
                <button
                  @click="isUserMenuOpen = !isUserMenuOpen"
                  class="flex flex-col items-center text-gray-700 hover:text-blue-600 focus:outline-none"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="text-[11px] mt-0.5 font-medium max-w-[80px] truncate">
                    {{ auth.user.name.split(' ')[0] }}
                  </span>
                </button>

                <div
                  v-if="isUserMenuOpen"
                  @click="isUserMenuOpen = false"
                  class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-50 text-xs"
                >
                  <div class="px-4 py-2 border-b border-gray-100">
                    <p class="font-bold text-gray-900 truncate">{{ auth.user.name }}</p>
                    <p class="text-gray-500 truncate">{{ auth.user.email }}</p>
                  </div>

                  <Link href="/dashboard" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard</Link>
                  <Link href="/profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Account</Link>

                  <button
                    @click="logout"
                    class="w-full text-left block px-4 py-2 text-red-600 hover:bg-red-50 border-t border-gray-100"
                  >
                    Log Out
                  </button>
                </div>
              </template>

              <template v-else>
                <div class="flex items-center space-x-2 text-xs">
                  <Link href="/login" class="text-gray-700 hover:text-blue-600 font-semibold">Log In</Link>
                  <span class="text-gray-300">|</span>
                  <Link href="/register" class="text-blue-600 hover:underline font-semibold">Register</Link>
                </div>
              </template>
            </div>

            <Link href="/favorites" class="flex flex-col items-center text-gray-700 hover:text-red-600 relative">
              <svg
                class="w-6 h-6"
                :class="auth?.user && user_favorites?.length ? 'text-red-500 fill-current' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                  :fill="auth?.user && user_favorites?.length ? 'currentColor' : 'none'"
                />
              </svg>
              <span class="text-[11px] mt-0.5">Favorites</span>
              <span
                v-if="auth?.user && user_favorites?.length"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold"
              >
                {{ user_favorites.length }}
              </span>
            </Link>

            <Link href="/basket" class="flex flex-col items-center text-gray-700 hover:text-blue-600 relative">
              <svg
                class="w-6 h-6"
                :class="cartItemCount > 0 ? 'text-blue-600 fill-current' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                  :fill="cartItemCount > 0 ? 'currentColor' : 'none'"
                />
              </svg>
              <span class="text-[11px] mt-0.5">Cart</span>
              <span
                v-if="cartItemCount > 0"
                class="absolute -top-1 -right-1 bg-blue-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold"
              >
                {{ cartItemCount }}
              </span>
            </Link>
          </div>
        </div>

        <nav class="flex space-x-8 py-2 border-t border-gray-100 text-sm font-semibold text-gray-700 min-h-10.5 items-center relative">
          <Link
            href="/"
            :class="['hover:text-blue-600 whitespace-nowrap', !filters?.category ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : '']"
          >
            All Products
          </Link>

          <Link
            href="/?category=kadin"
            :class="['hover:text-blue-600 whitespace-nowrap', filters?.category === 'kadin' ? 'text-blue-600 font-bold' : '']"
          >
            Kadın
          </Link>

          <Link
            href="/?category=erkek"
            :class="['hover:text-blue-600 whitespace-nowrap', filters?.category === 'erkek' ? 'text-blue-600 font-bold' : '']"
          >
            Erkek
          </Link>

          <Link
            href="/?category=kislik"
            :class="['hover:text-blue-600 whitespace-nowrap', filters?.category === 'kislik' ? 'text-blue-600 font-bold' : '']"
          >
            Kışlık
          </Link>

          <template v-if="categories && categories.length">
            <div
              v-for="cat in categories"
              :key="cat.id"
              class="relative group py-1"
              @mouseenter="openMenuId = cat.id"
              @mouseleave="openMenuId = null"
            >
              <Link
                :href="`/?category=${cat.slug}`"
                :class="['hover:text-blue-600 whitespace-nowrap inline-flex items-center', filters?.category === cat.slug ? 'text-blue-600 font-bold' : '']"
              >
                {{ cat.name }}
                <svg v-if="cat.children && cat.children.length" class="w-3.5 h-3.5 ml-1 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </Link>

              <div
                v-if="cat.children && cat.children.length && openMenuId === cat.id"
                class="absolute left-0 top-full bg-white border border-gray-200 shadow-xl rounded-b-md p-4 z-50 mt-1 min-w-120 grid grid-cols-2 sm:grid-cols-3 gap-4"
              >
                <div v-for="subCat in cat.children" :key="subCat.id" class="space-y-1">
                  <Link
                    :href="`/?category=${subCat.slug}`"
                    class="block text-xs font-bold text-gray-900 hover:text-blue-600 border-b border-gray-100 pb-1"
                  >
                    {{ subCat.name }}
                  </Link>

                  <div v-if="subCat.children && subCat.children.length" class="space-y-0.5 pt-1">
                    <Link
                      v-for="leafCat in subCat.children"
                      :key="leafCat.id"
                      :href="`/?category=${leafCat.slug}`"
                      class="block text-[11px] text-gray-600 hover:text-blue-600 hover:underline"
                    >
                      {{ leafCat.name }}
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </nav>
      </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="flex flex-col lg:flex-row gap-8">
        
        <aside class="w-full lg:w-64 shrink-0">
          <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b pb-3">
              <h3 class="font-bold text-gray-900">Filters</h3>
              <button @click="clearFilters" class="text-xs text-blue-600 hover:underline">Clear all</button>
            </div>

            <div v-if="categories && categories.length" class="space-y-2">
              <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Categories</label>
              <ul class="space-y-1 text-xs">
                <li v-for="cat in categories" :key="cat.id">
                  <Link 
                    :href="`/?category=${cat.slug}`"
                    :class="['block py-1 hover:text-blue-600', filters?.category === cat.slug ? 'font-bold text-blue-600' : 'text-gray-600']"
                  >
                    {{ cat.name }}
                  </Link>
                  <ul v-if="cat.children && cat.children.length" class="pl-3 space-y-1 mt-1 border-l border-gray-100">
                    <li v-for="child in cat.children" :key="child.id">
                      <Link 
                        :href="`/?category=${child.slug}`"
                        :class="['block py-0.5 hover:text-blue-600', filters?.category === child.slug ? 'font-bold text-blue-600' : 'text-gray-500']"
                      >
                        {{ child.name }}
                      </Link>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>

            <div v-if="features && features.length" class="space-y-4 pt-4 border-t border-gray-100">
              <div v-for="feature in features" :key="feature.id" class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                  {{ feature.name }}
                </label>
                <div v-if="feature.values && feature.values.length" class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                  <label 
                    v-for="val in feature.values" 
                    :key="val.id"
                    class="flex items-center space-x-2 text-xs text-gray-700 cursor-pointer hover:text-blue-600"
                  >
                    <input 
                      type="checkbox"
                      :value="val.id"
                      :checked="selectedFeatures.includes(val.id)"
                      @change="toggleFeatureFilter(val.id)"
                      class="rounded text-blue-600 focus:ring-blue-500 h-3.5 w-3.5 border-gray-300"
                    />
                    <span>{{ val.value }}</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="space-y-3 pt-4 border-t border-gray-100">
              <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Price Range</label>
              <div class="flex items-center space-x-2">
                <input v-model="priceFilter.min_price" type="number" placeholder="Min" class="w-full px-3 py-1.5 text-xs border rounded focus:outline-none focus:ring-1 focus:ring-blue-500" />
                <span class="text-gray-400">-</span>
                <input v-model="priceFilter.max_price" type="number" placeholder="Max" class="w-full px-3 py-1.5 text-xs border rounded focus:outline-none focus:ring-1 focus:ring-blue-500" />
              </div>
              <button @click="applyPriceFilter" class="w-full mt-2 bg-gray-900 hover:bg-blue-600 text-white text-xs font-medium py-2 rounded transition-colors">
                Apply Filter
              </button>
            </div>
          </div>
        </aside>

        <section class="flex-1">
          <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold text-gray-900">
              {{ filters?.category ? filters.category.toUpperCase() : 'All Products' }}
              <span class="text-sm font-normal text-gray-500">({{ products?.total || 0 }} items)</span>
            </h1>
          </div>

          <div v-if="products?.data && products.data.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <Link 
              v-for="product in products.data" 
              :key="product.id"
              :href="`/products/${product.slug || product.id}`"
              class="group bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col block"
            >
              <div class="relative aspect-3/4 bg-gray-100 overflow-hidden">
                <img 
                  :src="product.image_url || 'https://via.placeholder.com/300x400?text=No+Image'" 
                  :alt="product.name" 
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                
                <button 
                  @click.prevent="toggleFavorite(product.id)" 
                  class="absolute top-2 right-2 p-1.5 bg-white rounded-full shadow-md transition-colors"
                  :class="isFavorite(product.id) ? 'text-red-500 fill-red-500' : 'text-gray-400 hover:text-red-500'"
                >
                  <svg class="w-4 h-4" :fill="isFavorite(product.id) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                  </svg>
                </button>
              </div>

              <div class="p-3 flex-1 flex flex-col justify-between">
                <div>
                  <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mb-1">
                    {{ product.categories?.map(c => c.name).join(', ') }}
                  </div>
                  <h2 class="text-xs font-medium text-gray-800 line-clamp-2 group-hover:text-blue-600 transition-colors">
                    {{ product.name }}
                  </h2>

                  <div v-if="product.feature_values && product.feature_values.length" class="mt-2 flex flex-wrap gap-1">
                    <span 
                      v-for="fv in product.feature_values" 
                      :key="fv.id"
                      class="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200"
                    >
                      {{ fv.feature?.name }}: {{ fv.value }}
                    </span>
                  </div>
                </div>

                <div class="mt-3 flex items-baseline justify-between">
                  <span class="text-sm font-bold text-gray-900">₺{{ Number(product.price).toFixed(2) }}</span>
                  
                  <button 
                    @click.prevent="addToCart(product.id)" 
                    class="text-xs text-blue-600 font-semibold hover:underline"
                  >
                    Add to Cart
                  </button>
                </div>
              </div>
            </Link>
          </div>

          <div v-else class="text-center py-12 bg-white rounded-lg border border-gray-200">
            <p class="text-gray-500 text-sm">No products found matching your filter options.</p>
          </div>

     <div v-if="products?.links && products.links.length" class="mt-8 flex justify-center space-x-1">
  <Link 
    v-for="(link, i) in products.links" 
    :key="i"
    :href="link.url || '#'"
    :class="[
      'px-3 py-1.5 text-xs rounded border',
      link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50',
      !link.url ? 'opacity-50 pointer-events-none' : ''
    ]"
  >
    <span v-html="link.label"></span>
  </Link>
</div>
        </section>
      </div>
    </main>
  </div>
</template>