<?php

declare (strict_types=1);
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Catalog\Attribute_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Attribute_Family_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Category_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product\Bundle_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product\Configurable_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product\Downloadable_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product\Grouped_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product\Simple_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product\Virtual_Controller;
use Webkul\Admin\Http\Controllers\Catalog\Product_Controller;
/**
 * Catalog routes.
 */
Route::prefix('catalog')->group(function () {
    /**
     * Attributes routes.
     */
    Route::controller(Attribute_Controller::class)->prefix('attributes')->group(function () {
        Route::get('', 'index')->name('admin.catalog.attributes.index');
        Route::get('{id}/options', 'getAttributeOptions')->name('admin.catalog.attributes.options');
        Route::get('create', 'create')->name('admin.catalog.attributes.create');
        Route::post('create', 'store')->name('admin.catalog.attributes.store');
        Route::get('edit/{id}', 'edit')->name('admin.catalog.attributes.edit');
        Route::put('edit/{id}', 'update')->name('admin.catalog.attributes.update');
        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.attributes.delete');
        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.attributes.mass_delete');
    });
    /**
     * Attribute families routes.
     */
    Route::controller(Attribute_Family_Controller::class)->prefix('families')->group(function () {
        Route::get('', 'index')->name('admin.catalog.families.index');
        Route::get('create', 'create')->name('admin.catalog.families.create');
        Route::post('create', 'store')->name('admin.catalog.families.store');
        Route::get('edit/{id}', 'edit')->name('admin.catalog.families.edit');
        Route::put('edit/{id}', 'update')->name('admin.catalog.families.update');
        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.families.delete');
    });
    /**
     * Categories routes.
     */
    Route::controller(Category_Controller::class)->prefix('categories')->group(function () {
        Route::get('', 'index')->name('admin.catalog.categories.index');
        Route::get('create', 'create')->name('admin.catalog.categories.create');
        Route::post('create', 'store')->name('admin.catalog.categories.store');
        Route::get('edit/{id}', 'edit')->name('admin.catalog.categories.edit');
        Route::put('edit/{id}', 'update')->name('admin.catalog.categories.update');
        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.categories.delete');
        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.categories.mass_delete');
        Route::post('mass-update', 'massUpdate')->name('admin.catalog.categories.mass_update');
        Route::get('search', 'search')->name('admin.catalog.categories.search');
        Route::get('tree', 'tree')->name('admin.catalog.categories.tree');
    });
    /**
     * Sync route.
     */
    Route::get('/sync', [Product_Controller::class, 'sync']);
    /**
     * Products routes.
     */
    Route::controller(Product_Controller::class)->prefix('products')->group(function () {
        Route::get('', 'index')->name('admin.catalog.products.index');
        Route::post('create', 'store')->name('admin.catalog.products.store');
        Route::post('copy/{id}', 'copy')->name('admin.catalog.products.copy');
        Route::get('edit/{id}', 'edit')->name('admin.catalog.products.edit');
        Route::put('edit/{id}', 'update')->name('admin.catalog.products.update');
        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.products.delete');
        Route::put('edit/{id}/inventories', 'updateInventories')->name('admin.catalog.products.update_inventories');
        Route::post('upload-file/{id}', 'uploadLink')->name('admin.catalog.products.upload_link');
        Route::post('upload-sample/{id}', 'uploadSample')->name('admin.catalog.products.upload_sample');
        Route::post('mass-update', 'massUpdate')->name('admin.catalog.products.mass_update');
        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.products.mass_delete');
        Route::controller(Simple_Controller::class)->group(function () {
            Route::get('{id}/simple-customizable-options', 'customizableOptions')->name('admin.catalog.products.simple.customizable-options');
        });
        Route::controller(Configurable_Controller::class)->group(function () {
            Route::get('{id}/configurable-options', 'options')->name('admin.catalog.products.configurable.options');
        });
        Route::controller(Bundle_Controller::class)->group(function () {
            Route::get('{id}/bundle-options', 'options')->name('admin.catalog.products.bundle.options');
        });
        Route::controller(Grouped_Controller::class)->group(function () {
            Route::get('{id}/grouped-options', 'options')->name('admin.catalog.products.grouped.options');
        });
        Route::controller(Downloadable_Controller::class)->group(function () {
            Route::get('{id}/downloadable-options', 'options')->name('admin.catalog.products.downloadable.options');
        });
        Route::controller(Virtual_Controller::class)->group(function () {
            Route::get('{id}/virtual-customizable-options', 'customizableOptions')->name('admin.catalog.products.virtual.customizable-options');
        });
        Route::get('search', 'search')->name('admin.catalog.products.search');
        Route::get('{id}/{attribute_id}', 'download')->name('admin.catalog.products.file.download');
    });
});