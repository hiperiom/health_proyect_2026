<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Boxes,
    FileText,
    FolderGit2,
    Key,
    LayoutGrid,
    Shield,
    Stethoscope,
    Users,
    ShieldCheck,
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
import { dashboard } from '@/routes';
import { index as medicalespecialtiesIndex } from '@/routes/medicalespecialties';
import { index as modulesIndex } from '@/routes/modules';
import { index as patientsIndex } from '@/routes/patients';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { RoleModel } from '@/types';
import type { NavItem } from '@/types';

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

const userPermissions = computed<string[]>(
    () => page.props.auth.user?.permissions ?? [],
);

const isSuperuser = computed<boolean>(
    () => page.props.auth.isSuperuser === true,
);

const accessibleModules = computed<string[]>(
    () => page.props.auth.accessibleModules ?? [],
);

/**
 * Check whether the current user can see a given module by its
 * canonical name (e.g. `users`, `roles`, `modules`).
 *
 * The superusuario always has access to every module. For any other
 * role the module name must appear in the `accessibleModules` list
 * (computed server-side from the user's roles + the roles_modules
 * pivot table).
 */
const canAccessModule = (moduleName: string): boolean => {
    if (isSuperuser.value) {
        return true;
    }

    return accessibleModules.value.includes(moduleName);
};

/**
 * Check whether the current user has a permission on a given module.
 * Used as a secondary signal for the (legacy) per-route menu items
 * where the route has not been migrated to the new module-based
 * filtering yet.
 */
const hasModulePermission = (moduleName: string): boolean => {
    if (isSuperuser.value) {
        return true;
    }

    return userPermissions.value.some((slug) =>
        slug.startsWith(`${moduleName}.`),
    );
};

const canUsers = computed<boolean>(() => canAccessModule('users'));
const canRoles = computed<boolean>(() => canAccessModule('roles'));
const canPermissions = computed<boolean>(
    () => canAccessModule('permissions'),
);
const canModules = computed<boolean>(() => canAccessModule('modules'));
const canMedicalEspecialties = computed<boolean>(
    () => canAccessModule('medicalespecialties'),
);
const canPatients = computed<boolean>(() => canAccessModule('patients'));

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Pantalla Inicial',
            href: dashboardUrl.value,
            icon: LayoutGrid,
        },
    ];
    const geographicalLocation: NavItem[] = [];

    if (canUsers.value) {
        /*geographicalLocation.push({
            title: 'Countries',
            href: countriesIndex().url,
            icon: FileText,
        });*/
    }

    if (geographicalLocation.length > 0) {
        items.push({
            title: 'Ubicación Geográfica',
            icon: ShieldCheck,
            items: geographicalLocation,
        });
    }

    if (canUsers.value) {
        items.push({
            title: 'Usuarios',
            href: usersIndex().url,
            icon: Key,
        });
    }

    if (canRoles.value) {
        items.push({
            title: 'Roles',
            href: rolesIndex().url,
            icon: Shield,
        });
    }

    if (canPermissions.value) {
        items.push({
            title: 'Permisos',
            href: permissionsIndex().url,
            icon: FileText,
        });
    }

    if (canModules.value) {
        items.push({
            title: 'Modules',
            href: modulesIndex().url,
            icon: Boxes,
        });
    }

    if (canMedicalEspecialties.value) {
        items.push({
            title: 'Medical Specialties',
            href: medicalespecialtiesIndex().url,
            icon: Stethoscope,
        });
    }

    if (canPatients.value) {
        items.push({
            title: 'Patients',
            href: patientsIndex().url,
            icon: Users,
        });
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

// Suppress unused warning for the legacy helper. The helper is kept
// for non-migrated routes that still rely on the `permissions` array.
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const _legacy = hasModulePermission;
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
