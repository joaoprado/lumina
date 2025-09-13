<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppHeader from '../components/AppHeader.vue';
import { ArrowDownIcon, ArrowUpIcon } from '@heroicons/vue/20/solid'

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

const loading = ref(true);
const error = ref<string | null>(null);
const asset = ref<AssetDetails | null>(null);
const isFavorite = ref<boolean>(false);

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
  if (!id.value) return;
  try {
    const res = await fetch('/api/favorites');
    if (!res.ok) throw new Error('Failed to load favorites');
    const list: Array<{ asset_id: string }> = await res.json();
    isFavorite.value = !!list.find((f) => f.asset_id === id.value);
  } catch (e) {
    // non-fatal
    console.warn(e);
  }
};


onMounted(async () => {
  await Promise.all([fetchDetails(), fetchFavoriteStatus()]);
});
watch(id, async () => {
  await Promise.all([fetchDetails(), fetchFavoriteStatus()]);
});

const price = () => {
  const v = asset.value?.market_data?.current_price?.usd;
  return typeof v === 'number' ? new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v) : '—';
};

const pct = () => {
  const v = asset.value?.market_data?.price_change_percentage_24h;
  return typeof v === 'number' ? `${v.toFixed(2)}%` : '—';
};

</script>

<template>
  <div class="min-h-screen bg-slate-50 text-gray-700">
    <AppHeader title="Lumina • Asset Details" />

    <main class="max-w-3xl mx-auto px-4 py-6">
      <template v-if="loading">
        <div class="p-4 rounded border border-slate-200 bg-white text-gray-600">Loading…</div>
      </template>
      <template v-else-if="error">
        <div class="p-4 rounded border border-red-200 bg-red-50 text-red-800">{{ error }}</div>
      </template>
      <template v-else-if="asset">
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-6 shadow-sm sm:px-6 sm:pt-6">
          <div class="flex items-center gap-4">
            <img :src="asset.image" :alt="asset.name" class="w-16 h-16 rounded-full border border-slate-200" />
            <div class="min-w-0">
              <div class="text-2xl font-semibold truncate">{{ asset.name }}</div>
              <div class="text-sm uppercase text-gray-500">{{ asset.symbol }}</div>
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
                  'text-xl font-semibold flex items-center'
                ]"
              >
                <ArrowUpIcon
                  v-if="(asset.market_data?.price_change_percentage_24h ?? 0) >= 0"
                  class="size-4 mr-1 shrink-0 text-green-500"
                  aria-hidden="true"
                />
                <ArrowDownIcon
                  v-else
                  class="size-4 mr-1 shrink-0 text-red-500"
                  aria-hidden="true"
                />
                {{ pct() }}
              </div>
            </div>
          </div>

          <div v-if="(asset.description as any)?.en || typeof asset.description === 'string'" class="mt-6">
            <div class="text-sm mb-1 text-gray-500">Description</div>
            <p class="text-sm leading-6 text-gray-700" v-html="typeof asset.description === 'string' ? asset.description : (asset.description?.en || '')"></p>
          </div>
        </div>
      </template>
      <template v-else>
        <div class="p-4 rounded border border-slate-200 bg-white text-gray-600">No data.</div>
      </template>
    </main>
  </div>
</template>
