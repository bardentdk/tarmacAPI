<script setup lang="ts">
import { Trophy } from '@lucide/vue';
import Pill from '@/components/tarmac/Pill.vue';
import { formatNumber } from '@/lib/format';
import type { RunDetail } from '@/types/tarmac';

defineProps<{
    run: RunDetail;
    showPower: boolean;
}>();
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-[2fr_3fr]">
        <div
            class="flex flex-col justify-between gap-4 rounded-[22px] border bg-card p-6"
        >
            <div class="flex items-center justify-between gap-2">
                <span class="eyebrow">{{ run.mode_label }}</span>
                <Pill v-if="run.is_record" tone="ok"
                    ><Trophy class="size-3" /> Record perso</Pill
                >
            </div>
            <div class="num leading-none font-extrabold">
                <span class="text-7xl sm:text-8xl">{{
                    run.result_s !== null ? formatNumber(run.result_s, 2) : '—'
                }}</span>
                <span class="ml-2 text-3xl text-muted-foreground">s</span>
            </div>
            <p v-if="run.accuracy_s" class="text-sm text-muted-foreground">
                Précision ±{{ formatNumber(run.accuracy_s, 2) }} s
                <template v-if="run.source_label">
                    · {{ run.source_label }}</template
                >
            </p>
        </div>

        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-[20px] border bg-card p-4">
                <dt class="eyebrow">Vitesse de sortie</dt>
                <dd class="num text-3xl font-bold">
                    {{ formatNumber(run.trap_kmh, 1)
                    }}<span class="ml-1 text-base text-muted-foreground"
                        >km/h</span
                    >
                </dd>
            </div>
            <div class="rounded-[20px] border bg-card p-4">
                <dt class="eyebrow">Distance</dt>
                <dd class="num text-3xl font-bold">
                    {{ formatNumber(run.distance_m, 1)
                    }}<span class="ml-1 text-base text-muted-foreground"
                        >m</span
                    >
                </dd>
            </div>
            <div class="rounded-[20px] border bg-card p-4">
                <dt class="eyebrow">60 pieds</dt>
                <dd class="num text-3xl font-bold">
                    {{ formatNumber(run.sixty_ft_s, 2)
                    }}<span class="ml-1 text-base text-muted-foreground"
                        >s</span
                    >
                </dd>
            </div>
            <div class="rounded-[20px] border bg-card p-4">
                <dt class="eyebrow">Puissance estimée</dt>
                <dd class="num text-3xl font-bold">
                    <template v-if="showPower"
                        >{{ formatNumber(run.power_hp)
                        }}<span class="ml-1 text-base text-muted-foreground"
                            >ch</span
                        ></template
                    >
                    <span v-else class="text-base text-muted-foreground"
                        >Offre Pro</span
                    >
                </dd>
            </div>
            <div class="rounded-[20px] border bg-card p-4">
                <dt class="eyebrow">Couple max</dt>
                <dd class="num text-3xl font-bold">
                    <template v-if="showPower"
                        >{{ formatNumber(run.torque_nm)
                        }}<span class="ml-1 text-base text-muted-foreground"
                            >N·m</span
                        ></template
                    >
                    <span v-else class="text-base text-muted-foreground"
                        >Offre Pro</span
                    >
                </dd>
            </div>
            <div class="rounded-[20px] border bg-card p-4">
                <dt class="eyebrow">Régime au pic</dt>
                <dd class="num text-3xl font-bold">
                    {{ formatNumber(run.peak_rpm)
                    }}<span class="ml-1 text-base text-muted-foreground"
                        >tr/min</span
                    >
                </dd>
            </div>
        </dl>
    </div>
</template>
