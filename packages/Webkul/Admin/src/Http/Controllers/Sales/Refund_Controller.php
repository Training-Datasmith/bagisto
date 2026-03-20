<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Webkul\Admin\Data_Grids\Sales\Order_Refund_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Exceptions\Invalid_Refund_Quantity_Exception;
use Webkul\Sales\Repositories\Order_Item_Repository;
use Webkul\Sales\Repositories\Order_Repository;
use Webkul\Sales\Repositories\Refund_Repository;
class Refund_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Order_Repository $order_repository, protected Order_Item_Repository $order_item_repository, protected Refund_Repository $refund_repository)
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
            return datagrid(Order_Refund_Data_Grid::class)->process();
        }
        return view('admin::sales.refunds.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(int $order_id)
    {
        $order = $this->order_repository->find_or_fail($order_id);
        return view('admin::sales.refunds.create', compact('order'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(int $order_id)
    {
        $order = $this->order_repository->find_or_fail($order_id);
        if (!$order->can_refund()) {
            session()->flash('error', trans('admin::app.sales.refunds.create.creation-error'));
            return redirect()->back();
        }
        $this->validate(request(), ['refund.items' => 'array', 'refund.items.*' => 'required|numeric|min:0']);
        $data = request()->all();
        if (!isset($data['refund']['shipping'])) {
            $data['refund']['shipping'] = 0;
        }
        try {
            $totals = $this->refund_repository->get_order_items_refund_summary($data['refund'], $order_id);
            if (!$totals) {
                throw new Invalid_Refund_Quantity_Exception(trans('admin::app.sales.refunds.create.invalid-qty'));
            }
        } catch (Invalid_Refund_Quantity_Exception $invalid_refund_quantity_exception) {
            session()->flash('error', $invalid_refund_quantity_exception->get_message());
            return redirect()->back();
        }
        $max_refund_amount = $totals['grand_total']['price'] - $order->refunds()->sum('base_adjustment_refund');
        $refund_amount = $totals['grand_total']['price'] - $totals['shipping']['price'] + $data['refund']['shipping'] + $data['refund']['adjustment_refund'] - $data['refund']['adjustment_fee'];
        if (!$refund_amount) {
            session()->flash('error', trans('admin::app.sales.refunds.create.invalid-refund-amount-error'));
            return redirect()->back();
        }
        if ($refund_amount > $max_refund_amount) {
            session()->flash('error', trans('admin::app.sales.refunds.create.refund-limit-error', ['amount' => core()->format_base_price($max_refund_amount)]));
            return redirect()->back();
        }
        $this->refund_repository->create(array_merge($data, ['order_id' => $order_id]));
        session()->flash('success', trans('admin::app.sales.refunds.create.create-success'));
        return redirect()->route('admin.sales.orders.view', $order_id);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update_totals(int $order_id)
    {
        try {
            $data = $this->refund_repository->get_order_items_refund_summary(request()->input(), $order_id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->get_message()], 400);
        }
        return response()->json($data);
    }
    /**
     * Show the view for the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function view($id)
    {
        $refund = $this->refund_repository->find_or_fail($id);
        return view('admin::sales.refunds.view', compact('refund'));
    }
}