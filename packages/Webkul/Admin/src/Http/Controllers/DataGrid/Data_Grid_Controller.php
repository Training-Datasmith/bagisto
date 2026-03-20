<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Data_Grid;

use Illuminate\Support\Facades\Crypt;
use Webkul\Admin\Http\Controllers\Controller;
class Data_Grid_Controller extends Controller
{
    /**
     * Look up.
     */
    public function look_up()
    {
        /**
         * Validation for parameters.
         */
        $params = $this->validate(request(), ['datagrid_id' => ['required'], 'column' => ['required'], 'search' => ['required', 'min:2']]);
        /**
         * Preparing the datagrid instance and only columns.
         */
        $datagrid = app(Crypt::decrypt_string($params['datagrid_id']));
        $datagrid->prepare_columns();
        /**
         * Finding the first column from the collection.
         */
        $column = collect($datagrid->get_columns())->where('index', $params['column'])->first_or_fail();
        /**
         * Fetching on the basis of column options.
         */
        return app($column->options['params']['repository'])->select([$column->options['params']['column']['label'] . ' as label', $column->options['params']['column']['value'] . ' as value'])->where($column->options['params']['column']['label'], 'LIKE', '%' . $params['search'] . '%')->get();
    }
}