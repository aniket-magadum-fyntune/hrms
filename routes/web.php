<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('users', [UserController::class, 'index'])
        ->name('users.index')
        ->middleware('permission:users.view');
    Route::post('users', [UserController::class, 'store'])
        ->name('users.store')
        ->middleware('permission:users.create');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])
        ->name('users.update')
        ->middleware('permission:users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy')
        ->middleware('permission:users.delete');

    Route::get('roles', [RoleController::class, 'index'])
        ->name('roles.index')
        ->middleware('permission:roles.view');
    Route::post('roles', [RoleController::class, 'store'])
        ->name('roles.store')
        ->middleware('permission:roles.create');
    Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])
        ->name('roles.update')
        ->middleware('permission:roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->name('roles.destroy')
        ->middleware('permission:roles.delete');

    Route::get('departments', [DepartmentController::class, 'index'])
        ->name('departments.index')
        ->middleware('permission:departments.view');
    Route::post('departments', [DepartmentController::class, 'store'])
        ->name('departments.store')
        ->middleware('permission:departments.create');
    Route::match(['put', 'patch'], 'departments/{department}', [DepartmentController::class, 'update'])
        ->name('departments.update')
        ->middleware('permission:departments.update');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])
        ->name('departments.destroy')
        ->middleware('permission:departments.delete');

    Route::get('designations', [DesignationController::class, 'index'])
        ->name('designations.index')
        ->middleware('permission:designations.view');
    Route::post('designations', [DesignationController::class, 'store'])
        ->name('designations.store')
        ->middleware('permission:designations.create');
    Route::match(['put', 'patch'], 'designations/{designation}', [DesignationController::class, 'update'])
        ->name('designations.update')
        ->middleware('permission:designations.update');
    Route::delete('designations/{designation}', [DesignationController::class, 'destroy'])
        ->name('designations.destroy')
        ->middleware('permission:designations.delete');

    Route::get('permissions', [PermissionController::class, 'index'])
        ->name('permissions.index')
        ->middleware('role:Super Admin');
});

require __DIR__.'/settings.php';
