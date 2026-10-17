<script setup lang="ts">
defineProps<{
    title: string;
    eyebrow?: string;
    description?: string | null;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="flex min-w-0 flex-col gap-1">
            <span v-if="eyebrow" class="eyebrow">{{ eyebrow }}</span>
            <h1
                class="font-display text-4xl font-extrabold tracking-tight break-words uppercase sm:text-5xl"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="max-w-prose text-muted-foreground">
                {{ description }}
            </p>
            <slot name="meta" />
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
