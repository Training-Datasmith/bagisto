<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Catalog_Rule\Helpers\Catalog_Rule_Index;
class Price_Rule_Index extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:price-rule:index';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically updates catalog rule price index information (eg. rule_price)';
    /**
     * Create a new command instance.
     *
     * @param  \Webkul\CatalogRuleProduct\Helpers\CatalogRuleIndex  $catalogRuleIndexHelper
     * @return void
     */
    public function __construct(protected Catalog_Rule_Index $catalog_rule_index_helper)
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->catalog_rule_index_helper->re_index_complete();
    }
}