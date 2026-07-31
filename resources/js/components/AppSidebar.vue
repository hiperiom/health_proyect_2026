<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
} from '@lucide/vue';
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
import {
    defaultModuleSidebarEntry,
    moduleSidebarConfig,
} from '@/config/modules';
import type {
    ModuleSidebarEntry,
    ModuleSidebarGroup,
} from '@/config/modules';
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
};

type PageProps = {
    auth: Auth;
};

const page = usePage<PageProps>();

const dashboardUrl = computed(() => dashboard().url);

const isSuperuser = computed<boolean>(
    () => page.props.auth.isSuperuser === true,
);

const accessibleModules = computed<string[]>(
    () => page.props.auth.accessibleModules ?? [],
);

/**
 * The sidebar source-of-truth for "which modules to render" is the
 * server-provided `accessibleModules` list (driven by the
 * `roles_modules` pivot). The superusuario always gets the union
 * of every module in the system (the middleware pushes the entire
 * `modules` table to it).
 */
const visibleModules = computed<string[]>(() => {
    const seen = new Set<string>();

    for (const name of accessibleModules.value) {
        if (name) {
            seen.add(name);
        }
    }

    return Array.from(seen);
});

/**
 * Build a default human-readable title from a snake/kebab-case
 * module name (`medical_especialties` -> `Medical Especialties`).
 */
const defaultModuleTitle = (moduleName: string): string => {
    return moduleName
        .replace(/[-_]+/g, ' ')
        .split(' ')
        .filter((part) => part !== '')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

/**
 * Try to find a static config entry for a module, accepting both
 * the kebab-case key (`medical-especialties`) and the snake_case
 * key (`medical_especialties`) so the registry works regardless
 * of which canonical form was used when the module was registered.
 */
const lookupConfig = (moduleName: string): ModuleSidebarEntry | null => {
    if (moduleSidebarConfig[moduleName]) {
        return moduleSidebarConfig[moduleName];
    }

    const normalised = moduleName.replace(/_/g, '-');
    if (moduleSidebarConfig[normalised]) {
        return moduleSidebarConfig[normalised];
    }

    return null;
};

const hasChildren = (
    entry: ModuleSidebarEntry,
): entry is ModuleSidebarGroup => {
    return Array.isArray((entry as ModuleSidebarGroup).children);
};

/**
 * Resolve the display title for a module.
 */
const moduleTitle = (moduleName: string): string => {
    const entry = lookupConfig(moduleName);

    if (entry && !hasChildren(entry) && entry.title !== '') {
        return entry.title;
    }

    return defaultModuleTitle(moduleName);
};

/**
 * Resolve the icon for a module.
 */
const moduleIcon = (moduleName: string) => {
    const entry = lookupConfig(moduleName);

    if (entry) {
        return entry.icon;
    }

    return defaultModuleSidebarEntry.icon;
};

/**
 * Build the final `mainNavItems` list.
 *
 * By default every visible module is rendered as its own top-level
 * entry in the sidebar. If a module's entry in `moduleSidebarConfig`
 * has a `children` array, it becomes a collapsible group instead.
 *
 * To create your own groups manually, edit
 * `resources/js/config/modules.ts` and use the `group` shape. See the
 * commented example at the bottom of that file.
 */
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Pantalla Inicial',
            href: dashboardUrl.value,
            icon: LayoutGrid,
        },
    ];

    for (const moduleName of visibleModules.value) {
        const entry = lookupConfig(moduleName);

        if (entry && hasChildren(entry)) {
            // Group shape: render a collapsible parent with its
            // children. Useful when you want to nest several modules
            // under a common heading.
            const groupEntry = entry as ModuleSidebarGroup;
            items.push({
                title: groupEntry.title,
                icon: groupEntry.icon,
                items: groupEntry.children.map((child) => ({
                    title: child.title,
                    href: `/${moduleName}`,
                    icon: child.icon,
                })),
            });
        } else {
            // Default shape: single top-level menu item.
            items.push({
                title: moduleTitle(moduleName),
                href: `/${moduleName}`,
                icon: moduleIcon(moduleName),
            });
        }
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

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
