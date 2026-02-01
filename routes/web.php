<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Announcements\Http\Controllers\AnnouncementController;

/*
|--------------------------------------------------------------------------
| Announcements Web Routes
|--------------------------------------------------------------------------
|
| Routes for the Announcements module using web middleware for
| session-based functionality with Inertia.js frontend.
|
*/

Route::prefix('anuncios')
    ->name('announcements.')
    ->group(function (): void {
        Route::get('/', [AnnouncementController::class, 'index'])
            ->name('index');

        Route::get('/{id}', [AnnouncementController::class, 'show'])
            ->name('show')
            ->whereUuid('id');
    });
