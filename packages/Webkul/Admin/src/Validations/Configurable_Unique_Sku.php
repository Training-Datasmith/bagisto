<?php

declare (strict_types=1);
namespace Webkul\Admin\Validations;

use Closure;
use Illuminate\Contracts\Validation\Validation_Rule;
use Webkul\Product\Repositories\Product_Repository;
class Configurable_Unique_Sku implements Validation_Rule
{
    /**
     * Constructor.
     *
     * @param  array  $currentIds
     */
    public function __construct(protected $current_ids = null)
    {
    }
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->is_sku_exists_in_product()) {
            $fail('admin::app.catalog.products.index.already-taken')->translate(['name' => $attribute]);
        }
    }
    /**
     * Is SKU is exists in product.
     *
     * @return bool
     */
    protected function is_sku_exists_in_product()
    {
        $requested_skus = collect(request()->get('variants'))->pluck('sku')->to_array();
        $product_repository = app(Product_Repository::class);
        /**
         * First we will check sku in all the products except the
         * current variant ids.
         */
        if ($product_repository->where_in('sku', $requested_skus)->where_not_in('id', $this->current_ids)->exists()) {
            return false;
        }
        /**
         * Once, we don't found any sku in all the products then
         * we will check uniqueness in the current requested variant's skus.
         */
        return !(count($requested_skus) !== count(array_unique($requested_skus)));
    }
}