<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\User;

use Webkul\Admin\Http\Controllers\Controller;
class Session_Controller extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard.index');
        }
        if (strpos(url()->previous(), 'admin') !== false) {
            $intended_url = url()->previous();
        } else {
            $intended_url = route('admin.dashboard.index');
        }
        session()->put('url.intended', $intended_url);
        return view('admin::users.sessions.create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), ['email' => 'required|email', 'password' => 'required']);
        $remember = request('remember');
        if (!auth()->guard('admin')->attempt(request(['email', 'password']), $remember)) {
            session()->flash('error', trans('admin::app.settings.users.login-error'));
            return redirect()->back();
        }
        if (!auth()->guard('admin')->user()->status) {
            session()->flash('warning', trans('admin::app.settings.users.activate-warning'));
            auth()->guard('admin')->logout();
            return redirect()->route('admin.session.create');
        }
        if (!bouncer()->has_permission('dashboard')) {
            $all_permissions = collect(config('acl'));
            $permissions = auth()->guard('admin')->user()->role->permissions;
            foreach ($permissions as $permission) {
                if (bouncer()->has_permission($permission)) {
                    $permission_details = $all_permissions->first_where('key', $permission);
                    // If key is single level (no dots), find the first child entry
                    if (!str_contains($permission, '.')) {
                        $child_permission = $all_permissions->first(function ($item) use ($permission) {
                            return str_starts_with($item['key'], $permission . '.') && substr_count($item['key'], '.') === 1 && bouncer()->has_permission($item['key']);
                        });
                        if ($child_permission) {
                            return redirect()->route($child_permission['route']);
                        }
                    }
                    return redirect()->route($permission_details['route']);
                }
            }
        }
        return redirect()->intended(route('admin.dashboard.index'));
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        auth()->guard('admin')->logout();
        return redirect()->route('admin.session.create');
    }
}