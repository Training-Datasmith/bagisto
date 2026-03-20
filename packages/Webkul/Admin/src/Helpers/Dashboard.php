<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Admin\Helpers\Reporting\Customer;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Sale;
use Webkul\Admin\Helpers\Reporting\Visitor;
class Dashboard
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Sale $sale_reporting, protected Product $product_reporting, protected Customer $customer_reporting, protected Visitor $visitor_reporting)
    {
    }
    /**
     * Returns the overall statistics.
     */
    public function get_over_all_stats(): array
    {
        return ['total_customers' => $this->customer_reporting->get_total_customers_progress(), 'total_orders' => $this->sale_reporting->get_total_orders_progress(), 'total_sales' => $this->sale_reporting->get_total_sales_progress(), 'avg_sales' => $this->sale_reporting->get_average_sales_progress(), 'total_unpaid_invoices' => ['total' => $total = $this->sale_reporting->get_total_pending_invoices_amount(), 'formatted_total' => core()->format_base_price($total)]];
    }
    /**
     * Returns the today statistics.
     */
    public function get_today_stats(): array
    {
        $orders = $this->sale_reporting->get_today_orders();
        $orders = $orders->map(function ($order) {
            return ['id' => $order->id, 'increment_id' => $order->id, 'status' => $order->status, 'status_label' => $order->status_label, 'payment_method' => core()->get_config_data('sales.payment_methods.' . $order->payment->method . '.title'), 'base_grand_total' => $order->base_grand_total, 'formatted_base_grand_total' => core()->format_base_price($order->base_grand_total), 'channel_name' => $order->channel_name, 'customer_email' => $order->customer_email, 'customer_name' => $order->customer_full_name, 'items' => view('admin::sales.orders.items', compact('order'))->render(), 'billing_address' => $order?->billing_address->city . ($order?->billing_address->country ? ', ' . core()->country_name($order?->billing_address->country) : ''), 'created_at' => $order->created_at->format('d M Y, H:i:s')];
        });
        return ['total_sales' => $this->sale_reporting->get_today_sales_progress(), 'total_orders' => $this->sale_reporting->get_today_orders_progress(), 'total_customers' => $this->customer_reporting->get_today_customers_progress(), 'orders' => $orders];
    }
    /**
     * Returns the today statistics.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get_stock_threshold_products()
    {
        $products = $this->product_reporting->get_stock_threshold_products(5);
        $products = $products->map(function ($product) {
            return ['id' => $product->product_id, 'sku' => $product->product->sku, 'name' => $product->product->name, 'price' => $product->product->price, 'formatted_price' => core()->format_base_price($product->product->price), 'total_qty' => $product->total_qty, 'image' => $product->product->base_image_url];
        });
        return $products;
    }
    /**
     * Returns sales statistics.
     */
    public function get_sales_stats(): array
    {
        return ['total_orders' => $this->sale_reporting->get_total_orders_progress(), 'total_sales' => $this->sale_reporting->get_total_sales_progress(), 'over_time' => $this->sale_reporting->get_current_total_sales_over_time()];
    }
    /**
     * Returns visitors statistics.
     */
    public function get_visitor_stats(): array
    {
        return ['total' => $this->visitor_reporting->get_total_visitors_progress(), 'unique' => $this->visitor_reporting->get_total_unique_visitors_progress(), 'over_time' => $this->visitor_reporting->get_current_total_visitors_over_time()];
    }
    /**
     * Returns top selling products statistics.
     */
    public function get_top_selling_products(): Collection
    {
        return $this->product_reporting->get_top_selling_products_by_revenue(5);
    }
    /**
     * Returns top customers statistics.
     */
    public function get_top_customers(): Eloquent_Collection
    {
        $customers = $this->customer_reporting->get_customers_with_most_sales(5);
        $customers->map(function ($customer) {
            $customer->formatted_total = core()->format_base_price($customer->total);
        });
        return $customers;
    }
    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function get_start_date(): Carbon
    {
        return $this->sale_reporting->get_start_date();
    }
    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function get_end_date(): Carbon
    {
        return $this->sale_reporting->get_end_date();
    }
    /**
     * Returns date range
     */
    public function get_date_range(): string
    {
        return $this->get_start_date()->format('d M') . ' - ' . $this->get_end_date()->format('d M');
    }
}