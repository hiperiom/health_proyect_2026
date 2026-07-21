<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {name : The singular model name (PascalCase)} {--migrate : Run the migration after generating files}';
    protected $description = 'Generate a full CRUD (backend + frontend Inertia/Vue) for the given model';

    public function handle(): void
    {
        $model = Str::of($this->argument('name'))->trim()->toString();

        if (!ctype_upper($model[0])) {
            $this->error('Model name must be PascalCase (e.g. Patient, Product, Post).');
            return;
        }

        $plural = Str::of($model)->plural()->lower()->toString();
        $pluralUpper = Str::of($plural)->ucfirst()->toString();
        $timestamp = date('Y_m_d_His');

        $this->info("Generating CRUD for: {$model} ({$plural})");

        // Backend
        $this->createModel($model);
        $this->createMigration($timestamp, $plural, $model);
        $this->createRequests($model);
        $this->createController($model, $plural);
        $this->updateWebRoutes($plural, $model);

        // Frontend
        $this->createTypeScriptTypes($model, $plural);
        $this->createWayfinderRoutes($plural, $model);
        $this->createIndexPage($model, $plural);
        $this->updateTypeScriptIndex($plural);
        $this->updateTypeScriptRoutesIndex($plural);

        $this->info('Regenerating Wayfinder types...');
        $this->call('wayfinder:generate', ['--with-form' => true]);

        $this->info('CRUD generated successfully!');

        if ($this->option('migrate')) {
            $this->info('Running migration...');
            $this->call('migrate');
        }
    }

    protected function createModel(string $model): void
    {
        $path = app_path("Models/{$model}.php");
        if (File::exists($path)) {
            $this->warn("Model already exists: {$path}");
            return;
        }

        $content = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class {$model} extends Model
{
    protected function casts(): array
    {
        return [];
    }
}

PHP;

        File::put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function createMigration(string $timestamp, string $plural, string $model): void
    {
        $path = database_path("migrations/{$timestamp}_create_{$plural}_table.php");
        if (File::exists($path)) {
            $this->warn("Migration already exists: {$path}");
            return;
        }

        if (Schema::hasTable($plural)) {
            $this->warn("Table '{$plural}' already exists in the database. Skipping migration creation.");
            return;
        }

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$plural}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$plural}');
    }
};

PHP;

        File::put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function createRequests(string $model): void
    {
        $dir = app_path("Http/Requests/{$model}s");
        File::ensureDirectoryExists($dir);

        // Store
        $storePath = "{$dir}/Store{$model}Request.php";
        if (!File::exists($storePath)) {
            File::put($storePath, <<<PHP
<?php

namespace App\Http\Requests\\{$model}s;

use Illuminate\Foundation\Http\FormRequest;

class Store{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
PHP);
            $this->info("Created: {$storePath}");
        }

        // Update
        $updatePath = "{$dir}/Update{$model}Request.php";
        if (!File::exists($updatePath)) {
            File::put($updatePath, <<<PHP
<?php

namespace App\Http\Requests\\{$model}s;

use Illuminate\Foundation\Http\FormRequest;

class Update{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
PHP);
            $this->info("Created: {$updatePath}");
        }
    }

    protected function createController(string $model, string $plural): void
    {
        $dir = app_path("Http/Controllers/{$model}s");
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/{$model}Controller.php";
        if (File::exists($path)) {
            $this->warn("Controller already exists: {$path}");
            return;
        }

        $content = <<<PHP
<?php

namespace App\Http\Controllers\\{$model}s;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\{$model}s\\Store{$model}Request;
use App\\Http\\Requests\\{$model}s\\Update{$model}Request;
use App\\Models\\{$model};
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Inertia\\Inertia;
use Inertia\\Response;

class {$model}Controller extends Controller
{
    public function index(Request \$request): Response
    {
        \$items = {$model}::latest()->paginate();

        return Inertia::render('{$plural}/Index', [
            'items' => \$items->through(fn ({$model} \$item) => [
                'id' => \$item->id,
                'name' => \$item->name,
                'description' => \$item->description,
                'createdAt' => \$item->created_at->toISOString(),
                'updatedAt' => \$item->updated_at->toISOString(),
            ]),
        ]);
    }

    public function store(Store{$model}Request \$request): RedirectResponse
    {
        \$item = {$model}::create(\$request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('{$model} created.')]);

        return to_route('{$plural}.edit', ['{$plural}' => \$item->id]);
    }

    public function edit(Request \$request, {$model} \$item): Response
    {
        return Inertia::render('{$plural}/Edit', [
            'item' => [
                'id' => \$item->id,
                'name' => \$item->name,
                'description' => \$item->description,
            ],
        ]);
    }

    public function update(Update{$model}Request \$request, {$model} \$item): RedirectResponse
    {
        \$item->update(\$request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('{$model} updated.')]);

        return to_route('{$plural}.edit', ['{$plural}' => \$item->id]);
    }

    public function destroy(Request \$request, {$model} \$item): RedirectResponse
    {
        \$item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('{$model} deleted.')]);

        return to_route('{$plural}.index');
    }
}

PHP;

        File::put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function updateWebRoutes(string $plural, string $model): void
    {
        $path = base_path('routes/web.php');
        if (!File::exists($path)) {
            $this->warn("routes/web.php not found.");
            return;
        }

        $content = File::get($path);

        $routeBlock = <<<PHP

Route::middleware(['auth'])->group(function () {
    Route::get('{$plural}', [App\\Http\\Controllers\\{$model}s\\{$model}Controller::class, 'index'])->name('{$plural}.index');
    Route::post('{$plural}', [App\\Http\\Controllers\\{$model}s\\{$model}Controller::class, 'store'])->name('{$plural}.store');
    Route::get('{$plural}/{{item}}/edit', [App\\Http\\Controllers\\{$model}s\\{$model}Controller::class, 'edit'])->name('{$plural}.edit');
    Route::patch('{$plural}/{{item}}', [App\\Http\\Controllers\\{$model}s\\{$model}Controller::class, 'update'])->name('{$plural}.update');
    Route::delete('{$plural}/{{item}}', [App\\Http\\Controllers\\{$model}s\\{$model}Controller::class, 'destroy'])->name('{$plural}.destroy');
});

PHP;

        if (Str::contains($content, "'{$plural}.index'")) {
            $this->warn("Routes for {$plural} already exist in routes/web.php.");
            return;
        }

        File::append($path, $routeBlock);
        $this->info("Updated: routes/web.php");
    }

    protected function createTypeScriptTypes(string $model, string $plural): void
    {
        $dir = resource_path("js/types");
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/{$plural}.ts";
        if (File::exists($path)) {
            $this->warn("TypeScript types already exist: {$path}");
            return;
        }

        $content = <<<TS
export type {$model} = {
    id: number;
    name: string;
    description: string | null;
    createdAt: string;
    updatedAt: string;
};

TS;

        File::put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function createWayfinderRoutes(string $plural, string $model): void
    {
        $dir = resource_path("js/routes/{$plural}");
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/index.ts";
        if (File::exists($path)) {
            $this->warn("Wayfinder routes already exist: {$path}");
            return;
        }

        $content = <<<TS
import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/{$plural}',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::index
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:25
 * @route '/{$plural}'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::store
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:37
 * @route '/{$plural}'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/{$plural}',
} satisfies RouteDefinition<["post"]>

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::store
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:37
 * @route '/{$plural}'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::store
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:37
 * @route '/{$plural}'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::store
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:37
 * @route '/{$plural}'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::store
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:37
 * @route '/{$plural}'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
export const edit = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/{$plural}/{item}',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
edit.url = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { item: args }
    }

    if (Array.isArray(args)) {
        args = {
                    item: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                item: typeof args.item === 'object'
                ? args.item.id
                : args.item,
            }

    return edit.definition.url
            .replace('{item}', parsedArgs.item.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
edit.get = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})
/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
edit.head = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

    /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
    const editForm = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: edit.url(args, options),
        method: 'get',
    })

            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
        editForm.get = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: edit.url(args, options),
            method: 'get',
        })
            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::edit
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:49
 * @route '/{$plural}/{item}'
 */
        editForm.head = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: edit.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    edit.form = editForm
/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::update
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:91
 * @route '/{$plural}/{item}'
 */
export const update = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/{$plural}/{item}',
} satisfies RouteDefinition<["patch"]>

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::update
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:91
 * @route '/{$plural}/{item}'
 */
update.url = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { item: args }
    }

    if (Array.isArray(args)) {
        args = {
                    item: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                item: typeof args.item === 'object'
                ? args.item.id
                : args.item,
            }

    return update.definition.url
            .replace('{item}', parsedArgs.item.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::update
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:91
 * @route '/{$plural}/{item}'
 */
update.patch = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::update
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:91
 * @route '/{$plural}/{item}'
 */
    const updateForm = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::update
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:91
 * @route '/{$plural}/{item}'
 */
        updateForm.patch = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::destroy
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:149
 * @route '/{$plural}/{item}'
 */
export const destroy = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/{$plural}/{item}',
} satisfies RouteDefinition<["delete"]>

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::destroy
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:149
 * @route '/{$plural}/{item}'
 */
destroy.url = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { item: args }
    }

    if (Array.isArray(args)) {
        args = {
                    item: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                item: typeof args.item === 'object'
                ? args.item.id
                : args.item,
            }

    return destroy.definition.url
            .replace('{item}', parsedArgs.item.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::destroy
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:149
 * @route '/{$plural}/{item}'
 */
destroy.delete = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::destroy
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:149
 * @route '/{$plural}/{item}'
 */
    const destroyForm = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
 * @see \\App\\Http\\Controllers\\{$model}s\\{$model}Controller::destroy
 * @see app/Http/Controllers/{$model}s/{$model}Controller.php:149
 * @route '/{$plural}/{item}'
 */
        destroyForm.delete = (args: { item: string | number } | [item: string | number] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const {$plural} = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default {$plural}

TS;

        File::put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function createIndexPage(string $model, string $plural): void
    {
        $pluralUpper = Str::of($plural)->ucfirst()->toString();

        $dir = resource_path("js/pages/{$plural}");
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/Index.vue";
        if (File::exists($path)) {
            $this->warn("Page already exists: {$path}");
            return;
        }

        $content = <<<VUE
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { index, store, update, destroy } from '@/routes/{$plural}';
import type { {$model} } from '@/types/{$plural}';

type Props = {
    items: {$model}[];
};

defineProps<Props>();

const open = ref(false);
const editingItem = ref<{$model} | null>(null);
const deleteDialogOpen = ref(false);
const itemToDelete = ref<{$model} | null>(null);

function openCreateSheet() {
    editingItem.value = null;
    open.value = true;
}

function openEditSheet(item: {$model}) {
    editingItem.value = item;
    open.value = true;
}

function confirmDelete(item: {$model}) {
    itemToDelete.value = item;
    deleteDialogOpen.value = true;
}

function deleteItem() {
    if (!itemToDelete.value) return;
    router.delete(destroy(itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            itemToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="{$pluralUpper}" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="{$pluralUpper}"
                description="Manage your {$plural}"
            />
            <Sheet :open="open" @update:open="(v) => open = v">
                <SheetTrigger as-child>
                    <Button @click="openCreateSheet">
                        <Plus class="h-4 w-4" />
                        New {$model}
                    </Button>
                </SheetTrigger>
                <SheetContent>
                    <SheetHeader>
                        <SheetTitle>{{ editingItem ? 'Edit' : 'Create' }} {$model}</SheetTitle>
                        <SheetDescription>
                            {{ editingItem ? 'Update' : 'Create a new' }} {$model}.
                        </SheetDescription>
                    </SheetHeader>
                    <Form
                        :key="editingItem?.id ?? 'create'"
                        v-bind="editingItem ? update.form(editingItem.id) : store.form()"
                        class="space-y-6 px-4"
                        v-slot="{ errors, processing }"
                        @success="open = false; editingItem = null;"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="editingItem?.name"
                                placeholder="Name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                name="description"
                                :default-value="editingItem?.description ?? ''"
                                placeholder="Description"
                                class="border-input file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex min-h-[60px] w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <SheetFooter>
                            <SheetClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </SheetClose>
                            <Button type="submit" :disabled="processing">
                                {{ editingItem ? 'Update' : 'Create' }}
                            </Button>
                        </SheetFooter>
                    </Form>
                </SheetContent>
            </Sheet>

            <Dialog :open="deleteDialogOpen" @update:open="(v) => deleteDialogOpen = v">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete {$model}</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "{{ itemToDelete?.name }}"? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button variant="destructive" @click="deleteItem">
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in items"
                :key="item.id"
                class="flex flex-col justify-between rounded-lg border p-4"
            >
                <div class="space-y-2">
                    <div class="font-medium">{{ item.name }}</div>
                    <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="openEditSheet(item)"
                    >
                        <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="confirmDelete(item)"
                    >
                        <Trash class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

VUE;

        File::put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function updateTypeScriptIndex(string $plural): void
    {
        $path = resource_path("js/types/index.ts");
        if (!File::exists($path)) {
            return;
        }

        $content = File::get($path);
        $line = "export * from './{$plural}';";

        if (Str::contains($content, $line)) {
            return;
        }

        File::append($path, PHP_EOL . $line);
        $this->info("Updated: {$path}");
    }

    protected function updateTypeScriptRoutesIndex(string $plural): void
    {
        $path = resource_path("js/routes/index.ts");
        if (!File::exists($path)) {
            return;
        }

        $content = File::get($path);
        $line = "import {$plural} from './{$plural}';";

        if (Str::contains($content, $line)) {
            return;
        }

        File::append($path, PHP_EOL . $line);
        $this->info("Updated: {$path}");
    }
}
