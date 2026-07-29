<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Crear cuenta',
        description: 'Completa tus datos para crear tu cuenta',
        brandingTitle: 'Bienvenido a tu centro de salud digital',
        brandingSubtitle: 'Gestiona tus citas, historial médico y seguimiento de salud todo en un solo lugar.',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name" class="dark:text-slate-200">Nombre completo</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Tu nombre completo"
                    class="bg-slate-100 dark:bg-slate-700 dark:text-slate-100"
                />
                <InputError :message="errors.name" />
            </div>

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
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="tu@email.com"
                        class="bg-slate-100 pl-10 dark:bg-slate-700 dark:text-slate-100"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="dark:text-slate-200">Contraseña</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="••••••••"
                    :passwordrules="passwordRules"
                    class="bg-slate-100 dark:bg-slate-700 dark:text-slate-100"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="dark:text-slate-200">Confirmar contraseña</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="••••••••"
                    :passwordrules="passwordRules"
                    class="bg-slate-100 dark:bg-slate-700 dark:text-slate-100"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full bg-teal-600 hover:bg-teal-500 dark:bg-teal-500 dark:hover:bg-teal-400"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Crear cuenta
            </Button>
        </div>

        <div class="text-center text-sm text-slate-600 dark:text-slate-300">
            ¿Ya tienes cuenta?
            <TextLink
                :href="login()"
                class="text-teal-600 hover:text-teal-500 dark:text-teal-400"
            >
                Inicia sesión aquí
            </TextLink>
        </div>
    </Form>
</template>
