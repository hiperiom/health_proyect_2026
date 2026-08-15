<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { MoreVertical, Pencil, Plus, Search, Trash } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { index, store, update, destroy } from '@/routes/permissions';
import type { PermissionModel } from '@/types/permissions';

type Props = {
    items: {
        data: PermissionModel[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    item?: PermissionModel;
    availableModules?: string[];
    filters?: {
        search?: string;
        module?: string;
        per_page?: number;
    };
};

const props = withDefaults(defineProps<Props>(), {
    availableModules: () => [],
});

const filters = computed<{ search?: string; module?: string; per_page?: number }>(
    () => props.filters ?? {},
);

const availableModules = computed<string[]>(() => props.availableModules);
const selectedModule = ref<string>(
    props.filters?.module && props.filters.module !== ''
        ? props.filters.module
        : 'all',
);

const open = ref(false);
const editingItem = ref<PermissionModel | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<PermissionModel | null>(null);
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

    if (selectedModule.value !== 'all') {
        query.module = selectedModule.value;
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

watch(selectedModule, () => applyFilters());
watch(perPage, () => applyFilters());

function openEditSheet(item: PermissionModel) {
    editingItem.value = item;
    open.value = true;
}

function confirmDelete(item: PermissionModel) {
    itemToDelete.value = item;
    deleteDialogOpen.value = true;
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
</script>

<template>
    <Head title="Permisos" />

    <div class="flex h-full flex-col space-y-6">
        <div
            class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Permisos"
                description="Gestiona los permisos del sistema"
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre o slug..."
                        class="pl-8"
                    />
                </div>
                <Select v-model="selectedModule">
                    <SelectTrigger class="w-full sm:w-40">
                        <SelectValue placeholder="Todos los módulos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos los módulos</SelectItem>
                        <SelectItem
                            v-for="module in availableModules"
                            :key="module"
                            :value="module"
                        >
                            {{ module }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4" />
                            Nuevo Permiso
                        </Button>
                    </SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle
                                >{{
                                    editingItem ? 'Editar' : 'Crear'
                                }}
                                Permiso</SheetTitle
                            >
                            <SheetDescription>
                                {{
                                    editingItem
                                        ? 'Actualiza el permiso.'
                                        : 'Crea un nuevo permiso.'
                                }}
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
                                <Label for="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    :default-value="editingItem?.name"
                                    placeholder="Ver"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    :default-value="editingItem?.slug"
                                    placeholder="module.action"
                                    required
                                />
                                <InputError :message="errors.slug" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="module">Módulo</Label>
                                <Input
                                    id="module"
                                    name="module"
                                    :default-value="editingItem?.module"
                                    placeholder="users"
                                    required
                                />
                                <InputError :message="errors.module" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Descripción</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    :default-value="
                                        editingItem?.description ?? ''
                                    "
                                    placeholder="Qué permite este permiso"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none"
                                />
                                <InputError :message="errors.description" />
                            </div>
                            <SheetFooter>
                                <SheetClose as-child>
                                    <Button variant="secondary">Cancelar</Button>
                                </SheetClose>
                                <Button type="submit" :disabled="processing">
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
                    <DialogTitle>Eliminar Permiso</DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que quieres eliminar "{{
                            itemToDelete?.name
                        }}" ({{ itemToDelete?.slug }})? Esta acción no se
                        puede deshacer.
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

        <div class="min-h-0 mx-3 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">Módulo</th>
                        <th class="px-4 py-3 font-medium">Descripción</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.slug }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border border-transparent bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground"
                            >
                                {{ item.module }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.description }}
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
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No se encontraron permisos.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="text-sm text-muted-foreground">
                Mostrando {{ items.from }} a {{ items.to }} de
                {{ items.total }} resultados.
            </div>
            <div
                class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Por página</span>
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
                                { ...filters.value, page: items.current_page - 1 },
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
                                { ...filters.value, page: items.current_page + 1 },
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
