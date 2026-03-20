<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\CMS;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\CMS\Cms_Page_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\CMS\Repositories\Page_Repository;
use Webkul\Core\Rules\Slug;
class Page_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Page_Repository $page_repository)
    {
    }
    /**
     * Loads the index page showing the static pages resources.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Cms_Page_Data_Grid::class)->process();
        }
        return view('admin::cms.index');
    }
    /**
     * To create a new CMS page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::cms.create');
    }
    /**
     * To store a new CMS page in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), ['url_key' => ['required', 'unique:cms_page_translations,url_key', new \Webkul\Core\Rules\Slug()], 'page_title' => 'required', 'html_content' => 'required', 'channels' => 'required|array|min:1']);
        Event::dispatch('cms.page.create.before');
        $data = request()->only(['page_title', 'channels', 'html_content', 'meta_title', 'url_key', 'meta_keywords', 'meta_description']);
        $data['html_content'] = clean_content($data['html_content']);
        $page = $this->page_repository->create($data);
        Event::dispatch('cms.page.create.after', $page);
        session()->flash('success', trans('admin::app.cms.create-success'));
        return redirect()->route('admin.cms.index');
    }
    /**
     * To edit a previously created CMS page.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $page = $this->page_repository->find_or_fail($id);
        return view('admin::cms.edit', compact('page'));
    }
    /**
     * To update the previously created CMS page in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $locale = core()->get_requested_locale_code();
        $this->validate(request(), [$locale . '.url_key' => ['required', new Slug(), function ($attribute, $value, $fail) use ($id) {
            if (!$this->page_repository->is_url_key_unique($id, $value)) {
                $fail(trans('admin::app.cms.index.already-taken', ['name' => 'Page']));
            }
        }], $locale . '.page_title' => 'required', $locale . '.html_content' => 'required', 'channels' => 'required|array|min:1']);
        Event::dispatch('cms.page.update.before', $id);
        $locale_data = request()->input($locale);
        $locale_data['html_content'] = clean_content($locale_data['html_content']);
        $page = $this->page_repository->update([$locale => $locale_data, 'channels' => request()->input('channels'), 'locale' => $locale], $id);
        Event::dispatch('cms.page.update.after', $page);
        session()->flash('success', trans('admin::app.cms.update-success'));
        return redirect()->route('admin.cms.index');
    }
    /**
     * To delete the previously create CMS page.
     */
    public function delete(int $id): Json_Response
    {
        try {
            Event::dispatch('cms.page.delete.before', $id);
            $this->page_repository->delete($id);
            Event::dispatch('cms.page.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.cms.delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.cms.no-resource')]);
        }
    }
    /**
     * To mass delete the CMS resource from storage.
     */
    public function mass_delete(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $indices = $mass_destroy_request->input('indices');
        foreach ($indices as $index) {
            Event::dispatch('cms.page.delete.before', $index);
            $this->page_repository->delete($index);
            Event::dispatch('cms.page.delete.after', $index);
        }
        return new Json_Response(['message' => trans('admin::app.cms.index.datagrid.mass-delete-success')], 200);
    }
}