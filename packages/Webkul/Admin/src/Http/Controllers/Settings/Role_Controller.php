<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Settings\Roles_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Repositories\Admin_Repository;
use Webkul\User\Repositories\Role_Repository;
class Role_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Role_Repository $role_repository, protected Admin_Repository $admin_repository)
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
            return datagrid(Roles_Data_Grid::class)->process();
        }
        return view('admin::settings.roles.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.roles.create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), ['name' => 'required', 'permission_type' => 'required|in:all,custom', 'description' => 'required']);
        if (request('permission_type') == 'custom') {
            $this->validate(request(), ['permissions' => 'required']);
        }
        Event::dispatch('user.role.create.before');
        $data = request()->only(['name', 'description', 'permission_type', 'permissions']);
        $role = $this->role_repository->create($data);
        Event::dispatch('user.role.create.after', $role);
        session()->flash('success', trans('admin::app.settings.roles.create-success'));
        return redirect()->route('admin.settings.roles.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $role = $this->role_repository->find_or_fail($id);
        return view('admin::settings.roles.edit', compact('role'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), ['name' => 'required', 'permission_type' => 'required|in:all,custom', 'description' => 'required']);
        /**
         * Check for other admins if the role has been changed from all to custom.
         */
        $is_changed_from_all = request('permission_type') == 'custom' && $this->role_repository->find($id)->permission_type == 'all';
        if ($is_changed_from_all && $this->admin_repository->count_admins_with_all_access() === 1) {
            session()->flash('error', trans('admin::app.settings.roles.being-used'));
            return redirect()->route('admin.settings.roles.index');
        }
        $data = array_merge(request()->only(['name', 'description', 'permission_type']), ['permissions' => request()->has('permissions') ? request('permissions') : []]);
        Event::dispatch('user.role.update.before', $id);
        $role = $this->role_repository->update($data, $id);
        Event::dispatch('user.role.update.after', $role);
        session()->flash('success', trans('admin::app.settings.roles.update-success'));
        return redirect()->route('admin.settings.roles.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $role = $this->role_repository->find_or_fail($id);
        if ($role->admins->count() >= 1) {
            return new Json_Response(['message' => trans('admin::app.settings.roles.being-used', ['name' => 'admin::app.settings.roles.index.title', 'source' => 'admin::app.settings.roles.index.admin-user'])], 400);
        }
        if ($this->role_repository->count() == 1) {
            return new Json_Response(['message' => trans('admin::app.settings.roles.last-delete-error')], 400);
        }
        try {
            Event::dispatch('user.role.delete.before', $id);
            $this->role_repository->delete($id);
            Event::dispatch('user.role.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.settings.roles.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.settings.roles.delete-failed')], 500);
    }
}