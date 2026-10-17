<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DiagnosticsTabs from '@/components/tarmac/DiagnosticsTabs.vue';
import DtcExplanationCard from '@/components/tarmac/DtcExplanationCard.vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import NativeSelect from '@/components/tarmac/NativeSelect.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Pagination from '@/components/tarmac/Pagination.vue';
import Pill from '@/components/tarmac/Pill.vue';
import UrgencyBadge from '@/components/tarmac/UrgencyBadge.vue';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { index } from '@/routes/diagnostics';
import type {
    DtcExplanation,
    DtcSummary,
    Option,
    Paginated,
    VehicleSummary,
} from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Diagnostics', href: index() }],
    },
});

const props = defineProps<{
    records: Paginated<DtcSummary>;
    activeCount: number;
    filters: { vehicle: string | null; code: string | null };
    vehicles: VehicleSummary[];
    selected: DtcExplanation | null;
}>();

const vehicle = ref<string | null>(props.filters.vehicle);

const vehicleOptions = computed<Option[]>(() =>
    props.vehicles.map((item) => ({ value: item.uuid, label: item.name })),
);

watch(vehicle, () => {
    router.get(
        index.url({ query: vehicle.value ? { vehicle: vehicle.value } : {} }),
        {},
        { preserveState: true, replace: true },
    );
});

function codeLink(code: string) {
    const query: Record<string, string> = { code };

    if (vehicle.value) {
        query.vehicle = vehicle.value;
    }

    return index({ query });
}
</script>

<template>
    <Head title="Diagnostics" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Diagnostics"
            :description="
                activeCount === 0
                    ? 'Aucun code actif. Ton moteur est au calme.'
                    : activeCount === 1
                      ? '1 code actif à regarder.'
                      : `${activeCount} codes actifs à regarder.`
            "
        >
            <template #actions>
                <DiagnosticsTabs active="codes" />
            </template>
        </PageHeader>

        <DtcExplanationCard v-if="selected" :explanation="selected" />

        <div class="grid gap-1.5 sm:max-w-sm">
            <Label for="filter-vehicle">Véhicule</Label>
            <NativeSelect
                id="filter-vehicle"
                v-model="vehicle"
                :options="vehicleOptions"
                placeholder="Tous les véhicules"
            />
        </div>

        <EmptyState
            v-if="records.data.length === 0"
            title="Aucun code défaut"
            description="Les codes lus avec l'app apparaissent ici, avec leur explication."
        />

        <div v-else class="overflow-x-auto rounded-[20px] border bg-card">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b text-muted-foreground">
                        <th class="px-5 py-3 font-semibold">Code</th>
                        <th class="px-3 py-3 font-semibold">Défaut</th>
                        <th class="px-3 py-3 font-semibold">Type</th>
                        <th class="px-3 py-3 font-semibold">Véhicule</th>
                        <th class="px-3 py-3 font-semibold">Lu le</th>
                        <th class="px-5 py-3 font-semibold">État</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="record in records.data"
                        :key="record.uuid"
                        class="border-b last:border-0"
                        :class="
                            filters.code === record.code ? 'bg-accent/50' : ''
                        "
                    >
                        <td class="px-5 py-3">
                            <Link
                                :href="codeLink(record.code)"
                                preserve-scroll
                                class="num inline-flex min-h-11 items-center text-lg font-bold hover:text-primary"
                                >{{ record.code }}</Link
                            >
                        </td>
                        <td class="px-3 py-3">
                            {{ record.title ?? 'Explication à venir' }}
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            {{ record.kind_label }}
                        </td>
                        <td class="px-3 py-3">{{ record.vehicle?.name }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            {{ formatDate(record.read_at) }}
                        </td>
                        <td class="px-5 py-3">
                            <Pill v-if="record.cleared_at"
                                >Effacé le
                                {{ formatDate(record.cleared_at) }}</Pill
                            >
                            <UrgencyBadge v-else :urgency="record.urgency" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            :prev="records.prev_page_url"
            :next="records.next_page_url"
            :current="records.current_page"
            :last="records.last_page"
        />
    </div>
</template>
