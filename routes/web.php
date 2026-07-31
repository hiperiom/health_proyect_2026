<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Doctors\DoctorController;
use App\Http\Controllers\MedicalEspecialties\MedicalEspecialtiesController;
use App\Http\Controllers\Modules\ModuleController;
use App\Http\Controllers\Patients\PatientPhotoController;
use App\Http\Controllers\Patients\PatientsController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\RoleSelectionController;
use App\Http\Controllers\SwitchRoleController;
use App\Http\Controllers\Users\UserController;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureRoleSelection;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

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
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::patch('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    Route::post('/{user}/permissions', [UserController::class, 'assignPermissions'])->name('assignPermissions');
    Route::patch('/{user}/roles', [UserController::class, 'assignRoles'])->name('assignRoles');
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

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('medicalespecialties')->name('medicalespecialties.')->group(function () {
    Route::get('/', [MedicalEspecialtiesController::class, 'index'])->name('index');
    Route::post('/', [MedicalEspecialtiesController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [MedicalEspecialtiesController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [MedicalEspecialtiesController::class, 'update'])->name('update');
    Route::delete('/{item}', [MedicalEspecialtiesController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('patients')->name('patients.')->group(function () {
    Route::get('/', [PatientsController::class, 'index'])->name('index');
    Route::post('/', [PatientsController::class, 'store'])->name('store');
    Route::get('/check-dni', [PatientsController::class, 'checkDni'])->name('check-dni');
    Route::get('/check-email', [PatientsController::class, 'checkEmail'])->name('check-email');
    Route::get('/{item}/edit', [PatientsController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [PatientsController::class, 'update'])->name('update');
    Route::delete('/{item}', [PatientsController::class, 'destroy'])->name('destroy');
    Route::post('/{item}/photo', [PatientPhotoController::class, 'store'])->name('photo.store');
    Route::delete('/{item}/photo', [PatientPhotoController::class, 'destroy'])->name('photo.destroy');
});

Route::middleware(['auth', EnsureModuleAccess::class])->prefix('doctors')->name('doctors.')->group(function () {
    Route::get('/', [DoctorController::class, 'index'])->name('index');
    Route::post('/', [DoctorController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [DoctorController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [DoctorController::class, 'update'])->name('update');
    Route::delete('/{item}', [DoctorController::class, 'destroy'])->name('destroy');
});
