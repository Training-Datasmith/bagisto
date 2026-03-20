<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Marketing\Communications\Campaign_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Repositories\Campaign_Repository;
use Webkul\Marketing\Repositories\Template_Repository;
class Campaign_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Campaign_Repository $campaign_repository, protected Template_Repository $template_repository)
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
            return datagrid(Campaign_Data_Grid::class)->process();
        }
        return view('admin::marketing.communications.campaigns.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $templates = $this->template_repository->find_by_field('status', 'active');
        return view('admin::marketing.communications.campaigns.create', compact('templates'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $validated_data = $this->validate(request(), ['name' => 'required', 'subject' => 'required', 'marketing_template_id' => 'required', 'marketing_event_id' => 'required', 'channel_id' => 'required', 'customer_group_id' => 'required', 'status' => 'sometimes|required|in:0,1']);
        Event::dispatch('marketing.campaigns.create.before');
        $campaign = $this->campaign_repository->create($validated_data);
        Event::dispatch('marketing.campaigns.create.after', $campaign);
        session()->flash('success', trans('admin::app.marketing.communications.campaigns.create-success'));
        return redirect()->route('admin.marketing.communications.campaigns.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $campaign = $this->campaign_repository->find_or_fail($id);
        $templates = $this->template_repository->find_by_field('status', 'active');
        return view('admin::marketing.communications.campaigns.edit', compact('campaign', 'templates'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $validated_data = $this->validate(request(), ['name' => 'required', 'subject' => 'required', 'marketing_template_id' => 'required', 'marketing_event_id' => 'required', 'channel_id' => 'required', 'customer_group_id' => 'required']);
        Event::dispatch('marketing.campaigns.update.before', $id);
        $campaign = $this->campaign_repository->update([...$validated_data, 'status' => request()->input('status') ? 1 : 0], $id);
        Event::dispatch('marketing.campaigns.update.after', $campaign);
        session()->flash('success', trans('admin::app.marketing.communications.campaigns.update-success'));
        return redirect()->route('admin.marketing.communications.campaigns.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        try {
            Event::dispatch('marketing.campaigns.delete.before', $id);
            $this->campaign_repository->delete($id);
            Event::dispatch('marketing.campaigns.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.marketing.communications.campaigns.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.marketing.communications.campaigns.delete-failed', ['name' => 'admin::app.marketing.communications.campaigns.email-campaign'])], 500);
    }
}