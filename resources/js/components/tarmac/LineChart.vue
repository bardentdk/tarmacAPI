<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';

export type ChartPoint = {
    x: number;
    y: number;
    label?: string;
};

const props = withDefaults(
    defineProps<{
        points: ChartPoint[];
        title: string;
        unit?: string;
        color?: string;
        height?: number;
        yMin?: number;
        yMax?: number;
        xFormat?: (value: number, point?: ChartPoint) => string;
        yFormat?: (value: number) => string;
        showArea?: boolean;
    }>(),
    {
        unit: '',
        color: 'var(--info)',
        height: 220,
        yMin: undefined,
        yMax: undefined,
        xFormat: (value: number) => String(value),
        yFormat: (value: number) => String(Math.round(value)),
        showArea: true,
    },
);

const container = useTemplateRef<HTMLElement>('container');
const { width } = useElementSize(container);
const hoverIndex = ref<number | null>(null);
const chartId = `chart-${Math.random().toString(36).slice(2, 9)}`;

const padding = { top: 16, right: 16, bottom: 28, left: 44 };

const plotWidth = computed(() =>
    Math.max(120, width.value - padding.left - padding.right),
);
const plotHeight = computed(() => props.height - padding.top - padding.bottom);

const sorted = computed(() => [...props.points].sort((a, b) => a.x - b.x));

const xDomain = computed<[number, number]>(() => {
    const xs = sorted.value.map((point) => point.x);

    if (xs.length === 0) {
        return [0, 1];
    }

    const min = Math.min(...xs);
    const max = Math.max(...xs);

    return min === max ? [min - 1, max + 1] : [min, max];
});

function niceStep(range: number): number {
    const rough = range / 4;
    const power = Math.pow(10, Math.floor(Math.log10(rough || 1)));
    const candidates = [1, 2, 2.5, 5, 10].map((factor) => factor * power);

    return candidates.find((candidate) => candidate >= rough) ?? rough;
}

const yDomain = computed<[number, number]>(() => {
    const ys = sorted.value.map((point) => point.y);
    let min = props.yMin ?? Math.min(...ys, 0);
    let max = props.yMax ?? Math.max(...ys, 1);

    if (props.yMin === undefined && ys.length > 0) {
        min = Math.min(...ys);
        min = min > 0 && min < max * 0.4 ? 0 : min;
    }

    if (min === max) {
        max = min + 1;
    }

    const step = niceStep(max - min);

    return [
        props.yMin ?? Math.floor(min / step) * step,
        props.yMax ?? Math.ceil(max / step) * step,
    ];
});

const yTicks = computed(() => {
    const [min, max] = yDomain.value;
    const step = niceStep(max - min);
    const ticks: number[] = [];

    for (let value = min; value <= max + step / 1000; value += step) {
        ticks.push(Number(value.toFixed(6)));
    }

    return ticks;
});

const xTicks = computed(() => {
    const points = sorted.value;
    const maxTicks = Math.max(2, Math.floor(plotWidth.value / 90));

    if (points.length <= maxTicks) {
        return points.map((point) => point.x);
    }

    const every = Math.ceil(points.length / maxTicks);

    return points
        .filter((_, index) => index % every === 0)
        .map((point) => point.x);
});

function scaleX(value: number): number {
    const [min, max] = xDomain.value;

    return padding.left + ((value - min) / (max - min)) * plotWidth.value;
}

function scaleY(value: number): number {
    const [min, max] = yDomain.value;

    return padding.top + (1 - (value - min) / (max - min)) * plotHeight.value;
}

const linePath = computed(() =>
    sorted.value
        .map(
            (point, index) =>
                `${index === 0 ? 'M' : 'L'}${scaleX(point.x).toFixed(1)},${scaleY(point.y).toFixed(1)}`,
        )
        .join(' '),
);

const areaPath = computed(() => {
    if (sorted.value.length < 2) {
        return '';
    }

    const baseline = padding.top + plotHeight.value;
    const first = sorted.value[0];
    const last = sorted.value[sorted.value.length - 1];

    return `${linePath.value} L${scaleX(last.x).toFixed(1)},${baseline} L${scaleX(first.x).toFixed(1)},${baseline} Z`;
});

const hovered = computed(() =>
    hoverIndex.value === null ? null : sorted.value[hoverIndex.value],
);

function onPointerMove(event: PointerEvent): void {
    const svg = event.currentTarget as SVGElement;
    const rect = svg.getBoundingClientRect();
    const x = event.clientX - rect.left;
    let closest = 0;
    let distance = Infinity;

    sorted.value.forEach((point, index) => {
        const delta = Math.abs(scaleX(point.x) - x);

        if (delta < distance) {
            distance = delta;
            closest = index;
        }
    });

    hoverIndex.value = sorted.value.length > 0 ? closest : null;
}

const tooltipStyle = computed(() => {
    if (!hovered.value) {
        return {};
    }

    const left = scaleX(hovered.value.x);
    const flip = left > width.value - 140;

    return {
        left: `${flip ? left - 12 : left + 12}px`,
        top: `${Math.max(0, scaleY(hovered.value.y) - 48)}px`,
        transform: flip ? 'translateX(-100%)' : 'none',
    };
});
</script>

<template>
    <figure class="w-full">
        <div ref="container" class="relative w-full">
            <svg
                v-if="points.length > 0 && width > 0"
                :width="width"
                :height="height"
                role="img"
                :aria-labelledby="`${chartId}-title`"
                class="block touch-none select-none"
                @pointermove="onPointerMove"
                @pointerleave="hoverIndex = null"
            >
                <title :id="`${chartId}-title`">{{ title }}</title>
                <g>
                    <line
                        v-for="tick in yTicks"
                        :key="`y-${tick}`"
                        :x1="padding.left"
                        :x2="padding.left + plotWidth"
                        :y1="scaleY(tick)"
                        :y2="scaleY(tick)"
                        stroke="var(--border)"
                        stroke-width="1"
                    />
                    <text
                        v-for="tick in yTicks"
                        :key="`yl-${tick}`"
                        :x="padding.left - 8"
                        :y="scaleY(tick)"
                        text-anchor="end"
                        dominant-baseline="middle"
                        class="num fill-muted-foreground text-[11px]"
                    >
                        {{ yFormat(tick) }}
                    </text>
                    <text
                        v-for="tick in xTicks"
                        :key="`x-${tick}`"
                        :x="scaleX(tick)"
                        :y="height - 8"
                        text-anchor="middle"
                        class="num fill-muted-foreground text-[11px]"
                    >
                        {{
                            xFormat(
                                tick,
                                sorted.find((point) => point.x === tick),
                            )
                        }}
                    </text>
                </g>

                <path
                    v-if="showArea && areaPath"
                    :d="areaPath"
                    :fill="color"
                    fill-opacity="0.08"
                />
                <path
                    :d="linePath"
                    fill="none"
                    :stroke="color"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                <circle
                    v-if="sorted.length === 1"
                    :cx="scaleX(sorted[0].x)"
                    :cy="scaleY(sorted[0].y)"
                    r="4"
                    :fill="color"
                />

                <g v-if="hovered">
                    <line
                        :x1="scaleX(hovered.x)"
                        :x2="scaleX(hovered.x)"
                        :y1="padding.top"
                        :y2="padding.top + plotHeight"
                        stroke="var(--muted-foreground)"
                        stroke-width="1"
                        stroke-dasharray="3 3"
                    />
                    <circle
                        :cx="scaleX(hovered.x)"
                        :cy="scaleY(hovered.y)"
                        r="5"
                        :fill="color"
                        stroke="var(--card)"
                        stroke-width="2"
                    />
                </g>
            </svg>

            <div
                v-if="hovered"
                class="pointer-events-none absolute z-10 rounded-xl border bg-popover px-3 py-2 text-sm shadow-lg"
                :style="tooltipStyle"
            >
                <div class="text-xs text-muted-foreground">
                    {{ hovered.label ?? xFormat(hovered.x, hovered) }}
                </div>
                <div class="num text-lg font-bold">
                    {{ yFormat(hovered.y) }}
                    <span class="text-sm font-semibold text-muted-foreground">{{
                        unit
                    }}</span>
                </div>
            </div>

            <p
                v-if="points.length === 0"
                class="flex items-center justify-center text-sm text-muted-foreground"
                :style="{ height: `${height}px` }"
            >
                Pas encore de données.
            </p>
        </div>

        <details v-if="points.length > 0" class="mt-2 text-sm">
            <summary
                class="cursor-pointer text-muted-foreground hover:text-foreground"
            >
                Voir les valeurs
            </summary>
            <div class="mt-2 max-h-56 overflow-auto rounded-xl border">
                <table class="w-full text-left">
                    <tbody>
                        <tr
                            v-for="point in sorted"
                            :key="point.x"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-1.5 text-muted-foreground">
                                {{ point.label ?? xFormat(point.x, point) }}
                            </td>
                            <td class="num px-3 py-1.5 text-right">
                                {{ yFormat(point.y) }} {{ unit }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </figure>
</template>
