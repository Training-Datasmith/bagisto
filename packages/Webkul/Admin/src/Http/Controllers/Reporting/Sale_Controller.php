<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Reporting;

class Sale_Controller extends Controller
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $type_functions = ['total-sales' => 'getTotalSalesStats', 'average-sales' => 'getAverageSalesStats', 'total-orders' => 'getTotalOrdersStats', 'purchase-funnel' => 'getPurchaseFunnelStats', 'abandoned-carts' => 'getAbandonedCartsStats', 'refunds' => 'getRefundsStats', 'tax-collected' => 'getTaxCollectedStats', 'shipping-collected' => 'getShippingCollectedStats', 'top-payment-methods' => 'getTopPaymentMethods'];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::reporting.sales.index')->with(['startDate' => $this->reporting_helper->get_start_date(), 'endDate' => $this->reporting_helper->get_end_date()]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function view()
    {
        if ($this->validate_requested_type()) {
            abort(404);
        }
        return view('admin::reporting.view')->with(['entity' => 'sales', 'startDate' => $this->reporting_helper->get_start_date(), 'endDate' => $this->reporting_helper->get_end_date()]);
    }
}