<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Customers\Group_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Rules\Code;
use Webkul\Customer\Repositories\Customer_Group_Repository;
class Customer_Group_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Customer_Group_Repository $customer_group_repository)
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
            return datagrid(Group_Data_Grid::class)->process();
        }
        return view('admin::customers.groups.index');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['code' => ['required', 'unique:customer_groups,code', new Code()], 'name' => 'required']);
        Event::dispatch('customer.customer_group.create.before');
        $data = array_merge(request()->only(['code', 'name']), ['is_user_defined' => 1]);
        $customer_group = $this->customer_group_repository->create($data);
        Event::dispatch('customer.customer_group.create.after', $customer_group);
        return new Json_Response(['message' => trans('admin::app.customers.groups.index.create.success')]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(): Json_Response
    {
        $id = request()->input('id');
        $this->validate(request(), ['code' => ['required', 'unique:customer_groups,code,' . $id, new Code()], 'name' => 'required']);
        Event::dispatch('customer.customer_group.update.before', $id);
        $customer_group = $this->customer_group_repository->update(request()->only(['code', 'name']), $id);
        Event::dispatch('customer.customer_group.update.after', $customer_group);
        return new Json_Response(['message' => trans('admin::app.customers.groups.index.edit.success')]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $customer_group = $this->customer_group_repository->find_or_fail($id);
        if (!$customer_group->is_user_defined) {
            return new Json_Response(['message' => trans('admin::app.customers.groups.index.edit.group-default')], 400);
        }
        if ($customer_group->customers->count()) {
            return new Json_Response(['message' => trans('admin::app.customers.groups.customer-associate')], 400);
        }
        try {
            Event::dispatch('customer.customer_group.delete.before', $id);
            $this->customer_group_repository->delete($id);
            Event::dispatch('customer.customer_group.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.customers.groups.index.edit.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.customers.groups.index.edit.delete-failed')], 500);
    }
}