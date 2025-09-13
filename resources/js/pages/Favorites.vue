<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

type Favorite = {
  asset_id: string;
  created_at?: string;
  updated_at?: string;
};

const loading = ref(true);
const error = ref<string | null>(null);
const favorites = ref<Favorite[]>([]);

const fetchFavorites = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await fetch('/api/favorites');
    if (!res.ok) throw new Error(`Failed to load favorites: ${res.status}`);
    favorites.value = await res.json();
  } catch (e: any) {
    error.value = e?.message ?? 'Unknown error fetching favorites';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchFavorites);

const hasData = computed(() => !loading.value && !error.value && favorites.value.length > 0);

const removing = ref<Record<string, boolean>>({});

const unfavorite = async (assetId: string) => {
  if (removing.value[assetId]) return;
  const idx = favorites.value.findIndex(f => f.asset_id === assetId);
  if (idx === -1) return;
  // optimistic remove
  const original = favorites.value[idx];
  favorites.value.splice(idx, 1);
  removing.value[assetId] = true;
  try {
    const res = await fetch(`/api/favorites/${encodeURIComponent(assetId)}`, { method: 'DELETE' });
    if (!res.ok && res.status !== 204) throw new Error('Failed to unfavorite');
  } catch (e) {
    // revert on error
    favorites.value.splice(idx, 0, original);
    console.error(e);
    alert('Unable to remove favorite. Please try again.');
  } finally {
    removing.value[assetId] = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-gray-200">
      <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <a href="/" class="text-sm text-gray-600 hover:underline">← Home</a>
          <h1 class="text-xl font-semibold">Favorites</h1>
        </div>
        <nav class="text-sm">
          <a href="/" class="hover:underline mr-3">Home</a>
          <a href="/favorites" class="hover:underline font-semibold">Favorites</a>
        </nav>
      </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6">
      <div v-if="loading" class="p-4 rounded border border-gray-200 bg-white">Loading favorites…</div>
      <div v-else-if="error" class="p-4 rounded border border-red-200 bg-red-50 text-red-800">{{ error }}</div>

      <div v-else-if="hasData" class="space-y-3">
        <div
          v-for="f in favorites"
          :key="f.asset_id"
          class="group rounded-lg border border-gray-200 bg-white p-4 flex items-center justify-between hover:border-gray-300 hover:shadow-sm transition"
        >
          <button class="text-left" @click="router.visit(`/assets/${f.asset_id}`)">
            <div class="font-medium">{{ f.asset_id }}</div>
            <div class="text-xs text-gray-500">Click to view details</div>
          </button>
          <button
            class="ml-3 text-sm px-3 py-1 rounded border border-gray-300 hover:bg-gray-50"
            :disabled="removing[f.asset_id]"
            @click="unfavorite(f.asset_id)"
          >
            {{ removing[f.asset_id] ? 'Removing…' : 'Unfavorite' }}
          </button>
        </div>
      </div>

      <div v-else class="p-4 rounded border bg-white text-gray-600">No favorites yet. Go to the Assets page and add some!</div>
    </main>
  </div>
</template>

<style scoped>
/* Minimal utility fallbacks similar to other pages */
:root {
  --gray-50: #f9fafb; --gray-200: #e5e7eb; --gray-300: #d1d5db; --gray-500: #6b7280; --gray-900: #111827;
  --red-50: #fef2f2; --red-200: #fecaca; --red-800: #991b1b;
}
.bg-gray-50 { background: var(--gray-50); }
.bg-white { background: #fff; }
.text-gray-900 { color: var(--gray-900); }
.text-gray-600 { color: #4b5563; }
.text-red-800 { color: var(--red-800); }
.border { border: 1px solid #e5e7eb; }
.border-gray-200 { border-color: var(--gray-200); }
.border-gray-300 { border-color: var(--gray-300); }
.rounded { border-radius: .25rem; }
.rounded-lg { border-radius: .5rem; }
.p-4 { padding: 1rem; }
.px-3 { padding-left: .75rem; padding-right: .75rem; }
.py-1 { padding-top: .25rem; padding-bottom: .25rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
.text-sm { font-size: .875rem; }
.text-xs { font-size: .75rem; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.hover\:underline:hover { text-decoration: underline; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-4 { gap: 1rem; }
.space-y-3 > * + * { margin-top: .75rem; }
.sticky { position: sticky; }
.top-0 { top: 0; }
.z-10 { z-index: 10; }
.backdrop-blur { backdrop-filter: blur(8px); }
.transition { transition: all .15s ease; }
.hover\:border-gray-300:hover { border-color: var(--gray-300); }
.hover\:shadow-sm:hover { box-shadow: 0 1px 2px rgba(0,0,0,.05); }
.min-h-screen { min-height: 100vh; }
.max-w-3xl { max-width: 48rem; }
</style>
