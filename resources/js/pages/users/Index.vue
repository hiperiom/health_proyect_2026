<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import {
    CircleCheck,
    Key,
    MoreVertical,
    Pencil,
    Plus,
    Search,
    Shield,
    Trash,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
} from '@/routes/users';
import type { RoleOption, UserModel } from '@/types/users';

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
const selectedRole = ref<string>(availableRoles.value[0]?.value ?? '');
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
        roleError.value = errors?.role ?? null;
    },
    { immediate: true },
);

function openEditSheet(item: UserModel) {
    editingItem.value = item;
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
    selectedRole.value = item.role ?? availableRoles.value[0]?.value ?? '';
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

    router.patch(
        update(roleItem.value.id),
        { role: selectedRole.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                assignRoleOpen.value = false;
                roleItem.value = null;
            },
        },
    );
}

function roleLabel(slug: string | null | undefined): string {
    if (!slug) {
        return '—';
    }

    return (
        availableRoles.value.find((role: RoleOption) => role.value === slug)
            ?.label ?? slug
    );
}

function roleClasses(slug: string | null | undefined): string {
    if (!slug) {
        return 'border-transparent bg-muted text-muted-foreground';
    }
    const role = availableRoles.value.find((r: RoleOption) => r.value === slug);

    if (role) {
        const parts: string[] = [];
        if (role.color_class) parts.push(role.color_class);
        if (role.text_class) parts.push(role.text_class);
        // ensure border-transparent if not provided
        parts.push('border-transparent');

        return parts.join(' ');
    }

    return 'border-transparent bg-secondary text-secondary-foreground';
}

function roleIcon(slug: string | null | undefined): string | null {
    if (!slug) {
        return null;
    }

    const role = availableRoles.value.find((r: RoleOption) => r.value === slug);

    return role?.icon_svg ?? null;
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
                <div class="relative w-full sm:w-72">
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
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button>
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
                                <Label for="role">Role</Label>
                                <Select
                                    name="role"
                                    :default-value="
                                        editingItem?.role ??
                                        availableRoles[0]?.value ??
                                        ''
                                    "
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Select role"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="role in availableRoles"
                                            :key="role.value"
                                            :value="role.value"
                                        >
                                            {{ role.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors.role" />
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
                    <DialogTitle>Assign Role</DialogTitle>
                    <DialogDescription>
                        Change the role for "{{ roleItem?.name }}".
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2 py-4">
                    <Label>Role</Label>
                    <Select v-model="selectedRole">
                        <SelectTrigger>
                            <SelectValue placeholder="Select role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="role in availableRoles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="roleError ?? undefined" />
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button @click="assignRole"> Save Role </Button>
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

        <div class="min-h-0 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
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
                        <td class="px-4 py-3">{{ item.name }}</td>
                        <td class="px-4 py-3">{{ item.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                :class="roleClasses(item.role)"
                            >
                                <span
                                    v-if="roleIcon(item.role)"
                                    class="mr-2 h-4 w-4"
                                    v-html="roleIcon(item.role)"
                                ></span>
                                {{ roleLabel(item.role) }}
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
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openAssignRole(item)"
                                        >
                                            <Shield class="mr-2 h-4 w-4" />
                                            Assign role
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="confirmResetPassword(item)"
                                        >
                                            <Key class="mr-2 h-4 w-4" />
                                            Reset password
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
            class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-1 px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
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
