<?php

namespace App\Console\Commands;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TestModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Accepts:
     *  --module=*  : Filter by specific module(s) (kebab-case, repeatable)
     *  --skip-execute : Only audit/generate, do not run the test suite
     *  --force     : Overwrite existing test files
     *  --stop-on-fail : Abort as soon as a module fails
     */
    protected $signature = 'test:modules
        {--module=* : Filtrar por módulo(s) específico(s) en kebab-case (repetible)}
        {--skip-execute : Solo auditar/generar, no ejecutar la suite de pruebas}
        {--force : Sobrescribir archivos de test existentes (no recomendado)}
        {--stop-on-fail : Detener la ejecución al primer módulo con fallos}';

    protected $description = 'Audita, genera y ejecuta los tests críticos de cada módulo registrado en la tabla `modules`.';

    /**
     * Critical test files every module must have. Each entry maps to a stub
     * stored in stubs/tests/{Name}.stub under the project root.
     *
     * @var list<array{name: string, label: string, stub: string}>
     */
    protected array $criticalTests = [
        ['name' => 'IndexTest',    'label' => 'Index',    'stub' => 'IndexTest.stub'],
        ['name' => 'StoreTest',    'label' => 'Store',    'stub' => 'StoreTest.stub'],
        ['name' => 'UpdateTest',   'label' => 'Update',   'stub' => 'UpdateTest.stub'],
        ['name' => 'DestroyTest',  'label' => 'Destroy',  'stub' => 'DestroyTest.stub'],
    ];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $report = [];

    public function handle(): int
    {
        $this->newLine();
        $this->info('🧪 Auditoría y ejecución de tests por módulo');
        $this->newLine();

        if (! Schema::hasTable('modules')) {
            $this->error('La tabla `modules` no existe. Ejecuta `php artisan migrate --seed` primero.');

            return self::FAILURE;
        }

        $modules = $this->resolveModules();

        if ($modules->isEmpty()) {
            $this->warn('No se encontraron módulos para procesar.');

            return self::SUCCESS;
        }

        $this->line(sprintf('Se procesarán <info>%d</info> módulo(s).', $modules->count()));
        $this->newLine();

        $anyFailed = false;
        $stopOnFail = (bool) $this->option('stop-on-fail');

        foreach ($modules as $module) {
            $moduleResult = $this->processModule($module);

            if ($moduleResult['status'] === 'FAIL') {
                $anyFailed = true;

                if ($stopOnFail) {
                    $this->error('Abortando por --stop-on-fail.');

                    break;
                }
            }
        }

        $this->renderFinalReport($anyFailed);

        return $anyFailed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resolve the list of modules to process honoring the --module filter.
     *
     * @return Collection<int, Module>
     */
    protected function resolveModules()
    {
        $query = Module::query()->orderBy('name');

        $filter = $this->option('module');

        if (is_array($filter) && $filter !== []) {
            $filter = array_filter($filter, fn ($m) => is_string($m) && $m !== '');

            if ($filter !== []) {
                $query->whereIn('name', $filter);
            }
        }

        return $query->get();
    }

    /**
     * Audit, generate and (optionally) run the test suite for a single module.
     *
     * @return array<string, mixed>
     */
    protected function processModule(Module $module): array
    {
        $name = $module->name;
        $display = $module->display_name ?? Str::headline($name);

        $this->line(str_repeat('─', 64));
        $this->line("📦 Módulo: <comment>{$name}</comment> ({$display})");

        $mapping = $this->buildNamingMap($name);

        if (! File::exists($mapping['controller'])) {
            $this->warn("   ⏭  SKIPPED — Controller no encontrado en {$mapping['controller']}");

            return [
                'module' => $name,
                'status' => 'SKIPPED',
                'created' => [],
                'existing' => [],
                'errors' => [],
                'tests_output' => null,
            ];
        }

        File::ensureDirectoryExists($mapping['tests_dir']);

        $created = [];
        $existing = [];
        $missingCritical = [];

        foreach ($this->criticalTests as $test) {
            $path = $mapping['tests_dir'].DIRECTORY_SEPARATOR.$test['name'].'.php';

            if (File::exists($path)) {
                if ($this->option('force')) {
                    $this->generateTestFile($path, $mapping, $test);
                    $created[] = $test['name'];
                    $this->line("   ♻️  [SOBRESCRITO] {$test['name']}.php");
                } else {
                    $existing[] = $test['name'];
                    $this->line("   ✔  [EXISTE] {$test['name']}.php");
                }

                continue;
            }

            $this->generateTestFile($path, $mapping, $test);
            $created[] = $test['name'];
            $this->line("   ✨ [CREADO] {$test['name']}.php");
        }

        if ($created !== [] || $existing !== []) {
            // After audit, all 4 critical files should exist on disk.
            foreach ($this->criticalTests as $test) {
                $path = $mapping['tests_dir'].DIRECTORY_SEPARATOR.$test['name'].'.php';
                if (! File::exists($path)) {
                    $missingCritical[] = $test['name'];
                }
            }
        }

        $status = 'PASS';
        $errors = [];
        $testsOutput = null;

        if ($this->option('skip-execute')) {
            $this->line('   ⏭  Ejecución omitida (--skip-execute).');
        } else {
            $execution = $this->runModuleTests($mapping);

            $status = $execution['status'];
            $errors = $execution['errors'];
            $testsOutput = $execution['output'];

            $icon = $status === 'PASS' ? '✔' : '✖';
            $color = $status === 'PASS' ? 'info' : 'error';
            $this->{$color}("   {$icon} Tests: {$status}");

            if ($errors !== []) {
                foreach (array_slice($errors, 0, 5) as $err) {
                    $this->line("      · {$err}");
                }
                if (count($errors) > 5) {
                    $this->line('      · ... ('.(count($errors) - 5).' errores más)');
                }
            }
        }

        $result = [
            'module' => $name,
            'display' => $display,
            'status' => $status,
            'created' => $created,
            'existing' => $existing,
            'missing_after_audit' => $missingCritical,
            'errors' => $errors,
            'tests_output' => $testsOutput,
        ];

        $this->report[] = $result;

        return $result;
    }

    /**
     * Build the naming map that translates a kebab-case module name into
     * the file paths, class names and table names used by the CRUD
     * scaffolding convention.
     *
     * @return array<string, string>
     */
    protected function buildNamingMap(string $kebab): array
    {
        $kebabPlural = (string) Str::of($kebab)->lower();
        $singular = (string) Str::of($kebabPlural)->singular()->studly();
        $plural = (string) Str::of($kebabPlural)->studly();
        $table = (string) Str::of($kebabPlural)->snake();

        $testsDir = base_path('tests'.DIRECTORY_SEPARATOR.'Feature'.DIRECTORY_SEPARATOR.$singular);
        $controller = app_path('Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.$plural.DIRECTORY_SEPARATOR.$plural.'Controller.php');

        return [
            'kebab' => $kebabPlural,
            'singular' => $singular,
            'plural' => $plural,
            'table' => $table,
            'tests_dir' => $testsDir,
            'controller' => $controller,
        ];
    }

    /**
     * Render a stub into a concrete Pest test file under the module folder.
     *
     * @param  array<string, string>  $mapping
     * @param  array{name: string, label: string, stub: string}  $test
     */
    protected function generateTestFile(string $path, array $mapping, array $test): void
    {
        $stubPath = base_path('stubs'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.$test['stub']);

        if (! File::exists($stubPath)) {
            $this->error("   ✖ Stub no encontrado: {$stubPath}");

            return;
        }

        $content = File::get($stubPath);

        $replacements = [
            '{{Singular}}' => $mapping['singular'],
            '{{Plural}}' => $mapping['plural'],
            '{{Kebab}}' => $mapping['kebab'],
            '{{Table}}' => $mapping['table'],
            '{{DisplayName}}' => (string) Str::headline($mapping['singular']),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        File::put($path, $content);
    }

    /**
     * Run the Pest test suite scoped to the module folder using Process.
     *
     * @param  array<string, string>  $mapping
     * @return array{status: string, errors: list<string>, output: string|null}
     */
    protected function runModuleTests(array $mapping): array
    {
        $relativePath = 'tests'.DIRECTORY_SEPARATOR.'Feature'.DIRECTORY_SEPARATOR.$mapping['singular'];
        $command = 'php artisan test --compact "'.$relativePath.'"';

        try {
            $result = Process::path(base_path())
                ->timeout(180)
                ->run($command);
        } catch (\Throwable $e) {
            Log::error("test:modules fallo crítico en {$mapping['kebab']}: ".$e->getMessage());

            return [
                'status' => 'FAIL',
                'errors' => ['Excepción al ejecutar Process: '.$e->getMessage()],
                'output' => null,
            ];
        }

        $output = $result->output();
        $exitCode = $result->exitCode();

        if ($result->successful()) {
            return [
                'status' => 'PASS',
                'errors' => [],
                'output' => $output,
            ];
        }

        $errors = $this->extractFailureMessages($output);

        Log::warning("test:modules: módulo {$mapping['kebab']} falló", [
            'exit_code' => $exitCode,
            'errors' => $errors,
        ]);

        return [
            'status' => 'FAIL',
            'errors' => $errors,
            'output' => $output,
        ];
    }

    /**
     * Heuristically extract the failing test messages from a Pest/PHPUnit
     * output buffer. Falls back to the raw tail of the output when no
     * recognized pattern is present.
     *
     * @return list<string>
     */
    protected function extractFailureMessages(string $output): array
    {
        $messages = [];

        // Pest/PHPUnit "FAILED" lines such as "  ⨯ it loads the ... page"
        if (preg_match_all('/[✘⨯✗]\s+(.+)$/m', $output, $matches)) {
            foreach ($matches[1] as $line) {
                $messages[] = trim($line);
            }
        }

        // PHPUnit "Tests: X, Assertions: Y, Failures: Z." summary line.
        if (preg_match_all('/^\s*(?:There (?:was|were) \d+ failure.*|FAILED|PHPUnit\\\\.*Failed asserting.*)$/m', $output, $matches)) {
            foreach ($matches[0] as $line) {
                $messages[] = trim($line);
            }
        }

        if ($messages === []) {
            // Last-resort: take the last 10 non-empty lines so the user
            // still gets something meaningful in the report.
            $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));
            $messages = array_slice($lines, -10);
        }

        return $messages;
    }

    /**
     * Render the final aggregated report.
     */
    protected function renderFinalReport(bool $anyFailed): void
    {
        $this->newLine();
        $this->line(str_repeat('═', 64));
        $this->info('📊 Reporte final de tests por módulo');
        $this->line(str_repeat('═', 64));

        $rows = [];
        $totals = ['pass' => 0, 'fail' => 0, 'skipped' => 0, 'created' => 0, 'existing' => 0];

        foreach ($this->report as $row) {
            $rows[] = [
                'module' => $row['module'],
                'status' => $row['status'],
                'created' => count($row['created']),
                'existing' => count($row['existing']),
            ];

            match ($row['status']) {
                'PASS' => $totals['pass']++,
                'FAIL' => $totals['fail']++,
                default => $totals['skipped']++,
            };

            $totals['created'] += count($row['created']);
            $totals['existing'] += count($row['existing']);
        }

        $this->table(['Módulo', 'Estado', 'Creados', 'Existentes'], $rows);

        $this->newLine();
        $this->line("✔ PASS   : <info>{$totals['pass']}</info>");
        $this->line("✖ FAIL   : <error>{$totals['fail']}</error>");
        $this->line("⏭ SKIPPED: <comment>{$totals['skipped']}</comment>");
        $this->line("📄 Tests críticos creados : <info>{$totals['created']}</info>");
        $this->line("📁 Tests ya existentes    : <info>{$totals['existing']}</info>");

        $this->newLine();
        if ($anyFailed) {
            $this->error('❌ Uno o más módulos presentaron fallos en sus tests.');
        } else {
            $this->info('✅ Todos los módulos procesados pasaron la suite crítica.');
        }
    }
}
