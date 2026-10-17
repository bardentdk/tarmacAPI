<script setup lang="ts">
import { computed } from 'vue';
import LineChart from '@/components/tarmac/LineChart.vue';
import { formatNumber } from '@/lib/format';
import type { CurvePoint } from '@/types/tarmac';

const props = defineProps<{
    curve: CurvePoint[];
}>();

const powerPoints = computed(() =>
    props.curve.map((point) => ({
        x: point.rpm,
        y: point.hp,
        label: `${formatNumber(point.rpm)} tr/min`,
    })),
);
const torquePoints = computed(() =>
    props.curve.map((point) => ({
        x: point.rpm,
        y: point.nm,
        label: `${formatNumber(point.rpm)} tr/min`,
    })),
);

const rpmTick = (value: number): string => formatNumber(value);
const whole = (value: number): string => formatNumber(value);
</script>

<template>
    <div class="grid gap-6">
        <div>
            <h3 class="eyebrow mb-2">Puissance (ch) selon le régime</h3>
            <LineChart
                :points="powerPoints"
                title="Courbe de puissance en fonction du régime"
                unit="ch"
                color="var(--foreground)"
                :x-format="rpmTick"
                :y-format="whole"
                :height="200"
            />
        </div>
        <div>
            <h3 class="eyebrow mb-2">Couple (N·m) selon le régime</h3>
            <LineChart
                :points="torquePoints"
                title="Courbe de couple en fonction du régime"
                unit="N·m"
                color="var(--info)"
                :x-format="rpmTick"
                :y-format="whole"
                :height="200"
            />
        </div>
    </div>
</template>
