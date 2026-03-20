<?php

declare (strict_types=1);
namespace Webkul\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Webkul\Core\Models\Core_Config;
use Webkul\Core\Repositories\Core_Config_Repository;
use Webkul\Core\System_Config\Item;
class System_Config
{
    /**
     * Items array.
     */
    public array $items = [];
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(protected Core_Config_Repository $core_config_repository)
    {
    }
    /**
     * Add Item.
     */
    public function add_item(Item $item): void
    {
        $this->items[] = $item;
    }
    /**
     * Get all configuration items.
     */
    public function get_items(): Collection
    {
        if (!$this->items) {
            $this->prepare_configuration_items();
        }
        return collect($this->items)->sort_by('sort');
    }
    /**
     * Retrieve Core Config
     */
    private function retrieve_core_config(): array
    {
        static $items;
        if ($items) {
            return $items;
        }
        return $items = config('core');
    }
    /**
     * Prepare configuration items.
     */
    public function prepare_configuration_items()
    {
        $config_with_dot_notation = [];
        foreach ($this->retrieve_core_config() as $item) {
            $config_with_dot_notation[$item['key']] = $item;
        }
        $configs = Arr::undot(Arr::dot($config_with_dot_notation));
        foreach ($configs as $config_item) {
            $sub_config_items = $this->process_sub_config_items($config_item);
            $this->add_item(new Item(children: $sub_config_items, fields: $config_item['fields'] ?? null, icon: $config_item['icon'] ?? null, icon_class: $config_item['icon_class'] ?? null, info: trans($config_item['info']) ?? null, key: $config_item['key'], name: trans($config_item['name']), route: $config_item['route'] ?? null, sort: $config_item['sort']));
        }
    }
    /**
     * Process sub config items.
     */
    private function process_sub_config_items($config_item): Collection
    {
        return collect($config_item)->sort_by('sort')->filter(fn($value) => is_array($value) && isset($value['name']))->map(function ($sub_config_item) {
            $config_item_children = $this->process_sub_config_items($sub_config_item);
            return new Item(children: $config_item_children, fields: $sub_config_item['fields'] ?? null, icon: $sub_config_item['icon'] ?? null, icon_class: $sub_config_item['icon_class'] ?? null, info: trans($sub_config_item['info']) ?? null, key: $sub_config_item['key'], name: trans($sub_config_item['name']), route: $sub_config_item['route'] ?? null, sort: $sub_config_item['sort'] ?? null);
        });
    }
    /**
     * Get active configuration item.
     */
    public function get_active_configuration_item(): ?Item
    {
        if (!$slug = request()->route('slug')) {
            return null;
        }
        $active_item = $this->get_items()->where('key', $slug)->first() ?? null;
        if (!$active_item) {
            return null;
        }
        if ($slug2 = request()->route('slug2')) {
            $active_item = $active_item->get_children()[$slug2];
        }
        return $active_item;
    }
    /**
     * Get config field.
     */
    public function get_config_field(string $field_name): ?array
    {
        foreach ($this->retrieve_core_config() as $core_data) {
            if (!isset($core_data['fields'])) {
                continue;
            }
            foreach ($core_data['fields'] as $field) {
                $name = $core_data['key'] . '.' . $field['name'];
                if ($name == $field_name) {
                    return $field;
                }
            }
        }
        return null;
    }
    /**
     * Get core config values.
     */
    protected function get_core_config(string $field, ?string $channel, ?string $locale): ?Core_Config
    {
        $fields = $this->get_config_field($field);
        if (!empty($fields['channel_based'])) {
            if (!empty($fields['locale_based'])) {
                $core_config_value = $this->core_config_repository->find_one_where(['code' => $field, 'channel_code' => $channel, 'locale_code' => $locale]);
            } else {
                $core_config_value = $this->core_config_repository->find_one_where(['code' => $field, 'channel_code' => $channel]);
            }
        } else if (!empty($fields['locale_based'])) {
            $core_config_value = $this->core_config_repository->find_one_where(['code' => $field, 'locale_code' => $locale]);
        } else {
            $core_config_value = $this->core_config_repository->find_one_where(['code' => $field]);
        }
        return $core_config_value;
    }
    /**
     * Get default config.
     */
    protected function get_default_config(string $field): mixed
    {
        $config_field_info = $this->get_config_field($field);
        $fields = explode('.', $field);
        array_shift($fields);
        $field = implode('.', $fields);
        return Config::get($field, $config_field_info['default'] ?? null);
    }
    /**
     * Get the config data.
     */
    public function get_config_data(string $field, ?string $current_channel_code = null, ?string $current_locale_code = null): mixed
    {
        if (empty($current_channel_code)) {
            $current_channel_code = core()->get_requested_channel_code();
        }
        if (empty($current_locale_code)) {
            $current_locale_code = core()->get_requested_locale_code();
        }
        $core_config = $this->get_core_config($field, $current_channel_code, $current_locale_code);
        if (!$core_config) {
            return $this->get_default_config($field);
        }
        return $core_config->value;
    }
}