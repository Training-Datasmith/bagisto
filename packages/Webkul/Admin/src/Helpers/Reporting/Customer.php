<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Repositories\Customer_Repository;
use Webkul\Product\Repositories\Product_Review_Repository;
use Webkul\Sales\Repositories\Order_Repository;
class Customer extends Abstract_Reporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(protected Customer_Repository $customer_repository, protected Order_Repository $order_repository, protected Product_Review_Repository $review_repository)
    {
        parent::__construct();
    }
    /**
     * Retrieves total customers and their progress.
     */
    public function get_total_customers_progress(): array
    {
        return ['previous' => $previous = $this->get_total_customers($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_customers($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Returns previous customers over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_total_customers_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_customers_over_time($this->last_start_date, $this->last_end_date, $period);
    }
    /**
     * Returns current customers over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_total_customers_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_customers_over_time($this->start_date, $this->end_date, $period);
    }
    /**
     * Retrieves today customers and their progress.
     */
    public function get_today_customers_progress(): array
    {
        return ['previous' => $previous = $this->get_total_customers(now()->sub_day()->start_of_day(), now()->sub_day()->end_of_day()), 'current' => $current = $this->get_total_customers(now()->today(), now()->end_of_day()), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total customers by date
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_customers($start_date, $end_date): int
    {
        return $this->customer_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->count();
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
     * Gets customer with most sales.
     *
     * @param  int  $limit
     */
    public function get_customers_with_most_sales($limit = null): Collection
    {
        $table_prefix = DB::get_table_prefix();
        return $this->order_repository->reset_model()->add_select('orders.customer_id as id', 'orders.customer_email as email', DB::raw('CONCAT(' . $table_prefix . 'orders.customer_first_name, " ", ' . $table_prefix . 'orders.customer_last_name) as full_name'), DB::raw('SUM(base_grand_total_invoiced - base_grand_total_refunded) as total'), DB::raw('COUNT(*) as orders'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$this->start_date, $this->end_date])->group_by(DB::raw('CONCAT(customer_email, "-", customer_id)'))->order_by_desc('total')->limit($limit)->get();
    }
    /**
     * Gets customer with most orders.
     *
     * @param  int  $limit
     */
    public function get_customers_with_most_orders($limit = null): Collection
    {
        $table_prefix = DB::get_table_prefix();
        return $this->order_repository->reset_model()->add_select('orders.customer_id as id', 'orders.customer_email as email', DB::raw('CONCAT(' . $table_prefix . 'orders.customer_first_name, " ", ' . $table_prefix . 'orders.customer_last_name) as full_name'), DB::raw('COUNT(*) as orders'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$this->start_date, $this->end_date])->group_by(DB::raw('CONCAT(customer_email, "-", customer_id)'))->order_by_desc('orders')->limit($limit)->get();
    }
    /**
     * Gets customer with most orders.
     *
     * @param  int  $limit
     */
    public function get_customers_with_most_reviews($limit = null): Collection
    {
        $table_prefix = DB::get_table_prefix();
        return $this->review_repository->reset_model()->left_join('customers', 'product_reviews.customer_id', '=', 'customers.id')->left_join('product_channels', 'product_reviews.product_id', '=', 'product_channels.product_id')->add_select('customers.id as id', 'customers.email as email', DB::raw('CONCAT(' . $table_prefix . 'customers.first_name, " ", ' . $table_prefix . 'customers.last_name) as full_name'), DB::raw('COUNT(*) as reviews'))->where_in('customers.channel_id', $this->channel_ids)->where_in('product_channels.channel_id', $this->channel_ids)->where_between('product_reviews.created_at', [$this->start_date, $this->end_date])->where('product_reviews.status', 'approved')->where_not_null('customer_id')->group_by(DB::raw('CONCAT(email, "-", ' . $table_prefix . 'customers.id)'))->order_by_desc('reviews')->limit($limit)->get();
    }
    /**
     * Gets customer with most sales.
     *
     * @param  int  $limit
     */
    public function get_groups_with_most_customers($limit = null): Collection
    {
        return $this->customer_repository->reset_model()->left_join('customer_groups', 'customers.customer_group_id', '=', 'customer_groups.id')->select('customers.id as id', 'customer_groups.name as group_name')->add_select(DB::raw('COUNT(*) as total'))->where_in('channel_id', $this->channel_ids)->where_between('customers.created_at', [$this->start_date, $this->end_date])->group_by('customer_group_id')->order_by_desc('total')->limit($limit)->get();
    }
    /**
     * Returns over time stats.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     */
    public function get_total_customers_over_time($start_date, $end_date, $period = 'auto'): array
    {
        $config = $this->get_time_interval($start_date, $end_date, $period);
        $group_column = $config['group_column'];
        $results = $this->customer_repository->reset_model()->select(DB::raw("{$group_column} AS date"), DB::raw('COUNT(*) AS total'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->group_by('date')->get();
        $stats = [];
        foreach ($config['intervals'] as $interval) {
            $total = $results->where('date', $interval['filter'])->first();
            $stats[] = ['label' => $interval['start'], 'total' => $total?->total ?? 0];
        }
        return $stats;
    }
}