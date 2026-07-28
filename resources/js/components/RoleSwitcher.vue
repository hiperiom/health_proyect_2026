<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { RoleModel } from '@/types';

type Role = RoleModel;

type Auth = {
    user: {
        id: number;
        name: string;
        email: string;
        role: string | null;
        roleName: string | null;
        permissions: string[];
    } | null;
    roles: Role[];
    activeRole: {
        id: number | null;
        name: string | null;
        slug: string | null;
    } | null;
    hasMultipleRoles: boolean;
};

const page = usePage<{ auth: Auth }>();

const roles = computed<Role[]>(() => page.props.auth.roles ?? []);
const activeRole = computed(() => page.props.auth.activeRole);
const hasMultipleRoles = computed(() => page.props.auth.hasMultipleRoles);

function switchRole(roleId: number): void {
    router.post(
        '/switch-role',
        { role_id: roleId },
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
}
</script>

<template>
    <div v-if="hasMultipleRoles" class="flex flex-col gap-1 p-2">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    class="flex w-full items-center justify-between px-2 py-1.5 text-sm font-medium"
                >
                    <span class="truncate">{{
                        activeRole?.name ?? 'Seleccionar rol'
                    }}</span>
                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-60" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56" align="start">
                <DropdownMenuItem
                    v-for="role in roles"
                    :key="role.id"
                    :class="{ 'bg-accent': activeRole?.id === role.id }"
                    @click="switchRole(role.id)"
                >
                    <Check
                        class="mr-2 h-4 w-4"
                        :class="{ 'opacity-0': activeRole?.id !== role.id }"
                    />
                    <span>{{ role.name }}</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <div v-else-if="activeRole?.name" class="p-2 text-sm font-medium">
        {{ activeRole.name }}
    </div>
</template>
