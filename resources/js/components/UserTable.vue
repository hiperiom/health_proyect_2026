<script setup lang="ts">
import {
    Key,
    MoreVertical,
    Pencil,
    Shield,
    Trash,
} from '@lucide/vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { UserModel, UserRole } from '@/types/users';

type UserPagination = {
    data: UserModel[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
};

defineProps<{
    items: UserPagination;
    perPage: string;
}>();

const emit = defineEmits<{
    'update:perPage': [value: string];
    navigate: [page: number];
    edit: [item: UserModel];
    assignRole: [item: UserModel];
    resetPassword: [item: UserModel];
    delete: [item: UserModel];
}>();

function userRoleClasses(role: UserRole): string {
    const parts: string[] = ['border-transparent'];

    if (role.color_class) {
        parts.push(role.color_class);
    } else {
        parts.push('bg-secondary');
    }

    if (role.text_class) {
        parts.push(role.text_class);
    } else {
        parts.push('text-secondary-foreground');
    }

    return parts.join(' ');
}

function userRoleIcon(role: UserRole): string | null {
    return role.icon_svg ?? null;
}
</script>

<template>
    <div
        class="mx-3 min-h-0 flex-1 overflow-auto rounded-md border"
        id="tour-table"
    >
        <table class="w-full text-left text-sm">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3 font-medium">Foto</th>
                    <th class="px-4 py-3 font-medium">Nombre</th>
                    <th class="px-4 py-3 font-medium">
                        Correo Electrónico
                    </th>
                    <th class="px-4 py-3 font-medium">Estatus</th>
                    <th class="px-4 py-3 font-medium">Rol</th>
                    <th class="px-4 py-3 font-medium">Progreso</th>
                    <th
                        class="px-4 py-3 text-right font-medium"
                        id="tour-actions"
                    >
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="item in items.data"
                    :key="item.id"
                    class="border-t"
                >
                    <td class="px-4 py-3">
                        <Avatar class="h-9 w-9">
                            <AvatarImage
                                v-if="item.photoUrl"
                                :src="item.photoUrl"
                                :alt="item.name"
                            />
                            <AvatarFallback>{{
                                item.name
                                    .split(' ')
                                    .map((n) => n[0])
                                    .join('')
                                    .slice(0, 2)
                                    .toUpperCase()
                            }}</AvatarFallback>
                        </Avatar>
                    </td>
                    <td class="px-4 py-3">{{ item.name }}</td>
                    <td class="px-4 py-3">{{ item.email }}</td>
                    <td class="px-4 py-3">
                        <span
                            v-if="item.status"
                            class="inline-flex items-center rounded-full border border-transparent px-2.5 py-0.5 text-xs font-semibold"
                            :class="
                                item.statusColorClass ??
                                'bg-muted text-muted-foreground'
                            "
                        >
                            {{ item.statusLabel ?? item.status }}
                        </span>
                        <span
                            v-else
                            class="text-xs text-muted-foreground"
                        >
                            —
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div
                            v-if="item.roles && item.roles.length > 0"
                            class="flex flex-wrap items-center gap-1"
                        >
                            <span
                                v-for="role in item.roles"
                                :key="role.slug"
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                :class="userRoleClasses(role)"
                            >
                                <span
                                    v-if="userRoleIcon(role)"
                                    class="mr-2 h-4 w-4"
                                    v-html="userRoleIcon(role)"
                                ></span>
                                {{ role.name }}
                            </span>
                        </div>
                        <span
                            v-else
                            class="inline-flex items-center rounded-full border border-transparent bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                        >
                            Indefinido
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="group relative flex items-center gap-2">
                            <div
                                class="h-2 w-24 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full bg-linear-to-r from-red-500 via-yellow-500 to-green-500 transition-all duration-300"
                                    :style="{
                                        width: `${item.profileCompletion}%`,
                                    }"
                                ></div>
                            </div>
                            <span
                                class="text-xs tabular-nums text-muted-foreground"
                            >
                                {{ item.profileCompletion }}%
                            </span>
                            <div
                                class="pointer-events-none absolute top-full left-1/2 z-50 mt-2 hidden w-56 -translate-x-1/2 rounded-md border bg-popover p-3 text-xs text-popover-foreground shadow-lg group-hover:block"
                            >
                                <p class="mb-2 font-semibold">
                                    {{ item.profileCompletion }}% completado
                                    ({{ item.missingFields.length }}
                                    de 13 campos vacíos)
                                </p>
                                <template
                                    v-if="item.missingFields.length > 0"
                                >
                                    <p class="mb-1 text-muted-foreground">
                                        Datos faltantes:
                                    </p>
                                    <ul
                                        class="max-h-32 list-disc space-y-0.5 overflow-y-auto pl-4"
                                    >
                                        <li
                                            v-for="field in item.missingFields"
                                            :key="field"
                                        >
                                            {{ field }}
                                        </li>
                                    </ul>
                                </template>
                                <p
                                    v-else
                                    class="text-emerald-600 dark:text-emerald-400"
                                >
                                    ✅ Todos los datos completados
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Acciones"
                                    >
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        @click="emit('edit', item)"
                                    >
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Editar
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="emit('assignRole', item)"
                                    >
                                        <Shield class="mr-2 h-4 w-4" />
                                        Asignar rol
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="emit('resetPassword', item)"
                                    >
                                        <Key class="mr-2 h-4 w-4" />
                                        Restablecer contraseña
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="emit('delete', item)"
                                    >
                                        <Trash class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </td>
                </tr>
                <tr v-if="!items.data.length">
                    <td
                        colspan="7"
                        class="px-4 py-8 text-center text-muted-foreground"
                    >
                        No se encontraron usuarios.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div
        class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
        id="tour-pagination"
    >
        <div class="text-sm text-muted-foreground">
            Mostrando {{ items.from }} a {{ items.to }} de
            {{ items.total }} resultados.
        </div>
        <div
            class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground"
                    >Por página</span
                >
                <Select
                    :model-value="perPage"
                    @update:model-value="(v) => emit('update:perPage', String(v))"
                >
                    <SelectTrigger class="w-20">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="items.current_page === 1"
                    @click="emit('navigate', items.current_page - 1)"
                >
                    Anterior
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="items.current_page === items.last_page"
                    @click="emit('navigate', items.current_page + 1)"
                >
                    Siguiente
                </Button>
            </div>
        </div>
    </div>
</template>
