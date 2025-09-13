<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import { ArrowDownIcon, ArrowUpIcon, HeartIcon } from '@heroicons/vue/20/solid'

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

onMounted(() => {
    fetchAssets();
});

const formatted = (n: number) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n);
const pct = (n: number | null) => (n === null ? '—' : `${n.toFixed(2)}%`);

const hasData = computed(() => !loading.value && !error.value && assets.value.length > 0);

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
    <div class="min-h-screen bg-slate-50 text-gray-700">
        <AppHeader title="Lumina • Assets" />

        <main class="mx-auto max-w-5xl px-4 py-6">
            <template v-if="loading">
                <div class="p-4 rounded border border-slate-200 bg-white text-gray-600">Loading assets…</div>
            </template>
            <template v-else-if="error">
                <div class="rounded border border-red-200 bg-red-50 p-4 text-red-800">{{ error }}</div>
            </template>

            <template v-else-if="hasData">
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="a in assets"
                        :key="a.id"
                        class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-12 shadow-sm sm:px-6 sm:pt-6"
                    >
                        <a
                            :href="`/assets/${a.id}`">
                            <div class="absolute rounded-full bg-indigo-50 p-2">
                                <img :src="a.image" :alt="a.name" class="size-8 text-white" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">{{ a.symbol }} • {{ a.name }}</p>
                            <p class="ml-16 sm:pb-7 text-xl font-semibold text-gray-800">{{ formatted(a.current_price) }}</p>
                        </a>
                        <div class="">
                        </div>
                        <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6 flex justify-between items-center">
                            <p
                                :class="[
                                    (a.price_change_percentage_24h ?? 0) >= 0 ? 'text-green-600' : 'text-red-600',
                                    'ml-2 flex items-baseline text-sm font-semibold',
                                ]"
                            >
                                <ArrowUpIcon
                                    v-if="(a.price_change_percentage_24h ?? 0) >= 0"
                                    class="size-4 mr-0.5 shrink-0 self-center text-green-500"
                                    aria-hidden="true"
                                />
                                <ArrowDownIcon v-else class="size-4 mr-0.5 shrink-0 self-center text-red-500" aria-hidden="true" />
                                <span class="sr-only"> {{ (a.price_change_percentage_24h ?? 0) >= 0 ? 'Increased' : 'Decreased' }} by </span>
                                {{ pct(a.price_change_percentage_24h) }}
                            </p>
                            <button
                                class="ml-3 text-xl leading-none select-none"
                                :aria-pressed="a.is_favorite"
                                :title="a.is_favorite ? 'Unfavorite' : 'Favorite'"
                                @click.stop="toggleFavorite(a)"
                            >
                                <HeartIcon
                                    :class="[
                                    a.is_favorite ? 'text-indigo-400' : 'text-gray-300',
                                    'size-4 mr-0.5 shrink-0 self-center',
                                ]"
                                    class=""
                                    aria-hidden="true"
                                />
                            </button>
                        </div>

                    </div>
                </div>
            </template>

            <template v-else>
                <div class="p-4 rounded border border-slate-200 bg-white text-gray-600">No assets to display.</div>
            </template>
        </main>
    </div>
</template>
