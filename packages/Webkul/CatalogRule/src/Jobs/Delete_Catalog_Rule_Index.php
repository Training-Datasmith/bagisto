<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Should_Queue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Interacts_With_Queue;
use Illuminate\Queue\Serializes_Models;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Repositories\Product_Repository;
class Delete_Catalog_Rule_Index implements Should_Queue
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
     * @param  array  $productIds
     * @return void
     */
    public function __construct(protected $product_ids)
    {
        $this->product_ids = $product_ids;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * Reindex price index for the products associated with the catalog rule.
         */
        while (true) {
            $paginator = app(Product_Repository::class)->where_in('id', $this->product_ids)->cursor_paginate(self::BATCH_SIZE);
            /**
             * TODO:
             *
             * If the 'end_other_rules' flag is set for this catalog rule,
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