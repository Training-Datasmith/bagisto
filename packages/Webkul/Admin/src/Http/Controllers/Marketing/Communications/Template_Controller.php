<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Marketing\Communications\Email_Template_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Repositories\Template_Repository;
class Template_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Template_Repository $template_repository)
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
            return datagrid(Email_Template_Data_Grid::class)->process();
        }
        return view('admin::marketing.communications.templates.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::marketing.communications.templates.create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), ['name' => 'required', 'status' => 'required|in:active,inactive,draft', 'content' => 'required']);
        Event::dispatch('marketing.templates.create.before');
        $data = request()->only(['name', 'status', 'content']);
        $data['content'] = clean_content($data['content']);
        $template = $this->template_repository->create($data);
        Event::dispatch('marketing.templates.create.after', $template);
        session()->flash('success', trans('admin::app.marketing.communications.templates.create.create-success'));
        return redirect()->route('admin.marketing.communications.email_templates.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $template = $this->template_repository->find_or_fail($id);
        return view('admin::marketing.communications.templates.edit', compact('template'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), ['name' => 'required', 'status' => 'required|in:active,inactive,draft', 'content' => 'required']);
        Event::dispatch('marketing.templates.update.before', $id);
        $data = request()->only(['name', 'status', 'content']);
        $data['content'] = clean_content($data['content']);
        $template = $this->template_repository->update($data, $id);
        Event::dispatch('marketing.templates.update.after', $template);
        session()->flash('success', trans('admin::app.marketing.communications.templates.edit.update-success'));
        return redirect()->route('admin.marketing.communications.email_templates.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        try {
            Event::dispatch('marketing.templates.delete.before', $id);
            $this->template_repository->delete($id);
            Event::dispatch('marketing.templates.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.marketing.communications.templates.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.marketing.communications.templates.delete-failed', ['name' => 'admin::app.marketing.communications.templates.email-template'])], 400);
    }
}