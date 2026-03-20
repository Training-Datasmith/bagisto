<?php

declare (strict_types=1);
namespace Webkul\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webkul\Core\Menu\Menu_Item;
class Menu
{
    /**
     * Menu items.
     */
    private array $items = [];
    /**
     * Config menu.
     */
    private array $config_menu = [];
    /**
     * Contains current item key.
     */
    private string $current_key = '';
    /**
     * Menu area for admin.
     */
    public const ADMIN = 'admin';
    /**
     * Menu area for customer.
     */
    public const CUSTOMER = 'customer';
    /**
     * Add a new menu item.
     */
    public function add_item(Menu_Item $menu_item): void
    {
        $this->items[] = $menu_item;
    }
    /**
     * Get all menu items.
     */
    public function get_items(?string $area = null): Collection
    {
        if (!$area) {
            throw new \Exception('Area must be provided to get menu items.');
        }
        $config_menu = collect(config("menu.{$area}"));
        switch ($area) {
            case self::ADMIN:
                $this->config_menu = $config_menu->filter(fn($item) => bouncer()->has_permission($item['key']))->to_array();
                break;
            case self::CUSTOMER:
                $can_show_wishlist = !(bool) core()->get_config_data('customer.settings.wishlist.wishlist_option');
                $can_show_gdpr = !(bool) core()->get_config_data('general.gdpr.settings.enabled');
                $this->config_menu = $config_menu->reject(fn($item) => $item['key'] == 'account.wishlist' && $can_show_wishlist || $item['key'] == 'account.gdpr_data_request' && $can_show_gdpr)->to_array();
                break;
            default:
                $this->config_menu = $config_menu->to_array();
                break;
        }
        if (!$this->items) {
            $this->prepare_menu_items();
        }
        return collect($this->remove_unauthorized_menu_item())->sort_by('sort');
    }
    /**
     * Prepare menu items.
     */
    private function prepare_menu_items(): void
    {
        $menu_with_dot_notation = [];
        foreach ($this->config_menu as $item) {
            if (strpos(request()->url(), route($item['route'])) !== false) {
                $this->current_key = $item['key'];
            }
            $menu_with_dot_notation[$item['key']] = $item;
        }
        $menu = Arr::undot(Arr::dot($menu_with_dot_notation));
        foreach ($menu as $menu_item_key => $menu_item) {
            $sub_menu_items = $this->process_sub_menu_items($menu_item);
            $this->add_item(new Menu_Item(key: $menu_item_key, name: trans($menu_item['name']), route: $menu_item['route'], sort: $menu_item['sort'], icon: $menu_item['icon'], children: $sub_menu_items));
        }
    }
    /**
     * Process sub menu items.
     */
    private function process_sub_menu_items($menu_item): Collection
    {
        return collect($menu_item)->sort_by('sort')->filter(fn($value) => is_array($value))->map(function ($sub_menu_item) {
            $sub_sub_menu_items = $this->process_sub_menu_items($sub_menu_item);
            return new Menu_Item(key: $sub_menu_item['key'], name: trans($sub_menu_item['name']), route: $sub_menu_item['route'], sort: $sub_menu_item['sort'], icon: $sub_menu_item['icon'], children: $sub_sub_menu_items);
        });
    }
    /**
     * Get current active menu.
     */
    public function get_current_active_menu(?string $area = null): ?Menu_Item
    {
        $current_key = implode('.', array_slice(explode('.', $this->current_key), 0, 2));
        return $this->find_matching_item($this->get_items($area), $current_key);
    }
    /**
     * Finding the matching item.
     */
    private function find_matching_item($items, $current_key): ?Menu_Item
    {
        foreach ($items as $item) {
            if ($item->key == $current_key) {
                return $item;
            }
            if ($item->have_children()) {
                $matching_child = $this->find_matching_item($item->get_children(), $current_key);
                if ($matching_child) {
                    return $matching_child;
                }
            }
        }
        return null;
    }
    /**
     * Remove unauthorized menu item.
     */
    private function remove_unauthorized_menu_item(): array
    {
        return collect($this->items)->map(function ($item) {
            $this->remove_children_unauthorized_menu_item($item);
            return $item;
        })->to_array();
    }
    /**
     * Remove unauthorized menuItem's children. This will handle all levels.
     */
    private function remove_children_unauthorized_menu_item(Menu_Item &$menu_item): void
    {
        if ($menu_item->have_children()) {
            $first_children_item = $menu_item->get_children()->first();
            $menu_item->route = $first_children_item->get_route();
            $this->remove_children_unauthorized_menu_item($first_children_item);
        }
    }
}