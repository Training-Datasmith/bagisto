<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Repositories\Wishlist_Repository;
use Webkul\Marketing\Repositories\Search_Term_Repository;
use Webkul\Product\Repositories\Product_Inventory_Repository;
use Webkul\Product\Repositories\Product_Repository;
use Webkul\Product\Repositories\Product_Review_Repository;
use Webkul\Sales\Repositories\Order_Item_Repository;
class Product extends Abstract_Reporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(protected Product_Repository $product_repository, protected Product_Inventory_Repository $product_inventory_repository, protected Wishlist_Repository $wishlist_repository, protected Product_Review_Repository $review_repository, protected Order_Item_Repository $order_item_repository, protected Search_Term_Repository $search_term_repository)
    {
        parent::__construct();
    }
    /**
     * Retrieves total sold quantities and their progress.
     *
     * @return array
     */
    public function get_total_sold_quantities_progress()
    {
        return ['previous' => $previous = $this->get_total_sold_quantities($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_sold_quantities($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Returns previous sold quantities over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_total_sold_quantities_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_sold_quantities_over_time($this->last_start_date, $this->last_end_date, $period);
    }
    /**
     * Returns current sold quantities over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_total_sold_quantities_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_sold_quantities_over_time($this->start_date, $this->end_date, $period);
    }
    /**
     * Retrieves total sold quantities.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_sold_quantities($start_date, $end_date): int
    {
        return $this->order_item_repository->reset_model()->left_join('orders', 'order_items.order_id', '=', 'orders.id')->where_in('orders.channel_id', $this->channel_ids)->where_between('order_items.created_at', [$start_date, $end_date])->value(DB::raw('SUM(qty_invoiced - qty_refunded)')) ?? 0;
    }
    /**
     * Retrieves total products added to wishlist and their progress.
     *
     * @return array
     */
    public function get_total_products_added_to_wishlist_progress()
    {
        return ['previous' => $previous = $this->get_total_products_added_to_wishlist($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_products_added_to_wishlist($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Returns previous products added to wishlist over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_total_products_added_to_wishlist_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_products_added_to_wishlist_over_time($this->last_start_date, $this->last_end_date, $period);
    }
    /**
     * Returns current products added to wishlist over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_total_products_added_to_wishlist_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_products_added_to_wishlist_over_time($this->start_date, $this->end_date, $period);
    }
    /**
     * Retrieves total products added to wishlist.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_products_added_to_wishlist($start_date, $end_date): int
    {
        return $this->wishlist_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->count();
    }
    /**
     * Retrieves total reviews and their progress.
     */
    public function get_total_reviews_progress(): array
    {
        return ['previous' => $previous = $this->get_total_reviews($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_reviews($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total reviews by date
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_reviews($start_date, $end_date): int
    {
        return $this->review_repository->reset_model()->left_join('product_channels', 'product_reviews.product_id', '=', 'product_channels.product_id')->where('status', 'approved')->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->count();
    }
    /**
     * Gets stock threshold.
     *
     * @param  int  $limit
     */
    public function get_stock_threshold_products($limit = null): Eloquent_Collection
    {
        return $this->product_inventory_repository->reset_model()->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])->left_join('product_channels', 'product_inventories.product_id', '=', 'product_channels.product_id')->select('*', DB::raw('SUM(qty) as total_qty'))->where_in('channel_id', $this->channel_ids)->group_by('product_inventories.product_id')->order_by('total_qty', 'ASC')->limit($limit)->get();
    }
    /**
     * Gets top-selling products by revenue.
     *
     * @param  int  $limit
     */
    public function get_top_selling_products_by_revenue($limit = null): Collection
    {
        $items = $this->order_item_repository->reset_model()->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])->left_join('orders', 'order_items.order_id', '=', 'orders.id')->add_select('*', DB::raw('SUM(base_total_invoiced - base_amount_refunded) as revenue'))->where_null('parent_id')->where_in('channel_id', $this->channel_ids)->where_between('order_items.created_at', [$this->start_date, $this->end_date])->having(DB::raw('SUM(base_total_invoiced - base_amount_refunded)'), '>', 0)->group_by('product_id')->order_by('revenue', 'DESC')->limit($limit)->get();
        $items = $items->map(function ($item) {
            return ['id' => $item->product_id, 'name' => $item->name, 'price' => $item->product?->price, 'formatted_price' => core()->format_base_price($item->price), 'revenue' => $item->revenue, 'formatted_revenue' => core()->format_base_price($item->revenue), 'images' => $item->product?->images];
        });
        return $items;
    }
    /**
     * Gets top-selling products by quantity.
     *
     * @param  int  $limit
     */
    public function get_top_selling_products_by_quantity($limit = null): Collection
    {
        $items = $this->order_item_repository->reset_model()->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])->left_join('orders', 'order_items.order_id', '=', 'orders.id')->add_select('*', DB::raw('SUM(qty_invoiced - qty_refunded) as total_qty_ordered'))->where_null('parent_id')->where_in('channel_id', $this->channel_ids)->where_between('order_items.created_at', [$this->start_date, $this->end_date])->having(DB::raw('SUM(qty_invoiced - qty_refunded)'), '>', 0)->group_by('product_id')->order_by('total_qty_ordered', 'DESC')->limit($limit)->get();
        $items = $items->map(function ($item) {
            return ['id' => $item->product_id, 'name' => $item->name, 'price' => $item->product?->price, 'formatted_price' => core()->format_base_price($item->price), 'total_qty_ordered' => $item->total_qty_ordered, 'images' => $item->product?->images];
        });
        return $items;
    }
    /**
     * Gets products with most orders.
     *
     * @param  int  $limit
     */
    public function get_products_with_most_reviews($limit = null): Eloquent_Collection
    {
        $table_prefix = DB::get_table_prefix();
        $products = $this->review_repository->reset_model()->left_join('product_channels', 'product_reviews.product_id', '=', 'product_channels.product_id')->add_select('product_reviews.product_id', DB::raw('COUNT(*) as reviews'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$this->start_date, $this->end_date])->where('status', 'approved')->group_by('product_reviews.product_id')->order_by_desc('reviews')->limit($limit)->get();
        $products->map(function ($product) {
            $product->product_name = $product->product->name;
        });
        return $products;
    }
    /**
     * Gets last search terms
     *
     * @param  int  $limit
     */
    public function get_last_search_terms($limit = null): Eloquent_Collection
    {
        return $this->search_term_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('updated_at', [$this->start_date, $this->end_date])->order_by_desc('updated_at')->limit($limit)->get();
    }
    /**
     * Gets top search terms
     *
     * @param  int  $limit
     */
    public function get_top_search_terms($limit = null): Eloquent_Collection
    {
        return $this->search_term_repository->reset_model()->where_in('channel_id', $this->channel_ids)->order_by_desc('uses')->limit($limit)->get();
    }
    /**
     * Returns sold quantities over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     */
    public function get_total_sold_quantities_over_time($start_date, $end_date, $period = 'auto'): array
    {
        $table_prefix = DB::get_table_prefix();
        $config = $this->get_time_interval($start_date, $end_date, $period);
        $group_column = str_replace('created_at', "{$table_prefix}order_items.created_at", $config['group_column']);
        $results = $this->order_item_repository->reset_model()->left_join('orders', 'order_items.order_id', '=', 'orders.id')->select(DB::raw("{$group_column} AS date"), DB::raw('COUNT(*) AS total'))->where_in('channel_id', $this->channel_ids)->where_between('order_items.created_at', [$start_date, $end_date])->group_by('date')->get();
        $stats = [];
        foreach ($config['intervals'] as $interval) {
            $total = $results->where('date', $interval['filter'])->first();
            $stats[] = ['label' => $interval['start'], 'total' => $total?->total ?? 0];
        }
        return $stats;
    }
    /**
     * Returns products added to wishlist over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     */
    public function get_total_products_added_to_wishlist_over_time($start_date, $end_date, $period = 'auto'): array
    {
        $config = $this->get_time_interval($start_date, $end_date, $period);
        $group_column = $config['group_column'];
        $results = $this->wishlist_repository->reset_model()->select(DB::raw("{$group_column} AS date"), DB::raw('COUNT(*) AS total'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->group_by('date')->get();
        $stats = [];
        foreach ($config['intervals'] as $interval) {
            $total = $results->where('date', $interval['filter'])->first();
            $stats[] = ['label' => $interval['start'], 'total' => $total?->total ?? 0];
        }
        return $stats;
    }
}