<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Should_Queue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Interacts_With_Queue;
use Illuminate\Queue\Serializes_Models;
use Webkul\Catalog_Rule\Helpers\Catalog_Rule_Index;
class Update_Create_Product_Index implements Should_Queue
{
    use Dispatchable;
    use Interacts_With_Queue;
    use Queueable;
    use Serializes_Models;
    /**
     * Create a new job instance.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function __construct(protected $product)
    {
        $this->product = $product;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(Catalog_Rule_Index::class)->re_index_product($this->product);
    }
}