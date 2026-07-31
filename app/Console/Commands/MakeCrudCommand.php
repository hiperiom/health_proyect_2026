<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {--timestamp= : Fecha y hora en formato Y_m_d_His}
        {--modelTitle= : Título del modelo en español y plural (ej. Especialidades Médicas)}
        {--modelTitleSingular= : Título del modelo en español y singular (ej. Especialidad Médica)}
        {--modelNameSingular= : Nombre de la clase del modelo en inglés y singular (ej. MedicalSpecialty)}
        {--modelNamePlural= : Nombre del modelo en inglés y plural (ej. MedicalSpecialties)}
        {--modelNameKebabCase= : Nombre del modelo en formato kebab-case (ej. medical-specialties)}
        {--modelNameRoutes= : Nombre del prefijo o grupo de rutas (ej. medical-specialties)}
        {--modelNameController= : Nombre de la clase del controlador (ej. MedicalSpecialtyController)}
        {--modelNameTable= : Nombre en minúscula unido por underscore}
        {--migrate : Run standard migration} 
        {--fresh : Run migrate:fresh --seed automatically without asking} 
        {--rundev : Run composer run dev automatically}';

    protected $description = 'Generate a full CRUD (backend + frontend Inertia/Vue) following SOLID principles.';
    /*

                            {name : The singular model name (PascalCase)}
                            {--sidebar-name= : Nombre que se verá en el sidebar (Default: PluralUpper)}
                            {--module-title= : Título del módulo arriba de la descripción (Default: PluralUpper)}
                            {--module-description= : Texto debajo del título del módulo (Default: Gestión y administración de...)}
                            {--new-button-text= : Texto del botón de nuevo registro, singular y capitalizado (Default: New Model)}



                            {--test : Run backend tests and frontend build}
                            {--skip-test : Skip running tests}
    */

    public function handle(): void
    {

        $config = [];
        $config['timestamp'] = $this->option('timestamp');
        $config['modelTitle'] = $this->option('modelTitle');
        $this->warn($config['modelTitle']);
        $config['modelTitleSingular'] = $this->option('modelTitleSingular');
        $this->warn($config['modelTitleSingular']);
        $config['modelNameSingular'] = $this->option('modelNameSingular');
        $this->warn($config['modelNameSingular']);
        $config['modelNamePlural'] = $this->option('modelNamePlural');
        $this->warn($config['modelNamePlural']);
        $config['modelNameKebabCase'] = $this->option('modelNameKebabCase');
        $this->warn($config['modelNameKebabCase']);
        $config['modelNameRoutes'] = $this->option('modelNameRoutes');
        $this->warn($config['modelNameRoutes']);
        $config['modelNameController'] = $this->option('modelNameController');
        $this->warn($config['modelNameController']);
        $config['modelNameTable'] = $this->option('modelNameTable');
        $this->warn($config['modelNameTable']);

        // $this->info("Generating CRUD for: {$model} ({$plural})");
        $nombreRama = $this->option('modelNameTable');

        $this->info("Creando y cambiando a la rama: {$nombreRama}...");

        // Ejecutamos el comando en la raíz del proyecto Laravel
        $result = Process::path(base_path())
            ->run("git checkout -b {$nombreRama}");

        $this->info("🚀 Iniciando scaffolding integral para: {$config['modelNameSingular']}");

        // Backend Generation
        $this->createModel($config);
        $this->createMigration($config);
        $this->createFactory($config);
        $this->createSeeder($config);
        $this->createPolicy($config);
        $this->createRequests($config);

        $this->createResource($config);
        $this->createService($config);
        $this->createController($config);
        $this->updateWebRoutes($config);

        // Frontend Generation
        $this->createTypeScriptTypes($config);
        $this->createWayfinderRoutes($config);
        $this->createIndexPage($config);
        $this->updateTypeScriptIndex($config);
        $this->updateTypeScriptRoutesIndex($config);
        $this->appendToModuleSidebarConfig($config);

        $this->newLine();

        $wantsFresh = $this->option('fresh') || true;

        if ($wantsFresh) {
            $this->info('Ejecutando migrate:fresh --seed...');
            $this->call('migrate:fresh', ['--seed' => true]);
        }

        // System registry: register the new module + CRUD permissions so the
        // EnsureModuleAccess middleware and the superuser permission grants
        // know about it right after `migrate:fresh --seed` finishes.
        $this->registerModuleAndPermissions($config);

        $this->info('Regenerating Wayfinder types...');
        $this->call('wayfinder:generate', ['--with-form' => true]);
        $this->info('CRUD generated successfully!');

        if ($this->option('migrate')) {
            $this->info('Running migration...');
            $this->call('migrate');
        }
        $runDev = $this->option('rundev') || true;
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
        /*
        $plural = Str::of($model)->plural()->lower()->toString();
        $pluralUpper = Str::of($plural)->ucfirst()->toString();


        $sidebarName = $this->option('sidebar-name') ?: $pluralUpper;
        $moduleTitle = $this->option('module-title') ?: $pluralUpper;
        $moduleDescription = $this->option('module-description') ?: "Gestión y administración de {$plural}.";
        $newButtonText = $this->option('new-button-text') ?: "New {$model}";










        */
    }

    /**
     * Register the freshly generated module in the `modules` table and
     * attach the default CRUD permissions (read, create, update, delete)
     * to the `permissions` table. This method is idempotent: re-running
     * the make:crud command will not duplicate rows. The method is a
     * no-op when the underlying tables do not exist yet (e.g. when the
     * migration files for modules/permissions have not been executed).
     */
    protected function registerModuleAndPermissions(array $config): void
    {
        $moduleKey = $config['modelNameKebabCase'];
        // Use the human-friendly modelTitle so the displayed name matches
        // the one used in moduleSidebarConfig and the Index.vue page.
        $displayName = $config['modelTitle'];
        $description = "Gestión y administración de {$displayName}.";

        // The fresh seed wipes these tables, so we only attempt to seed
        // them when the schema is already in place. Skip silently otherwise.
        if (! Schema::hasTable('modules') || ! Schema::hasTable('permissions')) {
            $this->warn('Las tablas `modules` o `permissions` aún no existen. Se omitirá el registro automático.');

            return;
        }

        $now = now();

        // 1) Upsert module row keyed by the technical `name` (kebab-case).
        $moduleId = DB::table('modules')->where('name', $moduleKey)->value('id');

        if ($moduleId === null) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => $moduleKey,
                'display_name' => $displayName,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->info("✔ Módulo registrado: {$moduleKey} (display_name: {$displayName})");
        } else {
            $this->warn("El módulo \"{$moduleKey}\" ya existe (id={$moduleId}). Se omite su creación.");
        }

        // 2) Insert the four default CRUD permissions. They follow the
        //    pattern used by the rest of the seeders:
        //      name:   View | Create | Update | Delete
        //      slug:   {module}.read | .create | .update | .delete
        //      module: matches modules.name so EnsureModuleAccess and the
        //              permissions page can group them correctly.
        $crudActions = [
            ['name' => 'View',   'slug' => "{$moduleKey}.read",   'description' => "View the {$displayName} list and details"],
            ['name' => 'Create', 'slug' => "{$moduleKey}.create", 'description' => "Create new {$displayName}"],
            ['name' => 'Update', 'slug' => "{$moduleKey}.update", 'description' => "Edit existing {$displayName}"],
            ['name' => 'Delete', 'slug' => "{$moduleKey}.delete", 'description' => "Delete {$displayName}"],
        ];

        $inserted = 0;
        foreach ($crudActions as $action) {
            $exists = DB::table('permissions')
                ->where('slug', $action['slug'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $action['name'],
                'slug' => $action['slug'],
                'module' => $moduleKey,
                'description' => $action['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        if ($inserted > 0) {
            $this->info("✔ {$inserted} permiso(s) CRUD registrado(s) para el módulo \"{$moduleKey}\".");
        } else {
            $this->warn("Los permisos CRUD para \"{$moduleKey}\" ya existían. Se omiten.");
        }
    }

    protected function createModel(array $config): void
    {
        $path = app_path("Models/{$config['modelNameSingular']}.php");
        if (File::exists($path)) {
            return;
        }
        $content = <<<PHP
            <?php
            namespace App\Models;
            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Database\Eloquent\Model;

            class {$config['modelNameSingular']} extends Model
            {
                use HasFactory;
                protected \$fillable = ['name', 'description', 'value'];
            }
            PHP;
        File::put($path, $content);
    }

    protected function createMigration(array $config): void
    {
        $path = database_path("migrations/{$config['timestamp']}_create_{$config['modelNameTable']}_table.php");
        if (File::exists($path)) {
            return;
        }
        $content = <<<PHP
            <?php
            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration {
                public function up(): void {
                    Schema::create('{$config['modelNameTable']}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->string('name');
                        \$table->text('description')->nullable();
                        \$table->string('value')->nullable();
                        \$table->timestamps();
                    });
                }
                public function down(): void { Schema::dropIfExists('{$config['modelNameTable']}'); }
            };
            PHP;
        File::put($path, $content);
    }

    protected function createFactory(array $config): void
    {
        $path = database_path("factories/{$config['modelNameSingular']}Factory.php");
        if (File::exists($path)) {
            return;
        }
        $content = <<<PHP
            <?php
            namespace Database\Factories;
            use App\Models\\{$config['modelNameSingular']};
            use Illuminate\Database\Eloquent\Factories\Factory;

            class {$config['modelNameSingular']}Factory extends Factory {
                protected \$model = {$config['modelNameSingular']}::class;
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

    protected function createSeeder(array $config): void
    {
        $path = database_path("seeders/{$config['modelNameSingular']}Seeder.php");
        if (File::exists($path)) {
            return;
        }
        $content = <<<PHP
            <?php
            namespace Database\Seeders;
            use Illuminate\Database\Seeder;
            use App\Models\\{$config['modelNameSingular']};

            class {$config['modelNameSingular']}Seeder extends Seeder {
                public function run(): void {
                    {$config['modelNameSingular']}::factory()->count(50)->create();
                }
            }
            PHP;
        File::put($path, $content);
    }

    protected function createPolicy(array $config): void
    {
        $path = app_path("Policies/{$config['modelNameSingular']}Policy.php");
        if (File::exists($path)) {
            return;
        }
        $content = <<<PHP
            <?php
            namespace App\Policies;
            use App\Models\User;
            use App\Models\\{$config['modelNameSingular']};

            class {$config['modelNameSingular']}Policy {
                public function viewAny(User \$user): bool { return true; }
                public function create(User \$user): bool { return true; }
                public function update(User \$user, {$config['modelNameSingular']} \$model): bool { return true; }
                public function delete(User \$user, {$config['modelNameSingular']} \$model): bool { return true; }
            }
            PHP;
        File::put($path, $content);
    }

    protected function createRequests(array $config): void
    {
        $dir = app_path("Http/Requests/{$config['modelNameSingular']}");
        File::ensureDirectoryExists($dir);

        $storeContent = <<<PHP
            <?php
            namespace App\Http\Requests\\{$config['modelNameSingular']};
            use Illuminate\Foundation\Http\FormRequest;

            class Store{$config['modelNameSingular']}Request extends FormRequest {
                public function rules(): array {
                    return [
                        'name' => ['required', 'string', 'max:255'],
                        'description' => ['nullable', 'string'],
                        'value' => ['nullable', 'string', 'max:255'],
                    ];
                }
            }
            PHP;
        File::put("{$dir}/Store{$config['modelNameSingular']}Request.php", $storeContent);

        $updateContent = <<<PHP
        <?php
        namespace App\Http\Requests\\{$config['modelNameSingular']};
        use Illuminate\Foundation\Http\FormRequest;

        class Update{$config['modelNameSingular']}Request extends FormRequest {
            public function rules(): array {
                return [
                    'name' => ['required', 'string', 'max:255'],
                    'description' => ['nullable', 'string'],
                    'value' => ['nullable', 'string', 'max:255'],
                ];
            }
        }
        PHP;
        File::put("{$dir}/Update{$config['modelNameSingular']}Request.php", $updateContent);
    }

    protected function createResource(array $config): void
    {
        $dir = app_path("Http/Resources/{$config['modelNameSingular']}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$config['modelNameSingular']}Resource.php";
        if (File::exists($path)) {
            return;
        }

        $content = <<<PHP
            <?php
            namespace App\Http\Resources\\{$config['modelNameSingular']};
            use Illuminate\Http\Request;
            use Illuminate\Http\Resources\Json\JsonResource;

            class {$config['modelNameSingular']}Resource extends JsonResource {
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

    protected function createService(array $config): void
    {
        $dir = app_path("Services/{$config['modelNameSingular']}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$config['modelNameSingular']}Service.php";
        if (File::exists($path)) {
            return;
        }

        $content = <<<PHP
            <?php
            namespace App\Services\\{$config['modelNameSingular']};
            use App\Models\\{$config['modelNameSingular']};
            use Illuminate\Pagination\LengthAwarePaginator;

            class {$config['modelNameSingular']}Service {
                public function getList(array \$filters): LengthAwarePaginator {
                    \$query = {$config['modelNameSingular']}::query();
                    if (!empty(\$filters['search'])) {
                        \$query->where('name', 'like', '%' . \$filters['search'] . '%');
                    }
                    \$perPage = \$filters['per_page'] ?? 10;
                    return \$query->latest()->paginate(\$perPage);
                }
                public function store(array \$data): {$config['modelNameSingular']} { return {$config['modelNameSingular']}::create(\$data); }
                public function update({$config['modelNameSingular']} \$item, array \$data): {$config['modelNameSingular']} { \$item->update(\$data); return \$item; }
                public function destroy({$config['modelNameSingular']} \$item): bool { return \$item->delete(); }
            }
            PHP;
        File::put($path, $content);
    }

    protected function createController(array $config): void
    {
        $dir = app_path("Http/Controllers/{$config['modelNamePlural']}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$config['modelNamePlural']}Controller.php";
        if (File::exists($path)) {
            return;
        }

        $content = <<<PHP
            <?php
            namespace App\Http\Controllers\\{$config['modelNamePlural']};
            use App\Http\Controllers\Controller;
            use App\Http\Requests\\{$config['modelNameSingular']}\\Store{$config['modelNameSingular']}Request;
            use App\Http\Requests\\{$config['modelNameSingular']}\\Update{$config['modelNameSingular']}Request;
            use App\Http\Resources\\{$config['modelNameSingular']}\\{$config['modelNameSingular']}Resource;
            use App\Models\\{$config['modelNameSingular']};
            use App\Services\\{$config['modelNameSingular']}\\{$config['modelNameSingular']}Service;
            use Illuminate\Http\RedirectResponse;
            use Illuminate\Http\Request;
            use Inertia\Inertia;
            use Inertia\Response;

            class {$config['modelNamePlural']}Controller extends Controller {
                public function __construct(protected {$config['modelNameSingular']}Service \$service) {}

                public function index(Request \$request): Response {
                    \$items = \$this->service->getList(\$request->query());
                    return Inertia::render('{$config['modelNameTable']}/Index', [
                        'items' => fn () => {$config['modelNameSingular']}Resource::collection(\$items),
                        'filters' => \$request->only(['search', 'per_page']),
                    ]);
                }
                public function store(Store{$config['modelNameSingular']}Request \$request): RedirectResponse {
                    \$this->service->store(\$request->validated());
                    Inertia::flash('toast', ['type' => 'success', 'message' => __('{$config['modelTitle']} created.')]);
                    return to_route('{$config['modelNameKebabCase']}.index');
                }
                public function edit(Request \$request, {$config['modelNameSingular']} \$item): Response {
                    return Inertia::render('{$config['modelNameTable']}/Index', [
                        'item' => fn () => {$config['modelNameSingular']}Resource(\$item),
                    ]);
                }
                public function update(Update{$config['modelNameSingular']}Request \$request, {$config['modelNameSingular']} \$item): RedirectResponse {
                    \$this->service->update(\$item, \$request->validated());
                    Inertia::flash('toast', ['type' => 'success', 'message' => __('{$config['modelTitle']} updated.')]);
                    return to_route('{$config['modelNameKebabCase']}.index');
                }
                public function destroy(Request \$request, {$config['modelNameSingular']} \$item): RedirectResponse {
                    \$this->service->destroy(\$item);
                    Inertia::flash('toast', ['type' => 'success', 'message' => __('{$config['modelTitle']} deleted.')]);
                    return to_route('{$config['modelNameKebabCase']}.index');
                }
            }
            PHP;
        File::put($path, $content);
    }

    protected function updateWebRoutes(array $config): void
    {
        $path = base_path('routes/web.php');
        $content = File::get($path);
        if (Str::contains($content, "'{$config['modelNameKebabCase']}.index'")) {
            return;
        }

        $routeBlock = <<<PHP
        use App\Http\Controllers\\{$config['modelNamePlural']}\\{$config['modelNamePlural']}Controller;
        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('{$config['modelNameKebabCase']}', [{$config['modelNamePlural']}Controller::class, 'index'])->name('{$config['modelNameKebabCase']}.index');
            Route::post('{$config['modelNameKebabCase']}', [{$config['modelNamePlural']}Controller::class, 'store'])->name('{$config['modelNameKebabCase']}.store');
            Route::get('{$config['modelNameKebabCase']}/{item}/edit', [{$config['modelNamePlural']}Controller::class, 'edit'])->name('{$config['modelNameKebabCase']}.edit');
            Route::patch('{$config['modelNameKebabCase']}/{item}', [{$config['modelNamePlural']}Controller::class, 'update'])->name('{$config['modelNameKebabCase']}.update');
            Route::delete('{$config['modelNameKebabCase']}/{item}', [{$config['modelNamePlural']}Controller::class, 'destroy'])->name('{$config['modelNameKebabCase']}.destroy');
        });
        PHP;
        File::append($path, $routeBlock);
    }

    protected function createTypeScriptTypes(array $config): void
    {
        $dir = resource_path('js/types');
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$config['modelNameTable']}.ts";
        if (File::exists($path)) {
            return;
        }

        $content = <<<TS
            export type {$config['modelNameSingular']} = {
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

    protected function createWayfinderRoutes(array $config): void
    {
        $dir = resource_path("js/routes/{$config['modelNameTable']}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/index.ts";
        if (File::exists($path)) {
            return;
        }

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
        $content = str_replace('{models}', $config['modelNameSingular'], $content);
        File::put($path, $content);
    }

    protected function createIndexPage(array $config): void
    {
        $dir = resource_path("js/pages/{$config['modelNameTable']}");
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/Index.vue";
        if (File::exists($path)) {
            return;
        }

        $content = <<<'VUE'
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
            import type { {modelNameSingular} } from '@/types/{modelNameTable}';
            import { index, store, update, destroy } from '@/routes/{modelNameKebabCase}';

            const page = usePage();

            type Props = {
                items: {
                    data: {modelNameSingular}[];
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
            const editingItem = ref<{modelNameSingular} | null>(null);
            const deleteDialogOpen = ref(false);
            const itemToDelete = ref<{modelNameSingular} | null>(null);
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

            function openEditSheet(item: {modelNameSingular}) { editingItem.value = item; open.value = true; }
            function confirmDelete(item: {modelNameSingular}) { itemToDelete.value = item; deleteDialogOpen.value = true; }
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
                                description: 'Bienvenidos al módulo {modelTitle}. Esta es una guia rápida sobre el uso de esta pantalla.',
                            },
                        },
                        {
                            element: '#tour-search',
                            popover: {
                                title: '🔍 Buscar {modelTitleSingular}',
                                description: 'Escribe la {modelTitleSingular} para filtrar la lista en tiempo real.',
                                side: 'left',
                                align: 'start',
                            },
                        },
                        {
                            element: '#tour-new-btn',
                            popover: {
                                title: '➕ Crear {modelTitleSingular}',
                                description: 'Al hacer clic aquí, se abrirá un panel lateral para registrar una nueva {modelTitleSingular}. ¡Vamos a abrirlo!',
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
                                description: 'Usa "Guardar" para guardar la nueva {modelTitleSingular}, o "Cancelar" para descartar los cambios y cerrar el panel.',
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
                                description: 'Aquí se listan las {modelTitle}.',
                                side: 'top',
                                align: 'start',
                            },
                        },
                        {
                            element: '#tour-actions',
                            popover: {
                                title: '⚙️ Acciones por {modelTitleSingular}',
                                description: 'Usa este menú (icono de 3 puntos) para Editar o Eliminar un {modelTitleSingular} en específico.',
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
            <Head title="{modelTitle}" />
            <div class="flex h-full flex-col space-y-6">
                <Alert v-if="page.props.flash?.toast?.type === 'success'" variant="default" class="mb-4 border-green-500 bg-green-50 dark:bg-green-950">
                    <CircleCheck class="h-4 w-4" />
                    <AlertTitle>Success</AlertTitle>
                    <AlertDescription>{{ page.props.flash.toast.message }}</AlertDescription>
                </Alert>
                <div class="flex flex-col gap-4 px-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading 
                        variant="small" 
                        title="{modelTitle}" 
                        description="Gestión y administración de {modelTitle}." 
                    />
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-72" id="tour-search">
                            <Search class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"  />
                            <Input v-model="search" type="search" placeholder="Buscar por {modelTitleSingular}..." class="pl-8" />
                        </div>
                        <Button variant="outline" size="icon" @click="startTour" title="Ayuda para esta pantalla">
                            <HelpCircle class="h-4 w-4" />
                        </Button>
                        <Sheet v-model:open="open">
                            <SheetTrigger as-child id="tour-new-btn">
                                <Button @click="editingItem = null"><Plus class="h-4 w-4 mr-2" /> Crear {modelTitleSingular}</Button>
                            </SheetTrigger>
                            <SheetContent>
                                <SheetHeader>
                                    <SheetTitle>{{ editingItem ? 'Editar' : 'Crear' }} {modelTitleSingular}</SheetTitle>
                                    <SheetDescription>{{ editingItem ? 'Actualizar' : 'Crear una nueva' }} {modelTitleSingular}.</SheetDescription>
                                </SheetHeader>
                                <Form :key="editingItem?.id ?? 'create'" v-bind="editingItem ? update.form(editingItem.id) : store.form()" class="space-y-6 px-4 mt-4" v-slot="{ errors, processing }" @success="closeSheet">
                                    <div id="tour-form" >
                                        <div class="grid gap-2">
                                            <Label for="name">Nombre<span style="color:red">*</span></Label>
                                            <Input id="name" name="name" :default-value="editingItem?.name" placeholder="{modelTitleSingular}" required />
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
                            <DialogTitle>Eliminar {modelNameSingular}</DialogTitle>
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
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No se encontraron {modelTitle}.</td>
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
        VUE;

        $replacements = [
            '{modelNameTable}' => $config['modelNameTable'],
            '{modelNameSingular}' => $config['modelNameSingular'],
            '{modelTitle}' => $config['modelTitle'],
            '{modelNameKebabCase}' => $config['modelNameKebabCase'],
            '{modelTitleSingular}' => $config['modelTitleSingular'],

        ];
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        File::put($path, $content);
    }

    protected function updateTypeScriptIndex(array $config): void
    {
        $path = resource_path('js/types/index.ts');
        if (! File::exists($path)) {
            return;
        }
        $content = File::get($path);
        $line = "export * from './{$config['modelNameTable']}';";
        if (! Str::contains($content, $line)) {
            File::append($path, PHP_EOL.$line);
        }
    }

    protected function updateTypeScriptRoutesIndex(array $config): void
    {
        $path = resource_path('js/routes/index.ts');
        if (! File::exists($path)) {
            return;
        }
        $content = File::get($path);
        $line = "import {$config['modelNameKebabCase']} from './{$config['modelNameKebabCase']}';";
        if (! Str::contains($content, $line)) {
            File::append($path, PHP_EOL.$line);
        }
    }

    protected function addSidebarNavigation(array $config): void
    {
        $path = resource_path('js/components/AppSidebar.vue');
        if (! File::exists($path)) {
            $this->warn("AppSidebar.vue not found at {$path}");

            return;
        }

        $content = File::get($path);

        // 1. Verificar si ya fue agregado para evitar duplicados
        if (Str::contains($content, "title: '{$config['modelTitle']}'")) {
            $this->warn("Navigation item for '{$config['modelTitle']}' already exists in AppSidebar.vue");

            return;
        }

        // 2. Agregar el icono si no existe (usando regex para ser flexible con el formato de importación)
        $icon = 'FileText';
        if (! Str::contains($content, $icon)) {
            $content = preg_replace(
                "/(import \{[^}]+) from '@lucide\/vue';/",
                "\$1, {$icon} } from '@lucide/vue';",
                $content
            );
        }

        // 3. Agregar la importación de la ruta si no existe
        $routeImport = "import { index as {$config['modelNameSingular']}Index } from '@/routes/{$config['modelNameKebabCase']}';";
        if (! Str::contains($content, $routeImport)) {
            $content = str_replace(
                "import type { NavItem } from '@/types';",
                "import type { NavItem } from '@/types';\n{$routeImport}",
                $content
            );
        }

        // 4. Inyección segura del nuevo ítem del menú después de 'Dashboard' usando Regex
        // Esto busca el objeto del Dashboard sin importar los espacios o saltos de línea exactos
        $pattern = "/(\{\s*title:\s*['\"]Dashboard['\"],\s*href:\s*dashboardUrl\.value,\s*icon:\s*LayoutGrid,\s*\},)/";
        $replacement = "\$1\n        {\n            title: '{$config['modelTitle']}',\n            href: {$config['modelNameSingular']}Index().url,\n            icon: {$icon},\n        },";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            File::put($path, $content);
            $this->info("Updated: {$path}");
        } else {
            $this->warn("Could not find the 'Dashboard' menu item structure to append to. Please add the '{$config['modelNameSingular']}' navigation item manually.");
        }
    }

    /**
     * Persist the sidebar metadata for the freshly generated module
     * into `resources/js/config/modules.ts`. The AppSidebar reads
     * that file at runtime to build its `mainNavItems`, so as soon
     * as the entry is in place the new module shows up in the
     * sidebar for any user that has access to it.
     *
     * Idempotent: if an entry for the same module key already
     * exists, this method does nothing.
     */
    protected function appendToModuleSidebarConfig(array $config): void
    {
        $moduleKey = $config['modelNameKebabCase'];
        $displayName = $config['modelTitle'];
        $iconName = 'FileText';

        $path = resource_path('js/config/modules.ts');
        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $this->moduleSidebarConfigTemplate());
        }

        $contents = File::get($path);

        // Idempotency: skip if a key for this module already exists.
        if (preg_match("/['\"]".preg_quote($moduleKey, '/')."['\"]\s*:/", $contents) === 1) {
            $this->warn("La entrada para \"{$moduleKey}\" ya existe en moduleSidebarConfig. Se omite.");

            return;
        }

        $entry = "    '{$moduleKey}': { title: '{$displayName}', icon: {$iconName} },";
        $contents = preg_replace(
            '/(\};)(\s*)$/',
            $entry."\n$1$2",
            $contents,
            1
        );

        File::put($path, $contents);
        $this->info("moduleSidebarConfig actualizado con el módulo \"{$moduleKey}\".");
    }

    /**
     * Return a minimal starting template for the moduleSidebarConfig
     * file in case it does not exist yet.
     */
    protected function moduleSidebarConfigTemplate(): string
    {
        return <<<'TPL'
import { FileText } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

export type ModuleSidebarConfig = {
    title: string;
    icon: LucideIcon;
    iconFallback?: string;
};

export const moduleSidebarConfig: Record<string, ModuleSidebarConfig> = {
};

export const defaultModuleSidebarEntry: ModuleSidebarConfig = {
    title: '',
    icon: FileText,
};
TPL;
    }
}
