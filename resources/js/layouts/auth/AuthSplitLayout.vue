<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Users, Calendar, HeartPulse } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
    brandingTitle?: string;
    brandingSubtitle?: string;
}>();
</script>

<template>
    <div
        class="relative min-h-dvh bg-slate-50 dark:bg-slate-900"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-900/20" />
            <div class="absolute top-10 right-0 h-64 w-64 rounded-full bg-cyan-200/40 blur-3xl dark:bg-cyan-900/20" />
            <div class="absolute -bottom-20 left-10 h-72 w-72 rounded-full bg-teal-200/40 blur-3xl dark:bg-teal-900/20" />
        </div>

        <div class="relative z-10 flex min-h-dvh items-center justify-center p-4 sm:p-6 lg:p-8">
            <div class="flex w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-xl dark:border dark:border-slate-700 dark:bg-slate-800">
                <div class="hidden w-1/2 flex-col justify-between bg-teal-600 p-10 text-white lg:flex dark:bg-teal-700">
                    <div>
                        <Link :href="home()" class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/30 bg-white/10">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                </svg>
                            </span>
                            <span class="text-xl font-bold">{{ name }}</span>
                        </Link>

                        <div class="mt-10 space-y-6">
                            <h2 class="text-3xl font-bold leading-tight">{{ brandingTitle || 'Bienvenido a tu centro de salud digital' }}</h2>
                            <p class="text-base text-teal-50">{{ brandingSubtitle || 'Gestiona tus citas, historial médico y seguimiento de salud todo en un solo lugar.' }}</p>
                        </div>
                    </div>

                    <ul class="mt-10 space-y-4 text-sm text-teal-50">
                        <li class="flex items-center gap-3">
                            <Users class="h-5 w-5" />
                            <span>Más de 500 especialistas</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <Calendar class="h-5 w-5" />
                            <span>Agenda citas fácilmente</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <HeartPulse class="h-5 w-5" />
                            <span>Seguimiento de salud 24/7</span>
                        </li>
                    </ul>
                </div>

                <div class="w-full lg:w-1/2 bg-white p-8 dark:bg-slate-800">
                    <div class="mx-auto flex w-full max-w-sm flex-col justify-center">
                        <div v-if="title" class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ title }}</h1>
                            <p v-if="description" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ description }}</p>
                        </div>
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
