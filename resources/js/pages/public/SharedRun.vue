<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Panel from '@/components/tarmac/Panel.vue';
import PowerTorqueChart from '@/components/tarmac/PowerTorqueChart.vue';
import RunConditions from '@/components/tarmac/RunConditions.vue';
import RunResultHero from '@/components/tarmac/RunResultHero.vue';
import SplitsTable from '@/components/tarmac/SplitsTable.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { home } from '@/routes';
import type { RunDetail } from '@/types/tarmac';

defineProps<{
    run: RunDetail;
    driver: string;
}>();
</script>

<template>
    <Head :title="`${run.mode_label} de ${driver}`">
        <meta name="robots" content="noindex" />
    </Head>

    <div class="min-h-svh bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3"
            >
                <Link :href="home()" class="flex items-center gap-3">
                    <AppLogoIcon class="size-9" />
                    <span
                        class="font-display text-xl font-extrabold tracking-[0.08em] uppercase"
                        >Tarmac</span
                    >
                </Link>
                <span class="eyebrow">Partage · lecture seule</span>
            </div>
        </header>

        <main class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-8">
            <div class="flex flex-col gap-1">
                <span class="eyebrow">{{
                    formatDateTime(run.recorded_at)
                }}</span>
                <h1
                    class="font-display text-4xl font-extrabold uppercase sm:text-5xl"
                >
                    {{ run.mode_label }} de {{ driver }}
                </h1>
                <p v-if="run.vehicle" class="text-muted-foreground">
                    {{ run.vehicle.make }} {{ run.vehicle.model }}
                    <template v-if="run.vehicle.engine">
                        · {{ run.vehicle.engine }}</template
                    >
                    · {{ run.vehicle.tuning_label }}
                </p>
            </div>

            <RunResultHero :run="run" :show-power="true" />

            <div class="grid gap-6 lg:grid-cols-2">
                <Panel title="Temps intermédiaires">
                    <SplitsTable :splits="run.splits" />
                </Panel>
                <Panel title="Conditions">
                    <RunConditions :conditions="run.conditions" />
                </Panel>
            </div>

            <Panel
                v-if="run.curve.length > 0"
                title="Puissance et couple estimés"
            >
                <PowerTorqueChart :curve="run.curve" />
            </Panel>

            <div
                class="flex flex-col items-start gap-4 rounded-[22px] border bg-card p-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-display text-2xl font-bold">
                        Les autres mesurent. Tarmac mesure, explique et protège.
                    </p>
                    <p class="text-muted-foreground">
                        Chrono, puissance estimée et santé moteur dans ta poche.
                    </p>
                </div>
                <Button size="lg" as-child>
                    <Link :href="home()">Découvrir Tarmac</Link>
                </Button>
            </div>

            <p class="text-sm text-muted-foreground">
                Mesures réalisées sur circuit / piste privée. Les tirs et
                mesures de performance sont réservés aux circuits et pistes
                privées.
            </p>
        </main>
    </div>
</template>
