<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    CircleCheck,
    HelpCircle,
    Key,
    MoreVertical,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Shield,
    Trash,
} from '@lucide/vue';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import UsersProfilePhotoUploader from '@/components/UsersProfilePhotoUploader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    assignRoles as assignRolesRoute,
    destroy,
    index,
    resetPassword as resetPasswordRoute,
    store,
    update,
} from '@/routes/users';
import type {
    MunicipalityOption,
    RoleOption,
    StateOption,
    UserGenderOption,
    UserModel,
    UserNacionalityOption,
    UserRole,
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

// Collapsible "Perfil del usuario": abierto por defecto.
const profileOpen = ref(true);

// Collapsible "Ubicación geográfica": cerrado por defecto.
const geoOpen = ref(false);

// Estado seleccionado para filtrar municipios.
const selectedStateId = ref<number | null>(props.item?.stateId ?? null);

// Valores de los Selects para sincronizar con hidden inputs.
const statusValue = ref<string>(props.item?.status ?? 'active');
const nacionalityValue = ref<string>(props.item?.nacionality ?? 'V');
const genderValue = ref<string>(props.item?.gender ?? 'M');
const municipalityIdValue = ref<string>(
    props.item?.municipalityId ? String(props.item.municipalityId) : '',
);

const filteredMunicipalities = computed<MunicipalityOption[]>(() => {
    if (selectedStateId.value === null) {
        return [];
    }

    return props.availableMunicipalities.filter(
        (m) => m.state_id === selectedStateId.value,
    );
});

// Validación de correo en tiempo real (disponibilidad).
const emailValue = ref<string>(props.item?.email ?? '');
const emailChecking = ref(false);
const emailExists = ref(false);
const emailMessage = ref<string | null>(null);

let emailCheckAbort: AbortController | null = null;
let emailCheckSeq = 0;

const dniValue = ref<string>(props.item?.dni ?? '');
const dniChecking = ref(false);
const dniExists = ref(false);
const dniMessage = ref<string | null>(null);

let dniCheckAbort: AbortController | null = null;
let dniCheckSeq = 0;

// Valores de los Inputs para sincronizar con v-model.
const firstNameValue = ref<string>(props.item?.firstName ?? '');
const lastNameValue = ref<string>(props.item?.lastName ?? '');
const birthDateValue = ref<string>(props.item?.birthDate ?? '');
const phoneMobileValue = ref<string>(props.item?.phoneMobile ?? '');
const phoneLandlineValue = ref<string>(props.item?.phoneLandline ?? '');
const addressValue = ref<string>(props.item?.address ?? '');

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

watch(
    () => editingItem.value?.id ?? null,
    (id) => {
        emailValue.value = id ? (editingItem.value?.email ?? '') : '';
        dniValue.value = id ? (editingItem.value?.dni ?? '') : '';
        emailCheckSeq += 1;
        dniCheckSeq += 1;

        if (emailCheckAbort) {
            emailCheckAbort.abort();
        }

        if (dniCheckAbort) {
            dniCheckAbort.abort();
        }

        emailChecking.value = false;
        dniChecking.value = false;
        resetEmailState();
        resetDniState();
    },
);

watch(emailValue, (value) => {
    if (emailMessage.value !== null || emailExists.value) {
        emailMessage.value = null;
        emailExists.value = false;
    }

    if (value.trim().length > 0) {
        validateEmail();
    }
});

watch(dniValue, (value) => {
    if (dniMessage.value !== null || dniExists.value) {
        dniMessage.value = null;
        dniExists.value = false;
    }

    if (value.trim().length > 0) {
        validateDni();
    }
});

function reloadList() {
    router.get(index().url, {}, {
        preserveState: false,
        preserveScroll: true,
    });
}

function openEditSheet(item: UserModel) {
    editingItem.value = item;
    selectedRoleIds.value = item.role_ids ?? [];
    profileOpen.value = true;
    selectedStateId.value = item.stateId ?? null;
    statusValue.value = item.status ?? 'active';
    nacionalityValue.value = item.nacionality ?? 'V';
    genderValue.value = item.gender ?? 'M';
    municipalityIdValue.value = item.municipalityId ? String(item.municipalityId) : '';
    firstNameValue.value = item.firstName ?? '';
    lastNameValue.value = item.lastName ?? '';
    birthDateValue.value = item.birthDate ?? '';
    phoneMobileValue.value = item.phoneMobile ?? '';
    phoneLandlineValue.value = item.phoneLandline ?? '';
    addressValue.value = item.address ?? '';
    open.value = true;
}

function openCreateSheet() {
    editingItem.value = null;
    selectedRoleIds.value = [];
    profileOpen.value = true;
    selectedStateId.value = null;
    statusValue.value = 'active';
    nacionalityValue.value = 'V';
    genderValue.value = 'M';
    municipalityIdValue.value = '';
    firstNameValue.value = '';
    lastNameValue.value = '';
    birthDateValue.value = '';
    phoneMobileValue.value = '';
    phoneLandlineValue.value = '';
    addressValue.value = '';
    open.value = true;
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

function onPhotoUpdated(url: string | null) {
    if (editingItem.value) {
        editingItem.value = { ...editingItem.value, photoUrl: url };
    }
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

function userRoleClasses(role: UserRole): string {
    const parts: string[] = ['border-transparent'];

    if (role.color_class) {
        parts.push(role.color_class);
    } else {
        parts.push('bg-secondary');
    }

    if (role.text_class) {
        parts.push(role.text_class);
    } else {
        parts.push('text-secondary-foreground');
    }

    return parts.join(' ');
}

function userRoleIcon(role: UserRole): string | null {
    return role.icon_svg ?? null;
}

function resetEmailState(): void {
    emailExists.value = false;
    emailMessage.value = null;
}

function resetDniState(): void {
    dniExists.value = false;
    dniMessage.value = null;
}

function closeSheet(): void {
    open.value = false;
    editingItem.value = null;
    resetEmailState();
    resetDniState();
    emailValue.value = '';
    dniValue.value = '';
}

async function validateEmail(): Promise<void> {
    const value = emailValue.value.trim();

    if (value.length === 0) {
        resetEmailState();

        return;
    }

    if (emailCheckAbort) {
        emailCheckAbort.abort();
    }

    const controller = new AbortController();
    emailCheckAbort = controller;
    const seq = ++emailCheckSeq;

    emailChecking.value = true;

    try {
        const url = `/users/check-email?email=${encodeURIComponent(value)}${
            editingItem.value?.id ? `&ignore_id=${editingItem.value.id}` : ''
        }`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (seq !== emailCheckSeq) {
            return;
        }

        if (!response.ok) {
            resetEmailState();

            return;
        }

        const payload = (await response.json()) as { exists: boolean };

        if (payload.exists) {
            emailExists.value = true;
            emailMessage.value = 'Este correo electrónico ya está registrado.';
        } else {
            resetEmailState();
            emailMessage.value = 'Correo disponible.';
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        resetEmailState();
    } finally {
        if (seq === emailCheckSeq) {
            emailChecking.value = false;
        }
    }
}

async function validateDni(): Promise<void> {
    const value = dniValue.value.trim();

    if (value.length === 0) {
        resetDniState();

        return;
    }

    if (dniCheckAbort) {
        dniCheckAbort.abort();
    }

    const controller = new AbortController();
    dniCheckAbort = controller;
    const seq = ++dniCheckSeq;

    dniChecking.value = true;

    try {
        const url = `/users/check-dni?dni=${encodeURIComponent(value)}${
            editingItem.value?.id ? `&ignore_id=${editingItem.value.id}` : ''
        }`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (seq !== dniCheckSeq) {
            return;
        }

        if (!response.ok) {
            resetDniState();

            return;
        }

        const payload = (await response.json()) as { exists: boolean };

        if (payload.exists) {
            dniExists.value = true;
            dniMessage.value = 'Este número de documento ya está en uso.';
        } else {
            resetDniState();
            dniMessage.value = 'Número de documento disponible.';
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        resetDniState();
    } finally {
        if (seq === dniCheckSeq) {
            dniChecking.value = false;
        }
    }
}

const currentYear = new Date().getFullYear();
const birthDateMin = '1900-01-01';
const birthDateMax = `${currentYear}-12-31`;

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
                <Sheet v-model:open="open" @update:open="(value) => { if (!value) { editingItem.value = null; } }">
                    <SheetTrigger as-child>
                        <Button id="tour-new-btn" @click="openCreateSheet">
                            <Plus class="h-4 w-4" />
                            Nuevo Usuario
                        </Button>
                    </SheetTrigger>
                    <SheetContent class="overflow-y-auto">
                        <SheetHeader>
                            <SheetTitle
                                >{{
                                    editingItem ? 'Editar' : 'Crear'
                                }}
                                Usuario</SheetTitle
                            >
                            <SheetDescription>
                                {{
                                    editingItem
                                        ? 'Actualice los datos del'
                                        : 'Registre un nuevo'
                                }}
                                usuario en el sistema.
                            </SheetDescription>
                        </SheetHeader>
                        <Form
                            :key="editingItem?.id ?? 'create'"
                            v-bind="
                                editingItem
                                    ? update.form(editingItem.id)
                                    : store.form()
                            "
                            class="space-y-6 px-4"
                            v-slot="{ errors, processing }"
                            @success="
                                open = false;
                                editingItem = null;
                            "
                            @error="(e) => { console.error('Form error:', e); }"
                        >
                            <!-- Hidden inputs para sincronizar los Selects de shadcn/ui -->
                            <input type="hidden" name="status" :value="statusValue" />
                            <input type="hidden" name="nacionality" :value="nacionalityValue" />
                            <input type="hidden" name="gender" :value="genderValue" />
                            <input type="hidden" name="state_id" :value="selectedStateId ?? ''" />
                            <input type="hidden" name="municipality_id" :value="municipalityIdValue" />

                            <!-- Sección: Datos del usuario -->
                            <div class="space-y-4">
                                <h3
                                    class="border-b pb-2 text-sm font-semibold text-foreground"
                                >
                                    Datos del usuario
                                </h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-2">
                                        <Label for="email"
                                            >Correo Electrónico
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            v-model="emailValue"
                                            placeholder="usuario@ejemplo.com"
                                            required
                                        />
                                        <p
                                            v-if="emailChecking"
                                            class="text-xs text-muted-foreground"
                                        >
                                            Verificando correo...
                                        </p>
                                        <p
                                            v-else-if="
                                                emailExists && emailMessage
                                            "
                                            class="flex items-start gap-1 text-xs text-destructive"
                                        >
                                            <AlertCircle
                                                class="mt-0.5 h-3.5 w-3.5 flex-shrink-0"
                                            />
                                            <span>{{ emailMessage }}</span>
                                        </p>
                                        <p
                                            v-else-if="
                                                emailMessage && !emailExists
                                            "
                                            class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400"
                                        >
                                            <CheckCircle2
                                                class="h-3.5 w-3.5"
                                            />
                                            <span>{{ emailMessage }}</span>
                                        </p>
                                        <InputError :message="errors.email" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="status"
                                            >Estatus
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Select v-model="statusValue">
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Seleccione"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="option in availableStatuses"
                                                    :key="option.value"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="errors.status"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Collapsible: Perfil del usuario -->
                            <Collapsible
                                v-model:open="profileOpen"
                                class="rounded-md border"
                            >
                                <CollapsibleTrigger
                                    class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold hover:bg-muted/50"
                                >
                                    <span>Perfil del usuario</span>
                                    <ChevronDown
                                        class="h-4 w-4 transition-transform duration-200"
                                        :class="
                                            profileOpen ? 'rotate-180' : ''
                                        "
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent
                                    class="space-y-4 border-t px-4 py-4"
                                >
                                    <div v-if="!editingItem">
                                        <Label>Fotografía</Label>
                                        <p
                                            class="rounded-md border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground"
                                        >
                                            La fotografía se podrá cargar después de
                                            crear el usuario.
                                        </p>
                                    </div>
                                    <div v-else>
                                        <UsersProfilePhotoUploader
                                            :users-profile-id="editingItem.usersProfileId ?? 0"
                                            :initial-photo-url="
                                                editingItem.photoUrl ?? null
                                            "
                                            :upload-url="`/users/${editingItem.id}/photo`"
                                            :destroy-url="`/users/${editingItem.id}/photo`"
                                            @updated="onPhotoUpdated"
                                        />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="first_name"
                                                >Nombres
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="first_name"
                                                name="first_name"
                                                v-model="firstNameValue"
                                                placeholder="Ej. Juan"
                                                required
                                            />
                                            <InputError
                                                :message="errors.first_name"
                                            />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="last_name"
                                                >Apellidos
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="last_name"
                                                name="last_name"
                                                v-model="lastNameValue"
                                                placeholder="Ej. Pérez García"
                                                required
                                            />
                                            <InputError
                                                :message="errors.last_name"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="nacionality"
                                                >Nacionalidad
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Select v-model="nacionalityValue">
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder="Seleccione"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="option in availableNacionalities"
                                                        :key="option.value"
                                                        :value="option.value"
                                                    >
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="errors.nacionality"
                                            />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="dni"
                                                >Número de Documento
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="dni"
                                                name="dni"
                                                v-model="dniValue"
                                                placeholder="12345678"
                                                required
                                            />
                                            <InputError
                                                :message="errors.dni"
                                            />
                                            <p
                                                v-if="dniChecking"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Verificando número de documento...
                                            </p>
                                            <p
                                                v-else-if="
                                                    dniExists && dniMessage
                                                "
                                                class="flex items-start gap-1 text-xs text-destructive"
                                            >
                                                <AlertCircle
                                                    class="mt-0.5 h-3.5 w-3.5 flex-shrink-0"
                                                />
                                                <span>{{ dniMessage }}</span>
                                            </p>
                                            <p
                                                v-else-if="
                                                    dniMessage && !dniExists
                                                "
                                                class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400"
                                            >
                                                <CheckCircle2
                                                    class="h-3.5 w-3.5"
                                                />
                                                <span>{{ dniMessage }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="birth_date"
                                                >Fecha de Nacimiento
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="birth_date"
                                                name="birth_date"
                                                type="date"
                                                v-model="birthDateValue"
                                                :min="birthDateMin"
                                                :max="birthDateMax"
                                                required
                                            />
                                            <InputError
                                                :message="errors.birth_date"
                                            />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="gender"
                                                >Género
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Select v-model="genderValue">
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder="Seleccione"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="option in availableGenders"
                                                        :key="option.value"
                                                        :value="option.value"
                                                    >
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="errors.gender"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="phone_mobile"
                                                >Teléfono Móvil
                                                <span
                                                    class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="phone_mobile"
                                                name="phone_mobile"
                                                v-model="phoneMobileValue"
                                                placeholder="04141234567"
                                                required
                                            />
                                            <InputError
                                                :message="errors.phone_mobile"
                                            />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="phone_landline"
                                                >Teléfono Local</Label
                                            >
                                            <Input
                                                id="phone_landline"
                                                name="phone_landline"
                                                v-model="phoneLandlineValue"
                                                placeholder="02121234567"
                                            />
                                            <InputError
                                                :message="
                                                    errors.phone_landline
                                                "
                                            />
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>

                            <!-- Collapsible: Ubicación geográfica -->
                            <Collapsible
                                v-model:open="geoOpen"
                                class="rounded-md border"
                            >
                                <CollapsibleTrigger
                                    class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold hover:bg-muted/50"
                                >
                                    <span>Ubicación geográfica</span>
                                    <ChevronDown
                                        class="h-4 w-4 transition-transform duration-200"
                                        :class="geoOpen ? 'rotate-180' : ''"
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent
                                    class="space-y-4 border-t px-4 py-4"
                                >
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="state_id">Estado</Label>
                                            <Select v-model="selectedStateId">
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder="Seleccione un estado"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="state in availableStates"
                                                        :key="state.id"
                                                        :value="String(state.id)"
                                                    >
                                                        {{ state.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="errors.state_id"
                                            />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="municipality_id"
                                                >Municipio</Label
                                            >
                                            <Select v-model="municipalityIdValue">
                                                <SelectTrigger>
                                                    <SelectValue
                                                        placeholder="Seleccione un municipio"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="municipality in filteredMunicipalities"
                                                        :key="municipality.id"
                                                        :value="String(municipality.id)"
                                                    >
                                                        {{ municipality.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="errors.municipality_id"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="address">Dirección</Label>
                                        <Input
                                            id="address"
                                            name="address"
                                            v-model="addressValue"
                                            placeholder="Ej. Av. Principal, Edif. Los Andes, Piso 2, Apto 2B"
                                        />
                                        <InputError
                                            :message="errors.address"
                                        />
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>

                            <SheetFooter>
                                <SheetClose as-child>
                                    <Button variant="secondary"
                                        >Cancelar</Button
                                    >
                                </SheetClose>
                                <Button
                                    type="submit"
                                    :disabled="processing || emailExists || dniExists"
                                >
                                    {{ editingItem ? 'Actualizar' : 'Crear' }}
                                </Button>
                            </SheetFooter>
                        </Form>
                    </SheetContent>
                </Sheet>
            </div>
        </div>

        <Dialog
            :open="deleteDialogOpen"
            @update:open="(v) => (deleteDialogOpen = v)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Eliminar Usuario</DialogTitle>
                    <DialogDescription>
                        ¿Está seguro de eliminar "{{
                            itemToDelete?.name
                        }}"? Esta acción no se puede deshacer.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteItem">
                        Eliminar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="resetDialogOpen"
            @update:open="(v) => (resetDialogOpen = v)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Restablecer Contraseña</DialogTitle>
                    <DialogDescription>
                        Enviar una contraseña temporal a "{{
                            itemToReset?.name
                        }}"
                        y solicitar que cambie la contraseña en el primer
                        inicio de sesión.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>
                    <Button @click="resetPassword">
                        Restablecer Contraseña
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="assignRoleOpen"
            @update:open="(v) => (assignRoleOpen = v)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Asignar roles</DialogTitle>
                    <DialogDescription>
                        Seleccione los roles para "{{ roleItem?.name }}".
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2 py-4">
                    <div class="flex items-center justify-between">
                        <Label>Roles</Label>
                        <span class="text-xs text-muted-foreground">
                            {{ selectedRoleIds.length }} seleccionados
                        </span>
                    </div>
                    <div
                        class="max-h-[200px] space-y-1 overflow-y-auto rounded-md border p-2"
                    >
                        <label
                            v-for="role in availableRoles"
                            :key="role.id"
                            class="flex items-center gap-2 rounded-md p-2 hover:bg-muted/50"
                        >
                            <Checkbox
                                :model-value="selectedRoleIds.includes(role.id)"
                                @update:model-value="
                                    (checked) => {
                                        if (checked === true) {
                                            if (
                                                !selectedRoleIds.includes(
                                                    role.id,
                                                )
                                            ) {
                                                selectedRoleIds.push(role.id);
                                            }
                                        } else {
                                            selectedRoleIds =
                                                selectedRoleIds.filter(
                                                    (id) => id !== role.id,
                                                );
                                        }
                                    }
                                "
                            />
                            <span class="text-sm font-medium">{{
                                role.label
                            }}</span>
                        </label>
                    </div>
                    <InputError :message="roleError ?? undefined" />
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>
                    <Button @click="assignRole"> Guardar roles </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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

        <div
            class="mx-3 min-h-0 flex-1 overflow-auto rounded-md border"
            id="tour-table"
        >
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Foto</th>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">
                            Correo Electrónico
                        </th>
                        <th class="px-4 py-3 font-medium">Estatus</th>
                        <th class="px-4 py-3 font-medium">Rol</th>
                        <th class="px-4 py-3 font-medium">Progreso</th>
                        <th
                            class="px-4 py-3 text-right font-medium"
                            id="tour-actions"
                        >
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        class="border-t"
                    >
                        <td class="px-4 py-3">
                            <Avatar class="h-9 w-9">
                                <AvatarImage
                                    v-if="item.photoUrl"
                                    :src="item.photoUrl"
                                    :alt="item.name"
                                />
                                <AvatarFallback>{{
                                    item.name
                                        .split(' ')
                                        .map((n) => n[0])
                                        .join('')
                                        .slice(0, 2)
                                        .toUpperCase()
                                }}</AvatarFallback>
                            </Avatar>
                        </td>
                        <td class="px-4 py-3">{{ item.name }}</td>
                        <td class="px-4 py-3">{{ item.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-if="item.status"
                                class="inline-flex items-center rounded-full border border-transparent px-2.5 py-0.5 text-xs font-semibold"
                                :class="
                                    item.statusColorClass ??
                                    'bg-muted text-muted-foreground'
                                "
                            >
                                {{ item.statusLabel ?? item.status }}
                            </span>
                            <span
                                v-else
                                class="text-xs text-muted-foreground"
                            >
                                —
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="item.roles && item.roles.length > 0"
                                class="flex flex-wrap items-center gap-1"
                            >
                                <span
                                    v-for="role in item.roles"
                                    :key="role.slug"
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    :class="userRoleClasses(role)"
                                >
                                    <span
                                        v-if="userRoleIcon(role)"
                                        class="mr-2 h-4 w-4"
                                        v-html="userRoleIcon(role)"
                                    ></span>
                                    {{ role.name }}
                                </span>
                            </div>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full border border-transparent bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                            >
                                Indefinido
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="group relative flex items-center gap-2">
                                <div
                                    class="h-2 w-24 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 transition-all duration-300"
                                        :style="{
                                            width: `${item.profileCompletion}%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="text-xs tabular-nums text-muted-foreground">
                                    {{ item.profileCompletion }}%
                                </span>
                                <div
                                    class="pointer-events-none absolute top-full left-1/2 z-50 mt-2 hidden w-56 -translate-x-1/2 rounded-md border bg-popover p-3 text-xs text-popover-foreground shadow-lg group-hover:block"
                                >
                                    <p class="mb-2 font-semibold">
                                        {{ item.profileCompletion }}% completado
                                        ({{
                                            item.missingFields.length
                                        }}
                                        de 13 campos vacíos)
                                    </p>
                                    <template v-if="item.missingFields.length > 0">
                                        <p class="mb-1 text-muted-foreground">
                                            Datos faltantes:
                                        </p>
                                        <ul class="max-h-32 list-disc space-y-0.5 overflow-y-auto pl-4">
                                            <li
                                                v-for="field in item.missingFields"
                                                :key="field"
                                            >
                                                {{ field }}
                                            </li>
                                        </ul>
                                    </template>
                                    <p
                                        v-else
                                        class="text-emerald-600 dark:text-emerald-400"
                                    >
                                        ✅ Todos los datos completados
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Acciones"
                                        >
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="openEditSheet(item)"
                                        >
                                            <Pencil class="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openAssignRole(item)"
                                        >
                                            <Shield class="mr-2 h-4 w-4" />
                                            Asignar rol
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="
                                                confirmResetPassword(item)
                                            "
                                        >
                                            <Key class="mr-2 h-4 w-4" />
                                            Restablecer contraseña
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="confirmDelete(item)"
                                        >
                                            <Trash class="mr-2 h-4 w-4" />
                                            Eliminar
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No se encontraron usuarios.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
            id="tour-pagination"
        >
            <div class="text-sm text-muted-foreground">
                Mostrando {{ items.from }} a {{ items.to }} de
                {{ items.total }} resultados.
            </div>
            <div
                class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground"
                        >Por página</span
                    >
                    <Select v-model="perPage">
                        <SelectTrigger class="w-20">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="items.current_page === 1"
                        @click="
                            router.get(
                                index().url,
                                { ...filters, page: items.current_page - 1 },
                                { preserveState: true, preserveScroll: true },
                            )
                        "
                    >
                        Anterior
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="items.current_page === items.last_page"
                        @click="
                            router.get(
                                index().url,
                                { ...filters, page: items.current_page + 1 },
                                { preserveState: true, preserveScroll: true },
                            )
                        "
                    >
                        Siguiente
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
