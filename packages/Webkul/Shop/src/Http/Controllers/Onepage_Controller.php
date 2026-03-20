<?php

declare(strict_types=1);

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Facades\Cart;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Repositories\OrderRepository;

class OnepageController extends Controller
{
    /**
     * Displays the one-page checkout view after validating cart and customer eligibility.
     *
     * Performs the following pre-flight checks before rendering the checkout:
     * - Cart page must be enabled in store configuration
     * - Guest checkout must be allowed, or customer must be authenticated
     * - Customer account must not be suspended
     * - Cart must have no validation errors
     * - Downloadable-only carts require an authenticated customer
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *         Checkout view, or redirect to cart/login page if any check fails
     */
    public function index()
    {
        if (! core()->getConfigData('sales.checkout.shopping_cart.cart_page')) {
            abort(404);
        }

        Event::dispatch('checkout.load.index');

        /**
         * If guest checkout is not allowed then redirect back to the cart page
         */
        if (
            ! auth()->guard('customer')->check()
            && ! core()->getConfigData('sales.checkout.shopping_cart.allow_guest_checkout')
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        /**
         * If user is suspended then redirect back to the cart page
         */
        if (auth()->guard('customer')->user()?->is_suspended) {
            session()->flash('warning', trans('shop::app.checkout.cart.suspended-account-message'));

            return redirect()->route('shop.checkout.cart.index');
        }

        /**
         * If cart has errors then redirect back to the cart page
         */
        if (Cart::hasError()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        /**
         * If cart is has downloadable items and customer is not logged in
         * then redirect back to the cart page
         */
        if (
            ! auth()->guard('customer')->check()
            && (
                $cart->hasDownloadableItems()
                || ! $cart->hasGuestCheckoutItems()
            )
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        return view('shop::checkout.onepage.index', compact('cart'));
    }

    /**
     * Renders the post-checkout order success page.
     *
     * Reads the order ID from the session (`order_id` key). If MagicAI checkout
     * messaging is enabled, generates a personalised message via the configured LLM
     * and attaches it to the order DTO for display. Exceptions from the AI service
     * are swallowed so a failure never blocks the success page.
     *
     * @param OrderRepository $orderRepository Repository used to load the placed order
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *         Success view with order data, or redirect to cart if session order ID is missing
     */
    public function success(OrderRepository $orderRepository)
    {
        if (! $order = $orderRepository->find(session('order_id'))) {
            return redirect()->route('shop.checkout.cart.index');
        }

        if (
            core()->getConfigData('general.magic_ai.settings.enabled')
            && core()->getConfigData('general.magic_ai.checkout_message.enabled')
            && ! empty(core()->getConfigData('general.magic_ai.checkout_message.prompt'))
        ) {

            try {
                $model = core()->getConfigData('general.magic_ai.checkout_message.model');

                $response = MagicAI::setModel($model)
                    ->setTemperature(0)
                    ->setPrompt($this->getCheckoutPrompt($order))
                    ->ask();

                $order->checkout_message = $response;
            } catch (\Exception $e) {
            }
        }

        return view('shop::checkout.success', compact('order'));
    }

    /**
     * Builds the MagicAI prompt string for the post-checkout personalised message.
     *
     * Appends ordered product details (name, quantity, price), customer full name,
     * current locale, and store name to the admin-configured base prompt.
     *
     * @param \Webkul\Sales\Contracts\Order $order The just-placed order, with items relation loaded
     *
     * @return string Assembled prompt ready to be sent to the LLM
     */
    public function getCheckoutPrompt(OrderContract $order): string
    {
        $prompt = core()->getConfigData('general.magic_ai.checkout_message.prompt');

        $products = '';

        foreach ($order->items as $item) {
            $products .= "Name: $item->name\n";
            $products .= "Qty: $item->qty_ordered\n";
            $products .= 'Price: '.core()->formatPrice($item->total)."\n\n";
        }

        $prompt .= "\n\nProduct Details:\n $products";

        $prompt .= "Customer Details:\n $order->customer_full_name \n\n";

        $prompt .= "Current Locale:\n ".core()->getCurrentLocale()->name."\n\n";

        $prompt .= "Store Name:\n".core()->getCurrentChannel()->name;

        return $prompt;
    }
}
