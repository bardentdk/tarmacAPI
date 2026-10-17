<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, Gauge, ShieldCheck } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister?: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const pillars = [
    {
        icon: Gauge,
        title: 'Précis',
        text: 'Chrono au centième, puissance et couple estimés à chaque run.',
    },
    {
        icon: Activity,
        title: 'Franc',
        text: 'Chaque dixième perdu expliqué : météo, chaleur, masse, pilote.',
    },
    {
        icon: ShieldCheck,
        title: 'Protecteur',
        text: 'Codes défauts expliqués et score santé à chaque trajet.',
    },
];
</script>

<template>
    <Head title="Ton chrono. Ton coach. Ton moteur." />

    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4"
        >
            <div class="flex items-center gap-3">
                <AppLogoIcon class="size-10" />
                <span
                    class="font-display text-2xl font-extrabold tracking-[0.08em] uppercase"
                    >Tarmac</span
                >
            </div>
            <nav class="flex items-center gap-2">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex min-h-11 items-center rounded-2xl border border-input px-4 font-semibold hover:bg-accent"
                >
                    Mon espace
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-flex min-h-11 items-center rounded-2xl px-4 font-semibold hover:bg-accent"
                    >
                        Connexion
                    </Link>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center gap-10 px-4 py-12"
        >
            <div class="flex flex-col gap-6">
                <span class="eyebrow">Espace client</span>
                <h1
                    class="font-display text-6xl leading-[0.9] font-extrabold uppercase sm:text-8xl"
                >
                    Ton chrono.<br />
                    Ton coach.<br />
                    <span class="text-primary">Ton moteur.</span>
                </h1>
                <p class="max-w-xl text-lg text-muted-foreground">
                    Draggy te donne ton temps. Tarmac te dit comment
                    l'améliorer, sans casser ta voiture. Retrouve tes tirs, tes
                    rapports et la santé de ton moteur sur le web.
                </p>
                <div class="flex flex-wrap gap-3">
                    <Button
                        v-if="!$page.props.auth.user && canRegister"
                        size="lg"
                        as-child
                    >
                        <Link :href="register()">Essayer 14 jours</Link>
                    </Button>
                    <Button
                        v-else-if="$page.props.auth.user"
                        size="lg"
                        as-child
                    >
                        <Link :href="dashboard()">Ouvrir mon espace</Link>
                    </Button>
                    <Button
                        v-if="!$page.props.auth.user"
                        size="lg"
                        variant="outline"
                        as-child
                    >
                        <Link :href="login()">J'ai déjà un compte</Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div
                    v-for="pillar in pillars"
                    :key="pillar.title"
                    class="flex flex-col gap-2 rounded-[20px] border bg-card p-5"
                >
                    <component
                        :is="pillar.icon"
                        class="size-6 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <h2 class="font-display text-2xl font-bold uppercase">
                        {{ pillar.title }}
                    </h2>
                    <p class="text-muted-foreground">{{ pillar.text }}</p>
                </div>
            </div>
        </main>

        <footer
            class="mx-auto w-full max-w-6xl px-4 py-6 text-sm text-muted-foreground"
        >
            Les tirs et mesures de performance sont réservés aux circuits et
            pistes privées. Tarmac lit ton calculateur, il ne le modifie jamais.
        </footer>
    </div>
</template>
