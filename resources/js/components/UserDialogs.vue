<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { RoleOption, UserModel } from '@/types/users';

const props = defineProps<{
    deleteOpen: boolean;
    itemToDelete: UserModel | null;
    resetOpen: boolean;
    itemToReset: UserModel | null;
    assignOpen: boolean;
    roleItem: UserModel | null;
    availableRoles: RoleOption[];
    selectedRoleIds: number[];
    roleError: string | null;
}>();

const emit = defineEmits<{
    'update:deleteOpen': [value: boolean];
    'update:resetOpen': [value: boolean];
    'update:assignOpen': [value: boolean];
    'update:selectedRoleIds': [ids: number[]];
    confirmDelete: [];
    confirmReset: [];
    confirmAssign: [];
}>();

function toggleRole(roleId: number, checked: boolean): void {
    const ids = checked
        ? [...props.selectedRoleIds, roleId]
        : props.selectedRoleIds.filter((id) => id !== roleId);

    emit('update:selectedRoleIds', ids);
}
</script>

<template>
    <Dialog
        :open="deleteOpen"
        @update:open="(v) => emit('update:deleteOpen', v)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Eliminar Usuario</DialogTitle>
                <DialogDescription>
                    ¿Está seguro de eliminar "{{
                        itemToDelete?.name
                    }}"? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="emit('confirmDelete')">
                    Eliminar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="resetOpen"
        @update:open="(v) => emit('update:resetOpen', v)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Restablecer Contraseña</DialogTitle>
                <DialogDescription>
                    Enviar una contraseña temporal a "{{
                        itemToReset?.name
                    }}"
                    y solicitar que cambie la contraseña en el primer
                    inicio de sesión.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button @click="emit('confirmReset')">
                    Restablecer Contraseña
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="assignOpen"
        @update:open="(v) => emit('update:assignOpen', v)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Asignar roles</DialogTitle>
                <DialogDescription>
                    Seleccione los roles para "{{ roleItem?.name }}".
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2 py-4">
                <div class="flex items-center justify-between">
                    <Label>Roles</Label>
                    <span class="text-xs text-muted-foreground">
                        {{ selectedRoleIds.length }} seleccionados
                    </span>
                </div>
                <div
                    class="max-h-50 space-y-1 overflow-y-auto rounded-md border p-2"
                >
                    <label
                        v-for="role in availableRoles"
                        :key="role.id"
                        class="flex items-center gap-2 rounded-md p-2 hover:bg-muted/50"
                    >
                        <Checkbox
                            :model-value="selectedRoleIds.includes(role.id)"
                            @update:model-value="
                                (checked) =>
                                    toggleRole(role.id, checked === true)
                            "
                        />
                        <span class="text-sm font-medium">{{
                            role.label
                        }}</span>
                    </label>
                </div>
                <InputError :message="roleError ?? undefined" />
            </div>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button @click="emit('confirmAssign')"> Guardar roles </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
