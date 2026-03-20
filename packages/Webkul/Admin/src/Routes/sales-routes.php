<?php

declare (strict_types=1);
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Sales\Booking_Controller;
use Webkul\Admin\Http\Controllers\Sales\Cart_Controller;
use Webkul\Admin\Http\Controllers\Sales\Invoice_Controller;
use Webkul\Admin\Http\Controllers\Sales\Order_Controller;
use Webkul\Admin\Http\Controllers\Sales\Refund_Controller;
use Webkul\Admin\Http\Controllers\Sales\Shipment_Controller;
use Webkul\Admin\Http\Controllers\Sales\Transaction_Controller;
/**
 * Sales routes.
 */
Route::prefix('sales')->group(function () {
    /**
     * Invoices routes.
     */
    Route::controller(Invoice_Controller::class)->prefix('invoices')->group(function () {
        Route::get('', 'index')->name('admin.sales.invoices.index');
        Route::post('create/{order_id}', 'store')->name('admin.sales.invoices.store');
        Route::get('view/{id}', 'view')->name('admin.sales.invoices.view');
        Route::post('send-duplicate-email/{id}', 'sendDuplicateEmail')->name('admin.sales.invoices.send_duplicate_email');
        Route::get('print/{id}', 'printInvoice')->name('admin.sales.invoices.print');
        Route::post('mass-update/state', 'massUpdateState')->name('admin.sales.invoices.mass_update.state');
    });
    /**
     * Orders routes.
     */
    Route::controller(Order_Controller::class)->prefix('orders')->group(function () {
        Route::get('', 'index')->name('admin.sales.orders.index');
        Route::get('create/{cartId}', 'create')->name('admin.sales.orders.create');
        Route::post('create/{cartId}', 'store')->name('admin.sales.orders.store');
        Route::get('view/{id}', 'view')->name('admin.sales.orders.view');
        Route::post('cancel/{id}', 'cancel')->name('admin.sales.orders.cancel');
        Route::get('reorder/{id}', 'reorder')->name('admin.sales.orders.reorder');
        Route::post('comment/{order_id}', 'comment')->name('admin.sales.orders.comment');
        Route::get('search', 'search')->name('admin.sales.orders.search');
    });
    /**
     * Refunds routes.
     */
    Route::controller(Refund_Controller::class)->prefix('refunds')->group(function () {
        Route::get('', 'index')->name('admin.sales.refunds.index');
        Route::post('create/{order_id}', 'store')->name('admin.sales.refunds.store');
        Route::post('update-totals/{order_id}', 'updateTotals')->name('admin.sales.refunds.update_totals');
        Route::get('view/{id}', 'view')->name('admin.sales.refunds.view');
    });
    /**
     * Shipments routes.
     */
    Route::controller(Shipment_Controller::class)->prefix('shipments')->group(function () {
        Route::get('', 'index')->name('admin.sales.shipments.index');
        Route::post('create/{order_id}', 'store')->name('admin.sales.shipments.store');
        Route::get('view/{id}', 'view')->name('admin.sales.shipments.view');
    });
    /**
     * Transactions routes.
     */
    Route::controller(Transaction_Controller::class)->prefix('transactions')->group(function () {
        Route::get('', 'index')->name('admin.sales.transactions.index');
        Route::post('create', 'store')->name('admin.sales.transactions.store');
        Route::get('view/{id}', 'view')->name('admin.sales.transactions.view');
    });
    Route::controller(Cart_Controller::class)->prefix('cart')->group(function () {
        Route::get('{id}', 'index')->name('admin.sales.cart.index');
        Route::post('create', 'store')->name('admin.sales.cart.store');
        Route::post('{id}/items', 'storeItem')->name('admin.sales.cart.items.store');
        Route::put('{id}/items', 'updateItem')->name('admin.sales.cart.items.update');
        Route::delete('{id}/items', 'destroyItem')->name('admin.sales.cart.items.destroy');
        Route::post('{id}/addresses', 'storeAddress')->name('admin.sales.cart.addresses.store');
        Route::post('{id}/shipping-methods', 'storeShippingMethod')->name('admin.sales.cart.shipping_methods.store');
        Route::post('{id}/payment-methods', 'storePaymentMethod')->name('admin.sales.cart.payment_methods.store');
        Route::post('{id}/coupon', 'storeCoupon')->name('admin.sales.cart.store_coupon');
        Route::delete('{id}/coupon', 'destroyCoupon')->name('admin.sales.cart.remove_coupon');
    });
    Route::controller(Booking_Controller::class)->prefix('bookings')->group(function () {
        Route::get('', 'index')->name('admin.sales.bookings.index');
        Route::get('get', 'get')->name('admin.sales.bookings.get');
    });
});