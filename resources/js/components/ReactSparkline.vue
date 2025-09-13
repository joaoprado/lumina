<script setup lang="ts">
import { createElement } from 'react';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        prices: number[];
        width?: number;
        height?: number;
        strokeColor?: string;
        fillColor?: string;
    }>(),
    {
        width: 640,
        height: 96,
    },
);

const el = ref<HTMLElement | null>(null);
let root: any = null;
let Sparkline: any = null;

async function mount() {
    if (!el.value) return;
    const [{ createRoot }, mod] = await Promise.all([import('react-dom/client'), import('../react/Sparkline.jsx')]);
    Sparkline = mod.default;
    root = createRoot(el.value);
    root.render(
        createElement(Sparkline, {
            prices: props.prices,
            width: props.width,
            height: props.height,
            strokeColor: props.strokeColor,
            fillColor: props.fillColor,
        }),
    );
}

function update() {
    if (root && Sparkline && el.value) {
        root.render(
            createElement(Sparkline, {
                prices: props.prices,
                width: props.width,
                height: props.height,
                strokeColor: props.strokeColor,
                fillColor: props.fillColor,
            }),
        );
    }
}

onMounted(() => mount());
onUnmounted(() => {
    if (root) {
        root.unmount();
        root = null;
    }
});

watch(
    () => props.prices,
    () => update(),
    { deep: true },
);
</script>

<template>
    <div ref="el"></div>
</template>
