<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Json_Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Sales\Order_Invoice_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Update_Request;
use Webkul\Core\Traits\Pdf_Handler;
use Webkul\Sales\Repositories\Invoice_Repository;
use Webkul\Sales\Repositories\Order_Repository;
class Invoice_Controller extends Controller
{
    use Pdf_Handler;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Order_Repository $order_repository, protected Invoice_Repository $invoice_repository)
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
            return datagrid(Order_Invoice_Data_Grid::class)->process();
        }
        return view('admin::sales.invoices.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(int $order_id)
    {
        $order = $this->order_repository->find_or_fail($order_id);
        if ($order->payment->method === 'paypal_standard') {
            abort(404);
        }
        return view('admin::sales.invoices.create', compact('order'));
    }
    /**
     * (Store) a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(int $order_id)
    {
        $order = $this->order_repository->find_or_fail($order_id);
        if (!$order->can_invoice()) {
            session()->flash('error', trans('admin::app.sales.invoices.create.creation-error'));
            return redirect()->back();
        }
        $this->validate(request(), ['invoice.items' => 'required|array', 'invoice.items.*' => 'required|numeric|min:0']);
        if (!$this->invoice_repository->have_product_to_invoice(request()->all())) {
            session()->flash('error', trans('admin::app.sales.invoices.create.product-error'));
            return redirect()->back();
        }
        if (!$this->invoice_repository->is_valid_quantity(request()->all())) {
            session()->flash('error', trans('admin::app.sales.invoices.create.invalid-qty'));
            return redirect()->back();
        }
        $this->invoice_repository->create(array_merge(request()->all(), ['order_id' => $order_id]));
        session()->flash('success', trans('admin::app.sales.invoices.create.create-success'));
        return redirect()->route('admin.sales.orders.view', $order_id);
    }
    /**
     * Show the view for the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function view(int $id)
    {
        $invoice = $this->invoice_repository->find_or_fail($id);
        return view('admin::sales.invoices.view', compact('invoice'));
    }
    /**
     * Send duplicate invoice.
     *
     * @return \Illuminate\Http\Response
     */
    public function send_duplicate_email(Request $request, int $id)
    {
        $request->validate(['email' => 'required|email']);
        $invoice = $this->invoice_repository->find_or_fail($id);
        Event::dispatch('sales.invoice.send_duplicate_email', ['invoice' => $invoice, 'duplicate_invoice_email' => request()->input('email')]);
        session()->flash('success', trans('admin::app.sales.invoices.view.invoice-sent'));
        return redirect()->route('admin.sales.invoices.view', $invoice->id);
    }
    /**
     * Print and download the for the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function print_invoice(int $id)
    {
        $invoice = $this->invoice_repository->find_or_fail($id);
        $order_currency_code = $invoice->order->order_currency_code;
        return $this->download_pdf(view('shop::customers.account.orders.pdf', compact('invoice', 'orderCurrencyCode'))->render(), 'invoice-' . $invoice->created_at->format('d-m-Y'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function mass_update_state(Mass_Update_Request $mass_update_request)
    {
        $invoice_ids = $mass_update_request->input('indices');
        $invoices = $this->invoice_repository->find_where_in('id', $invoice_ids);
        foreach ($invoices as $invoice) {
            $invoice->state = $mass_update_request->input('value');
            $invoice->save();
        }
        return new Json_Response(['message' => trans('admin::app.sales.invoices.index.datagrid.mass-update-success')], 200);
    }
}