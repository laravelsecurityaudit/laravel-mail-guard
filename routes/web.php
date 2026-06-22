<?php

use Illuminate\Support\Facades\Route;
use LaravelSecurityAudit\MailGuard\Http\Controllers\InboxController;
use LaravelSecurityAudit\MailGuard\Http\Middleware\AuthorizeInbox;

$middleware = (array) config('mail-guard.middleware', ['web']);
$middleware[] = AuthorizeInbox::class;

Route::middleware($middleware)
    ->prefix(trim((string) config('mail-guard.path', 'mail-guard'), '/'))
    ->name('mail-guard.')
    ->group(function (): void {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::delete('/', [InboxController::class, 'destroyAll'])->name('destroy-all');
        Route::get('/{message}', [InboxController::class, 'show'])->name('show');
        Route::delete('/{message}', [InboxController::class, 'destroy'])->name('destroy');
    });
