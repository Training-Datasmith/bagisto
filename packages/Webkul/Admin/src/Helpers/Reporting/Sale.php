<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Repositories\Invoice_Repository;
use Webkul\Sales\Repositories\Order_Item_Repository;
use Webkul\Sales\Repositories\Order_Repository;
use Webkul\Sales\Repositories\Refund_Repository;
class Sale extends Abstract_Reporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(protected Order_Repository $order_repository, protected Order_Item_Repository $order_item_repository, protected Invoice_Repository $invoice_repository, protected Refund_Repository $refund_repository)
    {
        parent::__construct();
    }
    /**
     * Retrieves total orders and their progress.
     *
     * @return array
     */
    public function get_total_orders_progress()
    {
        return ['previous' => $previous = $this->get_total_orders($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_orders($this->start_date, $this->end_date), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Returns previous orders over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_total_orders_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_orders_over_time($this->last_start_date, $this->last_end_date, $period, $include_empty);
    }
    /**
     * Returns current orders over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_total_orders_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_orders_over_time($this->start_date, $this->end_date, $period, $include_empty);
    }
    /**
     * Retrieves total orders
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_orders($start_date, $end_date): int
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->count();
    }
    /**
     * Returns orders over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_total_orders_over_time($start_date, $end_date, $period, $include_empty): array
    {
        return $this->get_over_time_stats($start_date, $end_date, 'COUNT(*)', $period);
    }
    /**
     * Retrieves today orders and their progress.
     */
    public function get_today_orders_progress(): array
    {
        return ['previous' => $previous = $this->get_total_orders(now()->sub_day()->start_of_day(), now()->sub_day()->end_of_day()), 'current' => $current = $this->get_total_orders(now()->today(), now()->end_of_day()), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves orders
     *
     * @return array
     */
    public function get_today_orders()
    {
        return $this->order_repository->reset_model()->with(['addresses', 'payment', 'items'])->where_in('channel_id', $this->channel_ids)->where_between('orders.created_at', [now()->today(), now()->end_of_day()])->get();
    }
    /**
     * Retrieves total sales and their progress.
     */
    public function get_total_sales_progress(): array
    {
        return ['previous' => $previous = $this->get_total_sales($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_total_sales($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves sub total sales and their progress.
     */
    public function get_sub_total_sales_progress(): array
    {
        return ['previous' => $previous = $this->get_sub_total_sales($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_sub_total_sales($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves today sales and their progress.
     */
    public function get_today_sales_progress(): array
    {
        return ['previous' => $previous = $this->get_total_sales(now()->sub_day()->start_of_day(), now()->sub_day()->end_of_day()), 'current' => $current = $this->get_total_sales(now()->today(), now()->end_of_day()), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_total_sales($start_date, $end_date): float
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->sum(DB::raw('base_grand_total_invoiced - base_grand_total_refunded'));
    }
    /**
     * Retrieves sub total sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_sub_total_sales($start_date, $end_date): float
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->sum(DB::raw('base_sub_total_invoiced - base_sub_total_refunded'));
    }
    /**
     * Returns previous sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_total_sales_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_sales_over_time($this->last_start_date, $this->last_end_date, $period, $include_empty);
    }
    /**
     * Returns current sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_total_sales_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_total_sales_over_time($this->start_date, $this->end_date, $period, $include_empty);
    }
    /**
     * Returns sales over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_total_sales_over_time($start_date, $end_date, $period, $include_empty): array
    {
        return $this->get_over_time_stats($start_date, $end_date, 'SUM(base_grand_total_invoiced - base_grand_total_refunded)', $period);
    }
    /**
     * Retrieves average sales and their progress.
     */
    public function get_average_sales_progress(): array
    {
        return ['previous' => $previous = $this->get_average_sales($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_average_sales($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves average sales
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_average_sales($start_date, $end_date): ?float
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->avg(DB::raw('base_grand_total_invoiced - base_grand_total_refunded'));
    }
    /**
     * Returns previous average sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_average_sales_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_average_sales_over_time($this->last_start_date, $this->last_end_date, $period, $include_empty);
    }
    /**
     * Returns current average sales over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_average_sales_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_average_sales_over_time($this->start_date, $this->end_date, $period, $include_empty);
    }
    /**
     * Returns average sales over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_average_sales_over_time($start_date, $end_date, $period, $include_empty): array
    {
        return $this->get_over_time_stats($start_date, $end_date, 'AVG(base_grand_total_invoiced - base_grand_total_refunded)', $period);
    }
    /**
     * Retrieves refunds and their progress.
     */
    public function get_refunds_progress(): array
    {
        return ['previous' => $previous = $this->get_refunds($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_refunds($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves refunds
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_refunds($start_date, $end_date): float
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->sum(DB::raw('base_grand_total_refunded'));
    }
    /**
     * Returns previous refunds over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_refunds_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_refunds_over_time($this->last_start_date, $this->last_end_date, $period, $include_empty);
    }
    /**
     * Returns current refunds over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_refunds_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_refunds_over_time($this->start_date, $this->end_date, $period, $include_empty);
    }
    /**
     * Returns refunds over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_refunds_over_time($start_date, $end_date, $period, $include_empty): array
    {
        return $this->get_over_time_stats($start_date, $end_date, 'SUM(base_grand_total_refunded)', $period);
    }
    /**
     * Retrieves tax collected and their progress.
     */
    public function get_tax_collected_progress(): array
    {
        return ['previous' => $previous = $this->get_tax_collected($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_tax_collected($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves tax collected
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_tax_collected($start_date, $end_date): float
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->sum(DB::raw('base_tax_amount_invoiced - base_tax_amount_refunded'));
    }
    /**
     * Returns previous tax collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_tax_collected_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_tax_collected_over_time($this->last_start_date, $this->last_end_date, $period, $include_empty);
    }
    /**
     * Returns current tax collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_tax_collected_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_tax_collected_over_time($this->start_date, $this->end_date, $period, $include_empty);
    }
    /**
     * Returns tax collected over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_tax_collected_over_time($start_date, $end_date, $period, $include_empty): array
    {
        return $this->get_over_time_stats($start_date, $end_date, 'SUM(base_tax_amount_invoiced - base_tax_amount_refunded)', $period);
    }
    /**
     * Returns top tax categories
     *
     * @param  int  $limit
     */
    public function get_top_tax_categories($limit = null): Collection
    {
        $table_prefix = DB::get_table_prefix();
        return $this->order_item_repository->reset_model()->left_join('orders', 'order_items.order_id', '=', 'orders.id')->left_join('tax_categories', 'order_items.tax_category_id', '=', 'tax_categories.id')->select('tax_categories.id as tax_category_id', 'tax_categories.name')->add_select(DB::raw("SUM({$table_prefix}order_items.base_tax_amount_invoiced - {$table_prefix}order_items.base_tax_amount_refunded) as total"))->where_in('orders.channel_id', $this->channel_ids)->where_between('order_items.created_at', [$this->start_date, $this->end_date])->where_not_null('tax_category_id')->group_by('tax_category_id')->order_by_desc('total')->limit($limit)->get();
    }
    /**
     * Retrieves shipping collected and their progress.
     */
    public function get_shipping_collected_progress(): array
    {
        return ['previous' => $previous = $this->get_shipping_collected($this->last_start_date, $this->last_end_date), 'current' => $current = $this->get_shipping_collected($this->start_date, $this->end_date), 'formatted_total' => core()->format_base_price($current), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves shipping collected
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     */
    public function get_shipping_collected($start_date, $end_date): float
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->sum(DB::raw('base_shipping_invoiced - base_shipping_refunded'));
    }
    /**
     * Returns previous shipping collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_previous_shipping_collected_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_shipping_collected_over_time($this->last_start_date, $this->last_end_date, $period, $include_empty);
    }
    /**
     * Returns current shipping collected over time
     *
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_current_shipping_collected_over_time($period = 'auto', $include_empty = true): array
    {
        return $this->get_shipping_collected_over_time($this->start_date, $this->end_date, $period, $include_empty);
    }
    /**
     * Returns shipping collected over time
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  bool  $includeEmpty
     */
    public function get_shipping_collected_over_time($start_date, $end_date, $period, $include_empty): array
    {
        return $this->get_over_time_stats($start_date, $end_date, 'SUM(base_shipping_invoiced - base_shipping_refunded)', $period);
    }
    /**
     * Returns top shipping methods
     *
     * @param  int  $limit
     */
    public function get_top_shipping_methods($limit = null): Collection
    {
        return $this->order_repository->reset_model()->select('shipping_title as title')->add_select(DB::raw('SUM(base_shipping_invoiced - base_shipping_refunded) as total'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$this->start_date, $this->end_date])->where_not_null('shipping_method')->group_by('shipping_method')->order_by_desc('total')->limit($limit)->get();
    }
    /**
     * Returns top payment methods
     *
     * @param  int  $limit
     */
    public function get_top_payment_methods($limit = null): Collection
    {
        return $this->order_repository->reset_model()->left_join('order_payment', 'orders.id', '=', 'order_payment.order_id')->select('method', 'method_title as title')->add_select(DB::raw('COUNT(*) as total'))->add_select(DB::raw('SUM(base_grand_total) as base_total'))->where_in('orders.channel_id', $this->channel_ids)->where_between('orders.created_at', [$this->start_date, $this->end_date])->group_by('method')->order_by_desc('total')->limit($limit)->get();
    }
    /**
     * Gets the total amount of pending invoices.
     */
    public function get_total_pending_invoices_amount(): float
    {
        return $this->invoice_repository->get_total_pending_invoices_amount();
    }
    /**
     * Retrieves total unique cart users
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_total_unique_orders_users($start_date, $end_date): int
    {
        return $this->order_repository->reset_model()->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->group_by(DB::raw('CONCAT(customer_email, "-", customer_id)'))->get()->count();
    }
    /**
     * Returns over time stats.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $valueColumn
     * @param  string  $period
     */
    public function get_over_time_stats($start_date, $end_date, $value_column, $period = 'auto'): array
    {
        $config = $this->get_time_interval($start_date, $end_date, $period);
        $group_column = $config['group_column'];
        $results = $this->order_repository->reset_model()->select(DB::raw("{$group_column} AS date"), DB::raw("{$value_column} AS total"), DB::raw('COUNT(*) AS count'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->group_by('date')->get();
        foreach ($config['intervals'] as $interval) {
            $total = $results->where('date', $interval['filter'])->first();
            $stats[] = ['label' => $interval['start'], 'total' => $total?->total ?? 0, 'count' => $total?->count ?? 0];
        }
        return $stats ?? [];
    }
}