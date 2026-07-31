
<script setup>
import { computed, ref } from 'vue';
import {
    AlertOctagon,
    AlertTriangle,
    Bell,
    CheckCircle,
    FileText,
    Info,
    Search,
    Settings,
    User,
} from '@lucide/vue';
import { getInitials } from '@/composables/useInitials';
import { edit as profileEdit } from '@/routes/profile';
import { logout } from '@/routes';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

import { usePage } from '@inertiajs/vue3';
const page = usePage();

const auth = computed(() => page.props.auth);

const user = computed(() => auth.value.user);
const view = ref<'empty' | 'results'>('empty');

const searchTab = ref<'simple' | 'advanced'>('simple');
const simpleQuery = ref('');
const searchOpen = ref(false);
const notificationsOpen = ref(false);


const userMenuOpen = ref(false);


const notifications = [
    {
        id: 1,
        type: 'critical',
        icon: AlertTriangle,
        title: 'Alerta de signos vitales críticos',
        detail: 'Paciente: María Rodríguez - FC: 120 lpm',
        action: 'Ver detalle',
        color: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    },
    {
        id: 2,
        type: 'lab',
        icon: CheckCircle,
        title: 'Resultado de laboratorio disponible',
        detail: 'Hemograma Completo - María Rodríguez',
        action: 'Ver resultado',
        color: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    },
    {
        id: 3,
        type: 'admission',
        icon: Info,
        title: 'Nuevo paciente ingresado',
        detail: 'Carlos Gómez - Habitación 204',
        action: 'Ver ficha',
        color: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    },
    {
        id: 4,
        type: 'pharmacy',
        icon: AlertOctagon,
        title: 'Interacción de medicamentos detectada',
        detail: 'Warfarina + Ibuprofeno',
        action: 'Revisar',
        color: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    },
    {
        id: 5,
        type: 'prescription',
        icon: FileText,
        title: 'Prescripción médica emitida',
        detail: 'Dr. Admin - María Rodríguez',
        action: 'Ver prescripción',
        color: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    },
];

const searchResults = [
    {
        id: 1,
        icon: FileText,
        title: 'Hemograma Completo - María Rodríguez',
        requester: 'Dr. Admin',
        date: '28/07/2026',
        tags: ['Examen', 'Laboratorio', 'Disponible'],
        status: 'Normal',
        statusColor: 'text-emerald-600 dark:text-emerald-400',
        bgColor: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    },
    {
        id: 2,
        icon: FileText,
        title: 'Perfil Lipídico - Juan Pérez',
        requester: 'Dra. López',
        date: '27/07/2026',
        tags: ['Examen', 'Laboratorio', 'Disponible'],
        status: 'Elevado',
        statusColor: 'text-red-600 dark:text-red-400',
        bgColor: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    },
];

const handleSearch = () => {
    view.value = 'results';
    searchOpen.value = false;
};

const handleBack = () => {
    view.value = 'empty';
};

const handleLogout = () => {
    router.post(logout().url);
};
</script>
<template>
    
    <header
        class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/80">
        <div class="flex h-16 items-center justify-between px-3">
            <div class="flex flex-col">
                <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Dashboard</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Bienvenido al panel principal</p>
            </div>
            <div class="flex items-center gap-2">
                <Dialog v-model:open="searchOpen">
                    <DialogTrigger as-child>
                        <Button variant="ghost" size="icon" class="h-9 w-9 rounded-full">
                            <Search class="h-4 w-4 text-slate-600 dark:text-slate-300" />
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle class="text-left text-lg font-semibold text-slate-900 dark:text-slate-100">
                                Búsqueda
                            </DialogTitle>
                        </DialogHeader>
                        <div class="mt-4">
                            <div class="flex items-center gap-4 border-b border-slate-200 dark:border-slate-700">
                                <button type="button" class="pb-2 text-sm font-medium transition-colors relative"
                                    :class="searchTab === 'simple' ? 'text-teal-600 dark:text-teal-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                                    @click="searchTab = 'simple'">
                                    Búsqueda Simple
                                    <span v-if="searchTab === 'simple'"
                                        class="absolute bottom-0 left-0 right-0 h-0.5 bg-teal-600 dark:bg-teal-400"></span>
                                </button>
                                <button type="button" class="pb-2 text-sm font-medium transition-colors relative"
                                    :class="searchTab === 'advanced' ? 'text-teal-600 dark:text-teal-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                                    @click="searchTab = 'advanced'">
                                    Búsqueda Avanzada
                                    <span v-if="searchTab === 'advanced'"
                                        class="absolute bottom-0 left-0 right-0 h-0.5 bg-teal-600 dark:bg-teal-400"></span>
                                </button>
                            </div>

                            <div v-if="searchTab === 'simple'" class="mt-4 space-y-4">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input v-model="simpleQuery" placeholder="Buscar paciente, doctor, diagnóstico..."
                                        class="pl-9 bg-slate-50 dark:bg-slate-700 dark:text-slate-100"
                                        @keydown.enter="handleSearch" />
                                </div>
                                <div>
                                    <p class="mb-2 text-xs font-medium text-slate-500 dark:text-slate-400">Búsquedas
                                        rápidas</p>
                                    <div class="flex flex-wrap gap-2">
                                        <Button variant="outline" size="sm" class="rounded-full"
                                            @click="handleSearch">Pacientes recientes</Button>
                                        <Button variant="outline" size="sm" class="rounded-full"
                                            @click="handleSearch">Resultados de laboratorio</Button>
                                        <Button variant="outline" size="sm" class="rounded-full"
                                            @click="handleSearch">Alertas de signos vitales</Button>
                                        <Button variant="outline" size="sm" class="rounded-full"
                                            @click="handleSearch">Farmacia</Button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="searchTab === 'advanced'" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="adv-name" class="text-slate-700 dark:text-slate-200">Nombre del
                                        paciente</Label>
                                    <Input id="adv-name" class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-id" class="text-slate-700 dark:text-slate-200">Cédula / ID</Label>
                                    <Input id="adv-id" class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-type" class="text-slate-700 dark:text-slate-200">Tipo de
                                        búsqueda</Label>
                                    <Select>
                                        <SelectTrigger id="adv-type"
                                            class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100">
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="patient">Paciente</SelectItem>
                                            <SelectItem value="doctor">Doctor</SelectItem>
                                            <SelectItem value="exam">Examen</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-area" class="text-slate-700 dark:text-slate-200">Área /
                                        Servicio</Label>
                                    <Select>
                                        <SelectTrigger id="adv-area"
                                            class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100">
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="lab">Laboratorio</SelectItem>
                                            <SelectItem value="radiology">Radiología</SelectItem>
                                            <SelectItem value="pharmacy">Farmacia</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-from" class="text-slate-700 dark:text-slate-200">Fecha desde</Label>
                                    <Input id="adv-from" type="date"
                                        class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-to" class="text-slate-700 dark:text-slate-200">Fecha hasta</Label>
                                    <Input id="adv-to" type="date"
                                        class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-status" class="text-slate-700 dark:text-slate-200">Estado</Label>
                                    <Select>
                                        <SelectTrigger id="adv-status"
                                            class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100">
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">Pendiente</SelectItem>
                                            <SelectItem value="completed">Completado</SelectItem>
                                            <SelectItem value="cancelled">Cancelado</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="adv-diagnosis"
                                        class="text-slate-700 dark:text-slate-200">Diagnóstico</Label>
                                    <Input id="adv-diagnosis"
                                        class="bg-slate-50 dark:bg-slate-700 dark:text-slate-100" />
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end gap-2">
                                <Button variant="outline" class="border-slate-200 dark:border-slate-700">Limpiar
                                    filtros</Button>
                                <Button class="bg-teal-600 hover:bg-teal-500 dark:bg-teal-500 dark:hover:bg-teal-400"
                                    @click="handleSearch">
                                    <Search class="mr-2 h-4 w-4" />
                                    Buscar
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <Sheet v-model:open="notificationsOpen">
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="relative h-9 w-9 rounded-full">
                            <Bell class="h-4 w-4 text-slate-600 dark:text-slate-300" />
                            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right" class="w-[400px] sm:w-[440px]">
                        <SheetHeader
                            class="flex flex-row items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-700">
                            <SheetTitle class="text-left text-lg font-semibold text-slate-900 dark:text-slate-100">
                                Notificaciones</SheetTitle>
                            <span class="text-xs text-teal-600 dark:text-teal-400">Marcar todas leídas</span>
                        </SheetHeader>
                        <div class="mt-4 space-y-3 overflow-y-auto">
                            <div v-for="notification in notifications" :key="notification.id"
                                class="flex gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                <div
                                    :class="`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${notification.color}`">
                                    <component :is="notification.icon" class="h-5 w-5" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{
                                        notification.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ notification.detail }}
                                    </p>
                                    <Button variant="link" size="sm"
                                        class="mt-2 h-auto p-0 text-teal-600 dark:text-teal-400">
                                        {{ notification.action }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div
                            class="mt-4 border-t border-slate-200 pt-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Mostrando últimas 24 horas
                        </div>
                    </SheetContent>
                </Sheet>

                <DropdownMenu v-model:open="userMenuOpen">
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="flex items-center gap-2 rounded-full px-2">
                            <Avatar class="h-8 w-8">
                                <AvatarFallback class="bg-teal-600 text-sm text-white">
                                    {{ getInitials(user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="hidden text-sm font-medium text-slate-700 dark:text-slate-200 md:block">Dr.
                                Admin</span>
                            <svg class="hidden h-4 w-4 text-slate-500 md:block" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel class="p-0 font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <Avatar class="h-8 w-8">
                                    <AvatarFallback class="bg-teal-600 text-sm text-white">
                                        {{ getInitials(user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-900 dark:text-slate-100">Dr. Admin</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">admin@medical.app</span>
                                </div>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem :as-child="true">
                            <Link class="block w-full cursor-pointer" :href="profileEdit()" prefetch>
                            <User class="mr-2 h-4 w-4" />
                            Mi Perfil
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem :as-child="true">
                            <Link class="block w-full cursor-pointer" :href="profileEdit()" prefetch>
                            <Settings class="mr-2 h-4 w-4" />
                            Configuración
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                            <Bell class="mr-2 h-4 w-4" />
                            Notificaciones
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem :as-child="true">
                            <Link class="block w-full cursor-pointer" :href="logout()" @click.prevent="handleLogout"
                                as="button">
                            <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            Cerrar sesión
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </header>
    <!-- Empty State -->
        <div v-if="view === 'empty'" class="flex flex-col items-center justify-center py-20">
            <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-700">
                <Grid3X3 class="h-12 w-12 text-slate-400 dark:text-slate-500" />
            </div>
            <h2 class="mt-6 text-xl font-bold text-slate-900 dark:text-slate-100">Bienvenido al Dashboard</h2>
            <p class="mt-2 max-w-md text-center text-sm text-slate-500 dark:text-slate-400">
                Usa la búsqueda para encontrar pacientes, doctores, exámenes y más
            </p>
            <Button class="mt-6 bg-teal-600 hover:bg-teal-500 dark:bg-teal-500 dark:hover:bg-teal-400" @click="searchOpen = true">
                <Search class="mr-2 h-4 w-4" />
                Buscar
            </Button>
        </div>

        <!-- Search Results View -->
        <div v-else-if="view === 'results'" class="space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Resultados de búsqueda</h2>
                    <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">
                        Q laboratorio
                    </span>
                </div>
                <Button variant="ghost" size="sm" class="gap-1 text-slate-600 dark:text-slate-300" @click="handleBack">
                    <ArrowLeft class="h-4 w-4" />
                    Volver
                </Button>
            </div>

            <div class="space-y-3">
                <div
                    v-for="result in searchResults"
                    :key="result.id"
                    class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition-colors hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-slate-600"
                >
                    <div :class="`flex h-12 w-12 shrink-0 items-center justify-center rounded-lg ${result.bgColor}`">
                        <component :is="result.icon" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ result.title }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Solicitante: {{ result.requester }} · {{ result.date }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span
                                v-for="tag in result.tags"
                                :key="tag"
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="`text-sm font-semibold ${result.statusColor}`">{{ result.status }}</span>
                        <ChevronRight class="h-4 w-4 text-slate-400" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex-row">
                <p class="text-xs text-slate-500 dark:text-slate-400">Mostrando 1-2 de 2 resultados</p>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-slate-500 dark:text-slate-400">Por página: 5</span>
                    <div class="flex items-center gap-1">
                        <Button variant="ghost" size="icon" class="h-8 w-8">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button size="icon" class="h-8 w-8 bg-teal-600 text-white hover:bg-teal-500 dark:bg-teal-500 dark:hover:bg-teal-400">1</Button>
                        <Button variant="ghost" size="icon" class="h-8 w-8">
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
</template>


<style lang="scss" scoped></style>