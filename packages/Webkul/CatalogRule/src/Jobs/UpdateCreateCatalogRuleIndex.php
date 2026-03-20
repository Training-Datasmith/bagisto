<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Should_Queue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Interacts_With_Queue;
use Illuminate\Queue\Serializes_Models;
use Webkul\Catalog_Rule\Contracts\Catalog_Rule;
use Webkul\Catalog_Rule\Helpers\Catalog_Rule_Index;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Repositories\Product_Repository;
class Update_Create_Catalog_Rule_Index implements Should_Queue
{
    use Dispatchable;
    use Interacts_With_Queue;
    use Queueable;
    use Serializes_Models;
    /**
     * Default batch size
     */
    protected const BATCH_SIZE = 100;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected Catalog_Rule $catalog_rule)
    {
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->catalog_rule->status) {
            app(Catalog_Rule_Index::class)->re_index_rule($this->catalog_rule);
            /**
             * Reindex price index for the products associated with the catalog rule.
             */
            $product_ids = $this->catalog_rule->catalog_rule_products->pluck('product_id')->unique();
        } else {
            $product_ids = $this->catalog_rule->catalog_rule_products->pluck('product_id')->unique();
            app(Catalog_Rule_Index::class)->clean_product_indices($product_ids);
        }
        while (true) {
            $paginator = app(Product_Repository::class)->where_in('id', $product_ids)->cursor_paginate(self::BATCH_SIZE);
            /**
             * TODO:
             *
             * If the catalog rule is disabled and 'end_other_rules' flag is set,
             * it indicates that this rule might have preempted the
             * application of other rules on the products. In such a scenario,
             * it's necessary to reindex the remaining rules for these products.
             */
            app(Price_Indexer::class)->reindex_batch($paginator->items());
            if (!$cursor = $paginator->next_cursor()) {
                break;
            }
            request()->query->add(['cursor' => $cursor->encode()]);
        }
    }
}