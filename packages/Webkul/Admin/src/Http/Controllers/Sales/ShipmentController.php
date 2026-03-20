<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Webkul\Admin\Data_Grids\Sales\Order_Shipment_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Repositories\Order_Item_Repository;
use Webkul\Sales\Repositories\Order_Repository;
use Webkul\Sales\Repositories\Shipment_Repository;
class Shipment_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Order_Repository $order_repository, protected Order_Item_Repository $order_item_repository, protected Shipment_Repository $shipment_repository)
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
            return datagrid(Order_Shipment_Data_Grid::class)->process();
        }
        return view('admin::sales.shipments.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(int $order_id)
    {
        $order = $this->order_repository->find_or_fail($order_id);
        if (!$order->channel || !$order->can_ship()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.creation-error'));
            return redirect()->back();
        }
        return view('admin::sales.shipments.create', compact('order'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(int $order_id)
    {
        $order = $this->order_repository->find_or_fail($order_id);
        if (!$order->can_ship()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.order-error'));
            return redirect()->back();
        }
        $this->validate(request(), ['shipment.source' => 'required', 'shipment.items.*.*' => 'required|numeric|min:0']);
        $data = request()->only(['shipment', 'carrier_name']);
        if (!$this->is_inventory_validate($data)) {
            session()->flash('error', trans('admin::app.sales.shipments.create.quantity-invalid'));
            return redirect()->back();
        }
        $this->shipment_repository->create(array_merge($data, ['order_id' => $order_id]));
        session()->flash('success', trans('admin::app.sales.shipments.create.success'));
        return redirect()->route('admin.sales.orders.view', $order_id);
    }
    /**
     * Checks if requested quantity available or not.
     *
     * @param  array  $data
     * @return bool
     */
    public function is_inventory_validate(&$data)
    {
        if (!isset($data['shipment']['items'])) {
            return;
        }
        $valid = false;
        $inventory_source_id = $data['shipment']['source'];
        foreach ($data['shipment']['items'] as $item_id => $inventory_source) {
            $qty = $inventory_source[$inventory_source_id];
            if ((int) $qty) {
                $order_item = $this->order_item_repository->find($item_id);
                if ($order_item->qty_to_ship < $qty) {
                    return false;
                }
                if ($order_item->get_type_instance()->is_composite()) {
                    foreach ($order_item->children as $child) {
                        if (!$child->qty_ordered) {
                            continue;
                        }
                        $final_qty = $child->qty_ordered / $order_item->qty_ordered * $qty;
                        $available_qty = $child->product->inventories()->where('inventory_source_id', $inventory_source_id)->sum('qty');
                        if ($child->qty_to_ship < $final_qty || $available_qty < $final_qty) {
                            return false;
                        }
                    }
                } else {
                    $available_qty = $order_item->product->inventories()->where('inventory_source_id', $inventory_source_id)->sum('qty');
                    if ($order_item->qty_to_ship < $qty || $available_qty < $qty) {
                        return false;
                    }
                }
                $valid = true;
            } else {
                unset($data['shipment']['items'][$item_id]);
            }
        }
        return $valid;
    }
    /**
     * Show the view for the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function view(int $id)
    {
        $shipment = $this->shipment_repository->find_or_fail($id);
        return view('admin::sales.shipments.view', compact('shipment'));
    }
}