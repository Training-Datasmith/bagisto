<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Auth\Events\Password_Reset;
use Illuminate\Foundation\Auth\Resets_Passwords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
class Reset_Password_Controller extends Controller
{
    use Resets_Passwords;
    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create($token = null)
    {
        return view('admin::users.reset-password.create')->with(['token' => $token, 'email' => request('email')]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        try {
            $this->validate(request(), ['token' => 'required', 'email' => 'required|email', 'password' => 'required|confirmed|min:6']);
            $response = $this->broker()->reset(request(['email', 'password', 'password_confirmation', 'token']), function ($admin, $password) {
                $this->reset_password($admin, $password);
            });
            if ($response == Password::PASSWORD_RESET) {
                return redirect()->route('admin.dashboard.index');
            }
            return back()->with_input(request(['email']))->with_errors(['email' => trans($response)]);
        } catch (\Exception $e) {
            session()->flash('error', trans($e->get_message()));
            return redirect()->back();
        }
    }
    /**
     * Reset the given admin's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $admin
     * @param  string  $password
     * @return void
     */
    protected function reset_password($admin, $password)
    {
        $admin->password = Hash::make($password);
        $admin->set_remember_token(Str::random(60));
        $admin->save();
        event(new Password_Reset($admin));
        auth()->guard('admin')->login($admin);
    }
    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker('admins');
    }
}