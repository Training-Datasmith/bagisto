<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Json_Response;
use Illuminate\Http\Request;
use Webkul\Admin\Data_Grids\Sales\Order_Transaction_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Transaction_Resource;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\Invoice_Repository;
use Webkul\Sales\Repositories\Order_Repository;
use Webkul\Sales\Repositories\Order_Transaction_Repository;
use Webkul\Sales\Repositories\Shipment_Repository;
class Transaction_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Order_Repository $order_repository, protected Invoice_Repository $invoice_repository, protected Shipment_Repository $shipment_repository, protected Order_Transaction_Repository $order_transaction_repository)
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
            return datagrid(Order_Transaction_Data_Grid::class)->process();
        }
        $payment_methods = Payment::get_supported_payment_methods();
        return view('admin::sales.transactions.index', compact('paymentMethods'));
    }
    /**
     * Save the transaction.
     */
    public function store(Request $request): Json_Response
    {
        $this->validate(request(), ['invoice_id' => 'required', 'payment_method' => 'required', 'amount' => 'required|numeric']);
        $invoice = $this->invoice_repository->where('id', $request->invoice_id)->first();
        if (!$invoice) {
            return new Json_Response(['message' => trans('admin::app.sales.transactions.index.create.invoice-missing')], 400);
        }
        $transaction_amt_before = $this->order_transaction_repository->where('invoice_id', $invoice->id)->sum('amount');
        $transaction_amt_final = $request->amount + $transaction_amt_before;
        if ($invoice->state == 'paid') {
            return new Json_Response(['message' => trans('admin::app.sales.transactions.index.create.already-paid')], 400);
        }
        if ($transaction_amt_final > $invoice->base_grand_total) {
            return new Json_Response(['message' => trans('admin::app.sales.transactions.index.create.transaction-amount-exceeds')], 400);
        }
        if ($request->amount <= 0) {
            return new Json_Response(['message' => trans('admin::app.sales.transactions.index.create.transaction-amount-zero')], 400);
        }
        $order = $this->order_repository->find($invoice->order_id);
        $this->order_transaction_repository->create(['transaction_id' => bin2hex(random_bytes(20)), 'type' => $request->payment_method, 'payment_method' => $request->payment_method, 'invoice_id' => $invoice->id, 'order_id' => $invoice->order_id, 'amount' => $request->amount, 'status' => 'paid', 'data' => json_encode(['paidAmount' => $request->amount])]);
        $transaction_total = $this->order_transaction_repository->where('invoice_id', $invoice->id)->sum('amount');
        if ($transaction_total >= $invoice->base_grand_total) {
            $shipments = $this->shipment_repository->where('order_id', $invoice->order_id)->first();
            $status = isset($shipments) ? Order::STATUS_COMPLETED : Order::STATUS_PROCESSING;
            $this->order_repository->update_order_status($order, $status);
            $this->invoice_repository->update_state($invoice, Invoice::STATUS_PAID);
        }
        return new Json_Response(['message' => trans('admin::app.sales.transactions.index.create.transaction-saved')]);
    }
    /**
     * Show the view for the specified resource.
     */
    public function view(int $id): Transaction_Resource
    {
        $transaction = $this->order_transaction_repository->find_or_fail($id);
        return new Transaction_Resource($transaction);
    }
}