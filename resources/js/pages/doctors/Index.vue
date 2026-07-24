<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { index, store, update, destroy } from '@/routes/doctors';
import type { Doctor } from '@/types/doctors';

type Props = {
    items: Doctor[];
};

defineProps<Props>();

const open = ref(false);
const editingItem = ref<Doctor | null>(null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<Doctor | null>(null);

function openCreateSheet() {
    editingItem.value = null;
    open.value = true;
}

function openEditSheet(item: Doctor) {
    editingItem.value = item;
    open.value = true;
}

function confirmDelete(item: Doctor) {
    itemToDelete.value = item;
    deleteDialogOpen.value = true;
}

function deleteItem() {
    if (!itemToDelete.value) return;
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
    <Head title="Doctors" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Doctors"
                description="Manage your doctors"
            />
            <Sheet :open="open" @update:open="(v) => (open = v)">
                <Button @click="openCreateSheet">
                    <Plus class="h-4 w-4" />
                    New Doctor
                </Button>
                <SheetContent>
                    <SheetHeader>
                        <SheetTitle
                            >{{
                                editingItem ? 'Edit' : 'Create'
                            }}
                            Doctor</SheetTitle
                        >
                        <SheetDescription>
                            {{ editingItem ? 'Update' : 'Create a new' }}
                            Doctor.
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
                                placeholder="Name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                name="description"
                                :default-value="editingItem?.description ?? ''"
                                placeholder="Description"
                                class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                            />
                            <InputError :message="errors.description" />
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

            <Dialog
                :open="deleteDialogOpen"
                @update:open="(v) => (deleteDialogOpen = v)"
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Doctor</DialogTitle>
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
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in items"
                :key="item.id"
                class="flex flex-col justify-between rounded-lg border p-4"
            >
                <div class="space-y-2">
                    <div class="font-medium">{{ item.name }}</div>
                    <p class="text-sm text-muted-foreground">
                        {{ item.description }}
                    </p>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="openEditSheet(item)"
                    >
                        <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="confirmDelete(item)"
                    >
                        <Trash class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
