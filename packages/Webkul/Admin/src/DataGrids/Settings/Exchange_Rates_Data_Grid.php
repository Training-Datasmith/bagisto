<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Exchange_Rates_Data_Grid extends Data_Grid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primary_column = 'currency_exchange_id';
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('currency_exchange_rates')->left_join('currencies', 'currency_exchange_rates.target_currency', '=', 'currencies.id')->select('currency_exchange_rates.id as currency_exchange_id', 'currencies.name as currency_name', 'currency_exchange_rates.rate as currency_rate');
        $this->add_filter('currency_exchange_id', 'currency_exchange_rates.id');
        $this->add_filter('currency_name', 'currencies.name');
        $this->add_filter('currency_rate', 'currency_exchange_rates.rate');
        return $query_builder;
    }
    public function prepare_columns()
    {
        $this->add_column(['index' => 'currency_exchange_id', 'label' => trans('admin::app.settings.exchange-rates.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'currency_name', 'label' => trans('admin::app.settings.exchange-rates.index.datagrid.currency-name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'currency_rate', 'label' => trans('admin::app.settings.exchange-rates.index.datagrid.exchange-rate'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.exchange_rates.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.settings.exchange-rates.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.exchange_rates.edit', $row->currency_exchange_id);
            }]);
        }
        if (bouncer()->has_permission('settings.exchange_rates.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.settings.exchange-rates.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.exchange_rates.delete', $row->currency_exchange_id);
            }]);
        }
    }
}