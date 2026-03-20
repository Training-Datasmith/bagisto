<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\Authorizes_Requests;
use Illuminate\Foundation\Bus\Dispatches_Jobs;
use Illuminate\Foundation\Validation\Validates_Requests;
use Illuminate\Routing\Controller as BaseController;
class Controller extends Base_Controller
{
    use Authorizes_Requests;
    use Dispatches_Jobs;
    use Validates_Requests;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirect_to_login()
    {
        return redirect()->route('admin.session.create');
    }
}