<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password/force-update';

defineOptions({
    layout: {
        title: 'Update your password',
        description: 'You need to set a new password before continuing.',
    },
});
</script>

<template>
    <Head title="Update Password" />

    <div class="mb-4 rounded-md bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
        Your account was created with a temporary password. Please set a new password to continue.
    </div>

    <Form
        :action="update.url()"
        method="patch"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-2">
            <Label for="password">New Password</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                autofocus
                autocomplete="new-password"
                placeholder="New password"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">Confirm Password</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Update Password
        </Button>
    </Form>
</template>