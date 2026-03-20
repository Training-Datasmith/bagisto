<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Customers\Review_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Admin\Http\Requests\Mass_Update_Request;
use Webkul\Product\Repositories\Product_Review_Repository;
class Review_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Product_Review_Repository $product_review_repository)
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
            return datagrid(Review_Data_Grid::class)->process();
        }
        return view('admin::customers.reviews.index');
    }
    /**
     * Review Details
     */
    public function edit(int $id): Json_Response
    {
        $review = $this->product_review_repository->with(['images', 'product'])->find_or_fail($id);
        $review->date = $review->created_at->format('Y-m-d');
        return new Json_Response(['data' => $review]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), ['status' => 'required|in:approved,disapproved,pending']);
        Event::dispatch('customer.review.update.before', $id);
        $review = $this->product_review_repository->update(['status' => request()->input('status')], $id);
        Event::dispatch('customer.review.update.after', $review);
        return new Json_Response(['message' => trans('admin::app.customers.reviews.index.edit.update-success')]);
    }
    /**
     * Delete the review of the current product
     */
    public function destroy(int $id): Json_Response
    {
        try {
            Event::dispatch('customer.review.delete.before', $id);
            $this->product_review_repository->delete($id);
            Event::dispatch('customer.review.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.customers.reviews.index.datagrid.delete-success', ['name' => 'Review'])]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.response.delete-failed', ['name' => 'Review'])], 500);
        }
    }
    /**
     * Mass delete the reviews on the products.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $indices = $mass_destroy_request->input('indices');
        try {
            foreach ($indices as $index) {
                Event::dispatch('customer.review.delete.before', $index);
                $this->product_review_repository->delete($index);
                Event::dispatch('customer.review.delete.after', $index);
            }
            return new Json_Response(['message' => trans('admin::app.customers.reviews.index.datagrid.mass-delete-success')], 200);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
    /**
     * Mass approve the reviews on the products.
     */
    public function mass_update(Mass_Update_Request $mass_update_request): Json_Response
    {
        $indices = $mass_update_request->input('indices');
        foreach ($indices as $id) {
            Event::dispatch('customer.review.update.before', $id);
            $review = $this->product_review_repository->update(['status' => $mass_update_request->input('value')], $id);
            Event::dispatch('customer.review.update.after', $review);
        }
        return new Json_Response(['message' => trans('admin::app.customers.reviews.index.datagrid.mass-update-success')], 200);
    }
}