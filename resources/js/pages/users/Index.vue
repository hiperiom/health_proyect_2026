<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
// 1. Importar el icono de ayuda (agréguelo a su import existente de lucide)
import { CircleCheck, Key, MoreVertical, Pencil, Plus, Search, Shield, Trash, HelpCircle } from '@lucide/vue';

// 2. Importar driver.js y sus estilos
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

// ... (su código existente de refs, watchers, etc.) ...

// 3. Definir la función que inicia el Tour
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
                    description: 'Escribe el nombre o correo para filtrar la lista en tiempo real. El sistema aplica un debounce de 300ms para optimizar la búsqueda.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-filter',
                popover: {
                    title: '🛡️ Filtrar por Rol',
                    description: 'Selecciona un rol específico (Admin, User, etc.) para ver únicamente los usuarios que poseen ese perfil.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-new-btn',
                popover: {
                    title: '➕ Crear Nuevo Usuario',
                    description: 'Haz clic aquí para abrir el panel lateral (Sheet) y registrar un nuevo usuario en el sistema.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-table',
                popover: {
                    title: '📋 Tabla de Registros',
                    description: 'Aquí se listan todos los usuarios. Puedes ver su nombre, correo y el rol asignado con su respectiva insignia de color.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                element: '#tour-actions',
                popover: {
                    title: '⚙️ Acciones por Usuario',
                    description: 'Usa este menú (icono de 3 puntos) para Editar, Asignar Rol, Resetear Contraseña o Eliminar un usuario específico.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-pagination',
                popover: {
                    title: '📄 Paginación y Controles',
                    description: 'Navega entre las páginas y ajusta la cantidad de registros que deseas ver por página (10, 50, 100).',
                    side: 'top',
                    align: 'end',
                },
            },
        ],
    });

    driverObj.drive();
};
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    index,
    store,
    update,
    destroy,
    resetPassword as resetPasswordRoute,
    assignRoles as assignRolesRoute,
} from '@/routes/users';
import type { RoleOption, UserModel, UserRole } from '@/types/users';

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
    filters?: {
        search?: string;
        role?: string;
        per_page?: number;
    };
};

const props = withDefaults(defineProps<Props>(), {
    availableRoles: () => [],
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
    () => page.props.errors,
    (errors: Record<string, string> | undefined) => {
        roleError.value = errors?.role_ids ?? null;
    },
    { immediate: true },
);

function openEditSheet(item: UserModel) {
    editingItem.value = item;
    selectedRoleIds.value = item.role_ids ?? [];
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
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-col space-y-6">
        <div
            class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Users"
                description="Manage users and their roles"
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72" id="tour-search">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search by name or email..."
                        class="pl-8"
                    />
                </div>
                <div id="tour-filter">
                <Select v-model="roleFilter">
                    <SelectTrigger class="w-full sm:w-40">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All roles</SelectItem>
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
                <Button variant="outline" size="icon"  @click="startTour" title="Guía del módulo">
                    <HelpCircle class="h-4 w-4" />
                </Button>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button id="tour-new-btn">
                            <Plus class="h-4 w-4" />
                            New User
                        </Button>
                    </SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle
                                >{{
                                    editingItem ? 'Edit' : 'Create'
                                }}
                                User</SheetTitle
                            >
                            <SheetDescription>
                                {{ editingItem ? 'Update' : 'Create a new' }}
                                user account.
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
                        >
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    :default-value="editingItem?.name"
                                    placeholder="Full name"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    :default-value="editingItem?.email"
                                    placeholder="email@example.com"
                                    required
                                />
                                <InputError :message="errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Roles</Label>
                                <div class="space-y-1 max-h-[200px] overflow-y-auto rounded-md border p-2">
                                    <label
                                        v-for="role in availableRoles"
                                        :key="role.value"
                                        class="flex items-center gap-2 rounded-md p-2 hover:bg-muted/50"
                                    >
                                        <Checkbox
                                            :model-value="
                                                selectedRoleIds.includes(
                                                    role.value,
                                                )
                                            "
                                            @update:model-value="
                                                (checked) => {
                                                    if (checked === true) {
                                                        if (
                                                            !selectedRoleIds.includes(
                                                                role.value,
                                                            )
                                                        ) {
                                                            selectedRoleIds.push(
                                                                role.value,
                                                            );
                                                        }
                                                    } else {
                                                        selectedRoleIds =
                                                            selectedRoleIds.filter(
                                                                (id) =>
                                                                    id !== role.value,
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
                                <InputError :message="errors.role_ids" />
                            </div>
                            <SheetFooter>
                                <SheetClose as-child>
                                    <Button variant="secondary">Cancel</Button>
                                </SheetClose>
                                <Button type="submit" :disabled="processing">
                                    {{ editingItem ? 'Update' : 'Create' }}
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
                    <DialogTitle>Delete User</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{
                            itemToDelete?.name
                        }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteItem">
                        Delete
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
                    <DialogTitle>Reset Password</DialogTitle>
                    <DialogDescription>
                        Send a temporary password to "{{ itemToReset?.name }}"
                        and require them to set a new password on first login.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button @click="resetPassword"> Reset Password </Button>
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
                        Selecciona los roles para "{{ roleItem?.name }}".
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2 py-4">
                    <div class="flex items-center justify-between">
                        <Label>Roles</Label>
                        <span class="text-xs text-muted-foreground">
                            {{ selectedRoleIds.length }} seleccionados
                        </span>
                    </div>
                    <div class="space-y-1 max-h-[200px] overflow-y-auto rounded-md border p-2">
                        <label
                            v-for="role in availableRoles"
                            :key="role.id"
                            class="flex items-center gap-2 rounded-md p-2 hover:bg-muted/50"
                        >
                            <Checkbox
                                :model-value="
                                    selectedRoleIds.includes(role.id)
                                "
                                @update:model-value="
                                    (checked) => {
                                        if (checked === true) {
                                            if (
                                                !selectedRoleIds.includes(
                                                    role.id,
                                                )
                                            ) {
                                                    selectedRoleIds.push(
                                                        role.id,
                                                    );
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
            <AlertTitle>User created successfully</AlertTitle>
            <AlertDescription>
                The temporary password for this user is:
                <strong class="mt-2 block font-mono text-lg">{{
                    temporaryPassword
                }}</strong>
                <p class="mt-2 text-sm">
                    Share this password securely with the user. They will be
                    required to change it on first login.
                </p>
            </AlertDescription>
        </Alert>

        <div class="min-h-0 mx-3 flex-1 overflow-auto rounded-md border" id="tour-table">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 text-right font-medium" id="tour-actions">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        class="border-t"
                    >
                        <td class="px-4 py-3">{{ item.name }}</td>
                        <td class="px-4 py-3">{{ item.email }}</td>
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
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label="Actions"
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
                                            @click="confirmResetPassword(item)"
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
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-1 px-3 py-3 sm:flex-row sm:items-center sm:justify-between" id="tour-pagination"
        >
            <div class="text-sm text-muted-foreground">
                Showing {{ items.from }} to {{ items.to }} of
                {{ items.total }} results.
            </div>
            <div
                class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Per page</span>
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
                        Previous
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
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
