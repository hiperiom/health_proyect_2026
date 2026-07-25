<?php

use App\Http\Controllers\Appointments\AppointmentController;
use App\Http\Controllers\Bills\BillController;
use App\Http\Controllers\Categorys\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Doctors\DoctorController;
use App\Http\Controllers\Insurances\InsuranceController;
use App\Http\Controllers\MedicalEspecialties\MedicalEspecialtiesController;
use App\Http\Controllers\Modules\ModuleController;
use App\Http\Controllers\Patients\PatientPhotoController;
use App\Http\Controllers\Patients\PatientsController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\Regions\RegionController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Specialtys\SpecialtyController;
use App\Http\Controllers\Towns\TownController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';

Route::middleware(['auth'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::patch('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{item}', [RoleController::class, 'destroy'])->name('destroy');
    Route::post('/{item}/permissions', [RoleController::class, 'assignPermissions'])->name('assignPermissions');
});

Route::middleware(['auth'])->prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::post('/', [PermissionController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [PermissionController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [PermissionController::class, 'update'])->name('update');
    Route::delete('/{item}', [PermissionController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('modules')->name('modules.')->group(function () {
    Route::get('/', [ModuleController::class, 'index'])->name('index');
    Route::post('/', [ModuleController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [ModuleController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [ModuleController::class, 'update'])->name('update');
    Route::delete('/{item}', [ModuleController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('medicalespecialties')->name('medicalespecialties.')->group(function () {
    Route::get('/', [MedicalEspecialtiesController::class, 'index'])->name('index');
    Route::post('/', [MedicalEspecialtiesController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [MedicalEspecialtiesController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [MedicalEspecialtiesController::class, 'update'])->name('update');
    Route::delete('/{item}', [MedicalEspecialtiesController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('patients')->name('patients.')->group(function () {
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

Route::middleware(['auth'])->prefix('doctors')->name('doctors.')->group(function () {
    Route::get('/', [DoctorController::class, 'index'])->name('index');
    Route::post('/', [DoctorController::class, 'store'])->name('store');
    Route::get('/{item}/edit', [DoctorController::class, 'edit'])->name('edit');
    Route::patch('/{item}', [DoctorController::class, 'update'])->name('update');
    Route::delete('/{item}', [DoctorController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('appointments/{item}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::patch('appointments/{item}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('appointments/{item}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('insurances', [InsuranceController::class, 'index'])->name('insurances.index');
    Route::post('insurances', [InsuranceController::class, 'store'])->name('insurances.store');
    Route::get('insurances/{item}/edit', [InsuranceController::class, 'edit'])->name('insurances.edit');
    Route::patch('insurances/{item}', [InsuranceController::class, 'update'])->name('insurances.update');
    Route::delete('insurances/{item}', [InsuranceController::class, 'destroy'])->name('insurances.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('bills', [BillController::class, 'index'])->name('bills.index');
    Route::post('bills', [BillController::class, 'store'])->name('bills.store');
    Route::get('bills/{item}/edit', [BillController::class, 'edit'])->name('bills.edit');
    Route::patch('bills/{item}', [BillController::class, 'update'])->name('bills.update');
    Route::delete('bills/{item}', [BillController::class, 'destroy'])->name('bills.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{item}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('categories/{item}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{item}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('specialties', [SpecialtyController::class, 'index'])->name('specialties.index');
    Route::post('specialties', [SpecialtyController::class, 'store'])->name('specialties.store');
    Route::get('specialties/{item}/edit', [SpecialtyController::class, 'edit'])->name('specialties.edit');
    Route::patch('specialties/{item}', [SpecialtyController::class, 'update'])->name('specialties.update');
    Route::delete('specialties/{item}', [SpecialtyController::class, 'destroy'])->name('specialties.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('regions', [RegionController::class, 'index'])->name('regions.index');
    Route::post('regions', [RegionController::class, 'store'])->name('regions.store');
    Route::get('regions/{item}/edit', [RegionController::class, 'edit'])->name('regions.edit');
    Route::patch('regions/{item}', [RegionController::class, 'update'])->name('regions.update');
    Route::delete('regions/{item}', [RegionController::class, 'destroy'])->name('regions.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('towns', [TownController::class, 'index'])->name('towns.index');
    Route::post('towns', [TownController::class, 'store'])->name('towns.store');
    Route::get('towns/{item}/edit', [TownController::class, 'edit'])->name('towns.edit');
    Route::patch('towns/{item}', [TownController::class, 'update'])->name('towns.update');
    Route::delete('towns/{item}', [TownController::class, 'destroy'])->name('towns.destroy');
});
