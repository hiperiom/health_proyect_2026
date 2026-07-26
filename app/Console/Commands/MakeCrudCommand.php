<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {name : The singular model name (PascalCase)} 
                        {--migrate : Run standard migration} 
                        {--fresh : Run migrate:fresh --seed automatically without asking} 
                        {--rundev : Run composer run dev automatically} 
                        {--test : Run backend tests and frontend build} 
                        {--skip-test : Skip running tests}';
    protected $description = 'Generate a full CRUD (backend + frontend Inertia/Vue) following SOLID principles.';

    
    public function handle(): void
    {
        $model = Str::of($this->argument('name'))->trim()->toString();
        if (!ctype_upper($model[0])) {
            $this->error('Model name must be PascalCase (e.g. Country, Product).');
            return;
        }

        $plural = Str::of($model)->plural()->lower()->toString();
        $pluralUpper = Str::of($plural)->ucfirst()->toString();
        $timestamp = date('Y_m_d_His');

        $this->info("Generating CRUD for: {$model} ({$plural})");

        // Backend Generation
        $this->createModel($model);
        $this->createMigration($timestamp, $plural);
        $this->createFactory($model);
        $this->createSeeder($model);
        $this->createPolicy($model);
        $this->createRequests($model, $plural);
        $this->createResource($model, $plural);
        $this->createService($model, $plural);
        $this->createController($model, $plural);
        $this->updateWebRoutes($plural, $model);

        // Frontend Generation
        $this->createTypeScriptTypes($model, $plural);
        $this->createWayfinderRoutes($plural);
        $this->createIndexPage($model, $plural, $pluralUpper);
        $this->updateTypeScriptIndex($plural);
        $this->updateTypeScriptRoutesIndex($plural);
        $this->addSidebarNavigation($plural, $model, $pluralUpper);

        $this->newLine();
        $wantsFresh = $this->option('fresh');
        
        if ($wantsFresh) {
            $this->info('Ejecutando migrate:fresh --seed...');
            $this->call('migrate:fresh', ['--seed' => true]);
        }

        $this->info('Regenerating Wayfinder types...');
        $this->call('wayfinder:generate', ['--with-form' => true]);
        $this->info('CRUD generated successfully!');

        if ($this->option('migrate')) {
            $this->info('Running migration...');
            $this->call('migrate');
        }
        $runDev = $this->option('rundev');
        if ($runDev) {
            $this->newLine();
            $this->info('🚀 Iniciando entorno de desarrollo...');
            $this->info('💡 Presiona Ctrl+C en tu teclado para detener los servidores y volver a la terminal.');
            $this->newLine();
            
            // passthru() ejecuta el comando y deja que su salida (logs de Vite/PHP) 
            // se imprima directamente en la terminal, transfiriendo el control al usuario.
            passthru('composer run dev');
            
            $this->newLine();
            $this->info('Servidor de desarrollo detenido. Comando make:crud finalizado.');
        } else {
            $this->newLine();
            $this->info('✅ ¡Proceso finalizado!');
            $this->line('Puedes iniciar el servidor de desarrollo manualmente ejecutando:');
            $this->line('  <comment>composer run dev</comment>');
            $this->newLine();
        }
    }

    protected function createModel(string $model): void
    {
        $path = app_path("Models/{$model}.php");
        if (File::exists($path)) return;
        $content = <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$model} extends Model
{
    use HasFactory;
    protected \$fillable = ['name', 'description', 'value'];
}
PHP;
        File::put($path, $content);
    }

    protected function createMigration(string $timestamp, string $plural): void
    {
        $path = database_path("migrations/{$timestamp}_create_{$plural}_table.php");
        if (File::exists($path)) return;
        $content = <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('{$plural}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->string('value')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('{$plural}'); }
};
PHP;
        File::put($path, $content);
    }

    protected function createFactory(string $model): void
    {
        $path = database_path("factories/{$model}Factory.php");
        if (File::exists($path)) return;
        $content = <<<PHP
<?php
namespace Database\Factories;
use App\Models\\{$model};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$model}Factory extends Factory {
    protected \$model = {$model}::class;
    public function definition(): array {
        return [
            'name' => \$this->faker->company(),
            'description' => \$this->faker->sentence(),
            'value' => \$this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
PHP;
        File::put($path, $content);
    }

    protected function createSeeder(string $model): void
    {
        $path = database_path("seeders/{$model}Seeder.php");
        if (File::exists($path)) return;
        $content = <<<PHP
<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\\{$model};

class {$model}Seeder extends Seeder {
    public function run(): void {
        {$model}::factory()->count(50)->create();
    }
}
PHP;
        File::put($path, $content);
    }

    protected function createPolicy(string $model): void
    {
        $path = app_path("Policies/{$model}Policy.php");
        if (File::exists($path)) return;
        $content = <<<PHP
<?php
namespace App\Policies;
use App\Models\User;
use App\Models\\{$model};

class {$model}Policy {
    public function viewAny(User \$user): bool { return true; }
    public function create(User \$user): bool { return true; }
    public function update(User \$user, {$model} \$model): bool { return true; }
    public function delete(User \$user, {$model} \$model): bool { return true; }
}
PHP;
        File::put($path, $content);
    }

    protected function createRequests(string $model, string $plural): void
    {
        $dir = app_path("Http/Requests/{$plural}");
        File::ensureDirectoryExists($dir);
        
        $storeContent = <<<PHP
<?php
namespace App\Http\Requests\\{$plural};
use Illuminate\Foundation\Http\FormRequest;

class Store{$model}Request extends FormRequest {
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
PHP;
        File::put("{$dir}/Store{$model}Request.php", $storeContent);

        $updateContent = <<<PHP
<?php
namespace App\Http\Requests\\{$plural};
use Illuminate\Foundation\Http\FormRequest;

class Update{$model}Request extends FormRequest {
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
PHP;
        File::put("{$dir}/Update{$model}Request.php", $updateContent);
    }

    protected function createResource(string $model, string $plural): void
    {
        $dir = app_path("Http/Resources/{$plural}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$model}Resource.php";
        if (File::exists($path)) return;

        $content = <<<PHP
<?php
namespace App\Http\Resources\\{$plural};
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$model}Resource extends JsonResource {
    public function toArray(Request \$request): array {
        return [
            'id' => \$this->id,
            'name' => \$this->name,
            'description' => \$this->description,
            'value' => \$this->value,
            'createdAt' => \$this->created_at?->toISOString(),
            'updatedAt' => \$this->updated_at?->toISOString(),
        ];
    }
}
PHP;
        File::put($path, $content);
    }

    protected function createService(string $model, string $plural): void
    {
        $dir = app_path("Services/{$plural}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$model}Service.php";
        if (File::exists($path)) return;

        $content = <<<PHP
<?php
namespace App\Services\\{$plural};
use App\Models\\{$model};
use Illuminate\Pagination\LengthAwarePaginator;

class {$model}Service {
    public function getList(array \$filters): LengthAwarePaginator {
        \$query = {$model}::query();
        if (!empty(\$filters['search'])) {
            \$query->where('name', 'like', '%' . \$filters['search'] . '%');
        }
        \$perPage = \$filters['per_page'] ?? 10;
        return \$query->latest()->paginate(\$perPage);
    }
    public function store(array \$data): {$model} { return {$model}::create(\$data); }
    public function update({$model} \$item, array \$data): {$model} { \$item->update(\$data); return \$item; }
    public function destroy({$model} \$item): bool { return \$item->delete(); }
}
PHP;
        File::put($path, $content);
    }

    protected function createController(string $model, string $plural): void
    {
        $dir = app_path("Http/Controllers/{$plural}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$model}Controller.php";
        if (File::exists($path)) return;

        $content = <<<PHP
<?php
namespace App\Http\Controllers\\{$plural};
use App\Http\Controllers\Controller;
use App\Http\Requests\\{$plural}\\Store{$model}Request;
use App\Http\Requests\\{$plural}\\Update{$model}Request;
use App\Http\Resources\\{$plural}\\{$model}Resource;
use App\Models\\{$model};
use App\Services\\{$plural}\\{$model}Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class {$model}Controller extends Controller {
    public function __construct(protected {$model}Service \$service) {}

    public function index(Request \$request): Response {
        \$items = \$this->service->getList(\$request->query());
        return Inertia::render('{$plural}/Index', [
            'items' => fn () => {$model}Resource::collection(\$items),
            'filters' => \$request->only(['search', 'per_page']),
        ]);
    }
    public function store(Store{$model}Request \$request): RedirectResponse {
        \$this->service->store(\$request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('{$model} created.')]);
        return to_route('{$plural}.index');
    }
    public function edit(Request \$request, {$model} \$item): Response {
        return Inertia::render('{$plural}/Index', [
            'item' => fn () => new {$model}Resource(\$item),
        ]);
    }
    public function update(Update{$model}Request \$request, {$model} \$item): RedirectResponse {
        \$this->service->update(\$item, \$request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('{$model} updated.')]);
        return to_route('{$plural}.index');
    }
    public function destroy(Request \$request, {$model} \$item): RedirectResponse {
        \$this->service->destroy(\$item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('{$model} deleted.')]);
        return to_route('{$plural}.index');
    }
}
PHP;
        File::put($path, $content);
    }

    protected function updateWebRoutes(string $plural, string $model): void
    {
        $path = base_path('routes/web.php');
        $content = File::get($path);
        if (Str::contains($content, "'{$plural}.index'")) return;

        $routeBlock = <<<PHP

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('{$plural}', [App\Http\Controllers\\{$plural}\\{$model}Controller::class, 'index'])->name('{$plural}.index');
    Route::post('{$plural}', [App\Http\Controllers\\{$plural}\\{$model}Controller::class, 'store'])->name('{$plural}.store');
    Route::get('{$plural}/{item}/edit', [App\Http\Controllers\\{$plural}\\{$model}Controller::class, 'edit'])->name('{$plural}.edit');
    Route::patch('{$plural}/{item}', [App\Http\Controllers\\{$plural}\\{$model}Controller::class, 'update'])->name('{$plural}.update');
    Route::delete('{$plural}/{item}', [App\Http\Controllers\\{$plural}\\{$model}Controller::class, 'destroy'])->name('{$plural}.destroy');
});
PHP;
        File::append($path, $routeBlock);
    }

    protected function createTypeScriptTypes(string $model, string $plural): void
    {
        $dir = resource_path('js/types');
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$plural}.ts";
        if (File::exists($path)) return;

        $content = <<<TS
export type {$model} = {
    id: number;
    name: string;
    description: string | null;
    value: string | null;
    createdAt: string;
    updatedAt: string;
};
TS;
        File::put($path, $content);
    }

    protected function createWayfinderRoutes(string $plural): void
    {
        $dir = resource_path("js/routes/{$plural}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/index.ts";
        if (File::exists($path)) return;

        $content = <<<'TS'
import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'

export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({ url: index.url(options), method: 'get' })
index.definition = { methods: ["get","head"], url: '/{models}' } satisfies RouteDefinition<["get","head"]>
index.url = (options?: RouteQueryOptions) => index.definition.url + queryParams(options)
index.form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({ action: index.url(options), method: 'get' })

export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({ url: store.url(options), method: 'post' })
store.definition = { methods: ["post"], url: '/{models}' } satisfies RouteDefinition<["post"]>
store.url = (options?: RouteQueryOptions) => store.definition.url + queryParams(options)
store.form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({ action: store.url(options), method: 'post' })

export const edit = (args: any, options?: RouteQueryOptions): RouteDefinition<'get'> => ({ url: edit.url(args, options), method: 'get' })
edit.definition = { methods: ["get","head"], url: '/{models}/{item}' } satisfies RouteDefinition<["get","head"]>
edit.url = (args: any, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') args = { item: args }
    if (Array.isArray(args)) args = { item: args[0] }
    args = applyUrlDefaults(args)
    return edit.definition.url.replace('{item}', args.item.toString()) + queryParams(options)
}
edit.form = (args: any, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({ action: edit.url(args, options), method: 'get' })

export const update = (args: any, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({ url: update.url(args, options), method: 'patch' })
update.definition = { methods: ["patch"], url: '/{models}/{item}' } satisfies RouteDefinition<["patch"]>
update.url = (args: any, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') args = { item: args }
    if (Array.isArray(args)) args = { item: args[0] }
    args = applyUrlDefaults(args)
    return update.definition.url.replace('{item}', args.item.toString()) + queryParams(options)
}
update.form = (args: any, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({ action: update.url(args, options), method: 'post' })

export const destroy = (args: any, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({ url: destroy.url(args, options), method: 'delete' })
destroy.definition = { methods: ["delete"], url: '/{models}/{item}' } satisfies RouteDefinition<["delete"]>
destroy.url = (args: any, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') args = { item: args }
    if (Array.isArray(args)) args = { item: args[0] }
    args = applyUrlDefaults(args)
    return destroy.definition.url.replace('{item}', args.item.toString()) + queryParams(options)
}
destroy.form = (args: any, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({ action: destroy.url(args, options), method: 'post' })

const {models} = { index, store, edit, update, destroy }
export default {models}
TS;
        $content = str_replace('{models}', $plural, $content);
        File::put($path, $content);
    }

    protected function createIndexPage(string $model, string $plural, string $pluralUpper): void
    {
        $pluralLower = Str::of($pluralUpper)->lower();
        $this->info($pluralUpper.'-'.$pluralLower);
        $dir = resource_path("js/pages/{$pluralLower}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/Index.vue";
        if (File::exists($path)) return;

        $content = <<<'VUE'
<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, MoreVertical, Pencil, Plus, Search, Trash } from '@lucide/vue';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { index, store, update, destroy } from '@/routes/{models}';
import type { {Model} } from '@/types/{models}';

const page = usePage();

type Props = {
    items: {
        data: {Model}[];
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
const editingItem = ref<{Model} | null>(null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<{Model} | null>(null);
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

function openEditSheet(item: {Model}) { editingItem.value = item; open.value = true; }
function confirmDelete(item: {Model}) { itemToDelete.value = item; deleteDialogOpen.value = true; }
function deleteItem() {
    if (!itemToDelete.value) return;
    router.delete(destroy(itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteDialogOpen.value = false; itemToDelete.value = null; },
    });
}
function closeSheet() { open.value = false; editingItem.value = null; }
</script>

<template>
    <Head title="{pluralUpper}" />
    <div class="flex h-full flex-col space-y-6">
        <Alert v-if="page.props.flash?.toast?.type === 'success'" variant="default" class="mb-4 border-green-500 bg-green-50 dark:bg-green-950">
            <CircleCheck class="h-4 w-4" />
            <AlertTitle>Success</AlertTitle>
            <AlertDescription>{{ page.props.flash.toast.message }}</AlertDescription>
        </Alert>

        <div class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading variant="small" title="{pluralUpper}" description="Gestión y administración de {plural}." />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-72">
                    <Search class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" type="search" placeholder="Search by name..." class="pl-8" />
                </div>
                <Sheet v-model:open="open">
                    <SheetTrigger as-child>
                        <Button @click="editingItem = null"><Plus class="h-4 w-4 mr-2" /> New {Model}</Button>
                    </SheetTrigger>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle>{{ editingItem ? 'Edit' : 'Create' }} {Model}</SheetTitle>
                            <SheetDescription>{{ editingItem ? 'Update' : 'Create a new' }} {model} record.</SheetDescription>
                        </SheetHeader>
                        <Form :key="editingItem?.id ?? 'create'" v-bind="editingItem ? update.form(editingItem.id) : store.form()" class="space-y-6 px-4 mt-4" v-slot="{ errors, processing }" @success="closeSheet">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" name="name" :default-value="editingItem?.name" placeholder="{Model} name" required />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <Input id="description" name="description" :default-value="editingItem?.description" placeholder="Description" />
                                <InputError :message="errors.description" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="value">Value</Label>
                                <Input id="value" name="value" :default-value="editingItem?.value" placeholder="Value" />
                                <InputError :message="errors.value" />
                            </div>
                            <SheetFooter>
                                <SheetClose as-child><Button variant="secondary">Cancel</Button></SheetClose>
                                <Button type="submit" :disabled="processing">{{ editingItem ? 'Update' : 'Create' }}</Button>
                            </SheetFooter>
                        </Form>
                    </SheetContent>
                </Sheet>
            </div>
        </div>

        <Dialog :open="deleteDialogOpen" @update:open="(v) => (deleteDialogOpen = v)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {Model}</DialogTitle>
                    <DialogDescription>Are you sure you want to delete "{{ itemToDelete?.name }}"? This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child><Button variant="secondary">Cancel</Button></DialogClose>
                    <Button variant="destructive" @click="deleteItem">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div class="min-h-0 flex-1 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Description</th>
                        <th class="px-4 py-3 font-medium">Value</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3">{{ item.id }}</td>
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
                                        <DropdownMenuItem @click="openEditSheet(item)"><Pencil class="mr-2 h-4 w-4" /> Edit</DropdownMenuItem>
                                        <DropdownMenuItem @click="confirmDelete(item)"><Trash class="mr-2 h-4 w-4" /> Delete</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items.data.length">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No {plural} found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sticky bottom-0 z-10 -mx-1 flex flex-col gap-3 border-t bg-background px-1 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-muted-foreground">Showing {{ items.from }} to {{ items.to }} of {{ items.total }} results.</div>
            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Per page</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="w-20"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem><SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem><SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="items.current_page === 1" @click="router.get(index().url, { ...filters, page: items.current_page - 1 }, { preserveState: true, preserveScroll: true })">Previous</Button>
                    <Button variant="outline" size="sm" :disabled="items.current_page === items.last_page" @click="router.get(index().url, { ...filters, page: items.current_page + 1 }, { preserveState: true, preserveScroll: true })">Next</Button>
                </div>
            </div>
        </div>
    </div>
</template>
VUE;

        $replacements = [
            '{Model}' => $model, '{model}' => strtolower($model), '{Models}' => $pluralUpper,
            '{models}' => $plural, '{pluralUpper}' => $pluralUpper, '{plural}' => $plural,
        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        File::put($path, $content);
    }

    protected function updateTypeScriptIndex(string $plural): void
    {
        $path = resource_path('js/types/index.ts');
        if (!File::exists($path)) return;
        $content = File::get($path);
        $line = "export * from './{$plural}';";
        if (!Str::contains($content, $line)) File::append($path, PHP_EOL.$line);
    }

    protected function updateTypeScriptRoutesIndex(string $plural): void
    {
        $path = resource_path('js/routes/index.ts');
        if (!File::exists($path)) return;
        $content = File::get($path);
        $line = "import {$plural} from './{$plural}';";
        if (!Str::contains($content, $line)) File::append($path, PHP_EOL.$line);
    }
    
    protected function addSidebarNavigation(string $plural, string $model, string $pluralUpper): void
    {
        $path = resource_path('js/components/AppSidebar.vue');
        if (!File::exists($path)) {
            $this->warn("AppSidebar.vue not found at {$path}");
            return;
        }
        
        $content = File::get($path);
        
        // 1. Verificar si ya fue agregado para evitar duplicados
        if (Str::contains($content, "title: '{$pluralUpper}'")) {
            $this->warn("Navigation item for '{$pluralUpper}' already exists in AppSidebar.vue");
            return;
        }

        // 2. Agregar el icono si no existe (usando regex para ser flexible con el formato de importación)
        $icon = 'FileText'; 
        if (!Str::contains($content, $icon)) {
            $content = preg_replace(
                "/(import \{[^}]+) from '@lucide\/vue';/",
                "\$1, {$icon} } from '@lucide/vue';",
                $content
            );
        }

        // 3. Agregar la importación de la ruta si no existe
        $routeImport = "import { index as {$plural}Index } from '@/routes/{$plural}';";
        if (!Str::contains($content, $routeImport)) {
            $content = str_replace(
                "import type { NavItem } from '@/types';",
                "import type { NavItem } from '@/types';\n{$routeImport}",
                $content
            );
        }

        // 4. Inyección segura del nuevo ítem del menú después de 'Dashboard' usando Regex
        // Esto busca el objeto del Dashboard sin importar los espacios o saltos de línea exactos
        $pattern = "/(\{\s*title:\s*['\"]Dashboard['\"],\s*href:\s*dashboardUrl\.value,\s*icon:\s*LayoutGrid,\s*\},)/";
        $replacement = "\$1\n        {\n            title: '{$pluralUpper}',\n            href: {$plural}Index().url,\n            icon: {$icon},\n        },";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            File::put($path, $content);
            $this->info("Updated: {$path}");
        } else {
            $this->warn("Could not find the 'Dashboard' menu item structure to append to. Please add the '{$pluralUpper}' navigation item manually.");
        }
    }
}