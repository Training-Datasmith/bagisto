<?php

declare (strict_types=1);
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Reporting\Customer_Controller;
use Webkul\Admin\Http\Controllers\Reporting\Product_Controller;
use Webkul\Admin\Http\Controllers\Reporting\Sale_Controller;
/**
 * Reporting routes.
 */
Route::prefix('reporting')->group(function () {
    /**
     * Customer routes.
     */
    Route::controller(Customer_Controller::class)->prefix('customers')->group(function () {
        Route::get('', 'index')->name('admin.reporting.customers.index');
        Route::get('stats', 'stats')->name('admin.reporting.customers.stats');
        Route::get('export', 'export')->name('admin.reporting.customers.export');
        Route::get('view', 'view')->name('admin.reporting.customers.view');
        Route::get('view/stats', 'viewStats')->name('admin.reporting.customers.view.stats');
    });
    /**
     * Product routes.
     */
    Route::controller(Product_Controller::class)->prefix('products')->group(function () {
        Route::get('', 'index')->name('admin.reporting.products.index');
        Route::get('stats', 'stats')->name('admin.reporting.products.stats');
        Route::get('export', 'export')->name('admin.reporting.products.export');
        Route::get('view', 'view')->name('admin.reporting.products.view');
        Route::get('view/stats', 'viewStats')->name('admin.reporting.products.view.stats');
    });
    /**
     * Sale routes.
     */
    Route::controller(Sale_Controller::class)->prefix('sales')->group(function () {
        Route::get('', 'index')->name('admin.reporting.sales.index');
        Route::get('stats', 'stats')->name('admin.reporting.sales.stats');
        Route::get('export', 'export')->name('admin.reporting.sales.export');
        Route::get('view', 'view')->name('admin.reporting.sales.view');
        Route::get('view/stats', 'viewStats')->name('admin.reporting.sales.view.stats');
    });
});