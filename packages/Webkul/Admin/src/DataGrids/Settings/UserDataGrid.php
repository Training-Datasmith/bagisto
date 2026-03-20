<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Data_Grid\Data_Grid;
use Webkul\User\Repositories\Role_Repository;
class User_Data_Grid extends Data_Grid
{
    /**
     * Index.
     *
     * @var string
     */
    protected $primary_column = 'user_id';
    /**
     * Constructor for the class.
     *
     * @return void
     */
    public function __construct(protected Role_Repository $role_repository)
    {
    }
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('admins')->left_join('roles', 'admins.role_id', '=', 'roles.id')->select('admins.id as user_id', 'admins.name as user_name', 'admins.image as user_image', 'admins.status', 'admins.email', 'roles.name as role_name');
        $this->add_filter('user_id', 'admins.id');
        $this->add_filter('user_name', 'admins.name');
        $this->add_filter('role_name', 'roles.name');
        $this->add_filter('status', 'admins.status');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'user_id', 'label' => trans('admin::app.settings.users.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'user_name', 'label' => trans('admin::app.settings.users.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'user_img', 'label' => trans('admin::app.settings.users.index.datagrid.name'), 'type' => 'string', 'closure' => function ($row) {
            if ($row->user_image) {
                return Storage::url($row->user_image);
            }
            return null;
        }]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.settings.users.index.datagrid.status'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.settings.users.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.settings.users.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status) {
                return trans('admin::app.settings.users.index.datagrid.active');
            }
            return trans('admin::app.settings.users.index.datagrid.inactive');
        }]);
        $this->add_column(['index' => 'email', 'label' => trans('admin::app.settings.users.index.datagrid.email'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'role_name', 'label' => trans('admin::app.settings.users.index.datagrid.role'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => $this->role_repository->all(['name as label', 'name as value'])->to_array(), 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.users.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.settings.users.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.users.edit', $row->user_id);
            }]);
        }
        if (bouncer()->has_permission('settings.users.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.settings.users.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.users.delete', $row->user_id);
            }]);
        }
    }
}