<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { MoreVertical, Pencil, Plus, Search, Trash } from '@lucide/vue';
import { ref, watch } from 'vue';
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
import { index, store, update, destroy } from '@/routes/modules';
import type { ModuleModel } from '@/types/modules';

usePage();

type Props = {
    items: {
        data: ModuleModel[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    item?: ModuleModel;
    filters?: {
        search?: string;
        per_page?: number;
    };
};

const props = defineProps<Props>();

const open = ref(false);
const editingItem = ref<ModuleModel | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<ModuleModel | null>(null);
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

function openEditSheet(item: ModuleModel) {
    editingItem.value = item;
    open.value = true;
}
function confirmDelete(item: ModuleModel) {
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
    <Head title="Modules" />
    <div class="flex h-full flex-col space-y-6">
        <div
            class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Modules"
                description="Generate new CRUD modules"
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search by name, display name or description..."
                        class="pl-8"
                    />
                </div>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button><Plus class="h-4 w-4" />New Module</Button>
                    </SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle
                                >{{
                                    editingItem ? 'Edit' : 'Create'
                                }}
                                Module</SheetTitle
                            >
                            <SheetDescription>
                                {{
                                    editingItem
                                        ? 'Update the module description and display name.'
                                        : 'Create a new module. The CRUD for the given name will be generated automatically on save.'
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
                                <Label for="name">Name (PascalCase)</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    :default-value="editingItem?.name"
                                    placeholder="UsersProfile"
                                    required
                                />
                                <InputError :message="errors.name" />
                                <p class="text-xs text-muted-foreground">
                                    Must start with an uppercase letter (e.g.
                                    "UsersProfile", "OrderItem").
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="display_name"
                                    >Display name (sidebar)</Label
                                >
                                <Input
                                    id="display_name"
                                    name="display_name"
                                    :default-value="
                                        editingItem?.display_name ?? ''
                                    "
                                    placeholder="Users (shown in the dashboard sidebar)"
                                />
                                <InputError :message="errors.display_name" />
                                <p class="text-xs text-muted-foreground">
                                    Optional. Human readable label shown in the
                                    dashboard sidebar. Defaults to the name in
                                    title case.
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    :default-value="
                                        editingItem?.description ?? ''
                                    "
                                    placeholder="What this module is for"
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none"
                                />
                                <InputError :message="errors.description" />
                            </div>
                            <SheetFooter>
                                <SheetClose as-child
                                    ><Button variant="secondary"
                                        >Cancel</Button
                                    ></SheetClose
                                >
                                <Button type="submit" :disabled="processing">{{
                                    editingItem ? 'Update' : 'Generate CRUD'
                                }}</Button>
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
                    <DialogTitle>Delete Module</DialogTitle>
                    <DialogDescription
                        >Are you sure you want to delete "{{
                            itemToDelete?.name
                        }}"? The generated CRUD files will remain on disk; only
                        the record in the modules table is
                        removed.</DialogDescription
                    >
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child
                        ><Button variant="secondary"
                            >Cancel</Button
                        ></DialogClose
                    >
                    <Button variant="destructive" @click="deleteItem"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <div class="min-h-0 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Display name</th>
                        <th class="px-4 py-3 font-medium">Description</th>
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
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3">
                            {{ item.display_name ?? '—' }}
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
                                            aria-label="Actions"
                                            ><MoreVertical class="h-4 w-4"
                                        /></Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="openEditSheet(item)"
                                        >
                                            <Pencil class="mr-2 h-4 w-4" />Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="confirmDelete(item)"
                                        >
                                            <Trash class="mr-2 h-4 w-4" />Delete
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
                            No modules found.
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
                        <SelectTrigger class="w-20"
                            ><SelectValue
                        /></SelectTrigger>
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
                        >Previous</Button
                    >
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
                        >Next</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
