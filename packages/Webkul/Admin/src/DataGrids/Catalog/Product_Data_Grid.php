<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Catalog;

use Illuminate\Pagination\Length_Aware_Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Exports\Product_Data_Grid_Export;
use Webkul\Attribute\Repositories\Attribute_Family_Repository;
use Webkul\Core\Facades\Elastic_Search;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Product\Helpers\Product;
class Product_Data_Grid extends Data_Grid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primary_column = 'product_id';
    /**
     * Constructor for the class.
     *
     * @return void
     */
    public function __construct(protected Attribute_Family_Repository $attribute_family_repository)
    {
    }
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $table_prefix = DB::get_table_prefix();
        /**
         * Query Builder to fetch records from `product_flat` table
         */
        $query_builder = DB::table('product_flat')->distinct()->left_join('attribute_families as af', 'product_flat.attribute_family_id', '=', 'af.id')->left_join('product_inventories', 'product_flat.product_id', '=', 'product_inventories.product_id')->left_join('product_images', 'product_flat.product_id', '=', 'product_images.product_id')->left_join('product_categories as pc', 'product_flat.product_id', '=', 'pc.product_id')->left_join('category_translations as ct', function ($left_join) {
            $left_join->on('pc.category_id', '=', 'ct.category_id')->where('ct.locale', app()->get_locale());
        })->select('product_flat.locale', 'product_flat.channel', 'product_images.path as base_image', 'pc.category_id', 'ct.name as category_name', 'product_flat.product_id', 'product_flat.sku', 'product_flat.name', 'product_flat.type', 'product_flat.status', 'product_flat.price', 'product_flat.url_key', 'product_flat.visible_individually', 'af.name as attribute_family')->add_select(DB::raw('SUM(DISTINCT ' . $table_prefix . 'product_inventories.qty) as quantity'))->add_select(DB::raw('COUNT(DISTINCT ' . $table_prefix . 'product_images.id) as images_count'))->where('product_flat.locale', app()->get_locale())->group_by('product_flat.product_id');
        $this->add_filter('product_id', 'product_flat.product_id');
        $this->add_filter('channel', 'product_flat.channel');
        $this->add_filter('locale', 'product_flat.locale');
        $this->add_filter('name', 'product_flat.name');
        $this->add_filter('type', 'product_flat.type');
        $this->add_filter('status', 'product_flat.status');
        $this->add_filter('attribute_family', 'af.id');
        return $query_builder;
    }
    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $channels = core()->get_all_channels();
        if ($channels->count() > 1) {
            $this->add_column(['index' => 'channel', 'label' => trans('admin::app.catalog.products.index.datagrid.channel'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => collect($channels)->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->code])->values()->to_array(), 'sortable' => true, 'visibility' => false]);
        }
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.catalog.products.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'sku', 'label' => trans('admin::app.catalog.products.index.datagrid.sku'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'attribute_family', 'label' => trans('admin::app.catalog.products.index.datagrid.attribute-family'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => $this->attribute_family_repository->all(['name as label', 'id as value'])->to_array()]);
        $this->add_column(['index' => 'base_image', 'label' => trans('admin::app.catalog.products.index.datagrid.image'), 'type' => 'string', 'exportable' => false, 'closure' => function ($row) {
            if (!$row->base_image) {
                return;
            }
            return Storage::url($row->base_image);
        }]);
        $this->add_column(['index' => 'price', 'label' => trans('admin::app.catalog.products.index.datagrid.price'), 'type' => 'decimal', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'quantity', 'label' => trans('admin::app.catalog.products.index.datagrid.qty'), 'type' => 'integer', 'sortable' => true]);
        $this->add_column(['index' => 'product_id', 'label' => trans('admin::app.catalog.products.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.catalog.products.index.datagrid.status'), 'type' => 'boolean', 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.catalog.products.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.catalog.products.index.datagrid.disable'), 'value' => 0]], 'sortable' => true]);
        $this->add_column(['index' => 'category_name', 'label' => trans('admin::app.catalog.products.index.datagrid.category'), 'type' => 'string']);
        $this->add_column(['index' => 'type', 'label' => trans('admin::app.catalog.products.index.datagrid.type'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => collect(config('product_types'))->map(fn($type) => ['label' => trans($type['name']), 'value' => $type['key']])->values()->to_array(), 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('catalog.products.copy')) {
            $this->add_action(['icon' => 'icon-copy', 'title' => trans('admin::app.catalog.products.index.datagrid.copy'), 'method' => 'POST', 'url' => function ($row) {
                return route('admin.catalog.products.copy', $row->product_id);
            }]);
        }
        if (bouncer()->has_permission('catalog.products.edit')) {
            $this->add_action(['icon' => 'icon-sort-right', 'title' => trans('admin::app.catalog.products.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                $filtered_channel = request()->input('filters.channel')[0] ?? null;
                return route('admin.catalog.products.edit', ['id' => $row->product_id, 'channel' => $filtered_channel]);
            }]);
        }
    }
    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepare_mass_actions()
    {
        if (bouncer()->has_permission('catalog.products.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.catalog.products.index.datagrid.delete'), 'url' => route('admin.catalog.products.mass_delete'), 'method' => 'POST']);
        }
        if (bouncer()->has_permission('catalog.products.edit')) {
            $this->add_mass_action(['title' => trans('admin::app.catalog.products.index.datagrid.update-status'), 'url' => route('admin.catalog.products.mass_update'), 'method' => 'POST', 'options' => [['label' => trans('admin::app.catalog.products.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.catalog.products.index.datagrid.disable'), 'value' => 0]]]);
        }
    }
    /**
     * Return a custom exporter that includes all product attribute values.
     */
    public function get_exporter(): Product_Data_Grid_Export
    {
        return new Product_Data_Grid_Export($this);
    }
    /**
     * Process request.
     */
    protected function process_request(): void
    {
        if (core()->get_config_data('catalog.products.search.engine') != 'elastic' || core()->get_config_data('catalog.products.search.admin_mode') != 'elastic') {
            parent::process_request();
            return;
        }
        /**
         * Store all request parameters in this variable; avoid using direct request helpers afterward.
         */
        $params = $this->validated_request();
        if (isset($params['export']) && (bool) $params['export']) {
            parent::process_request();
            return;
        }
        $this->dispatch_event('process_request.before', $this);
        $pagination = $params['pagination'];
        $channel_codes = request()->input('filters.channel') ?? core()->get_all_channels()->pluck('code')->to_array();
        $index_names = collect($channel_codes)->map(function ($channel_code) {
            return Product::format_elastic_search_index_name($channel_code, app()->get_locale());
        })->to_array();
        $results = Elasticsearch::search(['index' => $index_names, 'body' => ['from' => $pagination['page'] * $pagination['per_page'] - $pagination['per_page'], 'size' => $pagination['per_page'], 'stored_fields' => [], 'query' => ['bool' => $this->get_elastic_filters($params['filters'] ?? []) ?: new \stdClass()], 'sort' => $this->get_elastic_sort($params['sort'] ?? []), 'track_total_hits' => true]]);
        $ids = collect($results['hits']['hits'])->pluck('_id')->to_array();
        $this->query_builder->where_in('product_flat.product_id', $ids);
        if ($ids) {
            $this->query_builder->order_by(DB::raw('FIELD(' . DB::get_table_prefix() . 'product_flat.product_id, ' . implode(',', $ids) . ')'));
        }
        $total = $results['hits']['total']['value'];
        $this->paginator = new Length_Aware_Paginator($total ? $this->query_builder->get() : [], $total, $pagination['per_page'], $pagination['page'], ['path' => request()->url(), 'query' => []]);
        $this->dispatch_event('process_request.after', $this);
    }
    /**
     * Process request.
     */
    protected function get_elastic_filters($params): array
    {
        $filters = [];
        foreach ($params as $attribute => $value) {
            if (in_array($attribute, ['channel', 'locale'])) {
                continue;
            }
            if ($attribute == 'all') {
                $attribute = 'name';
            }
            $filters['filter'][] = $this->get_filter_value($attribute, $value);
        }
        return $filters;
    }
    /**
     * Return applied filters
     */
    public function get_filter_value(mixed $attribute, mixed $values): array
    {
        switch ($attribute) {
            case 'product_id':
                return ['terms' => ['id' => $values]];
            case 'attribute_family':
                return ['terms' => ['attribute_family_id' => $values]];
            case 'sku':
            case 'name':
                $filters = [];
                foreach ($values as $value) {
                    $filters['bool']['should'][] = ['match_phrase_prefix' => [$attribute => $value]];
                }
                return $filters;
            default:
                return ['terms' => [$attribute => $values]];
        }
    }
    /**
     * Process request.
     */
    protected function get_elastic_sort($params): array
    {
        $sort = $params['column'] ?? $this->primary_column;
        if ($sort == 'type') {
            $sort .= '.keyword';
        }
        if ($sort == 'name') {
            $sort .= '.keyword';
        }
        if ($sort == 'attribute_family') {
            $sort .= '_id';
        }
        if ($sort == 'product_id') {
            $sort = 'id';
        }
        return [$sort => ['order' => $params['order'] ?? $this->sort_order]];
    }
}