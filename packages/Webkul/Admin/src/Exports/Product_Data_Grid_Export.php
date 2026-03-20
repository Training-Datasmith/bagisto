<?php

declare (strict_types=1);
namespace Webkul\Admin\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\From_Collection;
use Maatwebsite\Excel\Concerns\Should_Auto_Size;
use Maatwebsite\Excel\Concerns\With_Headings;
use Maatwebsite\Excel\Concerns\With_Mapping;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Product\Models\Product_Attribute_Value;
class Product_Data_Grid_Export implements From_Collection, Should_Auto_Size, With_Headings, With_Mapping
{
    /**
     * Cached product rows from the query builder (fetched once, reused by both
     * headings() and collection() since Maatwebsite Excel calls headings() first).
     */
    protected ?Collection $products = null;
    /**
     * Union of all attributes across the exported products' attribute families,
     * ordered by position and unique by attribute id.
     */
    protected ?Collection $attributes = null;
    /**
     * Attribute option labels keyed by option id, translated to the active locale.
     */
    protected ?Collection $option_labels = null;
    /**
     * Attribute values lookup: [product_id => [attribute_id => stdClass]]
     * Only values matching the active locale/channel are kept.
     */
    protected ?Collection $attribute_values_by_product = null;
    /**
     * Image URLs grouped by product_id: [product_id => Collection<string>]
     */
    protected ?Collection $images_by_product = null;
    /**
     * Video URLs grouped by product_id: [product_id => Collection<string>]
     */
    protected ?Collection $videos_by_product = null;
    /**
     * Map of attribute_family_id => int[] (attribute ids belonging to that family).
     */
    protected ?Collection $family_attribute_map = null;
    /**
     * Map of product_id => attribute_family_id.
     */
    protected ?Collection $product_family_map = null;
    /**
     * Create a new instance.
     */
    public function __construct(protected Data_Grid $datagrid)
    {
    }
    /**
     * Return the cached product collection.
     * Maatwebsite Excel calls headings() before collection(), so we pre-load
     * everything in ensurePreloaded() which is safe to call multiple times.
     */
    public function collection(): Collection
    {
        $this->ensure_preloaded();
        return $this->products;
    }
    /**
     * Build the header row: standard datagrid columns + all attribute columns
     * (union of families in the export) + Images + Videos.
     */
    public function headings(): array
    {
        $this->ensure_preloaded();
        $datagrid_headers = collect($this->datagrid->get_columns())->filter(fn($column) => $column->get_exportable())->map(fn($column) => $column->get_label())->values()->to_array();
        $attribute_headers = $this->attributes->map(fn($attribute) => $attribute->admin_name)->values()->to_array();
        return array_merge($datagrid_headers, $attribute_headers, [trans('admin::app.catalog.products.edit.images.title'), trans('admin::app.catalog.products.edit.videos.title')]);
    }
    /**
     * Map a single product row to export values.
     *
     * For each attribute column: only output a value when the attribute belongs
     * to the product's own attribute family; otherwise leave blank (null).
     */
    public function map(mixed $record): array
    {
        $datagrid_values = collect($this->datagrid->get_columns())->filter(fn($column) => $column->get_exportable())->map(fn($column) => $this->sanitize($record->{$column->get_index()}))->values()->to_array();
        $family_id = $this->product_family_map->get($record->product_id);
        $family_attr_ids = $this->family_attribute_map->get($family_id, []);
        $attribute_values = $this->attributes->map(function ($attribute) use ($record, $family_attr_ids) {
            // Only export value when this attribute belongs to the product's family.
            if (!in_array($attribute->id, $family_attr_ids)) {
                return null;
            }
            return $this->resolve_attribute_value($record->product_id, $attribute);
        })->values()->to_array();
        $images = $this->images_by_product->get($record->product_id, collect())->implode(', ');
        $videos = $this->videos_by_product->get($record->product_id, collect())->implode(', ');
        return array_merge($datagrid_values, $attribute_values, [$images, $videos]);
    }
    /**
     * Idempotent bootstrap: runs the product query once, then loads all related
     * data in a few batch queries.  Safe to call from both headings() and collection().
     */
    protected function ensure_preloaded(): void
    {
        if ($this->products !== null) {
            return;
        }
        $this->products = $this->datagrid->get_query_builder()->get();
        if ($this->products->is_empty()) {
            $this->attributes = collect();
            $this->option_labels = collect();
            $this->attribute_values_by_product = collect();
            $this->images_by_product = collect();
            $this->videos_by_product = collect();
            $this->family_attribute_map = collect();
            $this->product_family_map = collect();
            return;
        }
        $product_ids = $this->products->pluck('product_id')->unique()->values()->to_array();
        $this->preload_family_attributes($product_ids);
        $this->preload_attribute_values($product_ids);
        $this->preload_media($product_ids);
    }
    /**
     * Load the attribute-family context for the exported products:
     *  1. product_id → attribute_family_id mapping
     *  2. Unique attributes across all those families (ordered by position)
     *  3. family_id → [attribute_ids] map for per-row filtering in map()
     */
    protected function preload_family_attributes(array $product_ids): void
    {
        $locale = app()->get_locale();
        // product_id → attribute_family_id
        $this->product_family_map = DB::table('product_flat')->where_in('product_id', $product_ids)->where('locale', $locale)->pluck('attribute_family_id', 'product_id');
        $family_ids = $this->product_family_map->unique()->filter()->values()->to_array();
        if (empty($family_ids)) {
            $this->attributes = collect();
            $this->family_attribute_map = collect();
            return;
        }
        // All attributes that appear in at least one of the relevant families.
        // unique() on 'id' keeps the first occurrence so the ordered position is preserved.
        $this->attributes = DB::table('attributes')->join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')->join('attribute_groups', 'attribute_group_mappings.attribute_group_id', '=', 'attribute_groups.id')->where_in('attribute_groups.attribute_family_id', $family_ids)->select('attributes.id', 'attributes.code', 'attributes.admin_name', 'attributes.type', 'attributes.value_per_locale', 'attributes.value_per_channel')->order_by('attributes.position')->get()->unique('id')->values();
        // Build family → [attr_id, ...] map so map() can check membership quickly.
        // A separate query keeps correct many-to-family assignments.
        $family_attr_rows = DB::table('attributes')->join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')->join('attribute_groups', 'attribute_group_mappings.attribute_group_id', '=', 'attribute_groups.id')->where_in('attribute_groups.attribute_family_id', $family_ids)->select('attributes.id as attribute_id', 'attribute_groups.attribute_family_id')->get();
        $this->family_attribute_map = $family_attr_rows->group_by('attribute_family_id')->map(fn($rows) => $rows->pluck('attribute_id')->unique()->values()->to_array());
    }
    /**
     * Batch-load all product attribute values for the given product IDs.
     * Respects value_per_locale / value_per_channel flags and resolves select /
     * multiselect / checkbox option labels in one extra query.
     */
    protected function preload_attribute_values(array $product_ids): void
    {
        $locale = app()->get_locale();
        $channel = core()->get_requested_channel_code();
        $raw_values = DB::table('product_attribute_values')->select('product_id', 'attribute_id', 'locale', 'channel', 'text_value', 'boolean_value', 'integer_value', 'float_value', 'datetime_value', 'date_value')->where_in('product_id', $product_ids)->get();
        // Collect all attribute-option ids used so we can translate them in one query.
        $all_option_ids = collect();
        foreach ($raw_values as $row) {
            if ($row->integer_value !== null) {
                $all_option_ids->push((int) $row->integer_value);
            }
            if (!empty($row->text_value) && str_contains($row->text_value, ',')) {
                foreach (explode(',', $row->text_value) as $id) {
                    if (is_numeric(trim($id))) {
                        $all_option_ids->push((int) trim($id));
                    }
                }
            }
        }
        $unique_option_ids = $all_option_ids->unique()->filter()->values()->to_array();
        $this->option_labels = !empty($unique_option_ids) ? DB::table('attribute_option_translations')->where_in('attribute_option_id', $unique_option_ids)->where('locale', $locale)->pluck('label', 'attribute_option_id') : collect();
        // Keep only the value row that matches the active locale/channel for each
        // (product_id, attribute_id) pair.
        $attributes_by_id = $this->attributes->key_by('id');
        $grouped = [];
        foreach ($raw_values as $row) {
            $attribute = $attributes_by_id->get($row->attribute_id);
            if (!$attribute) {
                continue;
            }
            $matches_locale = $attribute->value_per_locale ? $row->locale === $locale : $row->locale === null;
            $matches_channel = $attribute->value_per_channel ? $row->channel === $channel : $row->channel === null;
            if (!$matches_locale || !$matches_channel) {
                continue;
            }
            $grouped[$row->product_id][$row->attribute_id] = $row;
        }
        $this->attribute_values_by_product = collect($grouped);
    }
    /**
     * Batch-load product images and videos, building per-product URL collections.
     */
    protected function preload_media(array $product_ids): void
    {
        $this->images_by_product = DB::table('product_images')->select('product_id', 'path', 'position')->where_in('product_id', $product_ids)->order_by('product_id')->order_by('position')->get()->group_by('product_id')->map(fn($rows) => $rows->map(fn($row) => Storage::url($row->path)));
        $this->videos_by_product = DB::table('product_videos')->select('product_id', 'path', 'position')->where_in('product_id', $product_ids)->order_by('product_id')->order_by('position')->get()->group_by('product_id')->map(fn($rows) => $rows->map(fn($row) => Storage::url($row->path)));
    }
    /**
     * Return the human-readable / sanitized value for a single attribute on a product.
     *
     * Returns null when no value exists.
     */
    protected function resolve_attribute_value(int $product_id, object $attribute): mixed
    {
        $product_values = $this->attribute_values_by_product->get($product_id, []);
        $row = $product_values[$attribute->id] ?? null;
        if ($row === null) {
            return null;
        }
        $column = Product_Attribute_Value::$attribute_type_fields[$attribute->type] ?? 'text_value';
        $value = $row->{$column};
        if ($value === null || $value === '') {
            return null;
        }
        return match ($attribute->type) {
            'select' => $this->option_labels->get((int) $value, $value),
            'multiselect', 'checkbox' => collect(explode(',', $value))->map(fn($id) => $this->option_labels->get((int) trim($id), trim($id)))->filter()->implode(', '),
            'boolean' => (bool) $value ? trans('admin::app.export.yes') : trans('admin::app.export.no'),
            default => $this->sanitize($value),
        };
    }
    /**
     * Sanitize a value to prevent formula injection in spreadsheet cells.
     */
    protected function sanitize(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return $value;
        }
        $dangerous_chars = ['=', '+', '-', '@', "\t", "\r", "\n", '|', '%'];
        $first_char = mb_substr($trimmed, 0, 1);
        if (in_array($first_char, $dangerous_chars, true)) {
            return "'" . $value;
        }
        if (preg_match('/^[\s]*[@=+\-|%]/u', $value)) {
            return "'" . $value;
        }
        return $value;
    }
}