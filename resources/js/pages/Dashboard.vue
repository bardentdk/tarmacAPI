<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Download, Plus, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import LineChart from '@/components/tarmac/LineChart.vue';
import Panel from '@/components/tarmac/Panel.vue';
import Pill from '@/components/tarmac/Pill.vue';
import StatTile from '@/components/tarmac/StatTile.vue';
import UrgencyBadge from '@/components/tarmac/UrgencyBadge.vue';
import { Button } from '@/components/ui/button';
import {
    formatBytes,
    formatDate,
    formatNumber,
    formatRelative,
    formatSeconds,
    formatShortDate,
    scoreTone,
} from '@/lib/format';
import { dashboard } from '@/routes';
import { exportMethod } from '@/routes/account';
import { index as billingIndex } from '@/routes/billing';
import { index as diagnosticsIndex } from '@/routes/diagnostics';
import { index as healthIndex } from '@/routes/health';
import { index as runsIndex, show as runShow } from '@/routes/runs';
import { destroy as shareDestroy, index as sharesIndex } from '@/routes/shares';
import {
    create as vehicleCreate,
    show as vehicleShow,
} from '@/routes/vehicles';
import type {
    DtcSummary,
    RunSummary,
    ShareSummary,
    Subscription,
    VehicleSummary,
} from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tableau de bord', href: dashboard() }],
    },
});

const props = defineProps<{
    greeting: string;
    firstName: string;
    lastSyncedAt: string | null;
    stats: {
        health: {
            score: number;
            previous: number | null;
            watch_count: number;
        } | null;
        best_zero_100: {
            uuid: string;
            result_s: number;
            recorded_at: string;
        } | null;
        power: { uuid: string; hp: number; delta_hp: number | null } | null;
        storage_bytes: number;
    };
    healthTrend: { date: string; score: number }[];
    bestByMode: {
        mode: string;
        mode_label: string;
        result_s: number;
        uuid: string;
        recorded_at: string;
    }[];
    recentRuns: RunSummary[];
    recentDtcs: DtcSummary[];
    vehicles: VehicleSummary[];
    activeShares: ShareSummary[];
    subscription: Subscription;
}>();

const planLabels: Record<string, string> = {
    free: 'Gratuit',
    pro: 'Pro',
    tuner: 'Tuner',
    garage: 'Garage',
};

const healthHint = computed(() => {
    const health = props.stats.health;

    if (!health) {
        return 'Fais un trajet avec le boîtier branché.';
    }

    if (health.watch_count === 0) {
        return 'Rien à signaler';
    }

    return health.watch_count === 1
        ? '1 point à surveiller'
        : `${health.watch_count} points à surveiller`;
});

const powerHint = computed(() => {
    const delta = props.stats.power?.delta_hp;

    if (delta === null || delta === undefined) {
        return 'Mesuré sur ton meilleur run';
    }

    const sign = delta >= 0 ? '+' : '−';

    return `${sign}${formatNumber(Math.abs(delta))} ch vs ton premier run`;
});

const trendPoints = computed(() =>
    props.healthTrend.map((point, index) => ({
        x: index,
        y: point.score,
        label: formatDate(point.date),
    })),
);

const subscriptionLine = computed(() => {
    const sub = props.subscription;
    const label = planLabels[sub.plan] ?? sub.plan;

    if (sub.is_trial) {
        return `${label} · essai jusqu'au ${formatDate(sub.trial_ends_at)}`;
    }

    if (sub.plan_period) {
        return `${label} · ${sub.plan_period === 'yearly' ? 'annuel' : 'mensuel'}`;
    }

    return label;
});

const upgradeTarget = computed(() =>
    props.subscription.plan === 'tuner' || props.subscription.plan === 'garage'
        ? null
        : props.subscription.plan === 'pro' && !props.subscription.is_trial
          ? 'Passer à Tuner'
          : 'Voir les offres',
);
</script>

<template>
    <Head title="Tableau de bord" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1
                    class="font-display text-4xl font-extrabold tracking-tight sm:text-5xl"
                >
                    {{ greeting }} {{ firstName }}
                </h1>
                <p class="text-muted-foreground">
                    <template v-if="lastSyncedAt">
                        Dernière synchro {{ formatRelative(lastSyncedAt) }}
                        depuis ton téléphone
                    </template>
                    <template v-else>
                        Pas encore de synchro : connecte-toi dans l'app Tarmac
                        pour envoyer tes données.
                    </template>
                </p>
            </div>
            <Button variant="outline" as-child>
                <a :href="exportMethod.url()" download>
                    <Download />
                    Exporter mes données
                </a>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                :href="healthIndex()"
                class="rounded-[20px] hover:ring-1 hover:ring-input"
            >
                <StatTile
                    label="Score santé"
                    :value="stats.health ? String(stats.health.score) : '—'"
                    unit="/100"
                    :hint="healthHint"
                    :tone="scoreTone(stats.health?.score)"
                />
            </Link>
            <StatTile
                label="Meilleur 0-100"
                :value="
                    stats.best_zero_100
                        ? formatNumber(stats.best_zero_100.result_s, 2)
                        : '—'
                "
                unit="s"
                :hint="
                    stats.best_zero_100
                        ? formatDate(stats.best_zero_100.recorded_at)
                        : 'Pas encore de 0-100'
                "
            />
            <StatTile
                label="Puissance estimée"
                :value="stats.power ? formatNumber(stats.power.hp) : '—'"
                unit="ch"
                :hint="stats.power ? powerHint : 'Pas encore de mesure'"
            />
            <StatTile
                label="Stockage logs"
                :value="formatBytes(stats.storage_bytes)"
                hint="Données brutes de tes runs"
            />
        </div>

        <Panel title="Derniers tirs">
            <template #action>
                <Link
                    :href="runsIndex()"
                    class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted-foreground hover:text-foreground"
                >
                    Tout voir <ArrowRight class="size-4" />
                </Link>
            </template>
            <EmptyState
                v-if="recentRuns.length === 0"
                title="Aucun tir pour l'instant"
                description="Tes runs apparaissent ici dès que ton téléphone se synchronise."
            />
            <div v-else class="-mx-5 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground">
                            <th class="px-5 py-2 font-semibold">Date</th>
                            <th class="px-3 py-2 font-semibold">Mode</th>
                            <th class="px-3 py-2 font-semibold">Résultat</th>
                            <th class="px-3 py-2 font-semibold">Conditions</th>
                            <th class="px-5 py-2 text-right font-semibold">
                                Rapport
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="run in recentRuns"
                            :key="run.uuid"
                            class="border-b last:border-0"
                        >
                            <td class="px-5 py-3 whitespace-nowrap">
                                {{ formatDate(run.recorded_at) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ run.mode_label }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="num text-lg font-bold">{{
                                    formatSeconds(run.result_s)
                                }}</span>
                                <Pill
                                    v-if="run.is_record"
                                    tone="ok"
                                    class="ml-2"
                                >
                                    <Trophy class="size-3" /> Record
                                </Pill>
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ run.conditions_summary ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <Link
                                    :href="runShow(run.uuid)"
                                    class="inline-flex min-h-11 items-center gap-1 font-semibold hover:text-primary"
                                >
                                    Ouvrir <ArrowRight class="size-4" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Panel>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel title="Abonnement">
                <div class="flex flex-col gap-1">
                    <span class="font-display text-3xl font-bold">{{
                        subscriptionLine
                    }}</span>
                    <span
                        v-if="
                            subscription.plan_period &&
                            subscription.plan_expires_at
                        "
                        class="text-sm text-muted-foreground"
                    >
                        Prochain paiement le
                        {{ formatDate(subscription.plan_expires_at) }}
                    </span>
                    <span
                        v-else-if="subscription.plan === 'free'"
                        class="text-sm text-muted-foreground"
                    >
                        3 tirs par mois, codes défauts et score santé.
                    </span>
                </div>
                <div class="mt-auto flex flex-wrap gap-2">
                    <Button v-if="upgradeTarget" as-child>
                        <Link :href="billingIndex()">{{ upgradeTarget }}</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="billingIndex()">Factures</Link>
                    </Button>
                </div>
            </Panel>

            <Panel title="Mes véhicules">
                <ul class="flex flex-col gap-2">
                    <li v-for="vehicle in vehicles" :key="vehicle.uuid">
                        <Link
                            :href="vehicleShow(vehicle.uuid)"
                            class="flex min-h-11 items-center justify-between gap-3 rounded-xl px-3 py-2 hover:bg-accent"
                        >
                            <span class="truncate font-semibold">{{
                                vehicle.name
                            }}</span>
                            <Pill tone="neutral">{{
                                vehicle.tuning_label
                            }}</Pill>
                        </Link>
                    </li>
                    <li
                        v-if="vehicles.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Aucun véhicule pour l'instant.
                    </li>
                </ul>
                <Link
                    :href="vehicleCreate()"
                    class="mt-auto inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-muted-foreground hover:text-foreground"
                >
                    <Plus class="size-4" /> Ajouter un véhicule
                </Link>
            </Panel>

            <Panel title="Partages actifs">
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="share in activeShares"
                        :key="share.id"
                        class="flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-semibold">
                                {{ share.recipient ?? 'Lien public' }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ share.run?.mode_label }} ·
                                {{ formatSeconds(share.run?.result_s) }} ·
                                lecture seule
                            </p>
                        </div>
                        <Link
                            :href="shareDestroy(share.id)"
                            method="delete"
                            as="button"
                            preserve-scroll
                            class="inline-flex min-h-11 shrink-0 items-center rounded-xl px-3 text-sm font-semibold text-warn-text hover:bg-warn-bg"
                        >
                            Révoquer
                        </Link>
                    </li>
                    <li
                        v-if="activeShares.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Aucun lien actif. Partage un run depuis son rapport.
                    </li>
                </ul>
                <Link
                    :href="sharesIndex()"
                    class="mt-auto inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted-foreground hover:text-foreground"
                >
                    Gérer les partages <ArrowRight class="size-4" />
                </Link>
            </Panel>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Panel title="Meilleurs temps">
                <ul v-if="bestByMode.length > 0" class="flex flex-col">
                    <li
                        v-for="best in bestByMode"
                        :key="best.mode"
                        class="border-b last:border-0"
                    >
                        <Link
                            :href="runShow(best.uuid)"
                            class="flex min-h-11 items-center justify-between gap-3 py-2 hover:text-primary"
                        >
                            <span>{{ best.mode_label }}</span>
                            <span class="num text-xl font-bold">{{
                                formatSeconds(best.result_s)
                            }}</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Tes records par mode s'afficheront ici.
                </p>
            </Panel>

            <Panel title="Santé" description="Score des derniers trajets">
                <LineChart
                    :points="trendPoints"
                    title="Évolution du score santé sur les derniers trajets"
                    unit="/100"
                    :y-min="0"
                    :y-max="100"
                    :height="180"
                    :x-format="
                        (value: number) =>
                            formatShortDate(healthTrend[value]?.date)
                    "
                />
                <p
                    v-if="stats.health?.previous !== null && stats.health"
                    class="text-sm text-muted-foreground"
                >
                    <template
                        v-if="
                            stats.health.score >= (stats.health.previous ?? 0)
                        "
                    >
                        Stable ou en hausse depuis le trajet précédent.
                    </template>
                    <template v-else>
                        En baisse de
                        {{ (stats.health.previous ?? 0) - stats.health.score }}
                        points depuis le trajet précédent.
                    </template>
                </p>
            </Panel>

            <Panel title="Codes défauts récents">
                <template #action>
                    <Link
                        :href="diagnosticsIndex()"
                        class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-muted-foreground hover:text-foreground"
                    >
                        Tout voir <ArrowRight class="size-4" />
                    </Link>
                </template>
                <ul v-if="recentDtcs.length > 0" class="flex flex-col gap-3">
                    <li
                        v-for="dtc in recentDtcs"
                        :key="dtc.uuid"
                        class="flex flex-col gap-1"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <Link
                                :href="
                                    diagnosticsIndex({
                                        query: { code: dtc.code },
                                    })
                                "
                                class="num text-lg font-bold hover:text-primary"
                                >{{ dtc.code }}</Link
                            >
                            <UrgencyBadge
                                v-if="!dtc.cleared_at"
                                :urgency="dtc.urgency"
                            />
                            <Pill v-else tone="neutral">Effacé</Pill>
                        </div>
                        <span class="text-sm text-muted-foreground">{{
                            dtc.title ?? 'Explication à venir'
                        }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Aucun code défaut. Ton moteur est au calme.
                </p>
            </Panel>
        </div>
    </div>
</template>
