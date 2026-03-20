<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

class Admin
{
    /**
     * Send mail on updating password.
     *
     * @param  \Webkul\User\Models\Admin  $admin
     * @return void
     */
    public function after_password_updated($admin)
    {
    }
}