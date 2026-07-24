# Skill: CRUD estilo `users` para `app_salud_laravel_13`

> Esta skill replica el patrón del módulo `users` para crear nuevos módulos CRUD
> completos (backend + Inertia/Vue + Wayfinder + Pest) en este proyecto.
>
> **Stack objetivo**: PHP 8.3 + Laravel 13 + Inertia v3 + Vue 3 + Wayfinder v0 + Pest v4 + Tailwind v4 + shadcn-vue.

---

## 0. Convenciones globales

- **Stack**: PHP 8.3, Laravel 13, Inertia v3, Vue 3, Wayfinder v0, Pest v4, Tailwind v4, shadcn-vue.
- **Idioma del usuario**: español (Venezuela). Etiquetas de UI, mensajes `__('...')`, descripciones y traducciones **en español**. Nombres de clases, métodos, columnas y rutas **en inglés**.
- **Estilo PHP**: llaves en todas las estructuras, property promotion cuando aplique, return types explícitos, PHPDoc para lógica compleja, `array_shape` cuando aplique.
- **Frontend**: SFC de raíz única, `<script setup lang="ts">`, Inertia `Head` + `Form` + `router`, iconos `@lucide/vue`, componentes UI en `@/components/ui/*`.
- **No crear**: scripts de verificación, archivos de documentación adicional, ni dependencias nuevas sin aprobación.
- **Tras modificar PHP**: ejecutar `vendor/bin/pint --dirty --format agent` antes de cerrar el cambio.
- **Tras modificar Vue/TS**: ejecutar `npm run build` (o pedirle al usuario que lo ejecute).
- **Skills que se deben activar** al trabajar en este dominio: `inertia-vue-development`, `tailwindcss-development`.

---

## 1. Estructura de carpetas (no crear carpetas nuevas, usar las existentes)

```
app/Models/{Model}.php
app/Http/Controllers/{Model}s/{Model}Controller.php
app/Http/Requests/{Model}s/Store{Model}Request.php
app/Http/Requests/{Model}s/Update{Model}Request.php
database/migrations/YYYY_MM_DD_HHMMSS_create_{plural_snake}_table.php
database/factories/{Model}Factory.php
database/seeders/DatabaseSeeder.php                 (referenciar aquí)
resources/js/pages/{plural}/Index.vue
resources/js/types/{plural}.ts
resources/js/routes/{plural}/index.ts               (auto-generado por Wayfinder)
resources/js/types/index.ts                         (exportar el nuevo tipo)
resources/js/components/AppSidebar.vue              (agregar entrada)
routes/web.php                                      (registrar grupo de rutas)
tests/Feature/{Model}s/IndexTest.php
```

> **Importante**: la carpeta singular se llama **`{Model}s`** (con `s` final) salvo que `{Model}` ya termine en `s` (p. ej. `Patients` → carpeta `Patients/`, **no** `Patientss/`). El comando `make:crud` actual tiene un bug conocido para este caso; corregir manualmente si aplica.

---

## 2. Modelo (`app/Models/{Model}.php`)

- Usar atributo **`#[Fillable([...])]`** (no `protected $fillable`) — patrón Laravel 13 vigente en el proyecto.
- Si la tabla tiene `name` y `description`, replicar exactamente:

  ```php
  #[Fillable(['name', 'description'])]
  class {Model} extends Model
  {
      use HasFactory;   // sólo si existe Factory asociada

      protected function casts(): array
      {
          return [];
      }
  }
  ```

- Si el modelo requiere factory, anotar PHPDoc: `@use HasFactory<{Model}Factory>`.
- Si tiene relaciones (`BelongsTo`, `HasMany`, etc.), declararlas con tipos de retorno explícitos y PHPDoc de la firma.
- **Relaciones con `User`**: si aplica, usar `belongsTo(User::class)` con FK explícita.

---

## 3. Migración

- Nombre: `YYYY_MM_DD_HHMMSS_create_{plural_snake}_table.php` (timestamp `date('Y_m_d_His')`).
- Columnas mínimas esperadas para módulos simples: `id`, `name`, `description` (nullable), `timestamps`.
- Para módulos con FK: usar `foreignId('user_id')->nullable()->constrained()->nullOnDelete()` cuando aplique.
- **Casts**: nunca aplicar `casts` en migración; los casts van en el modelo.
- Estructura anónima: `return new class extends Migration { public function up(): void { ... } public function down(): void { ... } };`
- Antes de crear, verificar con `Schema::hasTable('{plural}')` para evitar duplicados.

---

## 4. Factory (`database/factories/{Model}Factory.php`)

- Usar sintaxis moderna: `use Illuminate\Database\Eloquent\Factories\Factory; use App\Models\{Model};` con `/** @use HasFactory<{Model}Factory> */` en el modelo.
- Definir `$model = {Model}::class;`.
- Incluir estados (`state()`) útiles al dominio, por ejemplo `MedicalEspecialtiesFactory::seed()` para datos precargados, o `seedMedical{Model}s()` en `DatabaseSeeder` para ser idempotente (`firstOrCreate` por `name`).

---

## 5. Form Requests

### `Store{Model}Request`

- `authorize(): bool { return true; }`.
- `rules(): array` con todas las validaciones en arrays.
- Para campos únicos, usar `Rule::unique('{plural}', 'column')` o `Rule::unique('{plural}')->ignore($id)` en update.
- Para enums (estilo `UserRole`), usar `Rule::enum({Model}Status::class)` o `Rule::in([...])`.

### `Update{Model}Request`

- Misma estructura que `Store{Model}Request`, con `ignore($id)` para unique.
- **Convención**: usar `'sometimes', 'required'` para campos opcionales, y `Rule::unique(...)->ignore($this->route('{param}'))`.
- `password` siempre `'nullable'` en update (si existe).

---

## 6. Controller (`app/Http/Controllers/{Model}s/{Model}Controller.php`)

- **Namespace**: `App\Http\Controllers\{Model}s`.
- Hereda de `App\Http\Controllers\Controller`.
- **Index** con filtros y paginación, replicando el patrón de `UserController`:
  - Capturar `search` (string), filtros adicionales (`role`, `status`, etc.) y `per_page` (default 10, validar contra `[10, 50, 100]`).
  - Aplicar `when()` de Eloquent para cada filtro.
  - `->latest()->paginate($perPage)->withQueryString()->through(fn ({Model} $item) => [...])` mapeando a la forma de la API.
  - Formato de fechas: `->toISOString()`.
  - Devolver `Inertia::render('{plural}/Index', [..., 'filters' => [...]])` con todos los datos que la vista necesita.
- **Store**: `DB::transaction(...)` cuando haya múltiples escrituras; usar `Inertia::flash('toast', ['type' => 'success', 'message' => __('{Model} created.')])`; redirección a `to_route('{plural}.index')` o `edit` según el caso.
- **Edit**: renderizar `{plural}/Index` con `item` (no `Edit.vue` separado). Pasar también catálogos (`availableRoles`, `availableStatuses`, etc.) si aplica.
- **Update**: usar `DB::transaction` cuando hay relaciones; resetear campos nulos/vacíos; mismo flash de toast.
- **Destroy**: `Inertia::flash('toast', ['type' => 'success', 'message' => __('{Model} deleted.')])`; redirect a `index`.
- **Acciones adicionales** (ej. `resetPassword` en `UserController`): método público `{action}({Model} $item): RedirectResponse`, registrar ruta en `routes/web.php` con `Route::patch` o `Route::post` según semántica, y agregar entrada en `DropdownMenu` del frontend.
- **Extras en `index`**: si la entidad depende de catálogos (roles, estados, tipos), exponerlos como método privado `availableXxx(): array` y pasarlos en la respuesta Inertia.

---

## 7. Rutas (`routes/web.php`)

- Insertar **dentro** de un grupo `Route::middleware(['auth'])->group(...)` siguiendo el patrón de los demás módulos.
- Usar **FQCN** o import ya declarado al inicio del archivo:

  ```php
  Route::middleware(['auth'])->group(function () {
      Route::get('{plural}', [{Model}Controller::class, 'index'])->name('{plural}.index');
      Route::post('{plural}', [{Model}Controller::class, 'store'])->name('{plural}.store');
      Route::get('{plural}/{{param}}/edit', [{Model}Controller::class, 'edit'])->name('{plural}.edit');
      Route::patch('{plural}/{{param}}', [{Model}Controller::class, 'update'])->name('{plural}.update');
      Route::delete('{plural}/{{param}}', [{Model}Controller::class, 'destroy'])->name('{plural}.destroy');
  });
  ```

- `{param}` debe llamarse **`{item}`** salvo que el modelo sea `User` (donde es `{user}`) o el nombre convencional de la convención del proyecto (`{patient}`, `{doctor}`, etc.).
- **No duplicar**: antes de insertar, verificar con `Str::contains($content, "'{plural}.index'")`.

---

## 8. TypeScript Types (`resources/js/types/{plural}.ts`)

- **NO** exportar tipos genéricos `Item` o `Model`. Nombrar como **`{Model}`** (sin sufijo) salvo que sea `User` (usar `UserModel` para evitar colisión con globales de Inertia) o colisión conocida.
- Estructura de la entidad:

  ```ts
  export type {Model} = {
      id: number;
      name: string;
      description: string | null;
      createdAt: string;
      updatedAt: string;
      // + campos específicos (role, status, etc.)
  };
  ```

- Si hay catálogos (ej. `RoleOption` en `users.ts`), exportarlos en el mismo archivo.
- Registrar export en `resources/js/types/index.ts`: `export * from './{plural}';`.

---

## 9. Wayfinder (`resources/js/routes/{plural}/index.ts`)

- **Auto-generado** por el comando `php artisan wayfinder:generate --with-form` — no escribir a mano.
- Importar en la vista con: `import { index, store, update, destroy, ...extras } from '@/routes/{plural}';`
- Para acciones extra (ej. `resetPassword`), exponer la función generada por Wayfinder en la importación.

---

## 10. Vista Vue (`resources/js/pages/{plural}/Index.vue`)

> Esta vista es la más detallada. Replicar **literalmente** el patrón de `users/Index.vue`.

### 10.1 Script setup

- Importar de Inertia: `Form, Head, router, usePage` (este último sólo si se usan flashes dinámicos como `temporary_password`).
- Importar iconos de `@lucide/vue` que apliquen: `Plus, Pencil, Trash, MoreVertical, Search`, y los específicos del módulo (`Shield, Key, CircleCheck`, etc.).
- Importar componentes UI: `Heading`, `InputError`, `Alert/AlertDescription/AlertTitle`, `Button`, `Dialog/*`, `Input`, `Label`, `Select/*`, `DropdownMenu/*`, `Sheet/*`.
- **Refs y state**:
  - `open` (Sheet), `editingItem`, `deleteDialogOpen`, `itemToDelete`.
  - Refs para acciones extra: `resetDialogOpen`, `itemToReset`, `assignRoleOpen`, `roleItem`, `selectedRole`, `roleError`.
  - `search` (con debounce de 300 ms), `roleFilter` (u otros filtros), `perPage` (default `'10'`).
- **`applyFilters()`**: armar query `{ page: 1, search?, role?, per_page? }` y llamar `router.get(index().url, query, { preserveState: true, replace: true, preserveScroll: true })`.
- **Watchers**:
  - `watch(search, ...)` con `setTimeout(..., 300)`.
  - `watch(roleFilter, () => applyFilters())`.
  - `watch(perPage, () => applyFilters())`.
  - `watch(() => page.props.errors, ...)` para errores dinámicos (asignar a `roleError`, etc.).
- **Funciones**: `openEditSheet`, `confirmDelete`, `confirmResetPassword` (si aplica), `openAssignRole` (si aplica), `deleteItem`, `resetPassword` (si aplica), `assignRole` (si aplica).
- **Helpers de catálogo** (ej. `roleLabel`, `roleClasses`, `roleIcon`): usar `availableRoles.value.find(...)` y devolver `parts.join(' ')` con `color_class`, `text_class`, `border-transparent`.

### 10.2 Plantilla

- `<Head title="{Plural}" />` raíz.
- Layout principal: `class="flex h-full flex-col space-y-6"`.
- Encabezado: `Heading variant="small" title="{Plural}" description="..."` + búsqueda + Select de filtro + `Sheet v-model:open="open"` con `SheetTrigger` (`as-child`) y `Button` con icono `Plus` "New {Model}".
- **`SheetContent`**: contiene `SheetHeader`, `Form v-bind="editingItem ? update.form(editingItem.id) : store.form()"`, `@success="open = false; editingItem = null;"` y dentro los campos (`Input`, `Select` con `:default-value`, `InputError`). Cerrar con `SheetFooter` que tenga `SheetClose as-child` y `Button type="submit"`.
- **Diálogos de acción** (delete, reset, assign role): patrón `Dialog :open="..." @update:open="..."` con `DialogContent > DialogHeader/DialogFooter`. Cada acción destructiva usa `Button variant="destructive"`.
- **Alertas dinámicas** (ej. `temporaryPassword` en users): usar `Alert variant="default"` con clases condicionales y watcher sobre `page.props.flash`.
- **Tabla**:
  - Contenedor: `class="min-h-0 flex-1 overflow-auto rounded-md border"`.
  - `<table class="w-full text-left text-sm">` con `<thead class="bg-muted/50">` y `<tbody>`.
  - Cada fila: `v-for="item in items.data" :key="item.id" class="border-t"`.
  - **Acciones por fila**: usar **`DropdownMenu`** con `MoreVertical` (NO botones sueltos). Opciones del menú: Edit, Assign role (si aplica), Reset password (si aplica), Delete.
  - Estado vacío: `<tr v-if="!items.data.length">` con colspan y mensaje "No {plural} found."
- **Paginación**:
  - Sticky footer: `class="sticky bottom-0 z-10 -mx-1 px-3 flex flex-col gap-3 border-t bg-background px-1 py-3 sm:flex-row sm:items-center sm:justify-between"`.
  - Mostrar `items.from` a `items.to` de `items.total`.
  - Select de per-page (10/50/100) + botones Previous/Next usando `router.get(index().url, { ...filters, page: ... }, { preserveState: true, preserveScroll: true })`.

### 10.3 Props tipados

```ts
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
    item?: {Model};
    availableRoles?: RoleOption[];        // o catálogos equivalentes
    filters?: {
        search?: string;
        role?: string;
        per_page?: number;
    };
};
```

---

## 11. Sidebar (`resources/js/components/AppSidebar.vue`)

- Agregar **debajo del Dashboard** en `mainNavItems`:

  ```ts
  {
      title: '{Plural}',
      href: {plural}Index().url,
      icon: {Icon},
  }
  ```

- Importar la ruta: `import { index as {plural}Index } from '@/routes/{plural}';`.
- **Icono** sugerido por defecto: `FileText` para módulos genéricos, `Users` para módulos de usuarios, `Stethoscope` para doctores/especialidades, `CalendarDays` para citas, `ReceiptText` para facturas, `Tag` para categorías. Verificar que el icono esté disponible en `@lucide/vue` y agregarlo al `import { ... } from '@lucide/vue';` existente.
- Si la entrada debe ser condicional por permisos (estilo `canPatients`), envolver con `v-if="userCan('{plural}.view')"` o equivalente del proyecto.

---

## 12. Wayfinder & Build

- Tras crear el módulo, ejecutar:

  ```bash
  php artisan wayfinder:generate --with-form
  ```

- Si se cambian tipos, ejecutar `npm run build` (o avisar al usuario que lo ejecute).
- Verificar que el icono nuevo exista en `@lucide/vue` antes de declararlo.

---

## 13. Tests (Pest v4)

- Crear `tests/Feature/{Model}s/IndexTest.php` con `php artisan make:test --pest {Model}s/IndexTest`.
- Cubrir como mínimo:
  - `it renders the {plural} index page` (`get()->assertOk()->assertInertia(fn (Assert $page) => $page->component('{plural}/Index'))`).
  - `it requires authentication` (`get()->assertRedirect(route('login'))` o equivalente del proyecto).
  - `it paginates results` (crear 20 registros y verificar `items.total`).
  - `it filters by search` (si el módulo tiene búsqueda).
  - `it filters by {role|status|category}` (si aplica).
- **No eliminar tests** sin aprobación.
- Ejecutar el filtro: `php artisan test --compact --filter={Model}IndexTest`.

---

## 14. DatabaseSeeder

- Si el módulo requiere datos iniciales, agregar método `seed{Model}s(): void` con `firstOrCreate` por `name` (idempotente), invocado desde `DatabaseSeeder::run()`.

---

## 15. Orden de ejecución sugerido

1. Generar archivos con `php artisan make:crud {Model} --migrate --test --no-interaction`.
2. Refactorizar Controller/Requests para incluir filtros y acciones extra.
3. Refactorizar `Index.vue` con el patrón completo de `users/Index.vue`.
4. Agregar entrada en `AppSidebar.vue`.
5. Exportar tipo en `resources/js/types/index.ts`.
6. `php artisan wayfinder:generate --with-form`.
7. `vendor/bin/pint --dirty --format agent`.
8. `php artisan test --compact --filter={Model}IndexTest`.
9. `npm run build`.

---

## 16. Checklist final

- [ ] Modelo con `#[Fillable([...])]` y casts declarados.
- [ ] Migración con timestamp `date('Y_m_d_His')` y FKs correctas.
- [ ] Factory con al menos un estado útil al dominio.
- [ ] `Store` y `Update` Requests con `Rule::unique(...)->ignore()` en update.
- [ ] Controller con `index` (filtros + paginación), `store`/`update` en transacción, flash de toast, `edit` reusando `Index.vue`.
- [ ] Rutas dentro de `auth` middleware, sin duplicar.
- [ ] Type sin sufijo `Model` (excepto colisiones) y exportado en `types/index.ts`.
- [ ] Vista con `Sheet` + `Form`, `DropdownMenu` por fila, paginación sticky, búsqueda con debounce.
- [ ] Sidebar con icono de `@lucide/vue` e import correspondiente.
- [ ] Test Pest mínimo (render + auth + paginación).
- [ ] `php artisan wayfinder:generate --with-form` ejecutado.
- [ ] `vendor/bin/pint --dirty --format agent` ejecutado.
- [ ] `php artisan test --compact` pasa.
- [ ] `npm run build` sin errores.
