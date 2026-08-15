<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, HelpCircle, Plus, RefreshCw, Search } from '@lucide/vue';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import UserDialogs from '@/components/UserDialogs.vue';
import UserFormSheet from '@/components/UserFormSheet.vue';
import UserTable from '@/components/UserTable.vue';
import {
    assignRoles as assignRolesRoute,
    destroy,
    index,
    resetPassword as resetPasswordRoute,
} from '@/routes/users';
import type {
    MunicipalityOption,
    RoleOption,
    StateOption,
    UserGenderOption,
    UserModel,
    UserNacionalityOption,
    UserStatusOption,
} from '@/types/users';

const page = usePage();
const temporaryPassword = ref<string | null>(null);

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.temporary_password) {
            temporaryPassword.value = flash.temporary_password;
        }
    },
);

type Props = {
    items: {
        data: UserModel[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    item?: UserModel;
    availableRoles?: RoleOption[];
    availableStatuses?: UserStatusOption[];
    availableNacionalities?: UserNacionalityOption[];
    availableGenders?: UserGenderOption[];
    availableStates?: StateOption[];
    availableMunicipalities?: MunicipalityOption[];
    filters?: {
        search?: string;
        role?: string;
        per_page?: number;
    };
};

const props = withDefaults(defineProps<Props>(), {
    availableRoles: () => [],
    availableStatuses: () => [],
    availableNacionalities: () => [],
    availableGenders: () => [],
    availableStates: () => [],
    availableMunicipalities: () => [],
});

const availableRoles = computed<RoleOption[]>(() => props.availableRoles);

const open = ref(false);
const editingItem = ref<UserModel | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<UserModel | null>(null);
const resetDialogOpen = ref(false);
const itemToReset = ref<UserModel | null>(null);
const assignRoleOpen = ref(false);
const roleItem = ref<UserModel | null>(null);
const selectedRoleIds = ref<number[]>([]);
const roleError = ref<string | null>(null);
const search = ref<string>(props.filters?.search ?? '');
const roleFilter = ref<string>(
    props.filters?.role && props.filters.role !== ''
        ? props.filters.role
        : 'all',
);
const perPage = ref<string>(
    props.filters?.per_page && [10, 50, 100].includes(props.filters.per_page)
        ? String(props.filters.per_page)
        : '10',
);

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    const query: Record<string, string | number> = { page: 1 };

    if (search.value.trim() !== '') {
        query.search = search.value;
    }

    if (roleFilter.value !== 'all') {
        query.role = roleFilter.value;
    }

    if (perPage.value !== '10') {
        query.per_page = perPage.value;
    }

    router.get(index().url, query, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

watch(search, () => {
    if (searchDebounce) {
        clearTimeout(searchDebounce);
    }

    searchDebounce = setTimeout(() => applyFilters(), 300);
});

watch(roleFilter, () => applyFilters());
watch(perPage, () => applyFilters());

watch(
    open,
    (isOpen) => {
        if (!isOpen) {
            editingItem.value = null;
            selectedRoleIds.value = [];
        }
    },
);

watch(
    () => page.props.errors,
    (errors: Record<string, string> | undefined) => {
        roleError.value = errors?.role_ids ?? null;
    },
    { immediate: true },
);

function reloadList() {
    router.get(index().url, {}, {
        preserveState: false,
        preserveScroll: true,
    });
}

function navigateToPage(page: number) {
    router.get(index().url, { ...props.filters, page }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function openEditSheet(item: UserModel) {
    editingItem.value = item;
    selectedRoleIds.value = item.role_ids ?? [];
    open.value = true;
}

function openCreateSheet() {
    editingItem.value = null;
    selectedRoleIds.value = [];
    open.value = true;
}

function onFormSuccess() {
    open.value = false;
    editingItem.value = null;
}

function onPhotoUpdated(url: string | null) {
    if (editingItem.value) {
        editingItem.value = { ...editingItem.value, photoUrl: url };
    }
}

function confirmDelete(item: UserModel) {
    itemToDelete.value = item;
    deleteDialogOpen.value = true;
}

function confirmResetPassword(item: UserModel) {
    itemToReset.value = item;
    resetDialogOpen.value = true;
}

function openAssignRole(item: UserModel) {
    roleItem.value = item;
    selectedRoleIds.value = item.role_ids ?? [];
    assignRoleOpen.value = true;
}

function deleteItem() {
    if (!itemToDelete.value) {
        return;
    }

    router.delete(destroy(itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            itemToDelete.value = null;
        },
    });
}

function resetPassword() {
    if (!itemToReset.value) {
        return;
    }

    router.patch(
        resetPasswordRoute(itemToReset.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                resetDialogOpen.value = false;
                itemToReset.value = null;
            },
        },
    );
}

function assignRole() {
    if (!roleItem.value) {
        return;
    }

    roleError.value = null;

    router.patch(
        assignRolesRoute(roleItem.value.id),
        { role_ids: selectedRoleIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                assignRoleOpen.value = false;
                roleItem.value = null;
            },
            onError: (errors: Record<string, string>) => {
                roleError.value =
                    errors?.role_ids ??
                    Object.values(errors)[0] ??
                    'No se pudieron guardar los roles.';
            },
        },
    );
}

const startTour = () => {
    const driverObj = driver({
        showProgress: true,
        animate: true,
        allowClose: true,
        overlayOpacity: 0.5,
        nextBtnText: 'Siguiente →',
        prevBtnText: '← Anterior',
        doneBtnText: 'Finalizar',
        steps: [
            {
                element: '#tour-search',
                popover: {
                    title: '🔍 Buscar Usuarios',
                    description:
                        'Escribe el nombre o correo para filtrar la lista en tiempo real.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-filter',
                popover: {
                    title: '🛡️ Filtrar por Rol',
                    description:
                        'Selecciona un rol específico para ver únicamente los usuarios que poseen ese perfil.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-new-btn',
                popover: {
                    title: '➕ Crear Nuevo Usuario',
                    description:
                        'Haz clic aquí para abrir el panel lateral y registrar un nuevo usuario.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-table',
                popover: {
                    title: '📋 Tabla de Registros',
                    description:
                        'Aquí se listan todos los usuarios con su foto, nombre, correo y rol.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                element: '#tour-actions',
                popover: {
                    title: '⚙️ Acciones por Usuario',
                    description:
                        'Usa este menú para Editar, Asignar Rol, Resetear Contraseña o Eliminar un usuario.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-pagination',
                popover: {
                    title: '📄 Paginación y Controles',
                    description:
                        'Navega entre las páginas y ajusta la cantidad de registros por página.',
                    side: 'top',
                    align: 'end',
                },
            },
        ],
    });

    driverObj.drive();
};
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex h-full flex-col space-y-6">
        <div
            class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Usuarios"
                description="Gestión de usuarios y sus perfiles del sistema"
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72" id="tour-search">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre o correo..."
                        class="pl-8"
                    />
                </div>
                <div id="tour-filter">
                    <Select v-model="roleFilter">
                        <SelectTrigger class="w-full sm:w-40">
                            <SelectValue placeholder="Todos los roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los roles</SelectItem>
                            <SelectItem
                                v-for="role in availableRoles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <Button
                    variant="outline"
                    size="icon"
                    @click="reloadList"
                    title="Recargar listado"
                >
                    <RefreshCw class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    @click="startTour"
                    title="Guía del módulo"
                >
                    <HelpCircle class="h-4 w-4" />
                </Button>
                <Button id="tour-new-btn" @click="openCreateSheet">
                    <Plus class="h-4 w-4" />
                    Nuevo Usuario
                </Button>
            </div>
        </div>

        <UserFormSheet
            v-model:open="open"
            :editing-item="editingItem"
            :available-statuses="availableStatuses"
            :available-nacionalities="availableNacionalities"
            :available-genders="availableGenders"
            :available-states="availableStates"
            :available-municipalities="availableMunicipalities"
            @success="onFormSuccess"
            @photo-updated="onPhotoUpdated"
        />

        <UserDialogs
            v-model:delete-open="deleteDialogOpen"
            :item-to-delete="itemToDelete"
            v-model:reset-open="resetDialogOpen"
            :item-to-reset="itemToReset"
            v-model:assign-open="assignRoleOpen"
            :role-item="roleItem"
            :available-roles="availableRoles"
            v-model:selected-role-ids="selectedRoleIds"
            :role-error="roleError"
            @confirm-delete="deleteItem"
            @confirm-reset="resetPassword"
            @confirm-assign="assignRole"
        />

        <Alert
            v-if="temporaryPassword"
            variant="default"
            class="mb-4 border-green-500 bg-green-50 dark:bg-green-950"
        >
            <CircleCheck class="h-4 w-4" />
            <AlertTitle>Usuario creado exitosamente</AlertTitle>
            <AlertDescription>
                La contraseña temporal para este usuario es:
                <strong class="mt-2 block font-mono text-lg">{{
                    temporaryPassword
                }}</strong>
                <p class="mt-2 text-sm">
                    Comparta esta contraseña de forma segura con el usuario. Se
                    le solicitará cambiarla en el primer inicio de sesión.
                </p>
            </AlertDescription>
        </Alert>

        <UserTable
            :items="items"
            v-model:per-page="perPage"
            @navigate="navigateToPage"
            @edit="openEditSheet"
            @assign-role="openAssignRole"
            @reset-password="confirmResetPassword"
            @delete="confirmDelete"
        />
    </div>
</template>
