<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Panel from '@/components/tarmac/Panel.vue';
import Pill from '@/components/tarmac/Pill.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatPrice } from '@/lib/format';
import { index } from '@/routes/billing';
import type { Features, PlanValue, Subscription } from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Abonnement et factures', href: index() }],
    },
});

const props = defineProps<{
    subscription: Subscription;
    plans: {
        value: PlanValue;
        label: string;
        monthly: number | null;
        yearly: number | null;
        features: Features;
    }[];
    checkoutEnabled: boolean;
}>();

const period = ref<'monthly' | 'yearly'>('monthly');

const copy: Record<
    'free' | 'pro' | 'tuner',
    { summary: string; bullets: string[] }
> = {
    free: {
        summary: 'Codes, santé, 3 tirs / mois',
        bullets: [
            'Lecture et effacement des codes défauts',
            'Score santé à chaque trajet',
            '3 tirs sauvegardés par mois',
            '1 véhicule',
        ],
    },
    pro: {
        summary: 'Pour progresser à chaque run',
        bullets: [
            'Les 14 modes de tir + rapports expliqués',
            'Puissance et couple estimés',
            'Explications détaillées + assistant IA',
            'Historique illimité dans le cloud',
        ],
    },
    tuner: {
        summary: 'Pour valider une carto sans banc',
        bullets: [
            'Tout Pro',
            'Comparateur avant / après reprog',
            'Logs CSV',
            'Live partagé au mappeur',
            '5 véhicules',
        ],
    },
};

const displayedPlans = computed(() =>
    props.plans.filter((plan) => plan.value !== 'garage'),
);
const garage = computed(() =>
    props.plans.find((plan) => plan.value === 'garage'),
);

const currentLabel = computed(
    () =>
        props.plans.find((plan) => plan.value === props.subscription.plan)
            ?.label ?? props.subscription.plan,
);

function price(plan: { monthly: number | null; yearly: number | null }): {
    amount: string;
    suffix: string;
    note: string | null;
} {
    if (period.value === 'yearly' && plan.yearly !== null) {
        const discount =
            plan.monthly && plan.monthly > 0
                ? Math.round((1 - plan.yearly / (plan.monthly * 12)) * 100)
                : 0;

        return {
            amount: formatPrice(plan.yearly),
            suffix: '/an',
            note: discount > 0 ? `−${discount} %` : null,
        };
    }

    return {
        amount: formatPrice(plan.monthly ?? 0),
        suffix: plan.monthly ? '/mois' : '',
        note: null,
    };
}

function ctaLabel(plan: PlanValue): string {
    if (plan === props.subscription.plan && !props.subscription.is_trial) {
        return 'Ton offre actuelle';
    }

    if (plan === 'pro') {
        return props.subscription.is_trial ? 'Garder Pro' : 'Essayer 14 jours';
    }

    return plan === 'free'
        ? 'Offre de base'
        : `Passer à ${plan === 'tuner' ? 'Tuner' : 'Pro'}`;
}
</script>

<template>
    <Head title="Abonnement et factures" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Abonnement et factures" />

        <Panel title="Ton offre">
            <div class="flex flex-col gap-1">
                <span class="font-display text-4xl font-extrabold">
                    {{ currentLabel }}
                    <template v-if="subscription.plan_period">
                        ·
                        {{
                            subscription.plan_period === 'yearly'
                                ? 'annuel'
                                : 'mensuel'
                        }}
                    </template>
                </span>
                <p v-if="subscription.is_trial" class="text-muted-foreground">
                    Essai gratuit jusqu'au
                    {{ formatDate(subscription.trial_ends_at) }}. Ensuite, tu
                    repasses sur l'offre Gratuit si tu ne t'abonnes pas.
                </p>
                <p
                    v-else-if="subscription.plan_expires_at"
                    class="text-muted-foreground"
                >
                    Accès jusqu'au
                    {{ formatDate(subscription.plan_expires_at) }}
                    (renouvellement automatique sauf résiliation).
                </p>
                <p v-else class="text-muted-foreground">
                    Offre gratuite, sans limite de durée.
                </p>
            </div>
        </Panel>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold"
                :class="
                    period === 'monthly'
                        ? 'border-foreground bg-foreground text-background'
                        : 'border-input hover:bg-accent'
                "
                :aria-pressed="period === 'monthly'"
                @click="period = 'monthly'"
            >
                Mensuel
            </button>
            <button
                type="button"
                class="inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold"
                :class="
                    period === 'yearly'
                        ? 'border-foreground bg-foreground text-background'
                        : 'border-input hover:bg-accent'
                "
                :aria-pressed="period === 'yearly'"
                @click="period = 'yearly'"
            >
                Annuel · jusqu'à −35 %
            </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <article
                v-for="plan in displayedPlans"
                :key="plan.value"
                class="relative flex flex-col gap-4 rounded-[22px] border bg-card p-6"
                :class="plan.value === 'pro' ? 'border-ok-border' : ''"
            >
                <Pill
                    v-if="plan.value === 'pro'"
                    tone="ok"
                    class="absolute -top-3 left-6"
                    >LE PLUS CHOISI</Pill
                >
                <div class="flex items-center justify-between gap-2">
                    <h2 class="font-display text-3xl font-bold">
                        {{ plan.label }}
                    </h2>
                    <Pill v-if="plan.value === subscription.plan">{{
                        subscription.is_trial ? 'En essai' : 'Actuelle'
                    }}</Pill>
                </div>
                <p class="text-muted-foreground">
                    {{ copy[plan.value as 'free' | 'pro' | 'tuner'].summary }}
                </p>
                <div class="flex items-baseline gap-1">
                    <span class="num text-5xl font-extrabold">{{
                        price(plan).amount
                    }}</span>
                    <span class="text-muted-foreground">{{
                        price(plan).suffix
                    }}</span>
                    <Pill v-if="price(plan).note" tone="ok" class="ml-2">{{
                        price(plan).note
                    }}</Pill>
                </div>
                <p
                    v-if="
                        period === 'yearly' &&
                        plan.yearly === null &&
                        plan.monthly
                    "
                    class="text-xs text-muted-foreground"
                >
                    Pas de formule annuelle pour cette offre.
                </p>
                <ul class="flex flex-col gap-2 text-sm">
                    <li
                        v-for="bullet in copy[
                            plan.value as 'free' | 'pro' | 'tuner'
                        ].bullets"
                        :key="bullet"
                        class="flex items-start gap-2"
                    >
                        <Check class="mt-0.5 size-4 shrink-0 text-ok" />
                        {{ bullet }}
                    </li>
                </ul>
                <Button
                    class="mt-auto w-full"
                    size="lg"
                    :variant="plan.value === 'pro' ? 'default' : 'outline'"
                    :disabled="
                        !checkoutEnabled ||
                        plan.value === 'free' ||
                        (plan.value === subscription.plan &&
                            !subscription.is_trial)
                    "
                >
                    {{ ctaLabel(plan.value) }}
                </Button>
            </article>
        </div>

        <p
            v-if="!checkoutEnabled"
            class="rounded-2xl border border-info/30 bg-info/10 p-4 text-sm"
        >
            Paiement bientôt disponible sur le web. En attendant, abonne-toi
            depuis l'app Tarmac : ton offre s'active ici automatiquement.
        </p>

        <details v-if="garage" id="offre-garage" class="text-sm">
            <summary
                class="inline-flex min-h-11 cursor-pointer items-center text-muted-foreground"
            >
                Tu es un garage ?&nbsp;
                <span
                    class="font-semibold text-foreground underline underline-offset-4 hover:text-primary"
                    >Découvre l'offre Garage</span
                >
            </summary>
            <div class="mt-2 rounded-2xl border bg-card p-4">
                <p class="font-display text-2xl font-bold">
                    Garage · {{ formatPrice(garage.monthly) }}/mois
                </p>
                <p class="text-muted-foreground">
                    Clients et véhicules illimités, rapports en marque blanche,
                    devis à partir des codes défauts, multi-boîtiers. Demande
                    l'activation depuis l'app (Réglages, Contact).
                </p>
            </div>
        </details>

        <Panel title="Factures">
            <p class="text-sm text-muted-foreground">
                Aucune facture pour l'instant. Les achats faits dans l'app sont
                facturés par l'App Store ou Google Play : retrouve-les dans ton
                compte Apple ou Google.
            </p>
        </Panel>
    </div>
</template>
