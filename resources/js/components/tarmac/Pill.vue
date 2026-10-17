<script setup lang="ts">
defineProps<{
    tone?: 'neutral' | 'ok' | 'warn' | 'info';
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold whitespace-nowrap"
        :class="{
            'bg-secondary text-secondary-foreground':
                !tone || tone === 'neutral',
            'bg-ok-bg text-ok': tone === 'ok',
            'bg-warn-bg text-warn-text': tone === 'warn',
            'bg-info/15 text-info': tone === 'info',
        }"
    >
        <slot />
    </span>
</template>
