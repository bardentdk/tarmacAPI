<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background px-4 py-10 md:p-10"
    >
        <div class="w-full max-w-md">
            <div
                class="flex flex-col gap-8 rounded-[22px] border bg-card p-6 sm:p-8"
            >
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex items-center gap-3 font-medium"
                    >
                        <AppLogoIcon class="size-11" />
                        <span
                            class="font-display text-2xl font-extrabold tracking-[0.08em] uppercase"
                            >Tarmac</span
                        >
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1
                            class="font-display text-3xl font-bold tracking-tight"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-center text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
            <p class="mt-6 text-center text-sm text-muted-foreground">
                Ton chrono. Ton coach. Ton moteur.
            </p>
        </div>
    </div>
</template>
