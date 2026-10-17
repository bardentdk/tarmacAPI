<script setup lang="ts">
import { Form, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Check, Copy, Download, Lock } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Panel from '@/components/tarmac/Panel.vue';
import PowerTorqueChart from '@/components/tarmac/PowerTorqueChart.vue';
import RunConditions from '@/components/tarmac/RunConditions.vue';
import RunResultHero from '@/components/tarmac/RunResultHero.vue';
import SplitsTable from '@/components/tarmac/SplitsTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { index as billingIndex } from '@/routes/billing';
import { csv, index, share as shareRoute, show, unshare } from '@/routes/runs';
import { show as vehicleShow } from '@/routes/vehicles';
import type { RunDetail, ShareSummary } from '@/types/tarmac';

const props = defineProps<{
    run: RunDetail;
    share: ShareSummary | null;
    can: { see_power: boolean; download_csv: boolean };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Tirs et rapports', href: index() },
        { title: props.run.mode_label, href: show(props.run.uuid) },
    ],
});

const { copy, copied } = useClipboard();
</script>

<template>
    <Head :title="`${run.mode_label} · rapport`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="run.mode_label"
            :eyebrow="formatDateTime(run.recorded_at)"
        >
            <template #meta>
                <p v-if="run.vehicle" class="text-muted-foreground">
                    <Link
                        :href="vehicleShow(run.vehicle.uuid)"
                        class="font-semibold text-foreground hover:text-primary"
                        >{{ run.vehicle.name }}</Link
                    >
                    · {{ run.vehicle.tuning_label }}
                </p>
            </template>
            <template #actions>
                <Button
                    v-if="can.download_csv && run.has_samples"
                    variant="outline"
                    as-child
                >
                    <a :href="csv.url(run.uuid)" download>
                        <Download /> Logs CSV
                    </a>
                </Button>
            </template>
        </PageHeader>

        <RunResultHero :run="run" :show-power="can.see_power" />

        <div class="grid gap-6 lg:grid-cols-2">
            <Panel title="Temps intermédiaires">
                <SplitsTable :splits="run.splits" />
            </Panel>
            <Panel
                title="Conditions"
                description="Ce qui influence ton chrono : air, chaleur, masse, pente."
            >
                <RunConditions :conditions="run.conditions" />
            </Panel>
        </div>

        <Panel
            title="Puissance et couple"
            description="Estimés à partir de l'accélération mesurée, de la masse et de la densité de l'air."
        >
            <PowerTorqueChart
                v-if="can.see_power && run.curve.length > 0"
                :curve="run.curve"
            />
            <p v-else-if="can.see_power" class="text-sm text-muted-foreground">
                Pas de courbe pour ce tir : fais un run en rapport (3e
                idéalement) pour estimer la puissance.
            </p>
            <div
                v-else
                class="flex flex-col items-start gap-3 rounded-2xl border border-dashed p-5"
            >
                <p class="flex items-center gap-2 font-semibold">
                    <Lock class="size-4" /> La courbe de puissance est incluse
                    dans l'offre Pro.
                </p>
                <Link
                    :href="billingIndex()"
                    class="inline-flex min-h-11 items-center font-semibold text-primary hover:underline"
                    >Voir les offres</Link
                >
            </div>
        </Panel>

        <div class="grid gap-6 lg:grid-cols-2">
            <Panel
                title="Partage"
                description="Un lien public en lecture seule, que tu peux révoquer à tout moment."
            >
                <div v-if="share" class="flex flex-col gap-3">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Input
                            :model-value="share.url"
                            readonly
                            aria-label="Lien de partage"
                            class="font-mono text-sm"
                        />
                        <Button type="button" @click="copy(share.url)">
                            <Check v-if="copied" />
                            <Copy v-else />
                            {{ copied ? 'Copié' : 'Copier le lien' }}
                        </Button>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        <template v-if="share.recipient"
                            >Pour {{ share.recipient }} ·
                        </template>
                        Vu {{ share.views }} fois
                    </p>
                    <Link
                        :href="unshare(run.uuid)"
                        method="delete"
                        as="button"
                        preserve-scroll
                        class="inline-flex min-h-11 w-fit items-center rounded-xl px-3 font-semibold text-warn-text hover:bg-warn-bg"
                    >
                        Révoquer le lien
                    </Link>
                </div>
                <Form
                    v-else
                    v-bind="shareRoute.form(run.uuid)"
                    v-slot="{ errors, processing }"
                    :options="{ preserveScroll: true }"
                    class="flex flex-col gap-3"
                >
                    <div class="grid gap-2">
                        <Label for="recipient">Pour qui ? (facultatif)</Label>
                        <Input
                            id="recipient"
                            name="recipient"
                            placeholder="Mon mappeur, le club…"
                        />
                        <InputError :message="errors.recipient" />
                    </div>
                    <Button type="submit" class="w-fit" :disabled="processing">
                        Créer le lien de partage
                    </Button>
                </Form>
            </Panel>

            <Panel title="Logs bruts">
                <p
                    v-if="can.download_csv && run.has_samples"
                    class="text-sm text-muted-foreground"
                >
                    Temps, vitesse, régime et distance, point par point.
                    Ouvre-les dans un tableur ou envoie-les à ton mappeur.
                </p>
                <p
                    v-else-if="can.download_csv"
                    class="text-sm text-muted-foreground"
                >
                    Pas de logs bruts pour ce tir.
                </p>
                <div v-else class="flex flex-col items-start gap-2">
                    <p class="flex items-center gap-2 text-sm">
                        <Lock class="size-4" /> Les logs CSV sont inclus dans
                        l'offre Tuner.
                    </p>
                    <Link
                        :href="billingIndex()"
                        class="inline-flex min-h-11 items-center text-sm font-semibold text-primary hover:underline"
                        >Voir l'offre Tuner</Link
                    >
                </div>
                <p v-if="run.notes" class="border-t pt-3 text-sm">
                    <span class="eyebrow block">Notes</span>
                    {{ run.notes }}
                </p>
            </Panel>
        </div>

        <p class="text-xs text-muted-foreground">
            Mesures réalisées sur circuit / piste privée. Tarmac ne modifie
            jamais ton calculateur.
        </p>
    </div>
</template>
