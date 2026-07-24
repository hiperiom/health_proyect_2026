<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { MoreVertical, Pencil, Plus, Search, Trash, User } from '@lucide/vue';
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
import PatientPhotoUploader from '@/components/PatientPhotoUploader.vue';
import { index, store, update, destroy } from '@/routes/patients';
import type {
    Patient,
    PatientGenderOption,
    PatientNacionalityOption,
    PatientStatusOption,
} from '@/types/patients';

type Props = {
    items: {
        data: Patient[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    item?: Patient | null;
    availableStatuses?: PatientStatusOption[];
    availableNacionalities?: PatientNacionalityOption[];
    availableGenders?: PatientGenderOption[];
    filters?: {
        search?: string;
        status?: string;
        nacionality?: string;
        per_page?: number;
    };
};

const props = withDefaults(defineProps<Props>(), {
    availableStatuses: () => [],
    availableNacionalities: () => [],
    availableGenders: () => [],
});

const open = ref(false);
const editingItem = ref<Patient | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<Patient | null>(null);

const search = ref<string>(props.filters?.search ?? '');
const statusFilter = ref<string>(
    props.filters?.status && props.filters.status !== ''
        ? props.filters.status
        : 'all',
);
const nacionalityFilter = ref<string>(
    props.filters?.nacionality && props.filters.nacionality !== ''
        ? props.filters.nacionality
        : 'all',
);
const perPage = ref<string>(
    props.filters?.per_page && [10, 50, 100].includes(props.filters.per_page)
        ? String(props.filters.per_page)
        : '10',
);

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const filters = computed<Record<string, string | number | undefined>>(
    () => props.filters ?? {},
);

function applyFilters() {
    const query: Record<string, string | number> = { page: 1 };

    if (search.value.trim() !== '') {
        query.search = search.value;
    }

    if (statusFilter.value !== 'all') {
        query.status = statusFilter.value;
    }

    if (nacionalityFilter.value !== 'all') {
        query.nacionality = nacionalityFilter.value;
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

watch(statusFilter, () => applyFilters());
watch(nacionalityFilter, () => applyFilters());
watch(perPage, () => applyFilters());

function openEditSheet(item: Patient) {
    editingItem.value = item;
    open.value = true;
}

function confirmDelete(item: Patient) {
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

function fullName(item: Patient): string {
    return `${item.firstName} ${item.lastName}`.trim();
}

function age(birthDate: string | null | undefined): number {
    if (!birthDate) {
        return 0;
    }
    const birth = new Date(birthDate);
    const today = new Date();
    let years = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        years--;
    }
    return years;
}

function onPhotoUpdated(url: string | null) {
    if (editingItem.value) {
        editingItem.value = { ...editingItem.value, photoUrl: url };
    }
}
</script>

<template>
    <Head title="Patients" />

    <div class="flex h-full flex-col space-y-6">
        <div
            class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                variant="small"
                title="Patients"
                description="Gestión de pacientes del sistema"
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre, DNI, email o teléfono..."
                        class="pl-8"
                    />
                </div>
                <Select v-model="statusFilter">
                    <SelectTrigger class="w-full sm:w-40">
                        <SelectValue placeholder="Todos los estados" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos los estados</SelectItem>
                        <SelectItem
                            v-for="option in availableStatuses"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="nacionalityFilter">
                    <SelectTrigger class="w-full sm:w-40">
                        <SelectValue placeholder="Todas las nacionalidades" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all"
                            >Todas las nacionalidades</SelectItem
                        >
                        <SelectItem
                            v-for="option in availableNacionalities"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4" />
                            Nuevo Paciente
                        </Button>
                    </SheetTrigger>
                    <SheetContent class="overflow-y-auto">
                        <SheetHeader>
                            <SheetTitle
                                >{{
                                    editingItem ? 'Editar' : 'Crear'
                                }}
                                Paciente</SheetTitle
                            >
                            <SheetDescription>
                                {{
                                    editingItem
                                        ? 'Actualice los datos del'
                                        : 'Registre un nuevo'
                                }}
                                paciente en el sistema.
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
                            <PatientPhotoUploader
                                v-if="editingItem"
                                :patient-id="editingItem.id"
                                :initial-photo-url="editingItem.photoUrl"
                                :upload-url="`/patients/${editingItem.id}/photo`"
                                :destroy-url="`/patients/${editingItem.id}/photo`"
                                @updated="onPhotoUpdated"
                            />
                            <p
                                v-else
                                class="rounded-md border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground"
                            >
                                La fotografía se podrá cargar después de crear
                                el paciente.
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <Label for="first_name">Nombres</Label>
                                    <Input
                                        id="first_name"
                                        name="first_name"
                                        :default-value="editingItem?.firstName"
                                        placeholder="Ej. Juan"
                                        required
                                    />
                                    <InputError :message="errors.first_name" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="last_name">Apellidos</Label>
                                    <Input
                                        id="last_name"
                                        name="last_name"
                                        :default-value="editingItem?.lastName"
                                        placeholder="Ej. Pérez García"
                                        required
                                    />
                                    <InputError :message="errors.last_name" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <Label for="nacionality"
                                        >Nacionalidad</Label
                                    >
                                    <Select
                                        name="nacionality"
                                        :default-value="
                                            editingItem?.nacionality ?? 'V'
                                        "
                                    >
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
                                    <InputError :message="errors.nacionality" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="dni">Número de Documento</Label>
                                    <Input
                                        id="dni"
                                        name="dni"
                                        :default-value="editingItem?.dni"
                                        placeholder="12345678"
                                        required
                                    />
                                    <InputError :message="errors.dni" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <Label for="birth_date"
                                        >Fecha de Nacimiento</Label
                                    >
                                    <Input
                                        id="birth_date"
                                        name="birth_date"
                                        type="date"
                                        :default-value="editingItem?.birthDate"
                                        required
                                    />
                                    <InputError :message="errors.birth_date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="gender">Género</Label>
                                    <Select
                                        name="gender"
                                        :default-value="editingItem?.gender"
                                    >
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
                                    <InputError :message="errors.gender" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <Label for="phone_mobile"
                                        >Teléfono Móvil</Label
                                    >
                                    <Input
                                        id="phone_mobile"
                                        name="phone_mobile"
                                        :default-value="
                                            editingItem?.phoneMobile
                                        "
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
                                        :default-value="
                                            editingItem?.phoneLandline ?? ''
                                        "
                                        placeholder="02121234567"
                                    />
                                    <InputError
                                        :message="errors.phone_landline"
                                    />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Correo Electrónico</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    :default-value="editingItem?.email"
                                    placeholder="paciente@correo.com"
                                    required
                                />
                                <InputError :message="errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="status">Estado en Sistema</Label>
                                <Select
                                    name="status"
                                    :default-value="
                                        editingItem?.status ?? 'active'
                                    "
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione" />
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
                                <InputError :message="errors.status" />
                            </div>
                            <SheetFooter>
                                <SheetClose as-child>
                                    <Button variant="secondary"
                                        >Cancelar</Button
                                    >
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
                    <DialogTitle>Eliminar Paciente</DialogTitle>
                    <DialogDescription>
                        ¿Está seguro de eliminar a "{{
                            itemToDelete ? fullName(itemToDelete) : ''
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

        <div class="min-h-0 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Paciente</th>
                        <th class="px-4 py-3 font-medium">DNI</th>
                        <th class="px-4 py-3 font-medium">Teléfono</th>
                        <th class="px-4 py-3 font-medium">Edad</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">
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
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-muted text-muted-foreground"
                                >
                                    <img
                                        v-if="item.photoUrl"
                                        :src="item.photoUrl"
                                        :alt="fullName(item)"
                                        class="h-full w-full object-cover"
                                    />
                                    <User v-else class="h-4 w-4" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{
                                        fullName(item)
                                    }}</span>
                                    <span
                                        class="text-xs text-muted-foreground"
                                        >{{ item.email }}</span
                                    >
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ item.nacionality }}-{{ item.dni }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.phoneMobile }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ age(item.birthDate) }} años
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border border-transparent px-2.5 py-0.5 text-xs font-semibold"
                                :class="
                                    item.statusColorClass ??
                                    'bg-muted text-muted-foreground'
                                "
                            >
                                {{ item.statusLabel ?? item.status }}
                            </span>
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
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No se encontraron pacientes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-1 px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="text-sm text-muted-foreground">
                Mostrando {{ items.from ?? 0 }} a {{ items.to ?? 0 }} de
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
