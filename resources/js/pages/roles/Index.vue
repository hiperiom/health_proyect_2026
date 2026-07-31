<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { LayoutGrid, MoreVertical, Pencil, Plus, Search, Shield, Trash } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import RoleColorPicker from '@/components/RoleColorPicker.vue';
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
    assignModules as assignModulesRoute,
    assignModulePermissions as assignModulePermissionsRoute,
} from '@/routes/roles';
import type { ModuleWithPermissions, RoleModel } from '@/types/roles';

const page = usePage();

type Props = {
    items: {
        data: RoleModel[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    item?: RoleModel & { permission_ids?: number[]; module_ids?: number[] };
    allModules?: ModuleWithPermissions[];
    filters?: { search?: string; per_page?: number };
};

const props = withDefaults(defineProps<Props>(), {
    allModules: () => [],
});

const availableModules = computed(() => props.allModules);

const moduleSearch = ref('');

const filteredModules = computed<ModuleWithPermissions[]>(() => {
    const search = moduleSearch.value.trim().toLowerCase();

    if (search === '') {
        return availableModules.value;
    }

    return availableModules.value.filter((m) => {
        const label = (m.display_name ?? m.name).toLowerCase();

        return (
            label.includes(search) ||
            m.name.toLowerCase().includes(search) ||
            (m.description ?? '').toLowerCase().includes(search)
        );
    });
});

const open = ref(false);
const editingItem = ref<Props['item'] | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<RoleModel | null>(null);

const assignModulesOpen = ref(false);
const modulesItem = ref<RoleModel | null>(null);
const selectedModuleIds = ref<number[]>([]);
const modulesError = ref<string | null>(null);

const modulePermissionsOpen = ref(false);
const modulePermissionsTarget = ref<ModuleWithPermissions | null>(null);
const modulePermissionIds = ref<number[]>([]);
const modulePermissionsError = ref<string | null>(null);

const search = ref<string>(props.filters?.search ?? '');
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

watch(perPage, () => applyFilters());

watch(
    () => page.props.errors,
    (errors: Record<string, string> | undefined) => {
        modulesError.value = errors?.['module_ids'] ?? null;
        modulePermissionsError.value = errors?.['permission_ids'] ?? null;
    },
    { immediate: true },
);

function openEditSheet(item: RoleModel) {
    editingItem.value = item;
    open.value = true;
}

function openCreateSheet() {
    editingItem.value = null;
    open.value = true;
}

function confirmDelete(item: RoleModel) {
    itemToDelete.value = item;
    deleteDialogOpen.value = true;
}

function openAssignModules(
    item: RoleModel & { module_ids?: number[] },
) {
    modulesItem.value = item;
    selectedModuleIds.value = item.module_ids ?? [];
    moduleSearch.value = '';
    assignModulesOpen.value = true;
}

function openModulePermissions(
    item: RoleModel & { permission_ids?: number[]; module_ids?: number[] },
    module: ModuleWithPermissions,
) {
    modulesItem.value = item;
    selectedModuleIds.value = item.module_ids ?? [];
    modulePermissionsTarget.value = module;

    const allRolePerms = item.permission_ids ?? [];

    modulePermissionIds.value = module.permissions.filter((p) =>
        allRolePerms.includes(p),
    );

    modulePermissionsOpen.value = true;
}

function toggleModuleSelected(id: number) {
    const idx = selectedModuleIds.value.indexOf(id);

    if (idx >= 0) {
        selectedModuleIds.value.splice(idx, 1);
    } else {
        selectedModuleIds.value.push(id);
    }
}

function isModuleSelected(id: number): boolean {
    return selectedModuleIds.value.includes(id);
}

function toggleModulePermission(id: number) {
    const idx = modulePermissionIds.value.indexOf(id);

    if (idx >= 0) {
        modulePermissionIds.value.splice(idx, 1);
    } else {
        modulePermissionIds.value.push(id);
    }
}

function isModulePermissionSelected(id: number): boolean {
    return modulePermissionIds.value.includes(id);
}

function isAllModulePermissionsSelected(): boolean {
    if (!modulePermissionsTarget.value) {
        return false;
    }

    return (
        modulePermissionIds.value.length ===
        modulePermissionsTarget.value.permissions.length
    );
}

function toggleAllModulePermissions() {
    if (!modulePermissionsTarget.value) {
        return;
    }

    const allIds = modulePermissionsTarget.value.permissions;

    if (modulePermissionIds.value.length === allIds.length) {
        modulePermissionIds.value = [];
    } else {
        modulePermissionIds.value = [...allIds];
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

function saveModules() {
    if (!modulesItem.value) {
        return;
    }

    modulesError.value = null;

    router.patch(
        assignModulesRoute(modulesItem.value.id),
        { module_ids: selectedModuleIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                assignModulesOpen.value = false;
                modulesItem.value = null;
            },
            onError: (errors: Record<string, string>) => {
                modulesError.value =
                    errors?.module_ids ??
                    Object.values(errors)[0] ??
                    'No se pudieron guardar los módulos.';
            },
        },
    );
}

function saveModulePermissions() {
    if (!modulesItem.value || !modulePermissionsTarget.value) {
        return;
    }

    modulePermissionsError.value = null;

    router.patch(
        assignModulePermissionsRoute(modulesItem.value.id),
        {
            module_id: modulePermissionsTarget.value.id,
            permission_ids: modulePermissionIds.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                modulePermissionsOpen.value = false;
                modulePermissionsTarget.value = null;
            },
            onError: (errors: Record<string, string>) => {
                modulePermissionsError.value =
                    errors?.permission_ids ??
                    Object.values(errors)[0] ??
                    'No se pudieron guardar los permisos.';
            },
        },
    );
}

function roleBadgeClasses(item: RoleModel): string {
    if (item.color_class || item.text_class) {
        return [item.color_class, item.text_class, 'border-transparent']
            .filter(Boolean)
            .join(' ');
    }

    return 'border-transparent bg-secondary text-secondary-foreground';
}

function moduleBadgeClasses(m: {
    color_class?: string | null;
    text_class?: string | null;
}): string {
    if (m.color_class || m.text_class) {
        return [m.color_class, m.text_class, 'border-transparent']
            .filter(Boolean)
            .join(' ');
    }

    return 'border-transparent bg-muted text-muted-foreground';
}
</script>
<template>
    <Head title="Roles" />
    <div class="flex h-full flex-col space-y-6">
        <div class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading variant="small" title="Roles" description="Administra roles, módulos y sus permisos" />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72">
                    <Search class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" type="search" placeholder="Buscar por nombre o slug..." class="pl-8" />
                </div>
                <Sheet v-model:open="open" @update:open="(v) => { if (v && !editingItem) editingItem = null; open = v; }">
                    <SheetTrigger as-child><Button @click="openCreateSheet()"><Plus class="h-4 w-4" />Nuevo rol</Button></SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle>{{ editingItem ? 'Editar' : 'Crear' }} rol</SheetTitle>
                            <SheetDescription>{{ editingItem ? 'Modifica los datos del rol.' : 'Crea un nuevo rol en el sistema.' }}</SheetDescription>
                        </SheetHeader>
                        <Form :key="editingItem?.id ?? 'create'" v-bind="editingItem ? update.form(editingItem.id) : store.form()" class="space-y-6 px-4" v-slot="{ errors, processing }" @success="open = false; editingItem = null">
                            <div class="grid gap-2"><Label for="name">Nombre</Label><Input id="name" name="name" :default-value="editingItem?.name" placeholder="Nombre del rol" required /><InputError :message="errors.name" /></div>
                            <div class="grid gap-2"><Label for="slug">Slug</Label><Input id="slug" name="slug" :default-value="editingItem?.slug" placeholder="rol-slug" required /><InputError :message="errors.slug" /></div>
                            <RoleColorPicker :model-value="editingItem?.color_class ?? null" label="Color de fondo" name="color_class" variant="bg" :required="false" />
                            <InputError :message="errors.color_class" />
                            <RoleColorPicker :model-value="editingItem?.text_class ?? null" label="Color de texto" name="text_class" variant="text" :required="false" />
                            <InputError :message="errors.text_class" />
                            <div class="grid gap-2"><Label for="icon_svg">Icono (SVG en línea)</Label><textarea id="icon_svg" name="icon_svg" :default-value="editingItem?.icon_svg ?? ''" placeholder="<svg>...</svg>" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none" /><InputError :message="errors.icon_svg" /></div>
                            <SheetFooter><SheetClose as-child><Button variant="secondary">Cancelar</Button></SheetClose><Button type="submit" :disabled="processing">{{ editingItem ? 'Actualizar' : 'Crear' }}</Button></SheetFooter>
                        </Form>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
        <Dialog :open="deleteDialogOpen" @update:open="(v) => (deleteDialogOpen = v)">
            <DialogContent>
                <DialogHeader><DialogTitle>Eliminar rol</DialogTitle><DialogDescription>¿Estás seguro de que quieres eliminar "{{ itemToDelete?.name }}"? Esta acción no se puede deshacer.</DialogDescription></DialogHeader>
                <DialogFooter class="gap-2"><DialogClose as-child><Button variant="secondary">Cancelar</Button></DialogClose><Button variant="destructive" @click="deleteItem">Eliminar</Button></DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog :open="assignModulesOpen" @update:open="(v) => (assignModulesOpen = v)">
            <DialogContent class="max-w-2xl">
                <DialogHeader><DialogTitle>Asignar módulos</DialogTitle><DialogDescription>Selecciona los módulos a los que "{{ modulesItem?.name }}" tendrá acceso.</DialogDescription></DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1"><Search class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="moduleSearch" type="search" placeholder="Buscar módulo..." class="pl-8" /></div>
                        <div class="rounded-md border bg-muted/40 px-3 py-1 text-xs font-medium whitespace-nowrap text-muted-foreground">{{ selectedModuleIds.length }} / {{ availableModules.length }} módulos</div>
                    </div>
                    <InputError :message="modulesError ?? undefined" />
                    <div class="max-h-[55vh] space-y-2 overflow-y-auto pr-1">
                        <div v-for="m in filteredModules" :key="m.id" class="flex items-center justify-between gap-2 rounded-md border p-2">
                            <label class="flex flex-1 cursor-pointer items-center gap-2">
                                <Checkbox :model-value="isModuleSelected(m.id)" @update:model-value="toggleModuleSelected(m.id)" />
                                <div class="flex-1"><div class="text-sm font-medium">{{ m.display_name ?? m.name }}</div><div v-if="m.description" class="text-xs text-muted-foreground">{{ m.description }}</div></div>
                            </label>
                            <Button variant="ghost" size="sm" @click="modulesItem && openModulePermissions(modulesItem, m)"><Shield class="mr-2 h-4 w-4" />Permisos</Button>
                        </div>
                        <div v-if="filteredModules.length === 0" class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">No hay módulos que coincidan.</div>
                    </div>
                </div>
                <DialogFooter class="gap-2"><DialogClose as-child><Button variant="secondary">Cancelar</Button></DialogClose><Button @click="saveModules">Guardar módulos</Button></DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog :open="modulePermissionsOpen" @update:open="(v) => (modulePermissionsOpen = v)">
            <DialogContent>
                <DialogHeader><DialogTitle>Permisos de {{ modulePermissionsTarget?.display_name ?? modulePermissionsTarget?.name }}</DialogTitle><DialogDescription>Selecciona los permisos habilitados para este módulo en el rol "{{ modulesItem?.name }}".</DialogDescription></DialogHeader>
                <div class="space-y-4 py-2">
                    <InputError :message="modulePermissionsError ?? undefined" />
                    <div v-if="modulePermissionsTarget" class="space-y-1">
                        <div class="flex items-center justify-between">
                            <Label>Permisos</Label>
                            <button type="button" class="text-xs text-primary hover:underline" @click="toggleAllModulePermissions">{{ isAllModulePermissionsSelected() ? 'Desmarcar todos' : 'Marcar todos' }}</button>
                        </div>
                        <div class="max-h-[50vh] space-y-1 overflow-y-auto rounded-md border p-2">
                            <label v-for="pid in modulePermissionsTarget.permissions" :key="pid" class="flex items-center gap-2 rounded-md p-2 hover:bg-muted/50">
                                <Checkbox :model-value="isModulePermissionSelected(pid)" @update:model-value="toggleModulePermission(pid)" />
                                <span class="text-sm font-medium">Permiso #{{ pid }}</span>
                            </label>
                            <div v-if="modulePermissionsTarget.permissions.length === 0" class="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground">Este módulo no tiene permisos definidos.</div>
                        </div>
                        <div class="text-xs text-muted-foreground">{{ modulePermissionIds.length }} / {{ modulePermissionsTarget.permissions.length }} seleccionados</div>
                    </div>
                </div>
                <DialogFooter class="gap-2"><DialogClose as-child><Button variant="secondary">Cancelar</Button></DialogClose><Button @click="saveModulePermissions">Guardar permisos</Button></DialogFooter>
            </DialogContent>
        </Dialog>
        <div class="min-h-0 mx-3 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">Módulos</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold" :class="roleBadgeClasses(item)">
                                <span v-if="item.icon_svg" class="mr-2 h-4 w-4" v-html="item.icon_svg" />
                                {{ item.name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ item.slug }}</td>
                        <td class="px-4 py-3">
                            <div v-if="item.modules && item.modules.length > 0" class="flex flex-wrap gap-1">
                                <span v-for="m in item.modules" :key="m.id" class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium" :class="moduleBadgeClasses(m)">{{ m.display_name ?? m.name }}</span>
                            </div>
                            <span v-else class="text-xs text-muted-foreground">Sin módulos</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" aria-label="Actions"><MoreVertical class="h-4 w-4" /></Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openEditSheet(item)"><Pencil class="mr-2 h-4 w-4" />Editar</DropdownMenuItem>
                                        <DropdownMenuItem @click="openAssignModules(item)"><LayoutGrid class="mr-2 h-4 w-4" />Asignar módulos</DropdownMenuItem>
                                        <DropdownMenuItem @click="confirmDelete(item)"><Trash class="mr-2 h-4 w-4" />Eliminar</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length"><td colspan="4" class="px-4 py-8 text-center text-muted-foreground">No se encontraron roles.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-muted-foreground">Mostrando del {{ items.from }} al {{ items.to }} de {{ items.total }} resultados.</div>
            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2"><span class="text-sm text-muted-foreground">Por página</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="w-20"><SelectValue placeholder="Por página" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="items.current_page === 1" @click="router.get(index().url, { ...filters, page: items.current_page - 1 }, { preserveState: true, preserveScroll: true })">Anterior</Button>
                    <Button variant="outline" size="sm" :disabled="items.current_page === items.last_page" @click="router.get(index().url, { ...filters, page: items.current_page + 1 }, { preserveState: true, preserveScroll: true })">Siguiente</Button>
                </div>
            </div>
        </div>
    </div>
</template>
