<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Tax_Rate_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('tax_rates')->select('id', 'identifier', 'state', 'country', 'zip_code', 'zip_from', 'zip_to', 'tax_rate');
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'identifier', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.identifier'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'state', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.state'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($value) {
            if (empty($value->state)) {
                return '*';
            }
            return $value->state;
        }]);
        $this->add_column(['index' => 'country', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.country'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'zip_code', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.zip-code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'zip_from', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.zip-from'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'zip_to', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.zip-to'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'tax_rate', 'label' => trans('admin::app.settings.taxes.rates.index.datagrid.tax-rate'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.taxes.tax_rates.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.settings.taxes.rates.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.taxes.rates.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.taxes.tax_rates.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.settings.taxes.rates.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.taxes.rates.delete', $row->id);
            }]);
        }
    }
}