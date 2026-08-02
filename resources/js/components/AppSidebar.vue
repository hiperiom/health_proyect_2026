<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FileText, LayoutGrid } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import RoleSwitcher from '@/components/RoleSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { RoleModel } from '@/types';
import type { NavItem } from '@/types';
import { dashboard } from '@/routes';

type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    roleName: string | null;
    permissions: string[];
};

type Auth = {
    user: AuthUser | null;
    roles: RoleModel[];
    activeRole: {
        id: number | null;
        name: string | null;
        slug: string | null;
    } | null;
    hasMultipleRoles: boolean;
    isSuperuser: boolean;
    accessibleModules: string[];
    moduleDisplayNames: Record<string, string>;
};

type PageProps = {
    auth: Auth;
};

const page = usePage<PageProps>();

const dashboardUrl = computed(() => dashboard().url);

const accessibleModules = computed<string[]>(
    () => page.props.auth.accessibleModules ?? [],
);

const moduleDisplayNames = computed<Record<string, string>>(
    () => page.props.auth.moduleDisplayNames ?? {},
);

/**
 * Build a default human-readable title from a snake/kebab-case
 * module name. Used only as a last-resort fallback.
 */
const fallbackModuleTitle = (moduleName: string): string =>
    moduleName
        .replace(/[-_]+/g, ' ')
        .split(' ')
        .filter((part) => part !== '')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

/**
 * Resolve the display title for a module. The canonical source is the
 * server-provided `moduleDisplayNames` map (sourced from the
 * `modules` table in the database via
 * `App\Http\Middleware\HandleInertiaRequests`). This keeps the
 * sidebar always in sync with the data layer.
 */
const moduleTitle = (moduleName: string): string => {
    const dbTitle = moduleDisplayNames.value[moduleName];
    if (dbTitle && dbTitle !== '') {
        return dbTitle;
    }

    return fallbackModuleTitle(moduleName);
};

const visibleModules = computed<string[]>(() => {
    const seen = new Set<string>();

    for (const name of accessibleModules.value) {
        if (name) {
            seen.add(name);
        }
    }

    return Array.from(seen);
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Pantalla Inicial',
            href: dashboardUrl.value,
            icon: LayoutGrid,
        },
    ];

    for (const moduleName of visibleModules.value) {
        items.push({
            title: moduleTitle(moduleName),
            href: `/${moduleName}`,
            icon: FileText,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
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
            <RoleSwitcher />
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
