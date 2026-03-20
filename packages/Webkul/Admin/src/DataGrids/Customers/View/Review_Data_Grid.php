<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Customers\View;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Review_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @var string
     */
    protected $primary_column = 'product_review_id';
    /**
     * Review status "approved".
     */
    public const STATUS_APPROVED = 'approved';
    /**
     * Review status "pending", indicating awaiting approval or processing.
     */
    public const STATUS_PENDING = 'pending';
    /**
     * Review status "disapproved", indicating rejection or denial.
     */
    public const STATUS_DISAPPROVED = 'disapproved';
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('product_reviews')->left_join('product_flat', function ($left_join) {
            $left_join->on('product_flat.product_id', '=', 'product_reviews.product_id');
        })->select('product_flat.name as product_name', 'product_flat.id', 'product_reviews.id as product_review_id', 'product_reviews.status', 'product_reviews.rating', 'product_reviews.created_at', 'product_reviews.title', 'product_reviews.comment', 'product_reviews.product_id')->where('customer_id', request()->route('id'))->where('channel', core()->get_current_channel_code())->where('locale', app()->get_locale());
        $this->add_filter('product_review_id', 'product_reviews.id');
        $this->add_filter('created_at', 'product_reviews.created_at');
        $this->add_filter('status', 'product_reviews.status');
        $this->add_filter('product_name', 'product_flat.name');
        $this->add_filter('product_id', 'product_flat.product_id');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'product_review_id', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'product_name', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.product-name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'title', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.title'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'comment', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.comment'), 'type' => 'string']);
        $this->add_column(['index' => 'product_id', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.product-id'), 'type' => 'string']);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.status'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.customers.reviews.index.datagrid.approved'), 'value' => self::STATUS_APPROVED], ['label' => trans('admin::app.customers.reviews.index.datagrid.pending'), 'value' => self::STATUS_PENDING], ['label' => trans('admin::app.customers.reviews.index.datagrid.disapproved'), 'value' => self::STATUS_DISAPPROVED]], 'sortable' => true, 'closure' => function ($row) {
            switch ($row->status) {
                case self::STATUS_APPROVED:
                    return '<p class="label-active">' . trans('admin::app.customers.customers.view.datagrid.reviews.approved') . '</p>';
                case self::STATUS_PENDING:
                    return '<p class="label-pending">' . trans('admin::app.customers.customers.view.datagrid.reviews.pending') . '</p>';
                case self::STATUS_DISAPPROVED:
                    return '<p class="label-canceled">' . trans('admin::app.customers.customers.view.datagrid.reviews.disapproved') . '</p>';
            }
        }]);
        $this->add_column(['index' => 'rating', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.rating'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => array_map(function ($value) {
            return ['label' => $value, 'value' => (string) $value];
        }, range(1, 5)), 'sortable' => true]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.customers.customers.view.datagrid.reviews.created-at'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('catalog.products.edit')) {
            $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.customers.customers.view.datagrid.reviews.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.sales.orders.view', $row->id);
            }]);
        }
    }
}