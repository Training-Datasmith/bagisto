<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Marketing\Communications\Event_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Repositories\Event_Repository;
class Event_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Event_Repository $event_repository)
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
            return datagrid(Event_Data_Grid::class)->process();
        }
        return view('admin::marketing.communications.events.index');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->validate(request(), ['name' => 'required', 'description' => 'required', 'date' => 'date|required']);
        Event::dispatch('marketing.events.create.before');
        $event = $this->event_repository->create(request()->only(['name', 'description', 'date']));
        Event::dispatch('marketing.events.create.after', $event);
        return response()->json(['message' => trans('admin::app.marketing.communications.events.index.create.success')], 200);
    }
    /**
     * Event Details
     */
    public function edit(int $id): Json_Response
    {
        if ($id == 1) {
            return new Json_Response(['message' => trans('admin::app.marketing.communications.events.edit-error')]);
        }
        $event = $this->event_repository->find_or_fail($id);
        return new Json_Response($event);
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update()
    {
        $id = request()->id;
        $this->validate(request(), ['name' => 'required', 'description' => 'required', 'date' => 'date|required']);
        Event::dispatch('marketing.events.update.before', $id);
        $event = $this->event_repository->update(request()->only(['name', 'description', 'date']), $id);
        Event::dispatch('marketing.events.update.after', $event);
        return response()->json(['message' => trans('admin::app.marketing.communications.events.index.edit.success')], 200);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $this->event_repository->find_or_fail($id);
        try {
            Event::dispatch('marketing.events.delete.before', $id);
            $this->event_repository->delete($id);
            Event::dispatch('marketing.events.delete.after', $id);
            return response()->json(['message' => trans('admin::app.marketing.communications.events.delete-success')], 200);
        } catch (\Exception $e) {
        }
        return response()->json(['message' => trans('admin::app.marketing.communications.events.delete-failed', ['name' => 'admin::app.marketing.communications.events.index.event'])], 500);
    }
}