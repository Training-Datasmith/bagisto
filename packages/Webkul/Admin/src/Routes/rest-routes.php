<?php

declare (strict_types=1);
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Dashboard_Controller;
use Webkul\Admin\Http\Controllers\Data_Grid\Data_Grid_Controller;
use Webkul\Admin\Http\Controllers\Data_Grid\Saved_Filter_Controller;
use Webkul\Admin\Http\Controllers\Magic_Ai_Controller;
use Webkul\Admin\Http\Controllers\Tiny_Mce_Controller;
use Webkul\Admin\Http\Controllers\User\Account_Controller;
use Webkul\Admin\Http\Controllers\User\Session_Controller;
/**
 * Dashboard routes.
 */
Route::controller(Dashboard_Controller::class)->prefix('dashboard')->group(function () {
    Route::get('', 'index')->name('admin.dashboard.index');
    Route::get('stats', 'stats')->name('admin.dashboard.stats');
});
/**
 * Datagrid routes.
 */
Route::controller(Data_Grid_Controller::class)->prefix('datagrid')->group(function () {
    Route::get('look-up', 'lookUp')->name('admin.datagrid.look_up');
    Route::controller(Saved_Filter_Controller::class)->prefix('saved-filters')->group(function () {
        Route::post('', 'store')->name('admin.datagrid.saved_filters.store');
        Route::get('', 'get')->name('admin.datagrid.saved_filters.index');
        Route::put('{id}', 'update')->name('admin.datagrid.saved_filters.update');
        Route::delete('{id}', 'destroy')->name('admin.datagrid.saved_filters.destroy');
    });
});
/**
 * Tinymce file upload handler.
 */
Route::post('tinymce/upload', [Tiny_Mce_Controller::class, 'upload'])->name('admin.tinymce.upload');
/**
 * AI Routes
 */
Route::controller(Magic_Ai_Controller::class)->prefix('magic-ai')->group(function () {
    Route::post('content', 'content')->name('admin.magic_ai.content');
    Route::post('image', 'image')->name('admin.magic_ai.image');
});
/**
 * Admin profile routes.
 */
Route::controller(Account_Controller::class)->prefix('account')->group(function () {
    Route::get('', 'edit')->name('admin.account.edit');
    Route::put('', 'update')->name('admin.account.update');
});
Route::delete('logout', [Session_Controller::class, 'destroy'])->name('admin.session.destroy');