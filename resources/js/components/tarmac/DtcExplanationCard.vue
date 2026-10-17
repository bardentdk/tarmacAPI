<script setup lang="ts">
import { Sparkles } from '@lucide/vue';
import UrgencyBadge from '@/components/tarmac/UrgencyBadge.vue';
import { formatNumber, formatPrice } from '@/lib/format';
import type { DtcExplanation } from '@/types/tarmac';

defineProps<{
    explanation: DtcExplanation;
}>();
</script>

<template>
    <article class="flex flex-col gap-4 rounded-[20px] border bg-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <span class="eyebrow">{{ explanation.system }}</span>
                <h2 class="font-display text-3xl font-bold">
                    <span class="num">{{ explanation.code }}</span>
                    · {{ explanation.title }}
                </h2>
            </div>
            <UrgencyBadge :urgency="explanation.urgency" />
        </div>

        <p class="max-w-prose">{{ explanation.explanation }}</p>

        <p
            v-if="explanation.can_drive_note"
            class="rounded-2xl border border-info/30 bg-info/10 p-3 text-sm"
        >
            {{ explanation.can_drive_note }}
        </p>

        <div v-if="explanation.causes.length > 0">
            <h3 class="eyebrow mb-2">Causes probables</h3>
            <ol class="flex flex-col gap-2">
                <li
                    v-for="cause in explanation.causes"
                    :key="cause.label"
                    class="flex flex-col gap-1 rounded-2xl bg-background p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p class="font-semibold">{{ cause.label }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ cause.part }}
                            <template v-if="cause.difficulty">
                                · réparation {{ cause.difficulty }}</template
                            >
                        </p>
                    </div>
                    <div class="flex shrink-0 items-baseline gap-3">
                        <span class="num text-lg font-bold"
                            >{{ formatNumber(cause.probability * 100) }} %</span
                        >
                        <span
                            v-if="cause.cost_max"
                            class="num text-sm text-muted-foreground"
                            >{{ formatPrice(cause.cost_min ?? 0) }} –
                            {{ formatPrice(cause.cost_max) }}</span
                        >
                    </div>
                </li>
            </ol>
        </div>

        <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
            <template v-if="explanation.source === 'ai'">
                <Sparkles class="size-3.5" /> Explication générée par
                l'assistant Tarmac. Fais confirmer par un pro avant toute
                réparation.
            </template>
            <template v-else-if="explanation.generic">
                Explication générique déduite de la structure du code.
            </template>
            <template v-else>
                Base de connaissances Tarmac. Coûts indicatifs, main-d'œuvre
                comprise.
            </template>
        </p>
    </article>
</template>
