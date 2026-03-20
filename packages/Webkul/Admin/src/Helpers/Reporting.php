<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Webkul\Admin\Helpers\Reporting\Cart;
use Webkul\Admin\Helpers\Reporting\Customer;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Sale;
use Webkul\Admin\Helpers\Reporting\Visitor;
use Webkul\Product\Models\Product as ProductModel;
class Reporting
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Cart $cart_reporting, protected Sale $sale_reporting, protected Product $product_reporting, protected Customer $customer_reporting, protected Visitor $visitor_reporting)
    {
    }
    /**
     * Returns the sales statistics.
     *
     * @param  string  $type
     */
    public function get_total_sales_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = collect($this->sale_reporting->get_current_total_sales_over_time(request()->query('period') ?? 'day'));
            $records = $records->map(function ($record) {
                $record['formatted_total'] = core()->format_base_price($record['total']);
                return $record;
            });
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.sales.index.interval')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.orders')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.sales.index.total')]], 'records' => $records];
        }
        return ['sales' => $this->sale_reporting->get_total_sales_progress(), 'over_time' => ['previous' => $this->sale_reporting->get_previous_total_sales_over_time(), 'current' => $this->sale_reporting->get_current_total_sales_over_time()]];
    }
    /**
     * Returns the sales statistics.
     *
     * @param  string  $type
     */
    public function get_average_sales_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = collect($this->sale_reporting->get_current_average_sales_over_time(request()->query('period') ?? 'day'));
            $records = $records->map(function ($record) {
                $record['formatted_total'] = core()->format_base_price($record['total']);
                return $record;
            });
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.sales.index.interval')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.orders')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.sales.index.total')]], 'records' => $records];
        }
        return ['sales' => $this->sale_reporting->get_average_sales_progress(), 'over_time' => ['previous' => $this->sale_reporting->get_previous_average_sales_over_time(), 'current' => $this->sale_reporting->get_current_average_sales_over_time()]];
    }
    /**
     * Returns the total orders statistics.
     *
     * @param  string  $type
     */
    public function get_total_orders_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.sales.index.interval')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.orders')]], 'records' => $this->sale_reporting->get_current_total_orders_over_time(request()->query('period') ?? 'day')];
        }
        return ['orders' => $this->sale_reporting->get_total_orders_progress(), 'over_time' => ['previous' => $this->sale_reporting->get_previous_total_orders_over_time(), 'current' => $this->sale_reporting->get_current_total_orders_over_time()]];
    }
    /**
     * Returns the purchase funnel statistics.
     */
    public function get_purchase_funnel_stats(): array
    {
        $start_date = $this->visitor_reporting->get_start_date();
        $end_date = $this->visitor_reporting->get_end_date();
        return ['visitors' => ['total' => $total_visitors = $this->visitor_reporting->get_total_unique_visitors($start_date, $end_date), 'progress' => $total_visitors ? 100 : 0], 'product_visitors' => ['total' => $total_product_visitors = $this->visitor_reporting->get_total_unique_visitors($start_date, $end_date, Product_Model::class), 'progress' => round($total_visitors > 0 ? $total_product_visitors * 100 / $total_visitors : 0, 1)], 'carts' => ['total' => $total_carts = $this->cart_reporting->get_total_unique_carts_users($start_date, $end_date), 'progress' => round(min($total_visitors > 0 ? $total_carts * 100 / $total_visitors : 0, 100), 1)], 'orders' => ['total' => $total_orders = $this->sale_reporting->get_total_unique_orders_users($start_date, $end_date), 'progress' => round(min($total_visitors > 0 ? $total_orders * 100 / $total_visitors : 0, 100), 1)]];
    }
    /**
     * Returns the abandoned carts statistics.
     *
     * @param  string  $type
     */
    public function get_abandoned_carts_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = $this->cart_reporting->get_abandoned_cart_products();
            return ['columns' => [['key' => 'id', 'label' => trans('admin::app.reporting.sales.index.id')], ['key' => 'name', 'label' => trans('admin::app.reporting.sales.index.name')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.count')]], 'records' => $records];
        }
        $total_abandoned_products = $this->cart_reporting->get_total_abandoned_cart_products();
        $products = $this->cart_reporting->get_abandoned_cart_products(5);
        $products->map(function ($product) use ($total_abandoned_products) {
            if (!$total_abandoned_products) {
                $product->progress = 0;
            } else {
                $product->progress = $product->count * 100 / $total_abandoned_products;
            }
            return $product;
        });
        return ['sales' => $this->cart_reporting->get_total_abandoned_sales_progress(), 'carts' => $this->cart_reporting->get_total_abandoned_carts_progress(), 'rate' => $this->cart_reporting->get_total_abandoned_cart_rate_progress(), 'products' => $products];
    }
    /**
     * Returns the sales statistics.
     *
     * @param  string  $type
     */
    public function get_refunds_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = collect($this->sale_reporting->get_current_refunds_over_time(request()->query('period') ?? 'day'));
            $records = $records->map(function ($record) {
                $record['formatted_total'] = core()->format_base_price($record['total']);
                return $record;
            });
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.sales.index.interval')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.orders')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.sales.index.total')]], 'records' => $records];
        }
        return ['refunds' => $this->sale_reporting->get_refunds_progress(), 'over_time' => ['previous' => $this->sale_reporting->get_previous_refunds_over_time(), 'current' => $this->sale_reporting->get_current_refunds_over_time()]];
    }
    /**
     * Returns the tax collected statistics.
     *
     * @param  string  $type
     */
    public function get_tax_collected_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = collect($this->sale_reporting->get_current_tax_collected_over_time(request()->query('period') ?? 'day'));
            $records = $records->map(function ($record) {
                $record['formatted_total'] = core()->format_base_price($record['total']);
                return $record;
            });
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.sales.index.interval')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.orders')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.sales.index.total')]], 'records' => $records];
        }
        $tax_collected = $this->sale_reporting->get_tax_collected_progress();
        $tax_categories = $this->sale_reporting->get_top_tax_categories(5);
        $tax_categories->map(function ($tax_category) use ($tax_collected) {
            if (!$tax_collected['current']) {
                $tax_category->progress = 0;
            } else {
                $tax_category->progress = $tax_category->total * 100 / $tax_collected['current'];
            }
            $tax_category->formatted_total = core()->format_base_price($tax_category->total);
            return $tax_category;
        });
        return ['tax_collected' => $tax_collected, 'top_categories' => $tax_categories, 'over_time' => ['previous' => $this->sale_reporting->get_previous_tax_collected_over_time(), 'current' => $this->sale_reporting->get_current_tax_collected_over_time()]];
    }
    /**
     * Returns the shipping collected statistics.
     *
     * @param  string  $type
     */
    public function get_shipping_collected_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = collect($this->sale_reporting->get_current_shipping_collected_over_time(request()->query('period') ?? 'day'));
            $records = $records->map(function ($record) {
                $record['formatted_total'] = core()->format_base_price($record['total']);
                return $record;
            });
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.sales.index.interval')], ['key' => 'count', 'label' => trans('admin::app.reporting.sales.index.orders')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.sales.index.total')]], 'records' => $records];
        }
        $shipping_collected = $this->sale_reporting->get_shipping_collected_progress();
        $shipping_methods = $this->sale_reporting->get_top_shipping_methods(5);
        $shipping_methods->map(function ($shipping_method) use ($shipping_collected) {
            if (!$shipping_collected['current']) {
                $shipping_method->progress = 0;
            } else {
                $shipping_method->progress = $shipping_method->total * 100 / $shipping_collected['current'];
            }
            $shipping_method->formatted_total = core()->format_base_price($shipping_method->total);
            $shipping_method->title = current(explode(' - ', $shipping_method->title));
            return $shipping_method;
        });
        return ['shipping_collected' => $shipping_collected, 'top_methods' => $shipping_methods, 'over_time' => ['previous' => $this->sale_reporting->get_previous_shipping_collected_over_time(), 'current' => $this->sale_reporting->get_current_shipping_collected_over_time()]];
    }
    /**
     * Returns the shipping collected statistics.
     *
     * @param  string  $type
     */
    public function get_top_payment_methods($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = collect($this->sale_reporting->get_top_payment_methods());
            $records = $records->map(function ($payment_method) {
                $payment_method->formatted_total = core()->format_base_price($payment_method->base_total);
                $payment_method->title = $payment_method->title ?? core()->get_config_data('sales.payment_methods.' . $payment_method->method . '.title');
                return $payment_method;
            });
            return ['columns' => [['key' => 'title', 'label' => trans('admin::app.reporting.sales.index.payment-method')], ['key' => 'total', 'label' => trans('admin::app.reporting.sales.index.orders')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.sales.index.total')]], 'records' => $records];
        }
        $total_orders = $this->sale_reporting->get_total_orders_progress();
        $payment_methods = $this->sale_reporting->get_top_payment_methods(5);
        $payment_methods->map(function ($payment_method) use ($total_orders) {
            if (!$total_orders['current']) {
                $payment_method->progress = 0;
            } else {
                $payment_method->progress = $payment_method->total * 100 / $total_orders['current'];
            }
            $payment_method->formatted_total = core()->format_base_price($payment_method->base_total);
            $payment_method->title = $payment_method->title ?? core()->get_config_data('sales.payment_methods.' . $payment_method->method . '.title');
        });
        return $payment_methods;
    }
    /**
     * Returns the total customers statistics.
     *
     * @param  string  $type
     */
    public function get_total_customers_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.customers.index.interval')], ['key' => 'total', 'label' => trans('admin::app.reporting.customers.index.customers')]], 'records' => $this->customer_reporting->get_current_total_customers_over_time(request()->query('period') ?? 'day')];
        }
        return ['customers' => $this->customer_reporting->get_total_customers_progress(), 'over_time' => ['previous' => $this->customer_reporting->get_previous_total_customers_over_time(), 'current' => $this->customer_reporting->get_current_total_customers_over_time()]];
    }
    /**
     * Returns the total customers statistics.
     */
    public function get_customers_traffic_stats(): array
    {
        return ['total' => $this->visitor_reporting->get_total_visitors_progress(), 'unique' => $this->visitor_reporting->get_total_unique_visitors_progress(), 'over_time' => ['previous' => $this->visitor_reporting->get_previous_total_visitors_over_week(), 'current' => $this->visitor_reporting->get_current_total_visitors_over_week()]];
    }
    /**
     * Returns the customers with most sales
     *
     * @param  string  $type
     */
    public function get_customers_with_most_sales($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = collect($this->customer_reporting->get_customers_with_most_sales());
            $records = $records->map(function ($record) {
                $record['formatted_total'] = core()->format_base_price($record['total']);
                return $record;
            });
            return ['columns' => [['key' => 'full_name', 'label' => trans('admin::app.reporting.customers.index.name')], ['key' => 'email', 'label' => trans('admin::app.reporting.customers.index.email')], ['key' => 'formatted_total', 'label' => trans('admin::app.reporting.customers.index.total')]], 'records' => $records];
        }
        $total_sales = $this->sale_reporting->get_total_sales_progress();
        $customers = $this->customer_reporting->get_customers_with_most_sales(5);
        $customers->map(function ($customer) use ($total_sales) {
            if (!$total_sales['current']) {
                $customer->progress = 0;
            } else {
                $customer->progress = $customer->total * 100 / $total_sales['current'];
            }
            $customer->formatted_total = core()->format_base_price($customer->total);
        });
        return $customers;
    }
    /**
     * Returns the customers with most orders
     *
     * @param  string  $type
     */
    public function get_customers_with_most_orders($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = $this->customer_reporting->get_customers_with_most_orders();
            return ['columns' => [['key' => 'full_name', 'label' => trans('admin::app.reporting.customers.index.name')], ['key' => 'email', 'label' => trans('admin::app.reporting.customers.index.email')], ['key' => 'orders', 'label' => trans('admin::app.reporting.customers.index.orders')]], 'records' => $records];
        }
        $total_orders = $this->sale_reporting->get_total_orders_progress();
        $customers = $this->customer_reporting->get_customers_with_most_orders(5);
        $customers->map(function ($customer) use ($total_orders) {
            if (!$total_orders['current']) {
                $customer->progress = 0;
            } else {
                $customer->progress = $customer->orders * 100 / $total_orders['current'];
            }
        });
        return $customers;
    }
    /**
     * Returns the customers with most reviews
     *
     * @param  string  $type
     */
    public function get_customers_with_most_reviews($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = $this->customer_reporting->get_customers_with_most_reviews();
            return ['columns' => [['key' => 'full_name', 'label' => trans('admin::app.reporting.customers.index.name')], ['key' => 'email', 'label' => trans('admin::app.reporting.customers.index.email')], ['key' => 'reviews', 'label' => trans('admin::app.reporting.customers.index.reviews')]], 'records' => $records];
        }
        $total_reviews = $this->customer_reporting->get_total_reviews_progress();
        $customers = $this->customer_reporting->get_customers_with_most_reviews(5);
        $customers->map(function ($customer) use ($total_reviews) {
            if (!$total_reviews['current']) {
                $customer->progress = 0;
            } else {
                $customer->progress = $customer->reviews * 100 / $total_reviews['current'];
            }
        });
        return $customers;
    }
    /**
     * Returns the top customers
     *
     * @param  string  $type
     */
    public function get_top_customer_groups($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = $this->customer_reporting->get_groups_with_most_customers();
            return ['columns' => [['key' => 'group_name', 'label' => trans('admin::app.reporting.customers.index.name')], ['key' => 'total', 'label' => trans('admin::app.reporting.customers.index.customers')]], 'records' => $records];
        }
        $total_customers = $this->customer_reporting->get_total_customers_progress();
        $groups = $this->customer_reporting->get_groups_with_most_customers(5);
        $groups->map(function ($group) use ($total_customers) {
            if (!$total_customers['current']) {
                $group->progress = 0;
            } else {
                $group->progress = $group->total * 100 / $total_customers['current'];
            }
        });
        return $groups;
    }
    /**
     * Returns the total sold quantities statistics.
     *
     * @param  string  $type
     */
    public function get_total_sold_quantities_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.products.index.interval')], ['key' => 'total', 'label' => trans('admin::app.reporting.products.index.quantities')]], 'records' => $this->product_reporting->get_current_total_sold_quantities_over_time(request()->query('period') ?? 'day')];
        }
        return ['quantities' => $this->product_reporting->get_total_sold_quantities_progress(), 'over_time' => ['previous' => $this->product_reporting->get_previous_total_sold_quantities_over_time(), 'current' => $this->product_reporting->get_current_total_sold_quantities_over_time()]];
    }
    /**
     * Returns the total products added to wishlist statistics.
     *
     * @param  string  $type
     */
    public function get_total_products_added_to_wishlist_stats($type = 'graph'): array
    {
        if ($type == 'table') {
            return ['columns' => [['key' => 'label', 'label' => trans('admin::app.reporting.products.index.interval')], ['key' => 'total', 'label' => trans('admin::app.reporting.products.index.total')]], 'records' => $this->product_reporting->get_current_total_products_added_to_wishlist_over_time(request()->query('period') ?? 'day')];
        }
        return ['wishlist' => $this->product_reporting->get_total_products_added_to_wishlist_progress(), 'over_time' => ['previous' => $this->product_reporting->get_previous_total_products_added_to_wishlist_over_time(), 'current' => $this->product_reporting->get_current_total_products_added_to_wishlist_over_time()]];
    }
    /**
     * Returns top selling products by revenue statistics.
     *
     * @param  string  $type
     */
    public function get_top_selling_products_by_revenue($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = collect($this->product_reporting->get_top_selling_products_by_revenue());
            return ['columns' => [['key' => 'id', 'label' => trans('admin::app.reporting.products.index.id')], ['key' => 'name', 'label' => trans('admin::app.reporting.products.index.name')], ['key' => 'formatted_price', 'label' => trans('admin::app.reporting.products.index.price')], ['key' => 'formatted_revenue', 'label' => trans('admin::app.reporting.products.index.revenue')]], 'records' => $records];
        }
        $total_sales = $this->sale_reporting->get_sub_total_sales_progress();
        $products = $this->product_reporting->get_top_selling_products_by_revenue(5);
        $products = $products->map(function ($product) use ($total_sales) {
            if (!$total_sales['current']) {
                $product['progress'] = 0;
            } else {
                $product['progress'] = $product['revenue'] * 100 / $total_sales['current'];
            }
            $product['formatted_revenue'] = core()->format_base_price($product['revenue']);
            return $product;
        });
        return $products->to_array();
    }
    /**
     * Returns top selling products by quantity statistics.
     *
     * @param  string  $type
     */
    public function get_top_selling_products_by_quantity($type = 'graph'): array
    {
        if ($type == 'table') {
            $records = $this->product_reporting->get_top_selling_products_by_quantity();
            return ['columns' => [['key' => 'id', 'label' => trans('admin::app.reporting.products.index.id')], ['key' => 'name', 'label' => trans('admin::app.reporting.products.index.name')], ['key' => 'total_qty_ordered', 'label' => trans('admin::app.reporting.products.index.quantities')]], 'records' => $records];
        }
        $total_sold_quantities = $this->product_reporting->get_total_sold_quantities_progress();
        $products = $this->product_reporting->get_top_selling_products_by_quantity(5);
        $products = $products->map(function ($product) use ($total_sold_quantities) {
            if (!$total_sold_quantities['current']) {
                $product['progress'] = 0;
            } else {
                $product['progress'] = $product['total_qty_ordered'] * 100 / $total_sold_quantities['current'];
            }
            return $product;
        });
        return $products->to_array();
    }
    /**
     * Returns the products with most reviews
     *
     * @param  string  $type
     */
    public function get_products_with_most_reviews($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = $this->product_reporting->get_products_with_most_reviews();
            return ['columns' => [['key' => 'product_id', 'label' => trans('admin::app.reporting.products.index.id')], ['key' => 'product_name', 'label' => trans('admin::app.reporting.products.index.name')], ['key' => 'reviews', 'label' => trans('admin::app.reporting.products.index.reviews')]], 'records' => $records];
        }
        $total_reviews = $this->product_reporting->get_total_reviews_progress();
        $products = $this->product_reporting->get_products_with_most_reviews(5);
        $products->map(function ($product) use ($total_reviews) {
            if (!$total_reviews['current']) {
                $product->progress = 0;
            } else {
                $product->progress = $product->reviews * 100 / $total_reviews['current'];
            }
        });
        return $products;
    }
    /**
     * Returns the products with most visits
     *
     * @param  string  $type
     */
    public function get_products_with_most_visits($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = $this->visitor_reporting->get_visitable_with_most_visits(Product_Model::class);
            return ['columns' => [['key' => 'visitable_id', 'label' => trans('admin::app.reporting.products.index.id')], ['key' => 'name', 'label' => trans('admin::app.reporting.products.index.name')], ['key' => 'visits', 'label' => trans('admin::app.reporting.products.index.visits')]], 'records' => $records];
        }
        $total_visits = $this->visitor_reporting->get_total_visitors_progress(Product_Model::class);
        $products = $this->visitor_reporting->get_visitable_with_most_visits(Product_Model::class, 5);
        $products->map(function ($product) use ($total_visits) {
            if (!$total_visits['current']) {
                $product->progress = 0;
            } else {
                $product->progress = $product->visits * 100 / $total_visits['current'];
            }
        });
        return $products;
    }
    /**
     * Returns the last search terms
     *
     * @param  string  $type
     */
    public function get_last_search_terms($type = 'graph'): Eloquent_Collection|array
    {
        if ($type == 'table') {
            $records = $this->product_reporting->get_last_search_terms();
            return ['columns' => [['key' => 'id', 'label' => trans('admin::app.reporting.products.index.id')], ['key' => 'term', 'label' => trans('admin::app.reporting.products.index.search-term')], ['key' => 'results', 'label' => trans('admin::app.reporting.products.index.results')], ['key' => 'uses', 'label' => trans('admin::app.reporting.products.index.uses')], ['key' => 'channel_id', 'label' => trans('admin::app.reporting.products.index.channel')], ['key' => 'locale', 'label' => trans('admin::app.reporting.products.index.locale')]], 'records' => $records];
        }
        return $this->product_reporting->get_last_search_terms(5);
    }
    /**
     * Returns the top search terms
     *
     * @param  string  $type
     */
    public function get_top_search_terms($type = 'graph'): Eloquent_Collection|array
    {
        return $this->product_reporting->get_top_search_terms(5);
    }
    /**
     * Returns date range
     */
    public function get_date_range(): array
    {
        return ['previous' => $this->sale_reporting->get_last_start_date()->format('d M Y') . ' - ' . $this->sale_reporting->get_last_end_date()->format('d M Y'), 'current' => $this->sale_reporting->get_start_date()->format('d M Y') . ' - ' . $this->sale_reporting->get_end_date()->format('d M Y')];
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
}