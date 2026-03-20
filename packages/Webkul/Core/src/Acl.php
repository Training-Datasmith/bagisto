<?php

declare (strict_types=1);
namespace Webkul\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webkul\Core\Acl\Acl_Item;
class Acl
{
    /**
     * acl items.
     */
    protected array $items = [];
    /**
     * Add a new acl item.
     */
    public function add_item(Acl_Item $acl_item): void
    {
        $this->items[] = $acl_item;
    }
    /**
     * Get all acl items.
     */
    public function get_items(): Collection
    {
        if (!$this->items) {
            $this->prepare_acl_items();
        }
        return collect($this->items)->sort_by('sort');
    }
    /**
     * Acl Config.
     */
    private function get_acl_config(): array
    {
        static $acl_config;
        if ($acl_config) {
            return $acl_config;
        }
        $acl_config = config('acl');
        return $acl_config;
    }
    /**
     * Get all roles.
     */
    public function get_roles(): Collection
    {
        static $roles;
        if ($roles) {
            return $roles;
        }
        $roles = collect($this->get_acl_config())->map_with_keys(fn($role) => [$role['route'] => $role['key']]);
        return $roles;
    }
    /**
     * Prepare acl items.
     */
    private function prepare_acl_items(): void
    {
        $acl_with_dot_notation = [];
        foreach ($this->get_acl_config() as $item) {
            $acl_with_dot_notation[$item['key']] = $item;
        }
        $acl = Arr::undot(Arr::dot($acl_with_dot_notation));
        foreach ($acl as $acl_item_key => $acl_item) {
            $sub_acl_items = $this->process_sub_acl_items($acl_item);
            $this->add_item(new Acl_Item(key: $acl_item_key, name: trans($acl_item['name']), route: $acl_item['route'], sort: $acl_item['sort'], children: $sub_acl_items));
        }
    }
    /**
     * Process sub acl items.
     */
    private function process_sub_acl_items($acl_item): Collection
    {
        return collect($acl_item)->sort_by('sort')->filter(fn($value) => is_array($value))->map(function ($sub_acl_item) {
            $sub_sub_acl_items = $this->process_sub_acl_items($sub_acl_item);
            return new Acl_Item(key: $sub_acl_item['key'], name: trans($sub_acl_item['name']), route: $sub_acl_item['route'], sort: $sub_acl_item['sort'], children: $sub_sub_acl_items);
        });
    }
}