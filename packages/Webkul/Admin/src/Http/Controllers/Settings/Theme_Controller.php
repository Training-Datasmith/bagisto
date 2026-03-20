<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Data_Grids\Theme\Theme_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Admin\Http\Requests\Mass_Update_Request;
use Webkul\Theme\Repositories\Theme_Customization_Repository;
class Theme_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public Theme_Customization_Repository $theme_customization_repository)
    {
    }
    /**
     * Display a listing resource for the available tax rates.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Theme_Data_Grid::class)->process();
        }
        return view('admin::settings.themes.index');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function store()
    {
        if (request()->has('id')) {
            $this->validate(request(), [core()->get_requested_locale_code() . '.options.*.image' => 'image|extensions:jpeg,jpg,png,svg,webp']);
            $theme = $this->theme_customization_repository->find(request()->input('id'));
            return $this->theme_customization_repository->upload_image(request()->all(), $theme);
        }
        $validated = $this->validate(request(), ['name' => 'required', 'sort_order' => 'required|numeric', 'type' => 'required|in:product_carousel,category_carousel,static_content,image_carousel,footer_links,services_content', 'channel_id' => 'required|in:' . implode(',', core()->get_all_channels()->pluck('id')->to_array()), 'theme_code' => 'required']);
        Event::dispatch('theme_customization.create.before');
        $theme = $this->theme_customization_repository->create($validated);
        Event::dispatch('theme_customization.create.after', $theme);
        return new Json_Response(['redirect_url' => route('admin.settings.themes.edit', $theme->id)]);
    }
    /**
     * Edit the theme
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $theme = $this->theme_customization_repository->find($id);
        return view('admin::settings.themes.edit', compact('theme'));
    }
    /**
     * Update the specified resource
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id)
    {
        $this->validate(request(), ['name' => 'required', 'sort_order' => 'required|numeric', 'type' => 'required|in:product_carousel,category_carousel,static_content,image_carousel,footer_links,services_content', 'channel_id' => 'required|in:' . implode(',', core()->get_all_channels()->pluck('id')->to_array()), 'theme_code' => 'required']);
        $locale = request('locale');
        $data = request()->only('locale', 'type', 'name', 'sort_order', 'channel_id', 'theme_code', 'status', $locale);
        Event::dispatch('theme_customization.update.before', $id);
        $data['status'] = request()->input('status') == 'on';
        $theme = $this->theme_customization_repository->update($data, $id);
        Event::dispatch('theme_customization.update.after', $theme);
        session()->flash('success', trans('admin::app.settings.themes.update-success'));
        return redirect()->route('admin.settings.themes.index');
    }
    /**
     * Delete a specified theme.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        Event::dispatch('theme_customization.delete.before', $id);
        $this->theme_customization_repository->delete($id);
        Storage::delete_directory('theme/' . $id);
        Event::dispatch('theme_customization.delete.after', $id);
        return new Json_Response(['message' => trans('admin::app.settings.themes.delete-success')], 200);
    }
    public function mass_update(Mass_Update_Request $mass_update_request): Json_Response
    {
        $selected_theme_ids = $mass_update_request->input('indices');
        $this->theme_customization_repository->mass_update_status(['status' => $mass_update_request->input('value')], $selected_theme_ids);
        return new Json_Response(['message' => trans('admin::app.settings.themes.update-success')]);
    }
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $selected_theme_ids = $mass_destroy_request->input('indices');
        foreach ($selected_theme_ids as $theme_id) {
            $this->theme_customization_repository->delete($theme_id);
        }
        return new Json_Response(['message' => trans('admin::app.settings.themes.update-success')]);
    }
}