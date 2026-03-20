<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers;

use Webkul\Notification\Repositories\Notification_Repository;
class Notification_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Notification_Repository $notification_repository)
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::notifications.index');
    }
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function get_notifications()
    {
        $params = request()->except('page');
        $search_results = count($params) ? $this->notification_repository->get_params_data($params) : $this->notification_repository->get_all();
        $results = isset($search_results['notifications']) ? $search_results['notifications'] : $search_results;
        $status_count = isset($search_results['status_counts']) ? $search_results['status_counts'] : '';
        return ['search_results' => $results, 'status_count' => $status_count, 'total_unread' => $this->notification_repository->where('read', 0)->count()];
    }
    /**
     * Update the notification is reade or not.
     *
     * @param  int  $orderId
     * @return \Illuminate\View\View
     */
    public function viewed_notifications($order_id)
    {
        if ($notification = $this->notification_repository->where('order_id', $order_id)->first()) {
            $notification->read = 1;
            $notification->save();
            return redirect()->route('admin.sales.orders.view', $order_id);
        }
        abort(404);
    }
    /**
     * Update the notification is reade or not.
     *
     * @return array
     */
    public function read_all_notifications()
    {
        $this->notification_repository->where('read', 0)->update(['read' => 1]);
        $search_results = $this->notification_repository->get_params_data(['limit' => 5, 'read' => 0]);
        return ['search_results' => $search_results, 'total_unread' => $this->notification_repository->where('read', 0)->count(), 'success_message' => trans('admin::app.notifications.marked-success')];
    }
}