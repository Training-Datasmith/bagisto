<?php

declare (strict_types=1);
namespace Webkul\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Should_Queue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Interacts_With_Queue;
use Illuminate\Queue\Serializes_Models;
use Illuminate\Support\Arr;
use Webkul\Core\Repositories\Visit_Repository;
class Update_Create_Visit_Index implements Should_Queue
{
    use Dispatchable;
    use Interacts_With_Queue;
    use Queueable;
    use Serializes_Models;
    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $log
     * @return void
     */
    public function __construct(protected $model, protected $log)
    {
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $visit_repository = app(Visit_Repository::class);
        $last_visit = $visit_repository->where(Arr::only($this->log, ['method', 'url', 'ip', 'visitor_id', 'visitor_type', 'channel_id']))->latest()->first();
        if ($last_visit?->created_at->is_today()) {
            return;
        }
        if ($this->model !== null && method_exists($this->model, 'visitLogs')) {
            $visit = $this->model->visit_logs()->create($this->log);
            $visit->channel_id = $this->log['channel_id'];
            $visit->save();
        } else {
            $visit_repository->create($this->log);
        }
    }
}