<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

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
  image: { small?: string; large?: string; thumb?: string } | string;
  market_data?: MarketData;
  description?: { en?: string } | string;
}

const loading = ref(true);
const error = ref<string | null>(null);
const asset = ref<AssetDetails | null>(null);
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
  } catch (e) {
    isFavorite.value = original;
    console.error(e);
    alert('Unable to update favorite. Please try again.');
  } finally {
    toggling.value = false;
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

const img = () => {
  const i = (asset.value?.image ?? {}) as any;
  return i.large || i.small || i.thumb || (typeof i === 'string' ? i : '');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-gray-200">
      <div class="max-w-3xl mx-auto px-4 py-4 flex items-center gap-4 justify-between">
        <div class="flex items-center gap-4 min-w-0">
          <button class="text-sm text-gray-600 hover:underline" @click="router.visit('/')">← Back</button>
          <h1 class="text-xl font-semibold truncate">Asset Details</h1>
        </div>
        <button
          class="text-2xl leading-none select-none"
          :aria-pressed="isFavorite"
          :title="isFavorite ? 'Unfavorite' : 'Favorite'"
          @click="toggleFavorite"
        >
          <span :class="isFavorite ? 'text-yellow-500' : 'text-gray-300'">{{ isFavorite ? '★' : '☆' }}</span>
        </button>
      </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6">
      <div v-if="loading" class="p-4 rounded border border-gray-200 bg-white">Loading…</div>
      <div v-else-if="error" class="p-4 rounded border border-red-200 bg-red-50 text-red-800">{{ error }}</div>

      <div v-else-if="asset" class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="flex items-center gap-4">
          <img :src="img()" :alt="asset.name" class="w-16 h-16 rounded-full border" />
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
            <div :class="['text-xl font-semibold', (asset.market_data?.price_change_percentage_24h ?? 0) >= 0 ? 'text-green-600' : 'text-red-600']">
              {{ pct() }}
            </div>
          </div>
        </div>

        <div v-if="(asset.description as any)?.en || typeof asset.description === 'string'" class="mt-6 prose max-w-none">
          <div class="text-sm text-gray-500 mb-1">Description</div>
          <p class="text-sm leading-6 text-gray-700" v-html="typeof asset.description === 'string' ? asset.description : (asset.description?.en || '')"></p>
        </div>
      </div>

      <div v-else class="p-4 rounded border bg-white text-gray-600">No data.</div>
    </main>
  </div>
</template>

<style scoped>
:root {
  --gray-50: #f9fafb; --gray-200: #e5e7eb; --gray-300: #d1d5db; --gray-500: #6b7280; --gray-700: #374151; --gray-900: #111827;
  --red-50: #fef2f2; --red-200: #fecaca; --red-800: #991b1b; --green-600: #16a34a; --red-600: #dc2626;
}
.bg-gray-50 { background: var(--gray-50); }
.bg-white { background: #fff; }
.text-gray-900 { color: var(--gray-900); }
.text-gray-700 { color: var(--gray-700); }
.text-gray-600 { color: #4b5563; }
.text-red-800 { color: var(--red-800); }
.text-green-600 { color: var(--green-600); }
.text-red-600 { color: var(--red-600); }
.text-yellow-500 { color: #eab308; }
.text-gray-300 { color: #d1d5db; }
.border { border: 1px solid #e5e7eb; }
.border-gray-200 { border-color: var(--gray-200); }
.rounded-lg { border-radius: .5rem; }
.rounded-full { border-radius: 9999px; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.mt-6 { margin-top: 1.5rem; }
.mb-1 { margin-bottom: .25rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.text-xl { font-size: 1.25rem; }
.text-2xl { font-size: 1.5rem; }
.text-sm { font-size: .875rem; }
.font-semibold { font-weight: 600; }
.uppercase { text-transform: uppercase; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-4 { gap: 1rem; }
.grid { display: grid; }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.gap-4 { gap: 1rem; }
.sticky { position: sticky; }
.top-0 { top: 0; }
.z-10 { z-index: 10; }
.backdrop-blur { backdrop-filter: blur(8px); }
.min-h-screen { min-height: 100vh; }
.max-w-3xl { max-width: 48rem; }
.prose :where(p) { margin: 0; }
</style>
