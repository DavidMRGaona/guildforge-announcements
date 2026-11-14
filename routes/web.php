<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Announcements\Http\Controllers\AnnouncementController;
use Modules\Announcements\Http\Controllers\PopupViewController;

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

/*
 * The browser tells which windows it showed and closed (announcements#68), only for a signed-in
 * account; guests keep it in the browser alone.
 */
Route::prefix('anuncios/ventanas')
    ->name('announcements.popups.')
    ->middleware(['auth', 'email.verified-if-required', 'throttle:60,1,announcement-popups'])
    ->group(function (): void {
        Route::post('/{id}/mostrada', [PopupViewController::class, 'shown'])
            ->name('shown')
            ->whereUuid('id');

        Route::post('/{id}/cerrada', [PopupViewController::class, 'dismissed'])
            ->name('dismissed')
            ->whereUuid('id');
    });
