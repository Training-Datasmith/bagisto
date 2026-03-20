<?php

declare (strict_types=1);
namespace Webkul\Core\Console\Commands;

use Illuminate\Foundation\Console\Down_Command as BaseDownCommand;
use Webkul\Core\Models\Channel;
class Down_Command extends Base_Down_Command
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->down_all_channels();
        parent::handle();
    }
    /**
     * Update all channels.
     *
     * @return mixed
     */
    protected function down_all_channels()
    {
        $this->components->info('All channels are down.');
        return Channel::query()->update(['is_maintenance_on' => 1]);
    }
}