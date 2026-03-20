<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Json_Response;
use Illuminate\Http\Redirect_Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\Http_Foundation\Streamed_Response;
use Webkul\Admin\Http\Requests\Configuration_Form;
use Webkul\Core\Repositories\Core_Config_Repository;
class Configuration_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Core_Config_Repository $core_config_repository)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        if (request()->route('slug') && request()->route('slug2')) {
            return view('admin::configuration.edit');
        }
        return view('admin::configuration.index');
    }
    /**
     * Display a listing of the resource.
     */
    public function search(): Json_Response
    {
        $results = $this->core_config_repository->search(system_config()->get_items(), request()->query('query'));
        return new Json_Response(['data' => $results]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Configuration_Form $request): Redirect_Response
    {
        $data = $request->all();
        if (isset($data['sales']['carriers'])) {
            $at_least_one_carrier_enabled = false;
            foreach ($data['sales']['carriers'] as $carrier) {
                if ($carrier['active']) {
                    $at_least_one_carrier_enabled = true;
                    break;
                }
            }
            if (!$at_least_one_carrier_enabled) {
                session()->flash('error', trans('admin::app.configuration.index.enable-at-least-one-shipping'));
                return redirect()->back();
            }
        } elseif (isset($data['sales']['payment_methods'])) {
            $at_least_one_payment_method_enabled = false;
            foreach ($data['sales']['payment_methods'] as $payment_method) {
                if ($payment_method['active']) {
                    $at_least_one_payment_method_enabled = true;
                    break;
                }
            }
            if (!$at_least_one_payment_method_enabled) {
                session()->flash('error', trans('admin::app.configuration.index.enable-at-least-one-payment'));
                return redirect()->back();
            }
        }
        $this->core_config_repository->create($request->except(['_token', 'admin_locale']));
        session()->flash('success', trans('admin::app.configuration.index.save-message'));
        return redirect()->back();
    }
    /**
     * Download the file for the specified resource.
     */
    public function download(): Streamed_Response
    {
        $path = request()->route()->parameters()['path'];
        $file_name = 'configuration/' . $path;
        $config = $this->core_config_repository->find_one_by_field('value', $file_name);
        return Storage::download($config['value']);
    }
}