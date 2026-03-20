<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers;

use Webkul\Admin\Helpers\Dashboard;
class Dashboard_Controller extends Controller
{
    /**
     * Request param functions
     *
     * @var array
     */
    protected $type_functions = ['over-all' => 'getOverAllStats', 'today' => 'getTodayStats', 'stock-threshold-products' => 'getStockThresholdProducts', 'total-sales' => 'getSalesStats', 'total-visitors' => 'getVisitorStats', 'top-selling-products' => 'getTopSellingProducts', 'top-customers' => 'getTopCustomers'];
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Dashboard $dashboard_helper)
    {
    }
    /**
     * Dashboard page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return view('admin::dashboard.index')->with(['startDate' => $this->dashboard_helper->get_start_date(), 'endDate' => $this->dashboard_helper->get_end_date()]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = $this->dashboard_helper->{$this->type_functions[request()->query('type')]}();
        return response()->json(['statistics' => $stats, 'date_range' => $this->dashboard_helper->get_date_range()]);
    }
}