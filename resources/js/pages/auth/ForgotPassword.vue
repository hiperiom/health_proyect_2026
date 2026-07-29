<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Recuperar contraseña',
        description: 'Ingresa tu correo para recibir un enlace de restablecimiento',
        brandingTitle: 'Bienvenido a tu centro de salud digital',
        brandingSubtitle: 'Gestiona tus citas, historial médico y seguimiento de salud todo en un solo lugar.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-400"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email" class="dark:text-slate-200">Correo electrónico</Label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </span>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="tu@email.com"
                        class="bg-slate-100 pl-10 dark:bg-slate-700 dark:text-slate-100"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full bg-teal-600 hover:bg-teal-500 dark:bg-teal-500 dark:hover:bg-teal-400"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Enviar enlace de recuperación
                </Button>
            </div>
        </Form>

        <div class="text-center text-sm text-slate-600 dark:text-slate-300">
            <span>¿Ya recordaste tu contraseña? </span>
            <TextLink :href="login()" class="text-teal-600 hover:text-teal-500 dark:text-teal-400">
                Inicia sesión
            </TextLink>
        </div>
    </div>
</template>
