<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { MoreVertical, Pencil, Plus, Search, Shield, Trash } from '@lucide/vue';
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
    assignPermissions as assignPermissionsRoute,
} from '@/routes/roles';
import type { RoleModel } from '@/types/roles';

type PermissionOption = {
    id: number;
    name: string;
    slug: string;
    module: string;
    description: string | null;
};

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
    item?: RoleModel & { permission_ids?: number[] };
    allPermissions?: PermissionOption[];
    filters?: {
        search?: string;
        per_page?: number;
    };
};

const props = withDefaults(defineProps<Props>(), {
    allPermissions: () => [],
});

const availablePermissions = computed<PermissionOption[]>(
    () => props.allPermissions,
);

const groupedPermissions = computed<Record<string, PermissionOption[]>>(() => {
    const map: Record<string, PermissionOption[]> = {};
    for (const permission of availablePermissions.value) {
        if (!map[permission.module]) {
            map[permission.module] = [];
        }
        map[permission.module].push(permission);
    }

    return map;
});

const permissionSearch = ref<string>('');

const filteredGroupedPermissions = computed<Record<string, PermissionOption[]>>(
    () => {
        const search = permissionSearch.value.trim().toLowerCase();
        if (search === '') {
            return groupedPermissions.value;
        }

        const result: Record<string, PermissionOption[]> = {};
        for (const [module, perms] of Object.entries(
            groupedPermissions.value,
        )) {
            if (module.toLowerCase().includes(search)) {
                result[module] = perms;

                continue;
            }

            const matching = perms.filter(
                (p) =>
                    p.name.toLowerCase().includes(search) ||
                    p.slug.toLowerCase().includes(search) ||
                    (p.description ?? '').toLowerCase().includes(search),
            );
            if (matching.length > 0) {
                result[module] = matching;
            }
        }

        return result;
    },
);

const filteredTotalCount = computed<number>(() =>
    Object.values(filteredGroupedPermissions.value).reduce(
        (sum, perms) => sum + perms.length,
        0,
    ),
);

const totalPermissionCount = computed<number>(
    () => availablePermissions.value.length,
);

const open = ref(false);
const editingItem = ref<Props['item'] | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<RoleModel | null>(null);
const assignPermissionsOpen = ref(false);
const permissionsItem = ref<RoleModel | null>(null);
const selectedPermissionIds = ref<number[]>([]);
const permissionsError = ref<string | null>(null);
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
        permissionsError.value = errors?.['permission_ids'] ?? null;
    },
    { immediate: true },
);

function openEditSheet(item: RoleModel) {
    editingItem.value = item;
    open.value = true;
}

function confirmDelete(item: RoleModel) {
    itemToDelete.value = item;
    deleteDialogOpen.value = true;
}

function openAssignPermissions(
    item: RoleModel & { permission_ids?: number[] },
) {
    permissionsItem.value = item;
    selectedPermissionIds.value = item.permission_ids ?? [];
    permissionSearch.value = '';
    assignPermissionsOpen.value = true;
}

function togglePermission(id: number) {
    const idx = selectedPermissionIds.value.indexOf(id);
    if (idx >= 0) {
        selectedPermissionIds.value.splice(idx, 1);
    } else {
        selectedPermissionIds.value.push(id);
    }
}

function isPermissionSelected(id: number): boolean {
    return selectedPermissionIds.value.includes(id);
}

function toggleModulePermissions(module: string, selectAll: boolean) {
    const modulePermissionIds = (groupedPermissions.value[module] ?? []).map(
        (p) => p.id,
    );

    if (selectAll) {
        const merged = new Set<number>([
            ...selectedPermissionIds.value,
            ...modulePermissionIds,
        ]);
        selectedPermissionIds.value = Array.from(merged);
    } else {
        selectedPermissionIds.value = selectedPermissionIds.value.filter(
            (id) => !modulePermissionIds.includes(id),
        );
    }
}

function isModuleFullySelected(module: string): boolean {
    const modulePermissionIds = (groupedPermissions.value[module] ?? []).map(
        (p) => p.id,
    );

    if (modulePermissionIds.length === 0) {
        return false;
    }

    return modulePermissionIds.every((id) =>
        selectedPermissionIds.value.includes(id),
    );
}

function isModulePartiallySelected(module: string): boolean {
    const modulePermissionIds = (groupedPermissions.value[module] ?? []).map(
        (p) => p.id,
    );
    const selected = modulePermissionIds.filter((id) =>
        selectedPermissionIds.value.includes(id),
    );

    return selected.length > 0 && selected.length < modulePermissionIds.length;
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

function savePermissions() {
    if (!permissionsItem.value) {
        return;
    }

    router.post(
        assignPermissionsRoute(permissionsItem.value.id).url,
        { permission_ids: selectedPermissionIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                assignPermissionsOpen.value = false;
                permissionsItem.value = null;
                permissionSearch.value = '';
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
</script>

<template>
    <Head title="Roles" />

    <div class="flex h-full flex-col space-y-6">
        <div
            class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Roles"
                description="Manage roles and their permissions"
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search by name or slug..."
                        class="pl-8"
                    />
                </div>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4" />
                            New Role
                        </Button>
                    </SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle
                                >{{
                                    editingItem ? 'Edit' : 'Create'
                                }}
                                Role</SheetTitle
                            >
                            <SheetDescription>
                                {{ editingItem ? 'Update' : 'Create a new' }}
                                role.
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
                                    placeholder="Role name"
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
                                    placeholder="role-slug"
                                    required
                                />
                                <InputError :message="errors.slug" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="color_class"
                                    >Color class (Tailwind)</Label
                                >
                                <Input
                                    id="color_class"
                                    name="color_class"
                                    :default-value="
                                        editingItem?.color_class ?? ''
                                    "
                                    placeholder="bg-blue-100"
                                />
                                <InputError :message="errors.color_class" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="text_class"
                                    >Text class (Tailwind)</Label
                                >
                                <Input
                                    id="text_class"
                                    name="text_class"
                                    :default-value="
                                        editingItem?.text_class ?? ''
                                    "
                                    placeholder="text-blue-800"
                                />
                                <InputError :message="errors.text_class" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="icon_svg">Icon (inline SVG)</Label>
                                <textarea
                                    id="icon_svg"
                                    name="icon_svg"
                                    :default-value="editingItem?.icon_svg ?? ''"
                                    placeholder="<svg>...</svg>"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none"
                                />
                                <InputError :message="errors.icon_svg" />
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
                    <DialogTitle>Delete Role</DialogTitle>
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
            :open="assignPermissionsOpen"
            @update:open="(v) => (assignPermissionsOpen = v)"
        >
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Assign Permissions</DialogTitle>
                    <DialogDescription>
                        Select the permissions that
                        <strong>{{ permissionsItem?.name }}</strong>
                        will have.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="permissionSearch"
                                type="search"
                                placeholder="Filter by module or permission (e.g. 'users', 'create')..."
                                class="pl-8"
                            />
                        </div>
                        <div
                            class="rounded-md border bg-muted/40 px-3 py-1 text-xs font-medium whitespace-nowrap text-muted-foreground"
                        >
                            {{ selectedPermissionIds.length }} /
                            {{ totalPermissionCount }} selected
                        </div>
                    </div>

                    <InputError :message="permissionsError ?? undefined" />

                    <div class="max-h-[55vh] space-y-4 overflow-y-auto pr-1">
                        <div
                            v-for="(
                                perms, module
                            ) in filteredGroupedPermissions"
                            :key="module"
                            class="space-y-2"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <h4 class="text-sm font-semibold capitalize">
                                    {{ module }}
                                    <span
                                        class="ml-1 text-xs text-muted-foreground"
                                    >
                                        ({{ perms.length }})
                                    </span>
                                </h4>
                                <div class="flex gap-1">
                                    <button
                                        v-if="!isModuleFullySelected(module)"
                                        type="button"
                                        class="text-xs text-primary hover:underline"
                                        @click="
                                            toggleModulePermissions(
                                                module,
                                                true,
                                            )
                                        "
                                    >
                                        Select all
                                    </button>
                                    <button
                                        v-if="
                                            isModulePartiallySelected(module) ||
                                            isModuleFullySelected(module)
                                        "
                                        type="button"
                                        class="text-xs text-muted-foreground hover:underline"
                                        @click="
                                            toggleModulePermissions(
                                                module,
                                                false,
                                            )
                                        "
                                    >
                                        Deselect all
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1 pl-1">
                                <label
                                    v-for="permission in perms"
                                    :key="permission.id"
                                    class="flex items-start gap-2 rounded-md p-2 hover:bg-muted/50"
                                >
                                    <Checkbox
                                        :model-value="
                                            isPermissionSelected(permission.id)
                                        "
                                        @update:model-value="
                                            togglePermission(permission.id)
                                        "
                                    />
                                    <div class="flex-1">
                                        <div
                                            class="flex items-center gap-2 text-sm font-medium"
                                        >
                                            {{ permission.name }}
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                ({{ permission.slug }})
                                            </span>
                                        </div>
                                        <div
                                            v-if="permission.description"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ permission.description }}
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div
                            v-if="filteredTotalCount === 0"
                            class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                        >
                            No permissions match the filter
                            <strong v-if="permissionSearch"
                                >"{{ permissionSearch }}"</strong
                            >.
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button @click="savePermissions"> Save Permissions </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div class="min-h-0 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 text-right font-medium">
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
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                :class="roleBadgeClasses(item)"
                            >
                                <span
                                    v-if="item.icon_svg"
                                    class="mr-2 h-4 w-4"
                                    v-html="item.icon_svg"
                                />
                                {{ item.name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.slug }}
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
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openAssignPermissions(item)"
                                        >
                                            <Shield class="mr-2 h-4 w-4" />
                                            Assign Permissions
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="confirmDelete(item)"
                                        >
                                            <Trash class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length">
                        <td
                            colspan="3"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No roles found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
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
