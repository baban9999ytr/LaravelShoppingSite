<script setup lang="ts">
import { Link, Head, usePage, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface OrderItem {
    product_id: number;
    product_name: string;
    variant_id?: number | null;
    quantity: number;
    unit_price: number | string;
    total_price: number | string;
}

interface Order {
    id: number;
    user_id: number;
    origin_city: string;
    destination_city: string;
    status?: string;
    did_arrive?: boolean;
    ordered_at: string;
    estimated_arrival_at: string;
    items: OrderItem[];
    is_delivered: boolean;
    is_refundable: boolean;
    remaining_refund_time?: string;
    refund_deadline?: string;
}

interface Props {
    orders?: Order[];
}

const props = withDefaults(defineProps<Props>(), {
    orders: () => [],
});

const page = usePage<any>();

const auth = computed(() => {
    return page.props.auth as { user?: any } | undefined;
});

const userFavorites = computed<number[]>(() => {
    return (page.props.user_favorites as number[]) || [];
});

const isUserMenuOpen = ref(false);
const activeTab = ref<'all' | 'active' | 'completed' | 'refunded'>('all');
const refundingOrderId = ref<number | null>(null);

const flashSuccess = computed(() => page.props.flash?.status || page.props.status);
const flashError = computed(() => page.props.errors?.error);

const filteredOrders = computed(() => {
    if (activeTab.value === 'active') {
        return props.orders.filter(o => !o.is_delivered && o.status !== 'refunded' && o.status !== 'cancelled');
    }
    if (activeTab.value === 'completed') {
        return props.orders.filter(o => o.is_delivered && o.status !== 'refunded');
    }
    if (activeTab.value === 'refunded') {
        return props.orders.filter(o => o.status === 'refunded');
    }
    return props.orders;
});

const refundForm = useForm({});

const handleRefundRequest = (orderId: number) => {
    if (!confirm('Are you sure you want to request a refund for this order? Items will be restocked upon processing.')) {
        return;
    }

    refundingOrderId.value = orderId;
    refundForm.post(`/orders/${orderId}/refund`, {
        preserveScroll: true,
        onFinish: () => {
            refundingOrderId.value = null;
        },
    });
};

const formatDate = (dateString?: string): string => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('tr-TR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const calculateOrderTotal = (order: Order): number => {
    if (!order.items || !Array.isArray(order.items)) return 0;
    return order.items.reduce((sum, item) => sum + Number(item.total_price || 0), 0);
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Order History & Refunds - LCW" />

    <div class="min-h-screen bg-gray-50 font-sans text-gray-800">
        <!-- Top Announcement Bar -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 py-2 text-center text-xs font-medium text-white">
            📦 Track Orders & Manage 15-Day Easy Returns
        </div>

        <!-- Sticky Header (Matching Template Layout) -->
        <header class="sticky top-0 z-50 border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center space-x-2">
                        <span class="text-2xl font-black tracking-tight text-blue-700">LCW</span>
                        <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800">VUE</span>
                    </Link>

                    <div class="flex items-center space-x-6 text-sm">
                        <Link href="/" class="text-xs font-semibold text-gray-600 hover:text-blue-600">
                            ← Back to Store
                        </Link>

                        <!-- User Profile Menu -->
                        <div class="relative">
                            <template v-if="auth?.user">
                                <button
                                    @click="isUserMenuOpen = !isUserMenuOpen"
                                    class="flex flex-col items-center text-gray-700 hover:text-blue-600 focus:outline-none"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="mt-0.5 max-w-[80px] truncate text-[11px] font-medium">
                                        {{ auth.user.name.split(' ')[0] }}
                                    </span>
                                </button>

                                <div
                                    v-if="isUserMenuOpen"
                                    @click="isUserMenuOpen = false"
                                    class="absolute right-0 z-50 mt-2 w-48 rounded-md border border-gray-200 bg-white py-1 text-xs shadow-lg"
                                >
                                    <div class="border-b border-gray-100 px-4 py-2">
                                        <p class="truncate font-bold text-gray-900">{{ auth.user.name }}</p>
                                        <p class="truncate text-gray-500">{{ auth.user.email }}</p>
                                    </div>
                                    <Link href="/favorites" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Favorites</Link>
                                    <button @click="logout" class="block w-full border-t border-gray-100 px-4 py-2 text-left text-red-600 hover:bg-red-50">
                                        Log Out
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Favorites Link -->
                        <Link href="/favorites" class="relative flex flex-col items-center text-gray-700 hover:text-red-600">
                            <svg
                                class="h-6 w-6"
                                :class="userFavorites.length ? 'fill-current text-red-500' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" :fill="userFavorites.length ? 'currentColor' : 'none'" />
                            </svg>
                            <span class="mt-0.5 text-[11px]">Favorites</span>
                            <span v-if="userFavorites.length" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                {{ userFavorites.length }}
                            </span>
                        </Link>

                        <!-- Cart Link -->
                        <Link href="/basket" class="relative flex flex-col items-center text-gray-700 hover:text-blue-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span class="mt-0.5 text-[11px]">Cart</span>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Global Flash Alerts -->
            <div v-if="flashError" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                ⚠️ {{ flashError }}
            </div>

            <div v-if="flashSuccess" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
                ✅ {{ flashSuccess }}
            </div>

            <!-- Page Title & Navigation Tabs -->
            <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        My Orders
                        <span class="ml-2 text-base font-normal text-gray-500">
                            ({{ orders.length }} {{ orders.length === 1 ? 'order' : 'orders' }})
                        </span>
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        View order status and request 15-day returns on delivered items.
                    </p>
                </div>

                <!-- Filter Tabs -->
                <div class="flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
                    <button
                        @click="activeTab = 'all'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'all' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    >
                        All
                    </button>
                    <button
                        @click="activeTab = 'active'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'active' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    >
                        In Transit
                    </button>
                    <button
                        @click="activeTab = 'completed'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'completed' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    >
                        Delivered
                    </button>
                    <button
                        @click="activeTab = 'refunded'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'refunded' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'"
                    >
                        Refunded
                    </button>
                </div>
            </div>

            <!-- Orders List -->
            <div v-if="filteredOrders.length" class="space-y-6">
                <div
                    v-for="order in filteredOrders"
                    :key="order.id"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md"
                >
                    <!-- Order Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 bg-gray-50/60 p-4 text-xs">
                        <div class="flex flex-wrap items-center gap-6">
                            <div>
                                <span class="block text-gray-400 font-medium">Order Number</span>
                                <span class="font-bold text-gray-900">#ORD-{{ order.id }}</span>
                            </div>

                            <div>
                                <span class="block text-gray-400 font-medium">Order Date</span>
                                <span class="font-semibold text-gray-700">{{ formatDate(order.ordered_at) }}</span>
                            </div>

                            <div>
                                <span class="block text-gray-400 font-medium">Route</span>
                                <span class="font-semibold text-gray-700">{{ order.origin_city }} → {{ order.destination_city }}</span>
                            </div>

                            <div>
                                <span class="block text-gray-400 font-medium">Total Amount</span>
                                <span class="font-bold text-blue-700">₺{{ calculateOrderTotal(order).toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Dynamic Status Badge -->
                        <div>
                            <span v-if="order.status === 'refunded'" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                                🔄 Refunded
                            </span>
                            <span v-else-if="!order.is_delivered" class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 border border-amber-200">
                                🚚 In Transit (Estimated Arrival: {{ formatDate(order.estimated_arrival_at) }})
                            </span>
                            <span v-else-if="order.is_refundable" class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                ✅ Delivered (Refund Eligible)
                            </span>
                            <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 border border-gray-200">
                                🔒 Delivered (Refund Window Expired)
                            </span>
                        </div>
                    </div>

                    <!-- Order Items List -->
                    <div class="divide-y divide-gray-100 p-4">
                        <div
                            v-for="(item, index) in order.items"
                            :key="index"
                            class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 font-bold text-xs">
                                    {{ item.quantity }}x
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">{{ item.product_name }}</h4>
                                    <p v-if="item.variant_id" class="text-xs text-gray-400">Variant ID: #{{ item.variant_id }}</p>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="text-xs text-gray-400 block">₺{{ Number(item.unit_price).toFixed(2) }} / unit</span>
                                <span class="text-sm font-bold text-gray-900">₺{{ Number(item.total_price).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Actions & 15-Day Refund Notice Footer -->
                    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 bg-gray-50/30 p-4 text-xs">
                        <div>
                            <span v-if="order.status === 'refunded'" class="text-gray-500">
                                This order has been refunded and items have been restored to inventory.
                            </span>
                            <span v-else-if="order.is_refundable" class="text-emerald-700 font-medium">
                                🕒 {{ order.remaining_refund_time }} left to request a 15-day return.
                            </span>
                            <span v-else-if="order.is_delivered" class="text-gray-400">
                                The 15-day refund window for this order has closed.
                            </span>
                            <span v-else class="text-amber-700 font-medium">
                                Return window will open automatically once delivered.
                            </span>
                        </div>

                        <!-- Refund Trigger Button -->
                        <div v-if="order.is_refundable && order.status !== 'refunded'">
                            <button
                                @click="handleRefundRequest(order.id)"
                                :disabled="refundingOrderId === order.id || refundForm.processing"
                                class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white shadow transition-colors hover:bg-red-700 disabled:opacity-50"
                            >
                                <span v-if="refundingOrderId === order.id">Processing Refund...</span>
                                <span v-else>Request Refund</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="mx-auto flex max-w-md flex-col items-center rounded-2xl border border-gray-200 bg-white py-16 text-center shadow-sm">
                <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-10 w-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">No orders found</h2>
                <p class="mt-2 px-6 text-sm text-gray-500">
                    You haven't placed any orders matching this filter yet.
                </p>
                <Link href="/" class="mt-6 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-colors hover:bg-blue-700">
                    Start Shopping
                </Link>
            </div>
        </main>
    </div>
</template>