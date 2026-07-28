<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Check,
    ClipboardList,
    Crown,
    Gear,
    Heart,
    LogOut,
    Stethoscope,
    User,
} from '@lucide/vue';
import { ref } from 'vue';
import { logout } from '@/routes';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        title: 'Selecciona tu rol',
        description:
            'Elige cómo accederás al sistema para personalizar tu experiencia',
    },
});

type Role = {
    id: number;
    name: string;
    slug: string;
    color_class: string | null;
    text_class: string | null;
    icon_svg: string | null;
};

type RoleOption = {
    slug: string;
    title: string;
    description: string;
    icon: unknown;
    iconBg: string;
};

const roleOptions: RoleOption[] = [
    {
        slug: 'paciente',
        title: 'Paciente',
        description:
            'Accede a tu historial médico, agenda citas y consulta resultados de exámenes',
        icon: Heart,
        iconBg: 'bg-emerald-100 text-emerald-700',
    },
    {
        slug: 'doctor',
        title: 'Doctor',
        description:
            'Gestiona pacientes, consulta historiales clínicos y prescribe tratamientos',
        icon: Stethoscope,
        iconBg: 'bg-sky-100 text-sky-700',
    },
    {
        slug: 'enfermeria',
        title: 'Personal de Enfermería',
        description:
            'Registra signos vitales, administra medicamentos y documenta cuidados',
        icon: Activity,
        iconBg: 'bg-rose-100 text-rose-700',
    },
    {
        slug: 'asistencial',
        title: 'Personal Asistencial',
        description:
            'Coordina servicios, gestiona admisiones y apoya la logística hospitalaria',
        icon: ClipboardList,
        iconBg: 'bg-violet-100 text-violet-700',
    },
    {
        slug: 'administrador',
        title: 'Administrador',
        description:
            'Configura el sistema, gestiona usuarios y supervisa la operación general',
        icon: Heart,
        iconBg: 'bg-orange-100 text-orange-700',
    },
];

const props = defineProps<{
    roles: Role[];
    active_role_id?: number | null;
}>();

const selectedRoleId = ref<number | null>(props.active_role_id ?? null);

function selectRole(roleId: number): void {
    selectedRoleId.value = roleId;
}

function submitSelection(): void {
    if (selectedRoleId.value === null) {
        return;
    }

    router.post('/role-selection', { role_id: selectedRoleId.value }, {
        preserveScroll: true,
        preserveState: false,
    });
}

function handleLogout(): void {
    router.post(logout(), {}, {
        preserveScroll: true,
        preserveState: false,
    });
}
</script>

<template>
    <Head title="Selecciona tu rol" />

    <div class="relative min-h-svh overflow-hidden bg-slate-50">
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-emerald-200/40 blur-3xl"
            ></div>
            <div
                class="absolute top-10 right-0 h-64 w-64 rounded-full bg-cyan-200/40 blur-3xl"
            ></div>
            <div
                class="absolute -bottom-20 left-10 h-72 w-72 rounded-full bg-teal-200/40 blur-3xl"
            ></div>
        </div>

        <div
            class="relative z-10 flex min-h-svh flex-col items-center justify-center px-4 py-10"
        >
            <div class="flex w-full max-w-3xl flex-col items-center gap-6">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm"
                        >
                            <svg
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"
                                />
                            </svg>
                        </span>
                        <span class="text-2xl font-bold text-slate-800"
                            >MedicalApp</span
                        >
                    </div>

                    <h1 class="text-3xl font-bold text-slate-900">
                        Selecciona tu <span class="text-emerald-600">rol</span>
                    </h1>

                    <p class="text-base text-slate-500">
                        Elige cómo accederás al sistema para personalizar tu
                        experiencia
                    </p>
                </div>

                <div class="flex w-full max-w-xl items-center gap-4">
                    <div class="flex flex-col items-center gap-1">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-white"
                        >
                            <Check class="h-4 w-4" />
                        </span>
                        <span class="text-xs font-medium text-emerald-700"
                            >Login</span
                        >
                    </div>

                    <div class="h-px flex-1 bg-slate-200"></div>

                    <div class="flex flex-col items-center gap-1">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-white"
                        >
                            2
                        </span>
                        <span class="text-xs font-bold text-teal-700">Rol</span>
                    </div>

                    <div class="h-px flex-1 bg-slate-200"></div>

                    <div class="flex flex-col items-center gap-1">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-slate-600"
                        >
                            3
                        </span>
                        <span class="text-xs font-medium text-slate-500"
                            >Dashboard</span
                        >
                    </div>
                </div>

                <div class="w-full max-w-3xl">
                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <button
                            v-for="role in roles"
                            :key="role.id"
                            type="button"
                            class="flex flex-col items-start gap-3 rounded-2xl border bg-white p-5 text-left shadow-sm transition hover:border-emerald-300 hover:shadow-md"
                            :class="{
                                'border-emerald-500 bg-emerald-50/60 shadow-md ring-1 ring-emerald-500':
                                    selectedRoleId === role.id,
                            }"
                            @click="selectRole(role.id)"
                        >
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl"
                                    :class="
                                        roleOptions.find(
                                            (r) => r.slug === role.slug,
                                        )?.iconBg ??
                                        'bg-slate-100 text-slate-700'
                                    "
                                >
                                    <component
                                        :is="
                                            roleOptions.find(
                                                (r) => r.slug === role.slug,
                                            )?.icon ?? User
                                        "
                                        class="h-6 w-6"
                                    />
                                </span>

                                <div class="flex flex-col">
                                    <span class="font-semibold text-slate-900">
                                        {{ role.name }}
                                    </span>
                                    <span class="text-sm text-slate-500">
                                        {{
                                            roleOptions.find(
                                                (r) => r.slug === role.slug,
                                            )?.description ?? ''
                                        }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div class="mt-6 flex flex-col items-center gap-3">
                        <button
                            type="button"
                            :disabled="selectedRoleId === null"
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50"
                            @click="submitSelection"
                        >
                            Continuar
                            <ArrowRight class="h-4 w-4" />
                        </button>

                        <Button
                            type="button"
                            variant="ghost"
                            class="text-sm text-muted-foreground hover:text-slate-700"
                            @click="handleLogout"
                        >
                            <LogOut class="mr-2 h-4 w-4" />
                            Cerrar sesión
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
