<?php

declare(strict_types=1);

/**
 * Example: Common cart operations in Bagisto
 *
 * Demonstrates how to interact with the Cart facade for adding products,
 * checking out, and handling errors in a Bagisto storefront.
 *
 * The Cart facade wraps Webkul\Checkout\Cart and is available anywhere
 * in the application via the service container or Facade alias.
 *
 * All operations below would be called from a Controller or service class.
 */

use Webkul\Checkout\Facades\Cart;

// -------------------------------------------------------------------------
// 1. Add a product to the cart
// -------------------------------------------------------------------------
$product = app('Webkul\Product\Repositories\ProductRepository')->find(42);

// $data matches the request payload from the "Add to Cart" form
$data = [
    'product_id' => $product->id,
    'quantity'   => 2,
];

$result = Cart::addProduct($product, $data);

if ($result instanceof \Webkul\Checkout\Contracts\Cart) {
    echo 'Product added. Cart now has ' . Cart::getCart()->items_count . ' distinct items.' . PHP_EOL;
} else {
    echo 'Failed to add product: ' . ($result['warning'] ?? 'unknown error') . PHP_EOL;
}

// -------------------------------------------------------------------------
// 2. Check for cart errors before proceeding to checkout
// -------------------------------------------------------------------------
if (Cart::hasError()) {
    echo 'Cart has errors — redirect back to cart page.' . PHP_EOL;
} else {
    echo 'Cart is valid — proceed to checkout.' . PHP_EOL;
}

// -------------------------------------------------------------------------
// 3. Collect totals (runs shipping, tax, coupon calculations)
// -------------------------------------------------------------------------
Cart::collectTotals();

$cart = Cart::getCart();

echo 'Subtotal:     ' . core()->formatPrice($cart->sub_total) . PHP_EOL;
echo 'Grand total:  ' . core()->formatPrice($cart->grand_total) . PHP_EOL;

// -------------------------------------------------------------------------
// 4. Apply a coupon code
// -------------------------------------------------------------------------
$cart = Cart::setCouponCode('SUMMER20')->collectTotals()->getCart();

echo 'Discount:     ' . core()->formatPrice($cart->discount_amount) . PHP_EOL;

// -------------------------------------------------------------------------
// 5. Listening to cart events in a ServiceProvider
// -------------------------------------------------------------------------
// Event::listen('checkout.cart.add.after', function ($cart) {
//     \Log::info('Item added to cart', ['cart_id' => $cart->id]);
// });
