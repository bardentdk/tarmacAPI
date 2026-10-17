<script setup lang="ts">
import { CircleAlert, CircleCheck, OctagonAlert } from '@lucide/vue';
import { computed } from 'vue';
import type { Urgency } from '@/types/tarmac';

const props = defineProps<{
    urgency: Urgency | null;
    label?: string | null;
}>();

const config = computed(() => {
    switch (props.urgency) {
        case 'ok':
            return {
                icon: CircleCheck,
                text: 'Tu peux rouler',
                classes: 'border-ok-border bg-ok-bg text-ok',
            };
        case 'stop':
            return {
                icon: OctagonAlert,
                text: 'Arrête-toi',
                classes: 'border-danger-border bg-danger-bg text-danger',
            };
        case 'caution':
            return {
                icon: CircleAlert,
                text: 'Roule prudemment',
                classes: 'border-warn/40 bg-warn-bg text-warn-text',
            };
        default:
            return {
                icon: CircleAlert,
                text: 'À identifier',
                classes: 'border-border bg-muted text-muted-foreground',
            };
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold whitespace-nowrap"
        :class="config.classes"
    >
        <component :is="config.icon" class="size-3.5" aria-hidden="true" />
        {{ label ?? config.text }}
    </span>
</template>
