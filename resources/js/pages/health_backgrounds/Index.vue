<script setup lang="ts">
    import { driver } from 'driver.js';
    import 'driver.js/dist/driver.css';
    import { ref, watch } from 'vue';

    import { 
        Form, 
        Head, 
        router, 
        usePage 
    } from '@inertiajs/vue3';

    import { 
        CircleCheck, 
        Key, 
        MoreVertical, 
        Pencil, 
        Plus, 
        Search, 
        Shield, 
        Trash, 
        HelpCircle 
    } from '@lucide/vue';

    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

    import Heading from '@/components/Heading.vue';
    import InputError from '@/components/InputError.vue';
    import type { HealthBackground } from '@/types/health_backgrounds';
    import { index, store, update, destroy } from '@/routes/health-backgrounds';

    const page = usePage();

    type Props = {
        items: {
            data: HealthBackground[];
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        filters?: { search?: string; per_page?: number; };
    };

    const props = withDefaults(defineProps<Props>(), {});
    const open = ref(false);
    const editingItem = ref<HealthBackground | null>(null);
    const deleteDialogOpen = ref(false);
    const itemToDelete = ref<HealthBackground | null>(null);
    const search = ref<string>(props.filters?.search ?? '');
    const perPage = ref<string>(props.filters?.per_page && [10, 25, 50, 100].includes(props.filters.per_page) ? String(props.filters.per_page) : '10');

    let searchDebounce: ReturnType<typeof setTimeout> | null = null;

    function applyFilters() {
        const query: Record<string, string | number> = { page: 1 };
        if (search.value.trim() !== '') query.search = search.value;
        if (perPage.value !== '10') query.per_page = perPage.value;
        router.get(index().url, query, { preserveState: true, replace: true, preserveScroll: true });
    }

    watch(search, () => {
        if (searchDebounce) clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => applyFilters(), 300);
    });
    watch(perPage, () => applyFilters());

    function openEditSheet(item: HealthBackground) { editingItem.value = item; open.value = true; }
    function confirmDelete(item: HealthBackground) { itemToDelete.value = item; deleteDialogOpen.value = true; }
    function deleteItem() {
        if (!itemToDelete.value) return;
        router.delete(destroy(itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => { deleteDialogOpen.value = false; itemToDelete.value = null; },
        });
    }
    function closeSheet() { open.value = false; editingItem.value = null; }
    const startTour = () => {
        const driverObj = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.5,
            nextBtnText: 'Siguiente →',
            prevBtnText: '← Anterior',
            doneBtnText: 'Finalizar',
            steps: [
                {
                    popover: {
                        title: '¡Bienvenidos!',
                        description: 'Bienvenidos al módulo Antecedentes de Salud. Esta es una guia rápida sobre el uso de esta pantalla.',
                    },
                },
                {
                    element: '#tour-search',
                    popover: {
                        title: '🔍 Buscar Antecedente de Salud',
                        description: 'Escribe la Antecedente de Salud para filtrar la lista en tiempo real.',
                        side: 'left',
                        align: 'start',
                    },
                },
                {
                    element: '#tour-new-btn',
                    popover: {
                        title: '➕ Crear Antecedente de Salud',
                        description: 'Al hacer clic aquí, se abrirá un panel lateral para registrar una nueva Antecedente de Salud. ¡Vamos a abrirlo!',
                        side: 'left',
                        align: 'start',
                        onNextClick: () => {
                            // Abrimos el Sheet en modo CREACIÓN
                            editingItem.value = null;
                            open.value = true;
                            // Esperamos un poco a que el DOM se actualice y avanzamos
                            setTimeout(() => driverObj.moveNext(), 300);
                        },
                    },
                },
                {
                    element: '#tour-form',
                    popover: {
                        title: '📝 Completa los datos del formulario',
                        description: 'Rellena toda la información requerida por el formulario. Los datos sin el asterisco pueden ser omitidos.',
                        side: 'left',
                        align: 'start',
                    },
                },
                {
                    element: '#tour-sheet-footer',
                    popover: {
                        title: '💾 Guardar o Cancelar',
                        description: 'Usa "Guardar" para guardar la nueva Antecedente de Salud, o "Cancelar" para descartar los cambios y cerrar el panel.',
                        side: 'top',
                        align: 'center',
                        onNextClick: () => {
                            // Cerramos el Sheet antes de avanzar a la tabla
                            open.value = false;
                            setTimeout(() => driverObj.moveNext(), 300);
                        },
                    },
                },
                {
                    element: '#tour-table',
                    popover: {
                        title: '📋 Tabla de Registros',
                        description: 'Aquí se listan las Antecedentes de Salud.',
                        side: 'top',
                        align: 'start',
                    },
                },
                {
                    element: '#tour-actions',
                    popover: {
                        title: '⚙️ Acciones por Antecedente de Salud',
                        description: 'Usa este menú (icono de 3 puntos) para Editar o Eliminar un Antecedente de Salud en específico.',
                        side: 'left',
                        align: 'start',
                    },
                },
                {
                    element: '#tour-pagination',
                    popover: {
                        title: '📄 Paginación y Controles',
                        description: 'Navega entre las páginas y ajusta la cantidad de registros que deseas ver por página (10, 50, 100).',
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        });

        driverObj.drive();
    };  
</script>
<template>
    <Head title="Antecedentes de Salud" />
    <div class="flex h-full flex-col space-y-6">
        <Alert v-if="page.props.flash?.toast?.type === 'success'" variant="default" class="mb-4 border-green-500 bg-green-50 dark:bg-green-950">
            <CircleCheck class="h-4 w-4" />
            <AlertTitle>Success</AlertTitle>
            <AlertDescription>{{ page.props.flash.toast.message }}</AlertDescription>
        </Alert>
        <div class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading 
                variant="small" 
                title="Antecedentes de Salud" 
                description="Gestión y administración de Antecedentes de Salud." 
            />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72" id="tour-search">
                    <Search class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"  />
                    <Input v-model="search" type="search" placeholder="Buscar por Antecedente de Salud..." class="pl-8" />
                </div>
                <Button variant="outline" size="icon" @click="startTour" title="Ayuda para esta pantalla">
                    <HelpCircle class="h-4 w-4" />
                </Button>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child id="tour-new-btn">
                        <Button @click="editingItem = null"><Plus class="h-4 w-4 mr-2" /> Crear Antecedente de Salud</Button>
                    </SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle>{{ editingItem ? 'Editar' : 'Crear' }} Antecedente de Salud</SheetTitle>
                            <SheetDescription>{{ editingItem ? 'Actualizar' : 'Crear una nueva' }} Antecedente de Salud.</SheetDescription>
                        </SheetHeader>
                        <Form :key="editingItem?.id ?? 'create'" v-bind="editingItem ? update.form(editingItem.id) : store.form()" class="space-y-6 px-4 mt-4" v-slot="{ errors, processing }" @success="closeSheet">
                            <div id="tour-form" >
                                <div class="grid gap-2">
                                    <Label for="name">Nombre<span style="color:red">*</span></Label>
                                    <Input id="name" name="name" :default-value="editingItem?.name" placeholder="Antecedente de Salud" required />
                                    <InputError :message="errors.name" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="description">Descripción</Label>
                                    <Input id="description" name="description" :default-value="editingItem?.description" placeholder="Descripción" />
                                    <InputError :message="errors.description" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="value">Valor</Label>
                                    <Input id="value" name="value" :default-value="editingItem?.value" placeholder="Valor" />
                                    <InputError :message="errors.value" />
                                </div>
                            </div>

                            <SheetFooter id="tour-sheet-footer">
                                <Button type="submit" :disabled="processing">{{ editingItem ? 'Actualizar' : 'Guardar' }}</Button>
                                <SheetClose as-child><Button variant="secondary">Cancelar</Button></SheetClose>
                            </SheetFooter>
                        </Form>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
        
        <Dialog :open="deleteDialogOpen" @update:open="(v) => (deleteDialogOpen = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Eliminar HealthBackground</DialogTitle>
                    <DialogDescription>¿Estás seguro que quieres eliminar "{{ itemToDelete?.name }}"? Esta acción no se puede revertir.</DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child><Button variant="secondary">Cancelar</Button></DialogClose>
                    <Button variant="destructive" @click="deleteItem">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div class="min-h-0 mx-2 flex-1 overflow-auto rounded-md border" id="tour-table">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Descripción</th>
                        <th class="px-4 py-3 font-medium">Valor</th>
                        <th class="px-4 py-3 text-right font-medium" id="tour-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3">{{ item.description }}</td>
                        <td class="px-4 py-3">{{ item.value }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" aria-label="Actions"><MoreVertical class="h-4 w-4" /></Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openEditSheet(item)"><Pencil class="mr-2 h-4 w-4" /> Editar</DropdownMenuItem>
                                        <DropdownMenuItem @click="confirmDelete(item)"><Trash class="mr-2 h-4 w-4" /> Emininar</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No se encontraron Antecedentes de Salud.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-3 py-3 sm:flex-row sm:items-center sm:justify-between" id="tour-pagination">
            <div class="text-sm text-muted-foreground">Mostrando {{ items.from }} de {{ items.to }} de {{ items.total }} resultadoss.</div>
            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Por página</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="w-20"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem><SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem><SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="items.current_page === 1" @click="router.get(index().url, { ...filters, page: items.current_page - 1 }, { preserveState: true, preserveScroll: true })">Anterior</Button>
                    <Button variant="outline" size="sm" :disabled="items.current_page === items.last_page" @click="router.get(index().url, { ...filters, page: items.current_page + 1 }, { preserveState: true, preserveScroll: true })">Siguiente</Button>
                </div>
            </div>
        </div>
    </div>
</template>