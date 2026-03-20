<?php

declare (strict_types=1);
namespace Webkul\Core;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Visitor as BaseVisitor;
use Webkul\Core\Jobs\Update_Create_Visit_Index;
class Visitor extends Base_Visitor
{
    /**
     * Create a visit log.
     *
     * @return void
     */
    public function visit(?Model $model = null)
    {
        if (!core()->get_config_data('general.general.visitor_options.enabled')) {
            return;
        }
        foreach ($this->except as $path) {
            if ($this->request->is($path)) {
                return;
            }
        }
        Update_Create_Visit_Index::dispatch($model, $this->prepare_log());
    }
    /**
     * Retrieve request's url.
     */
    public function url(): string
    {
        return $this->request->url();
    }
    /**
     * Prepare log's data.
     *
     *
     * @throws \Exception
     */
    protected function prepare_log(): array
    {
        return array_merge(parent::prepare_log(), ['channel_id' => core()->get_current_channel()->id]);
    }
    /**
     * Returns logs.
     *
     * @return array
     */
    public function get_log()
    {
        return $this->prepare_log();
    }
}