<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Actualizar tu contraseña',
        description: 'Necesitas establecer una nueva contraseña antes de continuar.',
    },
});
</script>

<template>
    <Head title="Actualizar contraseña" />

    <div
        class="mb-4 rounded-md bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200"
    >
        Tu cuenta fue creada con una contraseña temporal. Por favor establece
        una nueva contraseña para continuar.
    </div>

    <Form
        :action="update.url()"
        method="patch"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-2">
            <Label for="password">Nueva contraseña</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                autofocus
                autocomplete="new-password"
                placeholder="Nueva contraseña"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">Confirmar contraseña</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirmar contraseña"
            />
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Actualizar contraseña
        </Button>
    </Form>
</template>
