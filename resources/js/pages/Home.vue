<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

type Asset = {
  id: string;
  name: string;
  symbol: string;
  image: string;
  current_price: number;
  price_change_percentage_24h: number | null;
  is_favorite: boolean;
};

const loading = ref(true);
const error = ref<string | null>(null);
const assets = ref<Asset[]>([]);

const fetchAssets = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await fetch('/api/assets');
    if (!res.ok) throw new Error(`Failed to load assets: ${res.status}`);
    assets.value = await res.json();
  } catch (e: any) {
    error.value = e?.message ?? 'Unknown error fetching assets';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchAssets);

const formatted = (n: number) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n);
const pct = (n: number | null) => n === null ? '—' : `${n.toFixed(2)}%`;

const hasData = computed(() => !loading.value && !error.value && assets.value.length > 0);

const goTo = (id: string) => router.visit(`/assets/${id}`);

const toggling = ref<Record<string, boolean>>({});

const toggleFavorite = async (a: Asset) => {
  if (toggling.value[a.id]) return;
  const original = a.is_favorite;
  a.is_favorite = !original; // optimistic
  toggling.value[a.id] = true;
  try {
    if (!original) {
      const res = await fetch('/api/favorites', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assetId: a.id }),
      });
      if (!res.ok) throw new Error('Failed to favorite');
    } else {
      const res = await fetch(`/api/favorites/${encodeURIComponent(a.id)}`, { method: 'DELETE' });
      if (!res.ok && res.status !== 204) throw new Error('Failed to unfavorite');
    }
  } catch (err) {
    a.is_favorite = original; // revert on error
    console.error(err);
    alert('Unable to update favorite. Please try again.');
  } finally {
    toggling.value[a.id] = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-gray-200">
      <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Lumina • Assets</h1>
        <nav class="text-sm">
          <a href="/" class="hover:underline mr-3">Home</a>
          <a href="/favorites" class="hover:underline">Favorites</a>
        </nav>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-6">
      <div v-if="loading" class="p-4 rounded border border-gray-200 bg-white">Loading assets…</div>
      <div v-else-if="error" class="p-4 rounded border border-red-200 bg-red-50 text-red-800">{{ error }}</div>

      <div v-else-if="hasData" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <button
          v-for="a in assets"
          :key="a.id"
          @click="goTo(a.id)"
          class="group text-left rounded-lg border border-gray-200 bg-white p-4 hover:border-gray-300 hover:shadow-sm transition"
        >
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <img :src="a.image" :alt="a.name" class="size-10 rounded-full border" />
              <div class="min-w-0">
                <div class="font-medium truncate">{{ a.name }}</div>
                <div class="text-xs uppercase text-gray-500">{{ a.symbol }}</div>
              </div>
            </div>
            <button
              class="ml-3 text-xl leading-none select-none"
              :aria-pressed="a.is_favorite"
              :title="a.is_favorite ? 'Unfavorite' : 'Favorite'"
              @click.stop="toggleFavorite(a)"
            >
              <span :class="a.is_favorite ? 'text-yellow-500' : 'text-gray-300'">{{ a.is_favorite ? '★' : '☆' }}</span>
            </button>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <div class="text-lg font-semibold">{{ formatted(a.current_price) }}</div>
            <div :class="[a.price_change_percentage_24h! >= 0 ? 'text-green-600' : 'text-red-600', 'text-sm font-medium']">
              {{ pct(a.price_change_percentage_24h) }}
            </div>
          </div>
        </button>
      </div>

      <div v-else class="p-4 rounded border bg-white text-gray-600">No assets to display.</div>
    </main>
  </div>
</template>

<style scoped>
/***** Minimal utility fallbacks if Tailwind isn’t enabled *****/
:root {
  --gray-50: #f9fafb; --gray-200: #e5e7eb; --gray-300: #d1d5db; --gray-500: #6b7280; --gray-900: #111827;
  --red-50: #fef2f2; --red-200: #fecaca; --red-800: #991b1b; --green-600: #16a34a; --red-600: #dc2626;
}
.bg-gray-50 { background: var(--gray-50); }
.bg-white { background: #fff; }
.text-gray-900 { color: var(--gray-900); }
.text-gray-600 { color: #4b5563; }
.text-red-800 { color: var(--red-800); }
.text-green-600 { color: var(--green-600); }
.text-red-600 { color: var(--red-600); }
.text-yellow-500 { color: #eab308; }
.text-gray-300 { color: #d1d5db; }
.border { border: 1px solid #e5e7eb; }
.border-gray-200 { border-color: var(--gray-200); }
.border-gray-300 { border-color: var(--gray-300); }
.rounded { border-radius: .25rem; }
.rounded-lg { border-radius: .5rem; }
.rounded-full { border-radius: 9999px; }
.p-4 { padding: 1rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.mt-3 { margin-top: .75rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.size-10 { width: 2.5rem; height: 2.5rem; }
.text-lg { font-size: 1.125rem; }
.text-xl { font-size: 1.25rem; }
.text-xs { font-size: .75rem; }
.text-sm { font-size: .875rem; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.uppercase { text-transform: uppercase; }
.truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.flex { display: flex; }
.items-center { align-items: center; }
.items-baseline { align-items: baseline; }
.justify-between { justify-content: space-between; }
.gap-3 { gap: .75rem; }
.grid { display: grid; }
.gap-3 { gap: .75rem; }
.sm\:grid-cols-2 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
@media (min-width: 640px) { .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.lg\:grid-cols-3 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
@media (min-width: 1024px) { .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
.sticky { position: sticky; }
.top-0 { top: 0; }
.z-10 { z-index: 10; }
.backdrop-blur { backdrop-filter: blur(8px); }
.transition { transition: all .15s ease; }
.hover\:border-gray-300:hover { border-color: var(--gray-300); }
.hover\:shadow-sm:hover { box-shadow: 0 1px 2px rgba(0,0,0,.05); }
.min-h-screen { min-height: 100vh; }
.max-w-5xl { max-width: 64rem; }
</style>
