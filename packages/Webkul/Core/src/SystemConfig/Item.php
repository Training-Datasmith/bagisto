<?php

declare (strict_types=1);
namespace Webkul\Core\System_Config;

use Illuminate\Support\Collection;
class Item
{
    /**
     * Create a new Item instance.
     */
    public function __construct(public Collection $children, public ?array $fields, public ?string $icon, public ?string $icon_class, public ?string $info, public string $key, public string $name, public ?string $route = null, public ?int $sort = null)
    {
    }
    /**
     * Get name of config item.
     */
    public function get_name(): string
    {
        return $this->name ?? '';
    }
    /**
     * Format options.
     */
    private function format_options($options)
    {
        return is_array($options) ? $options : (is_string($options) ? $options : []);
    }
    /**
     * Get fields of config item.
     */
    public function get_fields(): Collection
    {
        return collect($this->fields)->map(function ($field) {
            return new Item_Field(item_key: $this->key, name: $field['name'], title: $field['title'], info: $field['info'] ?? null, type: $field['type'], depends: $field['depends'] ?? null, path: $field['path'] ?? null, validation: $field['validation'] ?? null, default: $field['default'] ?? null, channel_based: $field['channel_based'] ?? null, locale_based: $field['locale_based'] ?? null, placeholder: $field['placeholder'] ?? null, options: $this->format_options($field['options'] ?? null), is_visible: true);
        });
    }
    /**
     * Get name of config item.
     */
    public function get_info(): ?string
    {
        return $this->info;
    }
    /**
     * Get current route.
     */
    public function get_route(): string
    {
        return $this->route;
    }
    /**
     * Get the url of the config item.
     */
    public function get_url(): string
    {
        return route($this->get_route());
    }
    /**
     * Get the key of the config item.
     */
    public function get_key(): string
    {
        return $this->key;
    }
    /**
     * Get Icon.
     */
    public function get_icon(): ?string
    {
        return $this->icon;
    }
    /**
     * Get Icon class.
     */
    public function get_icon_class(): ?string
    {
        return $this->icon_class;
    }
    /**
     * Check weather config item have children or not.
     */
    public function have_children(): bool
    {
        return $this->children->is_not_empty();
    }
    /**
     * Get children of config item.
     */
    public function get_children(): Collection
    {
        if (!$this->have_children()) {
            return collect();
        }
        return $this->children;
    }
}