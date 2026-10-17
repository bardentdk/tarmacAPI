<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Trophy } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import NativeSelect from '@/components/tarmac/NativeSelect.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Pagination from '@/components/tarmac/Pagination.vue';
import Pill from '@/components/tarmac/Pill.vue';
import { Label } from '@/components/ui/label';
import { formatDate, formatNumber, formatSeconds } from '@/lib/format';
import { index, show } from '@/routes/runs';
import type {
    Option,
    Paginated,
    RunSummary,
    VehicleSummary,
} from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tirs et rapports', href: index() }],
    },
});

const props = defineProps<{
    runs: Paginated<RunSummary>;
    filters: { vehicle: string | null; mode: string | null };
    vehicles: VehicleSummary[];
    modes: Option[];
}>();

const vehicle = ref<string | null>(props.filters.vehicle);
const mode = ref<string | null>(props.filters.mode);

const vehicleOptions = computed<Option[]>(() =>
    props.vehicles.map((item) => ({ value: item.uuid, label: item.name })),
);

watch([vehicle, mode], () => {
    const query: Record<string, string> = {};

    if (vehicle.value) {
        query.vehicle = vehicle.value;
    }

    if (mode.value) {
        query.mode = mode.value;
    }

    router.get(
        index.url({ query }),
        {},
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Tirs et rapports" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Tirs et rapports"
            :description="`${runs.total} tir(s) synchronisé(s). Ouvre un rapport pour comprendre chaque dixième.`"
        />

        <div class="grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
            <div class="grid gap-1.5">
                <Label for="filter-vehicle">Véhicule</Label>
                <NativeSelect
                    id="filter-vehicle"
                    v-model="vehicle"
                    :options="vehicleOptions"
                    placeholder="Tous les véhicules"
                />
            </div>
            <div class="grid gap-1.5">
                <Label for="filter-mode">Mode</Label>
                <NativeSelect
                    id="filter-mode"
                    v-model="mode"
                    :options="modes"
                    placeholder="Tous les modes"
                />
            </div>
        </div>

        <EmptyState
            v-if="runs.data.length === 0"
            title="Aucun tir ne correspond"
            description="Change les filtres, ou lance un run depuis l'app sur circuit ou piste privée."
        />

        <div v-else class="overflow-x-auto rounded-[20px] border bg-card">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b text-muted-foreground">
                        <th class="px-5 py-3 font-semibold">Date</th>
                        <th class="px-3 py-3 font-semibold">Véhicule</th>
                        <th class="px-3 py-3 font-semibold">Mode</th>
                        <th class="px-3 py-3 font-semibold">Résultat</th>
                        <th class="px-3 py-3 font-semibold">Puissance</th>
                        <th class="px-3 py-3 font-semibold">Conditions</th>
                        <th class="px-5 py-3 text-right font-semibold">
                            Rapport
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="run in runs.data"
                        :key="run.uuid"
                        class="border-b last:border-0"
                    >
                        <td class="px-5 py-3 whitespace-nowrap">
                            {{ formatDate(run.recorded_at) }}
                        </td>
                        <td class="px-3 py-3">{{ run.vehicle?.name }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            {{ run.mode_label }}
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="num text-lg font-bold">{{
                                formatSeconds(run.result_s)
                            }}</span>
                            <Pill v-if="run.is_record" tone="ok" class="ml-2">
                                <Trophy class="size-3" /> Record
                            </Pill>
                        </td>
                        <td class="num px-3 py-3 whitespace-nowrap">
                            {{
                                run.power_hp
                                    ? `${formatNumber(run.power_hp)} ch`
                                    : '—'
                            }}
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">
                            {{ run.conditions_summary ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link
                                :href="show(run.uuid)"
                                class="inline-flex min-h-11 items-center gap-1 font-semibold hover:text-primary"
                            >
                                Ouvrir <ArrowRight class="size-4" />
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            :prev="runs.prev_page_url"
            :next="runs.next_page_url"
            :current="runs.current_page"
            :last="runs.last_page"
        />
    </div>
</template>
