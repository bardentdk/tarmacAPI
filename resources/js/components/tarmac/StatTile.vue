<script setup lang="ts">
defineProps<{
    label: string;
    value: string;
    unit?: string;
    hint?: string | null;
    tone?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5 rounded-[20px] border bg-card p-5">
        <span class="eyebrow">{{ label }}</span>
        <span
            class="num text-4xl leading-none font-bold"
            :class="tone ?? 'text-foreground'"
        >
            {{ value
            }}<span
                v-if="unit"
                class="ml-1 text-xl font-semibold text-muted-foreground"
                >{{ unit }}</span
            >
        </span>
        <span v-if="hint" class="text-sm text-muted-foreground">{{
            hint
        }}</span>
        <slot />
    </div>
</template>
