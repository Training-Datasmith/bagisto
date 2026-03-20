<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Reporting;

use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reporting_Export;
use Webkul\Admin\Helpers\Reporting as ReportingHelper;
use Webkul\Admin\Http\Controllers\Controller as BaseController;
class Controller extends Base_Controller
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $type_functions = [];
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Reporting_Helper $reporting_helper)
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = $this->reporting_helper->{$this->resolve_type_function()}();
        return response()->json(['statistics' => $stats, 'date_range' => $this->reporting_helper->get_date_range()]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function view_stats()
    {
        $stats = $this->reporting_helper->{$this->resolve_type_function()}('table');
        return response()->json(['statistics' => $stats, 'date_range' => $this->reporting_helper->get_date_range()]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        $stats = $this->reporting_helper->{$this->resolve_type_function()}('table');
        return Excel::download(new Reporting_Export($stats), request()->query('type') . '.' . request()->query('format'));
    }
    /**
     * Validate if the requested type is valid.
     *
     * @return void
     */
    protected function validate_requested_type()
    {
        return !array_key_exists(request()->query('type'), $this->type_functions);
    }
    /**
     * Resolve the requested type into a valid function name.
     *
     * @return string
     */
    protected function resolve_type_function()
    {
        if ($this->validate_requested_type()) {
            abort(404);
        }
        return $this->type_functions[request()->query('type')];
    }
}