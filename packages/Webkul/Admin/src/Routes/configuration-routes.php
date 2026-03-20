<?php

declare (strict_types=1);
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Configuration_Controller;
/**
 * Configuration routes.
 */
Route::get('configuration/search', [Configuration_Controller::class, 'search'])->name('admin.configuration.search');
Route::controller(Configuration_Controller::class)->prefix('configuration/{slug?}/{slug2?}')->group(function () {
    Route::get('', 'index')->name('admin.configuration.index');
    Route::post('', 'store')->name('admin.configuration.store');
    Route::get('{path}', 'download')->name('admin.configuration.download');
});