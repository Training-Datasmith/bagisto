<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Data_Grids\Marketing\Communications\News_Letter_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\Subscribers_List_Repository;
class Subscription_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Subscribers_List_Repository $subscribers_list_repository)
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
            return datagrid(News_Letter_Data_Grid::class)->process();
        }
        return view('admin::marketing.communications.subscribers.index');
    }
    /**
     * Subscriber Details
     */
    public function edit(int $id): Json_Response
    {
        $subscriber = $this->subscribers_list_repository->find_or_fail($id);
        return new Json_Response(['data' => $subscriber]);
    }
    /**
     * To unsubscribe the user without deleting the resource of the subscribed
     *
     * @return void
     */
    public function update()
    {
        $validated_data = $this->validate(request(), ['id' => 'required', 'is_subscribed' => 'required|in:0,1']);
        $subscriber = $this->subscribers_list_repository->find_or_fail($validated_data['id']);
        $customer = $subscriber->customer;
        if ($customer) {
            $customer->subscribed_to_news_letter = $validated_data['is_subscribed'];
            $customer->save();
        }
        $result = $subscriber->update(['is_subscribed' => $validated_data['is_subscribed']]);
        if ($result) {
            return response()->json(['message' => trans('admin::app.marketing.communications.subscribers.index.edit.success')], 200);
        }
        return response()->json(['message' => trans('admin::app.marketing.communications.subscribers.index.edit.update-failed')], 500);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @return void
     */
    public function destroy(int $id)
    {
        try {
            $subscription = $this->subscribers_list_repository->find_or_fail($id);
            if ($subscription->customer) {
                $subscription->customer->subscribed_to_news_letter = false;
                $subscription->customer->save();
            }
            $subscription->delete();
            return response()->json(['message' => trans('admin::app.marketing.communications.subscribers.delete-success')], 200);
        } catch (\Exception $e) {
            report($e);
        }
        return response()->json(['message' => trans('admin::app.marketing.communications.subscribers.delete-failed')], 500);
    }
}