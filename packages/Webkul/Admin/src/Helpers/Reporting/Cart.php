<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Repositories\Cart_Item_Repository;
use Webkul\Checkout\Repositories\Cart_Repository;
class Cart extends Abstract_Reporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(protected Cart_Repository $cart_repository, protected Cart_Item_Repository $cart_item_repository)
    {
        parent::__construct();
    }
    /**
     * Retrieves total carts and their progress.
     *
     * @return array
     */
    public function get_total_carts_progress()
    {
        return ['previous' => $previous = $this->get_total_carts($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_carts($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves today carts and their progress.
     */
    public function get_today_carts_progress(): array
    {
        return ['previous' => $previous = $this->get_total_carts(now()->sub_day()->start_of_day(), now()->sub_day()->end_of_day()), 'current' => $current = $this->get_total_carts(now()->today(), now()->end_of_day()), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total abandoned sales and their progress.
     *
     * @return array
     */
    public function get_total_abandoned_sales_progress()
    {
        return ['previous' => $previous = $this->get_total_abandoned_sales($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_abandoned_sales($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total abandoned carts and their progress.
     *
     * @return array
     */
    public function get_total_abandoned_carts_progress()
    {
        return ['previous' => $previous = $this->get_total_abandoned_carts($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_abandoned_carts($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total abandoned carts rate and their progress.
     *
     * @return array
     */
    public function get_total_abandoned_cart_rate_progress()
    {
        return ['previous' => $previous = $this->get_total_abandoned_cart_rate($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_abandoned_cart_rate($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total carts
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_carts($start_date, $end_date): int
    {
        return $this->cart_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->count();
    }
    /**
     * Retrieves total abandoned carts
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_abandoned_carts($start_date, $end_date): int
    {
        return $this->cart_repository->reset_model()->where('is_active', 1)->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date->sub_days(2)])->count();
    }
    /**
     * Retrieves total abandoned cart rate
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_abandoned_cart_rate($start_date, $end_date): float
    {
        $total_carts = $this->get_total_carts($start_date, $end_date);
        if (!$total_carts) {
            return 0;
        }
        return $this->get_total_abandoned_carts($start_date, $end_date) * 100 / $total_carts;
    }
    /**
     * Retrieves total abandoned sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_abandoned_sales($start_date, $end_date): int
    {
        return $this->cart_repository->reset_model()->where('is_active', 1)->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date->sub_days(2)])->sum('base_grand_total');
    }
    /**
     * Retrieves abandoned cart products
     *
     * @param  int  $limit
     */
    public function get_abandoned_cart_products($limit = null): Collection
    {
        return $this->cart_item_repository->reset_model()->left_join('cart', 'cart_items.cart_id', '=', 'cart.id')->select('product_id as id', 'name')->add_select(DB::raw('COUNT(*) as count'))->where('is_active', 1)->where_in('cart.channel_id', $this->channel_ids)->where_between('cart.created_at', [$this->start_date, $this->end_date->sub_days(2)])->group_by('product_id')->limit($limit)->order_by_desc('count')->get();
    }
    /**
     * Retrieves total abandoned cart products
     */
    public function get_total_abandoned_cart_products(): int
    {
        return $this->cart_item_repository->reset_model()->distinct('product_id')->left_join('cart', 'cart_items.cart_id', '=', 'cart.id')->where('is_active', 1)->where_in('cart.channel_id', $this->channel_ids)->where_between('cart.created_at', [$this->start_date, $this->end_date->sub_days(2)])->count();
    }
    /**
     * Retrieves total unique cart users
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_total_unique_carts_users($start_date, $end_date): int
    {
        return $this->cart_repository->reset_model()->group_by(DB::raw('CONCAT(customer_email, "-", customer_id)'))->where_in('cart.channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->get()->count();
    }
}