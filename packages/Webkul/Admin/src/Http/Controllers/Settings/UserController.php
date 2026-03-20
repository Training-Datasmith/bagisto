<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Admin\Data_Grids\Settings\User_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\User_Form;
use Webkul\User\Repositories\Admin_Repository;
use Webkul\User\Repositories\Role_Repository;
class User_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Admin_Repository $admin_repository, protected Role_Repository $role_repository)
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
            return datagrid(User_Data_Grid::class)->process();
        }
        $roles = $this->role_repository->all();
        return view('admin::settings.users.index', compact('roles'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(User_Form $request): Json_Response
    {
        $data = $request->only(['name', 'email', 'password', 'password_confirmation', 'role_id', 'status']);
        if ($data['password'] ?? null) {
            $data['password'] = bcrypt($data['password']);
            $data['api_token'] = Str::random(80);
        }
        Event::dispatch('user.admin.create.before');
        $admin = $this->admin_repository->create($data);
        if (request()->has_file('image')) {
            $admin->image = current(request()->file('image'))->store('admins/' . $admin->id);
            $admin->save();
        }
        Event::dispatch('user.admin.create.after', $admin);
        return new Json_Response(['message' => trans('admin::app.settings.users.create-success')]);
    }
    /**
     * User Details
     *
     * @param  int  $id
     */
    public function edit($id): Json_Response
    {
        $user = $this->admin_repository->find_or_fail($id);
        $roles = $this->role_repository->all();
        return new Json_Response(['roles' => $roles, 'user' => $user]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(User_Form $request): Json_Response
    {
        $id = request()->id;
        $data = $this->prepare_user_data($request, $id);
        if ($data instanceof \Illuminate\Http\Redirect_Response) {
            return new Json_Response(['message' => trans('admin::app.settings.users.update-success')]);
        }
        Event::dispatch('user.admin.update.before', $id);
        /**
         * If the request has image.image, it means the request doesn't upload a new image.
         * So we need to remove it from the data to prevent the image from being overwritten.
         */
        if (request()->has('image.image')) {
            unset($data['image']);
        }
        $admin = $this->admin_repository->update($data, $id);
        if (request()->has_file('image')) {
            $admin->image = current(request()->file('image'))->store('admins/' . $admin->id);
        } else if (!request()->has('image.image')) {
            if (!empty(request()->input('image.image'))) {
                Storage::delete($admin->image);
            }
            $admin->image = null;
        }
        $admin->save();
        if (!empty($data['password'])) {
            Event::dispatch('admin.password.update.after', $admin);
        }
        Event::dispatch('user.admin.update.after', $admin);
        return new Json_Response(['message' => trans('admin::app.settings.users.update-success')]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     */
    public function destroy($id): Json_Response
    {
        if ($this->admin_repository->count() == 1) {
            return new Json_Response(['message' => trans('admin::app.settings.users.last-delete-error')], 400);
        }
        if (auth()->guard('admin')->user()->id == $id) {
            return new Json_Response(['message' => trans('admin::app.settings.users.delete-self-error')], 403);
        }
        try {
            Event::dispatch('user.admin.delete.before', $id);
            $this->admin_repository->delete($id);
            Event::dispatch('user.admin.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.settings.users.delete-success')], 200);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.settings.users.delete-failed')], 500);
    }
    /**
     * Show the form for confirming the user password.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function confirm($id)
    {
        $user = $this->admin_repository->find_or_fail($id);
        return view('admin::customers.customers.confirm-password', compact('user'));
    }
    /**
     * Destroy current after confirming.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy_self(): Json_Response
    {
        $password = request()->input('password');
        if (Hash::check($password, auth()->guard('admin')->user()->password)) {
            if ($this->admin_repository->count() == 1) {
                session()->flash('error', trans('admin::app.settings.users.delete-last'));
            } else {
                $id = auth()->guard('admin')->user()->id;
                Event::dispatch('user.admin.delete.before', $id);
                $this->admin_repository->delete($id);
                Event::dispatch('user.admin.delete.after', $id);
                return new Json_Response(['redirectUrl' => route('admin.session.create'), 'message' => trans('admin::app.settings.users.delete-success')]);
            }
        } else {
            return new Json_Response(['message' => trans('admin::app.settings.users.incorrect-password')], 404);
        }
    }
    /**
     * Prepare user data.
     *
     * @param  int  $id
     * @return array|\Illuminate\Http\RedirectResponse
     */
    private function prepare_user_data(User_Form $request, $id)
    {
        $data = $request->validated();
        $user = $this->admin_repository->find($id);
        /**
         * Password check.
         */
        if (!$data['password']) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }
        /**
         * Is user with `permission_type` all changed status.
         */
        $data['status'] = isset($data['status']);
        $is_status_changed_to_inactive = !$data['status'] && (bool) $user->status;
        if ($is_status_changed_to_inactive && (auth()->guard('admin')->user()->id === (int) $id && $this->admin_repository->count_admins_with_all_access_and_active_status() === 1)) {
            return $this->cannot_change_redirect_response('status');
        }
        /**
         * Is user with `permission_type` all role changed.
         */
        $is_role_changed = $user->role->permission_type === 'all' && isset($data['role_id']) && (int) $data['role_id'] !== $user->role_id;
        if ($is_role_changed && $this->admin_repository->count_admins_with_all_access() === 1) {
            return $this->cannot_change_redirect_response('role');
        }
        return $data;
    }
    /**
     * Cannot change redirect response.
     */
    private function cannot_change_redirect_response(string $column_name): \Illuminate\Http\Redirect_Response
    {
        session()->flash('error', trans('admin::app.settings.users.cannot-change', ['name' => $column_name]));
        return redirect()->route('admin.settings.users.index');
    }
}