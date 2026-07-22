<?php

use App\Http\Controllers\Settings\BackupController;
use App\Http\Controllers\Settings\ExportController;
use App\Http\Controllers\Settings\MembersController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar.show');
    Route::post('settings/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('settings/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::get(
        'settings/appearance',
        fn (): RedirectResponse => to_route('preferences.edit'),
    )->name('appearance.edit');
    Route::inertia('settings/preferences', 'settings/Preferences')->name('preferences.edit');
    Route::inertia('settings/notifications', 'settings/Notifications')->name('notifications.edit');
    Route::get('settings/export', [ExportController::class, 'edit'])->name('export.edit');
    Route::get('settings/members', [MembersController::class, 'edit'])->name('members.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/backup', [BackupController::class, 'edit'])
        ->middleware(['can:manageDatabaseBackups', RequirePassword::class])
        ->name('backup.edit');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
