<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Reporting;

class Product_Controller extends Controller
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $type_functions = ['total-sold-quantities' => 'getTotalSoldQuantitiesStats', 'total-products-added-to-wishlist' => 'getTotalProductsAddedToWishlistStats', 'top-selling-products-by-revenue' => 'getTopSellingProductsByRevenue', 'top-selling-products-by-quantity' => 'getTopSellingProductsByQuantity', 'products-with-most-reviews' => 'getProductsWithMostReviews', 'products-with-most-visits' => 'getProductsWithMostVisits', 'last-search-terms' => 'getLastSearchTerms', 'top-search-terms' => 'getTopSearchTerms'];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::reporting.products.index')->with(['startDate' => $this->reporting_helper->get_start_date(), 'endDate' => $this->reporting_helper->get_end_date()]);
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
        return view('admin::reporting.view')->with(['entity' => 'products', 'startDate' => $this->reporting_helper->get_start_date(), 'endDate' => $this->reporting_helper->get_end_date()]);
    }
}