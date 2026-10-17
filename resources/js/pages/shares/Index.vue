<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { ref } from 'vue';
import EmptyState from '@/components/tarmac/EmptyState.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import Panel from '@/components/tarmac/Panel.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatSeconds } from '@/lib/format';
import { show as runShow } from '@/routes/runs';
import { destroy, index } from '@/routes/shares';
import type { ShareSummary } from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Partages', href: index() }],
    },
});

defineProps<{
    active: ShareSummary[];
    revoked: ShareSummary[];
}>();

const { copy } = useClipboard();
const copiedId = ref<number | null>(null);

function copyLink(share: ShareSummary): void {
    copy(share.url);
    copiedId.value = share.id;
    setTimeout(() => (copiedId.value = null), 1500);
}
</script>

<template>
    <Head title="Partages" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Partages"
            description="Tes liens publics en lecture seule. Révoque-les quand tu veux : le lien cesse de marcher tout de suite."
        />

        <Panel title="Partages actifs">
            <EmptyState
                v-if="active.length === 0"
                title="Aucun lien actif"
                description="Ouvre un rapport de tir et crée un lien pour l'envoyer à ton mappeur ou à ton club."
            />
            <ul v-else class="flex flex-col">
                <li
                    v-for="share in active"
                    :key="share.id"
                    class="flex flex-col gap-3 border-b py-3 last:border-0 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p class="font-semibold">
                            {{ share.recipient ?? 'Lien public' }}
                            <span class="font-normal text-muted-foreground">
                                · lecture seule</span
                            >
                        </p>
                        <p class="text-sm text-muted-foreground">
                            <Link
                                v-if="share.run"
                                :href="runShow(share.run.uuid)"
                                class="hover:text-foreground"
                                >{{ share.run.mode_label }} ·
                                {{ formatSeconds(share.run.result_s) }}</Link
                            >
                            · créé le {{ formatDate(share.created_at) }} · vu
                            {{ share.views }} fois
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="copyLink(share)"
                        >
                            <Check v-if="copiedId === share.id" />
                            <Copy v-else />
                            {{ copiedId === share.id ? 'Copié' : 'Copier' }}
                        </Button>
                        <Link
                            :href="destroy(share.id)"
                            method="delete"
                            as="button"
                            preserve-scroll
                            class="inline-flex h-10 items-center rounded-xl px-3 text-sm font-semibold text-warn-text hover:bg-warn-bg"
                        >
                            Révoquer
                        </Link>
                    </div>
                </li>
            </ul>
        </Panel>

        <Panel v-if="revoked.length > 0" title="Liens révoqués">
            <ul class="flex flex-col">
                <li
                    v-for="share in revoked"
                    :key="share.id"
                    class="border-b py-2 text-sm text-muted-foreground last:border-0"
                >
                    {{ share.recipient ?? 'Lien public' }} ·
                    {{ share.run?.mode_label }} · révoqué le
                    {{ formatDate(share.revoked_at) }}
                </li>
            </ul>
        </Panel>
    </div>
</template>
