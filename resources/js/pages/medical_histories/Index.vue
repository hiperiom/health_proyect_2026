<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    HelpCircle,
    Info,
    MoreVertical,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Trash,
    User,
} from '@lucide/vue';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
import { index, store, update, destroy } from '@/routes/medical-histories';
import type { MedicalHistory, MedicalHistoryTicket } from '@/types/medical_histories';

const page = usePage();

type Props = {
    items: {
        data: MedicalHistory[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    item?: MedicalHistory;
    availableStatuses?: { value: string; label: string; colorClass?: string }[];
    availableNacionalities?: { value: string; label: string }[];
    availableGenders?: { value: string; label: string }[];
    availableStates?: { id: number; name: string }[];
    availableMunicipalities?: { id: number; name: string; state_id: number }[];
    filters?: { search?: string; per_page?: number };
};

const props = withDefaults(defineProps<Props>(), {
    availableStatuses: () => [],
    availableNacionalities: () => [],
    availableGenders: () => [],
    availableStates: () => [],
    availableMunicipalities: () => [],
});

const open = ref(false);
const editingItem = ref<MedicalHistory | null>(props.item ?? null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<MedicalHistory | null>(null);
const mrnInfoOpen = ref(false);

const search = ref<string>(props.filters?.search ?? '');
const perPage = ref<string>(
    props.filters?.per_page && [10, 25, 50, 100].includes(props.filters.per_page)
        ? String(props.filters.per_page)
        : '10',
);

// Patient search state
const patientQuery = ref<string>('');
const patientSearching = ref(false);
const patientTicket = ref<MedicalHistoryTicket | null>(null);
const patientFound = ref(false);
const patientHasHistory = ref(false);
const patientMessage = ref<string>('');
const patientCheckAbort = ref<AbortController | null>(null);
const patientCheckSeq = ref(0);

// Form state
const profileOpen = ref(true);
const geoOpen = ref(false);
const selectedStateId = ref<string | null>(null);
const statusValue = ref<string>('active');
const nacionalityValue = ref<string>('V');
const genderValue = ref<string>('M');
const municipalityIdValue = ref<string>('');
const emailValue = ref<string>('');
const firstNameValue = ref<string>('');
const lastNameValue = ref<string>('');
const dniValue = ref<string>('');
const birthDateValue = ref<string>('');
const phoneMobileValue = ref<string>('');
const phoneLandlineValue = ref<string>('');
const addressValue = ref<string>('');
const mrnValue = ref<string>('');

// Validación de correo en tiempo real (disponibilidad).
const emailChecking = ref(false);
const emailExists = ref(false);
const emailMessage = ref<string | null>(null);

let emailCheckAbort: AbortController | null = null;
let emailCheckSeq = 0;

const dniChecking = ref(false);
const dniExists = ref(false);
const dniMessage = ref<string | null>(null);

let dniCheckAbort: AbortController | null = null;
let dniCheckSeq = 0;

const availableStatuses = computed(() => props.availableStatuses ?? []);
const availableNacionalities = computed(() => props.availableNacionalities ?? []);
const availableGenders = computed(() => props.availableGenders ?? []);
const availableStates = computed(() => props.availableStates ?? []);
const availableMunicipalities = computed(() => props.availableMunicipalities ?? []);

const filteredMunicipalities = computed(() => {
    if (selectedStateId.value === null) {
        return [];
    }

    const sid = parseInt(selectedStateId.value, 10);

    return (availableMunicipalities.value || []).filter(
        (m: any) => m.state_id === sid,
    );
});

watch(selectedStateId, () => {
    municipalityIdValue.value = '';
});

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
    open,
    (isOpen) => {
        if (!isOpen) {
            editingItem.value = null;
            resetPatientSearch();
            resetFormState();
            resetEmailState();
            resetDniState();
        }
    },
);

watch(
    () => editingItem.value?.id ?? null,
    (id) => {
        emailValue.value = id ? (editingItem.value ? (editingItem.value as any)?.email ?? '' : '') : '';
        dniValue.value = id ? (editingItem.value?.patient_identifier ?? '') : '';
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

function resetPatientSearch() {
    patientQuery.value = '';
    patientTicket.value = null;
    patientFound.value = false;
    patientHasHistory.value = false;
    patientMessage.value = '';
    patientSearching.value = false;
}

function resetFormState() {
    profileOpen.value = true;
    geoOpen.value = false;
    selectedStateId.value = null;
    statusValue.value = 'active';
    nacionalityValue.value = 'V';
    genderValue.value = 'M';
    municipalityIdValue.value = '';
    emailValue.value = '';
    firstNameValue.value = '';
    lastNameValue.value = '';
    dniValue.value = '';
    birthDateValue.value = '';
    phoneMobileValue.value = '';
    phoneLandlineValue.value = '';
    addressValue.value = '';
    mrnValue.value = '';
}

function resetEmailState(): void {
    emailExists.value = false;
    emailMessage.value = null;
}

function resetDniState(): void {
    dniExists.value = false;
    dniMessage.value = null;
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
        const originalEmail = editingItem.value ? (editingItem.value as any)?.email?.trim() ?? '' : '';

        if (payload.exists) {
            emailExists.value = true;
            emailMessage.value = 'Este correo electrónico ya está registrado.';
        } else if (editingItem.value && value === originalEmail) {
            emailExists.value = false;
            emailMessage.value = '✅ Correo electrónico validado.';
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

function generateMrn(): void {
    const prefix = 'HN';
    const year = new Date().getFullYear().toString();
    const sequence = String(Math.floor(Math.random() * 9000000) + 1000000);
    const base = `${prefix}-${year}-${sequence}`;
    const checkDigit = calculateLuhnDigit(base);
    mrnValue.value = `${base}-${checkDigit}`;
}

function calculateLuhnDigit(value: string): string {
    const digits = value.replace(/[^0-9]/g, '').split('').reverse().map(Number);
    let sum = 0;

    for (let i = 0; i < digits.length; i++) {
        let digit = digits[i];

        if (i % 2 === 0) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }

        sum += digit;
    }

    return ((10 - (sum % 10)) % 10).toString();
}

async function searchPatient() {
    const value = patientQuery.value.trim();

    if (value.length === 0) {
        resetPatientSearch();
        return;
    }

    if (patientCheckAbort.value) {
        patientCheckAbort.value.abort();
    }

    const controller = new AbortController();
    patientCheckAbort.value = controller;
    const seq = ++patientCheckSeq.value;
    patientSearching.value = true;
    patientTicket.value = null;
    patientFound.value = false;
    patientHasHistory.value = false;
    patientMessage.value = '';

    try {
        const url = `/medical-histories/search?q=${encodeURIComponent(value)}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (seq !== patientCheckSeq.value) {
            return;
        }

        if (!response.ok) {
            patientMessage.value = 'No se pudo consultar el paciente.';
            return;
        }

        const payload = await response.json();

        if (payload.found && payload.hasHistory) {
            patientFound.value = true;
            patientHasHistory.value = true;
            patientTicket.value = payload.ticket;
        } else if (payload.found && !payload.hasHistory) {
            patientFound.value = true;
            patientHasHistory.value = false;
            patientMessage.value = 'Paciente encontrado sin Historia Clínica. Complete los datos para crearla.';

            if (!mrnValue.value) {
                generateMrn();
            }
        } else {
            patientFound.value = false;
            patientHasHistory.value = false;
            patientMessage.value = 'Dato no registrado. Complete los datos para crear el paciente y su Historia Clínica.';

            if (!mrnValue.value) {
                generateMrn();
            }
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        patientMessage.value = 'Error consultando el paciente.';
    } finally {
        if (seq === patientCheckSeq.value) {
            patientSearching.value = false;
        }
    }
}

watch(patientQuery, () => {
    if (searchDebounce) {
        clearTimeout(searchDebounce);
    }

    searchDebounce = setTimeout(() => searchPatient(), 300);
});

function openCreateSheet() {
    editingItem.value = null;
    resetPatientSearch();
    resetFormState();
    generateMrn();
    open.value = true;
}

function openEditSheet(item: MedicalHistory) {
    editingItem.value = item;
    patientQuery.value = item.mrn ?? '';
    patientTicket.value = {
        mrn: item.mrn ?? '',
        firstName: null,
        lastName: null,
        dni: item.patient_identifier ?? null,
        totalEncounters: 0,
    };
    patientFound.value = true;
    patientHasHistory.value = true;
    mrnValue.value = item.mrn ?? '';
    open.value = true;
}

function confirmDelete(item: MedicalHistory) {
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

function closeSheet() {
    open.value = false;
    editingItem.value = null;
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
                popover: {
                    title: '¡Bienvenidos!',
                    description: 'Bienvenidos al módulo Historias Clínicas. Esta es una guía rápida sobre el uso de esta pantalla.',
                },
            },
            {
                element: '#tour-search',
                popover: {
                    title: '🔍 Buscar Paciente',
                    description: 'Escribe cédula, correo o número de historia clínica para buscar un paciente existente.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-new-btn',
                popover: {
                    title: '➕ Crear Historia Clínica',
                    description: 'Al hacer clic aquí, se abrirá un panel lateral para registrar una nueva Historia Clínica.',
                    side: 'left',
                    align: 'start',
                    onNextClick: () => {
                        editingItem.value = null;
                        open.value = true;

                        setTimeout(() => driverObj.moveNext(), 300);
                    },
                },
            },
            {
                element: '#tour-table',
                popover: {
                    title: '📋 Tabla de Registros',
                    description: 'Aquí se listan las Historias Clínicas.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                element: '#tour-actions',
                popover: {
                    title: '⚙️ Acciones por Historia Clínica',
                    description: 'Usa este menú para Editar o Eliminar una Historia Clínica.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '#tour-pagination',
                popover: {
                    title: '📄 Paginación y Controles',
                    description: 'Navega entre las páginas y ajusta la cantidad de registros por página.',
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
    <Head title="Historias Clínicas" />
    <div class="flex h-full flex-col space-y-6">
        <Alert v-if="(page.props.flash as any)?.toast?.type === 'success'" variant="default" class="mb-4 border-green-500 bg-green-50 dark:bg-green-950">
            <CircleCheck class="h-4 w-4" />
                    <AlertTitle>Éxito</AlertTitle>
            <AlertDescription>{{ (page.props.flash as any)?.toast?.message }}</AlertDescription>
        </Alert>
        <div class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Historias Clínicas"
                description="Gestión y administración de Historias Clínicas."
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72" id="tour-search">
                    <Search class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" type="search" placeholder="Buscar por nombre..." class="pl-8" />
                </div>
                <Button variant="outline" size="icon" @click="startTour" title="Ayuda para esta pantalla">
                    <HelpCircle class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="icon" @click="applyFilters" title="Recargar listado">
                    <RefreshCw class="h-4 w-4" />
                </Button>
                <Sheet v-model:open="open" @update:open="(value) => { if (!value) { editingItem = null; } }">
                    <SheetTrigger as-child id="tour-new-btn">
                        <Button @click="openCreateSheet"><Plus class="h-4 w-4 mr-2" /> Nueva Historia Clínica</Button>
                    </SheetTrigger>
                    <SheetContent class="overflow-y-auto">
                        <SheetHeader>
                            <SheetTitle>{{ editingItem ? 'Editar' : 'Crear' }} Historia Clínica</SheetTitle>
                            <SheetDescription>{{ editingItem ? 'Actualizar' : 'Crear una nueva' }} Historia Clínica.</SheetDescription>
                        </SheetHeader>
                        <Form
                            :key="editingItem?.id ?? 'create'"
                            v-bind="editingItem ? update.form(editingItem.id) : store.form()"
                            class="space-y-6 px-4 overflow-auto"
                            v-slot="{ errors, processing }"
                            @success="closeSheet"
                            @error="(e) => { console.error('Form error:', e); }"
                        >
                            <input type="hidden" name="status" :value="statusValue" />
                            <input type="hidden" name="nacionality" :value="nacionalityValue" />
                            <input type="hidden" name="gender" :value="genderValue" />
                            <input type="hidden" name="state_id" :value="selectedStateId ?? ''" />
                            <input type="hidden" name="municipality_id" :value="municipalityIdValue" />
                            <input type="hidden" name="patient_identifier" :value="dniValue" />
                            <input type="hidden" name="dni" :value="dniValue" />
                            <input type="hidden" name="mrn" :value="mrnValue" />

                            <!-- Patient search -->
                            <div class="grid gap-2">
                                <Label for="patient_search">Buscar paciente por cédula, correo o historia clínica</Label>
                                <Input id="patient_search" v-model="patientQuery" placeholder="Ej. 12345678, usuario@ejemplo.com o HN-2026-123456-7" />
                                <p v-if="patientSearching" class="text-sm text-muted-foreground">Buscando...</p>
                                <p v-else-if="patientMessage" :class="['text-sm', patientMessage.includes('Dato no registrado') ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground']">{{ patientMessage }}</p>

                                <!-- Ticket -->
                                <div v-if="patientHasHistory && patientTicket" class="rounded-md border border-dashed border-border bg-muted/30 p-4">
                                    <p class="text-sm font-semibold">Historia Clínica encontrada</p>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="text-muted-foreground">MRN:</span>
                                            <span class="ml-2 font-mono">{{ patientTicket.mrn }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground">Paciente:</span>
                                            <span class="ml-2">{{ patientTicket.firstName }} {{ patientTicket.lastName }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground">Cédula:</span>
                                            <span class="ml-2">{{ patientTicket.dni }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground">Encuentros:</span>
                                            <span class="ml-2">{{ patientTicket.totalEncounters }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full form -->
                            <div v-if="!patientHasHistory" class="space-y-4">
                                <div class="grid gap-2">
                                    <div class="flex items-center justify-between">
                                        <Label>Número de Historia Clínica (MRN)</Label>
                                        <Button type="button" variant="ghost" size="icon" @click="mrnInfoOpen = true" title="Información sobre el número de historia clínica">
                                            <Info class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div class="rounded-md border p-3">
                                        <p class="font-mono text-lg">{{ mrnValue }}</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h3 class="border-b pb-2 text-sm font-semibold text-foreground">Datos del usuario</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="email">Correo electrónico <span class="text-destructive">*</span></Label>
                                            <Input id="email" name="email" v-model="emailValue" placeholder="usuario@ejemplo.com" required />
                                            <p v-if="emailChecking" class="text-xs text-muted-foreground">Verificando correo...</p>
                                            <p v-else-if="emailExists && emailMessage" class="flex items-start gap-1 text-xs text-destructive">
                                                <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                                <span>{{ emailMessage }}</span>
                                            </p>
                                            <p v-else-if="emailMessage && !emailExists" class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                                <CheckCircle2 class="h-3.5 w-3.5" />
                                                <span>{{ emailMessage }}</span>
                                            </p>
                                            <InputError :message="errors.email" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="status">Estatus <span class="text-destructive">*</span></Label>
                                            <Select v-model="statusValue">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="Seleccione" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="opt in availableStatuses" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.status" />
                                        </div>
                                    </div>

                                    <Collapsible v-model:open="profileOpen" class="rounded-md border">
                                        <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold hover:bg-muted/50">
                                            <span>Perfil del usuario</span>
                                            <ChevronDown class="h-4 w-4 transition-transform duration-200" :class="profileOpen ? 'rotate-180' : ''" />
                                        </CollapsibleTrigger>
                                        <CollapsibleContent class="space-y-4 border-t px-4 py-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="grid gap-2">
                                                    <Label for="first_name">Nombres <span class="text-destructive">*</span></Label>
                                                    <Input id="first_name" name="first_name" v-model="firstNameValue" placeholder="Ej. Juan" required />
                                                    <InputError :message="errors.first_name" />
                                                </div>
                                                <div class="grid gap-2">
                                                    <Label for="last_name">Apellidos <span class="text-destructive">*</span></Label>
                                                    <Input id="last_name" name="last_name" v-model="lastNameValue" placeholder="Ej. Pérez García" required />
                                                    <InputError :message="errors.last_name" />
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="grid gap-2">
                                                    <Label for="nacionality">Nacionalidad <span class="text-destructive">*</span></Label>
                                                    <Select v-model="nacionalityValue">
                                                        <SelectTrigger><SelectValue placeholder="Seleccione" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="opt in availableNacionalities" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="errors.nacionality" />
                                                </div>
                                                <div class="grid gap-2">
                                                    <Label for="dni">Documento de Identidad <span class="text-destructive">*</span></Label>
                                                    <Input id="dni" name="dni" v-model="dniValue" placeholder="12345678" required />
                                                    <p v-if="dniChecking" class="text-xs text-muted-foreground">Verificando número de documento...</p>
                                                    <p v-else-if="dniExists && dniMessage" class="flex items-start gap-1 text-xs text-destructive">
                                                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                                        <span>{{ dniMessage }}</span>
                                                    </p>
                                                    <p v-else-if="dniMessage && !dniExists" class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                                        <span>{{ dniMessage }}</span>
                                                    </p>
                                                    <InputError :message="errors.dni" />
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="grid gap-2">
                                                    <Label for="birth_date">Fecha de nacimiento <span class="text-destructive">*</span></Label>
                                                    <Input id="birth_date" name="birth_date" v-model="birthDateValue" type="date" required />
                                                    <InputError :message="errors.birth_date" />
                                                </div>
                                                <div class="grid gap-2">
                                                    <Label for="gender">Género <span class="text-destructive">*</span></Label>
                                                    <Select v-model="genderValue">
                                                        <SelectTrigger><SelectValue placeholder="Seleccione" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="opt in availableGenders" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="errors.gender" />
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="grid gap-2">
                                                    <Label for="phone_mobile">Teléfono Móvil <span class="text-destructive">*</span></Label>
                                                    <Input id="phone_mobile" name="phone_mobile" v-model="phoneMobileValue" placeholder="04141234567" required />
                                                    <InputError :message="errors.phone_mobile" />
                                                </div>
                                                <div class="grid gap-2">
                                                    <Label for="phone_landline">Teléfono Local</Label>
                                                    <Input id="phone_landline" name="phone_landline" v-model="phoneLandlineValue" placeholder="02121234567" />
                                                    <InputError :message="errors.phone_landline" />
                                                </div>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>

                                    <Collapsible v-model:open="geoOpen" class="rounded-md border">
                                        <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold hover:bg-muted/50">
                                            <span>Ubicación geográfica</span>
                                            <ChevronDown class="h-4 w-4 transition-transform duration-200" :class="geoOpen ? 'rotate-180' : ''" />
                                        </CollapsibleTrigger>
                                        <CollapsibleContent class="space-y-4 border-t px-4 py-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="grid gap-2">
                                                    <Label for="state_id">Estado</Label>
                                                    <Select v-model="selectedStateId">
                                                        <SelectTrigger class="w-full"><SelectValue placeholder="Seleccione un estado" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="s in availableStates" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="errors.state_id" />
                                                </div>
                                                <div class="grid gap-2">
                                                    <Label for="municipality_id">Municipio</Label>
                                                    <Select v-model="municipalityIdValue">
                                                        <SelectTrigger class="w-full"><SelectValue placeholder="Seleccione un municipio" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="m in filteredMunicipalities" :key="m.id" :value="String(m.id)">{{ m.name }}</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="errors.municipality_id" />
                                                </div>
                                            </div>
                                            <div class="grid gap-2">
                                                <Label for="address">Dirección</Label>
                                                <Input id="address" name="address" v-model="addressValue" placeholder="Dirección" />
                                                <InputError :message="errors.address" />
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </div>
                            </div>

                            <SheetFooter id="tour-sheet-footer">
                                <SheetClose as-child>
                                    <Button variant="secondary">Cancelar</Button>
                                </SheetClose>
                                <Button type="submit" :disabled="processing || (patientHasHistory && !!patientTicket) || emailExists || dniExists">{{ editingItem ? 'Actualizar' : 'Guardar' }}</Button>
                            </SheetFooter>
                        </Form>
                    </SheetContent>
                </Sheet>
            </div>
        </div>

        <Dialog :open="deleteDialogOpen" @update:open="(v) => (deleteDialogOpen = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Eliminar Historia Clínica</DialogTitle>
                    <DialogDescription>¿Estás seguro que quieres eliminar "{{ itemToDelete?.name }}"? Esta acción no se puede revertir.</DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child><Button variant="secondary">Cancelar</Button></DialogClose>
                    <Button variant="destructive" @click="deleteItem">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="mrnInfoOpen" @update:open="(v) => (mrnInfoOpen = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Número de Historia Clínica (MRN)</DialogTitle>
                    <DialogDescription>
                        El MRN es un identificador único generado automáticamente para cada paciente.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 text-sm">
                    <p>Formato: <span class="font-mono">HN-YYYY-SSSSSS-D</span></p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li><span class="font-semibold">HN:</span> prefijo de Historia Clínica.</li>
                        <li><span class="font-semibold">YYYY:</span> año de generación.</li>
                        <li><span class="font-semibold">SSSSSS:</span> número secuencial aleatorio de 7 dígitos.</li>
                        <li><span class="font-semibold">D:</span> dígito verificador calculado con el algoritmo de Luhn.</li>
                    </ul>
                    <p class="text-muted-foreground">Este número no se puede modificar manualmente y se asigna automáticamente al crear una historia clínica.</p>
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child><Button variant="secondary">Cerrar</Button></DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div class="min-h-0 mx-2 flex-1 overflow-auto rounded-md border" id="tour-table">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fotografía</th>
                        <th class="px-4 py-3 font-medium">MRN</th>
                        <th class="px-4 py-3 font-medium">Nombres</th>
                        <th class="px-4 py-3 font-medium">Apellidos</th>
                        <th class="px-4 py-3 font-medium">Cédula</th>
                        <th class="px-4 py-3 font-medium">Correo</th>
                        <th class="px-4 py-3 text-right font-medium" id="tour-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3">
                            <img v-if="item.patient?.photoUrl" :src="item.patient.photoUrl" alt="Foto" class="h-10 w-10 rounded-full object-cover" />
                            <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                <User class="h-5 w-5 text-muted-foreground" />
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono">{{ item.mrn }}</td>
                        <td class="px-4 py-3">{{ item.patient?.firstName }}</td>
                        <td class="px-4 py-3">{{ item.patient?.lastName }}</td>
                        <td class="px-4 py-3">{{ item.patient?.dni }}</td>
                        <td class="px-4 py-3">{{ item.patient?.email }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" aria-label="Actions"><MoreVertical class="h-4 w-4" /></Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openEditSheet(item)"><Pencil class="mr-2 h-4 w-4" /> Editar</DropdownMenuItem>
                                        <DropdownMenuItem @click="confirmDelete(item)"><Trash class="mr-2 h-4 w-4" /> Eliminar</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length">
                        <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No se encontraron Historias Clínicas.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between" id="tour-pagination">
            <div class="text-sm text-muted-foreground">Mostrando {{ items.from }} a {{ items.to }} de {{ items.total }} resultados.</div>
            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Por página</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="w-20"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="25">25</SelectItem>
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