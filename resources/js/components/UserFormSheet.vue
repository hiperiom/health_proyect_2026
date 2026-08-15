<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, ChevronDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
} from '@/components/ui/sheet';
import UsersProfilePhotoUploader from '@/components/UsersProfilePhotoUploader.vue';
import { store, update } from '@/routes/users';
import type {
    MunicipalityOption,
    StateOption,
    UserGenderOption,
    UserModel,
    UserNacionalityOption,
    UserStatusOption,
} from '@/types/users';

const props = defineProps<{
    open: boolean;
    editingItem: UserModel | null;
    availableStatuses: UserStatusOption[];
    availableNacionalities: UserNacionalityOption[];
    availableGenders: UserGenderOption[];
    availableStates: StateOption[];
    availableMunicipalities: MunicipalityOption[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
    'photo-updated': [url: string | null];
}>();

// Collapsible "Perfil del usuario": abierto por defecto.
const profileOpen = ref(true);

// Collapsible "Ubicación geográfica": cerrado por defecto.
const geoOpen = ref(false);

// Estado seleccionado para filtrar municipios (string para coincidir con SelectItem values).
const selectedStateId = ref<string | null>(
    props.editingItem?.stateId ? String(props.editingItem.stateId) : null,
);

// Valores de los Selects para sincronizar con hidden inputs.
const statusValue = ref<string>(props.editingItem?.status ?? 'active');
const nacionalityValue = ref<string>(props.editingItem?.nacionality ?? 'V');
const genderValue = ref<string>(props.editingItem?.gender ?? 'M');
const municipalityIdValue = ref<string>(
    props.editingItem?.municipalityId
        ? String(props.editingItem.municipalityId)
        : '',
);

const filteredMunicipalities = computed<MunicipalityOption[]>(() => {
    if (selectedStateId.value === null) {
        return [];
    }

    const stateIdNum = parseInt(selectedStateId.value, 10);

    return props.availableMunicipalities.filter(
        (m) => m.state_id === stateIdNum,
    );
});

// Cuando cambia el estado, resetear el municipio seleccionado
watch(selectedStateId, () => {
    municipalityIdValue.value = '';
});

// Validación de correo en tiempo real (disponibilidad).
const emailValue = ref<string>(props.editingItem?.email ?? '');
const emailChecking = ref(false);
const emailExists = ref(false);
const emailMessage = ref<string | null>(null);

let emailCheckAbort: AbortController | null = null;
let emailCheckSeq = 0;

const dniValue = ref<string>(props.editingItem?.dni ?? '');
const dniChecking = ref(false);
const dniExists = ref(false);
const dniMessage = ref<string | null>(null);

let dniCheckAbort: AbortController | null = null;
let dniCheckSeq = 0;

// Valores de los Inputs para sincronizar con v-model.
const firstNameValue = ref<string>(props.editingItem?.firstName ?? '');
const lastNameValue = ref<string>(props.editingItem?.lastName ?? '');
const birthDateValue = ref<string>(props.editingItem?.birthDate ?? '');
const phoneMobileValue = ref<string>(props.editingItem?.phoneMobile ?? '');
const phoneLandlineValue = ref<string>(props.editingItem?.phoneLandline ?? '');
const addressValue = ref<string>(props.editingItem?.address ?? '');

// Sincroniza el formulario con el usuario que se está editando (o lo resetea al crear).
watch(
    () => [props.open, props.editingItem?.id ?? null] as const,
    ([isOpen]) => {
        if (!isOpen) {
            return;
        }

        const item = props.editingItem;

        profileOpen.value = true;
        selectedStateId.value = item?.stateId ? String(item.stateId) : null;
        statusValue.value = item?.status ?? 'active';
        nacionalityValue.value = item?.nacionality ?? 'V';
        genderValue.value = item?.gender ?? 'M';
        municipalityIdValue.value = item?.municipalityId
            ? String(item.municipalityId)
            : '';
        firstNameValue.value = item?.firstName ?? '';
        lastNameValue.value = item?.lastName ?? '';
        birthDateValue.value = item?.birthDate ?? '';
        phoneMobileValue.value = item?.phoneMobile ?? '';
        phoneLandlineValue.value = item?.phoneLandline ?? '';
        addressValue.value = item?.address ?? '';
        emailValue.value = item?.email ?? '';
        dniValue.value = item?.dni ?? '';
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

function onPhotoUpdated(url: string | null) {
    emit('photo-updated', url);
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
            props.editingItem?.id ? `&ignore_id=${props.editingItem.id}` : ''
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
        const originalEmail = props.editingItem?.email?.trim() ?? '';

        if (payload.exists) {
            emailExists.value = true;
            emailMessage.value = 'Este correo electrónico ya está registrado.';
        } else if (props.editingItem && value === originalEmail) {
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
            props.editingItem?.id ? `&ignore_id=${props.editingItem.id}` : ''
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
</script>

<template>
    <Sheet
        :open="open"
        @update:open="(value) => emit('update:open', value)"
    >
        <SheetContent class="overflow-y-auto">
            <SheetHeader>
                <SheetTitle
                    >{{ editingItem ? 'Editar' : 'Crear' }}
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
                @success="emit('success')"
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
                                <span class="text-destructive">*</span></Label
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
                                v-else-if="emailExists && emailMessage"
                                class="flex items-start gap-1 text-xs text-destructive"
                            >
                                <AlertCircle
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{ emailMessage }}</span>
                            </p>
                            <p
                                v-else-if="emailMessage && !emailExists"
                                class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400"
                            >
                                <span>{{ emailMessage }}</span>
                            </p>
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="status"
                                >Estatus
                                <span class="text-destructive">*</span></Label
                            >
                            <Select v-model="statusValue">
                                <SelectTrigger class="w-full">
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
                            :class="profileOpen ? 'rotate-180' : ''"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent
                        class="space-y-4 border-t px-4 py-4"
                    >
                        <div v-if="!editingItem">
                            <Label>Fotografía</Label>
                            <p
                                class="rounded-md mt-2 border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground"
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
                                    <span class="text-destructive"
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
                                <InputError :message="errors.first_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="last_name"
                                    >Apellidos
                                    <span class="text-destructive"
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
                                <InputError :message="errors.last_name" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="nacionality"
                                    >Nacionalidad
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Select v-model="nacionalityValue">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione" />
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
                                <Label for="dni"
                                    >Documento de Identidad
                                    <span class="text-destructive"
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
                                <InputError :message="errors.dni" />
                                <p
                                    v-if="dniChecking"
                                    class="text-xs text-muted-foreground"
                                >
                                    Verificando número de documento...
                                </p>
                                <p
                                    v-else-if="dniExists && dniMessage"
                                    class="flex items-start gap-1 text-xs text-destructive"
                                >
                                    <AlertCircle
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                    />
                                    <span>{{ dniMessage }}</span>
                                </p>
                                <p
                                    v-else-if="dniMessage && !dniExists"
                                    class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5" />
                                    <span>{{ dniMessage }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="birth_date"
                                    >Fecha de Nacimiento
                                    <span class="text-destructive"
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
                                <InputError :message="errors.birth_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="gender"
                                    >Género
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Select v-model="genderValue">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione" />
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
                                    >Teléfono Móvil
                                    <span class="text-destructive"
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
                                <InputError :message="errors.phone_mobile" />
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
                                <InputError :message="errors.phone_landline" />
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <!-- Collapsible: Ubicación geográfica -->
                <Collapsible v-model:open="geoOpen" class="rounded-md border">
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
                                <InputError :message="errors.state_id" />
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
                            <Label for="address">Domicilio</Label>
                            <Input
                                id="address"
                                name="address"
                                v-model="addressValue"
                                placeholder="Ej. Av. Principal, Edif. Los Andes, Piso 2, Apto 2B"
                            />
                            <InputError :message="errors.address" />
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <SheetFooter>
                    <SheetClose as-child>
                        <Button variant="secondary">Cancelar</Button>
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
</template>
