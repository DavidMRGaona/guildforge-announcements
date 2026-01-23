<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Announcements\Http\Controllers\AnnouncementController;

/*
|--------------------------------------------------------------------------
| Announcements API Routes
|--------------------------------------------------------------------------
|
| API routes for the Announcements module.
|
*/

Route::prefix('api/announcements')
    ->name('announcements.api.')
    ->middleware('api')
    ->group(function (): void {
        Route::get('/', [AnnouncementController::class, 'index'])
            ->name('index');

        Route::get('/{id}', [AnnouncementController::class, 'show'])
            ->name('show')
            ->whereUuid('id');
    });
