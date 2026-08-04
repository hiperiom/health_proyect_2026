# Skill: Nuevo Módulo CRUD

## Cuándo activar esta skill

Esta skill se activa cuando:
- El usuario menciona "crear módulo", "nuevo módulo", "generar CRUD"
- El usuario ejecuta `php artisan make:crud`
- Palabras clave: "módulo", "CRUD", "scaffolding"

## Uso del comando

### Sintaxis básica

```bash
php artisan make:crud \
  --modelTitle="Especialidades Médicas" \
  --modelTitleSingular="Especialidad Médica" \
  --modelNameSingular="MedicalSpecialty" \
  --modelNamePlural="MedicalSpecialties" \
  --modelNameKebabCase="medical-specialties" \
  --modelNameRoutes="medical-specialties" \
  --modelNameController="MedicalSpecialtyController" \
  --modelNameTable="medical_specialties"
```

### Parámetros obligatorios

| Parámetro | Descripción | Ejemplo |
|-----------|-------------|---------|
| `--modelTitle` | Título en español y plural | `"Especialidades Médicas"` |
| `--modelTitleSingular` | Título en español y singular | `"Especialidad Médica"` |
| `--modelNameSingular` | Modelo en PascalCase singular | `MedicalSpecialty` |
| `--modelNamePlural` | Modelo en PascalCase plural | `MedicalSpecialties` |
| `--modelNameKebabCase` | Nombre en kebab-case | `medical-specialties` |
| `--modelNameRoutes` | Prefijo de rutas | `medical-specialties` |
| `--modelNameController` | Nombre del controlador | `MedicalSpecialtyController` |
| `--modelNameTable` | Nombre de tabla en snake_case | `medical_specialties` |

### Parámetros opcionales

| Parámetro | Descripción | Valor por defecto |
|-----------|-------------|-------------------|
| `--timestamp` | Fecha en formato `Y_m_d_His` | Auto-generado |
| `--migrate` | Ejecutar migración | `false` |
| `--fresh` | Ejecutar `migrate:fresh --seed` | `true` |
| `--rundev` | Ejecutar `composer run dev` | `true` |
| `--skip-tests` | Omitir tests | `false` |

## Ejemplo de uso

### Crear módulo de "Especialidades Médicas"

```bash
php artisan make:crud \
  --modelTitle="Especialidades Médicas" \
  --modelTitleSingular="Especialidad Médica" \
  --modelNameSingular="MedicalSpecialty" \
  --modelNamePlural="MedicalSpecialties" \
  --modelNameKebabCase="medical-specialties" \
  --modelNameRoutes="medical-specialties" \
  --modelNameController="MedicalSpecialtyController" \
  --modelNameTable="medical_specialties"
```

## Arquitectura generada

### Backend

- **Model**: `app/Models/{ModelNameSingular}.php`
- **Controlador**: `app/Http/Controllers/{Plural}/{Plural}Controller.php`
- **Requests**: `app/Http/Requests/{Singular}/Store{Singular}Request.php` y `Update{Singular}Request.php`
- **Resource**: `app/Http/Resources/{Singular}/{Singular}Resource.php`
- **Service**: `app/Services/{Singular}/{Singular}Service.php`
- **Policy**: `app/Policies/{Singular}Policy.php`
- **Migración**: `database/migrations/{timestamp}_create_{table}_table.php`
- **Factory**: `database/factories/{Singular}Factory.php`
- **Seeder**: `database/seeders/{Singular}Seeder.php`

### Frontend

- **Página Vue**: `resources/js/pages/{kebab-case}/Index.vue`
- **Tipos TS**: `resources/js/types/{kebab-case}.ts`
- **Rutas**: `resources/js/routes/{kebab-case}/index.ts`

### Tests

- **Feature tests**: `tests/Feature/{Singular}/` (IndexTest, StoreTest, UpdateTest, DestroyTest)

## Características

### Backend
- ✅ Model Eloquent con `$fillable`
- ✅ Migración con campos: `id`, `name`, `description`, `value`, `timestamps`
- ✅ Factory con Faker
- ✅ Seeder con 50 registros
- ✅ Policy con permisos básicos
- ✅ Form Requests con validación
- ✅ API Resource para JSON
- ✅ Service Layer con métodos CRUD
- ✅ Controlador con inyección de dependencias
- ✅ Rutas con middleware `auth` y `verified`

### Frontend
- ✅ Página Index.vue con listado, búsqueda, paginación
- ✅ Formulario crear/editar en Sheet
- ✅ Tipos TypeScript
- ✅ Rutas Wayfinder type-safe
- ✅ Navegación en sidebar
- ✅ Tour guiado con Driver.js

### Base de datos
- ✅ Registro automático en tabla `modules`
- ✅ Permisos CRUD creados automáticamente
- ✅ Seed data persistente

## Flujo de ejecución

1. Crea rama Git con nombre de la tabla
2. Genera backend completo
3. Genera frontend completo
4. Actualiza archivos de configuración
5. Ejecuta `migrate:fresh --seed`
6. Registra módulo y permisos
7. Regenera Wayfinder
8. Ejecuta tests
9. Inicia `composer run dev`

## Convenciones

### Backend
- Modelo: PascalCase singular
- Controlador: `{Plural}Controller`
- Servicio: `{Singular}Service`
- Tabla: snake_case

### Frontend
- Página: `{kebab-case}/Index.vue`
- Tipos: `{kebab-case}.ts`
- Rutas: `{kebab-case}/index.ts`

## Notas importantes

1. **Idempotencia**: No sobrescribe archivos existentes
2. **Rama Git**: Crea automáticamente una rama por módulo
3. **Tests**: Ejecuta auditoría automática al final
4. **Desarrollo**: Inicia servidor automáticamente