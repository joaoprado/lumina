<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';

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

onMounted(() => {
    fetchFavorites();
});

const hasData = computed(() => !loading.value && !error.value && favorites.value.length > 0);

const removing = ref<Record<string, boolean>>({});

const unfavorite = async (assetId: string) => {
    if (removing.value[assetId]) return;
    const idx = favorites.value.findIndex((f) => f.asset_id === assetId);
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
    <div class="min-h-screen bg-slate-50 text-gray-700">
        <AppHeader title="Lumina • Favorites" />

        <main class="mx-auto max-w-3xl px-4 py-6">
            <template v-if="loading">
                <div class="rounded border border-slate-200 bg-white p-4 text-gray-600">Loading favorites…</div>
            </template>
            <template v-else-if="error">
                <div class="rounded border border-red-200 bg-red-50 p-4 text-red-800">{{ error }}</div>
            </template>

            <template v-else-if="hasData">
                <div class="space-y-3">
                    <div v-for="f in favorites" :key="f.asset_id" class="relative flex overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                        <a :href="`/assets/${f.asset_id}`" class="text-left">
                            <div class="font-medium capitalize">{{ f.asset_id }}</div>
                            <div class="text-xs text-gray-600">Click to view details</div>
                        </a>
                        <button
                            class="ml-auto rounded border border-indigo-800 px-3 py-1 text-sm text-indigo-800"
                            :disabled="removing[f.asset_id]"
                            @click="unfavorite(f.asset_id)"
                        >
                            {{ removing[f.asset_id] ? 'Removing…' : 'Unfavorite' }}
                        </button>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="rounded border border-slate-200 bg-white p-4 text-gray-600">No favorites yet. Go to the Assets page and add some!</div>
            </template>
        </main>
    </div>
</template>
