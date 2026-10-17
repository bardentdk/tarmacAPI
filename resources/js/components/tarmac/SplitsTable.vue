<script setup lang="ts">
import { formatNumber, formatSeconds } from '@/lib/format';
import type { Split } from '@/types/tarmac';

defineProps<{
    splits: Split[];
}>();
</script>

<template>
    <table v-if="splits.length > 0" class="w-full text-left text-sm">
        <thead>
            <tr class="border-b text-muted-foreground">
                <th class="py-2 font-semibold">Passage</th>
                <th class="py-2 text-right font-semibold">Temps</th>
                <th class="py-2 text-right font-semibold">Vitesse</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="split in splits"
                :key="split.label"
                class="border-b last:border-0"
            >
                <td class="py-2.5">{{ split.label }}</td>
                <td class="num py-2.5 text-right text-lg font-bold">
                    {{ formatSeconds(split.t_s) }}
                </td>
                <td class="num py-2.5 text-right text-muted-foreground">
                    {{
                        split.speed_kmh !== null &&
                        split.speed_kmh !== undefined
                            ? `${formatNumber(split.speed_kmh)} km/h`
                            : '—'
                    }}
                </td>
            </tr>
        </tbody>
    </table>
    <p v-else class="text-sm text-muted-foreground">
        Pas de temps intermédiaires pour ce tir.
    </p>
</template>
