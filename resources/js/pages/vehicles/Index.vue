<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Plus } from '@lucide/vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Pill from '@/components/tarmac/Pill.vue';
import { Button } from '@/components/ui/button';
import { scoreTone } from '@/lib/format';
import { create, index, show } from '@/routes/vehicles';
import type { VehicleSummary } from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mes véhicules', href: index() }],
    },
});

defineProps<{
    vehicles: (VehicleSummary & {
        runs_count: number;
        trips_count: number;
        active_dtc_count: number;
        health_score: number | null;
    })[];
    maxVehicles: number | null;
}>();
</script>

<template>
    <Head title="Mes véhicules" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Mes véhicules"
            :description="
                maxVehicles === null
                    ? 'Véhicules illimités avec ton offre.'
                    : `${vehicles.length} / ${maxVehicles} véhicule(s) avec ton offre.`
            "
        >
            <template #actions>
                <Button as-child>
                    <Link :href="create()"><Plus /> Ajouter un véhicule</Link>
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="vehicles.length === 0"
            title="Aucun véhicule"
            description="Ajoute ta voiture ici ou depuis l'app : elle se synchronise dans les deux sens."
        />

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="vehicle in vehicles"
                :key="vehicle.uuid"
                :href="show(vehicle.uuid)"
                class="group flex flex-col gap-4 rounded-[20px] border bg-card p-5 transition hover:border-input"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate font-display text-2xl font-bold">
                            {{ vehicle.name }}
                        </h2>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ vehicle.make }} {{ vehicle.model }}
                            <template v-if="vehicle.year">
                                · {{ vehicle.year }}</template
                            >
                            <template v-if="vehicle.engine">
                                · {{ vehicle.engine }}</template
                            >
                        </p>
                    </div>
                    <Pill>{{ vehicle.tuning_label }}</Pill>
                </div>
                <dl class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-background p-2">
                        <dt class="eyebrow">Santé</dt>
                        <dd
                            class="num text-2xl font-bold"
                            :class="scoreTone(vehicle.health_score)"
                        >
                            {{ vehicle.health_score ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-background p-2">
                        <dt class="eyebrow">Tirs</dt>
                        <dd class="num text-2xl font-bold">
                            {{ vehicle.runs_count }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-background p-2">
                        <dt class="eyebrow">Codes</dt>
                        <dd
                            class="num text-2xl font-bold"
                            :class="
                                vehicle.active_dtc_count > 0
                                    ? 'text-warn-text'
                                    : ''
                            "
                        >
                            {{ vehicle.active_dtc_count }}
                        </dd>
                    </div>
                </dl>
                <span
                    class="inline-flex items-center gap-1 text-sm font-semibold text-muted-foreground group-hover:text-foreground"
                >
                    Voir le détail <ArrowRight class="size-4" />
                </span>
            </Link>
        </div>
    </div>
</template>
