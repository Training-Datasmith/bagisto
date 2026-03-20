<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Reporting;

class Customer_Controller extends Controller
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $type_functions = ['total-customers' => 'getTotalCustomersStats', 'customers-traffic' => 'getCustomersTrafficStats', 'customers-with-most-sales' => 'getCustomersWithMostSales', 'customers-with-most-orders' => 'getCustomersWithMostOrders', 'customers-with-most-reviews' => 'getCustomersWithMostReviews', 'top-customer-groups' => 'getTopCustomerGroups'];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::reporting.customers.index')->with(['startDate' => $this->reporting_helper->get_start_date(), 'endDate' => $this->reporting_helper->get_end_date()]);
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
        return view('admin::reporting.view')->with(['entity' => 'customers', 'startDate' => $this->reporting_helper->get_start_date(), 'endDate' => $this->reporting_helper->get_end_date()]);
    }
}