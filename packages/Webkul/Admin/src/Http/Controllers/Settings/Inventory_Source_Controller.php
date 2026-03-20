<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Settings\Inventory_Sources_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Inventory_Source_Request;
use Webkul\Inventory\Repositories\Inventory_Source_Repository;
class Inventory_Source_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Inventory_Source_Repository $inventory_source_repository)
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
            return datagrid(Inventory_Sources_Data_Grid::class)->process();
        }
        return view('admin::settings.inventory-sources.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.inventory-sources.create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Inventory_Source_Request $inventory_source_request)
    {
        Event::dispatch('inventory.inventory_source.create.before');
        $data = request()->only(['code', 'name', 'description', 'latitude', 'longitude', 'priority', 'contact_name', 'contact_email', 'contact_number', 'contact_fax', 'country', 'state', 'city', 'street', 'postcode', 'status']);
        $inventory_source = $this->inventory_source_repository->create($data);
        Event::dispatch('inventory.inventory_source.create.after', $inventory_source);
        session()->flash('success', trans('admin::app.settings.inventory-sources.create-success'));
        return redirect()->route('admin.settings.inventory_sources.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $inventory_source = $this->inventory_source_repository->find_or_fail($id);
        return view('admin::settings.inventory-sources.edit', compact('inventorySource'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Inventory_Source_Request $inventory_source_request, int $id)
    {
        Event::dispatch('inventory.inventory_source.update.before', $id);
        if (!$inventory_source_request->status) {
            $inventory_source_request['status'] = 0;
        }
        $data = $inventory_source_request->only(['code', 'name', 'description', 'latitude', 'longitude', 'priority', 'contact_name', 'contact_email', 'contact_number', 'contact_fax', 'country', 'state', 'city', 'street', 'postcode', 'status']);
        $inventory_source = $this->inventory_source_repository->update($data, $id);
        Event::dispatch('inventory.inventory_source.update.after', $inventory_source);
        session()->flash('success', trans('admin::app.settings.inventory-sources.update-success'));
        return redirect()->route('admin.settings.inventory_sources.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $this->inventory_source_repository->find_or_fail($id);
        if ($this->inventory_source_repository->count() == 1) {
            return response()->json(['message' => trans('admin::app.settings.inventory-sources.last-delete-error')], 400);
        }
        try {
            Event::dispatch('inventory.inventory_source.delete.before', $id);
            $this->inventory_source_repository->delete($id);
            Event::dispatch('inventory.inventory_source.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.settings.inventory-sources.delete-success')]);
        } catch (\Exception $e) {
            report($e);
        }
        return new Json_Response(['message' => trans('admin::app.settings.inventory-sources.delete-failed', ['name' => 'admin::app.settings.inventory_sources.index.title'])], 500);
    }
}