<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Home,
    LogIn,
    RotateCcw,
    SearchX,
    ShieldAlert,
    WifiOff,
    Wrench,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';

const props = withDefaults(
    defineProps<{
        status?: number;
        message?: string | null;
    }>(),
    {
        message: null,
    },
);

interface SharedProps {
    name?: string;
    auth?: {
        user?: { name?: string } | null;
    } | null;
}

const page = usePage<SharedProps>();

const isAuthenticated = computed(() => Boolean(page.props.auth?.user?.name));

type ErrorContent = {
    code: string;
    title: string;
    description: string;
    icon: Component;
    iconClasses: string;
    badgeClasses: string;
};

const CONTENT: Record<number, ErrorContent> = {
    403: {
        code: '403',
        title: 'Acceso restringido',
        description: 'No cuentas con los permisos necesarios para acceder a este módulo.',
        icon: ShieldAlert,
        iconClasses: 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400',
        badgeClasses:
            'bg-rose-50 text-rose-600 ring-rose-200/70 dark:bg-rose-900/30 dark:text-rose-300 dark:ring-rose-800',
    },
    404: {
        code: '404',
        title: 'Página no encontrada',
        description: 'La página que estás buscando no existe o fue movida de lugar.',
        icon: SearchX,
        iconClasses: 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400',
        badgeClasses:
            'bg-amber-50 text-amber-600 ring-amber-200/70 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
    },
    500: {
        code: '500',
        title: 'Error interno del servidor',
        description: 'Ocurrió un problema inesperado. Por favor, inténtalo de nuevo en unos momentos.',
        icon: Wrench,
        iconClasses: 'bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400',
        badgeClasses:
            'bg-teal-50 text-teal-600 ring-teal-200/70 dark:bg-teal-900/30 dark:text-teal-300 dark:ring-teal-800',
    },
    503: {
        code: '503',
        title: 'Servicio no disponible',
        description: 'Estamos realizando tareas de mantenimiento. Vuelve a intentarlo más tarde.',
        icon: WifiOff,
        iconClasses: 'bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-400',
        badgeClasses:
            'bg-sky-50 text-sky-600 ring-sky-200/70 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800',
    },
};

const content = computed<ErrorContent>(
    () => CONTENT[props.status ?? 500] ?? CONTENT[500],
);

const pageTitle = computed(() =>
    content.value.title + ' · Código ' + content.value.code,
);

const description = computed(() => {
    if (props.status === 403 && props.message) {
        return props.message;
    }

    return content.value.description;
});

const homeUrl = computed(() =>
    isAuthenticated.value ? dashboard().url : home().url,
);
const homeLabel = computed(() =>
    isAuthenticated.value ? 'Ir al tablero' : 'Iniciar sesión',
);

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = homeUrl.value;
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <div
        class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-slate-50 p-4 dark:bg-slate-900 sm:p-6"
    >
        <!-- Fondo decorativo: mismo lenguaje visual de las pantallas de autenticación -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-900/20" />
            <div class="absolute top-10 right-0 h-64 w-64 rounded-full bg-cyan-200/40 blur-3xl dark:bg-cyan-900/20" />
            <div class="absolute -bottom-20 left-10 h-72 w-72 rounded-full bg-teal-200/40 blur-3xl dark:bg-teal-900/20" />
        </div>

        <div class="relative z-10 flex w-full max-w-md flex-col items-center gap-6">
            <Link
                :href="home().url"
                class="mb-1 flex h-11 w-11 items-center justify-center rounded-xl font-medium"
            >
                <AppLogoIcon class="size-8 fill-current text-[var(--foreground)] dark:text-white" />
                <span class="sr-only">{{ page.props.name ?? 'HealthAgent' }}</span>
            </Link>

            <div class="w-full overflow-hidden rounded-3xl bg-white shadow-xl dark:border dark:border-slate-700 dark:bg-slate-800">
                <div class="p-8 text-center sm:p-10">
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                        :class="content.badgeClasses"
                    >
                        Código {{ content.code }}
                    </span>

                    <div
                        class="mx-auto mt-6 flex h-16 w-16 items-center justify-center rounded-2xl"
                        :class="content.iconClasses"
                    >
                        <component :is="content.icon" class="size-8" />
                    </div>

                    <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        {{ content.title }}
                    </h1>

                    <p class="mt-3 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                        {{ description }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Button
                            as-child
                            class="bg-teal-600 hover:bg-teal-500 dark:bg-teal-500 dark:hover:bg-teal-400"
                        >
                            <Link :href="homeUrl">
                                <component :is="isAuthenticated ? Home : LogIn" class="size-4" />
                                {{ homeLabel }}
                            </Link>
                        </Button>

                        <Button
                            variant="ghost"
                            class="text-slate-600 dark:text-slate-300"
                            @click="goBack"
                        >
                            <RotateCcw class="size-4" />
                            Volver atrás
                        </Button>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                © {{ new Date().getFullYear() }} {{ page.props.name ?? 'HealthAgent' }}. Todos los derechos reservados.
            </p>
        </div>
    </div>
</template>
