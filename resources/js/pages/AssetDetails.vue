<script setup lang="ts">
import { ArrowDownIcon, ArrowUpIcon, HeartIcon } from '@heroicons/vue/20/solid';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import ReactSparkline from '../components/ReactSparkline.vue';

const page = usePage();
const id = ref<string>(page.props.id as string);

interface MarketData {
    current_price?: { usd?: number };
    price_change_percentage_24h?: number | null;
}

interface AssetDetails {
    id: string;
    name: string;
    symbol: string;
    image: string;
    market_data?: MarketData;
    description?: { en?: string } | string;
}

interface HistoryPoint {
    timestamp: number;
    price: number;
}

const loading = ref(true);
const error = ref<string | null>(null);
const asset = ref<AssetDetails | null>(null);

const historyLoading = ref(true);
const historyError = ref<string | null>(null);
const history = ref<HistoryPoint[]>([]);

// Favorite state (copied pattern from Home.vue)
const isFavorite = ref<boolean>(false);
const toggling = ref<boolean>(false);

const fetchDetails = async () => {
    if (!id.value) return;
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch(`/api/assets/${encodeURIComponent(id.value)}`);
        if (!res.ok) throw new Error(`Failed to load asset: ${res.status}`);
        asset.value = await res.json();
    } catch (e: any) {
        error.value = e?.message ?? 'Unknown error fetching asset';
    } finally {
        loading.value = false;
    }
};

const fetchFavoriteStatus = async () => {
    try {
        const res = await fetch('/api/favorites');
        if (res.ok) {
            const favs: Array<{ asset_id: string }> = await res.json();
            isFavorite.value = !!favs.find((f) => f.asset_id === id.value);
        }
    } catch {
        // ignore
    }
};

const toggleFavorite = async () => {
    if (!id.value || toggling.value) return;
    const original = isFavorite.value;
    isFavorite.value = !original; // optimistic
    toggling.value = true;
    try {
        if (!original) {
            const res = await fetch('/api/favorites', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assetId: id.value }),
            });
            if (!res.ok) throw new Error('Failed to favorite');
        } else {
            const res = await fetch(`/api/favorites/${encodeURIComponent(id.value)}`, { method: 'DELETE' });
            if (!res.ok && res.status !== 204) throw new Error('Failed to unfavorite');
        }
    } catch (err) {
        isFavorite.value = original; // revert on error
        console.error(err);
        alert('Unable to update favorite. Please try again.');
    } finally {
        toggling.value = false;
    }
};

const fetchHistory = async (days = 7) => {
    if (!id.value) return;
    historyLoading.value = true;
    historyError.value = null;
    try {
        const res = await fetch(`/api/assets/${encodeURIComponent(id.value)}/history?days=${days}`);
        if (!res.ok) throw new Error(`Failed to load history: ${res.status}`);
        const data = await res.json();
        history.value = Array.isArray(data?.prices) ? data.prices : [];
    } catch (e: any) {
        historyError.value = e?.message ?? 'Unknown error fetching history';
    } finally {
        historyLoading.value = false;
    }
};

onMounted(async () => {
    await Promise.all([fetchDetails(), fetchHistory(7)]);
    await fetchFavoriteStatus();
});
watch(id, async () => {
    await Promise.all([fetchDetails(), fetchHistory(7)]);
    await fetchFavoriteStatus();
});

const price = () => {
    const v = asset.value?.market_data?.current_price?.usd;
    return typeof v === 'number' ? new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v) : '—';
};

const pct = () => {
    const v = asset.value?.market_data?.price_change_percentage_24h;
    return typeof v === 'number' ? `${v.toFixed(2)}%` : '—';
};

const formatUSD = (n: number) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n);
const formatDateTime = (ms: number) => {
    const d = new Date(ms);
    return isNaN(d.getTime()) ? '' : d.toLocaleString();
};

// Descending order for history list (newest first)
const historyDesc = computed(() => {
    return [...history.value].sort((a, b) => b.timestamp - a.timestamp);
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-gray-700">
        <AppHeader title="Lumina • Asset Details" />

        <main class="mx-auto max-w-3xl px-4 py-6">
            <template v-if="loading">
                <div class="rounded border border-slate-200 bg-white p-4 text-gray-600">Loading…</div>
            </template>
            <template v-else-if="error">
                <div class="rounded border border-red-200 bg-red-50 p-4 text-red-800">{{ error }}</div>
            </template>
            <template v-else-if="asset">
                <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-6 shadow-sm sm:px-6 sm:pt-6">
                    <!-- Favorite toggle in top-right -->
                    <button
                        class="absolute top-3 right-3 text-xl leading-none select-none"
                        :aria-pressed="isFavorite"
                        :title="isFavorite ? 'Unfavorite' : 'Favorite'"
                        @click="toggleFavorite"
                    >
                        <HeartIcon :class="[isFavorite ? 'text-indigo-400' : 'text-gray-300', 'size-5 shrink-0']" aria-hidden="true" />
                    </button>

                    <div class="flex items-center gap-4">
                        <img :src="asset.image" :alt="asset.name" class="h-16 w-16 rounded-full border border-slate-200" />
                        <div class="min-w-0">
                            <div class="truncate text-2xl font-semibold">{{ asset.name }}</div>
                            <div class="text-sm text-gray-500 uppercase">{{ asset.symbol }}</div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Price (USD)</div>
                            <div class="text-xl font-semibold">{{ price() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">24h Change</div>
                            <div
                                :class="[
                                    (asset.market_data?.price_change_percentage_24h ?? 0) >= 0 ? 'text-green-600' : 'text-red-600',
                                    'flex items-center text-xl font-semibold',
                                ]"
                            >
                                <ArrowUpIcon
                                    v-if="(asset.market_data?.price_change_percentage_24h ?? 0) >= 0"
                                    class="mr-1 size-4 shrink-0 text-green-500"
                                    aria-hidden="true"
                                />
                                <ArrowDownIcon v-else class="mr-1 size-4 shrink-0 text-red-500" aria-hidden="true" />
                                {{ pct() }}
                            </div>
                        </div>
                    </div>

                    <div v-if="(asset.description as any)?.en || typeof asset.description === 'string'" class="mt-6">
                        <div class="mb-1 text-sm text-gray-500">Description</div>
                        <p
                            class="text-sm leading-6 text-gray-700"
                            v-html="typeof asset.description === 'string' ? asset.description : asset.description?.en || ''"
                        ></p>
                    </div>
                </div>

                <!-- History Chart Card -->
                <div class="mt-4 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="text-sm text-gray-500">Price (last 7 days)</div>
                    </div>

                    <template v-if="historyLoading">
                        <div class="text-gray-600">Loading history…</div>
                    </template>
                    <template v-else-if="historyError">
                        <div class="rounded border border-red-200 bg-red-50 p-3 text-red-800">{{ historyError }}</div>
                    </template>
                    <template v-else>
                        <!-- React sparkline widget only -->
                        <div class="overflow-x-auto">
                            <ReactSparkline :prices="history.map((h) => h.price)" />
                        </div>
                    </template>
                </div>

                <!-- History List Card (separate) -->
                <div class="mt-4 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                    <div class="mb-2 text-sm text-gray-500">Historical Prices</div>

                    <template v-if="historyLoading">
                        <div class="text-gray-600">Loading history…</div>
                    </template>
                    <template v-else-if="historyError">
                        <div class="rounded border border-red-200 bg-red-50 p-3 text-red-800">{{ historyError }}</div>
                    </template>
                    <template v-else>
                        <div v-if="history.length" class="-mx-4 max-h-64 overflow-y-auto">
                            <ul role="list" class="divide-y divide-slate-200">
                                <li v-for="point in historyDesc" :key="point.timestamp" class="flex items-center justify-between px-4 py-2">
                                    <span class="text-sm text-gray-600">{{ formatDateTime(point.timestamp) }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ formatUSD(point.price) }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="text-gray-600">No history data</div>
                    </template>
                </div>
            </template>
            <template v-else>
                <div class="rounded border border-slate-200 bg-white p-4 text-gray-600">No data.</div>
            </template>
        </main>
    </div>
</template>
