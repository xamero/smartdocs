<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Documents
    Route::resource('documents', \App\Http\Controllers\DocumentController::class);
    Route::post('documents/{document}/route', [\App\Http\Controllers\DocumentRoutingController::class, 'store'])->name('documents.route');
    Route::post('documents/{document}/routings/{routing}/receive', [\App\Http\Controllers\DocumentRoutingController::class, 'receive'])->name('documents.routings.receive');
    Route::post('documents/{document}/actions', [\App\Http\Controllers\DocumentActionController::class, 'store'])->name('documents.actions.store');
    Route::post('documents/{document}/attachments', [\App\Http\Controllers\DocumentAttachmentController::class, 'store'])->name('documents.attachments.store');
    Route::get('documents/{document}/attachments/{attachment}/download', [\App\Http\Controllers\DocumentAttachmentController::class, 'download'])->name('documents.attachments.download');
    Route::delete('documents/{document}/attachments/{attachment}', [\App\Http\Controllers\DocumentAttachmentController::class, 'destroy'])->name('documents.attachments.destroy');
    Route::get('documents/verify/{code}', function ($code) {
        $qrCode = app(\App\Services\QRCodeService::class)->verify($code);

        return Inertia::render('qr/Verify', [
            'code' => $code,
            'qrCode' => $qrCode,
            'document' => $qrCode?->document,
        ]);
    })->name('documents.verify');

    Route::get('documents/{document}/qr-code/download', [\App\Http\Controllers\DocumentController::class, 'downloadQRCode'])->name('documents.qr-code.download');
    Route::post('documents/{document}/qr-code/regenerate', [\App\Http\Controllers\DocumentController::class, 'regenerateQRCode'])->name('documents.qr-code.regenerate');
    Route::post('documents/{document}/archive', [\App\Http\Controllers\DocumentController::class, 'archive'])->name('documents.archive');
    Route::post('documents/{document}/restore', [\App\Http\Controllers\DocumentController::class, 'restore'])->name('documents.restore');
    Route::post('documents/import', [\App\Http\Controllers\DocumentController::class, 'import'])->name('documents.import');

    // Offices
    Route::get('offices/my', [\App\Http\Controllers\OfficeController::class, 'my'])->name('offices.my');
    Route::resource('offices', \App\Http\Controllers\OfficeController::class)->except(['show', 'create', 'edit']);

    // Users
    Route::resource('users', \App\Http\Controllers\UserController::class);

    // Notifications
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('notifications/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__ . '/settings.php';
