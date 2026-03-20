<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Customers;

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
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('product_reviews')->left_join('product_flat', 'product_reviews.product_id', '=', 'product_flat.product_id')->left_join('customers', 'product_reviews.customer_id', '=', 'customers.id')->select('product_reviews.id as product_review_id', 'product_reviews.title', 'product_reviews.comment', 'product_reviews.name as customer_full_name', 'product_flat.name as product_name', 'product_reviews.status as product_review_status', 'product_reviews.rating', 'product_reviews.created_at')->where('channel', core()->get_current_channel_code())->where('locale', app()->get_locale());
        $this->add_filter('product_review_id', 'product_reviews.id');
        $this->add_filter('product_review_status', 'product_reviews.status');
        $this->add_filter('product_name', 'product_flat.name');
        $this->add_filter('created_at', 'product_reviews.created_at');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'customer_full_name', 'label' => trans('admin::app.customers.reviews.index.datagrid.customer-names'), 'type' => 'string', 'sortable' => true]);
        $this->add_column(['index' => 'product_name', 'label' => trans('admin::app.customers.reviews.index.datagrid.product'), 'type' => 'string', 'searchable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'product_review_status', 'label' => trans('admin::app.customers.reviews.index.datagrid.status'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.customers.reviews.index.datagrid.approved'), 'value' => self::STATUS_APPROVED], ['label' => trans('admin::app.customers.reviews.index.datagrid.pending'), 'value' => self::STATUS_PENDING], ['label' => trans('admin::app.customers.reviews.index.datagrid.disapproved'), 'value' => self::STATUS_DISAPPROVED]], 'sortable' => true, 'closure' => function ($row) {
            switch ($row->product_review_status) {
                case self::STATUS_APPROVED:
                    return '<p class="label-active">' . trans('admin::app.customers.reviews.index.datagrid.approved') . '</p>';
                case self::STATUS_PENDING:
                    return '<p class="label-pending">' . trans('admin::app.customers.reviews.index.datagrid.pending') . '</p>';
                case self::STATUS_DISAPPROVED:
                    return '<p class="label-canceled">' . trans('admin::app.customers.reviews.index.datagrid.disapproved') . '</p>';
            }
        }]);
        $this->add_column(['index' => 'rating', 'label' => trans('admin::app.customers.reviews.index.datagrid.rating'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => array_map(function ($value) {
            return ['label' => $value, 'value' => (string) $value];
        }, range(1, 5)), 'sortable' => true]);
        $this->add_column(['index' => 'product_review_id', 'label' => trans('admin::app.customers.reviews.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'title', 'label' => trans('admin::app.customers.reviews.index.datagrid.title'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'comment', 'label' => trans('admin::app.customers.reviews.index.datagrid.comment'), 'type' => 'string']);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.customers.reviews.index.datagrid.date'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('customers.reviews.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.customers.reviews.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.customers.customers.review.edit', $row->product_review_id);
            }]);
        }
        if (bouncer()->has_permission('customers.reviews.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.customers.reviews.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.customers.customers.review.delete', $row->product_review_id);
            }]);
        }
    }
    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepare_mass_actions()
    {
        if (bouncer()->has_permission('customers.reviews.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.customers.reviews.index.datagrid.delete'), 'url' => route('admin.customers.customers.review.mass_delete'), 'method' => 'POST']);
        }
        if (bouncer()->has_permission('customers.reviews.edit')) {
            $this->add_mass_action(['title' => trans('admin::app.customers.reviews.index.datagrid.update-status'), 'method' => 'POST', 'url' => route('admin.customers.customers.review.mass_update'), 'options' => [['label' => trans('admin::app.customers.reviews.index.datagrid.pending'), 'value' => 'pending'], ['label' => trans('admin::app.customers.reviews.index.datagrid.approved'), 'value' => 'approved'], ['label' => trans('admin::app.customers.reviews.index.datagrid.disapproved'), 'value' => 'disapproved']]]);
        }
    }
}