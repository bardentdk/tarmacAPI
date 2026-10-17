<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        role="img"
        aria-label="Tarmac"
        :class="className"
        v-bind="$attrs"
    >
        <rect width="48" height="48" rx="12" fill="#C6F432" />
        <path
            d="M12 14h24M24 14v22"
            stroke="#0E0F11"
            stroke-width="5"
            stroke-linecap="round"
        />
        <circle cx="36" cy="26" r="2.6" fill="#0E0F11" />
        <circle cx="36" cy="33" r="2.6" fill="#0E0F11" />
    </svg>
</template>
