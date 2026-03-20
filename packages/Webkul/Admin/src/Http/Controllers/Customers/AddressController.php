<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Address_Request;
use Webkul\Admin\Http\Resources\Address_Resource;
use Webkul\Customer\Repositories\Customer_Address_Repository;
use Webkul\Customer\Repositories\Customer_Repository;
class Address_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Customer_Repository $customer_repository, protected Customer_Address_Repository $customer_address_repository)
    {
    }
    /**
     * Fetch address by customer id.
     *
     * @return \Illuminate\View\View
     */
    public function index(int $id)
    {
        $customer = $this->customer_repository->find($id);
        return view('admin::customers.addresses.index', compact('customer'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(int $id)
    {
        $customer = $this->customer_repository->find($id);
        return view('admin::customers.addresses.create', compact('customer'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(int $id, Address_Request $request): Json_Response
    {
        $data = array_merge($request->only(['customer_id', 'company_name', 'vat_id', 'first_name', 'last_name', 'address', 'city', 'country', 'state', 'postcode', 'phone', 'email', 'default_address']), ['address' => implode(PHP_EOL, array_filter(request()->input('address')))]);
        Event::dispatch('customer.addresses.create.before');
        if (!empty($data['default_address'])) {
            $this->customer_address_repository->where('customer_id', $data['customer_id'])->where('default_address', 1)->update(['default_address' => 0]);
        }
        $address = $this->customer_address_repository->create(array_merge($data, ['customer_id' => $id]));
        Event::dispatch('customer.addresses.create.after', $address);
        return new Json_Response(['message' => trans('admin::app.customers.customers.view.address.create-success'), 'data' => new Address_Resource($address)]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $address = $this->customer_address_repository->find($id);
        return view('admin::customers.addresses.edit', compact('address'));
    }
    /**
     * Edit's the pre made resource of customer called address.
     */
    public function update(int $id, Address_Request $request): Json_Response
    {
        $data = array_merge($request->only(['customer_id', 'company_name', 'vat_id', 'first_name', 'last_name', 'address', 'city', 'country', 'state', 'postcode', 'phone', 'email', 'default_address']), ['address' => implode(PHP_EOL, array_filter(request()->input('address')))]);
        Event::dispatch('customer.addresses.update.before', $id);
        if (!empty($data['default_address'])) {
            $this->customer_address_repository->where('customer_id', $data['customer_id'])->where('default_address', 1)->update(['default_address' => 0]);
        }
        $address = $this->customer_address_repository->update($data, $id);
        Event::dispatch('customer.addresses.update.after', $address);
        return new Json_Response(['message' => trans('admin::app.customers.customers.view.address.update-success'), 'data' => new Address_Resource($address)]);
    }
    /**
     * To change the default address or make the default address,
     * by default when first address is created will be the default address.
     *
     * @return \Illuminate\Http\Response
     */
    public function make_default($id)
    {
        if ($default = $this->customer_address_repository->find_one_where(['customer_id' => $id, 'default_address' => 1])) {
            $default->update(['default_address' => 0]);
        }
        $address = $this->customer_address_repository->find_one_where(['id' => request('set_as_default'), 'customer_id' => $id]);
        $address->update(['default_address' => 1]);
        return new Json_Response(['message' => trans('admin::app.customers.customers.view.address.set-default-success'), 'data' => $address]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        Event::dispatch('customer.addresses.delete.before', $id);
        $this->customer_address_repository->delete($id);
        Event::dispatch('customer.addresses.delete.after', $id);
        return new Json_Response(['message' => trans('admin::app.customers.customers.view.address.address-delete-success')]);
    }
}