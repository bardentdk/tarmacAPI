<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <section
        class="flex min-w-0 flex-col gap-4 rounded-[20px] border bg-card p-5"
    >
        <div
            v-if="title || $slots.action"
            class="flex items-start justify-between gap-4"
        >
            <div class="min-w-0">
                <h2 class="font-display text-2xl font-bold">{{ title }}</h2>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </div>
        <slot />
    </section>
</template>
