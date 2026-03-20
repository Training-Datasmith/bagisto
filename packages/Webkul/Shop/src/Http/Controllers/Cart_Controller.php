<?php

declare(strict_types=1);

namespace Webkul\Shop\Http\Controllers;

class CartController extends Controller
{
    /**
     * Renders the shopping cart page.
     *
     * Aborts with a 404 response if the cart page feature is disabled in store
     * configuration (sales.checkout.shopping_cart.cart_page).
     *
     * @return \Illuminate\View\View The cart index view
     */
    public function index(): \Illuminate\View\View
    {
        if (! core()->getConfigData('sales.checkout.shopping_cart.cart_page')) {
            abort(404);
        }

        return view('shop::checkout.cart.index');
    }
}
