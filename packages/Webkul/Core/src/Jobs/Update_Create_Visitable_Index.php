<?php

declare (strict_types=1);
namespace Webkul\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Should_Queue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Interacts_With_Queue;
use Illuminate\Queue\Serializes_Models;
use Webkul\Category\Repositories\Category_Repository;
use Webkul\Product\Repositories\Product_Repository;
class Update_Create_Visitable_Index implements Should_Queue
{
    use Dispatchable;
    use Interacts_With_Queue;
    use Queueable;
    use Serializes_Models;
    /**
     * Create a new job instance.
     *
     * @param  array  $log
     * @return void
     */
    public function __construct(protected $log)
    {
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $slug_or_url_key = urldecode(trim($this->log['path_info'], '/'));
        /**
         * Support url for chinese, japanese, arabic and english with numbers.
         */
        if (!preg_match('/^([\x{0621}-\x{064A}\x{4e00}-\x{9fa5}\x{3402}-\x{FA6D}\x{3041}-\x{30A0}\x{30A0}-\x{31FF}_a-z0-9-]+\/?)+$/u', $slug_or_url_key)) {
            Update_Create_Visit_Index::dispatch(null, $this->log);
            return;
        }
        $category = app(Category_Repository::class)->find_by_slug($slug_or_url_key);
        if ($category) {
            Update_Create_Visit_Index::dispatch($category, $this->log);
            return;
        }
        $product = app(Product_Repository::class)->find_by_slug($slug_or_url_key);
        if (!$product || !$product->visible_individually || !$product->url_key || !$product->status) {
            return;
        }
        Update_Create_Visit_Index::dispatch($product, $this->log);
    }
}