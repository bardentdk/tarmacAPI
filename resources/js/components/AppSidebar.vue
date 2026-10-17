<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Car,
    CreditCard,
    LayoutGrid,
    Share2,
    Stethoscope,
    Timer,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, logout } from '@/routes';
import { index as billingIndex } from '@/routes/billing';
import { index as diagnosticsIndex } from '@/routes/diagnostics';
import { edit as profileEdit } from '@/routes/profile';
import { index as runsIndex } from '@/routes/runs';
import { index as sharesIndex } from '@/routes/shares';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);

const mainNavItems: NavItem[] = [
    { title: 'Tableau de bord', href: dashboard(), icon: LayoutGrid },
    { title: 'Mes véhicules', href: vehiclesIndex(), icon: Car },
    { title: 'Tirs et rapports', href: runsIndex(), icon: Timer },
    { title: 'Diagnostics', href: diagnosticsIndex(), icon: Stethoscope },
    { title: 'Partages', href: sharesIndex(), icon: Share2 },
    {
        title: 'Abonnement et factures',
        href: billingIndex(),
        icon: CreditCard,
    },
];

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <div
                class="flex flex-wrap items-center gap-x-2 px-2 py-3 text-sm group-data-[collapsible=icon]:hidden"
            >
                <Link
                    :href="profileEdit()"
                    class="max-w-[60%] truncate font-semibold hover:text-primary"
                    >{{ user?.name }}</Link
                >
                <span class="text-muted-foreground" aria-hidden="true">·</span>
                <Link
                    :href="logout()"
                    as="button"
                    class="text-muted-foreground hover:text-foreground"
                    data-test="logout-button"
                    @click="handleLogout"
                >
                    Déconnexion
                </Link>
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
