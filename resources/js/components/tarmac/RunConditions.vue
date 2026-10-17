<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import type { RunConditions } from '@/types/tarmac';

const props = defineProps<{
    conditions: RunConditions;
}>();

const rows = computed(() => {
    const c = props.conditions;
    const items: { label: string; value: string }[] = [];
    const add = (
        label: string,
        value: number | undefined,
        unit: string,
        decimals = 0,
    ) => {
        if (value !== undefined && value !== null) {
            items.push({
                label,
                value: `${formatNumber(value, decimals)} ${unit}`.trim(),
            });
        }
    };

    add('Température extérieure', c.amb_c, '°C', 1);
    add('Pression atmosphérique', c.baro_hpa, 'hPa');
    add('Air sec', c.dry_hpa, 'hPa');
    add('Humidité', c.humidity, '%');
    add('Air admission', c.iat_c, '°C', 1);
    add('Eau moteur', c.ect_c, '°C', 1);
    add('Masse au départ', c.mass_kg, 'kg');
    add('Pente', c.slope_pct, '%', 1);
    add('Satellites', c.sats, '');
    add('Fréquence GPS', c.gps_hz, 'Hz');
    add('Carburant', c.fuel_pct, '%');

    return items;
});
</script>

<template>
    <dl v-if="rows.length > 0" class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
        <div
            v-for="row in rows"
            :key="row.label"
            class="flex items-center justify-between gap-3 border-b py-2"
        >
            <dt class="text-sm text-muted-foreground">{{ row.label }}</dt>
            <dd class="num text-lg font-semibold">{{ row.value }}</dd>
        </div>
    </dl>
    <p v-else class="text-sm text-muted-foreground">
        Pas de conditions enregistrées pour ce tir.
    </p>
</template>
