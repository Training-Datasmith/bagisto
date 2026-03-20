<?php

declare (strict_types=1);
namespace Webkul\CMS\Repositories;

use Illuminate\Database\Eloquent\Model_Not_Found_Exception;
use Webkul\CMS\Models\Page_Translation_Proxy;
use Webkul\Core\Eloquent\Repository;
class Page_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\CMS\Contracts\Page';
    }
    /**
     * @return \Webkul\CMS\Contracts\Page
     */
    public function create(array $data)
    {
        $model = $this->get_model();
        foreach (core()->get_all_locales() as $locale) {
            foreach ($model->translated_attributes as $attribute) {
                if (isset($data[$attribute])) {
                    $data[$locale->code][$attribute] = $data[$attribute];
                }
            }
            $data[$locale->code]['html_content'] = str_replace('=&gt;', '=>', $data[$locale->code]['html_content']);
        }
        $page = parent::create($data);
        $page->channels()->sync($data['channels']);
        return $page;
    }
    /**
     * @param  int  $id
     * @return \Webkul\CMS\Contracts\Page
     */
    public function update(array $data, $id)
    {
        $page = $this->find($id);
        $locale = $data['locale'] ?? app()->get_locale();
        $data[$locale]['html_content'] = str_replace('=&gt;', '=>', $data[$locale]['html_content']);
        $page = parent::update($data, $id);
        $page->channels()->sync($data['channels']);
        return $page;
    }
    /**
     * Checks slug is unique or not based on locale
     *
     * @param  int  $id
     * @param  string  $urlKey
     * @return bool
     */
    public function is_url_key_unique($id, $url_key)
    {
        $exists = Page_Translation_Proxy::model_class()::where('cms_page_id', '<>', $id)->where('url_key', $url_key)->limit(1)->select(\DB::raw(1))->exists();
        return !$exists;
    }
    /**
     * Retrieve category from slug
     *
     * @param  string  $urlKey
     * @return \Webkul\CMS\Contracts\Page
     */
    public function find_by_url_key($url_key)
    {
        return $this->model->where_translation('url_key', $url_key)->first();
    }
    /**
     * Retrieve category from slug
     *
     * @param  string  $urlKey
     * @return \Webkul\CMS\Contracts\Page|\Exception
     */
    public function find_by_url_key_or_fail($url_key)
    {
        $page = $this->model->where_translation('url_key', $url_key)->first();
        if ($page) {
            return $page;
        }
        throw (new Model_Not_Found_Exception())->set_model(get_class($this->model), $url_key);
    }
}