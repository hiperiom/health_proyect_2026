<?php

use App\Http\Controllers\Allergies\AllergiesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthBackgrounds\HealthBackgroundsController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Modules\ModuleController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\RoleSelectionController;
use App\Http\Controllers\SwitchRoleController;
use App\Http\Controllers\Users\UserController;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureRoleSelection;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

// Locale switcher used by the frontend i18n composable. Must be
// outside the `auth` group so the user can change the language on
// the login screen (before being authenticated).
Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified', EnsureRoleSelection::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('role-selection', [RoleSelectionController::class, 'index'])->name('role.selection');
    Route::post('role-selection', [RoleSelectionController::class, 'store'])->name('role.selection.store');
    Route::post('switch-role', SwitchRoleController::class)->name('switch-role');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/check-email', [UserController::class, 'checkEmail'])->name('check-email');
    Route::get('/check-dni', [UserController::class, 'checkDni'])->name('check-dni');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::patch('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    Route::post('/{user}/permissions', [UserController::class, 'assignPermissions'])->name('assignPermissions');
    Route::patch('/{user}/roles', [UserController::class, 'assignRoles'])->name('assignRoles');
    Route::post('/{user}/photo', [UserController::class, 'photoStore'])->name('photo.store');
    Route::delete('/{user}/photo', [UserController::class, 'photoDestroy'])->name('photo.destroy');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{item}', [RoleController::class, 'destroy'])->name('destroy');
    Route::post('/{item}/permissions', [RoleController::class, 'assignPermissions'])->name('assignPermissions');
    Route::patch('/{item}/modules', [RoleController::class, 'assignModules'])->name('assignModules');
    Route::patch('/{item}/modules/permissions', [RoleController::class, 'assignModulePermissions'])->name('assignModulePermissions');
});

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::post('/', [PermissionController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [PermissionController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [PermissionController::class, 'update'])->name('update');
    Route::delete('/{item}', [PermissionController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('modules')->name('modules.')->group(function () {
    Route::get('/', [ModuleController::class, 'index'])->name('index');
    Route::post('/', [ModuleController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [ModuleController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [ModuleController::class, 'update'])->name('update');
    Route::delete('/{item}', [ModuleController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('allergies', [AllergiesController::class, 'index'])->name('allergies.index');
    Route::post('allergies', [AllergiesController::class, 'store'])->name('allergies.store');
    Route::get('allergies/{item}/edit', [AllergiesController::class, 'edit'])->name('allergies.edit');
    Route::patch('allergies/{item}', [AllergiesController::class, 'update'])->name('allergies.update');
    Route::delete('allergies/{item}', [AllergiesController::class, 'destroy'])->name('allergies.destroy');

    Route::get('health-backgrounds', [HealthBackgroundsController::class, 'index'])->name('health-backgrounds.index');
    Route::post('health-backgrounds', [HealthBackgroundsController::class, 'store'])->name('health-backgrounds.store');
    Route::get('health-backgrounds/{item}/edit', [HealthBackgroundsController::class, 'edit'])->name('health-backgrounds.edit');
    Route::patch('health-backgrounds/{item}', [HealthBackgroundsController::class, 'update'])->name('health-backgrounds.update');
    Route::delete('health-backgrounds/{item}', [HealthBackgroundsController::class, 'destroy'])->name('health-backgrounds.destroy');
});
use App\Http\Controllers\Doctors\DoctorsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('doctors', [DoctorsController::class, 'index'])->name('doctors.index');
    Route::post('doctors', [DoctorsController::class, 'store'])->name('doctors.store');
    Route::get('doctors/{item}/edit', [DoctorsController::class, 'edit'])->name('doctors.edit');
    Route::patch('doctors/{item}', [DoctorsController::class, 'update'])->name('doctors.update');
    Route::delete('doctors/{item}', [DoctorsController::class, 'destroy'])->name('doctors.destroy');
});
use App\Http\Controllers\MedicalSpecialties\MedicalSpecialtiesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('medical-specialties', [MedicalSpecialtiesController::class, 'index'])->name('medical-specialties.index');
    Route::post('medical-specialties', [MedicalSpecialtiesController::class, 'store'])->name('medical-specialties.store');
    Route::get('medical-specialties/{item}/edit', [MedicalSpecialtiesController::class, 'edit'])->name('medical-specialties.edit');
    Route::patch('medical-specialties/{item}', [MedicalSpecialtiesController::class, 'update'])->name('medical-specialties.update');
    Route::delete('medical-specialties/{item}', [MedicalSpecialtiesController::class, 'destroy'])->name('medical-specialties.destroy');
});
use App\Http\Controllers\States\StatesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('states', [StatesController::class, 'index'])->name('states.index');
    Route::post('states', [StatesController::class, 'store'])->name('states.store');
    Route::get('states/{item}/edit', [StatesController::class, 'edit'])->name('states.edit');
    Route::patch('states/{item}', [StatesController::class, 'update'])->name('states.update');
    Route::patch('states/{item}/toggle-active', [StatesController::class, 'toggleActive'])->name('states.toggleActive');
    Route::delete('states/{item}', [StatesController::class, 'destroy'])->name('states.destroy');
});
use App\Http\Controllers\Municipalities\MunicipalitiesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('municipalities', [MunicipalitiesController::class, 'index'])->name('municipalities.index');
    Route::post('municipalities', [MunicipalitiesController::class, 'store'])->name('municipalities.store');
    Route::get('municipalities/{item}/edit', [MunicipalitiesController::class, 'edit'])->name('municipalities.edit');
    Route::patch('municipalities/{item}', [MunicipalitiesController::class, 'update'])->name('municipalities.update');
    Route::patch('municipalities/{item}/toggle-active', [MunicipalitiesController::class, 'toggleActive'])->name('municipalities.toggleActive');
    Route::delete('municipalities/{item}', [MunicipalitiesController::class, 'destroy'])->name('municipalities.destroy');
});
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\MedicalHistories\MedicalHistoriesController;
use App\Http\Controllers\ServiceRequestController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('medical-histories', [MedicalHistoriesController::class, 'index'])->name('medical-histories.index');
    Route::post('medical-histories', [MedicalHistoriesController::class, 'store'])->name('medical-histories.store');
    Route::get('medical-histories/search', [MedicalHistoriesController::class, 'search'])->name('medical-histories.search');
    Route::get('medical-histories/{item}/edit', [MedicalHistoriesController::class, 'edit'])->name('medical-histories.edit');
    Route::patch('medical-histories/{item}', [MedicalHistoriesController::class, 'update'])->name('medical-histories.update');
    Route::delete('medical-histories/{item}', [MedicalHistoriesController::class, 'destroy'])->name('medical-histories.destroy');

    Route::post('encounters', [EncounterController::class, 'store'])->name('encounters.store');
    Route::post('service-requests', [ServiceRequestController::class, 'store'])->name('service-requests.store');
});
