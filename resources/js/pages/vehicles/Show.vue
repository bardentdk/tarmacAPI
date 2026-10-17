<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowRight, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import LineChart from '@/components/tarmac/LineChart.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Panel from '@/components/tarmac/Panel.vue';
import Pill from '@/components/tarmac/Pill.vue';
import StatTile from '@/components/tarmac/StatTile.vue';
import UrgencyBadge from '@/components/tarmac/UrgencyBadge.vue';
import {
    formatDate,
    formatNumber,
    formatSeconds,
    formatShortDate,
    scoreTone,
} from '@/lib/format';
import { index as diagnosticsIndex } from '@/routes/diagnostics';
import { index as healthIndex } from '@/routes/health';
import { index as runsIndex, show as runShow } from '@/routes/runs';
import { index, show } from '@/routes/vehicles';
import type {
    DtcSummary,
    RunSummary,
    TripSummary,
    VehicleSummary,
} from '@/types/tarmac';

const props = defineProps<{
    vehicle: VehicleSummary & {
        fuel: string | null;
        gearbox: string | null;
        drivetrain: string | null;
        mass_kg: number;
        tire_size: string | null;
        redline_rpm: number;
        k_cal: number;
    };
    runs: RunSummary[];
    dtcRecords: DtcSummary[];
    trips: TripSummary[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Mes véhicules', href: index() },
        { title: props.vehicle.name, href: show(props.vehicle.uuid) },
    ],
});

const latestTrip = computed(() => props.trips[props.trips.length - 1] ?? null);
const activeDtcs = computed(() =>
    props.dtcRecords.filter((record) => !record.cleared_at),
);
const trendPoints = computed(() =>
    props.trips.map((trip, position) => ({
        x: position,
        y: trip.health_score,
        label: formatDate(trip.started_at),
    })),
);
</script>

<template>
    <Head :title="vehicle.name" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="vehicle.name"
            :eyebrow="`${vehicle.make} ${vehicle.model}${vehicle.year ? ' · ' + vehicle.year : ''}`"
        >
            <template #meta>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Pill>{{ vehicle.tuning_label }}</Pill>
                    <Pill v-if="vehicle.engine">{{ vehicle.engine }}</Pill>
                    <Pill v-if="vehicle.gearbox">{{ vehicle.gearbox }}</Pill>
                    <Pill v-if="vehicle.drivetrain">{{
                        vehicle.drivetrain
                    }}</Pill>
                    <Pill v-if="vehicle.fuel">{{ vehicle.fuel }}</Pill>
                </div>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                label="Score santé"
                :value="latestTrip ? String(latestTrip.health_score) : '—'"
                unit="/100"
                :tone="scoreTone(latestTrip?.health_score)"
                :hint="
                    latestTrip
                        ? `Dernier trajet le ${formatDate(latestTrip.started_at)}`
                        : 'Pas encore de trajet'
                "
            />
            <StatTile
                label="Codes actifs"
                :value="String(activeDtcs.length)"
                :tone="activeDtcs.length > 0 ? 'text-warn-text' : 'text-ok'"
                :hint="activeDtcs.length > 0 ? 'À regarder' : 'Rien à signaler'"
            />
            <StatTile
                label="Masse"
                :value="formatNumber(vehicle.mass_kg)"
                unit="kg"
                :hint="vehicle.tire_size ? `Pneus ${vehicle.tire_size}` : null"
            />
            <StatTile
                label="Zone rouge"
                :value="formatNumber(vehicle.redline_rpm)"
                unit="tr/min"
                :hint="`Calibration ×${formatNumber(vehicle.k_cal, 2)}`"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Panel title="Tirs" description="Les 20 derniers">
                <template #action>
                    <Link
                        :href="runsIndex({ query: { vehicle: vehicle.uuid } })"
                        class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted-foreground hover:text-foreground"
                        >Tout voir <ArrowRight class="size-4"
                    /></Link>
                </template>
                <EmptyState
                    v-if="runs.length === 0"
                    title="Aucun tir"
                    description="Lance un run depuis l'app."
                />
                <ul v-else class="flex flex-col">
                    <li
                        v-for="run in runs"
                        :key="run.uuid"
                        class="border-b last:border-0"
                    >
                        <Link
                            :href="runShow(run.uuid)"
                            class="flex min-h-12 items-center justify-between gap-3 py-2 hover:text-primary"
                        >
                            <span class="flex flex-col">
                                <span class="font-semibold">{{
                                    run.mode_label
                                }}</span>
                                <span class="text-xs text-muted-foreground">{{
                                    formatDate(run.recorded_at)
                                }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <Pill v-if="run.is_record" tone="ok"
                                    ><Trophy class="size-3" /> Record</Pill
                                >
                                <span class="num text-xl font-bold">{{
                                    formatSeconds(run.result_s)
                                }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </Panel>

            <div class="flex flex-col gap-6">
                <Panel title="Santé" description="Score par trajet">
                    <template #action>
                        <Link
                            :href="
                                healthIndex({
                                    query: { vehicle: vehicle.uuid },
                                })
                            "
                            class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted-foreground hover:text-foreground"
                            >Détail <ArrowRight class="size-4"
                        /></Link>
                    </template>
                    <LineChart
                        :points="trendPoints"
                        title="Score santé par trajet"
                        unit="/100"
                        :y-min="0"
                        :y-max="100"
                        :height="200"
                        :x-format="
                            (value: number) =>
                                formatShortDate(trips[value]?.started_at)
                        "
                    />
                </Panel>

                <Panel title="Codes défauts">
                    <template #action>
                        <Link
                            :href="
                                diagnosticsIndex({
                                    query: { vehicle: vehicle.uuid },
                                })
                            "
                            class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted-foreground hover:text-foreground"
                            >Historique <ArrowRight class="size-4"
                        /></Link>
                    </template>
                    <p
                        v-if="dtcRecords.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Aucun code défaut lu sur cette voiture.
                    </p>
                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="dtc in dtcRecords.slice(0, 8)"
                            :key="dtc.uuid"
                            class="flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="
                                        diagnosticsIndex({
                                            query: { code: dtc.code },
                                        })
                                    "
                                    class="num text-lg font-bold hover:text-primary"
                                    >{{ dtc.code }}</Link
                                >
                                <p class="text-sm text-muted-foreground">
                                    {{ dtc.title ?? 'Explication à venir' }} ·
                                    {{ formatDate(dtc.read_at) }}
                                </p>
                            </div>
                            <UrgencyBadge
                                v-if="!dtc.cleared_at"
                                :urgency="dtc.urgency"
                            />
                            <Pill v-else>Effacé</Pill>
                        </li>
                    </ul>
                </Panel>
            </div>
        </div>
    </div>
</template>
