<?php

declare (strict_types=1);
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Controllers\User\Forget_Password_Controller;
use Webkul\Admin\Http\Controllers\User\Reset_Password_Controller;
use Webkul\Admin\Http\Controllers\User\Session_Controller;
/**
 * Auth routes.
 */
Route::group(['prefix' => config('app.admin_url')], function () {
    /**
     * Redirect route.
     */
    Route::get('/', [Controller::class, 'redirectToLogin']);
    Route::controller(Session_Controller::class)->prefix('login')->group(function () {
        /**
         * Login routes.
         */
        Route::get('', 'create')->name('admin.session.create');
        /**
         * Login post route to admin auth controller.
         */
        Route::post('', 'store')->name('admin.session.store');
    });
    /**
     * Forget password routes.
     */
    Route::controller(Forget_Password_Controller::class)->prefix('forget-password')->group(function () {
        Route::get('', 'create')->name('admin.forget_password.create');
        Route::post('', 'store')->name('admin.forget_password.store');
    });
    /**
     * Reset password routes.
     */
    Route::controller(Reset_Password_Controller::class)->prefix('reset-password')->group(function () {
        Route::get('{token}', 'create')->name('admin.reset_password.create');
        Route::post('', 'store')->name('admin.reset_password.store');
    });
});