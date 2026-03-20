<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Listeners;

use Webkul\Catalog_Rule\Jobs\Delete_Catalog_Rule_Index as DeleteCatalogRuleIndexJob;
use Webkul\Catalog_Rule\Jobs\Update_Create_Catalog_Rule_Index as UpdateCreateCatalogRuleIndexJob;
use Webkul\Catalog_Rule\Repositories\Catalog_Rule_Product_Price_Repository;
use Webkul\Catalog_Rule\Repositories\Catalog_Rule_Repository;
class Catalog_Rule
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(protected Catalog_Rule_Repository $catalog_rule_repository, protected Catalog_Rule_Product_Price_Repository $catalog_rule_product_price_repository)
    {
    }
    /**
     * @param  \Webkul\CatalogRule\Contracts\CatalogRule  $catalogRule
     * @return void
     */
    public function after_update_create($catalog_rule)
    {
        Update_Create_Catalog_Rule_Index_Job::dispatch($catalog_rule);
    }
    /**
     * @param  int  $catalogRuleId
     * @return void
     */
    public function before_update($catalog_rule_id)
    {
        $catalog_rule = $this->catalog_rule_repository->find($catalog_rule_id);
        $product_ids = $catalog_rule->catalog_rule_products->pluck('product_id')->unique();
        $this->catalog_rule_product_price_repository->delete_where(['catalog_rule_id' => $catalog_rule_id]);
        Delete_Catalog_Rule_Index_Job::dispatch($product_ids->to_array());
    }
    /**
     * @param  int  $catalogRuleId
     * @return void
     */
    public function before_delete($catalog_rule_id)
    {
        $catalog_rule = $this->catalog_rule_repository->find($catalog_rule_id);
        $product_ids = $catalog_rule->catalog_rule_products->pluck('product_id')->unique();
        $this->catalog_rule_product_price_repository->delete_where(['catalog_rule_id' => $catalog_rule_id]);
        Delete_Catalog_Rule_Index_Job::dispatch($product_ids->to_array());
    }
}