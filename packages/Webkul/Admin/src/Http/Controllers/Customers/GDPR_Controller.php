<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Customers\Gdpr_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Customer\Repositories\Customer_Repository;
use Webkul\GDPR\Repositories\Gdpr_Data_Request_Repository;
class Gdpr_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Customer_Repository $customer_repository, protected Gdpr_Data_Request_Repository $gdpr_data_request_repository)
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
            return datagrid(Gdpr_Data_Grid::class)->process();
        }
        return view('admin::customers.gdpr.index');
    }
    /**
     * Method to show the form for updating a new Data Request.
     */
    public function edit(int $id)
    {
        try {
            $request = $this->gdpr_data_request_repository->find_or_fail($id);
            return new Json_Response(['data' => $request]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.customers.gdpr.index.attribute-reason-error'), 'error' => $e->get_message()], 500);
        }
    }
    /**
     * Method to update the Data Request information.
     */
    public function update(int $id)
    {
        try {
            Event::dispatch('customer.gdpr-request.update.before');
            $gdpr_request = $this->gdpr_data_request_repository->update(request()->all(), $id);
            Event::dispatch('customer.account.gdpr-request.update.after', $gdpr_request);
            return response()->json(['message' => trans(key: 'admin::app.customers.gdpr.index.update-success')]);
        } catch (\Exception $e) {
            return response()->json(['message' => trans('admin::app.customers.gdpr.index.update-success-unsent-email'), 'error' => $e->get_message()], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function delete(int $id)
    {
        try {
            $gdpr_request = $this->gdpr_data_request_repository->find_or_fail($id);
            $gdpr_request->delete();
            return new Json_Response(['message' => trans('admin::app.customers.gdpr.index.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.customers.gdpr.index.attribute-reason-error')], 500);
    }
}