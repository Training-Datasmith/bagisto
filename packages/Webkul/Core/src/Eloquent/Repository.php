<?php

declare (strict_types=1);
namespace Webkul\Core\Eloquent;

use Prettus\Repository\Contracts\Cacheable_Interface;
use Prettus\Repository\Eloquent\Base_Repository;
use Prettus\Repository\Traits\Cacheable_Repository;
abstract class Repository extends Base_Repository implements Cacheable_Interface
{
    use Cacheable_Repository;
    /**
     * Cache only enabled.
     *
     * @var array
     */
    protected $cache_only;
    /**
     * Cache except enabled.
     *
     * @var array
     */
    protected $cache_except;
    /**
     * Clean enabled.
     *
     * @var bool
     */
    protected $clean_enabled;
    /**
     * Allowed clean.
     *
     * @return bool
     */
    public function allowed_clean()
    {
        if (!isset($this->clean_enabled)) {
            return config('repository.cache.clean.enabled', true);
        }
        return $this->clean_enabled;
    }
    /**
     * Allowed cache.
     *
     * @return bool
     */
    protected function allowed_cache($method)
    {
        $class_name = get_class($this);
        $cache_enabled = config("repository.cache.repositories.{$class_name}.enabled", config('repository.cache.enabled', true));
        if (!$cache_enabled) {
            return false;
        }
        $cache_only = isset($this->cache_only) ? $this->cache_only : config("repository.cache.repositories.{$class_name}.allowed.only", config('repository.cache.allowed.only', null));
        $cache_except = isset($this->cache_except) ? $this->cache_except : config("repository.cache.repositories.{$class_name}.allowed.except", config('repository.cache.allowed.only', null));
        if (is_array($cache_only)) {
            return in_array($method, $cache_only);
        }
        if (is_array($cache_except)) {
            return !in_array($method, $cache_except);
        }
        if (is_null($cache_only) && is_null($cache_except)) {
            return true;
        }
        return false;
    }
    /**
     * Reset model.
     *
     * @throws RepositoryException
     */
    public function reset_model()
    {
        $this->make_model();
        return $this;
    }
    /**
     * Find data by field and value.
     *
     * @param  string  $field
     * @param  string  $value
     * @param  array  $columns
     * @return mixed
     */
    public function find_one_by_field($field, $value = null, $columns = ['*'])
    {
        $model = $this->find_by_field($field, $value, $columns);
        return $model->first();
    }
    /**
     * Find data by field and value.
     *
     * @param  string  $field
     * @param  string  $value
     * @param  array  $columns
     * @return mixed
     */
    public function find_one_where(array $where, $columns = ['*'])
    {
        $model = $this->find_where($where, $columns);
        return $model->first();
    }
    /**
     * Find data by id.
     *
     * @param  int  $id
     * @param  array  $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->apply_criteria();
        $this->apply_scope();
        $model = $this->model->find($id, $columns);
        $this->reset_model();
        return $this->parser_result($model);
    }
    /**
     * Find data by id.
     *
     * @param  int  $id
     * @param  array  $columns
     * @return mixed
     */
    public function find_or_fail($id, $columns = ['*'])
    {
        $this->apply_criteria();
        $this->apply_scope();
        $model = $this->model->find_or_fail($id, $columns);
        $this->reset_model();
        return $this->parser_result($model);
    }
    /**
     * Count results of repository.
     *
     * @param  string  $columns
     * @return int
     */
    public function count(array $where = [], $columns = '*')
    {
        $this->apply_criteria();
        $this->apply_scope();
        if ($where) {
            $this->apply_conditions($where);
        }
        $result = $this->model->count($columns);
        $this->reset_model();
        $this->reset_scope();
        return $result;
    }
    /**
     * Sum.
     *
     * @param  string  $columns
     * @return mixed
     */
    public function sum($columns)
    {
        $this->apply_criteria();
        $this->apply_scope();
        $sum = $this->model->sum($columns);
        $this->reset_model();
        return $sum;
    }
    /**
     * Avg.
     *
     * @param  string  $columns
     * @return mixed
     */
    public function avg($columns)
    {
        $this->apply_criteria();
        $this->apply_scope();
        $avg = $this->model->avg($columns);
        $this->reset_model();
        return $avg;
    }
    /**
     * Get model.
     *
     * @return mixed
     */
    public function get_model()
    {
        return $this->model;
    }
}