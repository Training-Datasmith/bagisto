<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Data_Grids\Customers\Customer_Data_Grid;
use Webkul\Admin\Data_Grids\Customers\View\Invoice_Data_Grid;
use Webkul\Admin\Data_Grids\Customers\View\Order_Data_Grid;
use Webkul\Admin\Data_Grids\Customers\View\Review_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Admin\Http\Requests\Mass_Update_Request;
use Webkul\Admin\Mail\Customer\New_Customer_Notification;
use Webkul\Core\Rules\Phone_Number;
use Webkul\Customer\Repositories\Customer_Group_Repository;
use Webkul\Customer\Repositories\Customer_Note_Repository;
use Webkul\Customer\Repositories\Customer_Repository;
class Customer_Controller extends Controller
{
    /**
     * Ajax request for orders.
     */
    public const ORDERS = 'orders';
    /**
     * Ajax request for invoices.
     */
    public const INVOICES = 'invoices';
    /**
     * Ajax request for reviews.
     */
    public const REVIEWS = 'reviews';
    /**
     * Static pagination count.
     *
     * @var int
     */
    public const COUNT = 10;
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Customer_Repository $customer_repository, protected Customer_Group_Repository $customer_group_repository, protected Customer_Note_Repository $customer_note_repository)
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
            return datagrid(Customer_Data_Grid::class)->process();
        }
        $channels = core()->get_all_channels();
        $groups = $this->customer_group_repository->find_where([['code', '<>', 'guest']]);
        return view('admin::customers.customers.index', compact('channels', 'groups'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['first_name' => 'string|required', 'last_name' => 'string|required', 'gender' => 'required', 'channel_id' => 'required|integer', 'email' => 'required|unique:customers,email,NULL,id,channel_id,' . request('channel_id'), 'date_of_birth' => 'date|before:today', 'phone' => ['unique:customers,phone', new Phone_Number()]]);
        $password = bin2hex(random_bytes(12));
        Event::dispatch('customer.registration.before');
        $data = array_merge(['password' => bcrypt($password), 'is_verified' => 1], request()->only(['first_name', 'last_name', 'gender', 'email', 'date_of_birth', 'phone', 'customer_group_id', 'channel_id']));
        if (empty($data['phone'])) {
            $data['phone'] = null;
        }
        Event::dispatch('customer.create.before');
        $customer = $this->customer_repository->create($data);
        if (core()->get_config_data('emails.general.notifications.emails.general.notifications.customer_account_credentials')) {
            try {
                Mail::queue(new New_Customer_Notification($customer, $password));
            } catch (\Exception $e) {
                report($e);
            }
        }
        Event::dispatch('customer.create.after', $customer);
        Event::dispatch('customer.registration.after', $customer);
        return new Json_Response(['data' => $customer, 'message' => trans('admin::app.customers.customers.index.create.create-success')]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(int $id)
    {
        $this->validate(request(), ['first_name' => 'string|required', 'last_name' => 'string|required', 'gender' => 'required', 'email' => 'required|unique:customers,email,' . $id, 'date_of_birth' => 'date|before:today', 'phone' => ['unique:customers,phone,' . $id, new Phone_Number()]]);
        $data = request()->only(['first_name', 'last_name', 'gender', 'email', 'date_of_birth', 'phone', 'customer_group_id', 'status', 'is_suspended']);
        if (empty($data['phone'])) {
            $data['phone'] = null;
        }
        Event::dispatch('customer.update.before', $id);
        $customer = $this->customer_repository->update($data, $id);
        Event::dispatch('customer.update.after', $customer);
        return new Json_Response(['message' => trans('admin::app.customers.customers.update-success'), 'data' => ['customer' => $customer->fresh(), 'group' => $customer->group]]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $customer = $this->customer_repository->findor_fail($id);
        if (!$customer) {
            return response()->json(['message' => trans('admin::app.customers.customers.delete-failed')], 400);
        }
        if (!$this->customer_repository->have_active_orders($customer)) {
            $this->customer_repository->delete($id);
            session()->flash('success', trans('admin::app.customers.customers.delete-success'));
            return redirect()->route('admin.customers.customers.index');
        }
        session()->flash('error', trans('admin::app.customers.customers.view.order-pending'));
        return redirect()->route('admin.customers.customers.index');
    }
    /**
     * Login as customer.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login_as_customer(int $id)
    {
        $customer = $this->customer_repository->find_or_fail($id);
        auth()->guard('customer')->login($customer);
        session()->flash('success', trans('admin::app.customers.customers.index.login-message', ['customer_name' => $customer->name]));
        return redirect(route('shop.customers.account.profile.index'));
    }
    /**
     * To store the response of the note.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store_notes(int $id)
    {
        $this->validate(request(), ['note' => 'string|required']);
        Event::dispatch('customer.note.create.before', $id);
        $customer_note = $this->customer_note_repository->create(['customer_id' => $id, 'note' => request()->input('note'), 'customer_notified' => request()->input('customer_notified', 0)]);
        Event::dispatch('customer.note.create.after', $customer_note);
        session()->flash('success', trans('admin::app.customers.customers.view.note-created-success'));
        return redirect()->route('admin.customers.customers.view', $id);
    }
    /**
     * View all details of customer.
     */
    public function show(int $id)
    {
        $customer = $this->customer_repository->with(['addresses', 'group'])->find_or_fail($id);
        $groups = $this->customer_group_repository->find_where([['code', '<>', 'guest']]);
        if (request()->ajax()) {
            switch (request()->query('type')) {
                case self::ORDERS:
                    return datagrid(Order_Data_Grid::class)->process();
                case self::INVOICES:
                    return datagrid(Invoice_Data_Grid::class)->process();
                case self::REVIEWS:
                    return datagrid(Review_Data_Grid::class)->process();
            }
        }
        return view('admin::customers.customers.view', compact('customer', 'groups'));
    }
    /**
     * Result of search customer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $customers = $this->customer_repository->scope_query(function ($query) {
            return $query->where('email', 'like', '%' . urldecode(request()->input('query')) . '%')->or_where(DB::raw('CONCAT(first_name, " ", last_name)'), 'like', '%' . urldecode(request()->input('query')) . '%')->order_by('created_at', 'desc');
        })->paginate(self::COUNT);
        return response()->json($customers);
    }
    /**
     * To mass update the customer.
     */
    public function mass_update(Mass_Update_Request $mass_update_request): Json_Response
    {
        $selected_customer_ids = $mass_update_request->input('indices');
        foreach ($selected_customer_ids as $customer_id) {
            Event::dispatch('customer.update.before', $customer_id);
            $customer = $this->customer_repository->update(['status' => $mass_update_request->input('value')], $customer_id);
            Event::dispatch('customer.update.after', $customer);
        }
        return new Json_Response(['message' => trans('admin::app.customers.customers.index.datagrid.update-success')]);
    }
    /**
     * To mass delete the customer.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $customers = $this->customer_repository->find_where_in('id', $mass_destroy_request->input('indices'));
        try {
            /**
             * Ensure that customers do not have any active orders before performing deletion.
             */
            foreach ($customers as $customer) {
                if ($this->customer_repository->have_active_orders($customer)) {
                    throw new \Exception(trans('admin::app.customers.customers.index.datagrid.order-pending'));
                }
            }
            /**
             * After ensuring that they have no active orders delete the corresponding customer.
             */
            foreach ($customers as $customer) {
                Event::dispatch('customer.delete.before', $customer);
                $this->customer_repository->delete($customer->id);
                Event::dispatch('customer.delete.after', $customer);
            }
            return new Json_Response(['message' => trans('admin::app.customers.customers.index.datagrid.delete-success')]);
        } catch (\Exception $exception) {
            return new Json_Response(['message' => $exception->get_message()], 500);
        }
    }
}