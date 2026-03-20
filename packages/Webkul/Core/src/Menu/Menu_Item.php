<?php

declare (strict_types=1);
namespace Webkul\Core\Menu;

use Illuminate\Support\Collection;
class Menu_Item
{
    /**
     * Create a new MenuItem instance.
     *
     * @return void
     */
    public function __construct(public string $key, public string $name, public string $route, public int $sort, public string $icon, public Collection $children)
    {
    }
    /**
     * Get name of menu item.
     */
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * Get the icon of menu item.
     */
    public function get_icon(): string
    {
        return $this->icon;
    }
    /**
     * Get current route.
     */
    public function get_route(): string
    {
        return $this->route;
    }
    /**
     * Get the url of the menu item.
     */
    public function get_url(): string
    {
        return route($this->get_route());
    }
    /**
     * Get the key of the menu item.
     */
    public function get_key(): string
    {
        return $this->key;
    }
    /**
     * Check weather menu item have children or not.
     */
    public function have_children(): bool
    {
        return $this->children->is_not_empty();
    }
    /**
     * Get children of menu item.
     */
    public function get_children(): Collection
    {
        if (!$this->have_children()) {
            return collect();
        }
        return $this->children;
    }
    /**
     * Check weather menu item is active or not.
     */
    public function is_active(): bool
    {
        if (request()->full_url_is($this->get_url() . '*')) {
            return true;
        }
        if ($this->have_children()) {
            foreach ($this->get_children() as $child) {
                if ($child->is_active()) {
                    return true;
                }
            }
        }
        return false;
    }
}