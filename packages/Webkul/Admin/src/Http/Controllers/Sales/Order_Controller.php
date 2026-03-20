<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Resources\Json\Json_Resource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Sales\Order_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Address_Resource;
use Webkul\Admin\Http\Resources\Cart_Resource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\Cart_Repository;
use Webkul\Customer\Repositories\Customer_Group_Repository;
use Webkul\Sales\Repositories\Order_Comment_Repository;
use Webkul\Sales\Repositories\Order_Repository;
use Webkul\Sales\Transformers\Order_Resource;
class Order_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Order_Repository $order_repository, protected Order_Comment_Repository $order_comment_repository, protected Cart_Repository $cart_repository, protected Customer_Group_Repository $customer_group_repository)
    {
    }
    /**
     * Display a paginated listing of orders, or return a DataGrid JSON response for AJAX requests.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     *         View with channels and customer groups for filters, or processed DataGrid payload
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Order_Data_Grid::class)->process();
        }
        $channels = core()->get_all_channels();
        $groups = $this->customer_group_repository->find_where([['code', '<>', 'guest']]);
        return view('admin::sales.orders.index', compact('channels', 'groups'));
    }
    /**
     * Displays the admin order creation form for a given cart.
     *
     * Loads the cart, resolves the customer's saved addresses for pre-filling,
     * and passes a CartResource representation to the view.
     *
     * @param int $cart_id The ID of the cart to convert into an order
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *         Order creation view, or redirect to index if cart is not found
     */
    public function create(int $cart_id)
    {
        $cart = $this->cart_repository->find($cart_id);
        if (!$cart) {
            return redirect()->route('admin.sales.orders.index');
        }
        $addresses = Address_Resource::collection($cart->customer->addresses);
        $cart = new Cart_Resource($cart);
        return view('admin::sales.orders.create', compact('cart', 'addresses'));
    }
    /**
     * Validates the cart, collects totals, and persists a new order from the given cart.
     *
     * Only cash-on-delivery and money-transfer payment methods are supported for
     * admin-created orders. Fires `sales.order.created.before` / `.after` events.
     *
     * @param int $cart_id The cart ID to convert into a confirmed order
     *
     * @return \Illuminate\Http\JsonResponse JSON with redirect URL on success, or error message
     *
     * @throws \Exception If cart validation fails (minimum order amount, missing addresses, etc.)
     */
    public function store(int $cart_id)
    {
        $cart = $this->cart_repository->find_or_fail($cart_id);
        Cart::set_cart($cart);
        if (Cart::has_error()) {
            return response()->json(['message' => trans('admin::app.sales.orders.create.error')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        Cart::collect_totals();
        try {
            $this->validate_order();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->get_message()], Response::HTTP_BAD_REQUEST);
        }
        $cart = Cart::get_cart();
        if (!in_array($cart->payment->method, ['cashondelivery', 'moneytransfer'])) {
            return response()->json(['message' => trans('admin::app.sales.orders.create.payment-not-supported')], Response::HTTP_BAD_REQUEST);
        }
        $data = (new Order_Resource($cart))->jsonSerialize();
        $order = $this->order_repository->create($data);
        Cart::remove_cart($cart);
        session()->flash('order', trans('admin::app.sales.orders.create.order-placed-success'));
        return new Json_Resource(['redirect' => true, 'redirect_url' => route('admin.sales.orders.view', $order->id)]);
    }
    /**
     * Displays the admin detail view for a single order.
     *
     * @param int $id The order's primary key
     *
     * @return \Illuminate\View\View The order detail view with the order model
     */
    public function view(int $id): \Illuminate\View\View
    {
        $order = $this->order_repository->find_or_fail($id);
        return view('admin::sales.orders.view', compact('order'));
    }
    /**
     * Creates a new draft cart pre-populated with items from an existing order.
     *
     * Products that are no longer available or throw exceptions are silently skipped.
     * Redirects to the order creation form with the newly created cart.
     *
     * @param int $id The ID of the source order to reorder from
     *
     * @return \Illuminate\Http\RedirectResponse Redirect to admin.sales.orders.create with new cart ID
     */
    public function reorder(int $id)
    {
        $order = $this->order_repository->find_or_fail($id);
        $cart = Cart::create_cart(['customer' => $order->customer, 'is_active' => false]);
        Cart::set_cart($cart);
        foreach ($order->items as $item) {
            try {
                Cart::add_product($item->product, $item->additional);
            } catch (\Exception $e) {
                // do nothing
            }
        }
        return redirect()->route('admin.sales.orders.create', $cart->id);
    }
    /**
     * Cancels an order and flashes a success or error message to the session.
     *
     * Delegates actual cancellation logic to the OrderRepository. Cancellation is only
     * possible when the order is in a cancellable state (not already completed/cancelled).
     *
     * @param int $id The ID of the order to cancel
     *
     * @return \Illuminate\Http\RedirectResponse Redirect back to the order view
     */
    public function cancel(int $id)
    {
        $result = $this->order_repository->cancel($id);
        if ($result) {
            session()->flash('success', trans('admin::app.sales.orders.view.cancel-success'));
        } else {
            session()->flash('error', trans('admin::app.sales.orders.view.create-error'));
        }
        return redirect()->route('admin.sales.orders.view', $id);
    }
    /**
     * Saves a new comment on an order and optionally notifies the customer.
     *
     * Fires `sales.order.comment.create.before` and `.after` events for extensibility.
     *
     * @param int $id The order's primary key
     *
     * @return \Illuminate\Http\RedirectResponse Redirect back to the order view with success flash
     */
    public function comment(int $id): \Illuminate\Http\RedirectResponse
    {
        $validated_data = $this->validate(request(), ['comment' => 'required', 'customer_notified' => 'sometimes|sometimes']);
        $validated_data['order_id'] = $id;
        Event::dispatch('sales.order.comment.create.before');
        $comment = $this->order_comment_repository->create($validated_data);
        Event::dispatch('sales.order.comment.create.after', $comment);
        session()->flash('success', trans('admin::app.sales.orders.view.comment-success'));
        return redirect()->route('admin.sales.orders.view', $id);
    }
    /**
     * Searches orders by customer email, status, customer name, or increment ID.
     *
     * Returns a paginated JSON response suitable for autocomplete/search widgets.
     * The query parameter is URL-decoded before matching.
     *
     * @return \Illuminate\Http\JsonResponse Paginated order records with formatted dates and status labels
     *
     * @complexity O(n log n) for DB index scan + sort; performance degrades on large datasets
     *             without an index on customer_email and increment_id columns
     */
    public function search()
    {
        $orders = $this->order_repository->scope_query(function ($query) {
            return $query->where('customer_email', 'like', '%' . urldecode(request()->input('query')) . '%')->or_where('status', 'like', '%' . urldecode(request()->input('query')) . '%')->or_where(DB::raw('CONCAT(' . DB::get_table_prefix() . 'customer_first_name, " ", ' . DB::get_table_prefix() . 'customer_last_name)'), 'like', '%' . urldecode(request()->input('query')) . '%')->or_where('increment_id', request()->input('query'))->order_by('created_at', 'desc');
        })->paginate(10);
        foreach ($orders as $key => $order) {
            $orders[$key]['formatted_created_at'] = core()->format_date($order->created_at, 'd M Y');
            $orders[$key]['status_label'] = $order->status_label;
            $orders[$key]['customer_full_name'] = $order->customer_full_name;
        }
        return response()->json($orders);
    }
    /**
     * Validates the cart state before an admin-created order is committed.
     *
     * Checks minimum order amount, shipping address presence (for stockable items),
     * billing address presence, shipping method selection, and payment method presence.
     *
     * @return void
     *
     * @throws \Exception With a translated message when any validation condition fails
     */
    public function validate_order(): void
    {
        $cart = Cart::get_cart();
        if (!Cart::have_minimum_order_amount()) {
            throw new \Exception(trans('admin::app.sales.orders.create.minimum-order-error', ['amount' => core()->format_price(core()->get_config_data('sales.order_settings.minimum_order.minimum_order_amount') ?: 0)]));
        }
        if ($cart->have_stockable_items() && !$cart->shipping_address) {
            throw new \Exception(trans('admin::app.sales.orders.create.check-shipping-address'));
        }
        if (!$cart->billing_address) {
            throw new \Exception(trans('admin::app.sales.orders.create.check-billing-address'));
        }
        if ($cart->have_stockable_items() && !$cart->selected_shipping_rate) {
            throw new \Exception(trans('admin::app.sales.orders.create.specify-shipping-method'));
        }
        if (!$cart->payment) {
            throw new \Exception(trans('admin::app.sales.orders.create.specify-payment-method'));
        }
    }
}