<?php

declare (strict_types=1);
namespace Webkul\Core\Repositories;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
class Core_Config_Repository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Core\Contracts\CoreConfig';
    }
    /**
     * Create core configuration.
     */
    public function create(array $data)
    {
        Event::dispatch('core.configuration.save.before');
        if ($data['locale'] || $data['channel']) {
            $locale = $data['locale'];
            $channel = $data['channel'];
            unset($data['locale']);
            unset($data['channel']);
        }
        foreach ($data as $method => $field_data) {
            $recursive_data = $this->recursive_array($field_data, $method);
            foreach ($recursive_data as $field_name => $value) {
                $field = core()->get_config_field($field_name);
                $channel_based = !empty($field['channel_based']);
                $locale_based = !empty($field['locale_based']);
                if (gettype($value) == 'array' && !isset($value['delete'])) {
                    $value = implode(',', $value);
                }
                if (!empty($field['channel_based'])) {
                    if (!empty($field['locale_based'])) {
                        $core_config_value = $this->model->where('code', $field_name)->where('locale_code', $locale)->where('channel_code', $channel)->get();
                    } else {
                        $core_config_value = $this->model->where('code', $field_name)->where('channel_code', $channel)->get();
                    }
                } else if (!empty($field['locale_based'])) {
                    $core_config_value = $this->model->where('code', $field_name)->where('locale_code', $locale)->get();
                } else {
                    $core_config_value = $this->model->where('code', $field_name)->get();
                }
                if (request()->has_file($field_name)) {
                    $value = request()->file($field_name)->store('configuration');
                }
                if (!count($core_config_value)) {
                    parent::create(['code' => $field_name, 'value' => $value, 'locale_code' => $locale_based ? $locale : null, 'channel_code' => $channel_based ? $channel : null]);
                } else {
                    foreach ($core_config_value as $core_config) {
                        if (request()->has_file($field_name)) {
                            Storage::delete($core_config['value']);
                        }
                        if (isset($value['delete'])) {
                            parent::delete($core_config['id']);
                        } else {
                            parent::update(['code' => $field_name, 'value' => $value, 'locale_code' => $locale_based ? $locale : null, 'channel_code' => $channel_based ? $channel : null], $core_config->id);
                        }
                    }
                }
            }
        }
        Event::dispatch('core.configuration.save.after');
    }
    /**
     * Get the configuration title.
     */
    protected function get_translated_title(mixed $configuration): string
    {
        if (method_exists($configuration, 'getTitle') && !is_null($configuration->get_title())) {
            return trans($configuration->get_title());
        }
        if (method_exists($configuration, 'getName') && !is_null($configuration->get_name())) {
            return trans($configuration->get_name());
        }
        return '';
    }
    /**
     * Get children and fields.
     */
    protected function get_children_and_fields(mixed $configuration, string $search_term, array $path, array &$results): void
    {
        if (method_exists($configuration, 'getChildren') || method_exists($configuration, 'getFields')) {
            $children = $configuration->have_children() ? $configuration->get_children() : $configuration->get_fields();
            $temp_path = array_merge($path, [['key' => $configuration->get_key() ?? null, 'title' => $this->get_translated_title($configuration)]]);
            $results = array_merge($results, $this->search($children, $search_term, $temp_path));
        }
    }
    /**
     * Search configuration.
     *
     * @param  array  $items
     */
    public function search(Collection $items, string $search_term, array $path = []): array
    {
        $results = [];
        foreach ($items as $configuration) {
            $title = $this->get_translated_title($configuration);
            if (stripos($title, $search_term) !== false && count($path)) {
                $query_param = $path[1]['key'] ?? $configuration->get_key();
                $results[] = ['title' => implode(' > ', [...Arr::pluck($path, 'title'), $title]), 'url' => route('admin.configuration.index', Str::replace('.', '/', $query_param))];
            }
            $this->get_children_and_fields($configuration, $search_term, $path, $results);
        }
        return $results;
    }
    /**
     * Recursive array.
     *
     * @return array
     */
    public function recursive_array(array $form_data, string $method, array &$data = [], array &$recursive_array_data = [])
    {
        foreach ($form_data as $form => $form_value) {
            $value = $method . '.' . $form;
            if (is_array($form_value)) {
                $dim = $this->count_dim($form_value);
                if ($dim > 1) {
                    $this->recursive_array($form_value, $value, $data, $recursive_array_data);
                } elseif ($dim == 1) {
                    $data[$value] = $form_value;
                }
            }
        }
        foreach ($data as $key => $value) {
            $field = core()->get_config_field($key);
            if ($field) {
                $recursive_array_data[$key] = $value;
            } else {
                foreach ($value as $key1 => $val) {
                    $recursive_array_data[$key . '.' . $key1] = $val;
                }
            }
        }
        return $recursive_array_data;
    }
    /**
     * Return dimension of the array.
     *
     * @param  array  $array
     * @return int
     */
    public function count_dim($array)
    {
        return is_array(reset($array)) ? $this->count_dim(reset($array)) + 1 : 1;
    }
}