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
import type { NavItem } from '@/types';


type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    roleName: string | null;
    permissions: string[];
};

type PageProps = {
    auth: {
        user: AuthUser | null;
    };
};

const page = usePage<PageProps>();

const dashboardUrl = computed(() => dashboard().url);

const userPermissions = computed<string[]>(
    () => page.props.auth.user?.permissions ?? [],
);

const isSuperuser = computed<boolean>(
    () => page.props.auth.user?.role === 'superusuario',
);

const canUsers = computed<boolean>(
    () =>
        isSuperuser.value ||
        userPermissions.value.some((slug) => slug.startsWith('users.')),
);

const canRoles = computed<boolean>(
    () =>
        isSuperuser.value ||
        userPermissions.value.some((slug) => slug.startsWith('roles.')),
);

const canPermissions = computed<boolean>(
    () =>
        isSuperuser.value ||
        userPermissions.value.some((slug) => slug.startsWith('permissions.')),
);

const canModules = computed<boolean>(
    () =>
        isSuperuser.value ||
        userPermissions.value.some((slug) => slug.startsWith('modules.')),
);

const canMedicalEspecialties = computed<boolean>(
    () =>
        isSuperuser.value ||
        userPermissions.value.some((slug) =>
            slug.startsWith('medicalespecialties.'),
        ),
);

const canPatients = computed<boolean>(
    () =>
        isSuperuser.value ||
        userPermissions.value.some((slug) => slug.startsWith('patients.')),
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl.value,
            icon: LayoutGrid,
        },
        
        
    ];
    // --- OPCIÓN DESPLEGABLE CON SUBMENÚS ---
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
            items: geographicalLocation, // Lista de subelementos
        });
    }

    if (canUsers.value) {
        items.push({
            title: 'Users',
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
            title: 'Permissions',
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
