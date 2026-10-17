<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    prev: string | null;
    next: string | null;
    current: number;
    last: number;
}>();
</script>

<template>
    <nav
        v-if="last > 1"
        class="flex items-center justify-between gap-4"
        aria-label="Pagination"
    >
        <Link
            v-if="prev"
            :href="prev"
            preserve-scroll
            class="inline-flex h-11 items-center rounded-2xl border border-input px-4 font-semibold hover:bg-accent"
            >Précédent</Link
        >
        <span v-else />
        <span class="num text-sm text-muted-foreground"
            >Page {{ current }} / {{ last }}</span
        >
        <Link
            v-if="next"
            :href="next"
            preserve-scroll
            class="inline-flex h-11 items-center rounded-2xl border border-input px-4 font-semibold hover:bg-accent"
            >Suivant</Link
        >
        <span v-else />
    </nav>
</template>
