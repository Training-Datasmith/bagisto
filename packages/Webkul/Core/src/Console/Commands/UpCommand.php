<?php

declare (strict_types=1);
namespace Webkul\Core\Console\Commands;

use Illuminate\Foundation\Console\Up_Command as BaseUpCommand;
use Webkul\Core\Models\Channel;
class Up_Command extends Base_Up_Command
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->up_all_channels();
        parent::handle();
    }
    /**
     * Update all channels.
     *
     * @return mixed
     */
    protected function up_all_channels()
    {
        $this->components->info('Activating all channels.');
        return Channel::query()->update(['is_maintenance_on' => 0]);
    }
}