<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DiagnosticsTabs from '@/components/tarmac/DiagnosticsTabs.vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import LineChart from '@/components/tarmac/LineChart.vue';
import NativeSelect from '@/components/tarmac/NativeSelect.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Panel from '@/components/tarmac/Panel.vue';
import StatTile from '@/components/tarmac/StatTile.vue';
import { Label } from '@/components/ui/label';
import {
    componentLabels,
    formatDate,
    formatNumber,
    formatShortDate,
    scoreTone,
} from '@/lib/format';
import { index } from '@/routes/health';
import type { Option, TripSummary, VehicleSummary } from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Santé', href: index() }],
    },
});

const props = defineProps<{
    vehicles: VehicleSummary[];
    vehicle: VehicleSummary | null;
    trips: TripSummary[];
    latest: TripSummary | null;
    averageScore: number | null;
    activeDtcCount: number;
}>();

const selectedVehicle = ref<string | null>(props.vehicle?.uuid ?? null);

const vehicleOptions = computed<Option[]>(() =>
    props.vehicles.map((item) => ({ value: item.uuid, label: item.name })),
);

watch(selectedVehicle, () => {
    router.get(
        index.url({
            query: selectedVehicle.value
                ? { vehicle: selectedVehicle.value }
                : {},
        }),
        {},
        { preserveState: true, replace: true },
    );
});

const trendPoints = computed(() =>
    props.trips.map((trip, position) => ({
        x: position,
        y: trip.health_score,
        label: formatDate(trip.started_at),
    })),
);

const components = computed(() =>
    Object.entries(props.latest?.components ?? {}).map(([key, value]) => ({
        key,
        label: componentLabels[key] ?? key,
        value: Number(value),
    })),
);

function componentVerdict(value: number): { text: string; tone: string } {
    if (value >= 0.9) {
        return { text: 'OK', tone: 'text-ok' };
    }

    if (value >= 0.6) {
        return { text: 'À surveiller', tone: 'text-warn-text' };
    }

    return { text: 'À contrôler', tone: 'text-danger' };
}

const reversedTrips = computed(() => [...props.trips].reverse().slice(0, 20));
</script>

<template>
    <Head title="Santé" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Santé"
            description="Ça va ? Un score sur 100 calculé à chaque trajet : codes, ratés, mélange, refroidissement, électricité."
        >
            <template #actions>
                <DiagnosticsTabs active="health" />
            </template>
        </PageHeader>

        <div v-if="vehicles.length > 1" class="grid gap-1.5 sm:max-w-sm">
            <Label for="health-vehicle">Véhicule</Label>
            <NativeSelect
                id="health-vehicle"
                v-model="selectedVehicle"
                :options="vehicleOptions"
            />
        </div>

        <EmptyState
            v-if="trips.length === 0"
            title="Pas encore de trajet analysé"
            description="Roule avec le boîtier branché : chaque trajet donne un score santé."
        />

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-3">
                <StatTile
                    label="Dernier score"
                    :value="String(latest?.health_score ?? '—')"
                    unit="/100"
                    :tone="scoreTone(latest?.health_score)"
                    :hint="latest ? formatDate(latest.started_at) : null"
                />
                <StatTile
                    label="Moyenne"
                    :value="String(averageScore ?? '—')"
                    unit="/100"
                    :hint="`Sur ${trips.length} trajet(s)`"
                />
                <StatTile
                    label="Codes actifs"
                    :value="String(activeDtcCount)"
                    :tone="activeDtcCount > 0 ? 'text-warn-text' : 'text-ok'"
                    :hint="activeDtcCount > 0 ? 'Voir Diagnostics' : 'Aucun'"
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-[3fr_2fr]">
                <Panel title="Évolution du score">
                    <LineChart
                        :points="trendPoints"
                        title="Score santé par trajet"
                        unit="/100"
                        :y-min="0"
                        :y-max="100"
                        :height="260"
                        :x-format="
                            (value: number) =>
                                formatShortDate(trips[value]?.started_at)
                        "
                    />
                </Panel>

                <Panel
                    title="Dernier trajet"
                    description="Chaque composante, de 0 à 100 %."
                >
                    <ul class="flex flex-col gap-3">
                        <li
                            v-for="component in components"
                            :key="component.key"
                            class="flex flex-col gap-1"
                        >
                            <div
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span>{{ component.label }}</span>
                                <span
                                    class="font-semibold"
                                    :class="
                                        componentVerdict(component.value).tone
                                    "
                                >
                                    {{ formatNumber(component.value * 100) }} %
                                    ·
                                    {{ componentVerdict(component.value).text }}
                                </span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                                role="presentation"
                            >
                                <div
                                    class="h-full rounded-full bg-foreground/70"
                                    :style="{
                                        width: `${Math.round(component.value * 100)}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ul>
                </Panel>
            </div>

            <Panel title="Trajets">
                <div class="-mx-5 overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead>
                            <tr class="border-b text-muted-foreground">
                                <th class="px-5 py-2 font-semibold">Date</th>
                                <th class="px-3 py-2 font-semibold">
                                    Distance
                                </th>
                                <th class="px-3 py-2 font-semibold">Eau max</th>
                                <th class="px-3 py-2 font-semibold">
                                    Batterie
                                </th>
                                <th class="px-5 py-2 text-right font-semibold">
                                    Score
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="trip in reversedTrips"
                                :key="trip.uuid"
                                class="border-b last:border-0"
                            >
                                <td class="px-5 py-2.5 whitespace-nowrap">
                                    {{ formatDate(trip.started_at) }}
                                </td>
                                <td class="num px-3 py-2.5">
                                    {{ formatNumber(trip.distance_km, 1) }} km
                                </td>
                                <td class="num px-3 py-2.5">
                                    {{
                                        trip.metrics.max_ect
                                            ? `${formatNumber(trip.metrics.max_ect)} °C`
                                            : '—'
                                    }}
                                </td>
                                <td class="num px-3 py-2.5">
                                    {{
                                        trip.metrics.battery_v
                                            ? `${formatNumber(trip.metrics.battery_v, 1)} V`
                                            : '—'
                                    }}
                                </td>
                                <td
                                    class="num px-5 py-2.5 text-right text-lg font-bold"
                                    :class="scoreTone(trip.health_score)"
                                >
                                    {{ trip.health_score }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Panel>
        </template>
    </div>
</template>
