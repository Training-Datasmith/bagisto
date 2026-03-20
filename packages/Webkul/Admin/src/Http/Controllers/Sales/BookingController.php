<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Carbon\Carbon;
use Webkul\Admin\Data_Grids\Sales\Booking_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Booking_Product\Repositories\Booking_Repository;
class Booking_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Booking_Repository $booking_repository)
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Booking_Data_Grid::class)->process();
        }
        return view('admin::sales.bookings.index');
    }
    /**
     * Returns a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        if (!request('view_type')) {
            return app(Booking_Data_Grid::class)->process();
        }
        $start_date = request()->get('startDate') ? Carbon::create_from_time_string(request()->get('startDate') . ' 00:00:01') : Carbon::now()->start_of_week()->format('Y-m-d H:i:s');
        $end_date = request()->get('endDate') ? Carbon::create_from_time_string(request()->get('endDate') . ' 23:59:59') : Carbon::now()->end_of_week()->format('Y-m-d H:i:s');
        $bookings = $this->booking_repository->get_bookings([strtotime($start_date), strtotime($end_date)])->map(function ($booking) {
            $booking['start'] = Carbon::create_from_timestamp($booking->start)->format('Y-m-d h:i A');
            $booking['end'] = Carbon::create_from_timestamp($booking->end)->format('Y-m-d h:i A');
            $booking->total = core()->format_base_price($booking->total);
            return $booking;
        });
        return response()->json(['bookings' => $bookings]);
    }
}