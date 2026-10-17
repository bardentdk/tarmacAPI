<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { index as diagnosticsIndex } from '@/routes/diagnostics';
import { index as healthIndex } from '@/routes/health';

defineProps<{
    active: 'codes' | 'health';
}>();
</script>

<template>
    <nav class="flex gap-2" aria-label="Diagnostics">
        <Link
            :href="diagnosticsIndex()"
            class="inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold"
            :class="
                active === 'codes'
                    ? 'border-foreground bg-foreground text-background'
                    : 'border-input hover:bg-accent'
            "
            >Codes défauts</Link
        >
        <Link
            :href="healthIndex()"
            class="inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold"
            :class="
                active === 'health'
                    ? 'border-foreground bg-foreground text-background'
                    : 'border-input hover:bg-accent'
            "
            >Santé</Link
        >
    </nav>
</template>
