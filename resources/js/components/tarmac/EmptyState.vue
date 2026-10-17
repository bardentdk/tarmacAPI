<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-2 rounded-2xl border border-dashed p-8 text-center"
    >
        <p class="font-semibold">{{ title }}</p>
        <p v-if="description" class="max-w-md text-sm text-muted-foreground">
            {{ description }}
        </p>
        <slot />
    </div>
</template>
