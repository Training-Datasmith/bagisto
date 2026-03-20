<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Data_Grid;

use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Data_Grid\Repositories\Saved_Filter_Repository;
class Saved_Filter_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Saved_Filter_Repository $saved_filter_repository)
    {
    }
    /**
     * Save filters to the database.
     */
    public function store()
    {
        $user_id = auth()->guard('admin')->user()->id;
        $this->validate(request(), ['name' => 'required|unique:datagrid_saved_filters,name,NULL,id,src,' . request('src') . ',user_id,' . $user_id]);
        Event::dispatch('datagrid.saved_filter.create.before');
        $saved_filter = $this->saved_filter_repository->create(['user_id' => $user_id, 'name' => request('name'), 'src' => request('src'), 'applied' => request('applied')]);
        Event::dispatch('datagrid.saved_filter.create.after', $saved_filter);
        return response()->json(['data' => $saved_filter, 'message' => trans('admin::app.components.datagrid.toolbar.filter.saved-success')]);
    }
    /**
     * Retrieves the saved filters.
     */
    public function get()
    {
        $saved_filters = $this->saved_filter_repository->find_where(['src' => request()->get('src'), 'user_id' => auth()->guard('admin')->user()->id]);
        return response()->json(['data' => $saved_filters]);
    }
    /**
     * Update the saved filter.
     */
    public function update(int $id)
    {
        $user_id = auth()->guard('admin')->user()->id;
        $this->validate(request(), ['name' => 'required|unique:datagrid_saved_filters,name,' . $id . ',id,src,' . request('src') . ',user_id,' . $user_id]);
        $saved_filter = $this->saved_filter_repository->find_one_where(['id' => $id, 'user_id' => auth()->guard('admin')->user()->id]);
        if (!$saved_filter) {
            return response()->json([], 404);
        }
        Event::dispatch('datagrid.saved_filter.update.before', $id);
        $updated_filter = $this->saved_filter_repository->update(request()->only(['name', 'src', 'applied']), $id);
        Event::dispatch('datagrid.saved_filter.update.after', $updated_filter);
        return response()->json(['data' => $updated_filter, 'message' => trans('admin::app.components.datagrid.toolbar.filter.updated-success')]);
    }
    /**
     * Delete the saved filter.
     */
    public function destroy(int $id)
    {
        Event::dispatch('datagrid.saved_filter.delete.before', $id);
        $success = $this->saved_filter_repository->delete_where(['id' => $id, 'user_id' => auth()->guard('admin')->user()->id]);
        Event::dispatch('datagrid.saved_filter.delete.after', $id);
        if (!$success) {
            return response()->json(['message' => trans('admin::app.components.datagrid.toolbar.filter.delete-error')]);
        }
        return response()->json(['message' => trans('admin::app.components.datagrid.toolbar.filter.delete-success')]);
    }
}